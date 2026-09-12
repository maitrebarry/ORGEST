<?php

namespace Tests\Feature;

use App\Models\BarreAchat;
use App\Models\BaremeVersion;
use App\Models\Client;
use App\Models\JourneeFinanciere;
use App\Models\MouvementFinancier;
use App\Models\OperationVente;
use App\Models\User;
use App\Support\CalculOr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationVenteManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
    }

    /**
     * Barème couvrant les densités des deux lignes réelles de la facture
     * VENTE n°00-2024-7191 utilisées dans CalculOrTest.
     */
    private function creerBaremeReel(): BaremeVersion
    {
        return BaremeVersion::creerNouvelleVersion('Barème test', [
            ['densite_min' => '19.10', 'densite_max' => '19.16', 'carat' => '23.60'],
            ['densite_min' => '18.22', 'densite_max' => '18.27', 'carat' => '22.20'],
        ]);
    }

    /**
     * Fait passer une barre par un achat réel (seul moyen d'en créer une :
     * pas de fabrique directe, pour ne jamais désynchroniser un test du vrai
     * flux de calcul/carat).
     */
    private function creerBarreEnStock(User $operateur, Client $vendeur, string $poids, string $eau): BarreAchat
    {
        $this->actingAs($operateur)->post('/achats', [
            'client_id' => $vendeur->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => $poids, 'eau' => $eau]],
        ]);

        return BarreAchat::disponible()->latest('id')->first();
    }

    public function test_creates_a_vente_reproducing_the_real_invoice_calculation(): void
    {
        $this->creerBaremeReel();
        $gerant = $this->userWithRole('gerant');
        $vendeur = Client::factory()->create();
        $acheteur = Client::factory()->create();

        // Facture réelle VENTE n°00-2024-7191, ligne 1 : 84.26/4.40 -> carat 23.60
        $barre = $this->creerBarreEnStock($gerant, $vendeur, '84.26', '4.40');

        $response = $this->actingAs($gerant)->post('/ventes', [
            'client_id' => $acheteur->id,
            'date_operation' => now(),
            'prix_base' => 82000,
            'barres_ids' => [$barre->id],
        ]);

        $vente = OperationVente::first();
        $response->assertRedirect(route('ventes.show', $vente));

        $this->assertSame('validee', $vente->statut);
        $this->assertNotNull($vente->validee_at);
        $this->assertSame($gerant->id, $vente->validee_par);

        $barre->refresh();
        $this->assertSame('vendu', $barre->statut);
        $this->assertSame($vente->id, $barre->operation_vente_id);
        // U/BASE observé sur la vraie facture pour carat 23.60/base 82000.
        $this->assertSame('80633.33', $barre->prix_unitaire_vente);

        $montantAttendu = CalculOr::montant('84.26', '80633.33');
        $this->assertSame($montantAttendu, $barre->montant_vente);
        $this->assertSame($montantAttendu, $vente->montant_total);
    }

    public function test_rejects_the_whole_operation_if_a_selected_barre_is_no_longer_available(): void
    {
        $this->creerBaremeReel();
        $gerant = $this->userWithRole('gerant');
        $vendeur = Client::factory()->create();
        $acheteur = Client::factory()->create();

        $barreDisponible = $this->creerBarreEnStock($gerant, $vendeur, '84.26', '4.40');
        $barreDejaVendue = $this->creerBarreEnStock($gerant, $vendeur, '62.66', '3.43');
        $barreDejaVendue->update(['statut' => 'vendu']); // simule une vente concurrente

        $response = $this->actingAs($gerant)->post('/ventes', [
            'client_id' => $acheteur->id,
            'date_operation' => now(),
            'prix_base' => 82000,
            'barres_ids' => [$barreDisponible->id, $barreDejaVendue->id],
        ]);

        $response->assertSessionHasErrors('barres_ids');
        $this->assertSame(0, OperationVente::count());
        $this->assertSame('en_stock', $barreDisponible->fresh()->statut); // pas touchée non plus
    }

    public function test_annuler_returns_barres_to_stock(): void
    {
        $this->creerBaremeReel();
        $gerant = $this->userWithRole('gerant');
        $proprietaire = $this->userWithRole('proprietaire');
        $vendeur = Client::factory()->create();
        $acheteur = Client::factory()->create();

        $barre = $this->creerBarreEnStock($gerant, $vendeur, '84.26', '4.40');

        $this->actingAs($gerant)->post('/ventes', [
            'client_id' => $acheteur->id,
            'date_operation' => now(),
            'prix_base' => 82000,
            'barres_ids' => [$barre->id],
        ]);
        $vente = OperationVente::first();

        $this->actingAs($proprietaire)->patch("/ventes/{$vente->id}/annuler")->assertRedirect();

        $vente->refresh();
        $barre->refresh();
        $this->assertSame('annulee', $vente->statut);
        $this->assertSame('en_stock', $barre->statut);
        $this->assertNull($barre->operation_vente_id);
        $this->assertNull($barre->montant_vente);
    }

    public function test_enregistrer_paiement_tracks_credit_owed_by_buyer(): void
    {
        $this->creerBaremeReel();
        $gerant = $this->userWithRole('gerant');
        $vendeur = Client::factory()->create();
        $acheteur = Client::factory()->create();

        $barre = $this->creerBarreEnStock($gerant, $vendeur, '84.26', '4.40');

        $this->actingAs($gerant)->post('/ventes', [
            'client_id' => $acheteur->id,
            'date_operation' => now(),
            'prix_base' => 82000,
            'barres_ids' => [$barre->id],
        ]);
        $vente = OperationVente::first();
        $montantTotal = (float) $vente->montant_total;

        $this->actingAs($gerant)->patch("/ventes/{$vente->id}/paiement", ['montant' => 1000000, 'mode_paiement' => 'especes'])->assertRedirect();
        $vente->refresh();

        $this->assertSame('1000000.00', $vente->montant_paye);
        $this->assertEqualsWithDelta($montantTotal - 1000000, $vente->reste(), 0.01);

        // Interdiction de dépasser le montant total.
        $response = $this->actingAs($gerant)->patch("/ventes/{$vente->id}/paiement", ['montant' => $montantTotal, 'mode_paiement' => 'especes']);
        $response->assertSessionHasErrors('montant');
    }

    public function test_treasury_encaissement_vente_credits_the_fund(): void
    {
        $this->creerBaremeReel();
        $gerant = $this->userWithRole('gerant');
        $vendeur = Client::factory()->create();
        $acheteur = Client::factory()->create();

        $this->actingAs($gerant)->post('/tresorerie/ouvrir', ['montant_initial' => 100000]);
        $journee = JourneeFinanciere::first();

        $barre = $this->creerBarreEnStock($gerant, $vendeur, '84.26', '4.40');
        $this->actingAs($gerant)->post('/ventes', [
            'client_id' => $acheteur->id,
            'date_operation' => now(),
            'prix_base' => 82000,
            'barres_ids' => [$barre->id],
        ]);
        $vente = OperationVente::first();

        $this->actingAs($gerant)->patch("/ventes/{$vente->id}/paiement", ['montant' => $vente->montant_total, 'mode_paiement' => 'especes']);

        $mouvement = MouvementFinancier::where('nature', 'encaissement_vente')->first();
        $this->assertNotNull($mouvement);
        $this->assertSame('entree', $mouvement->sens);
        $this->assertSame((string) $vente->fresh()->montant_paye, $mouvement->montant);

        $journee->refresh();
        $this->assertEqualsWithDelta(100000 + (float) $vente->montant_total, $journee->soldeDisponible(), 0.01);
    }

    public function test_pdf_includes_bambara_and_amounts_paid_and_remaining(): void
    {
        $this->creerBaremeReel();
        $gerant = $this->userWithRole('gerant');
        $vendeur = Client::factory()->create();
        $acheteur = Client::factory()->create();

        $barre = $this->creerBarreEnStock($gerant, $vendeur, '84.26', '4.40');
        $this->actingAs($gerant)->post('/ventes', [
            'client_id' => $acheteur->id,
            'date_operation' => now(),
            'prix_base' => 82000,
            'barres_ids' => [$barre->id],
        ]);
        $vente = OperationVente::first();
        $this->actingAs($gerant)->patch("/ventes/{$vente->id}/paiement", ['montant' => 1000000, 'mode_paiement' => 'especes']);
        $vente->refresh();

        $bambaraAttendu = CalculOr::arrondir(bcdiv((string) $vente->montant_total, '5', 4));
        $resteAttendu = number_format($vente->reste(), 2, ',', ' ');

        $html = view('pdf.vente', ['vente' => $vente->load('barres', 'client', 'user', 'bureau')])->render();

        $this->assertStringContainsString('Bambara', $html);
        $this->assertStringContainsString(number_format((float) $bambaraAttendu, 2, ',', ' '), $html);
        $this->assertStringContainsString('Montant encaissé', $html);
        $this->assertStringContainsString('1 000 000,00', $html);
        $this->assertStringContainsString('Reste à encaisser', $html);
        $this->assertStringContainsString($resteAttendu, $html);
    }
}
