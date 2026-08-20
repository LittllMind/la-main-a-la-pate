<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ExampleTest extends TestCase
{
    use RefreshDatabase;
    public function test_homepage_loads()
    {
        $response = $this->get('/');
        $response->assertOk();
    }
}
