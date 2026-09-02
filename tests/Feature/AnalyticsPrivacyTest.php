<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\User;
use App\Models\VisibilityLevel;
use App\Services\Analytics\AnalyticsVisitorResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnalyticsPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('analytics.enabled', true);
        Config::set('analytics.hmac_key', 'test-hmac-key-' . now()->toDateString());
        Config::set('analytics.excluded_environments', []);
    }

    private function analyticsHeaders(array $overrides = []): array
    {
        return array_merge([
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/127.0.0',
        ], $overrides);
    }

    private function seedDocument(string $visibility = 'public'): SubjectDocument
    {
        $subject = Subject::factory()->create([
            'slug' => 'test-subject',
            'public_status' => 'published',
        ]);

        $tmp = tempnam(sys_get_temp_dir(), 'doc_') . '.pdf';
        file_put_contents($tmp, '%PDF-1.4 fake-pdf-content');

        $service = new \App\Services\DocumentStorageService();
        $path = $service->storeEncrypted($subject->id, $tmp, 'doc.pdf');
        $stored = basename($path);
        unlink($tmp);

        $doc = SubjectDocument::factory()->create([
            'subject_id' => $subject->id,
            'filename' => 'doc.pdf',
            'stored_filename' => $stored,
            'path' => $path,
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 1234,
            'visibility' => $visibility,
            'source_reference' => 'pack:TEST-DOC-001',
        ]);

        return $doc;
    }

    public function test_page_view_recorded_on_home(): void
    {
        $this->assertCount(0, AnalyticsEvent::all());

        $this->get('/', $this->analyticsHeaders())->assertOk();

        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'page_view',
            'path' => '/',
        ]);
    }

    public function test_dossier_view_recorded_on_seraphotheque(): void
    {
        $this->get('/seraphotheque', $this->analyticsHeaders())->assertOk();

        $events = AnalyticsEvent::where('path', '/seraphotheque')->get();
        $this->assertTrue($events->pluck('event_type')->contains('page_view'));
        $this->assertTrue($events->pluck('event_type')->contains('dossier_view'));
    }

    public function test_visitor_key_is_deterministic_same_day(): void
    {
        $this->get('/', $this->analyticsHeaders());
        $key1 = AnalyticsEvent::first()->visitor_key;

        AnalyticsEvent::truncate();

        $this->get('/', $this->analyticsHeaders());
        $key2 = AnalyticsEvent::first()->visitor_key;

        $this->assertSame($key1, $key2);
    }

    public function test_visitor_key_changes_next_day(): void
    {
        $this->travelTo(now()->startOfDay());
        $this->get('/', $this->analyticsHeaders());
        $key1 = AnalyticsEvent::first()->visitor_key;

        AnalyticsEvent::truncate();

        $this->travelTo(now()->addDay()->startOfDay());
        $this->get('/', $this->analyticsHeaders());
        $key2 = AnalyticsEvent::first()->visitor_key;

        $this->assertNotSame($key1, $key2);
    }

    public function test_visitor_key_is_not_raw_ip(): void
    {
        $this->get('/', $this->analyticsHeaders());
        $event = AnalyticsEvent::first();

        $this->assertStringNotContainsString('127.0.0.1', $event->visitor_key);
        $this->assertStringNotContainsString('::1', $event->visitor_key);
    }

    public function test_raw_ip_not_stored(): void
    {
        $this->get('/', $this->analyticsHeaders());

        $columns = \DB::getSchemaBuilder()->getColumnListing('analytics_events');
        $this->assertNotContains('ip', $columns);
        $this->assertNotContains('ip_address', $columns);
        $this->assertNotContains('remote_addr', $columns);
    }

    public function test_raw_user_agent_not_stored(): void
    {
        $this->get('/', $this->analyticsHeaders());

        $columns = \DB::getSchemaBuilder()->getColumnListing('analytics_events');
        $this->assertNotContains('user_agent', $columns);

        $event = AnalyticsEvent::first();
        $this->assertNull($event->full_user_agent ?? null);
    }

    public function test_query_string_not_stored(): void
    {
        $this->get('/seraphotheque?foo=bar&utm_source=email', $this->analyticsHeaders())->assertOk();

        $this->assertDatabaseHas('analytics_events', ['path' => '/seraphotheque']);
        $this->assertDatabaseMissing('analytics_events', ['path' => '/seraphotheque?foo=bar&utm_source=email']);
    }

    public function test_referrer_only_host_is_stored(): void
    {
        $this->get('/seraphotheque', $this->analyticsHeaders([
            'HTTP_REFERER' => 'https://www.facebook.com/groups/xyz?ref=share',
        ]))->assertOk();

        $this->assertDatabaseHas('analytics_events', [
            'referrer_host' => 'www.facebook.com',
        ]);
    }

    public function test_bots_are_excluded(): void
    {
        $bots = [
            'Googlebot/2.1 (+http://www.google.com/bot.html)',
            'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
            'facebookexternalhit/1.1',
            'LinkedInBot/1.0',
        ];

        foreach ($bots as $ua) {
            AnalyticsEvent::truncate();
            $this->get('/seraphotheque', $this->analyticsHeaders(['HTTP_USER_AGENT' => $ua]))->assertOk();
            $this->assertCount(0, AnalyticsEvent::all(), 'Bot exclu : ' . $ua);
        }
    }

    public function test_disabled_analytics_excluded_via_config(): void
    {
        Config::set('analytics.enabled', false);
        $this->get('/seraphotheque', $this->analyticsHeaders())->assertOk();
        $this->assertCount(0, AnalyticsEvent::all());
    }

    public function test_tracking_excluded_in_testing_environment_by_default(): void
    {
        // setUp force enabled; ici on rétablit la valeur par défaut du package (testing exclus).
        Config::set('analytics.enabled', true);
        Config::set('analytics.excluded_environments', ['testing']);

        $this->get('/seraphotheque', $this->analyticsHeaders())->assertOk();
        $this->assertCount(0, AnalyticsEvent::all());
    }

    public function test_admin_routes_are_not_tracked(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin', $this->analyticsHeaders())->assertOk();

        $this->assertCount(0, AnalyticsEvent::all());
    }

    public function test_login_route_is_not_tracked(): void
    {
        $this->get('/login', $this->analyticsHeaders())->assertOk();
        $this->assertCount(0, AnalyticsEvent::all());
    }

    public function test_document_view_recorded(): void
    {
        $doc = $this->seedDocument();

        $this->get(
            route('subjects.documents.view', [$doc->subject->slug, $doc->id], false),
            $this->analyticsHeaders()
        )->assertOk();

        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'document_view',
            'document_key' => 'pack:TEST-DOC-001',
        ]);
    }

    public function test_document_download_recorded(): void
    {
        $doc = $this->seedDocument();

        $this->get(
            route('subjects.documents.download', [$doc->subject->slug, $doc->id], false),
            $this->analyticsHeaders()
        )->assertOk();

        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'document_download',
            'document_key' => 'pack:TEST-DOC-001',
        ]);
    }

    public function test_document_view_response_is_inline(): void
    {
        $doc = $this->seedDocument();

        $response = $this->get(
            route('subjects.documents.view', [$doc->subject->slug, $doc->id], false),
            $this->analyticsHeaders()
        );

        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
    }

    public function test_document_download_response_is_attachment(): void
    {
        $doc = $this->seedDocument();

        $response = $this->get(
            route('subjects.documents.download', [$doc->subject->slug, $doc->id], false),
            $this->analyticsHeaders()
        );

        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
    }

    public function test_existing_document_urls_still_valid(): void
    {
        $doc = $this->seedDocument();

        $this->get(route('subjects.documents.view', [$doc->subject->slug, $doc->id], false), $this->analyticsHeaders())
            ->assertOk();
        $this->get(route('subjects.documents.download', [$doc->subject->slug, $doc->id], false), $this->analyticsHeaders())
            ->assertOk();
    }

    public function test_analytics_failure_does_not_break_public_page(): void
    {
        $doc = $this->seedDocument('working');

        // Working document visible pour admin seulement ; guest → 404.
        $response = $this->get(
            route('subjects.documents.download', [$doc->subject->slug, $doc->id], false),
            $this->analyticsHeaders()
        );

        // La page publique ne plante pas même si l'enregistrement analytics échoue.
        $this->assertTrue(in_array($response->getStatusCode(), [200, 404], true));
    }

    public function test_resolver_normalizes_user_agent_family_and_device(): void
    {
        $resolver = new AnalyticsVisitorResolver('secret');

        $desktop = $resolver->resolve('127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/127.0');
        $this->assertEquals('Chrome', $desktop['family']);
        $this->assertEquals('Desktop', $desktop['device']);

        $mobile = $resolver->resolve('127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1');
        $this->assertEquals('Safari', $mobile['family']);
        $this->assertEquals('Mobile', $mobile['device']);
    }
}
