<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\User;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $mod   = User::where('role', 'moderator')->first();
        $cit   = User::where('role', 'citoyen')->first();

        if (! $admin || ! $mod || ! $cit) {
            return;
        }

        $themes = $this->themeMap();

        $subjects = [
            [
                'user_id' => $admin->id,
                'theme_text' => 'Conseil municipal & Gouvernance',
                'sub_text' => 'Conseils municipaux',
                'title' => 'Réunion du conseil municipal — compte-rendu',
                'slug' => 'reunion-conseil-municipal-compte-rendu',
                'body' => "# Compte-rendu de la réunion du conseil municipal\n\nDate : 15 juin 2026\n\n## Points abordés\n\n- Budget communal 2026-2027\n- Travaux rue Louis Armand\n- Séraphothèque : statut du local\n\n> Cette information est publique et accessible à tous.",
                'status' => 'published', 'visibility' => 'public', 'published_at' => now()->subDays(2),
            ],
            [
                'user_id' => $mod->id,
                'theme_text' => 'Infrastructures & Travaux',
                'sub_text' => 'Ponts & voirie',
                'title' => 'Projet d’aménagement centre-bourg — brouillon',
                'slug' => 'projet-amenagement-centre-bourg-brouillon',
                'body' => "# Projet d’aménagement centre-bourg\n\n## État d’avancement\n\n- [ ] Étude d’impact\n- [ ] Consultation citoyenne\n- [x] Cahier des charges\n\n> Document de travail interne. Accès réservé aux citoyens connectés.",
                'status' => 'draft', 'visibility' => 'citoyen',
            ],
            [
                'user_id' => $admin->id,
                'theme_text' => 'Patrimoine & Mémoire',
                'sub_text' => 'Monuments & lieux de mémoire',
                'title' => 'Notes préliminaires — patrimoine bâti',
                'slug' => 'notes-preliminaires-patrimoine-bati',
                'body' => "# Notes préliminaires sur le patrimoine bâti du Rozier\n\n## Inventaire provisoire\n\n| Bâtiment | État | Priorité |\n|----------|------|----------|\n| Ancienne école | Bon | Moyenne |\n| Mairie | Bon | Faible |\n| Église | À restaurer | Haute |\n\n> Document confidentiel. Accès restreint à l’équipe administrative.",
                'status' => 'draft', 'visibility' => 'admin',
            ],
            [
                'user_id' => $cit->id,
                'theme_text' => 'Environnement & Cadre de vie',
                'sub_text' => 'Camping & espaces verts',
                'title' => 'Sortie naturaliste — photos et observations',
                'slug' => 'sortie-naturaliste-photos-observations',
                'body' => "# Sortie naturaliste du 12 juin 2026\n\n## Observations\n\n- Orchidée sauvage (Orchis militaris) au bord du chemin de la Serre\n- Chevreuil dans le bois de la Vaysse\n- Nid de milan noir repéré au rocher de la Lauzière\n\n> Contenu partagé par un citoyen. Visible par tous les membres connectés.",
                'status' => 'published', 'visibility' => 'citoyen', 'published_at' => now()->subDays(5),
            ],
            [
                'user_id' => $admin->id,
                'theme_text' => 'Vie du village & Actualités',
                'sub_text' => 'Séraphothèque',
                'title' => 'Dossier juridique — autorisation d’occupation',
                'slug' => 'dossier-juridique-autorisation-occupation',
                'body' => "# Dossier juridique : autorisation d’occupation temporaire\n\n## Documents à fournir\n\n1. Attestation d’assurance\n2. Plan de l’occupation\n3. Engagement de remise en état\n\n> Ce document est sensible. Accès strictement réservé à l’équipe administrative.",
                'status' => 'draft', 'visibility' => 'admin',
            ],
            [
                'user_id' => $mod->id,
                'theme_text' => 'Vie du village & Actualités',
                'sub_text' => 'Événements & fêtes',
                'title' => 'Programme des fêtes de la Saint-Jean 2026',
                'slug' => 'programme-fetes-saint-jean-2026',
                'body' => "# Programme des fêtes de la Saint-Jean 2026\n\n## Samedi 20 juin\n\n- 18h : Apéritif communal sur la place\n- 20h : Repas partagé (inscription obligatoire)\n- 22h : Feu de la Saint-Jean et animations\n\n## Dimanche 21 juin\n\n- 10h : Messe en plein air\n- 14h : Jeux traditionnels pour enfants\n\n> Événement public. Tout le monde est le bienvenu.",
                'status' => 'published', 'visibility' => 'public', 'published_at' => now()->subDays(1),
            ],
        ];

        foreach ($subjects as $s) {
            $catId = $themes[$s['theme_text']]['id'] ?? null;
            $subId = $themes[$s['theme_text']]['subs'][$s['sub_text']] ?? null;

            Subject::create([
                'user_id' => $s['user_id'],
                'theme' => $s['theme_text'],
                'category_id' => $catId,
                'sub_category_id' => $subId,
                'title' => $s['title'],
                'slug' => $s['slug'],
                'body' => $s['body'],
                'status' => $s['status'],
                'visibility' => $s['visibility'],
                'published_at' => $s['published_at'] ?? null,
            ]);
        }
    }

    private function themeMap(): array
    {
        $map = [];
        $categories = Category::with('subCategories')->get();

        foreach ($categories as $category) {
            $subs = [];
            foreach ($category->subCategories as $sub) {
                $subs[$sub->name] = $sub->id;
            }
            $map[$category->name] = [
                'id' => $category->id,
                'subs' => $subs,
            ];
        }

        return $map;
    }
}
