<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeraphothequeV1ARefonteTest extends TestCase
{
    public function test_seraphotheque_public_route_returns_200_for_guest(): void
    {
        $response = $this->get('/seraphotheque');
        $response->assertOk();
    }

    public function test_hero_content_is_present_and_readable(): void
    {
        $response = $this->get('/seraphotheque');
        $content = $response->getContent();

        $this->assertStringContainsString('LA SÉRAPHOTHÈQUE', $content);
        $this->assertStringContainsString('Le Rozier · depuis 2022', $content);
        $this->assertStringContainsString('Friperie, brocante, bouquinerie, espace enfant', $content);
        $this->assertStringContainsString('Au cœur du village du Rozier, en Lozère', $content);
        $this->assertStringContainsString('gorges du Tarn', $content);
        $this->assertStringContainsString('Jonte', $content);
        $this->assertStringContainsString('2 rue Louis Armand', $content);
        $this->assertStringContainsString('48150 Le Rozier', $content);
    }

    public function test_main_univers_sections_are_present(): void
    {
        $response = $this->get('/seraphotheque');
        $content = $response->getContent();

        $this->assertStringContainsString('Découvrez la Séraphothèque', $content);
        $this->assertStringContainsString('FRIPERIE', $content);
        $this->assertStringContainsString('BROCANTE', $content);
        $this->assertStringContainsString('BOUQUINERIE', $content);
        $this->assertStringContainsString('ESPACE ENFANT', $content);

        $this->assertStringContainsString('Des vêtements qui ont encore beaucoup à vivre.', $content);
        $this->assertStringContainsString('Seconde main · pièces singulières · petits prix', $content);

        $this->assertStringContainsString('Artisanat &amp; créations', $content);
        $this->assertStringContainsString('Coups de cœur du moment', $content);
    }

    public function test_values_section_is_present(): void
    {
        $response = $this->get('/seraphotheque');
        $content = $response->getContent();

        $this->assertStringContainsString('RÉEMPLOI', $content);
        $this->assertStringContainsString('CRÉATIONS LOCALES', $content);
        $this->assertStringContainsString('PROXIMITÉ', $content);
        $this->assertStringContainsString('ÉCOLOGIQUE', $content);
    }

    public function test_since_2022_section_is_present(): void
    {
        $response = $this->get('/seraphotheque');
        $content = $response->getContent();

        $this->assertStringContainsString('Depuis 2022', $content);
        $this->assertStringContainsString('Installée dans l’ancienne école du Rozier', $content);
    }

    public function test_civic_block_is_present_and_linked(): void
    {
        $response = $this->get('/seraphotheque');
        $content = $response->getContent();

        $subjectPath = route('subjects.show', 'seraphotheque-situation-2026', false);
        $this->assertStringContainsString('La situation du local en 2026', $content);
        $this->assertStringContainsString('Comprendre la situation', $content);
        $this->assertStringContainsString($subjectPath, $content);
    }

    public function test_petition_link_is_present(): void
    {
        $response = $this->get('/seraphotheque');
        $content = $response->getContent();

        $this->assertStringContainsString('Signer la pétition', $content);
        $this->assertStringContainsString('change.org/p/pour-le-maintien-de-la-s', urldecode($content));
        $this->assertStringContainsString('rozier-48150', urldecode($content));
    }

    public function test_social_links_are_present(): void
    {
        $response = $this->get('/seraphotheque');
        $content = $response->getContent();

        $this->assertStringContainsString('instagram.com/seraphotheque', $content);
        $this->assertStringContainsString('facebook.com/seraphotheque', $content);
    }

    public function test_deprecated_prototype_content_is_absent(): void
    {
        $response = $this->get('/seraphotheque');
        $content = $response->getContent();

        $this->assertStringNotContainsString('🏠', $content);
        $this->assertStringNotContainsString('Recyclerie', $content);
        $this->assertStringNotContainsString('Réunions publiques', $content);
        $this->assertStringNotContainsString('parents de deux enfants scolarisés au village', $content);
        $this->assertStringNotContainsString('bail précaire', $content);
        $this->assertStringNotContainsString('@if(false)', $content);

        $this->assertStringNotContainsString('carousel-track', $content);
        $this->assertStringNotContainsString('setInterval(carouselNext', $content);
    }

    public function test_static_favorites_component_handles_zero_to_three_items(): void
    {
        $response = $this->get('/seraphotheque');
        $content = $response->getContent();

        $this->assertStringContainsString('Coups de cœur du moment', $content);

        $count = preg_match_all('/class="[^"]*favori-card[^"]*"/', $content, $matches);
        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertLessThanOrEqual(3, $count);
    }

    public function test_images_have_alt_and_lazy_loading(): void
    {
        $response = $this->get('/seraphotheque');
        $content = $response->getContent();

        $this->assertStringContainsString('<img', $content);
        $this->assertStringContainsString('alt=', $content);
        $this->assertStringContainsString('loading="lazy"', $content);
    }

    public function test_view_uses_seraphotheque_controller(): void
    {
        $response = $this->get('/seraphotheque');
        $response->assertViewIs('seraphotheque.index');
    }
}
