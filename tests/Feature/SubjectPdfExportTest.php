<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use PDF;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class SubjectPdfExportTest extends TestCase
{
    public function test_authenticated_owner_can_view_subject_pdf()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $user->id, 'status' => 'published']);

        $response = $this->actingAs($user)
            ->get(route('subjects.pdf.show', $subject->slug));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_authenticated_owner_can_download_subject_pdf()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $user->id, 'status' => 'published']);

        $response = $this->actingAs($user)
            ->get(route('subjects.pdf.download', $subject->slug));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition');
    }

    public function test_guest_cannot_view_subject_pdf()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $user->id, 'status' => 'published']);

        $this->get(route('subjects.pdf.show', $subject->slug))
            ->assertRedirect('/login');
    }

    public function test_admin_can_export_multiple_subjects_pdf()
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'role' => 'admin']);
        $subjectA = Subject::factory()->create([
            'user_id' => $admin->id,
            'citizen_body' => 'Body A',
            'public_body' => 'Body A public',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);
        $subjectB = Subject::factory()->create([
            'user_id' => $admin->id,
            'citizen_body' => 'Body B',
            'public_body' => 'Body B public',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);

        $response = $this->actingAs($admin)
            ->get('/sujets/export-pdf?subjects[]=' . $subjectA->slug . '&subjects[]=' . $subjectB->slug);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_export_contains_subject_title()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $user->id, 'status' => 'published', 'title' => 'Rapport énergie']);

        $pdf = PDF::loadView('subjects.pdf.show', [
            'subject' => $subject,
            'body' => $subject->renderBody(),
        ]);

        $parser = new Parser();
        $document = $parser->parseContent($pdf->output());

        $this->assertStringContainsString('Rapport énergie', $document->getText());
    }
}
