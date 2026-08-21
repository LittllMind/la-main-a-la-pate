<?php

namespace App\Console\Commands\Ingestion;

use App\Models\Category;
use App\Models\RepresentationType;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\SubjectVersion;
use App\Models\User;
use App\Models\VisibilityLevel;
use App\Services\DocumentStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Ingestion locale du Subject Séraphothèque selon le manifest v1.1.
 *
 * Crée le Subject en draft et attache uniquement les SubjectDocument Working
 * dont les assets physiques existent réellement. Aucun placeholder n'est créé.
 */
class SeraphothequeIngestion extends Command
{
    protected $signature = 'app:seraphotheque-ingestion {--dry-run} {--force} {--pack-path=} {--user-id=1}';

    protected $description = 'Ingère le Subject Séraphothèque et ses documents Working dans LMALP (draft uniquement).';

    private string $packPath;
    private DocumentStorageService $storage;

    public function handle(): int
    {
        $this->storage = app(DocumentStorageService::class);
        $userId = (int) $this->option('user-id', 1);

        if ($this->option('pack-path')) {
            $this->packPath = $this->option('pack-path');
        } else {
            $this->error("L'option --pack-path est obligatoire.");
            return self::FAILURE;
        }

        if (! is_dir($this->packPath)) {
            $this->error("Pack introuvable : {$this->packPath}");
            return self::FAILURE;
        }

        $author = User::find($userId);
        $category = Category::find(10);
        $subCategory = SubCategory::find(14);

        if (! $author || ! $category || ! $subCategory) {
            $this->error("Prérequis manquants : user_id={$userId}, category_id=10, sub_category_id=14.");
            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('[DRY-RUN] Analyse du manifest uniquement.');
            $this->line('Subject: La Séraphothèque — Comprendre la situation');
            $this->line('Documents Working prévus : 5');
            $this->line('Documents publics absents : sommation expurgée, email Curvelier nettoyé, AOT publiable, profession de foi.');
            return self::SUCCESS;
        }

        $publicBody = $this->assemblePublicBody();
        $citizenBody = $this->assembleCitizenBody($publicBody);
        $workingBody = $this->assembleWorkingBody($publicBody);

        $existingSubject = Subject::where('slug', 'seraphotheque-situation-2026')->first();

        $subject = Subject::updateOrCreate(
            ['slug' => 'seraphotheque-situation-2026'],
            [
                'user_id' => $userId,
                'category_id' => 10,
                'sub_category_id' => 14,
                'theme' => $category->name,
                'title' => 'La Séraphothèque — Comprendre la situation',
                'body' => $workingBody,
                'citizen_body' => $citizenBody,
                'public_body' => $publicBody,
                'status' => 'draft',
                'citizen_status' => 'draft',
                'public_status' => 'draft',
            ]
        );

        $this->info("Subject créé/mis à jour : ID {$subject->id}, slug {$subject->slug}");

        // Snapshot des trois représentations uniquement si une d'elles a changé
        // ou si c'est la première création par le pipeline.
        $bodyChanged = ! $existingSubject || $existingSubject->body !== $workingBody;
        $citizenChanged = ! $existingSubject || $existingSubject->citizen_body !== $citizenBody;
        $publicChanged = ! $existingSubject || $existingSubject->public_body !== $publicBody;

        if ($bodyChanged || $citizenChanged || $publicChanged) {
            SubjectVersion::create([
                'subject_id' => $subject->id,
                'user_id' => $userId,
                'body' => $workingBody,
                'citizen_body' => $citizenBody,
                'public_body' => $publicBody,
                'change_summary' => 'Ingestion du pack Séraphothèque',
            ]);

            $this->info('SubjectVersion créée : snapshot des trois représentations.');
        } else {
            $this->info('Aucune SubjectVersion créée : représentations inchangées.');
        }

        $documents = [
            [
                'title' => 'Sommation du 24 avril 2026 — originale',
                'type' => 'sommation',
                'date' => '2026-04-24',
                'author' => 'Maître Eric de Jurquet, commissaire de justice',
                'recipient' => 'Aurélien Tisserand',
                'rep' => RepresentationType::Scan,
                'source' => $this->packPath . '/archives-LEX/OPS-originaux-LEX/04-procedure/sommation-huissier.pdf',
                'source_reference' => 'seraphotheque-pack:archives-LEX/OPS-originaux-LEX/04-procedure/sommation-huissier.pdf',
                'establishes' => 'Les demandes formellement adressées à Aurélien Tisserand par l\'intermédiaire du commissaire de justice mandaté par la commune.',
                'limitations' => 'La date et l\'heure exactes de la remise ne sont pas suffisamment lisibles sur la feuille correspondante. L\'acte ne constitue pas à lui seul une décision de justice tranchant l\'ensemble des questions juridiques en discussion.',
            ],
            [
                'title' => 'Recommandé avec A/R n° 1A 216 790 8709 — demande de documents',
                'type' => 'courrier administratif',
                'date' => '2026-04-16',
                'author' => 'La Séraphothèque',
                'recipient' => 'Mairie du Rozier',
                'rep' => RepresentationType::Scan,
                'source' => $this->packPath . '/archives-LEX/LEX-26-042/recommande-AR.pdf',
                'source_reference' => 'seraphotheque-pack:archives-LEX/LEX-26-042/recommande-AR.pdf',
                'establishes' => 'Dépôt postal d\'un courrier recommandé avec avis de réception adressé à la mairie.',
                'limitations' => 'Le contenu exact de la lettre n\'est pas entièrement transcrit dans les sources disponibles.',
            ],
            [
                'title' => 'Convention occupation 2025 — boutique',
                'type' => 'convention',
                'date' => '2025-04-01',
                'author' => 'Commune du Rozier',
                'recipient' => 'Anna El Agri et Aurélien Tisserand',
                'rep' => RepresentationType::Scan,
                'source' => $this->packPath . '/archives-LEX/LEX-26-042/bail-boutique-2025.pdf',
                'source_reference' => 'seraphotheque-pack:archives-LEX/LEX-26-042/bail-boutique-2025.pdf',
                'establishes' => 'Convention d\'occupation précaire signée pour l\'été 2025.',
                'limitations' => 'Représentation numérisée ; vérifier la qualité d\'OCR avant toute citation.',
            ],
            [
                'title' => 'Projet de convention 2026 — Tisserand / El Agri',
                'type' => 'convention',
                'date' => '2026-04-01',
                'author' => 'Commune du Rozier',
                'recipient' => 'Anna El Agri et Aurélien Tisserand',
                'rep' => RepresentationType::Scan,
                'source' => $this->packPath . '/archives-LEX/LEX-26-042/bail tisserand - el agri.pdf',
                'source_reference' => 'seraphotheque-pack:archives-LEX/LEX-26-042/bail tisserand - el agri.pdf',
                'establishes' => 'Projet de convention d\'occupation précaire proposé par la commune pour 2026.',
                'limitations' => 'Projet non signé par les deux parties à la date de mise à jour.',
            ],
            [
                'title' => 'Délibération du Conseil municipal du 27 avril 2026 — délégations au maire',
                'type' => 'délibération',
                'date' => '2026-04-27',
                'author' => 'Conseil municipal du Rozier',
                'recipient' => 'Maire',
                'rep' => RepresentationType::Copy,
                'source' => $this->packPath . '/archives-LEX/LEX-26-042/-DELEGATION MAIRE.doc',
                'source_reference' => 'seraphotheque-pack:archives-LEX/LEX-26-042/-DELEGATION MAIRE.doc',
                'establishes' => 'Délégation au maire pour la conclusion et la révision du louage de choses pour une durée ≤ 12 ans.',
                'limitations' => 'La date de transmission/publication portée sur le document (24/03/2026) est antérieure à la séance du 27/04/2026 ; anomalie documentaire à vérifier.',
            ],
        ];

        $knownRefs = collect($documents)->pluck('source_reference')->all();

        if ($this->option('force')) {
            $subject->documents()
                ->whereIn('source_reference', $knownRefs)
                ->get()
                ->each(function ($doc) {
                    if ($this->storage->exists($doc->path)) {
                        $this->storage->delete($doc->path);
                    }
                    $doc->delete();
                });
        }

        $position = 0;

        $createdCount = 0;
        foreach ($documents as $meta) {
            if (! $meta['source'] || ! file_exists($meta['source'])) {
                $this->warn("Asset manquant : {$meta['title']} — ignoré.");
                continue;
            }

            $currentSha256 = hash_file('sha256', $meta['source']);

            $existingDoc = SubjectDocument::where('subject_id', $subject->id)
                ->where('source_reference', $meta['source_reference'])
                ->first();

            $shouldStore = ! $existingDoc || $existingDoc->source_sha256 !== $currentSha256;

            if ($shouldStore) {
                $newPath = $this->storage->storeEncrypted(
                    $subject->id,
                    $meta['source'],
                    basename($meta['source'])
                );
            }

            if ($existingDoc) {
                if ($shouldStore) {
                    $oldPath = $existingDoc->path;

                    try {
                        $existingDoc->update([
                            'filename' => basename($meta['source']),
                            'stored_filename' => basename($newPath),
                            'path' => $newPath,
                            'disk' => 'documents',
                            'mime_type' => mime_content_type($meta['source']) ?: 'application/octet-stream',
                            'size' => filesize($meta['source']),
                            'title' => $meta['title'],
                            'document_date' => $meta['date'],
                            'document_type' => $meta['type'],
                            'author' => $meta['author'],
                            'recipient' => $meta['recipient'],
                            'representation_type' => $meta['rep'],
                            'redacted' => false,
                            'visibility' => VisibilityLevel::Working->value,
                            'establishes' => $meta['establishes'],
                            'limitations' => $meta['limitations'],
                            'position' => ++$position,
                            'source_sha256' => $currentSha256,
                        ]);
                    } catch (\Exception $e) {
                        // En cas d'échec DB, nettoyer le nouveau fichier pour éviter l'orphelin
                        if ($this->storage->exists($newPath)) {
                            $this->storage->delete($newPath);
                        }
                        throw $e;
                    }

                    // Supprimer l'ancien fichier seulement après succes de la mise à jour DB
                    if ($this->storage->exists($oldPath)) {
                        $this->storage->delete($oldPath);
                    }

                    $path = $newPath;
                } else {
                    // Contenu identique : pas de nouveau fichier, mettre à jour métadonnées seulement
                    $existingDoc->update([
                        'title' => $meta['title'],
                        'document_date' => $meta['date'],
                        'document_type' => $meta['type'],
                        'author' => $meta['author'],
                        'recipient' => $meta['recipient'],
                        'representation_type' => $meta['rep'],
                        'establishes' => $meta['establishes'],
                        'limitations' => $meta['limitations'],
                        'position' => ++$position,
                    ]);

                    $path = $existingDoc->path;
                }
            } else {
                $doc = SubjectDocument::create([
                    'subject_id' => $subject->id,
                    'filename' => basename($meta['source']),
                    'stored_filename' => basename($newPath),
                    'path' => $newPath,
                    'disk' => 'documents',
                    'mime_type' => mime_content_type($meta['source']) ?: 'application/octet-stream',
                    'size' => filesize($meta['source']),
                    'title' => $meta['title'],
                    'document_date' => $meta['date'],
                    'document_type' => $meta['type'],
                    'author' => $meta['author'],
                    'recipient' => $meta['recipient'],
                    'source_reference' => $meta['source_reference'],
                    'representation_type' => $meta['rep'],
                    'redacted' => false,
                    'visibility' => VisibilityLevel::Working->value,
                    'establishes' => $meta['establishes'],
                    'limitations' => $meta['limitations'],
                    'position' => ++$position,
                    'source_sha256' => $currentSha256,
                ]);

                $path = $newPath;
            }

            $createdCount++;
            $this->info("Document attaché : {$meta['title']}");
        }

        $this->info("Ingestion terminée : {$createdCount} document(s) Working attaché(s).");
        $this->info('Aucun SubjectDocument public créé (versions expurgées/nettoyées absentes).');

        return self::SUCCESS;
    }

