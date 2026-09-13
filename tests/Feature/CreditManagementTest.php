<?php

namespace Tests\Feature;

use App\Models\BarreAchat;
use App\Models\BaremeVersion;
use App\Models\Client;
use App\Models\Credit;
use App\Models\JourneeFinanciere;
use App\Models\MouvementFinancier;
use App\Models\RemboursementCredit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
    }

    private function creerBaremeReel(): BaremeVersion
    {
        return BaremeVersion::creerNouvelleVersion('Barème test', [
            ['densite_min' => '18.59', 'densite_max' => '18.64', 'carat' => '22.80'],
        ]);
    }

    public function test_octroyer_un_credit_debite_la_tresorerie(): void
    {
        $gerant = $this->userWithRole('gerant');
        $client = Client::factory()->create();
        JourneeFinanciere::ouvrirJournee(10000000, null, $gerant);

        $response = $this->actingAs($gerant)->post('/credits', [
            'client_id' => $client->id,
            'date_credit' => now(),
            'montant_accorde' => 5000000,
            'montant_remis' => 5000000,
            'mode_paiement' => 'especes',
        ]);

        $credit = Credit::first();
        $response->assertRedirect(route('credits.show', $credit));
        $this->assertSame('5000000.00', $credit->montant_remis);
        $this->assertSame(5000000.0, $credit->solde());
        $this->assertSame('En cours', $credit->statutLibelle());

        $journee = JourneeFinanciere::first();
        $this->assertSame(1, $journee->mouvements()->where('nature', 'credit_octroi')->count());
        $this->assertEqualsWithDelta(5000000.0, $journee->fresh()->soldeDisponible(), 0.01);
    }

    public function test_octroyer_un_credit_sans_journee_ouverte_est_bloque(): void
    {
        $gerant = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        $response = $this->actingAs($gerant)->post('/credits', [
            'client_id' => $client->id,
            'date_credit' => now(),
            'montant_accorde' => 5000000,
            'montant_remis' => 5000000,
        ]);

        $response->assertSessionHasErrors('credit');
        $this->assertSame(0, Credit::count());
    }

    public function test_remboursement_en_especes_partiel_puis_total(): void
    {
        $gerant = $this->userWithRole('gerant');
        $client = Client::factory()->create();
        JourneeFinanciere::ouvrirJournee(10000000, null, $gerant);

        $this->actingAs($gerant)->post('/credits', [
            'client_id' => $client->id,
            'date_credit' => now(),
            'montant_accorde' => 5000000,
            'montant_remis' => 5000000,
            'mode_paiement' => 'especes',
        ]);
        $credit = Credit::first();

        $this->actingAs($gerant)->post("/credits/{$credit->id}/remboursements", [
            'mode' => 'especes',
            'date_remboursement' => now(),
            'montant_especes' => 1000000,
            'mode_paiement' => 'especes',
        ])->assertRedirect();

        $credit->refresh();
        $this->assertSame('1000000.00', $credit->montant_rembourse);
        $this->assertSame(4000000.0, $credit->solde());
        $this->assertSame('Partiellement remboursé', $credit->statutLibelle());

        $this->actingAs($gerant)->post("/credits/{$credit->id}/remboursements", [
            'mode' => 'especes',
            'date_remboursement' => now(),
            'montant_especes' => 4000000,
            'mode_paiement' => 'especes',
        ])->assertRedirect();

        $credit->refresh();
        $this->assertSame(0.0, $credit->solde());
        $this->assertSame('Soldé', $credit->statutLibelle());
    }

    public function test_remboursement_en_especes_superieur_au_solde_est_bloque(): void
    {
        $gerant = $this->userWithRole('gerant');
        $client = Client::factory()->create();
        JourneeFinanciere::ouvrirJournee(10000000, null, $gerant);

        $this->actingAs($gerant)->post('/credits', [
            'client_id' => $client->id,
            'date_credit' => now(),
            'montant_accorde' => 5000000,
            'montant_remis' => 5000000,
        ]);
        $credit = Credit::first();

        $response = $this->actingAs($gerant)->post("/credits/{$credit->id}/remboursements", [
            'mode' => 'especes',
            'date_remboursement' => now(),
            'montant_especes' => 6000000,
            'mode_paiement' => 'especes',
        ]);

        $response->assertSessionHasErrors('remboursement');
        $this->assertSame('0.00', $credit->fresh()->montant_rembourse);
    }

    public function test_remboursement_en_or_excedentaire_cree_un_reliquat_et_du_stock(): void
    {
        $this->creerBaremeReel();
        $gerant = $this->userWithRole('gerant');
        $client = Client::factory()->create();
        JourneeFinanciere::ouvrirJournee(10000000, null, $gerant);

        $this->actingAs($gerant)->post('/credits', [
            'client_id' => $client->id,
            'date_credit' => now(),
            'montant_accorde' => 5000000,
            'montant_remis' => 5000000,
        ]);
        $credit = Credit::first();

        // Poids 119.47 / eau 6.42 -> densité 18.60 -> carat 22.80 ; base 80000
        // -> PU 76 000 ; montant = 9 079 720 (> solde de 5 000 000).
        $response = $this->actingAs($gerant)->post("/credits/{$credit->id}/remboursements", [
            'mode' => 'or',
            'date_remboursement' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);
        $response->assertRedirect();

        $credit->refresh();
        $this->assertSame(0.0, $credit->solde());
        $this->assertSame('Soldé', $credit->statutLibelle());

        $remboursement = RemboursementCredit::first();
        $this->assertSame('9079720.00', $remboursement->montant_or);
        $this->assertSame('5000000.00', $remboursement->montant_total);
        $this->assertSame('4079720.00', $remboursement->reliquat);

        $barre = $remboursement->lignesOr()->first();
        $this->assertNotNull($barre);
        $this->assertNull($barre->operation_achat_id);
        $this->assertSame('en_stock', $barre->statut);
        $this->assertTrue(BarreAchat::disponible()->where('id', $barre->id)->exists());

        // Le reliquat est une vraie sortie de caisse.
        $this->assertSame(1, MouvementFinancier::where('nature', 'credit_reliquat')->where('sens', 'sortie')->count());
    }

    public function test_remboursement_or_plus_especes_complement_solde_exactement(): void
    {
        $this->creerBaremeReel();
        $gerant = $this->userWithRole('gerant');
        $client = Client::factory()->create();
        JourneeFinanciere::ouvrirJournee(10000000, null, $gerant);

        $this->actingAs($gerant)->post('/credits', [
            'client_id' => $client->id,
            'date_credit' => now(),
            'montant_accorde' => 10000000,
            'montant_remis' => 10000000,
        ]);
        $credit = Credit::first();

        // Or seul insuffisant (poids réduit pour ne couvrir qu'une partie) : on
        // choisit un poids qui donne un montant < solde, puis on complète.
        $response = $this->actingAs($gerant)->post("/credits/{$credit->id}/remboursements", [
            'mode' => 'or_especes',
            'date_remboursement' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '50', 'eau' => '2.688']], // densité ~18.60 -> carat 22.80 ; montant = 50*76000 = 3 800 000
            'montant_especes' => 1000000,
            'mode_paiement' => 'especes',
        ]);
        $response->assertRedirect();

        $credit->refresh();
        $remboursement = RemboursementCredit::first();
        $this->assertSame('3800000.00', $remboursement->montant_or);
        $this->assertSame('1000000.00', $remboursement->montant_especes);
        $this->assertSame('4800000.00', $remboursement->montant_total);
        $this->assertSame('0.00', $remboursement->reliquat);
        $this->assertSame(4800000.0, (float) $credit->montant_rembourse);
        $this->assertSame('Partiellement remboursé', $credit->statutLibelle());
    }

    public function test_annuler_est_bloque_des_qu_un_remboursement_existe(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');
        $client = Client::factory()->create();
        JourneeFinanciere::ouvrirJournee(10000000, null, $proprietaire);

        $this->actingAs($proprietaire)->post('/credits', [
            'client_id' => $client->id,
            'date_credit' => now(),
            'montant_accorde' => 5000000,
            'montant_remis' => 5000000,
        ]);
        $credit = Credit::first();

        $this->actingAs($proprietaire)->post("/credits/{$credit->id}/remboursements", [
            'mode' => 'especes',
            'date_remboursement' => now(),
            'montant_especes' => 1000000,
            'mode_paiement' => 'especes',
        ]);

        $response = $this->actingAs($proprietaire)->patch("/credits/{$credit->id}/annuler");
        $response->assertSessionHasErrors('credit');
        $this->assertNull($credit->fresh()->annule_at);
    }

    public function test_annuler_avant_tout_remboursement_restitue_la_tresorerie(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');
        $client = Client::factory()->create();
        JourneeFinanciere::ouvrirJournee(10000000, null, $proprietaire);

        $this->actingAs($proprietaire)->post('/credits', [
            'client_id' => $client->id,
            'date_credit' => now(),
            'montant_accorde' => 5000000,
            'montant_remis' => 5000000,
        ]);
        $credit = Credit::first();
        $journee = JourneeFinanciere::first();
        $soldeApresOctroi = $journee->fresh()->soldeDisponible();

        $this->actingAs($proprietaire)->patch("/credits/{$credit->id}/annuler")->assertRedirect();

        $credit->refresh();
        $this->assertNotNull($credit->annule_at);
        $this->assertEqualsWithDelta($soldeApresOctroi + 5000000, $journee->fresh()->soldeDisponible(), 0.01);
    }

    public function test_gerant_cannot_annuler_a_credit(): void
    {
        $gerant = $this->userWithRole('gerant');
        $client = Client::factory()->create();
        JourneeFinanciere::ouvrirJournee(10000000, null, $gerant);

        $this->actingAs($gerant)->post('/credits', [
            'client_id' => $client->id,
            'date_credit' => now(),
            'montant_accorde' => 5000000,
            'montant_remis' => 5000000,
        ]);
        $credit = Credit::first();

        $this->actingAs($gerant)->patch("/credits/{$credit->id}/annuler")->assertForbidden();
    }

    private function bureauAvecProprietaire(string $nomBureau): array
    {
        $bureau = \App\Models\Bureau::create(['nom' => $nomBureau, 'actif' => true]);

        $proprietaire = \App\Models\User::factory()->create(['bureau_id' => $bureau->id]);
        $proprietaire->assignRole('proprietaire');
        $proprietaire->givePermissionTo(config('role_permissions.proprietaire', []));
        $bureau->update(['proprietaire_id' => $proprietaire->id]);

        return [$bureau, $proprietaire];
    }

    public function test_gold_repayment_bar_is_isolated_from_other_bureaux(): void
    {
        $this->creerBaremeReel();
        [$bureauA, $proprioA] = $this->bureauAvecProprietaire('Bureau A');
        [, $proprioB] = $this->bureauAvecProprietaire('Bureau B');

        $client = Client::factory()->create(['bureau_id' => $bureauA->id]);
        JourneeFinanciere::ouvrirJournee(10000000, null, $proprioA);

        $this->actingAs($proprioA)->post('/credits', [
            'client_id' => $client->id,
            'date_credit' => now(),
            'montant_accorde' => 5000000,
            'montant_remis' => 5000000,
        ]);
        $credit = Credit::withoutGlobalScope('bureau')->where('bureau_id', $bureauA->id)->first();

        $this->actingAs($proprioA)->post("/credits/{$credit->id}/remboursements", [
            'mode' => 'or',
            'date_remboursement' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);

        $barre = BarreAchat::withoutGlobalScopes()->whereNotNull('remboursement_credit_id')->first();
        $this->assertNotNull($barre);

        $this->actingAs($proprioB);
        $this->assertFalse(BarreAchat::disponible()->where('id', $barre->id)->exists());
    }
}
