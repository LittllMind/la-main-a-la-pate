<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteMapController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $sections = [
            [
                'label' => 'Vitrine publique',
                'icon' => '🏠',
                'color' => 'emerald',
                'items' => [
                    ['label' => 'Accueil / Séraphothèque', 'route' => route('home')],
                ],
            ],
            [
                'label' => 'Hall',
                'icon' => '📰',
                'color' => 'indigo',
                'items' => [
                    ['label' => 'Actualités', 'route' => route('hall')],
                    ['label' => 'Article', 'route' => '#'],
                ],
            ],
            [
                'label' => 'Espace sujets',
                'icon' => '📚',
                'color' => 'amber',
                'items' => [
                    ['label' => 'Liste des sujets', 'route' => route('subjects.index')],
                    ['label' => 'Créer un sujet', 'route' => route('subjects.create')],
                    ['label' => 'Importer des sujets', 'route' => route('subjects.import.create')],
                    ['label' => 'Fiche d\'un sujet', 'route' => '#'],
                    ['label' => 'Édition / collaborateurs / vote', 'route' => '#'],
                    ['label' => 'Documents et images', 'route' => '#'],
                    ['label' => 'Export PDF', 'route' => route('subjects.pdf.index')],
                ],
            ],
            [
                'label' => 'Communauté',
                'icon' => '💬',
                'color' => 'sky',
                'items' => [
                    ['label' => 'Espaces de discussion', 'route' => route('community.index')],
                    ['label' => 'Thème / topics', 'route' => '#'],
                    ['label' => 'Répondre', 'route' => '#'],
                ],
            ],
            [
                'label' => 'Mon compte',
                'icon' => '👤',
                'color' => 'rose',
                'items' => [
                    ['label' => 'Tableau de bord', 'route' => route('dashboard')],
                    ['label' => 'Mon profil', 'route' => route('profile.edit')],
                ],
            ],
        ];

        if ($user !== null && $user->isAdmin()) {
            $sections[] = [
                'label' => 'Administration',
                'icon' => '🛠️',
                'color' => 'slate',
                'items' => [
                    ['label' => 'Panneau admin', 'route' => route('admin.panel')],
                    ['label' => 'Gestion des actus (Hall)', 'route' => route('admin.posts.index')],
                    ['label' => 'Gestion des utilisateurs', 'route' => route('admin.users.index')],
                    ['label' => 'Sections vitrine', 'route' => route('admin.sections.index')],
                    ['label' => 'Journal d\'activité', 'route' => route('admin.activity')],
                ],
            ];
        }

        return view('site-map', compact('sections'));
    }
}