    private function assemblePublicBody(): string
    {
        $index = $this->readPackFile('index.md');
        $ficheD = $this->readPackFile('fiche-d-sommation-24-avril-2026.md');
        $ficheE = $this->readPackFile('fiche-e-mail-maire-14-mai-2026.md');
        $ficheH = $this->readPackFile('fiche-h-demande-aot.md');
        $chronologie = $this->readPackFile('chronologie.md');
        $questions = $this->readPackFile('questions-ouvertes.md');

        $index = $this->cleanIndex($index);

        [$ficheEclean, $ficheF] = $this->splitFicheE($ficheE);

        $ficheD = $this->stripLeadingH1($ficheD);
        $ficheEclean = $this->stripLeadingH1($ficheEclean);
        $ficheF = $this->stripLeadingH1($ficheF);
        $ficheH = $this->stripLeadingH1($ficheH);

        $chronologie = $this->cleanChronologie($chronologie);
        $questions = $this->addAnchorId($questions, 'questions-ouvertes');

        $index = $this->insertAfterSection($index, '## 5.', "\n\n## Fiche — Sommation du 24 avril 2026\n\n" . $ficheD);
        $index = $this->insertAfterSection($index, '## 6.', "\n\n## Fiche — Email du maire du 14 mai 2026\n\n" . $ficheEclean);
        $index = $this->insertAfterSection($index, '## 7.', "\n\n## Fiche — Demandes de documents administratifs\n\n" . $ficheF);
        $index = $this->insertAfterSection($index, '## 8.', "\n\n## Fiche — Demande d'AOT du 16 juin 2026\n\n" . $ficheH);

        $index .= "\n\n---\n\n## Comparatif 2025 / 2026 {#comparatif-2025-2026}\n\n"
            . "Voir le tableau comparatif des conventions 2025 et 2026 (à venir).\n\n"
            . "---\n\n" . $chronologie . "\n\n---\n\n" . $questions;

        return $index;
    }

