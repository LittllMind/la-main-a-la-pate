<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteUniquenessTest extends TestCase
{
    /** @test */
    public function route_names_are_unique(): void
    {
        $routes = Route::getRoutes();
        $names = [];

        foreach ($routes as $route) {
            $name = $route->getName();
            if ($name === null) {
                continue;
            }

            if (isset($names[$name])) {
                $this->fail("Route name '{$name}' is defined more than once ({$names[$name]} and {$route->uri()}).");
            }
            $names[$name] = $route->uri();
        }

        $this->assertNotEmpty($names);
    }

    /** @test */
    public function home_route_resolves_correctly(): void
    {
        $this->assertSame(url('/'), route('home'));
    }
}
