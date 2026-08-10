<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subject;
use App\Models\SubjectVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectVersionThreeBodyTest extends TestCase
{
    use RefreshDatabase;

    private const WORKING_MARKER = 'WORKING_BODY_VSNAPSHOT';
    private const CITIZEN_MARKER = 'CITIZEN_BODY_VSNAPSHOT';
    private const PUBLIC_MARKER = 'PUBLIC_BODY_VSNAPSHOT';

    protected function setUpSubject(): Subject
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
            'requires_setup' => false,
            'username' => 'admin_version_' . uniqid(),
        ]);

        return Subject::factory()->create([
            'user_id' => $admin->id,
            'body' => '# Travail\n\n' . self::WORKING_MARKER,
            'citizen_body' => '# Citoyen\n\n' . self::CITIZEN_MARKER,
            'public_body' => '# Public\n\n' . self::PUBLIC_MARKER,
            'public_status' => 'draft',
            'citizen_status' => 'draft',
        ]);
    }

    public function test_changing_working_body_creates_three_body_snapshot()
    {
        $subject = $this->setUpSubject();
        $admin = User::find($subject->user_id);

        $this->actingAs($admin)
            ->put(route('subjects.update', $subject->slug), [
                'theme' => $subject->theme,
                'title' => $subject->title,
                'body' => '# Travail\n\nMODIFIED_WORKING',
                'citizen_body' => $subject->citizen_body,
                'public_body' => $subject->public_body,
                'change_summary' => 'Mise à jour du travail',
            ])
            ->assertRedirect();

        $versions = SubjectVersion::where('subject_id', $subject->id)->orderByDesc('id')->get();
        $this->assertCount(1, $versions);

        $snapshot = $versions->first();
        $this->assertStringContainsString(self::WORKING_MARKER, $snapshot->body);
        $this->assertStringContainsString(self::CITIZEN_MARKER, $snapshot->citizen_body);
        $this->assertStringContainsString(self::PUBLIC_MARKER, $snapshot->public_body);
    }

    public function test_changing_citizen_body_creates_version()
    {
        $subject = $this->setUpSubject();
        $admin = User::find($subject->user_id);

        $this->actingAs($admin)
            ->put(route('subjects.update', $subject->slug), [
                'theme' => $subject->theme,
                'title' => $subject->title,
                'body' => $subject->body,
                'citizen_body' => '# Citoyen\n\nMODIFIED_CITIZEN',
                'public_body' => $subject->public_body,
            ])
            ->assertRedirect();

        $snapshots = SubjectVersion::where('subject_id', $subject->id)->get();
        $this->assertCount(1, $snapshots);
        $this->assertStringContainsString(self::CITIZEN_MARKER, $snapshots->first()->citizen_body);
        $this->assertStringContainsString(self::WORKING_MARKER, $snapshots->first()->body);
        $this->assertStringContainsString(self::PUBLIC_MARKER, $snapshots->first()->public_body);
    }

    public function test_changing_public_body_creates_version()
    {
        $subject = $this->setUpSubject();
        $admin = User::find($subject->user_id);

        $this->actingAs($admin)
            ->put(route('subjects.update', $subject->slug), [
                'theme' => $subject->theme,
                'title' => $subject->title,
                'body' => $subject->body,
                'citizen_body' => $subject->citizen_body,
                'public_body' => '# Public\n\nMODIFIED_PUBLIC',
            ])
            ->assertRedirect();

        $this->assertCount(1, SubjectVersion::where('subject_id', $subject->id)->get());
    }

    public function test_changing_only_public_body_preserves_working_and_citizen_values_in_snapshot()
    {
        $subject = $this->setUpSubject();
        $admin = User::find($subject->user_id);

        $this->actingAs($admin)
            ->put(route('subjects.update', $subject->slug), [
                'theme' => $subject->theme,
                'title' => $subject->title,
                'body' => $subject->body,
                'citizen_body' => $subject->citizen_body,
                'public_body' => '# Public\n\nMODIFIED_PUBLIC_ONLY',
            ]);

        $snapshot = SubjectVersion::where('subject_id', $subject->id)->first();
        // Le snapshot contient les trois représentations telles qu'elles étaient avant la modification.
        $this->assertEquals($subject->body, $snapshot->body);
        $this->assertEquals($subject->citizen_body, $snapshot->citizen_body);
        $this->assertStringContainsString(self::PUBLIC_MARKER, $snapshot->public_body);

        $subject->refresh();
        $this->assertStringContainsString('MODIFIED_PUBLIC_ONLY', $subject->public_body);
    }

    public function test_legacy_version_with_null_citizen_and_public_remains_readable()
    {
        $subject = $this->setUpSubject();
        $admin = User::find($subject->user_id);

        SubjectVersion::create([
            'subject_id' => $subject->id,
            'user_id' => $admin->id,
            'body' => 'ANCIEN_BODY',
            'citizen_body' => null,
            'public_body' => null,
        ]);

        $version = SubjectVersion::where('body', 'ANCIEN_BODY')->first();
        $this->assertNotNull($version);
        $this->assertNull($version->citizen_body);
        $this->assertNull($version->public_body);
    }

    public function test_editing_document_metadata_does_not_create_subject_version()
    {
        $subject = $this->setUpSubject();
        $admin = User::find($subject->user_id);

        $document = \App\Models\SubjectDocument::factory()->create([
            'subject_id' => $subject->id,
            'visibility' => \App\Models\VisibilityLevel::Public,
            'title' => 'Doc initial',
        ]);

        $this->actingAs($admin)
            ->patch(route('subjects.documents.update', [$subject->slug, $document->id]), [
                'title' => 'Doc renommé',
                'visibility' => 'public',
                'category' => 'source',
            ])
            ->assertRedirect();

        $this->assertCount(0, SubjectVersion::where('subject_id', $subject->id)->get());
    }

    public function test_publishing_or_hiding_representation_does_not_modify_versioned_content()
    {
        $subject = $this->setUpSubject();
        $admin = User::find($subject->user_id);

        $this->actingAs($admin)
            ->patch(route('subjects.publish.public', $subject->slug))
            ->assertRedirect();

        $this->assertCount(0, SubjectVersion::where('subject_id', $subject->id)->get());

        $this->actingAs($admin)
            ->patch(route('subjects.hide.public', $subject->slug))
            ->assertRedirect();

        $this->assertCount(0, SubjectVersion::where('subject_id', $subject->id)->get());
    }

    public function test_non_body_change_does_not_create_subject_version()
    {
        $subject = $this->setUpSubject();
        $admin = User::find($subject->user_id);

        $this->actingAs($admin)
            ->put(route('subjects.update', $subject->slug), [
                'theme' => $subject->theme,
                'title' => 'Titre modifié sans toucher aux corps',
                'body' => $subject->body,
                'citizen_body' => $subject->citizen_body,
                'public_body' => $subject->public_body,
            ])
            ->assertRedirect();

        $this->assertCount(0, SubjectVersion::where('subject_id', $subject->id)->get());
    }

    public function test_subject_history_does_not_leak_snapshot_to_guest()
    {
        $subject = $this->setUpSubject();
        $admin = User::find($subject->user_id);
        $subject->update(['public_status' => 'published']);

        // Le marqueur working ne doit pas apparaître dans le rendu public
        auth()->logout();
        $this->assertGuest();
        $this->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee(self::PUBLIC_MARKER)
            ->assertDontSee(self::WORKING_MARKER)
            ->assertDontSee(self::CITIZEN_MARKER);
    }

    public function test_two_consecutive_body_changes_create_two_snapshots()
    {
        $subject = $this->setUpSubject();
        $admin = User::find($subject->user_id);

        $this->actingAs($admin)
            ->put(route('subjects.update', $subject->slug), [
                'theme' => $subject->theme,
                'title' => $subject->title,
                'body' => '# Travail\n\nFIRST_EDIT',
                'citizen_body' => $subject->citizen_body,
                'public_body' => $subject->public_body,
            ]);

        $this->actingAs($admin)
            ->put(route('subjects.update', $subject->slug), [
                'theme' => $subject->theme,
                'title' => $subject->title,
                'body' => '# Travail\n\nSECOND_EDIT',
                'citizen_body' => $subject->citizen_body,
                'public_body' => $subject->public_body,
            ]);

        $versions = SubjectVersion::where('subject_id', $subject->id)
            ->orderBy('created_at')
            ->get();

        $this->assertCount(2, $versions);
        // Snapshot 1 = état avant FIRST_EDIT ; snapshot 2 = état avant SECOND_EDIT.
        $this->assertStringContainsString(self::WORKING_MARKER, $versions[0]->body);
        $this->assertStringContainsString(self::CITIZEN_MARKER, $versions[0]->citizen_body);
        $this->assertStringContainsString(self::PUBLIC_MARKER, $versions[0]->public_body);

        $this->assertStringContainsString('FIRST_EDIT', $versions[1]->body);
        $this->assertStringContainsString(self::CITIZEN_MARKER, $versions[1]->citizen_body);
        $this->assertStringContainsString(self::PUBLIC_MARKER, $versions[1]->public_body);
    }
}