    private function assembleCitizenBody(string $publicBody): string
    {
        return $publicBody;
    }

    private function assembleWorkingBody(string $publicBody): string
    {
        $assets = "**Assets Working disponibles :**\n";
        $assets .= "- Sommation originale (PDF) — archives-LEX/OPS-originaux-LEX/04-procedure/sommation-huissier.pdf\n";
        $assets .= "- Recommandé A/R (PDF) — archives-LEX/LEX-26-042/recommande-AR.pdf\n";
        $assets .= "- Convention 2025 (PDF) — archives-LEX/LEX-26-042/bail-boutique-2025.pdf\n";
        $assets .= "- Projet 2026 (PDF) — archives-LEX/LEX-26-042/bail tisserand - el agri.pdf\n";
        $assets .= "- Délibération délégations (DOC) — archives-LEX/LEX-26-042/-DELEGATION MAIRE.doc\n\n";
        $assets .= "**Versions expurgées / publiques absentes :**\n";
        $assets .= "- Sommation expurgée\n";
        $assets .= "- Email Curvelier nettoyé\n";
        $assets .= "- AOT publiable\n";
        $assets .= "- Profession de foi originale\n";

        return $publicBody . "\n\n---\n\n" . $assets;
    }

    private function readPackFile(string $file): string
    {
        $path = $this->packPath . '/' . $file;
        if (! file_exists($path)) {
            return "\n\n> Fichier {$file} manquant.\n";
        }
        return file_get_contents($path);
    }

