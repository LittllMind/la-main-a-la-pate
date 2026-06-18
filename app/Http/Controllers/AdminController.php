<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.panel', [
            'routeGroups' => $this->gatherRoutes(),
        ]);
    }

    public function routes()
    {
        return view('admin.routes', [
            'routeGroups' => $this->gatherRoutes(),
        ]);
    }

    private function gatherRoutes(): array
    {
        $all = collect(Route::getRoutes()->getRoutesByName());

        $grouped = [
            'Public' => [],
            'Hall / Actualites' => [],
            'Sujets' => [],
            'Communaute' => [],
            'Admin' => [],
            'Auth / Profil' => [],
            'Autre' => [],
        ];

        foreach ($all as $name => $route) {
            $uri = $route->uri();
            $methods = $route->methods();
            $middleware = $route->gatherMiddleware();
            $link = $this->guessExampleUrl($uri);

            $item = [
                'name' => $name,
                'uri' => $uri,
                'methods' => array_diff($methods, ['HEAD']),
                'middleware' => $middleware,
                'link' => $link,
            ];

            if (str_starts_with($name, 'admin.')) {
                $grouped['Admin'][] = $item;
            } elseif (str_starts_with($name, 'subjects.')) {
                $grouped['Sujets'][] = $item;
            } elseif (str_starts_with($name, 'community.')) {
                $grouped['Communaute'][] = $item;
            } elseif (str_starts_with($name, 'auth.')) {
                $grouped['Auth / Profil'][] = $item;
            } elseif (in_array($name, ['login', 'register', 'logout', 'password.', 'verification.'])) {
                $grouped['Auth / Profil'][] = $item;
            } elseif (in_array($name, ['home', 'seraphotheque', 'about', 'contact', 'legal', 'privacy'])) {
                $grouped['Public'][] = $item;
            } elseif (str_starts_with($name, 'posts.') || $name === 'hall') {
                $grouped['Hall / Actualites'][] = $item;
            } else {
                $grouped['Autre'][] = $item;
            }
        }

        return array_filter($grouped, fn ($routes) => count($routes) > 0);
    }

    private function guessExampleUrl(string $uri): ?string
    {
        $replacements = [
            '{subject:slug}' => 'mon-sujet',
            '{slug}' => 'mon-espace',
            '{spaceSlug}' => 'animations',
            '{topicSlug}' => 'premier-topic',
            '{post}' => '1',
            '{token}' => 'token-exemple',
            '{id}' => '1',
            '{hash}' => 'hash',
        ];

        $url = str_replace(array_keys($replacements), array_values($replacements), $uri);

        if (! str_starts_with($url, '/')) {
            $url = '/' . $url;
        }

        // Routes parametrees purement administratives : on ne propose pas de lien direct.
        if (str_contains($url, '{') || str_contains($url, '}')) {
            return null;
        }

        return $url;
    }
}
