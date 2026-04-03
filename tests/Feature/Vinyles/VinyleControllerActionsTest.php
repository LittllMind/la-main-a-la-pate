<?php

namespace Tests\Feature\Vinyles;

use App\Models\Vinyle;
use App\Models\Fond;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VinyleControllerActionsTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->adminUser();
    }

    /** @test */
    public function admin_peut_voir_formulaire_creation(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('vinyles.create'));

        $response->assertOk()
            ->assertViewIs('vinyles.form')
            ->assertViewHas('vinyle');
    }

    /** @test */
    public function employe_peut_voir_formulaire_creation(): void
    {
        $employe = $this->employeUser();

        $response = $this->actingAs($employe)
            ->get(route('vinyles.create'));

        $response->assertOk()
            ->assertViewIs('vinyles.form');
    }

    /** @test */
    public function client_ne_peut_pas_voir_formulaire_creation(): void
    {
        $client = $this->clientUser();

        $response = $this->actingAs($client)
            ->get(route('vinyles.create'));

        // Redirection vers kiosque (middleware role redirige vers kiosque.index)
        $response->assertRedirect(route('kiosque.index'));
    }

    /** @test */
    public function admin_peut_creer_vinyle(): void
    {
        // Note: fond_id n'est pas stocké sur le vinyle (choisi au moment du panier)
        $response = $this->actingAs($this->admin)
            ->post(route('vinyles.store'), [
                'reference' => 'REF-001',
                'artiste' => 'Test Artist',
                'modele' => 'Standard',
                'genre' => 'Rock',
                'style' => '33 Tours',
                'prix' => 25.50,
                'quantite' => 5,
                'seuil_alerte' => 5,
                // 'fond_id' retiré - pas dans fillable
            ]);

        $response->assertRedirect(route('vinyles.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('vinyles', [
            'reference' => 'REF-001',
            'artiste' => 'Test Artist',
            'modele' => 'Standard',
            'prix' => 25.50,
            'quantite' => 5,
        ]);
    }

    /** @test */
    public function employe_peut_creer_vinyle(): void
    {
        $employe = $this->employeUser();

        $response = $this->actingAs($employe)
            ->post(route('vinyles.store'), [
                'reference' => 'REF-002',
                'artiste' => 'Employe Artist',
                'modele' => 'Deluxe',
                'genre' => 'Jazz',
                'style' => '45 Tours',
                'prix' => 30.00,
                'quantite' => 3,
                'seuil_alerte' => 5,
            ]);

        $response->assertRedirect(route('vinyles.index'));

        $this->assertDatabaseHas('vinyles', [
            'reference' => 'REF-002',
            'modele' => 'Deluxe',
        ]);
    }

    /** @test */
    public function client_ne_peut_pas_creer_vinyle(): void
    {
        $client = $this->clientUser();

        $response = $this->actingAs($client)
            ->post(route('vinyles.store'), [
                'reference' => 'HACK-001',
                'artiste' => 'Hacker',
                'modele' => 'Standard',
                'prix' => 1.00,
                'quantite' => 100,
                'seuil_alerte' => 5,
            ]);

        $response->assertRedirect(route('kiosque.index'));

        $this->assertDatabaseMissing('vinyles', [
            'reference' => 'HACK-001',
        ]);
    }

    /** @test */
    public function validation_rejecte_reference_vide(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('vinyles.store'), [
                'reference' => '',
                'artiste' => 'Test',
                'modele' => 'Standard',
                'prix' => 25.00,
                'quantite' => 5,
                'seuil_alerte' => 5,
            ]);

        $response->assertSessionHasErrors('reference');
    }

    /** @test */
    public function validation_rejecte_prix_negatif(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('vinyles.store'), [
                'reference' => 'REF-TEST',
                'artiste' => 'Test',
                'modele' => 'Standard',
                'prix' => -10.00,
                'quantite' => 5,
                'seuil_alerte' => 5,
            ]);

        $response->assertSessionHasErrors('prix');
    }

    /** @test */
    public function validation_rejecte_quantite_negative(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('vinyles.store'), [
                'reference' => 'REF-TEST',
                'artiste' => 'Test',
                'modele' => 'Standard',
                'prix' => 25.00,
                'quantite' => -5,
                'seuil_alerte' => 5,
            ]);

        $response->assertSessionHasErrors('quantite');
    }

    /** @test */
    public function admin_peut_modifier_vinyle(): void
    {
        // Créer l'admin et s'authentifier AVANT de créer le vinyle (Auth::id() doit exister)
        $this->actingAs($this->admin);
        
        $vinyle = Vinyle::factory()->create([
            'reference' => 'OLD-REF',
            'prix' => 20.00,
        ]);

        $response = $this
            ->patch(route('vinyles.update', $vinyle), [
                'reference' => 'NEW-REF',
                'artiste' => $vinyle->artiste,
                'modele' => $vinyle->modele,
                'prix' => 35.00,
                'quantite' => 10,
                'seuil_alerte' => $vinyle->seuil_alerte,
                'genre' => $vinyle->genre ?? 'Rock',
                'style' => $vinyle->style ?? 'Classic',
            ]);

        $response->assertRedirect(route('vinyles.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('vinyles', [
            'id' => $vinyle->id,
            'reference' => 'NEW-REF',
            'prix' => 35.00,
        ]);
    }

    /** @test */
    public function employe_peut_modifier_vinyle(): void
    {
        // Créer l'employé AVANT de créer le vinyle (pour que Auth::id() existe)
        $employe = $this->employeUser();
        
        $vinyle = Vinyle::factory()->create(['reference' => 'ORIGINAL']);

        $response = $this->actingAs($employe)
            ->patch(route('vinyles.update', $vinyle), [
                'reference' => 'Modified by Employe',
                'artiste' => $vinyle->artiste,
                'modele' => $vinyle->modele,
                'prix' => $vinyle->prix,
                'quantite' => $vinyle->quantite,
                'seuil_alerte' => $vinyle->seuil_alerte,
                'genre' => $vinyle->genre ?? 'Rock',
                'style' => $vinyle->style ?? 'Classic',
            ]);

        $response->assertRedirect(route('vinyles.index'));

        $this->assertDatabaseHas('vinyles', [
            'id' => $vinyle->id,
            'reference' => 'Modified by Employe',
        ]);
    }

    /** @test */
    public function client_ne_peut_pas_modifier_vinyle(): void
    {
        $client = $this->clientUser();
        $vinyle = Vinyle::factory()->create(['reference' => 'Protected']);

        $response = $this->actingAs($client)
            ->patch(route('vinyles.update', $vinyle), [
                'reference' => 'Hacked',
                'artiste' => $vinyle->artiste ?? 'Test',
                'modele' => $vinyle->modele,
                'prix' => $vinyle->prix,
                'quantite' => $vinyle->quantite,
                'seuil_alerte' => $vinyle->seuil_alerte ?? 5,
            ]);

        $response->assertRedirect(route('kiosque.index'));

        $this->assertDatabaseHas('vinyles', [
            'id' => $vinyle->id,
            'reference' => 'Protected',
        ]);
    }

    /** @test */
    public function admin_peut_supprimer_vinyle(): void
    {
        $vinyle = Vinyle::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('vinyles.destroy', $vinyle));

        $response->assertRedirect(route('vinyles.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('vinyles', [
            'id' => $vinyle->id,
        ]);
    }

    /** @test */
    public function employe_peut_supprimer_vinyle(): void
    {
        $employe = $this->employeUser();
        $vinyle = Vinyle::factory()->create();

        $response = $this->actingAs($employe)
            ->delete(route('vinyles.destroy', $vinyle));

        $response->assertRedirect(route('vinyles.index'));

        $this->assertDatabaseMissing('vinyles', [
            'id' => $vinyle->id,
        ]);
    }

    /** @test */
    public function client_ne_peut_pas_supprimer_vinyle(): void
    {
        $client = $this->clientUser();
        $vinyle = Vinyle::factory()->create();

        $response = $this->actingAs($client)
            ->delete(route('vinyles.destroy', $vinyle));

        $response->assertRedirect(route('kiosque.index'));

        $this->assertDatabaseHas('vinyles', [
            'id' => $vinyle->id,
        ]);
    }

    /** @test */
    public function suppression_vinyle_inexistant_retourne_404(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('vinyles.destroy', 99999));

        $response->assertNotFound();
    }

    /** @test */
    public function modification_vinyle_inexistant_retourne_404(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch(route('vinyles.update', 99999), [
                'reference' => 'TEST-REF',
                'artiste' => 'Test',
                'modele' => 'Standard',
                'prix' => 25.00,
                'quantite' => 5,
                'seuil_alerte' => 5,
            ]);

        $response->assertNotFound();
    }

    /** @test */
    public function guest_est_redirige_vers_login_pour_actions(): void
    {
        $vinyle = Vinyle::factory()->create();

        // Create
        $response = $this->get(route('vinyles.create'));
        $response->assertRedirect(route('login'));

        // Store
        $response = $this->post(route('vinyles.store'), []);
        $response->assertRedirect(route('login'));

        // Edit
        $response = $this->get(route('vinyles.edit', $vinyle));
        $response->assertRedirect(route('login'));

        // Update
        $response = $this->patch(route('vinyles.update', $vinyle), []);
        $response->assertRedirect(route('login'));

        // Destroy
        $response = $this->delete(route('vinyles.destroy', $vinyle));
        $response->assertRedirect(route('login'));
    }
}