    private function cleanIndex(string $markdown): string
    {
        // 1. Supprimer H1 titre du pack (même avec whitespace précédent)
        $markdown = preg_replace('/^\s*#\s+LA\s+S[ÉE]RAPHOTH[ÈE]QUE.*?\n+/ui', "\n", $markdown);

        // 2. Supprimer noms de fichiers + backticks éventuels (regex multi-formes)
        $markdown = preg_replace('/`?fiche-[a-z0-9\-]+\.md`?/iu', '', $markdown);

        // 3. Nettoyer doubles backticks résiduels et backticks isolés devenus parasites
        $markdown = str_replace('``', '', $markdown);           // `` → rien
        $markdown = str_replace("'`", "'", $markdown);            // "'`" n'existe probablement pas
        $markdown = str_replace("`\n", "\n", $markdown);          // backtick avant newline
        $markdown = str_replace("\n`", "\n", $markdown);          // backtick après newline

        // 4. Normaliser hiérarchie
        $lines = explode("\n", $markdown);
        $newLines = [];
        foreach ($lines as $line) {
            if (preg_match('/^#\s+(\d+\.\s+.*)/', $line, $m)) {
                // Section principale en H1 du pack (2-12) → H2
                $newLines[] = '## ' . $m[1];
            } elseif (preg_match('/^##\s+(\d+\.\s+.*)/', $line, $m)) {
                // Section déjà H2 → garder H2
                $newLines[] = '## ' . $m[1];
            } elseif (preg_match('/^##\s+(.+)/', $line, $m)) {
                // Sous-section texte → H3
                $newLines[] = '### ' . $m[1];
            } else {
                $newLines[] = $line;
            }
        }
        $markdown = implode("\n", $newLines);

        // 5. Substitutions éditoriales validées
        $markdown = str_replace(
            'une sommation mandatée par la commune leur demande',
            'une sommation établie à la demande de la commune et adressée à Aurélien Tisserand demande',
            $markdown
        );
        $markdown = str_replace(
            'Les demandes ne commencent pas le 6 mai.',
            'Les premières demandes documentaires actuellement retrouvées remontent au 13 avril 2026.',
            $markdown
        );
        // Neutraliser mention non attestée
        $markdown = preg_replace(
            '/le Défenseur des Droits de la Lozère en copie\.?/u',
            "l'avocat en copie.",
            $markdown
        );
        // Titre §10
        $markdown = str_replace(
            '## 10. Un engagement du programme municipal à documenter',
            '## 10. Profession de foi : source originale à obtenir',
            $markdown
        );

        // 6. Navigation liens
        $markdown = preg_replace('/→\s*\*\*Voir la chronologie\*\*/u', '→ [Voir la chronologie](#chronologie)', $markdown);
        $markdown = preg_replace('/→\s*\*\*Voir les documents\*\*/u', '→ [Voir les documents](#documents)', $markdown);
        $markdown = preg_replace('/→\s*\*\*Voir les questio[^*]*\*\*/u', '→ [Voir les questions ouvertes](#questions-ouvertes)', $markdown);

        // §12
        $markdown = preg_replace('/→\s*\*\*Chronologie\*\*/u', '→ [Chronologie](#chronologie)', $markdown);
        $markdown = preg_replace('/→\s*\*\*Comparaison convention 2025[^*]*\*\*/u', '→ [Comparaison 2025 / projet 2026](#comparatif-2025-2026)', $markdown);
        $markdown = preg_replace('/→\s*\*\*Documents et fiches documentaires\*\*/u', '→ [Documents et fiches documentaires](#documents)', $markdown);
        $markdown = preg_replace('/→\s*\*\*Questions ouvertes\*\*/u', '→ [Questions ouvertes](#questions-ouvertes)', $markdown);

        // §2
        $markdown = preg_replace('/→\s*\*\*Comparer la convention 2025[^*]*\*\*/u', '→ [Comparer la convention 2025 et le projet 2026](#comparatif-2025-2026)', $markdown);

        // §5 / §6
        $markdown = preg_replace('/→\s*\*\*Consulter la fiche documentaire[^*]*version expurgée[^*]*\*\*/u', '→ Voir la fiche ci-dessous. Version expurgée à venir.', $markdown);
        $markdown = preg_replace('/→\s*\*\*Consulter les extraits approuvés[^*]*\*\*/u', '→ Version expurgée à venir.', $markdown);

        // 7. Compact lignes vides + whitespace trailing
        $markdown = preg_replace("/[ \t]+\n/", "\n", $markdown);
        $markdown = preg_replace("/\n{3,}/", "\n\n", $markdown);

        return trim($markdown);
    }

