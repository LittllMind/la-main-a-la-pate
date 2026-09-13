<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subject;
use App\Models\VisibilityLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectCitizenRenderTest extends TestCase
{
    use RefreshDatabase;
    private function makeCitizenSubject(): Subject
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
            'requires_setup' => false,
            'username' => 'admin_citizen_' . uniqid(),
        ]);

        $title = 'Vidéoprotection au Rozier';
        $body = "# {$title}\n\n> **L’essentiel**\n>\n> Test blockquote calibré.\n\n## Questions\n\n| Date | Événement |\n|---|---|\n| 21/10/2025 | Approbation |\n| 22/10/2025 | Notification |\n\n1. Question première.\n2. Question suivante.\n\nTexte normal pour vérifier lisibilité.\n";

        return Subject::factory()->create([
            'user_id' => $admin->id,
            'title' => $title,
            'body' => $body,
            'citizen_body' => $body,
            'public_body' => $body,
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);
    }

    /** 1. GREEN — wrapper Citizen dédié */
    public function test_citizen_preview_uses_citizen_document_wrapper(): void
    {
        $subject = $this->makeCitizenSubject();

        $html = $this->actingAs(User::find($subject->user_id))
            ->get(route('subjects.preview', [$subject->slug, 'citizen']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="bg-white rounded-lg border border-slate-200 p-6 mb-8 citizen-document"', $html, 'Le wrapper Citizen doit être actif dans le markup <article>');
    }

    /** 2. GREEN — suppression du H1 Markdown quand stripTitle=true */
    public function test_render_body_eliminates_duplicate_title_heading(): void
    {
        $subject = $this->makeCitizenSubject();
        $html = $subject->renderBody(stripTitle: true);

        $this->assertStringNotContainsString('<h1', $html, 'Le H1 Markdown dupliquant le titre doit être supprimé côté rendu Citizen.');
    }

    /** 3. GREEN — blockquote "L’essentiel" présent et wrapper Citizen actif */
    public function test_citizen_preview_blockquote_is_callout_styled(): void
    {
        $subject = $this->makeCitizenSubject();

        $html = $this->actingAs(User::find($subject->user_id))
            ->get(route('subjects.preview', [$subject->slug, 'citizen']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('citizen-document', $html, 'Wrapper Citizen doit être présent');
        $this->assertStringContainsString('<blockquote>', $html, 'Le bloc callout doit être présent');
    }

    /** 4. GREEN — tableaux avec overflow-x-auto */
    public function test_citizen_preview_table_has_overflow_wrapper(): void
    {
        $subject = $this->makeCitizenSubject();

        $this->actingAs(User::find($subject->user_id))
            ->get(route('subjects.preview', [$subject->slug, 'citizen']))
            ->assertOk()
            ->assertSee('overflow-x-auto');
    }

    /** 5. GREEN — Instruction reste subject-document (non-régression) */
    public function test_instruction_render_uses_subject_document_not_citizen(): void
    {
        $subject = $this->makeCitizenSubject();

        $html = $this->actingAs(User::find($subject->user_id))
            ->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="bg-white rounded-lg border border-slate-200 p-6 mb-8 subject-document"', $html, 'Instruction doit rester subject-document');
        // citizen-document apparaît dans le bloc <style> de show.blade.php ; on vérifie qu'il n'est pas sur le <article>.
        $this->assertStringNotContainsString('class="bg-white rounded-lg border border-slate-200 p-6 mb-8 citizen-document"', $html, 'Instruction ne doit pas utiliser le wrapper Citizen');
    }

    /** 6. GREEN — guest 404 (non-régression) */
    public function test_guest_sees_404_on_citizen_show(): void
    {
        $subject = $this->makeCitizenSubject();
        $subject->update(['citizen_status' => 'draft', 'public_status' => 'draft']);

        $this->assertGuest();
        $this->get(route('subjects.show', $subject->slug))
            ->assertNotFound();
    }
}
