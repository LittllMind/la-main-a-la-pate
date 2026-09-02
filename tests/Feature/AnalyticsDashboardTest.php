<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('analytics.enabled', true);
        Config::set('analytics.hmac_key', 'test-hmac-key');
        Config::set('analytics.excluded_environments', []);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function recordDays(int $daysBack, int $visitors, string $path = '/'): void
    {
        for ($d = 0; $d <= $daysBack; $d++) {
            $date = now()->subDays($daysBack - $d);
            for ($v = 1; $v <= $visitors; $v++) {
                AnalyticsEvent::create([
                    'event_type' => 'page_view',
                    'visitor_key' => hash('sha256', "visitor-{$d}-{$v}"),
                    'path' => $path,
                    'user_agent_family' => 'Chrome',
                    'device_family' => 'Desktop',
                    'created_at' => $date,
                ]);
            }
        }
    }

    public function test_admin_dashboard_is_protected(): void
    {
        $this->get('/admin/analytics')->assertRedirect();

        $citizen = User::factory()->create(['role' => 'citoyen']);
        $this->actingAs($citizen)->get('/admin/analytics')->assertForbidden();
    }

    public function test_dashboard_renders_for_admin(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get('/admin/analytics');
        $response->assertOk();

        $content = html_entity_decode($response->getContent(), ENT_QUOTES, 'UTF-8');

        $this->assertStringContainsString('Audience LMALP', $content);
        $this->assertStringContainsString('Visiteurs journaliers uniques', $content);
        $this->assertStringContainsString('Visiteurs journaliers cumulés', $content);
        $this->assertStringContainsString('Visites approximatives', $content);
        $this->assertStringContainsString('Pages vues', $content);
    }

    public function test_dashboard_today_unique_visitors(): void
    {
        $admin = $this->admin();
        $this->recordDays(0, 3);

        $response = $this->actingAs($admin)->get('/admin/analytics');
        $content = html_entity_decode($response->getContent(), ENT_QUOTES, 'UTF-8');

        $this->assertStringContainsString('3', $content);
        $this->assertStringContainsString("unique aujourd'hui", $content);
    }

    public function test_dashboard_cumulative_daily_visitors_across_days(): void
    {
        $admin = $this->admin();
        $this->recordDays(2, 1);

        // 1 visiteur unique chaque jour × 3 jours = 3 visiteurs-journées (pas 1 unique cross-day)
        $this->actingAs($admin)->get('/admin/analytics?period=7')
            ->assertSee("3 visiteurs-journées");
    }

    public function test_dashboard_does_not_show_named_visitor_paths(): void
    {
        $admin = $this->admin();
        $this->recordDays(0, 1);

        $response = $this->actingAs($admin)->get('/admin/analytics');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringNotContainsString('visitor-', $content, 'Aucune clé brute ne doit apparaître.');
        $this->assertStringNotContainsString('Parcours détaillé', $content);
    }

    public function test_dashboard_top_pages(): void
    {
        $admin = $this->admin();

        AnalyticsEvent::create(['event_type' => 'page_view', 'visitor_key' => 'a', 'path' => '/seraphotheque']);
        AnalyticsEvent::create(['event_type' => 'page_view', 'visitor_key' => 'b', 'path' => '/']);
        AnalyticsEvent::create(['event_type' => 'page_view', 'visitor_key' => 'c', 'path' => '/']);

        $this->actingAs($admin)->get('/admin/analytics')
            ->assertSee('/')
            ->assertSee('/seraphotheque');
    }

    public function test_dashboard_top_documents(): void
    {
        $admin = $this->admin();

        AnalyticsEvent::create(['event_type' => 'page_view', 'visitor_key' => 'a', 'document_key' => 'doc-a', 'path' => '/']);
        AnalyticsEvent::create(['event_type' => 'document_download', 'visitor_key' => 'b', 'document_key' => 'doc-a', 'path' => '/']);
        AnalyticsEvent::create(['event_type' => 'document_view', 'visitor_key' => 'c', 'document_key' => 'doc-b', 'path' => '/']);

        $this->actingAs($admin)->get('/admin/analytics')
            ->assertSee('doc-a')
            ->assertSee('doc-b')
            ->assertSee('Vues documents')
            ->assertSee('Téléchargements');
    }

    public function test_dashboard_referrers_devices_browsers(): void
    {
        $admin = $this->admin();

        AnalyticsEvent::create([
            'event_type' => 'page_view',
            'visitor_key' => 'a',
            'path' => '/',
            'referrer_host' => 'google.com',
            'device_family' => 'Mobile',
            'user_agent_family' => 'Chrome',
        ]);

        $this->actingAs($admin)->get('/admin/analytics')
            ->assertSee('google.com')
            ->assertSee('Mobile')
            ->assertSee('Chrome');
    }
}