    private function splitFicheE(string $fiche): array
    {
        if (preg_match('/^\s*#\s*[`\']*fiche-f-demandes-documents\.md[`\']*\s*$/mi', $fiche, $m, PREG_OFFSET_CAPTURE)) {
            $ePart = trim(substr($fiche, 0, $m[0][1]));
            $fPart = trim(substr($fiche, $m[0][1] + strlen($m[0][0])));
            // Nettoyer lignes artefacts restantes
            $fPart = preg_replace('/^\s*#\s*[`\']*fiche-f-demandes-documents\.md[`\']*\s*\n*/mi', '', $fPart);
            $fPart = preg_replace('/^\s*#\s+Demandes de documents administratifs\s*$/mi', '', $fPart);
            return [$ePart, trim($fPart)];
        }
        return [$fiche, ''];
    }

    private function stripLeadingH1(string $text): string
    {
        $text = preg_replace('/^\s*#\s+[^\n]+\n*/u', '', $text);
        return trim($text);
    }

    private function cleanChronologie(string $text): string
    {
        $text = preg_replace('/^\s*#\s+Chronologie.*?\n/m', "## Chronologie — La Séraphothèque {#chronologie}\n", $text);
        return trim($text);
    }

    private function addAnchorId(string $text, string $id): string
    {
        // Remove artifact filename headings first
        $text = preg_replace('/^\s*#\s*`?[^`]+?\.md`?\s*\n*/m', '', $text);

        // Add anchor ID to the first remaining heading
        $text = preg_replace('/^(\s*##?\s+[^#\n{]+)/m', "\\1 { #$id }", $text, 1);

        return trim($text);
    }

    private function insertAfterSection(string $body, string $sectionMarker, string $content): string
    {
        if (! str_contains($body, $sectionMarker)) {
            return $body . $content;
        }

        $pos = strpos($body, $sectionMarker);
        $next = strpos($body, "\n## ", $pos + strlen($sectionMarker));

        if ($next === false) {
            return $body . $content;
        }

        return substr($body, 0, $next) . $content . substr($body, $next);
    }
}
