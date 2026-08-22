<?php

namespace Tests\Feature;

use App\Console\Commands\Ingestion\SeraphothequeIngestion;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\SubjectVersion;
use App\Models\User;
use App\Models\VisibilityLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests de l'ingestion du Subject Séraphothèque pilotée par le manifest PUBLIC-V1.
 *
 * Le manifest 99-MANIFEST/public-v1.csv est autoritaire ; aucun catalogue
 * hardcodé ne décide des documents, de leur source_reference ou de leur audience.
 */
class SeraphothequeIngestionTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC_DOC_ID = 'DOC-PUBLIC-01';
    private const CITIZEN_DOC_ID = 'DOC-CITIZEN-01';
    private const NO_ASSET_DOC_ID = 'DOC-NO-ASSET';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
    }

    private function seedEnvironment(array $manifestOverrides = []): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create([
            'id' => 10,
            'name' => 'Vie du village & Actualités',
            'slug' => 'vie-du-village-actualites',
        ]);
        $subCategory = SubCategory::factory()->create([
            'id' => 14,
            'category_id' => 10,
            'name' => 'Séraphothèque',
            'slug' => 'seraphotheque',
        ]);

        // Subjects historiques factices à préserver
        Category::factory()->create();
        SubCategory::factory()->create();
        $existing = Subject::factory()->count(3)->create();

        $pack = storage_path('testing/seraphotheque-pack');
        $this->createMinimalPackFiles($pack, $manifestOverrides);

        return [
            'admin' => $admin,
            'category' => $category,
            'subCategory' => $subCategory,
            'existing' => $existing,
            'pack' => $pack,
        ];
    }

    private function createMinimalPackFiles(string $pack, array $manifestOverrides = []): void
    {
        @mkdir($pack . '/03-DOCUMENTS/PUBLIC', 0755, true);
        @mkdir($pack . '/03-DOCUMENTS/CITIZEN', 0755, true);
        @mkdir($pack . '/99-MANIFEST', 0755, true);

        file_put_contents($pack . '/03-DOCUMENTS/PUBLIC/doc-public.pdf', "%PDF-1.4 fake public\n");
        file_put_contents($pack . '/03-DOCUMENTS/CITIZEN/doc-citizen.pdf', "%PDF-1.4 fake citizen\n");

        $publicSha = hash_file('sha256', $pack . '/03-DOCUMENTS/PUBLIC/doc-public.pdf');
        $citizenSha = hash_file('sha256', $pack . '/03-DOCUMENTS/CITIZEN/doc-citizen.pdf');

        $rows = [
            [
                'public_id' => 'PUB-01',
                'doc_id' => self::PUBLIC_DOC_ID,
                'titre' => 'Document public',
                'date' => '2026-01-01',
                'type' => 'pdf',
                'audience' => 'PUBLIC',
                'source' => 'test',
                'source_reference' => 'seraphotheque-pack:' . self::PUBLIC_DOC_ID,
                'original_sha256' => '',
                'asset_path' => '03-DOCUMENTS/PUBLIC/doc-public.pdf',
                'asset_sha256' => $publicSha,
                'expurgations' => '',
                'fiche' => '',
                'chronology_event' => '',
                'status' => 'gelé',
            ],
            [
                'public_id' => 'CIT-01',
                'doc_id' => self::CITIZEN_DOC_ID,
                'titre' => 'Document citoyen',
                'date' => '2026-01-02',
                'type' => 'pdf',
                'audience' => 'CITIZEN',
                'source' => 'test',
                'source_reference' => 'seraphotheque-pack:' . self::CITIZEN_DOC_ID,
                'original_sha256' => '',
                'asset_path' => '03-DOCUMENTS/CITIZEN/doc-citizen.pdf',
                'asset_sha256' => $citizenSha,
                'expurgations' => '',
                'fiche' => '',
                'chronology_event' => '',
                'status' => 'gelé',
            ],
            [
                'public_id' => 'NO-01',
                'doc_id' => self::NO_ASSET_DOC_ID,
                'titre' => 'Sans asset',
                'date' => '2026-01-03',
                'type' => 'md',
                'audience' => 'PUBLIC',
                'source' => 'test',
                'source_reference' => 'seraphotheque-pack:' . self::NO_ASSET_DOC_ID,
                'original_sha256' => '',
                'asset_path' => '',
                'asset_sha256' => '',
                'expurgations' => '',
                'fiche' => '',
                'chronology_event' => '',
                'status' => 'gelé',
            ],
        ];

        if (! empty($manifestOverrides)) {
            // Écraser/remplacer des lignes par doc_id
            $byDocId = [];
            foreach ($rows as $row) {
                $byDocId[$row['doc_id']] = $row;
            }
            foreach ($manifestOverrides as $override) {
                $byDocId[$override['doc_id']] = array_merge($byDocId[$override['doc_id']] ?? [], $override);
            }
            $rows = array_values($byDocId);
        }

        $headers = array_keys($rows[0]);
        $csv = implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
        }
        file_put_contents($pack . '/99-MANIFEST/public-v1.csv', $csv);

        $readme = <<<'MD'
---
tags: [public-v1, seraphotheque, readme, canon]
date: 2026-08-21
---

# 00 — LIRE D'ABORD — Pack PUBLIC V1

## Doctrine

- Chaque assertion importante est explicitement classée : **Fait documenté**, **Position d'acteur**, ou **Question ouverte**.
- Aucune analyse juridique dans la narration principale.
- Les documents manquants sont mentionnés comme tels — jamais comme preuves d'absence.
MD;

        $index = <<<'MD'
---
tags: [sujet-public, seraphotheque, v1, narration]
date: 2026-08-21
type: public-narrative
dossier: litige-boutique
---

# La Séraphothèque — Comprendre la situation

**Dernière mise à jour : août 2026**

Cette page rassemble les principaux faits, leur chronologie et les documents disponibles concernant le désaccord entre La Séraphothèque et la commune du Rozier.

L'objectif est de permettre à chacun de distinguer ce qui est établi par les documents, les positions exprimées par les différentes parties et les questions qui restent ouvertes.

---

# 1. Comprendre en une minute

Le local communal aujourd'hui occupé par La Séraphothèque fait l'objet de conventions précaires successives depuis 2022.

→ **Voir la chronologie**

→ **Voir les documents**

→ **Voir les questions encore ouvertes**

---

# 2. Ce qui a changé dans la convention 2026

Le projet 2026 conserve :

- une durée de six mois ;
- un loyer de 350 € par mois charges comprises ;
- une destination commerciale du local.

Mais plusieurs dispositions nouvelles apparaissent.

### Domaine public

Un nouvel article 4 bis précise que la convention ne confère aucun droit d'occupation du domaine public.

