<?php

namespace Tests\Feature;

use App\Models\BaremeVersion;
use App\Models\Client;
use App\Models\JourneeFinanciere;
use App\Models\MouvementFinancier;
use App\Models\OperationAchat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TresorerieManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
    }

    public function test_space_formatted_montant_initial_is_accepted(): void
    {
        // Le formatage en direct (cf. layouts/admin.blade.php) insère des
        // espaces tous les 3 chiffres ("100 000 000") : le serveur doit les
        // retirer avant validation plutôt que de rejeter le champ.
        $caissier = $this->userWithRole('gerant');

        $this->actingAs($caissier)->post('/tresorerie/ouvrir', ['montant_initial' => '100 000 000'])
            ->assertRedirect(route('tresorerie.index'));

        $this->assertSame('100000000.00', JourneeFinanciere::first()->mouvements()->first()->montant);
    }

    public function test_only_one_journee_can_be_open_at_a_time(): void
    {
        $caissier = $this->userWithRole('gerant');

        $this->actingAs($caissier)->post('/tresorerie/ouvrir', ['montant_initial' => 100000000])
            ->assertRedirect(route('tresorerie.index'));

        $response = $this->actingAs($caissier)->post('/tresorerie/ouvrir', ['montant_initial' => 50000000]);
        $response->assertSessionHasErrors('journee');
        $this->assertSame(1, JourneeFinanciere::count());
    }

    public function test_fonds_initial_is_recorded_as_a_movement_not_a_header_field(): void
    {
        $caissier = $this->userWithRole('gerant');
        $this->actingAs($caissier)->post('/tresorerie/ouvrir', ['montant_initial' => 100000000]);

        $journee = JourneeFinanciere::first();
        $this->assertSame(1, $journee->mouvements()->count());
        $mouvement = $journee->mouvements()->first();
        $this->assertSame('fonds_initial', $mouvement->nature);
        $this->assertSame('entree', $mouvement->sens);
        $this->assertSame('100000000.00', $mouvement->montant);
        $this->assertSame(100000000.0, $journee->soldeDisponible());
    }

    public function test_approvisionnement_increases_solde_disponible_immediately(): void
    {
        $caissier = $this->userWithRole('gerant');
        $this->actingAs($caissier)->post('/tresorerie/ouvrir', ['montant_initial' => 100000000]);
        $journee = JourneeFinanciere::first();

        $this->actingAs($caissier)->post("/tresorerie/{$journee->id}/approvisionnement", ['montant' => 50000000])
            ->assertRedirect();

        $this->assertSame(150000000.0, $journee->soldeDisponible());
    }

    public function test_paying_an_achat_automatically_debits_the_fund(): void
    {
        BaremeVersion::creerNouvelleVersion('B', [
            ['densite_min' => '18.59', 'densite_max' => '18.64', 'carat' => '22.80'],
        ]);
        $caissier = $this->userWithRole('gerant');
        $operateur = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        $this->actingAs($caissier)->post('/tresorerie/ouvrir', ['montant_initial' => 100000000]);
        $journee = JourneeFinanciere::first();

        $this->actingAs($operateur)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);
        $achat = OperationAchat::first();
        $this->assertSame('validee', $achat->statut);

        // montant_total = 9 079 720.00 ; on paie tout — l'opération étant
        // déjà validée dès sa création, le paiement débite le fonds direct.
        $this->assertSame(1, $journee->fresh()->mouvements()->count());

        $this->actingAs($operateur)->patch("/achats/{$achat->id}/paiement", ['montant' => $achat->montant_total, 'mode_paiement' => 'especes']);

        $journee->refresh();
        $this->assertSame(2, $journee->mouvements()->count());
        $mouvementAchat = $journee->mouvements()->where('nature', 'paiement_achat')->first();
        $this->assertSame('sortie', $mouvementAchat->sens);
        $this->assertSame((string) $achat->fresh()->montant_paye, $mouvementAchat->montant);
        $this->assertTrue($mouvementAchat->estAutomatique());

        $soldeAttendu = 100000000.0 - (float) $achat->fresh()->montant_total;
        $this->assertEqualsWithDelta($soldeAttendu, $journee->soldeDisponible(), 0.01);
    }

    public function test_payment_without_open_journee_is_blocked(): void
    {
        // Un paiement d'achat est un décaissement réel : impossible de le
        // faire "disparaître" du suivi de trésorerie faute de journée
        // ouverte (confirmé par l'entreprise après un achat payé alors
        // qu'aucun fonds n'avait jamais été ouvert).
        BaremeVersion::creerNouvelleVersion('B', [
            ['densite_min' => '18.59', 'densite_max' => '18.64', 'carat' => '22.80'],
        ]);
        $operateur = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        $this->actingAs($operateur)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);
        $achat = OperationAchat::first();

        $response = $this->actingAs($operateur)->patch("/achats/{$achat->id}/paiement", ['montant' => $achat->montant_total, 'mode_paiement' => 'especes']);
        $response->assertSessionHasErrors('montant');
        $this->assertSame(0, MouvementFinancier::count());
        $this->assertSame('0.00', $achat->fresh()->montant_paye);
    }

    public function test_payment_exceeding_available_funds_is_blocked(): void
    {
        BaremeVersion::creerNouvelleVersion('B', [
            ['densite_min' => '18.59', 'densite_max' => '18.64', 'carat' => '22.80'],
        ]);
        $operateur = $this->userWithRole('gerant');
        $client = Client::factory()->create();
        JourneeFinanciere::ouvrirJournee(1000000, null, $operateur);

        $this->actingAs($operateur)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']], // montant = 9 079 720.00
        ]);
        $achat = OperationAchat::first();

        // Solde disponible (1 000 000) très inférieur au montant total (9 079 720).
        $response = $this->actingAs($operateur)->patch("/achats/{$achat->id}/paiement", ['montant' => $achat->montant_total, 'mode_paiement' => 'especes']);
        $response->assertSessionHasErrors('montant');
        // Seul le mouvement "fonds_initial" de l'ouverture de journée existe : le
        // paiement bloqué n'a créé aucun mouvement supplémentaire.
        $this->assertSame(1, MouvementFinancier::count());
        $this->assertSame('0.00', $achat->fresh()->montant_paye);
    }

    public function test_cannot_delete_an_automatic_movement(): void
    {
        BaremeVersion::creerNouvelleVersion('B', [
            ['densite_min' => '18.59', 'densite_max' => '18.64', 'carat' => '22.80'],
        ]);
        $caissier = $this->userWithRole('gerant');
        $operateur = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        $this->actingAs($caissier)->post('/tresorerie/ouvrir', ['montant_initial' => 100000000]);
        $this->actingAs($operateur)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);
        $achat = OperationAchat::first();
        $this->actingAs($operateur)->patch("/achats/{$achat->id}/paiement", ['montant' => 1000000, 'mode_paiement' => 'especes']);

        $mouvementAuto = MouvementFinancier::where('nature', 'paiement_achat')->first();

        $response = $this->actingAs($caissier)->delete("/tresorerie/mouvement/{$mouvementAuto->id}");
        $response->assertSessionHasErrors('journee');
        $this->assertDatabaseHas('mouvement_financiers', ['id' => $mouvementAuto->id]);
    }

    public function test_fermer_journee_computes_ecart_correctly(): void
    {
        $caissier = $this->userWithRole('gerant');
        $this->actingAs($caissier)->post('/tresorerie/ouvrir', ['montant_initial' => 100000000]);
        $journee = JourneeFinanciere::first();

        $response = $this->actingAs($caissier)->patch("/tresorerie/{$journee->id}/fermer", ['solde_physique' => 99500000]);
        $response->assertRedirect(route('tresorerie.index'));

        $journee->refresh();
        $this->assertSame('fermee', $journee->statut);
        $this->assertSame('100000000.00', $journee->solde_theorique_fermeture);
        $this->assertSame('99500000.00', $journee->solde_physique_fermeture);
        $this->assertSame('-500000.00', $journee->ecart_fermeture);
    }

    public function test_gerant_without_fonds_gerer_permission_can_view_but_not_manage_tresorerie(): void
    {
        // Les permissions sont individuelles : un gérant à qui on a retiré
        // fonds.gerer (via « Assigner permissions ») garde la consultation.
        $gerantRestreint = $this->userWithRole('gerant');
        $gerantRestreint->syncPermissions(['fonds.voir']);

        $this->actingAs($gerantRestreint)->get('/tresorerie')->assertOk();
        $this->actingAs($gerantRestreint)->post('/tresorerie/ouvrir', ['montant_initial' => 1000])->assertForbidden();
    }

    public function test_solde_faible_is_relative_to_the_days_total_entrees(): void
    {
        $caissier = $this->userWithRole('gerant');
        $this->actingAs($caissier)->post('/tresorerie/ouvrir', ['montant_initial' => 100000]);
        $journee = JourneeFinanciere::first();

        $this->assertFalse($journee->soldeFaible());
        $this->assertFalse($journee->estEnRupture());

        // Dépense 95% du fonds : il ne reste que 5% -> solde faible.
        $this->actingAs($caissier)->post("/tresorerie/{$journee->id}/mouvement", [
            'sens' => 'sortie', 'montant' => 95000, 'libelle' => 'Test',
        ]);
        $journee->refresh();

        $this->assertTrue($journee->soldeFaible());
        $this->assertFalse($journee->estEnRupture());
    }

    public function test_estEnRupture_when_solde_reaches_zero(): void
    {
        $caissier = $this->userWithRole('gerant');
        $this->actingAs($caissier)->post('/tresorerie/ouvrir', ['montant_initial' => 100000]);
        $journee = JourneeFinanciere::first();

        $this->actingAs($caissier)->post("/tresorerie/{$journee->id}/mouvement", [
            'sens' => 'sortie', 'montant' => 100000, 'libelle' => 'Tout dépenser',
        ]);
        $journee->refresh();

        $this->assertTrue($journee->estEnRupture());
        // La rupture prime sur "solde faible" : pas les deux alertes en même temps.
        $this->assertFalse($journee->soldeFaible());
    }

    public function test_low_balance_alert_appears_on_tresorerie_and_dashboard_pages(): void
    {
        $caissier = $this->userWithRole('gerant');
        $this->actingAs($caissier)->post('/tresorerie/ouvrir', ['montant_initial' => 100000]);
        $journee = JourneeFinanciere::first();
        $this->actingAs($caissier)->post("/tresorerie/{$journee->id}/mouvement", [
            'sens' => 'sortie', 'montant' => 95000, 'libelle' => 'Test',
        ]);

        $this->actingAs($caissier)->get('/tresorerie')->assertOk()->assertSee('Solde faible');
        $this->actingAs($caissier)->get('/')->assertOk()->assertSee('Solde faible');
    }
}
