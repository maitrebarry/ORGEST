<?php

namespace Tests\Feature;

use App\Models\BaremeVersion;
use App\Models\Bureau;
use App\Models\Client;
use App\Models\JourneeFinanciere;
use App\Models\OperationAchat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vérifie le cloisonnement réel des données métier entre bureaux
 * (clients, achats, barème, trésorerie) : ce que les autres tests de
 * gestion ne couvrent pas, puisqu'ils utilisent des utilisateurs sans
 * bureau_id (non filtrés par la global scope).
 */
class BureauDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
    }

    /**
     * @return array{0: Bureau, 1: User, 2: User} [bureau, proprietaire, gerant]
     */
    private function bureauAvecGerant(string $nomBureau): array
    {
        $bureau = Bureau::create(['nom' => $nomBureau, 'actif' => true]);

        $proprietaire = User::factory()->create(['bureau_id' => $bureau->id]);
        $proprietaire->assignRole('proprietaire');
        $proprietaire->givePermissionTo(config('role_permissions.proprietaire', []));
        $bureau->update(['proprietaire_id' => $proprietaire->id]);

        $gerant = User::factory()->create(['bureau_id' => $bureau->id]);
        $gerant->assignRole('gerant');
        $gerant->givePermissionTo(config('role_permissions.gerant', []));

        return [$bureau, $proprietaire, $gerant];
    }

    public function test_clients_are_isolated_per_bureau(): void
    {
        [, , $gerantA] = $this->bureauAvecGerant('Bureau A');
        [, , $gerantB] = $this->bureauAvecGerant('Bureau B');

        $this->actingAs($gerantA)->post('/clients', ['nom' => 'Client de A']);
        $clientA = Client::where('nom', 'Client de A')->first();

        $response = $this->actingAs($gerantB)->get('/clients');
        $response->assertOk();
        $response->assertDontSee('Client de A');

        // Accès direct par ID depuis l'autre bureau : 404, jamais une fuite.
        $this->actingAs($gerantB)->put("/clients/{$clientA->id}", ['nom' => 'Hack'])->assertNotFound();
    }

    public function test_client_sequential_identifiant_is_independent_per_bureau(): void
    {
        [, , $gerantA] = $this->bureauAvecGerant('Bureau A');
        [, , $gerantB] = $this->bureauAvecGerant('Bureau B');

        $this->actingAs($gerantA)->post('/clients', ['nom' => 'Premier de A']);
        $this->actingAs($gerantB)->post('/clients', ['nom' => 'Premier de B']);

        // withoutGlobalScope ici : ces assertions tournent APRÈS le dernier
        // actingAs() (gérant B), qui reste l'utilisateur ambiant pour toute
        // requête Eloquent directe faite depuis le corps du test lui-même.
        $this->assertSame('CL-0001', Client::withoutGlobalScope('bureau')->where('nom', 'Premier de A')->first()->identifiant);
        $this->assertSame('CL-0001', Client::withoutGlobalScope('bureau')->where('nom', 'Premier de B')->first()->identifiant);
    }

    public function test_bareme_versions_are_isolated_per_bureau(): void
    {
        [, $proprietaireA] = $this->bureauAvecGerant('Bureau A');
        [, $proprietaireB] = $this->bureauAvecGerant('Bureau B');

        $this->actingAs($proprietaireA)->post('/bareme', [
            'libelle' => 'Barème de A',
            'lignes' => [['densite_min' => '18.00', 'densite_max' => '18.50', 'carat' => '21.00']],
        ]);
        $versionA = BaremeVersion::where('libelle', 'Barème de A')->first();

        // B n'a pas encore de barème actif : le sien reste introuvable.
        $response = $this->actingAs($proprietaireB)->get('/bareme');
        $response->assertOk();
        $response->assertDontSee('Barème de A');

        // Accès direct par ID depuis l'autre bureau : 404.
        $this->actingAs($proprietaireB)->get("/bareme/{$versionA->id}")->assertNotFound();
        $this->actingAs($proprietaireB)->get("/bareme/{$versionA->id}/modifier")->assertNotFound();

        // Chaque bureau peut créer SA propre version active en parallèle.
        $this->actingAs($proprietaireB)->post('/bareme', [
            'libelle' => 'Barème de B',
            'lignes' => [['densite_min' => '18.00', 'densite_max' => '18.50', 'carat' => '22.00']],
        ]);

        $this->assertTrue($versionA->fresh()->actif, "La désactivation côté B n'aurait jamais dû toucher la version active de A.");
    }

    /**
     * Crée une version de barème directement rattachée à un bureau, sans
     * dépendre de la résolution du bureau via un utilisateur connecté.
     */
    private function baremeActifPour(Bureau $bureau): BaremeVersion
    {
        $version = BaremeVersion::create([
            'libelle' => 'Barème '.$bureau->nom,
            'actif' => true,
            'bureau_id' => $bureau->id,
        ]);
        $version->lignes()->create([
            'densite_min' => '18.59', 'densite_max' => '18.64', 'carat' => '22.80', 'ordre' => 0, 'bureau_id' => $bureau->id,
        ]);

        return $version;
    }

    public function test_gerant_cannot_sell_another_bureaus_stock(): void
    {
        [$bureauA, , $gerantA] = $this->bureauAvecGerant('Bureau A');
        [, , $gerantB] = $this->bureauAvecGerant('Bureau B');

        $this->baremeActifPour($bureauA);
        $vendeurA = Client::factory()->create(['bureau_id' => $bureauA->id]);
        $acheteurB = Client::factory()->create(['bureau_id' => $gerantB->bureau_id]);

        $this->actingAs($gerantA)->post('/achats', [
            'client_id' => $vendeurA->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);
        $barreDeA = \App\Models\BarreAchat::withoutGlobalScope('bureau')->with('operation')->latest('id')->first();
        $numeroAchatDeA = $barreDeA->operation->numero;

        // La barre de A ne doit même pas apparaître dans le formulaire de B.
        $response = $this->actingAs($gerantB)->get('/ventes-nouveau');
        $response->assertOk();
        $response->assertDontSee($numeroAchatDeA);

        // Et une tentative directe par ID est rejetée (barre "introuvable" du point de vue de B).
        $response = $this->actingAs($gerantB)->post('/ventes', [
            'client_id' => $acheteurB->id,
            'date_operation' => now(),
            'prix_base' => 82000,
            'barres_ids' => [$barreDeA->id],
        ]);
        $response->assertSessionHasErrors('barres_ids');
        $this->assertSame('en_stock', $barreDeA->fresh()->statut, "La barre de A ne doit jamais être touchée par une tentative de B.");
    }

    public function test_achats_are_isolated_per_bureau(): void
    {
        [$bureauA, , $gerantA] = $this->bureauAvecGerant('Bureau A');
        [, , $gerantB] = $this->bureauAvecGerant('Bureau B');

        $this->baremeActifPour($bureauA);
        $clientA = Client::factory()->create(['bureau_id' => $gerantA->bureau_id]);

        $this->actingAs($gerantA)->post('/achats', [
            'client_id' => $clientA->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);
        $achatA = OperationAchat::first();
        $this->assertNotNull($achatA);

        $response = $this->actingAs($gerantB)->get('/achats');
        $response->assertOk();
        $response->assertDontSee($achatA->numero);

        $this->actingAs($gerantB)->get("/achats/{$achatA->id}")->assertNotFound();
    }

    public function test_achat_sequential_numero_is_independent_per_bureau(): void
    {
        [$bureauA, , $gerantA] = $this->bureauAvecGerant('Bureau A');
        [$bureauB, , $gerantB] = $this->bureauAvecGerant('Bureau B');

        // baremeActifPour() fixe bureau_id explicitement, sans dépendre de
        // l'utilisateur ambiant (contrairement à creerNouvelleVersion() sans
        // $userId, qui se baserait sur auth()->user() — lequel reste celui du
        // DERNIER actingAs() tant qu'on ne le change pas explicitement).
        $this->baremeActifPour($bureauA);
        $this->baremeActifPour($bureauB);

        $clientA = Client::factory()->create(['bureau_id' => $bureauA->id]);
        $clientB = Client::factory()->create(['bureau_id' => $bureauB->id]);

        $this->actingAs($gerantA)->post('/achats', [
            'client_id' => $clientA->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);
        $this->actingAs($gerantB)->post('/achats', [
            'client_id' => $clientB->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);

        $achatA = OperationAchat::withoutGlobalScope('bureau')->where('bureau_id', $bureauA->id)->first();
        $achatB = OperationAchat::withoutGlobalScope('bureau')->where('bureau_id', $bureauB->id)->first();

        $this->assertNotNull($achatA);
        $this->assertNotNull($achatB);
        $this->assertStringContainsString('-0001', $achatA->numero);
        $this->assertStringContainsString('-0001', $achatB->numero);
    }

    public function test_each_bureau_can_have_its_own_open_journee_simultaneously(): void
    {
        [, , $gerantA] = $this->bureauAvecGerant('Bureau A');
        [, , $gerantB] = $this->bureauAvecGerant('Bureau B');

        $this->actingAs($gerantA)->post('/tresorerie/ouvrir', ['montant_initial' => 100000])
            ->assertRedirect(route('tresorerie.index'));

        // B doit pouvoir ouvrir SA propre journée sans être bloqué par celle de A.
        $this->actingAs($gerantB)->post('/tresorerie/ouvrir', ['montant_initial' => 50000])
            ->assertRedirect(route('tresorerie.index'));

        $this->assertSame(2, JourneeFinanciere::withoutGlobalScope('bureau')->where('statut', 'ouverte')->count());
    }

    public function test_gerant_cannot_view_or_delete_another_bureaus_mouvement(): void
    {
        [, , $gerantA] = $this->bureauAvecGerant('Bureau A');
        [, , $gerantB] = $this->bureauAvecGerant('Bureau B');

        $this->actingAs($gerantA)->post('/tresorerie/ouvrir', ['montant_initial' => 100000]);
        $journeeA = JourneeFinanciere::first();
        $mouvementA = $journeeA->mouvements()->first();

        $this->actingAs($gerantB)->delete("/tresorerie/mouvement/{$mouvementA->id}")->assertNotFound();
        $this->assertDatabaseHas('mouvement_financiers', ['id' => $mouvementA->id]);
    }

    public function test_treasury_is_isolated_per_bureau_even_with_same_client_names(): void
    {
        [$bureauA, , $gerantA] = $this->bureauAvecGerant('Bureau A');
        [$bureauB, , $gerantB] = $this->bureauAvecGerant('Bureau B');

        $this->actingAs($gerantA)->post('/tresorerie/ouvrir', ['montant_initial' => 100000]);
        $this->actingAs($gerantB)->post('/tresorerie/ouvrir', ['montant_initial' => 999999]);

        $journeeA = JourneeFinanciere::withoutGlobalScope('bureau')->where('bureau_id', $bureauA->id)->first();
        $journeeB = JourneeFinanciere::withoutGlobalScope('bureau')->where('bureau_id', $bureauB->id)->first();

        // soldeDisponible() interroge les mouvements (également cloisonnés
        // par bureau) : on se replace dans le contexte du bon gérant pour
        // que cette lecture voie ses propres mouvements.
        $this->actingAs($gerantA);
        $this->assertSame(100000.0, $journeeA->soldeDisponible());

        $this->actingAs($gerantB);
        $this->assertSame(999999.0, $journeeB->soldeDisponible());
    }
}
