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
            'sections' => $this->dashboardSections(),
        ]);
    }

    public function dashboardSections(): array
    {
        return [
            'Comptes' => [
                [
                    'title' => 'Utilisateurs',
                    'description' => 'Créer, modifier, supprimer les comptes et leurs rôles.',
                    'route' => route('admin.users.index'),
                    'actions' => [
                        ['label' => 'Liste', 'route' => route('admin.users.index')],
                        ['label' => 'Créer un compte', 'route' => route('admin.users.create')],
                    ],
                    'color' => 'indigo',
                    'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v.003A9.318 9.318 0 0112 21c-1.8 0-3.5-.5-5-1.4m10-1.928c1.274-1.067 2-2.634 2-4.25C17 9.516 14.761 7 12 7s-5 2.516-5 4.75c0 1.616.726 3.183 2 4.25M12 14.25c-1.8 0-3.5-.5-5-1.4m5 1.4v.003',
                ],
                [
                    'title' => 'Mon profil',
                    'description' => 'Modifier mon email, nom, commune ou mot de passe.',
                    'route' => route('profile.edit'),
                    'actions' => [
                        ['label' => 'Profil', 'route' => route('profile.edit')],
                        ['label' => 'Mot de passe', 'route' => route('profile.edit')],
                    ],
                    'color' => 'slate',
                    'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
                ],
            ],
            'Contenu' => [
                [
                    'title' => 'Hall / Articles',
                    'description' => 'Actualités publiées sur la page d’accueil.',
                    'route' => route('admin.posts.index'),
                    'actions' => [
                        ['label' => 'Liste', 'route' => route('admin.posts.index')],
                        ['label' => 'Créer', 'route' => route('admin.posts.create')],
                    ],
                    'color' => 'blue',
                    'icon' => 'M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9v12m0 0h-3.75m3.75 0v1.5m0-1.5H21M3 19.5v-12a2.25 2.25 0 012.25-2.25h13.5a2.25 2.25 0 012.25 2.25v12',
                ],
                [
                    'title' => 'Sections du Hall',
                    'description' => 'Ordre et contenu des blocs de la page d’accueil.',
                    'route' => route('admin.sections.index'),
                    'actions' => [
                        ['label' => 'Liste', 'route' => route('admin.sections.index')],
                        ['label' => 'Créer', 'route' => route('admin.sections.create')],
                    ],
                    'color' => 'orange',
                    'icon' => 'M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 00-1.3 4.146c2.7.818 5.5.416 7.955-1.223a11.619 11.619 0 011.013-7.135 9.479 9.479 0 00-1.045-7.325c-1.305-1.855-3.417-3.101-5.805-3.293a6.896 6.896 0 00-.567 0 6.896 6.896 0 00-.567 0c-2.388.192-4.5 1.438-5.805 3.293a9.479 9.479 0 00-1.045 7.325 11.619 11.619 0 011.013 7.135c2.455 1.639 5.255 2.041 7.955 1.223A2.25 2.25 0 0015 20.25h3.75A2.25 2.25 0 0021 18v-3.75',
                ],
                [
                    'title' => 'Sujets',
                    'description' => 'Documents de référence, images et export PDF.',
                    'route' => route('subjects.index'),
                    'actions' => [
                        ['label' => 'Liste', 'route' => route('subjects.index')],
                        ['label' => 'Créer', 'route' => route('subjects.create')],
                        ['label' => 'Importer ZIP', 'route' => route('subjects.import.create')],
                        ['label' => 'Export PDF global', 'route' => route('subjects.pdf.index')],
                    ],
                    'color' => 'amber',
                    'icon' => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',
                ],
            ],
            'Communauté & Outils' => [
                [
                    'title' => 'Communauté',
                    'description' => 'Espaces de discussion (masqué en navigation publique).',
                    'route' => route('community.index'),
                    'actions' => [
                        ['label' => 'Accéder', 'route' => route('community.index')],
                    ],
                    'color' => 'purple',
                    'icon' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m0 0a3 3 0 00-4.682 2.72M3 18.72a9.094 9.094 0 003.741.479 3 3 0 004.682-2.72m0 0a3 3 0 00-4.682-2.72M12 12a3 3 0 100-6 3 3 0 000 6z',
                ],
                [
                    'title' => 'Routes du site',
                    'description' => 'Liste complète des routes, groupées par namespace.',
                    'route' => route('admin.routes'),
                    'actions' => [
                        ['label' => 'Voir', 'route' => route('admin.routes')],
                    ],
                    'color' => 'emerald',
                    'icon' => 'M9 6.75V3m0 18v-3.75m6-12V3m0 18v-3.75m-9 3.75h12',
                ],
                [
                    'title' => 'Site public',
                    'description' => 'Retourner à l’accueil du site visible par tout le monde.',
                    'route' => url('/'),
                    'actions' => [
                        ['label' => 'Accueil', 'route' => url('/')],
                    ],
                    'color' => 'teal',
                    'icon' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
                ],
            ],
        ];
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
