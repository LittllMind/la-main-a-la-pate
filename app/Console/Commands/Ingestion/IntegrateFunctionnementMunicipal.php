<?php

namespace App\Console\Commands\Ingestion;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectVersion;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Intégration locale du sujet LMALP "Fonctionnement municipal".
 *
 * Corps sources :
 *   - INSTRUCTION V0.2 → body (working)
 *   - CITOYEN V0.2    → citizen_body
 *   - public_body       → NULL
 *
 * La commande est idempotente par slug : une relance ne duplique ni le
 * Subject ni la SubjectVersion si les représentations n'ont pas changé.
 */
class IntegrateFunctionnementMunicipal extends Command
{
    protected $signature = 'app:integrate-functionnement-municipal
                            {--pack-path= : Chemin vers le dossier contenant les fichiers canoniques}
                            {--user-id=1 : ID de l\'auteur/admin}
                            {--dry-run : Analyser les sources sans muter la DB}';

    protected $description = 'Intègre le Subject LMALP "Fonctionnement municipal et information des conseillers".';

    private const SLUG = 'fonctionnement-municipal';
    private const TITLE = 'Fonctionnement municipal et information des conseillers';
    private const CATEGORY_SLUG = 'conseil-municipal-gouvernance';
    private const SUB_CATEGORY_SLUG = 'conseils-municipaux';
    private const INSTRUCTION_FILE = 'LMALP-FONCTIONNEMENT-MUNICIPAL-INSTRUCTION-V0.2.md';
    private const CITIZEN_FILE = 'LMALP-FONCTIONNEMENT-MUNICIPAL-CITOYEN-V0.2.md';

    public function handle(): int
    {
        $packPath = $this->option('pack-path');

        if (! $packPath || ! is_dir($packPath)) {
            $this->error("Le dossier pack est obligatoire et doit exister : {$packPath}");

            return self::FAILURE;
        }

        $packPath = rtrim($packPath, '/');
        $instructionPath = $packPath . '/' . self::INSTRUCTION_FILE;
        $citizenPath = $packPath . '/' . self::CITIZEN_FILE;

        foreach ([$instructionPath, $citizenPath] as $path) {
            if (! File::isFile($path)) {
                $this->error("Fichier canonique manquant : {$path}");

                return self::FAILURE;
            }
        }

        $workingBody = File::get($instructionPath);
        $citizenBody = File::get($citizenPath);
        $publicBody = null;

        $instructionSha = hash('sha256', $workingBody);
        $citizenSha = hash('sha256', $citizenBody);

        $this->info("Source INSTRUCTION SHA : {$instructionSha}");
        $this->info("Source CITOYEN   SHA : {$citizenSha}");

        if ($this->option('dry-run')) {
            $this->info('[DRY-RUN] Analyse terminée. Aucune mutation.');

            return self::SUCCESS;
        }

        $author = User::find((int) $this->option('user-id', 1));
        $category = Category::where('slug', self::CATEGORY_SLUG)->first();
        $subCategory = SubCategory::where('slug', self::SUB_CATEGORY_SLUG)->first();

        if (! $author || ! $category || ! $subCategory) {
            $this->error('Prérequis manquants : user, category=' . self::CATEGORY_SLUG . ', sub_category=' . self::SUB_CATEGORY_SLUG . '.');

            return self::FAILURE;
        }

        $existing = Subject::where('slug', self::SLUG)->first();

        $subject = Subject::updateOrCreate(
            ['slug' => self::SLUG],
            [
                'user_id' => $author->id,
                'category_id' => $category->id,
                'sub_category_id' => $subCategory->id,
                'theme' => $category->name,
                'title' => self::TITLE,
                'body' => $workingBody,
                'citizen_body' => $citizenBody,
                'public_body' => $publicBody,
                'status' => 'draft',
                'citizen_status' => 'draft',
                'public_status' => 'draft',
                'public_is_listed' => false,
                'access_level' => Subject::ACCESS_LEVEL_STANDARD,
            ]
        );

        $bodyChanged = ! $existing || $existing->body !== $workingBody;
        $citizenChanged = ! $existing || $existing->citizen_body !== $citizenBody;
        $publicChanged = ! $existing || $existing->public_body !== $publicBody;

        if ($bodyChanged || $citizenChanged || $publicChanged) {
            SubjectVersion::create([
                'subject_id' => $subject->id,
                'user_id' => $author->id,
                'body' => $workingBody,
                'citizen_body' => $citizenBody,
                'public_body' => $publicBody,
                'change_summary' => 'Initial LMALP integration after JURIDIQUE and COMMUNICATION review.',
            ]);

            $this->info('SubjectVersion initiale créée.');
        } else {
            $this->info('Aucune SubjectVersion créée : représentations inchangées.');
        }

        $this->info("Subject ID {$subject->id} — slug {$subject->slug}");

        return self::SUCCESS;
    }
}
