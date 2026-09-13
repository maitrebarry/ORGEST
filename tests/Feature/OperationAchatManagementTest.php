<?php

namespace Tests\Feature;

use App\Models\BaremeVersion;
use App\Models\Client;
use App\Models\JourneeFinanciere;
use App\Models\OperationAchat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationAchatManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
    }

    /**
     * Barème réel (extrait) permettant de couvrir les densités des factures
     * réelles utilisées dans ces tests.
     */
    private function creerBaremeReel(): BaremeVersion
    {
        return BaremeVersion::creerNouvelleVersion('Barème test', [
            ['densite_min' => '18.59', 'densite_max' => '18.64', 'carat' => '22.80'],
            ['densite_min' => '18.53', 'densite_max' => '18.58', 'carat' => '22.70'],
            ['densite_min' => '18.65', 'densite_max' => '18.71', 'carat' => '22.90'],
        ]);
    }

    public function test_cannot_create_an_achat_without_an_active_bareme(): void
    {
        $operateur = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        $response = $this->actingAs($operateur)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => 119.47, 'eau' => 6.42]],
        ]);

        $response->assertSessionHasErrors('barres');
        $this->assertSame(0, OperationAchat::count());
    }

    public function test_creates_an_achat_reproducing_the_real_invoice_calculation(): void
    {
        $this->creerBaremeReel();
        $operateur = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        // Facture réelle ACHAT n°00-2024-7176 (4 premières lignes)
        $response = $this->actingAs($operateur)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [
                ['poids' => '119.47', 'eau' => '6.42'],
                ['poids' => '90.15', 'eau' => '4.86'],
                ['poids' => '54.88', 'eau' => '2.94'],
                ['poids' => '49.91', 'eau' => '2.68'],
            ],
        ]);

        $operation = OperationAchat::first();
        $response->assertRedirect(route('achats.show', $operation));

        $this->assertSame('validee', $operation->statut);
        $this->assertNotNull($operation->validee_at);
        $this->assertCount(4, $operation->barres);

        $barre1 = $operation->barres()->where('numero_barre', 1)->first();
        $this->assertSame('18.60', $barre1->densite_tronquee);
        $this->assertSame('22.80', $barre1->carat);
        $this->assertSame('76000.00', $barre1->prix_unitaire);

        $barre3 = $operation->barres()->where('numero_barre', 3)->first();
        $this->assertSame('18.66', $barre3->densite_tronquee);
        $this->assertSame('22.90', $barre3->carat);
        $this->assertSame('76333.33', $barre3->prix_unitaire);

        // Poids total = somme simple des poids (cahier des charges §3.6)
        $this->assertSame('314.410', $operation->poids_total);
    }

    public function test_rejects_the_whole_operation_if_one_barre_density_is_outside_the_bareme(): void
    {
        $this->creerBaremeReel();
        $operateur = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        $response = $this->actingAs($operateur)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [
                ['poids' => '119.47', 'eau' => '6.42'], // densité 18.60, dans le barème
                ['poids' => '10', 'eau' => '1'],          // densité 10.00, hors barème
            ],
        ]);

        $response->assertSessionHasErrors('barres');
        // Aucune validation silencieuse : rien n'est enregistré, même la barre valide.
        $this->assertSame(0, OperationAchat::count());
    }

    public function test_manual_carat_and_prix_unitaire_override_the_bareme_lookup(): void
    {
        $this->creerBaremeReel();
        $operateur = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        // Densité 10.00 hors barème, mais carat et prix unitaire saisis
        // directement par l'opérateur : aucun rejet, aucune recherche dans
        // le barème pour cette barre.
        $response = $this->actingAs($operateur)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [
                ['poids' => '10', 'eau' => '1', 'carat' => '18,00', 'prix_unitaire' => '60 000'],
            ],
        ]);

        $operation = OperationAchat::first();
        $response->assertRedirect(route('achats.show', $operation));

        $barre = $operation->barres->first();
        $this->assertSame('18.00', $barre->carat);
        $this->assertSame('60000.00', $barre->prix_unitaire);
        $this->assertNull($barre->bareme_ligne_id);
        // montant = poids (10) x prix_unitaire (60000) = 600 000
        $this->assertSame('600000.00', $barre->montant);
        $this->assertSame('600000.00', $operation->montant_total);
    }

    public function test_achat_is_validated_immediately_upon_creation(): void
    {
        $this->creerBaremeReel();
        $operateur = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        $this->actingAs($operateur)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);
        $operation = OperationAchat::first();

        $this->assertSame('validee', $operation->statut);
        $this->assertNotNull($operation->validee_at);
        $this->assertSame($operateur->id, $operation->validee_par);
    }

    public function test_annuler_is_blocked_once_a_barre_has_been_sold(): void
    {
        $this->creerBaremeReel();
        $operateur = $this->userWithRole('gerant');
        $proprietaire = $this->userWithRole('proprietaire');
        $client = Client::factory()->create();

        $this->actingAs($operateur)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);
        $operation = OperationAchat::first();
        $operation->barres()->first()->update(['statut' => 'vendu']);

        $response = $this->actingAs($proprietaire)->patch("/achats/{$operation->id}/annuler");
        $response->assertSessionHasErrors('achat');
        $this->assertSame('validee', $operation->fresh()->statut);
    }

    public function test_enregistrer_paiement_tracks_credit_owed_to_client(): void
    {
        $this->creerBaremeReel();
        $operateur = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        $this->actingAs($operateur)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']], // montant = 9 079 720.00
        ]);
        $operation = OperationAchat::first();
        $this->assertSame('9079720.00', $operation->montant_total);
        $this->assertSame(9079720.0, $operation->reste());

        JourneeFinanciere::ouvrirJournee(10000000, null, $operateur);

        $this->actingAs($operateur)->patch("/achats/{$operation->id}/paiement", ['montant' => 5000000, 'mode_paiement' => 'especes'])->assertRedirect();
        $operation->refresh();
        $this->assertSame('5000000.00', $operation->montant_paye);
        $this->assertSame(4079720.0, $operation->reste());

        // Interdiction de dépasser le montant total (protection contre la sur-saisie)
        $response = $this->actingAs($operateur)->patch("/achats/{$operation->id}/paiement", ['montant' => 5000000, 'mode_paiement' => 'especes']);
        $response->assertSessionHasErrors('montant');
        $this->assertSame('5000000.00', $operation->fresh()->montant_paye);
    }

    public function test_modifier_barre_recalculates_montant_and_total(): void
    {
        $this->creerBaremeReel();
        $operateur = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        $this->actingAs($operateur)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']], // montant = 9 079 720.00
        ]);
        $operation = OperationAchat::first();
        $barre = $operation->barres->first();

        $response = $this->actingAs($operateur)->patch("/achats/{$operation->id}/barres/{$barre->id}", [
            'densite_tronquee' => '18,60',
            'carat' => '23,00',
            'prix_unitaire' => '77 000',
        ]);
        $response->assertRedirect();

        $barre->refresh();
        $operation->refresh();
        $this->assertSame('23.00', $barre->carat);
        $this->assertSame('77000.00', $barre->prix_unitaire);
        // montant = poids (119.47) x prix_unitaire (77000) = 9 199 190.00
        $this->assertSame('9199190.00', $barre->montant);
        $this->assertSame('9199190.00', $operation->montant_total);
    }

    public function test_modifier_barre_is_blocked_below_montant_paye(): void
    {
        $this->creerBaremeReel();
        $operateur = $this->userWithRole('gerant');
        $client = Client::factory()->create();
        JourneeFinanciere::ouvrirJournee(10000000, null, $operateur);

        $this->actingAs($operateur)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']], // montant = 9 079 720.00
        ]);
        $operation = OperationAchat::first();
        $barre = $operation->barres->first();

        $this->actingAs($operateur)->patch("/achats/{$operation->id}/paiement", ['montant' => 9000000, 'mode_paiement' => 'especes']);

        // Un prix unitaire très bas ferait tomber le nouveau montant total
        // (119.47 x 1000 = 119 470) sous les 9 000 000 déjà payés.
        $response = $this->actingAs($operateur)->patch("/achats/{$operation->id}/barres/{$barre->id}", [
            'densite_tronquee' => '18,60',
            'carat' => '22,80',
            'prix_unitaire' => '1000',
        ]);
        $response->assertSessionHasErrors('barre');
        $this->assertSame('76000.00', $barre->fresh()->prix_unitaire);
    }

    public function test_gerant_without_achats_creer_permission_cannot_create_an_achat(): void
    {
        // Les permissions sont individuelles : un gérant à qui on a retiré
        // achats.creer (via « Assigner permissions ») ne peut plus créer.
        $this->creerBaremeReel();
        $gerantRestreint = $this->userWithRole('gerant');
        $gerantRestreint->syncPermissions(['achats.voir']);
        $client = Client::factory()->create();

        $this->actingAs($gerantRestreint)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ])->assertForbidden();
    }

    public function test_space_formatted_prix_base_is_accepted(): void
    {
        // Le formatage en direct (cf. layouts/admin.blade.php) insère des
        // espaces tous les 3 chiffres ("80 000") : le serveur doit les
        // retirer avant validation.
        $this->creerBaremeReel();
        $operateur = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        $response = $this->actingAs($operateur)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => '80 000',
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);

        $operation = OperationAchat::first();
        $this->assertNotNull($operation, "L'espace de formatage a fait échouer la création.");
        $this->assertSame('80000.00', $operation->prix_base);
    }

    public function test_comma_decimal_input_is_accepted(): void
    {
        // Clavier français : un utilisateur tape souvent une virgule plutôt
        // qu'un point pour les décimales.
        $this->creerBaremeReel();
        $operateur = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        $response = $this->actingAs($operateur)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => '80000',
            'barres' => [['poids' => '119,47', 'eau' => '6,42']],
        ]);

        $operation = OperationAchat::first();
        $this->assertNotNull($operation, 'La virgule décimale a fait échouer la création.');
        $this->assertSame('18.60', $operation->barres->first()->densite_tronquee);
    }

    public function test_entirely_empty_barre_rows_are_ignored(): void
    {
        $this->creerBaremeReel();
        $operateur = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        $this->actingAs($operateur)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [
                ['poids' => '119.47', 'eau' => '6.42'],
                ['poids' => '', 'eau' => ''],
            ],
        ]);

        $this->assertCount(1, OperationAchat::first()->barres);
    }

    public function test_pdf_includes_bambara_and_amounts_paid_and_remaining(): void
    {
        $this->creerBaremeReel();
        $operateur = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        $this->actingAs($operateur)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']], // montant = 9 079 720.00
        ]);
        $achat = OperationAchat::first();
        JourneeFinanciere::ouvrirJournee(10000000, null, $operateur);
        $this->actingAs($operateur)->patch("/achats/{$achat->id}/paiement", ['montant' => 4000000, 'mode_paiement' => 'especes']);
        $achat->refresh();

        $html = view('pdf.achat', ['achat' => $achat->load('barres', 'client', 'user', 'bureau')])->render();

        // 9 079 720,00 ÷ 5 = 1 815 944,00
        $this->assertStringContainsString('Bambara', $html);
        $this->assertStringContainsString('1 815 944,00', $html);
        $this->assertStringContainsString('Montant payé', $html);
        $this->assertStringContainsString('4 000 000,00', $html);
        $this->assertStringContainsString('Reste à payer', $html);
        $this->assertStringContainsString('5 079 720,00', $html);
    }
}