→ **Comparer la convention 2025 et le projet 2026 article par article**

---

# 3. Pourquoi avons-nous refusé de signer ?

## Position de La Séraphothèque

Notre refus ne portait pas sur le principe de payer un loyer. Nous avons demandé que la situation puisse être expliquée et discutée plutôt que de signer immédiatement le nouveau projet.

---

# 4. Qui pouvait décider ?

Le 27 avril 2026, le Conseil municipal examine un point « Délégation au maire ».

**Il n'est pas établi dans les documents actuellement disponibles que l'acte final de délégation a été signé ou publié.**

→ **Question ouverte** : contenu exact et conditions de la délégation votée le 27 avril.

---

# 5. La sommation du 24 avril 2026

L'acte est **daté du 24 avril 2026**.

La sommation demande notamment : signer la convention, cesser l'occupation du domaine public, retirer les installations, rétablir une largeur de passage de 1,40 m.

→ **Consulter la fiche documentaire et la version expurgée**

---

# 6. Ce que dit la mairie sur l'avenir du local

Le maire a évoqué par écrit la possibilité d'une reprise future du bâtiment.

### Ce qui n'est pas établi à ce jour

Les documents actuellement disponibles ne permettent pas d'identifier :

- une décision formelle de transférer la mairie ;
- une étude de faisabilité ;
- une estimation du coût ;
- une inscription budgétaire spécifique ;
- une délibération actant ce transfert.

Le dossier sera mis à jour si de tels documents sont obtenus.

---

# 7. Nos demandes de documents

Les demandes ne commencent pas le 6 mai.

**Lorsqu'un document n'a pas été communiqué, cela ne signifie pas que nous affirmons qu'il n'existe pas.**

---

# 8. Les solutions que nous avons proposées

### Discussion

Nous avons demandé à plusieurs reprises que la situation puisse être discutée.

### Régularisation des portants : demande d'AOT

Le 16 juin 2026, une demande d'autorisation d'occupation temporaire du domaine public a été adressée à la mairie pour deux portants commerciaux.

---

# 9. Chronologie condensée

→ **Voir la chronologie complète**

| Date | Événement |
|------|-----------|
| Mai 2022 | Première convention pour le local |
| 2026-04-24 | Sommation (72 h) |
| Août 2026 | Aucune convention 2026 signée par les deux parties |
MD;

        $chronologie = <<<'MD'
---
tags: [chronologie, documentaire, public-v1, seraphotheque]
date: 2026-08-21
type: chronologie-documentaire
dossier: litige-boutique
---

# Chronologie documentaire — PUBLIC V1

## 2022 — Installation et premières conventions

| Date | Événement neutre | DOC_ID | Type source | Certitude |
|------|------------------|--------|-------------|-----------|
| 2022-05-01 | Convention été 2022 signée | BAIL-2022 | Bail signé | CERTAIN |

## 2026 — Conflit contractuel

| Date | Événement neutre | DOC_ID | Type source | Certitude |
|------|------------------|--------|-------------|-----------|
| 2026-04-24 | Sommation par commissaire de justice | SERAPH-DOC-0904 | Acte d'huissier | CERTAIN |

## Événements non datés documentairement

| Événement | DOC_ID | Statut |
|-----------|--------|--------|
| Exemplaire signé de la convention privée | MANQ-009 | NON LOCALISÉ |
| Acte final officiel de délégation | MANQ-005 | ACTE FINAL MANQUANT |

---

*Pack : PUBLIC V1 — gelé le 2026-08-21*
MD;

        $fiche = <<<'MD'
---
tags: [fiche-documentaire, trottoir-prive, email, public-v1]
date: 2026-08-21
type: fiche-documentaire
dossier: litige-boutique
---

# Fiche documentaire — Email du 1er juillet 2026 : déplacement des portants

**Titre :** Information relative au déplacement de nos portants
**Date :** 2026-07-01
**Auteur / émetteur :** Aurélien Tisserand / Anna El Agri
**Destinataire :** Mairie du Rozier
**Nature :** email
**Statut :** source originale retrouvée

## Pourquoi publier cette pièce

Elle établit que les exploitants ont informé la mairie par écrit du déplacement de leurs installations commerciales hors du domaine public communal, vers une emprise privée.

## Ce qu'elle établit

- Les portants ont été déplacés vers une emprise présentée comme privée.
- La mairie était informée par écrit de cette démarche.

## Ce qu'elle n'établit pas

- Elle ne prouve pas que la convention a effectivement été signée.
- L'exemplaire signé de la convention n'a pas été localisé.

→ **Version expurgée disponible**

---

*Fiche gelée le 2026-08-21*
MD;

        $questions = <<<'MD'
---
tags: [questions-ouvertes, public-v1, seraphotheque]
date: 2026-08-21
type: questions-ouvertes
dossier: litige-boutique
---

# Questions ouvertes — PUBLIC V1

Questions auxquelles les documents actuellement disponibles ne permettent pas de répondre complètement.

---

## Statut et gestion du local

### Le local relève-t-il du domaine public ou du domaine privé communal ?

Des informations ont été demandées. La réponse documentaire complète n'est pas disponible dans le corpus actuel.

### Existe-t-il un acte de classement ou de déclassement ?

Le maire a exprimé une position à ce sujet, mais la pièce administrative correspondante n'a pas été identifiée.

---

## AOT

### Quelle suite officielle a été donnée à la demande du 16 juin ?

La demande et son dépôt sont documentés. Aucune décision écrite, arrêté ou délibération formalisant son acceptation ou son refus n'a été retrouvée dans le corpus actuellement disponible.

---

*Pack : PUBLIC V1 — gelé le 2026-08-21*
MD;

        $sources = <<<'MD'
---
tags: [sources, public-v1, seraphotheque, reference]
date: 2026-08-21
type: sources-index
dossier: litige-boutique
---

# Sources — PUBLIC V1

## Sources primaires (A)

| Source | Type | Identifiant |
|--------|------|-------------|
| Convocation CM 27 avril 2026 | Document officiel | SERAPH-DOC-0445 |
| Compte rendu CM 27 avril 2026 | Document transmis | CM-27-04-CR |
| Sommation 24 avril 2026 | Acte d'huissier | SERAPH-DOC-0904 |

## Sources secondaires (B)

| Source | Type | Identifiant |
|--------|------|-------------|
| Emails échanges | Correspondance | Archives Himalaya |

---

