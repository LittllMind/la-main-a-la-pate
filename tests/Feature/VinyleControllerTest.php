<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vinyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VinyleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // ==========================================================
    // TESTS INDEX
    // ==========================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_view_vinyles_index(): void
    {
        $user = $this->adminUser();
        Vinyle::factory()->count(5)->create();

        $response = $this->actingAs($user)
            ->get(route('vinyles.index'));

        $response->assertOk()
            ->assertViewIs('vinyles.index')
            ->assertViewHas('vinyles')
            ->assertSee('Gestion des Vinyles');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function employe_can_view_vinyles_index(): void
    {
        $user = $this->employeUser();
        Vinyle::factory()->count(3)->create();

        $response = $this->actingAs($user)
            ->get(route('vinyles.index'));

        $response->assertOk()
            ->assertViewIs('vinyles.index');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function client_cannot_view_vinyles_index(): void
    {
        $user = $this->clientUser();

        $response = $this->actingAs($user)
            ->get(route('vinyles.index'));

        // Middleware role redirige vers accueil si rôle insuffisant
        $response->assertRedirect();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function guest_cannot_view_vinyles_index(): void
    {
        $response = $this->get(route('vinyles.index'));

        $response->assertRedirect();
    }

    // ==========================================================
    // TESTS CREATE
    // ==========================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_view_create_form(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)
            ->get(route('vinyles.create'));

        $response->assertOk()
            ->assertViewIs('vinyles.create')
            ->assertSee('Nouveau Vinyle');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function employe_can_view_create_form(): void
    {
        $user = $this->employeUser();

        $response = $this->actingAs($user)
            ->get(route('vinyles.create'));

        $response->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function client_cannot_view_create_form(): void
    {
        $user = $this->clientUser();

        $response = $this->actingAs($user)
            ->get(route('vinyles.create'));

        $response->assertRedirect();
    }

    // ==========================================================
    // TESTS STORE
    // ==========================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_create_vinyle(): void
    {
        $user = $this->adminUser();
        $data = [
            'reference' => 'VIN-TEST-001',
            'artiste' => 'Test Artist',
            'modele' => 'Test Model',
            'genre' => 'Rock',
            'style' => 'Classic',
            'prix' => 25.50,
            'quantite' => 10,
            'seuil_alerte' => 3,
        ];

        $response = $this->actingAs($user)
            ->post(route('vinyles.store'), $data);

        $response->assertRedirect(route('vinyles.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('vinyles', [
            'reference' => 'VIN-TEST-001',
            'artiste' => 'Test Artist',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function employe_can_create_vinyle(): void
    {
        $user = $this->employeUser();
        $data = [
            'reference' => 'VIN-EMP-001',
            'artiste' => 'Employee Artist',
            'prix' => 30.00,
            'quantite' => 5,
        ];

        $response = $this->actingAs($user)
            ->post(route('vinyles.store'), $data);

        $response->assertRedirect(route('vinyles.index'));
        $this->assertDatabaseHas('vinyles', ['reference' => 'VIN-EMP-001']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function client_cannot_create_vinyle(): void
    {
        $user = $this->clientUser();
        $data = [
            'reference' => 'VIN-CLIENT-001',
            'artiste' => 'Client Artist',
            'prix' => 20.00,
            'quantite' => 5,
        ];

        $response = $this->actingAs($user)
            ->post(route('vinyles.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseMissing('vinyles', ['reference' => 'VIN-CLIENT-001']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_create_vinyle_without_required_fields(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)
            ->post(route('vinyles.store'), []);

        $response->assertSessionHasErrors(['reference', 'artiste', 'prix', 'quantite']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_create_vinyle_with_duplicate_reference(): void
    {
        $user = $this->adminUser();
        Vinyle::factory()->create(['reference' => 'VIN-DUPLICATE']);

        $data = [
            'reference' => 'VIN-DUPLICATE',
            'artiste' => 'New Artist',
            'prix' => 20.00,
            'quantite' => 5,
        ];

        $response = $this->actingAs($user)
            ->post(route('vinyles.store'), $data);

        $response->assertSessionHasErrors(['reference']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_create_vinyle_with_images(): void
    {
        $user = $this->adminUser();
        $image = UploadedFile::fake()->image('vinyle.jpg', 800, 800);

        $data = [
            'reference' => 'VIN-IMAGE-001',
            'artiste' => 'Image Artist',
            'prix' => 25.00,
            'quantite' => 5,
            'images' => [$image],
        ];

        $response = $this->actingAs($user)
            ->post(route('vinyles.store'), $data);

        $response->assertRedirect(route('vinyles.index'));
        
        $vinyle = Vinyle::where('reference', 'VIN-IMAGE-001')->first();
        $this->assertNotNull($vinyle);
        $this->assertTrue($vinyle->getMedia('photo')->isNotEmpty());
    }

    // ==========================================================
    // TESTS SHOW
    // ==========================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_view_vinyle_details(): void
    {
        $user = $this->adminUser();
        $vinyle = Vinyle::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('vinyles.show', $vinyle));

        $response->assertOk()
            ->assertViewIs('vinyles.show')
            ->assertViewHas('vinyle')
            ->assertSee($vinyle->artiste);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function employe_can_view_vinyle_details(): void
    {
        $user = $this->employeUser();
        $vinyle = Vinyle::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('vinyles.show', $vinyle));

        $response->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function client_cannot_view_vinyle_details(): void
    {
        $user = $this->clientUser();
        $vinyle = Vinyle::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('vinyles.show', $vinyle));

        $response->assertRedirect();
    }

    // ==========================================================
    // TESTS EDIT
    // ==========================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_view_edit_form(): void
    {
        $user = $this->adminUser();
        $vinyle = Vinyle::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('vinyles.edit', $vinyle));

        $response->assertOk()
            ->assertViewIs('vinyles.edit')
            ->assertViewHas('vinyle')
            ->assertSee('Modifier Vinyle');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function employe_can_view_edit_form(): void
    {
        $user = $this->employeUser();
        $vinyle = Vinyle::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('vinyles.edit', $vinyle));

        $response->assertOk();
    }

    // ==========================================================
    // TESTS UPDATE
    // ==========================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_update_vinyle(): void
    {
        $user = $this->adminUser();
        $vinyle = Vinyle::factory()->create([
            'artiste' => 'Old Artist',
            'prix' => 20.00,
        ]);

        $data = [
            'reference' => $vinyle->reference,
            'artiste' => 'Updated Artist',
            'modele' => 'Updated Model',
            'prix' => 35.00,
            'quantite' => 15,
        ];

        $response = $this->actingAs($user)
            ->put(route('vinyles.update', $vinyle), $data);

        $response->assertRedirect(route('vinyles.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('vinyles', [
            'id' => $vinyle->id,
            'artiste' => 'Updated Artist',
            'prix' => 35.00,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function employe_can_update_vinyle(): void
    {
        $user = $this->employeUser();
        $vinyle = Vinyle::factory()->create();

        $data = [
            'reference' => $vinyle->reference,
            'artiste' => 'Employee Updated',
            'prix' => $vinyle->prix,
            'quantite' => $vinyle->quantite + 5,
        ];

        $response = $this->actingAs($user)
            ->put(route('vinyles.update', $vinyle), $data);

        $response->assertRedirect(route('vinyles.index'));
        $this->assertDatabaseHas('vinyles', [
            'id' => $vinyle->id,
            'artiste' => 'Employee Updated',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function client_cannot_update_vinyle(): void
    {
        $user = $this->clientUser();
        $vinyle = Vinyle::factory()->create(['artiste' => 'Original']);

        $data = [
            'reference' => $vinyle->reference,
            'artiste' => 'Hacked',
            'prix' => $vinyle->prix,
            'quantite' => $vinyle->quantite,
        ];

        $response = $this->actingAs($user)
            ->put(route('vinyles.update', $vinyle), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('vinyles', [
            'id' => $vinyle->id,
            'artiste' => 'Original',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_update_vinyle_with_invalid_data(): void
    {
        $user = $this->adminUser();
        $vinyle = Vinyle::factory()->create();

        $response = $this->actingAs($user)
            ->put(route('vinyles.update', $vinyle), [
                'reference' => '',
                'artiste' => '',
                'prix' => -10,
                'quantite' => -5,
            ]);

        $response->assertSessionHasErrors(['reference', 'artiste', 'prix', 'quantite']);
    }

    // ==========================================================
    // TESTS DESTROY
    // ==========================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_delete_vinyle(): void
    {
        $user = $this->adminUser();
        $vinyle = Vinyle::factory()->create();

        $response = $this->actingAs($user)
            ->delete(route('vinyles.destroy', $vinyle));

        $response->assertRedirect(route('vinyles.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('vinyles', ['id' => $vinyle->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function employe_can_delete_vinyle(): void
    {
        $user = $this->employeUser();
        $vinyle = Vinyle::factory()->create();

        $response = $this->actingAs($user)
            ->delete(route('vinyles.destroy', $vinyle));

        $response->assertRedirect(route('vinyles.index'));
        $this->assertDatabaseMissing('vinyles', ['id' => $vinyle->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function client_cannot_delete_vinyle(): void
    {
        $user = $this->clientUser();
        $vinyle = Vinyle::factory()->create();

        $response = $this->actingAs($user)
            ->delete(route('vinyles.destroy', $vinyle));

        $response->assertRedirect();
        $this->assertDatabaseHas('vinyles', ['id' => $vinyle->id]);
    }

    // ==========================================================
    // TESTS PAGINATION
    // ==========================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function vinyles_are_paginated(): void
    {
        $user = $this->adminUser();
        Vinyle::factory()->count(15)->create();

        $response = $this->actingAs($user)
            ->get(route('vinyles.index'));

        $response->assertOk()
            ->assertViewHas('vinyles', function ($vinyles) {
                return $vinyles->count() <= 10; // Pagination par défaut
            });
    }

    // ==========================================================
    // TESTS VALIDATION
    // ==========================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function prix_cannot_be_negative(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)
            ->post(route('vinyles.store'), [
                'reference' => 'VIN-001',
                'artiste' => 'Test',
                'prix' => -10,
                'quantite' => 5,
            ]);

        $response->assertSessionHasErrors(['prix']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function quantite_cannot_be_negative(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)
            ->post(route('vinyles.store'), [
                'reference' => 'VIN-001',
                'artiste' => 'Test',
                'prix' => 25.00,
                'quantite' => -5,
            ]);

        $response->assertSessionHasErrors(['quantite']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function reference_has_max_length(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)
            ->post(route('vinyles.store'), [
                'reference' => str_repeat('A', 51),
                'artiste' => 'Test',
                'prix' => 25.00,
                'quantite' => 5,
            ]);

        $response->assertSessionHasErrors(['reference']);
    }
}
