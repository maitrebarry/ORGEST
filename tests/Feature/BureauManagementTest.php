<?php

namespace Tests\Feature;

use App\Models\Bureau;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BureauManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        Storage::fake('public');
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }

    /**
     * UploadedFile::fake()->image() ne remplit pas l'image (canevas GD par
     * défaut = noir), inutilisable pour vérifier une VRAIE extraction de
     * couleur : on fabrique un vrai PNG uni à la place.
     */
    private function fausseImageUnie(int $r, int $g, int $b): UploadedFile
    {
        $chemin = tempnam(sys_get_temp_dir(), 'logo').'.png';
        $image = imagecreatetruecolor(20, 20);
        imagefill($image, 0, 0, imagecolorallocate($image, $r, $g, $b));
        imagepng($image, $chemin);
        imagedestroy($image);

        return new UploadedFile($chemin, 'logo.png', 'image/png', null, true);
    }

    public function test_only_superadmin_can_access_bureau_management(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');
        $gerant = $this->userWithRole('gerant');

        $this->actingAs($proprietaire)->get('/bureaux')->assertForbidden();
        $this->actingAs($gerant)->get('/bureaux')->assertForbidden();
    }

    public function test_superadmin_creates_a_bureau_with_its_proprietaire_in_one_go(): void
    {
        $superadmin = $this->superadmin();

        $response = $this->actingAs($superadmin)->post('/bureaux', [
            'nom' => 'Bureau de Kayes',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'adresse' => 'Kayes',
            'telephone' => '70000000',
            'pays' => 'Mali',
            'proprietaire_nom' => 'Amadou Diallo',
            'proprietaire_telephone' => '76000000',
            'proprietaire_password' => 'MotDePasse1!',
            'proprietaire_password_confirmation' => 'MotDePasse1!',
        ]);

        $response->assertRedirect(route('bureaux.index'));

        $bureau = Bureau::where('nom', 'Bureau de Kayes')->first();
        $this->assertNotNull($bureau);
        $this->assertNotNull($bureau->logo);
        Storage::disk('public')->assertExists($bureau->logo);

        $proprietaire = User::where('phone', '76000000')->first();
        $this->assertNotNull($proprietaire);
        $this->assertTrue($proprietaire->hasRole('proprietaire'));
        $this->assertSame($bureau->id, $proprietaire->bureau_id);
        $this->assertSame($proprietaire->id, $bureau->fresh()->proprietaire_id);
        $this->assertTrue($proprietaire->hasDirectPermission('achats.creer'));
    }

    public function test_proprietaire_can_only_update_their_own_bureau_logo(): void
    {
        $bureau = Bureau::create(['nom' => 'Bureau A', 'actif' => true]);
        $proprietaire = User::factory()->create(['bureau_id' => $bureau->id]);
        $proprietaire->assignRole('proprietaire');
        $proprietaire->givePermissionTo(config('role_permissions.proprietaire', []));
        $bureau->update(['proprietaire_id' => $proprietaire->id]);

        $response = $this->actingAs($proprietaire)->post('/mon-bureau/logo', [
            'logo' => UploadedFile::fake()->image('nouveau-logo.png'),
        ]);

        $response->assertRedirect();
        $bureau->refresh();
        $this->assertNotNull($bureau->logo);
        Storage::disk('public')->assertExists($bureau->logo);
    }

    public function test_proprietaire_cannot_create_or_edit_other_bureaux(): void
    {
        $bureau = Bureau::create(['nom' => 'Bureau A', 'actif' => true]);
        $proprietaire = $this->userWithRole('proprietaire');

        $this->actingAs($proprietaire)->post('/bureaux', ['nom' => 'Hack'])->assertForbidden();
        $this->actingAs($proprietaire)->get("/bureaux/{$bureau->id}/modifier")->assertForbidden();
        $this->actingAs($proprietaire)->put("/bureaux/{$bureau->id}", ['nom' => 'Hack'])->assertForbidden();
    }

    public function test_gerant_cannot_update_the_bureau_logo(): void
    {
        $gerant = $this->userWithRole('gerant');

        $this->actingAs($gerant)->post('/mon-bureau/logo', [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertForbidden();
    }

    public function test_uploading_a_logo_extracts_and_stores_its_dominant_color(): void
    {
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)->post('/bureaux', [
            'nom' => 'Bureau de Kayes',
            'logo' => $this->fausseImageUnie(200, 30, 30),
            'pays' => 'Mali',
            'proprietaire_nom' => 'Amadou Diallo',
            'proprietaire_telephone' => '76000000',
            'proprietaire_password' => 'MotDePasse1!',
            'proprietaire_password_confirmation' => 'MotDePasse1!',
        ]);

        $bureau = Bureau::where('nom', 'Bureau de Kayes')->first();
        $this->assertSame('#c81e1e', $bureau->couleur);
        $this->assertSame('#961717', $bureau->couleur_sombre);
    }

    public function test_replacing_the_logo_updates_the_stored_color(): void
    {
        $bureau = Bureau::create(['nom' => 'Bureau A', 'actif' => true]);
        $proprietaire = User::factory()->create(['bureau_id' => $bureau->id]);
        $proprietaire->assignRole('proprietaire');
        $proprietaire->givePermissionTo(config('role_permissions.proprietaire', []));
        $bureau->update(['proprietaire_id' => $proprietaire->id]);

        $this->actingAs($proprietaire)->post('/mon-bureau/logo', [
            'logo' => $this->fausseImageUnie(20, 90, 30),
        ]);

        $this->assertSame('#145a1e', $bureau->fresh()->couleur);
    }
}
