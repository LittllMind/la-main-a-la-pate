<?php

namespace Tests\Feature;

use App\Models\RepresentationType;
use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class C1SyntheticWorkflowAuditTest extends TestCase
{
    public function test_c1_document_metadata_full_edit_cycle()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);
        $doc = SubjectDocument::factory()->for($subject)->working()->create([
            'title' => 'Initial title',
        ]);

        $this->actingAs($admin)
            ->patch(route('subjects.documents.update', [$subject->slug, $doc->id]), [
                'title' => 'Corrected title',
                'document_date' => '2026-04-24',
                'document_type' => 'sommation',
                'author' => 'Corrected author',
                'recipient' => 'Corrected recipient',
                'source_reference' => 'Internal corrected ref',
                'representation_type' => 'scan',
                'redacted' => '1',
                'establishes' => 'Corrected establishes',
                'limitations' => 'Corrected limitations',
            ])
            ->assertRedirect();

        $doc->refresh();
        $this->assertEquals('Corrected title', $doc->title);
        $this->assertEquals('2026-04-24', $doc->document_date->format('Y-m-d'));
        $this->assertEquals('sommation', $doc->document_type);
        $this->assertEquals('Corrected author', $doc->author);
        $this->assertEquals('Corrected recipient', $doc->recipient);
        $this->assertEquals('Internal corrected ref', $doc->source_reference);
        $this->assertEquals(RepresentationType::Scan, $doc->representation_type);
        $this->assertTrue($doc->redacted);
        $this->assertEquals('Corrected establishes', $doc->establishes);
        $this->assertEquals('Corrected limitations', $doc->limitations);

        $this->actingAs($admin)
            ->patch(route('subjects.documents.update', [$subject->slug, $doc->id]), [
                'redacted' => '0',
            ])
            ->assertRedirect();
        $doc->refresh();
        $this->assertFalse($doc->redacted);
    }
}