*Pack : PUBLIC V1 — gelé le 2026-08-21*
MD;

        @mkdir($pack . '/01-SUJET', 0755, true);
        @mkdir($pack . '/02-CHRONOLOGIE', 0755, true);
        @mkdir($pack . '/04-FICHES', 0755, true);
        @mkdir($pack . '/05-QUESTIONS-OUVERTES', 0755, true);
        @mkdir($pack . '/06-SOURCES', 0755, true);

        file_put_contents($pack . '/00-LIRE-DABORD.md', $readme);
        file_put_contents($pack . '/01-SUJET/index.md', $index);
        file_put_contents($pack . '/02-CHRONOLOGIE/chronologie.md', $chronologie);
        file_put_contents($pack . '/04-FICHES/fiche-email-01-07-2026-trottoir-prive.md', $fiche);
        file_put_contents($pack . '/05-QUESTIONS-OUVERTES/questions-ouvertes.md', $questions);
        file_put_contents($pack . '/06-SOURCES/index.md', $sources);

        // Nettoyer les anciens fichiers legacy du layout précédent
        @unlink($pack . '/index.md');
        @unlink($pack . '/fiche-d-sommation-24-avril-2026.md');
        @unlink($pack . '/fiche-e-mail-maire-14-mai-2026.md');
        @unlink($pack . '/fiche-h-demande-aot.md');
    }

    private function manifestPath(string $pack): string
    {
        return $pack . '/99-MANIFEST/public-v1.csv';
    }

    private function writeManifest(string $pack, array $rows): void
    {
        @mkdir($pack . '/99-MANIFEST', 0755, true);
        if (empty($rows)) {
            file_put_contents($this->manifestPath($pack), "public_id,doc_id,titre,date,type,audience,source,source_reference,original_sha256,asset_path,asset_sha256,expurgations,fiche,chronology_event,status\n");
            return;
        }
        $headers = array_keys($rows[0]);
        $csv = implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
        }
        file_put_contents($this->manifestPath($pack), $csv);
    }

    /** @test */
    public function it_creates_seraphotheque_subject_with_manifest_attributes(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        $exitCode = Artisan::call('app:seraphotheque-ingestion', [
            '--dry-run' => true,
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);
        $this->assertEquals(0, $exitCode);

        $exitCode = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);
        $this->assertEquals(0, $exitCode);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();

        $this->assertEquals($admin->id, $subject->user_id);
        $this->assertEquals(10, $subject->category_id);
        $this->assertEquals(14, $subject->sub_category_id);
        $this->assertEquals('La Séraphothèque — Comprendre la situation', $subject->title);
        $this->assertEquals('seraphotheque-situation-2026', $subject->slug);
        $this->assertEquals('draft', $subject->status);
        $this->assertEquals('draft', $subject->citizen_status);
        $this->assertEquals('draft', $subject->public_status);

        $this->assertNotEmpty($subject->body);
        $this->assertNotEmpty($subject->citizen_body);
        $this->assertNotEmpty($subject->public_body);
        $this->assertStringContainsString('## Documents et fiches documentaires {#documents}', $subject->public_body);
        $this->assertStringContainsString('## Chronologie {#chronologie}', $subject->public_body);
        $this->assertStringContainsString('{#questions-ouvertes}', $subject->public_body);
    }

    /** @test */
    public function public_body_uses_canonical_public_v1_mapping(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();

        $this->assertStringContainsString('# La Séraphothèque — Situation du local communal', $subject->public_body);
        $this->assertStringContainsString('Dossier documentaire en cours d’enrichissement — version du 21 août 2026', $subject->public_body);

        $this->assertStringContainsString('## Comprendre en une minute {#comprendre}', $subject->public_body);
        $this->assertStringContainsString('## Les principaux enjeux {#enjeux}', $subject->public_body);
        $this->assertStringContainsString('## Ce qui change en 2026 {#changements-2026}', $subject->public_body);
        $this->assertStringContainsString('## Chronologie {#chronologie}', $subject->public_body);
        $this->assertStringContainsString('## Positions des acteurs {#positions}', $subject->public_body);
        $this->assertStringContainsString('## Principaux points de désaccord {#desaccords}', $subject->public_body);
        $this->assertStringContainsString('## Questions ouvertes {#questions-ouvertes}', $subject->public_body);
        $this->assertStringContainsString('## Documents et fiches documentaires {#documents}', $subject->public_body);
        $this->assertStringContainsString('## Lire les sources {#lire-les-sources}', $subject->public_body);

        $this->assertStringNotContainsString('## Fiche — Sommation du 24 avril 2026', $subject->public_body, 'Ancienne fiche legacy absente.');
        $this->assertStringNotContainsString('archives-LEX', $subject->public_body, 'Anciens chemins legacy absents.');

        // Ordre des anchors : comprendre doit apparaître avant enjeux, enjeux avant changements, etc.
        $order = [
            'comprendre',
            'enjeux',
            'changements-2026',
            'chronologie',
            'positions',
            'desaccords',
            'questions-ouvertes',
            'documents',
            'lire-les-sources',
        ];
        $positions = array_map(fn ($a) => strpos($subject->public_body, "{#{$a}}"), $order);
        for ($i = 0; $i < count($order) - 1; $i++) {
            $this->assertNotFalse($positions[$i], "Anchor manquant : {$order[$i]}");
            $this->assertGreaterThan($positions[$i], $positions[$i + 1], "Anchor {$order[$i + 1]} doit suivre {$order[$i]}");
        }
    }

    /** @test */
    public function chronology_and_questions_and_comparator_are_included(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();

        $this->assertStringContainsString('## Chronologie {#chronologie}', $subject->public_body);
        $this->assertStringContainsString('## Questions ouvertes {#questions-ouvertes}', $subject->public_body);
        $this->assertStringContainsString('Comparer la convention 2025 et le projet 2026', $subject->public_body);
    }

    /** @test */
    public function documentary_doctrine_is_preserved(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();

        $this->assertStringContainsString('FAIT DOCUMENTÉ', $subject->public_body, 'Doctrine : distinction FAIT DOCUMENTÉ présente.');
        $this->assertStringContainsString('POSITION / DÉCLARATION', $subject->public_body, 'Doctrine : distinction POSITION présente.');
        $this->assertStringContainsString('QUESTION OUVERTE', $subject->public_body, 'Doctrine : distinction QUESTION OUVERTE présente.');
        $this->assertStringContainsString('SOURCE', $subject->public_body, 'Doctrine : distinction SOURCE présente.');

        $this->assertStringNotContainsString("ce document n'existe pas", strtolower($subject->public_body), 'Absence documentaire jamais présentée comme existence négative.');
        $this->assertStringContainsString('n\'a pas été communiqué', $subject->public_body);
        $this->assertStringContainsString('n\'a pas été identifié', $subject->public_body);
    }

    /** @test */
    public function citizen_assets_are_not_linked_inside_public_body(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();

        $this->assertStringNotContainsString('03-DOCUMENTS/CITIZEN', $subject->public_body, 'Aucun chemin CITIZEN ne doit apparaître dans public_body.');
        $this->assertStringNotContainsString('doc-citizen', $subject->public_body, 'Aucune référence directe à un asset CITIZEN dans public_body.');
    }

    /** @test */
    public function subject_versions_snapshot_new_public_v1_content(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $firstVersion = SubjectVersion::where('subject_id', $subject->id)->firstOrFail();

        $this->assertStringContainsString('La Séraphothèque — Situation du local communal', $firstVersion->public_body);
        $this->assertStringContainsString('## Comprendre en une minute {#comprendre}', $firstVersion->public_body);
    }

    /** @test */
    public function it_maps_public_documents_to_public_visibility(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();

        $publicDocs = $subject->documents()->where('visibility', VisibilityLevel::Public->value)->get();
        $citizenDocs = $subject->documents()->where('visibility', VisibilityLevel::Citizen->value)->get();
        $workingDocs = $subject->documents()->where('visibility', VisibilityLevel::Working->value)->get();

        $this->assertEquals(1, $publicDocs->count(), 'Une entrée PUBLIC avec asset crée un document Public.');
        $this->assertEquals(1, $citizenDocs->count(), 'Une entrée CITIZEN avec asset crée un document Citoyen.');
        $this->assertEquals(0, $workingDocs->count(), 'Aucun Working ne doit être inventé par PUBLIC-V1.');
    }

    /** @test */
    public function it_maps_citizen_documents_to_citizen_visibility(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();

        $this->assertDatabaseHas('subject_documents', [
            'subject_id' => $subject->id,
            'source_reference' => 'seraphotheque-pack:' . self::CITIZEN_DOC_ID,
            'visibility' => VisibilityLevel::Citizen->value,
        ]);
    }

    /** @test */
    public function it_skips_no_asset_rows_without_creating_subject_documents(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();

        $this->assertDatabaseMissing('subject_documents', [
            'subject_id' => $subject->id,
            'source_reference' => 'seraphotheque-pack:' . self::NO_ASSET_DOC_ID,
        ]);

        $this->assertCount(2, $subject->documents, 'Seuls les deux docs avec asset sont créés.');
    }

    /** @test */
    public function it_preserves_existing_subjects(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        $beforeIds = Subject::pluck('id')->toArray();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $afterIds = Subject::pluck('id')->toArray();

        foreach ($beforeIds as $id) {
            $this->assertContains($id, $afterIds, "Subject historique {$id} manquant.");
        }

        $this->assertEquals(count($beforeIds) + 1, count($afterIds));
    }

    /** @test */
    public function it_does_not_duplicate_documents_on_rerun(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $countAfterFirst = $subject->documents()->count();
        $this->assertEquals(2, $countAfterFirst, 'Les deux assets avec fichier sont ingérés.');

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject->refresh();
        $countAfterSecond = $subject->documents()->count();
        $this->assertEquals(2, $countAfterSecond, 'La relance identique ne doit pas dupliquer les documents.');
    }

    /** @test */
    public function it_fails_without_pack_path(): void
    {
        $this->seedEnvironment();

        $exitCode = Artisan::call('app:seraphotheque-ingestion');

        $this->assertEquals(1, $exitCode);
        $this->assertDatabaseMissing('subjects', ['slug' => 'seraphotheque-situation-2026']);
    }

    /** @test */
    public function force_preserves_manual_documents_and_does_not_touch_other_subjects(): void
    {
        ['admin' => $admin, 'pack' => $pack, 'existing' => $existing] = $this->seedEnvironment();

        // Ingestion initiale
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $otherSubject = $existing[0];

        // Document manuel sur le même Subject
        $manualDoc = SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'manuel-document.pdf',
            'stored_filename' => 'manuel-document.pdf',
            'path' => 'test/manuel-document.pdf',
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 1234,
            'title' => 'Document manuel hors pipeline',
            'visibility' => VisibilityLevel::Working->value,
            'source_reference' => 'manuel/hors-pipeline.pdf',
        ]);

        // Document sur un autre Subject
        $otherDoc = SubjectDocument::create([
            'subject_id' => $otherSubject->id,
            'filename' => 'other-document.pdf',
            'stored_filename' => 'other-document.pdf',
            'path' => 'test/other-document.pdf',
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 5678,
            'title' => 'Document autre sujet',
            'visibility' => VisibilityLevel::Working->value,
            'source_reference' => 'other/subject.pdf',
        ]);

        Storage::disk('documents')->put('test/manuel-document.pdf', 'fake content');
        Storage::disk('documents')->put('test/other-document.pdf', 'fake other');

        $this->assertDatabaseHas('subject_documents', ['id' => $manualDoc->id]);
        $this->assertDatabaseHas('subject_documents', ['id' => $otherDoc->id]);

        // --force
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
            '--force' => true,
        ]);

        $this->assertDatabaseHas('subject_documents', ['id' => $manualDoc->id]);
        $this->assertDatabaseHas('subject_documents', ['id' => $otherDoc->id]);
        $this->assertTrue(Storage::disk('documents')->exists('test/manuel-document.pdf'));
        $this->assertTrue(Storage::disk('documents')->exists('test/other-document.pdf'));

        $subject->refresh();
        $pipelineRefs = $subject->documents->pluck('source_reference')->toArray();
        $this->assertContains('seraphotheque-pack:' . self::PUBLIC_DOC_ID, $pipelineRefs);
        $this->assertContains('seraphotheque-pack:' . self::CITIZEN_DOC_ID, $pipelineRefs);
        $this->assertContains('manuel/hors-pipeline.pdf', $pipelineRefs);
    }

    /** @test */
    public function force_removes_stale_pipeline_documents_outside_manifest(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        // Ingestion initiale avec les 3 lignes (2 assets)
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $this->assertCount(2, $subject->documents);

        // Nouveau manifest sans le citoyen
        $publicSha = hash_file('sha256', $pack . '/03-DOCUMENTS/PUBLIC/doc-public.pdf');
        $rows = [
            [
                'public_id' => 'PUB-01',
                'doc_id' => self::PUBLIC_DOC_ID,
                'titre' => 'Document public',
                'date' => '2026-01-01',
                'type' => 'pdf',
                'audience' => 'PUBLIC',
                'source' => 'test',
                'source_reference' => 'seraphotheque-pack:' . self::PUBLIC_DOC_ID,
                'original_sha256' => '',
                'asset_path' => '03-DOCUMENTS/PUBLIC/doc-public.pdf',
                'asset_sha256' => $publicSha,
                'expurgations' => '',
                'fiche' => '',
                'chronology_event' => '',
                'status' => 'gelé',
            ],
        ];
        $this->writeManifest($pack, $rows);

        // --force
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
            '--force' => true,
        ]);

        $subject->refresh();
        $refs = $subject->documents->pluck('source_reference')->toArray();
        $this->assertContains('seraphotheque-pack:' . self::PUBLIC_DOC_ID, $refs);
        $this->assertNotContains('seraphotheque-pack:' . self::CITIZEN_DOC_ID, $refs);
        $this->assertCount(1, $subject->documents);
    }

    /** @test */
    public function it_does_not_create_extra_files_on_identical_rerun(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $filesAfterFirst = Storage::disk('documents')->allFiles();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $filesAfterSecond = Storage::disk('documents')->allFiles();

        $this->assertEquals(count($filesAfterFirst), count($filesAfterSecond), 'Une relance identique ne doit pas créer de fichiers supplémentaires.');
    }

    /** @test */
    public function dry_run_does_not_create_database_records(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        $exitCode = Artisan::call('app:seraphotheque-ingestion', [
            '--dry-run' => true,
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertDatabaseMissing('subjects', ['slug' => 'seraphotheque-situation-2026']);
        $this->assertDatabaseMissing('subject_documents', ['title' => 'Document public']);
        $this->assertCount(0, Storage::disk('documents')->allFiles(), 'Aucun fichier ne doit être écrit en dry-run.');
    }

    /** @test */
    public function absent_asset_preserved_without_force(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        // Run 1 : tous les assets présents
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $this->assertDatabaseHas('subject_documents', [
            'subject_id' => $subject->id,
            'source_reference' => 'seraphotheque-pack:' . self::CITIZEN_DOC_ID,
        ]);

        // Retirer un asset du pack
        @unlink($pack . '/03-DOCUMENTS/CITIZEN/doc-citizen.pdf');

        // Run 2 sans --force
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        // Le document doit persister
        $this->assertDatabaseHas('subject_documents', [
            'subject_id' => $subject->id,
            'source_reference' => 'seraphotheque-pack:' . self::CITIZEN_DOC_ID,
        ]);
    }

    /** @test */
    public function absent_doc_id_removed_with_force(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        // Run 1 : tous les assets présents
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();

        $this->assertDatabaseHas('subject_documents', [
            'subject_id' => $subject->id,
            'source_reference' => 'seraphotheque-pack:' . self::CITIZEN_DOC_ID,
        ]);

        // Retirer le doc_id du manifest (asset absent du catalogue courant)
        $publicSha = hash_file('sha256', $pack . '/03-DOCUMENTS/PUBLIC/doc-public.pdf');
        $rows = [
            [
                'public_id' => 'PUB-01',
                'doc_id' => self::PUBLIC_DOC_ID,
                'titre' => 'Document public',
                'date' => '2026-01-01',
                'type' => 'pdf',
                'audience' => 'PUBLIC',
                'source' => 'test',
                'source_reference' => 'seraphotheque-pack:' . self::PUBLIC_DOC_ID,
                'original_sha256' => '',
                'asset_path' => '03-DOCUMENTS/PUBLIC/doc-public.pdf',
                'asset_sha256' => $publicSha,
                'expurgations' => '',
                'fiche' => '',
                'chronology_event' => '',
                'status' => 'gelé',
            ],
        ];
        $this->writeManifest($pack, $rows);

        // Run 2 avec --force : le document pipeline citoyen est obsolète
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
            '--force' => true,
        ]);

        // Le document doit être supprimé
        $this->assertDatabaseMissing('subject_documents', [
            'subject_id' => $subject->id,
            'source_reference' => 'seraphotheque-pack:' . self::CITIZEN_DOC_ID,
        ]);
    }

    /** @test */
    public function no_source_reference_contains_local_paths(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $refs = $subject->documents->pluck('source_reference');

        foreach ($refs as $ref) {
            $this->assertStringNotContainsString('/home/', $ref, 'La source_reference ne doit pas contenir de chemin absolu /home/.');
            $this->assertStringNotContainsString('/tmp/', $ref, 'La source_reference ne doit pas contenir de chemin absolu /tmp/.');
            $this->assertStringNotContainsString('storage/framework/testing', $ref, 'La source_reference ne doit pas contenir de chemin de test temporaire.');
        }
    }

    /** @test */
    public function manual_document_with_shared_source_reference_is_not_appropriated(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        // Créer Subject sans ingestion
        $adminUser = User::factory()->create(['role' => 'admin']);
        $subject = Subject::create([
            'slug' => 'seraphotheque-situation-2026',
            'user_id' => $adminUser->id,
            'category_id' => 10,
            'sub_category_id' => 14,
            'theme' => 'test',
            'title' => 'Test',
            'body' => 'test body',
            'citizen_body' => 'test citizen',
            'public_body' => 'test public',
            'status' => 'draft',
            'citizen_status' => 'draft',
            'public_status' => 'draft',
        ]);

        // Document MANUEL avec une source_reference du catalogue pipeline (SANS namespace)
        $manualDoc = SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'manuel.pdf',
            'stored_filename' => 'manuel.pdf',
            'path' => 'test/manuel-preserve.pdf',
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 9999,
            'title' => 'Document manuel collisionnel',
            'visibility' => VisibilityLevel::Working->value,
            'source_reference' => '03-DOCUMENTS/CITIZEN/doc-citizen.pdf',
        ]);

        Storage::disk('documents')->put('test/manuel-preserve.pdf', 'contenu-manuel');

        // Ingestion
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject->refresh();

        // Le document manuel ne doit PAS être approprié (le titre doit rester manuel)
        $this->assertDatabaseHas('subject_documents', [
            'id' => $manualDoc->id,
            'title' => 'Document manuel collisionnel',
            'filename' => 'manuel.pdf',
            'size' => 9999,
        ]);
    }

    /** @test */
    public function sha256_is_stored_on_first_ingestion(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $doc = $subject->documents()->where('source_reference', 'seraphotheque-pack:' . self::PUBLIC_DOC_ID)->first();

        $this->assertNotNull($doc);
        $this->assertNotNull($doc->source_sha256);
        $this->assertEquals(64, strlen($doc->source_sha256), 'SHA-256 doit être 64 caractères hex.');
        $this->assertEquals(hash_file('sha256', $pack . '/03-DOCUMENTS/PUBLIC/doc-public.pdf'), $doc->source_sha256);
    }

    // =========================================================================
    // Tests de validation FAIL CLOSED du manifest
    // =========================================================================

    /** @test */
    public function manifest_absent_fails_without_mutation(): void
    {
        ['admin' => $admin] = $this->seedEnvironment();

        $pack = storage_path('testing/empty-pack');
        @mkdir($pack, 0755, true);

        $exitCode = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertDatabaseMissing('subjects', ['slug' => 'seraphotheque-situation-2026']);
        $this->assertCount(0, Storage::disk('documents')->allFiles());
    }

    /** @test */
    public function duplicate_doc_id_in_manifest_fails(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        $publicSha = hash_file('sha256', $pack . '/03-DOCUMENTS/PUBLIC/doc-public.pdf');
        $rows = [
            [
                'public_id' => 'PUB-01',
                'doc_id' => self::PUBLIC_DOC_ID,
                'titre' => 'Document public',
                'date' => '2026-01-01',
                'type' => 'pdf',
                'audience' => 'PUBLIC',
                'source' => 'test',
                'source_reference' => 'seraphotheque-pack:' . self::PUBLIC_DOC_ID,
                'original_sha256' => '',
                'asset_path' => '03-DOCUMENTS/PUBLIC/doc-public.pdf',
                'asset_sha256' => $publicSha,
                'expurgations' => '',
                'fiche' => '',
                'chronology_event' => '',
                'status' => 'gelé',
            ],
            [
                'public_id' => 'PUB-02',
                'doc_id' => self::PUBLIC_DOC_ID,
                'titre' => 'Doublon',
                'date' => '2026-01-01',
                'type' => 'pdf',
                'audience' => 'PUBLIC',
                'source' => 'test',
                'source_reference' => 'seraphotheque-pack:' . self::PUBLIC_DOC_ID,
                'original_sha256' => '',
                'asset_path' => '03-DOCUMENTS/PUBLIC/doc-public.pdf',
                'asset_sha256' => $publicSha,
                'expurgations' => '',
                'fiche' => '',
                'chronology_event' => '',
                'status' => 'gelé',
            ],
        ];
        $this->writeManifest($pack, $rows);

        $exitCode = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertDatabaseMissing('subjects', ['slug' => 'seraphotheque-situation-2026']);
    }

    /** @test */
    public function invalid_source_reference_fails(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        $publicSha = hash_file('sha256', $pack . '/03-DOCUMENTS/PUBLIC/doc-public.pdf');
        $rows = [
            [
                'public_id' => 'PUB-01',
                'doc_id' => self::PUBLIC_DOC_ID,
                'titre' => 'Document public',
                'date' => '2026-01-01',
                'type' => 'pdf',
                'audience' => 'PUBLIC',
                'source' => 'test',
                'source_reference' => 'seraphotheque-pack:autre-chose',
                'original_sha256' => '',
                'asset_path' => '03-DOCUMENTS/PUBLIC/doc-public.pdf',
                'asset_sha256' => $publicSha,
                'expurgations' => '',
                'fiche' => '',
                'chronology_event' => '',
                'status' => 'gelé',
            ],
        ];
        $this->writeManifest($pack, $rows);

        $exitCode = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $this->assertEquals(1, $exitCode);
    }

    /** @test */
    public function invalid_audience_fails(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        $publicSha = hash_file('sha256', $pack . '/03-DOCUMENTS/PUBLIC/doc-public.pdf');
        $rows = [
            [
                'public_id' => 'PUB-01',
                'doc_id' => self::PUBLIC_DOC_ID,
                'titre' => 'Document public',
                'date' => '2026-01-01',
                'type' => 'pdf',
                'audience' => 'WORKING',
                'source' => 'test',
                'source_reference' => 'seraphotheque-pack:' . self::PUBLIC_DOC_ID,
                'original_sha256' => '',
                'asset_path' => '03-DOCUMENTS/PUBLIC/doc-public.pdf',
                'asset_sha256' => $publicSha,
                'expurgations' => '',
                'fiche' => '',
                'chronology_event' => '',
                'status' => 'gelé',
            ],
        ];
        $this->writeManifest($pack, $rows);

        $exitCode = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertDatabaseMissing('subjects', ['slug' => 'seraphotheque-situation-2026']);
    }

    /** @test */
    public function missing_asset_sha_fails(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        $rows = [
            [
                'public_id' => 'PUB-01',
                'doc_id' => self::PUBLIC_DOC_ID,
                'titre' => 'Document public',
                'date' => '2026-01-01',
                'type' => 'pdf',
                'audience' => 'PUBLIC',
                'source' => 'test',
                'source_reference' => 'seraphotheque-pack:' . self::PUBLIC_DOC_ID,
                'original_sha256' => '',
                'asset_path' => '03-DOCUMENTS/PUBLIC/doc-public.pdf',
                'asset_sha256' => '',
                'expurgations' => '',
                'fiche' => '',
                'chronology_event' => '',
                'status' => 'gelé',
            ],
        ];
        $this->writeManifest($pack, $rows);

        $exitCode = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $this->assertEquals(1, $exitCode);
    }

    /** @test */
    public function unexpected_asset_sha_fails(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        $rows = [
            [
                'public_id' => 'PUB-01',
                'doc_id' => self::PUBLIC_DOC_ID,
                'titre' => 'Document public',
                'date' => '2026-01-01',
                'type' => 'pdf',
                'audience' => 'PUBLIC',
                'source' => 'test',
                'source_reference' => 'seraphotheque-pack:' . self::PUBLIC_DOC_ID,
                'original_sha256' => '',
                'asset_path' => '',
                'asset_sha256' => '0000000000000000000000000000000000000000000000000000000000000000',
                'expurgations' => '',
                'fiche' => '',
                'chronology_event' => '',
                'status' => 'gelé',
            ],
        ];
        $this->writeManifest($pack, $rows);

        $exitCode = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $this->assertEquals(1, $exitCode);
    }

    /** @test */
    public function missing_asset_fails(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        // Supprimer l'asset sans modifier le manifest
        @unlink($pack . '/03-DOCUMENTS/PUBLIC/doc-public.pdf');

        $exitCode = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertDatabaseMissing('subjects', ['slug' => 'seraphotheque-situation-2026']);
    }

    /** @test */
    public function wrong_hash_fails(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment([
            ['doc_id' => self::PUBLIC_DOC_ID, 'asset_sha256' => '0000000000000000000000000000000000000000000000000000000000000000'],
        ]);

        $exitCode = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $this->assertEquals(1, $exitCode);
    }

    /** @test */
    public function absolute_asset_path_fails(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        $rows = [
            [
                'public_id' => 'PUB-01',
                'doc_id' => self::PUBLIC_DOC_ID,
                'titre' => 'Document public',
                'date' => '2026-01-01',
                'type' => 'pdf',
                'audience' => 'PUBLIC',
                'source' => 'test',
                'source_reference' => 'seraphotheque-pack:' . self::PUBLIC_DOC_ID,
                'original_sha256' => '',
                'asset_path' => '/etc/passwd',
                'asset_sha256' => '0000000000000000000000000000000000000000000000000000000000000000',
                'expurgations' => '',
                'fiche' => '',
                'chronology_event' => '',
                'status' => 'gelé',
            ],
        ];
        $this->writeManifest($pack, $rows);

        $exitCode = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $this->assertEquals(1, $exitCode);
    }

    /** @test */
    public function parent_directory_asset_path_fails(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        $rows = [
            [
                'public_id' => 'PUB-01',
                'doc_id' => self::PUBLIC_DOC_ID,
                'titre' => 'Document public',
                'date' => '2026-01-01',
                'type' => 'pdf',
                'audience' => 'PUBLIC',
                'source' => 'test',
                'source_reference' => 'seraphotheque-pack:' . self::PUBLIC_DOC_ID,
                'original_sha256' => '',
                'asset_path' => '../outside.pdf',
                'asset_sha256' => '0000000000000000000000000000000000000000000000000000000000000000',
                'expurgations' => '',
                'fiche' => '',
                'chronology_event' => '',
                'status' => 'gelé',
            ],
        ];
        $this->writeManifest($pack, $rows);

        $exitCode = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $this->assertEquals(1, $exitCode);
    }

    /** @test */
    public function undeclared_physical_asset_is_not_ingested(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        // Fichier physique non listé dans le manifest
        file_put_contents($pack . '/03-DOCUMENTS/PUBLIC/non-listed.pdf', "%PDF-1.4 non listé\n");

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();

        $this->assertDatabaseMissing('subject_documents', [
            'subject_id' => $subject->id,
            'filename' => 'non-listed.pdf',
        ]);
    }

    // =========================================================================
    // Tests SubjectVersion (non-régression)
    // =========================================================================

    /** @test */
    public function first_ingestion_creates_exactly_one_subject_version(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        $this->assertNull(Subject::where('slug', 'seraphotheque-situation-2026')->first());

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $versions = SubjectVersion::where('subject_id', $subject->id)->get();

        $this->assertCount(1, $versions, 'La première ingestion doit créer exactement une SubjectVersion.');
        $this->assertEquals($admin->id, $versions->first()->user_id);
        $this->assertEquals($subject->body, $versions->first()->body);
        $this->assertEquals($subject->citizen_body, $versions->first()->citizen_body);
        $this->assertEquals($subject->public_body, $versions->first()->public_body);
    }

    /** @test */
    public function identical_rerun_does_not_create_new_subject_version(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $this->assertCount(1, SubjectVersion::where('subject_id', $subject->id)->get());

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject->refresh();
        $this->assertCount(1, SubjectVersion::where('subject_id', $subject->id)->get(), 'Une relance identique ne doit pas créer de SubjectVersion.');
    }

    /** @test */
    public function changed_text_content_creates_a_new_subject_version(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $versions = SubjectVersion::where('subject_id', $subject->id)->orderBy('id')->get();
        $this->assertCount(1, $versions);

        $firstSnapshotBody = $versions->first()->body;

        // Modifier le contenu textuel du pack (dans la section 1, utilisée pour public_body)
        $index = file_get_contents($pack . '/01-SUJET/index.md');
        $index = str_replace(
            '# 1. Comprendre en une minute',
            "# 1. Comprendre en une minute\n\nNouveau paragraphe ajouté après la première version.",
            $index
        );
        file_put_contents($pack . '/01-SUJET/index.md', $index);

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject->refresh();
        $versions = SubjectVersion::where('subject_id', $subject->id)->orderBy('id')->get();

        $this->assertCount(2, $versions, 'Un changement de contenu textuel doit créer exactement une nouvelle SubjectVersion.');
        $this->assertStringContainsString('Nouveau paragraphe ajouté', $subject->body);
        $this->assertEquals($firstSnapshotBody, $versions[0]->body, 'Le premier snapshot doit contenir l\'ancien body.');
        $this->assertStringContainsString('Nouveau paragraphe ajouté', $versions[1]->body, 'Le second snapshot doit contenir le nouvel état final.');
        $this->assertEquals($subject->citizen_body, $versions[1]->citizen_body);
        $this->assertEquals($subject->public_body, $versions[1]->public_body);
    }

    /** @test */
    public function document_only_change_does_not_create_subject_version(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $this->assertCount(1, SubjectVersion::where('subject_id', $subject->id)->get());

        $bodyBefore = $subject->body;
        $citizenBodyBefore = $subject->citizen_body;
        $publicBodyBefore = $subject->public_body;

        // Modifier uniquement le contenu binaire d'un asset sans toucher aux fichiers texte
        file_put_contents($pack . '/03-DOCUMENTS/PUBLIC/doc-public.pdf', "%PDF-1.4 V2 fake documentaire seul\n");

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject->refresh();
        $this->assertCount(1, SubjectVersion::where('subject_id', $subject->id)->get(), 'Une modification documentaire seule ne doit pas créer de SubjectVersion.');
        $this->assertEquals($bodyBefore, $subject->body);
        $this->assertEquals($citizenBodyBefore, $subject->citizen_body);
        $this->assertEquals($publicBodyBefore, $subject->public_body);
    }

    /** @test */
    public function dry_run_does_not_create_subject_version(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $this->assertCount(1, SubjectVersion::where('subject_id', $subject->id)->get());

        // Modifier le contenu textuel et binaire
        $index = file_get_contents($pack . '/01-SUJET/index.md');
        file_put_contents($pack . '/01-SUJET/index.md', $index . "\n\nTEXT DRY RUN.");
        file_put_contents($pack . '/03-DOCUMENTS/PUBLIC/doc-public.pdf', "%PDF-1.4 dry run changed\n");

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
            '--dry-run' => true,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $subject->refresh();
        $this->assertCount(1, SubjectVersion::where('subject_id', $subject->id)->get(), 'Dry-run ne doit créer aucune SubjectVersion.');
        $this->assertStringNotContainsString('TEXT DRY RUN', $subject->body, 'Dry-run ne doit pas modifier les représentations.');
    }

    /** @test */
    public function identical_rerun_preserves_path_and_checksum(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        // Run 1
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $docAfterFirst = $subject->documents()->where('source_reference', 'seraphotheque-pack:' . self::PUBLIC_DOC_ID)->first();
        $pathAfterFirst = $docAfterFirst->path;
        $shaAfterFirst = $docAfterFirst->source_sha256;

        // Run 2 identique
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject->refresh();
        $docAfterSecond = $subject->documents()->where('source_reference', 'seraphotheque-pack:' . self::PUBLIC_DOC_ID)->first();

        $this->assertEquals($pathAfterFirst, $docAfterSecond->path, 'Le chemin stocké doit être identique sur rerun.');
        $this->assertEquals($shaAfterFirst, $docAfterSecond->source_sha256, 'Le SHA-256 doit être identique sur rerun.');
    }

    /** @test */
    public function changed_source_content_updates_path_and_checksum(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        // Run 1
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $docAfterFirst = $subject->documents()->where('source_reference', 'seraphotheque-pack:' . self::PUBLIC_DOC_ID)->first();
        $pathAfterFirst = $docAfterFirst->path;
        $shaAfterFirst = $docAfterFirst->source_sha256;
        $docId = $docAfterFirst->id;
        $fileCountAfterFirst = count(Storage::disk('documents')->allFiles());

        // Modifier le contenu source dans le pack
        file_put_contents($pack . '/03-DOCUMENTS/PUBLIC/doc-public.pdf', "%PDF-1.4 V2 fake\n");

        // Mettre à jour le manifest avec le nouveau hash du fichier modifié
        $publicSha = hash_file('sha256', $pack . '/03-DOCUMENTS/PUBLIC/doc-public.pdf');
        $citizenSha = hash_file('sha256', $pack . '/03-DOCUMENTS/CITIZEN/doc-citizen.pdf');
        $rows = [
            [
                'public_id' => 'PUB-01',
                'doc_id' => self::PUBLIC_DOC_ID,
                'titre' => 'Document public',
                'date' => '2026-01-01',
                'type' => 'pdf',
                'audience' => 'PUBLIC',
                'source' => 'test',
                'source_reference' => 'seraphotheque-pack:' . self::PUBLIC_DOC_ID,
                'original_sha256' => '',
                'asset_path' => '03-DOCUMENTS/PUBLIC/doc-public.pdf',
                'asset_sha256' => $publicSha,
                'expurgations' => '',
                'fiche' => '',
                'chronology_event' => '',
                'status' => 'gelé',
            ],
            [
                'public_id' => 'CIT-01',
                'doc_id' => self::CITIZEN_DOC_ID,
                'titre' => 'Document citoyen',
                'date' => '2026-01-02',
                'type' => 'pdf',
                'audience' => 'CITIZEN',
                'source' => 'test',
                'source_reference' => 'seraphotheque-pack:' . self::CITIZEN_DOC_ID,
                'original_sha256' => '',
                'asset_path' => '03-DOCUMENTS/CITIZEN/doc-citizen.pdf',
                'asset_sha256' => $citizenSha,
                'expurgations' => '',
                'fiche' => '',
                'chronology_event' => '',
                'status' => 'gelé',
            ],
            [
                'public_id' => 'NO-01',
                'doc_id' => self::NO_ASSET_DOC_ID,
                'titre' => 'Sans asset',
                'date' => '2026-01-03',
                'type' => 'md',
                'audience' => 'PUBLIC',
                'source' => 'test',
                'source_reference' => 'seraphotheque-pack:' . self::NO_ASSET_DOC_ID,
                'original_sha256' => '',
                'asset_path' => '',
                'asset_sha256' => '',
                'expurgations' => '',
                'fiche' => '',
                'chronology_event' => '',
                'status' => 'gelé',
            ],
        ];
        $this->writeManifest($pack, $rows);

        // Run 2 avec nouveau contenu
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $docAfterSecond = SubjectDocument::find($docId);
        $pathAfterSecond = $docAfterSecond->path;
        $shaAfterSecond = $docAfterSecond->source_sha256;
        $fileCountAfterSecond = count(Storage::disk('documents')->allFiles());

        // Assertions
        $this->assertEquals($docId, $docAfterSecond->id, 'Le SubjectDocument id doit être préservé.');
        $this->assertNotEquals($shaAfterFirst, $shaAfterSecond, 'Le SHA-256 doit changer après modification du contenu source.');
        $this->assertNotEquals($pathAfterFirst, $pathAfterSecond, 'Le chemin doit être différent après modification.');
        $this->assertFalse(Storage::disk('documents')->exists($pathAfterFirst), 'L\'ancien fichier stocké doit être supprimé.');
        $this->assertTrue(Storage::disk('documents')->exists($pathAfterSecond), 'Le nouveau fichier stocké doit exister.');
        $this->assertEquals($fileCountAfterFirst, $fileCountAfterSecond, 'Le nombre total de fichiers doit rester stable.');
    }

    /** @test */
    public function metadata_change_content_unchanged_does_not_replace_file(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        // Run 1
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $docAfterFirst = $subject->documents()->where('source_reference', 'seraphotheque-pack:' . self::PUBLIC_DOC_ID)->first();
        $pathAfterFirst = $docAfterFirst->path;
        $shaAfterFirst = $docAfterFirst->source_sha256;
        $fileCountAfterFirst = count(Storage::disk('documents')->allFiles());

        // Réécrire le même contenu
        file_put_contents($pack . '/03-DOCUMENTS/PUBLIC/doc-public.pdf', "%PDF-1.4 fake public\n");

        // Run 2
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject->refresh();
        $docAfterSecond = $subject->documents()->where('source_reference', 'seraphotheque-pack:' . self::PUBLIC_DOC_ID)->first();
        $fileCountAfterSecond = count(Storage::disk('documents')->allFiles());

        $this->assertEquals($pathAfterFirst, $docAfterSecond->path, 'Le chemin doit être identique sur contenu inchangé.');
        $this->assertEquals($shaAfterFirst, $docAfterSecond->source_sha256, 'Le SHA-256 doit être identique sur contenu inchangé.');
        $this->assertEquals($fileCountAfterFirst, $fileCountAfterSecond, 'Aucun fichier ne doit être créé sur contenu inchangé.');
    }

    /** @test */
    public function dry_run_does_not_replace_changed_source_content(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        // Run 1 ingestion réelle
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $docAfterFirst = $subject->documents()->where('source_reference', 'seraphotheque-pack:' . self::PUBLIC_DOC_ID)->first();
        $pathAfterFirst = $docAfterFirst->path;
        $shaAfterFirst = $docAfterFirst->source_sha256;
        $fileCountAfterFirst = count(Storage::disk('documents')->allFiles());

        // Modifier le contenu source
        file_put_contents($pack . '/03-DOCUMENTS/PUBLIC/doc-public.pdf', "%PDF-1.4 V2 fake\n");

        // Run 2 en dry-run
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
            '--dry-run' => true,
        ]);

        $subject->refresh();
        $docAfterDryRun = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail()
            ->documents()
            ->where('source_reference', 'seraphotheque-pack:' . self::PUBLIC_DOC_ID)
            ->first();
        $fileCountAfterDryRun = count(Storage::disk('documents')->allFiles());

        $this->assertEquals($shaAfterFirst, $docAfterDryRun->source_sha256, 'Dry-run ne doit pas modifier le SHA-256.');
        $this->assertEquals($pathAfterFirst, $docAfterDryRun->path, 'Dry-run ne doit pas modifier le path.');
        $this->assertEquals($fileCountAfterFirst, $fileCountAfterDryRun, 'Dry-run ne doit pas créer de nouveaux fichiers.');
    }
}
