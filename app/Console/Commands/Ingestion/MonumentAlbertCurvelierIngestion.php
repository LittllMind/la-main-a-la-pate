<?php

namespace App\Console\Commands\Ingestion;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectVersion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Intégration idempotente du Subject Monument aux morts — Albert Curvelier.
 *
 * Sources canoniques (ne pas modifier) :
 *   - database/seeders/data/monument-instruction-v0.1.md
 *   - database/seeders/data/monument-citoyen-v0.1.md
 *
 * Sujet créé avec :
 *   - access_level = collaborators_only
 *   - status / citizen_status / public_status = draft
 *   - public_body = null
 *   - public_is_listed = false
 */
class MonumentAlbertCurvelierIngestion extends Command
{
    protected $signature = 'app:monument-albert-curvelier-ingestion
                            {--user-id=1 : ID de l\'auteur du sujet}
                            {--dry-run : Affiche le plan sans modifier la DB}
                            {--force : Crée/met à jour malgré l\'existence}';

    protected $description = 'Ingère le Subject Monument aux morts — Albert Curvelier (draft restreint).';

    private const SLUG = 'monument-aux-morts-albert-curvelier';
    private const TITLE = 'Monument aux morts du Rozier — Albert Curvelier';
    private const CATEGORY_SLUG = 'patrimoine-memoire';
    private const SUB_CATEGORY_SLUG = 'monuments-lieux-de-memoire';

    public function handle(): int
    {
        $userId = (int) $this->option('user-id');
        $author = User::find($userId);

        if (! $author) {
            $this->error("Utilisateur {$userId} introuvable.");

            return self::FAILURE;
        }

        $category = Category::where('slug', self::CATEGORY_SLUG)->first();
        $subCategory = $category ? SubCategory::where('slug', self::SUB_CATEGORY_SLUG)->where('category_id', $category->id)->first() : null;

        if (! $category || ! $subCategory) {
            $this->error("Taxonomie introuvable : category='" . self::CATEGORY_SLUG . "', sub_category='" . self::SUB_CATEGORY_SLUG . "'.");

            return self::FAILURE;
        }

        $instructionPath = database_path('seeders/data/monument-instruction-v0.1.md');
        $citizenPath = database_path('seeders/data/monument-citoyen-v0.1.md');

        foreach ([$instructionPath, $citizenPath] as $path) {
            if (! is_file($path)) {
                $this->error("Source manquante : {$path}");

                return self::FAILURE;
            }
        }

        $instructionBody = $this->readSource($instructionPath);
        $citizenBody = $this->readSource($citizenPath);

        $instructionSha = hash('sha256', $instructionBody);
        $citizenSha = hash('sha256', $citizenBody);

        $this->info("INSTRUCTION SHA256 : {$instructionSha}");
        $this->info("CITOYEN    SHA256 : {$citizenSha}");

        if ($this->option('dry-run')) {
            $this->info('[DRY-RUN] Aucune mutation.');

            return self::SUCCESS;
        }

        $existing = Subject::where('slug', self::SLUG)->first();

        if ($existing && ! $this->option('force')) {
            $this->info("Subject existant : ID {$existing->id} — aucune modification (utiliser --force pour mettre à jour).");

            return self::SUCCESS;
        }

        $subject = Subject::updateOrCreate(
            ['slug' => self::SLUG],
            [
                'user_id' => $author->id,
                'category_id' => $category->id,
                'sub_category_id' => $subCategory->id,
                'theme' => $category->name,
                'title' => self::TITLE,
                'body' => $instructionBody,
                'citizen_body' => $citizenBody,
                'public_body' => null,
                'status' => 'draft',
                'citizen_status' => 'draft',
                'public_status' => 'draft',
                'public_is_listed' => false,
                'access_level' => Subject::ACCESS_LEVEL_COLLABORATORS_ONLY,
                'published_at' => null,
                'citizen_published_at' => null,
                'public_published_at' => null,
            ]
        );

        $subject->load('collaborators');

        $this->attachCollaborators($subject, $author);

        $versionChanged = true;
        if ($existing) {
            $lastVersion = $subject->versions()->first();
            $versionChanged = ! $lastVersion
                || $lastVersion->body !== $instructionBody
                || $lastVersion->citizen_body !== $citizenBody;
        }

        if ($versionChanged) {
            SubjectVersion::create([
                'subject_id' => $subject->id,
                'user_id' => $author->id,
                'body' => $instructionBody,
                'citizen_body' => $citizenBody,
                'public_body' => null,
                'change_summary' => 'Initial private draft integration — human review.',
            ]);

            $this->info('SubjectVersion créée.');
        }

        $this->info("Subject ID {$subject->id} — slug {$subject->slug}");
        $this->info('Collaborateurs : ' . $subject->collaborators->pluck('name')->implode(', '));

        return self::SUCCESS;
    }

    private function readSource(string $path): string
    {
        $raw = file_get_contents($path);
        // Normalisation : retirer l'eventuel H1 du pack pour eviter le doublon
        // avec le titre affiche par le template Blade.
        $title = self::TITLE;
        $patterns = [
            '/^#\s+' . preg_quote($title, '/') . '\s*\r?\n/i',
            '/^#\s+Albert Curvelier et le monument aux morts du Rozier\s*\r?\n/i',
        ];
        foreach ($patterns as $pattern) {
            $raw = preg_replace($pattern, '', $raw, 1);
        }

        return trim($raw);
    }

    private function attachCollaborators(Subject $subject, User $author): void
    {
        $wantedNames = [
            'Aurélien',
            'Aurelien',
            'Aurélien Tisserand',
            'Patrice',
        ];

        $users = User::query()
            ->where(function ($q) use ($wantedNames) {
                foreach ($wantedNames as $name) {
                    $q->orWhere('name', 'LIKE', '%' . $name . '%')
                        ->orWhere('username', 'LIKE', '%' . $name . '%')
                        ->orWhere('pseudonyme', 'LIKE', '%' . $name . '%');
                }
            })
            ->get();

        $patrice = $users->first(fn (User $u) => str_contains(strtolower($u->name), 'patrice'));

        if (! $patrice) {
            $this->warn('Compte Patrice introuvable en base locale. Création d\'un compte local de substitution.');
            $patrice = $this->createLocalPatrice();
        }

        $collaboratorIds = collect([$author->id, $patrice->id])
            ->unique()
            ->values()
            ->all();

        $subject->collaborators()->sync($collaboratorIds);
    }

    private function createLocalPatrice(): User
    {
        $email = 'patrice-' . time() . '@example.test';
        $username = 'patrice_' . time();

        return User::create([
            'name' => 'Patrice',
            'pseudonyme' => 'patrice',
            'email' => $email,
            'username' => $username,
            'password' => 'password',
            'email_verified_at' => now(),
            'role' => 'citoyen',
            'requires_setup' => false,
        ]);
    }
}
