<?php

namespace Tests\Feature;

use App\Models\BaremeVersion;
use App\Models\Bureau;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * La devise affichée partout dans l'application (factures, écrans,
 * messages d'erreur) est déduite automatiquement du pays du bureau — jamais
 * choisie séparément par l'utilisateur — pour permettre à des bureaux hors
 * zone FCFA d'utiliser l'application (cf. config/pays_devises.php).
 */
class DeviseMultiPaysTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
    }

    private function bureauAvecGerant(string $nom, string $pays): array
    {
        $bureau = Bureau::create(['nom' => $nom, 'pays' => $pays, 'actif' => true]);

        $proprietaire = User::factory()->create(['bureau_id' => $bureau->id]);
        $proprietaire->assignRole('proprietaire');
        $proprietaire->givePermissionTo(config('role_permissions.proprietaire', []));
        $bureau->update(['proprietaire_id' => $proprietaire->id]);

        $gerant = User::factory()->create(['bureau_id' => $bureau->id]);
        $gerant->assignRole('gerant');
        $gerant->givePermissionTo(config('role_permissions.gerant', []));

        return [$bureau, $proprietaire, $gerant];
    }

    public function test_devise_is_derived_from_bureau_pays(): void
    {
        $bureauNigeria = Bureau::create(['nom' => 'Bureau Nigeria', 'pays' => 'Nigeria', 'actif' => true]);
        $bureauFrance = Bureau::create(['nom' => 'Bureau France', 'pays' => 'France', 'actif' => true]);
        $bureauMali = Bureau::create(['nom' => 'Bureau Mali', 'pays' => 'Mali', 'actif' => true]);

        $this->assertSame('₦', $bureauNigeria->devise_symbole);
        $this->assertSame('NGN', $bureauNigeria->devise_code);
        $this->assertSame('€', $bureauFrance->devise_symbole);
        $this->assertSame('EUR', $bureauFrance->devise_code);
        $this->assertSame('FCFA', $bureauMali->devise_symbole);
        $this->assertSame('XOF', $bureauMali->devise_code);
    }

    public function test_bureau_without_pays_falls_back_to_mali_fcfa(): void
    {
        $bureau = Bureau::create(['nom' => 'Sans pays', 'actif' => true]);

        $this->assertSame('FCFA', $bureau->devise_symbole);
        $this->assertSame('XOF', $bureau->devise_code);
    }

    public function test_creating_a_bureau_requires_a_valid_pays(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $response = $this->actingAs($superadmin)->post('/bureaux', [
            'nom' => 'Bureau invalide',
            'pays' => 'Narnia',
            'proprietaire_nom' => 'Test',
            'proprietaire_telephone' => '76000000',
            'proprietaire_password' => 'MotDePasse1!',
            'proprietaire_password_confirmation' => 'MotDePasse1!',
        ]);

        $response->assertSessionHasErrors('pays');
        $this->assertNull(Bureau::where('nom', 'Bureau invalide')->first());
    }

    public function test_achat_pages_and_pdf_use_the_bureaus_own_currency(): void
    {
        [$bureau, , $gerant] = $this->bureauAvecGerant('Bureau Nigeria', 'Nigeria');
        BaremeVersion::creerNouvelleVersion('Barème', [
            ['densite_min' => '18.59', 'densite_max' => '18.64', 'carat' => '22.80'],
        ]);
        $client = Client::factory()->create(['bureau_id' => $bureau->id]);

        $this->actingAs($gerant)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);
        $achat = \App\Models\OperationAchat::first();

        $response = $this->actingAs($gerant)->get("/achats/{$achat->id}");
        $response->assertOk();
        $response->assertSee('₦');
        $response->assertDontSee('FCFA');

        $html = view('pdf.achat', ['achat' => $achat->load('barres', 'client', 'user', 'bureau')])->render();
        $this->assertStringContainsString('₦', $html);
        $this->assertStringNotContainsString('FCFA', $html);
    }

    public function test_uploading_a_country_specific_logo_does_not_affect_currency(): void
    {
        // La devise vient uniquement du pays, jamais du logo — même si les
        // deux sont mis à jour dans le même formulaire de création.
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $this->actingAs($superadmin)->post('/bureaux', [
            'nom' => 'Bureau Ghana',
            'pays' => 'Ghana',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'proprietaire_nom' => 'Test',
            'proprietaire_telephone' => '76000000',
            'proprietaire_password' => 'MotDePasse1!',
            'proprietaire_password_confirmation' => 'MotDePasse1!',
        ]);

        $bureau = Bureau::where('nom', 'Bureau Ghana')->first();
        $this->assertSame('GH₵', $bureau->devise_symbole);
    }
}
