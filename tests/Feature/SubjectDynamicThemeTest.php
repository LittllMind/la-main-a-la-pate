<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectDynamicThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_subject_with_a_new_theme(): void
    {
        $user = User::factory()->create(['role' => 'citoyen']);

        $response = $this->actingAs($user)->post('/sujets', [
            'theme' => '__new__',
            'theme_other' => 'Économie locale',
            'title' => 'Coopérative agricole',
            'body' => '<p>Contenu.</p>',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('subjects', [
            'title' => 'Coopérative agricole',
            'theme' => 'Économie locale',
            'status' => 'draft',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_use_existing_theme_from_select(): void
    {
        $user = User::factory()->create(['role' => 'citoyen']);

        $response = $this->actingAs($user)->post('/sujets', [
            'theme' => 'Nature',
            'title' => 'Projet de jardin partagé',
            'body' => '<p>Contenu.</p>',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('subjects', [
            'title' => 'Projet de jardin partagé',
            'theme' => 'Nature',
        ]);
    }

    public function test_new_theme_is_required_when_autre_is_selected(): void
    {
        $user = User::factory()->create(['role' => 'citoyen']);

        $response = $this->actingAs($user)->post('/sujets', [
            'theme' => '__new__',
            'theme_other' => '',
            'title' => 'Titre',
            'body' => '<p>Contenu.</p>',
        ]);

        $response->assertSessionHasErrors(['theme_other']);
    }

    public function test_new_theme_is_normalized_by_capitalizing_first_letter_and_trimming(): void
    {
        $user = User::factory()->create(['role' => 'citoyen']);

        $this->actingAs($user)->post('/sujets', [
            'theme' => '__new__',
            'theme_other' => '  TRANSPORT  ',
            'title' => 'Bus',
            'body' => '<p>Contenu.</p>',
        ]);

        $this->assertDatabaseHas('subjects', [
            'title' => 'Bus',
            'theme' => 'Transport',
        ]);
    }

    public function test_dynamically_created_themes_are_returned_to_index_filter(): void
    {
        $user = User::factory()->create();
        $subject = Subject::factory()->create([
            'user_id' => $user->id,
            'theme' => 'Économie locale',
            'status' => 'published',
        ]);

        $this->actingAs($user)->get('/sujets')
            ->assertOk()
            ->assertSee('Économie locale');
    }

    public function test_user_can_change_theme_to_new_one_when_editing(): void
    {
        $user = User::factory()->create(['role' => 'citoyen']);
        $subject = Subject::factory()->create([
            'user_id' => $user->id,
            'theme' => 'Nature',
            'status' => 'published',
        ]);

        $response = $this->actingAs($user)->put("/sujets/{$subject->slug}", [
            'theme' => '__new__',
            'theme_other' => 'Culture',
            'title' => $subject->title,
            'body' => $subject->body,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'theme' => 'Culture',
        ]);
    }
}
