<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\User;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $mod   = User::where('role', 'moderator')->first();
        $cit   = User::where('role', 'citoyen')->first();

        // Sécurité si un seeder précédent manque
        if (! $admin) { return; }

        $subjects = [
            [
                'user_id' => $admin->id, 'theme' => 'Vie du village',
                'title' => 'Réunion du conseil municipal — compte-rendu',
                'slug' => 'reunion-conseil-municipal-compte-rendu',
                'body' => "# Compte-rendu de la réunion du conseil municipal\n\nDate : 15 juin 2026\n\n## Points abordés\n\n- Budget communal 2026-2027\n- Travaux rue Louis Armand\n- Séraphothèque : statut du local\n\n> Cette information est publique et accessible à tous.",
                'status' => 'published', 'visibility' => 'public', 'published_at' => now()->subDays(2),
            ],
            [
                'user_id' => $mod->id, 'theme' => 'Urbanisme',
                'title' => 'Projet d’aménagement centre-bourg — brouillon',
                'slug' => 'projet-amenagement-centre-bourg-brouillon',
                'body' => "# Projet d’aménagement centre-bourg\n\n## État d’avancement\n\n- [ ] Étude d’impact\n- [ ] Consultation citoyenne\n- [x] Cahier des charges\n\n> Document de travail interne. Accès réservé aux citoyens connectés.",
                'status' => 'draft', 'visibility' => 'citoyen',
            ],
            [
                'user_id' => $admin->id, 'theme' => 'Mémoire',
                'title' => 'Notes préliminaires — patrimoine bâti',
                'slug' => 'notes-preliminaires-patrimoine-bati',
                'body' => "# Notes préliminaires sur le patrimoine bâti du Rozier\n\n## Inventaire provisoire\n\n| Bâtiment | État | Priorité |\n|----------|------|----------|\n| Ancienne école | Bon | Moyenne |\n| Mairie | Bon | Faible |\n| Église | À restaurer | Haute |\n\n> Document confidentiel. Accès restreint à l’équipe administrative.",
                'status' => 'draft', 'visibility' => 'admin',
            ],
            [
                'user_id' => $cit->id, 'theme' => 'Nature',
                'title' => 'Sortie naturaliste — photos et observations',
                'slug' => 'sortie-naturaliste-photos-observations',
                'body' => "# Sortie naturaliste du 12 juin 2026\n\n## Observations\n\n- Orchidée sauvage (Orchis militaris) au bord du chemin de la Serre\n- Chevreuil dans le bois de la Vaysse\n- Nid de milan noir repéré au rocher de la Lauzière\n\n> Contenu partagé par un citoyen. Visible par tous les membres connectés.",
                'status' => 'published', 'visibility' => 'citoyen', 'published_at' => now()->subDays(5),
            ],
            [
                'user_id' => $admin->id, 'theme' => 'Séraphothèque',
                'title' => 'Dossier juridique — autorisation d’occupation',
                'slug' => 'dossier-juridique-autorisation-occupation',
                'body' => "# Dossier juridique : autorisation d’occupation temporaire\n\n## Documents à fournir\n\n1. Attestation d’assurance\n2. Plan de l’occupation\n3. Engagement de remise en état\n\n> Ce document est sensible. Accès strictement réservé à l’équipe administrative.",
                'status' => 'draft', 'visibility' => 'admin',
            ],
            [
                'user_id' => $mod->id, 'theme' => 'Vie du village',
                'title' => 'Programme des fêtes de la Saint-Jean 2026',
                'slug' => 'programme-fetes-saint-jean-2026',
                'body' => "# Programme des fêtes de la Saint-Jean 2026\n\n## Samedi 20 juin\n\n- 18h : Apéritif communal sur la place\n- 20h : Repas partagé (inscription obligatoire)\n- 22h : Feu de la Saint-Jean et animations\n\n## Dimanche 21 juin\n\n- 10h : Messe en plein air\n- 14h : Jeux traditionnels pour enfants\n\n> Événement public. Tout le monde est le bienvenu.",
                'status' => 'published', 'visibility' => 'public', 'published_at' => now()->subDays(1),
            ],
        ];

        foreach ($subjects as $s) {
            Subject::create($s);
        }
    }
}
