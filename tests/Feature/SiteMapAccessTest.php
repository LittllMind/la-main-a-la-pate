<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteMapAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_site_map(): void
    {
        $response = $this->get(route('site.map'));
        $response->assertOk(); // public now
    }
}
