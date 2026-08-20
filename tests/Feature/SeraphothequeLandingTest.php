<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeraphothequeLandingTest extends TestCase
{
    public function test_landing_links_to_civic_subject_public_show(): void
    {
        $response = $this->get('/seraphotheque');
        $response->assertOk();

        $content = $response->getContent();

        $subjectPath = route(
            'subjects.show',
            'seraphotheque-situation-2026',
            false
        );

        // --- Ancien CTA générique absent ---
        $this->assertStringNotContainsString('href="/sujets"', $content);
        $this->assertStringNotContainsString('Découvrir les sujets publics', $content);

        // --- Nouveau CTA vers le Subject civique présent ---
        $this->assertStringContainsString(
            $subjectPath,
            $content,
            'Landing should link to civic subject public show'
        );
        $this->assertStringContainsString(
            'Consulter le dossier civique documenté',
            $content
        );
    }
}
