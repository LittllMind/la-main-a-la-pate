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

        if (! $admin || ! $mod) {
            return;
        }

        $themes = $this->themeMap();

        // Structure representant la base de production actuelle :
        // 16 sujets, visibility citoyen, 4 publies / 12 brouillons,
        // repartis sur 6 themes, auteurs admin + moderateurs.
        $subjects = [
            // ---- Infrastructures & Travaux / Batiments communaux (3) ----
            [
                'user_id' => $admin->id,
                'theme_text' => 'Infrastructures & Travaux',
                'sub_text' => 'Bâtiments communaux',
                'title' => 'PAC : chauffage et climatisation',
                'slug' => 'pac-chauffage-climatisation',
                'body' => "# PAC : chauffage et climatisation\n\n- [x] Diagnostic initial\n- [ ] Consultation des exploitants\n- [ ] Chiffrage des travaux\n\nDocument de travail interne.",
                'status' => 'published', 'visibility' => 'citoyen', 'published_at' => now()->subDays(3),
            ],
            [
                'user_id' => $admin->id,
                'theme_text' => 'Infrastructures & Travaux',
                'sub_text' => 'Bâtiments communaux',
                'title' => 'Piscine communale',
                'slug' => 'piscine-communale',
                'body' => "# Piscine communale\n\nPoints à clarifier : entretien, ouverture estivale, budget eau.",
                'status' => 'draft', 'visibility' => 'citoyen',
            ],
            [
                'user_id' => $admin->id,
                'theme_text' => 'Infrastructures & Travaux',
                'sub_text' => 'Bâtiments communaux',
                'title' => 'Salle des fêtes',
                'slug' => 'salle-des-fetes',
                'body' => "# Salle des fêtes\n\nÉtat des lieux, programmation et réglementation intérieure.",
                'status' => 'draft', 'visibility' => 'citoyen',
            ],

            // ---- Environnement & Cadre de vie / Camping & espaces verts (2) ----
            [
                'user_id' => $admin->id,
                'theme_text' => 'Environnement & Cadre de vie',
                'sub_text' => 'Camping & espaces verts',
                'title' => 'Camping municipal : bénévolat et masse salariale',
                'slug' => 'camping-municipal-benevolat-masse-salariale',
                'body' => "# Camping municipal\n\nOrganisation de la saison, plannings et répartition du travail.",
                'status' => 'published', 'visibility' => 'citoyen', 'published_at' => now()->subDays(8),
            ],
            [
                'user_id' => $admin->id,
                'theme_text' => 'Environnement & Cadre de vie',
                'sub_text' => 'Camping & espaces verts',
                'title' => 'Lotissement des Faisses — actions collectives',
                'slug' => 'lotissement-des-faisses-actions-collectives',
                'body' => "# Lotissement des Faisses\n\nRépertoire des actions collectives, documents utiles et contacts.",
                'status' => 'draft', 'visibility' => 'citoyen',
            ],

            // ---- Conseil municipal & Gouvernance (4) ----
            [
                'user_id' => $admin->id,
                'theme_text' => 'Conseil municipal & Gouvernance',
                'sub_text' => 'Conseils municipaux',
                'title' => 'Déplacement mairie',
                'slug' => 'deplacement-mairie',
                'body' => "# Déplacement de la mairie\n\nChronologie et documents liés au projet.",
                'status' => 'draft', 'visibility' => 'citoyen',
            ],
            [
                'user_id' => $admin->id,
                'theme_text' => 'Conseil municipal & Gouvernance',
                'sub_text' => 'Conseils municipaux',
                'title' => 'Ressources humaines',
                'slug' => 'ressources-humaines',
                'body' => "# Ressources humaines\n\nEffectifs, contrats et suivi administratif.",
                'status' => 'draft', 'visibility' => 'citoyen',
            ],
            [
                'user_id' => $admin->id,
                'theme_text' => 'Conseil municipal & Gouvernance',
                'sub_text' => 'Budget communal',
                'title' => 'SIVU',
                'slug' => 'sivu',
                'body' => "# SIVU\n\nInterrogations budgétaires et partages de compétences.",
                'status' => 'draft', 'visibility' => 'citoyen',
            ],
            [
                'user_id' => $admin->id,
                'theme_text' => 'Conseil municipal & Gouvernance',
                'sub_text' => 'Conseils municipaux',
                'title' => 'Comptes-rendus du conseil municipal — archive',
                'slug' => 'comptes-rendus-du-conseil-municipal-archive',
                'body' => "# Comptes-rendus du conseil municipal\n\nArchive des CR triés par année.",
                'status' => 'draft', 'visibility' => 'citoyen',
            ],

            // ---- Infrastructures & Travaux / Ponts & voirie (1) ----
            [
                'user_id' => $admin->id,
                'theme_text' => 'Infrastructures & Travaux',
                'sub_text' => 'Ponts & voirie',
                'title' => 'AOT — autorisation d’occupation temporaire',
                'slug' => 'aot-autorisation-occupation-temporaire',
                'body' => "# AOT\n\nDemande, renouvellement et suivi des autorisations.",
                'status' => 'draft', 'visibility' => 'citoyen',
            ],

            // ---- Patrimoine & Mémoire / Monuments & lieux de mémoire (2) ----
            [
                'user_id' => $mod->id,
                'theme_text' => 'Patrimoine & Mémoire',
                'sub_text' => 'Monuments & lieux de mémoire',
                'title' => 'Travaux église',
                'slug' => 'travaux-eglise',
                'body' => "# Travaux église\n\nÉtat descriptif, devis et calendrier.",
                'status' => 'draft', 'visibility' => 'citoyen',
            ],
            [
                'user_id' => $mod->id,
                'theme_text' => 'Patrimoine & Mémoire',
                'sub_text' => 'Monuments & lieux de mémoire',
                'title' => 'Maison Doy',
                'slug' => 'maison-doy',
                'body' => "# Maison Doy\n\nHistorique, propriété et projets de valorisation.",
                'status' => 'draft', 'visibility' => 'citoyen',
            ],

            // ---- Éducation & Jeunesse / Écoles & cantines (1) ----
            [
                'user_id' => $admin->id,
                'theme_text' => 'Éducation & Jeunesse',
                'sub_text' => 'Écoles & cantines',
                'title' => 'École',
                'slug' => 'ecole',
                'body' => "# École\n\nInscriptions, effectifs et cantine.",
                'status' => 'draft', 'visibility' => 'citoyen',
            ],

            // ---- Vie du village & Actualités (3) ----
            [
                'user_id' => $mod->id,
                'theme_text' => 'Vie du village & Actualités',
                'sub_text' => 'Actualités',
                'title' => 'Le monument aux morts : un nom fantôme ?',
                'slug' => 'le-monument-aux-morts-un-nom-fantome',
                'body' => "# Monument aux morts\n\nEnquête sur les noms manquants du monument.",
                'status' => 'published', 'visibility' => 'citoyen', 'published_at' => now()->subDays(12),
            ],
            [
                'user_id' => $admin->id,
                'theme_text' => 'Vie du village & Actualités',
                'sub_text' => 'Actualités',
                'title' => 'Pont cassé de la Muse — restauration et financements',
                'slug' => 'pont-casse-de-la-muse-restauration',
                'body' => "# Pont cassé de la Muse\n\nRestauration, passerelle provisoire et financements.",
                'status' => 'published', 'visibility' => 'citoyen', 'published_at' => now()->subDays(6),
            ],
            [
                'user_id' => $mod->id,
                'theme_text' => 'Vie du village & Actualités',
                'sub_text' => 'Séraphothèque',
                'title' => 'Résumé de l’affaire Séraphothèque',
                'slug' => 'resume-affaire-seraphotheque',
                'body' => "# Affaire Séraphothèque\n\nChronologie synthétique et points de vigilance.",
                'status' => 'draft', 'visibility' => 'citoyen',
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
