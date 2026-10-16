<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Subject;
use App\Models\SubjectDocument;
use Illuminate\Support\Str;

$srcDir = '/home/aur-lien/Classement-Classeur/CIVITAS/01-SUJETS/sivu-eau-assainissement';
$storageDir = 'subjects/sivu-eau-assainissement';
$files = [
    'CR_14.03.2026_Peyreleau.pdf' => [
        'title' => 'Compte-rendu CM Peyreleau — 14/03/2026',
        'description' => 'Compte-rendu complet du conseil municipal de Peyreleau du 14 mars 2026 incluant les délibérations sur la dissolution du SIVU et la reprise de compétence assainissement.',
        'category' => 'source',
        'sha256' => '615ba777c5b6a66c1a8408b7a5433a4490ac7b618dbed666154a595413f05c8f',
    ],
    'DL_Dissolution_SIVU_Peyreleau_Le_Rozier.pdf' => [
        'title' => 'Délibération n°20260314-05 — Dissolution du SIVU',
        'description' => 'Délibération du 14/03/2026 portant dissolution du SIVU PEYRELEAU-LE ROZIER à la fin de l\'exercice 2026, sous réserve de l\'arrêté interpréfectoral.',
        'category' => 'source',
        'sha256' => '03289fdd09dd0fd9f50d3bc5d7eab1484d713be67b257c17f1c126c40c2b221e',
    ],
    'DL_Reprise_Competences_Assainissement.pdf' => [
        'title' => 'Délibération n°20260314-06 — Reprise compétence assainissement',
        'description' => 'Délibération du 14/03/2026 demandant la reprise par les communes de la compétence « assainissement collectif ».',
        'category' => 'source',
        'sha256' => 'fe7ffdb5a10894a6169c85dac2515919c5d75008738bd464d2c8d4adf90e087f',
    ],
    'DL_Creation_Budget_Assainissement.pdf' => [
        'title' => 'Délibération n°20260314-07 — Création budget assainissement',
        'description' => 'Délibération du 14/03/2026 créant le budget annexe assainissement, autonome financièrement et comptablement.',
        'category' => 'source',
        'sha256' => '0f04c5642601d229a6aaaabdb078f62c36d2eed76a1c5771079cc49f3d80fda9',
    ],
];

// Admin user must exist; use the one from UserSeeder or fail explicitly.
$admin = User::where('role', 'admin')->first();
if (! $admin) {
    fwrite(STDERR, "Aucun utilisateur admin trouvé.\n");
    exit(1);
}

$bodyMarkdown = <<<MD
# SIVU Eau / Assainissement — Peyreleau / Le Rozier

**Statut :** dossier CIVITAS intégré le 2026-10-15 suite au signal Patrice DENJEAN.

**Entité concernée :** SIVU ASSAINISSEMENT PEYRELEAU-LE ROZIER (SIREN 254 800 527), créé le 17/06/1987, siège Mairie du Rozier.

## Faits établis

- **Compétence :** assainissement collectif en régie (source Sispea 2024/2025). Aucun service eau potable déclaré au SIVU et aucun RPQS en ligne.
- **CM Peyreleau 14/03/2026** : 3 délibérations adoptées à l'unanimité :
  1. **Dissolution** du SIVU à la fin de l'exercice 2026, sous réserve de l'arrêté interpréfectoral.
  2. **Reprise** par les communes de la compétence assainissement.
  3. **Création** du budget annexe assainissement (autonomie financière, TVA, comptable SGC St-Affrique).
- **Emprunt en cours :** Caisse des Dépôts et Consignations n° 5094389, capital restant dû au 1er janvier 2026 = **122 029,63 €**.
- **Convention future :** entre Peyreleau et Le Rozier pour fixer la gestion, l'entretien et le financement des ouvrages d'assainissement communs après dissolution.

## Points de vigilance

- L'e-mail mentionne un "RPQS eau potable / assainissement collectif adopté par le SIVU". Sispea indique que le SIVU n'a pas de service eau potable déclaré et qu'aucun RPQS n'est en ligne. L'eau potable est probablement gérée par une autre entité.
- La dissolution n'est effective qu'après arrêté interpréfectoral.
- Une convention entre les deux communes reste à négocier pour l'après-SIVU.

## Documents joints

- Compte-rendu CM Peyreleau — 14/03/2026
- Délibération n°20260314-05 — Dissolution du SIVU
- Délibération n°20260314-06 — Reprise compétence assainissement
- Délibération n°20260314-07 — Création budget assainissement
MD;

$citizenMarkdown = <<<MD
# Le SIVU eau / assainissement Peyreleau–Le Rozier va être dissous

Lors du conseil municipal du 14 mars 2026, la commune de Peyreleau a voté à l'unanimité la dissolution du SIVU (syndicat intercommunal à vocation unique) qui gère l'assainissement collectif avec Le Rozier.

## Ce qui change concrètement

- **Fin 2026 :** le SIVU disparaît après un arrêté préfectoral.
- **Compétence reprise :** chaque commune (Peyreleau et Le Rozier) reprendra la gestion de l'assainissement sur son territoire.
- **Budget dédié :** Peyreleau crée un "budget annexe assainissement" autonome, avec sa propre comptabilité.
- ** finances existantes :** l'emprunt CDC n° 5094389 reste dû : 122 029,63 € au 1er janvier 2026.

## Prochaine étape

Les deux communes doivent signer une convention pour fixer qui gère, qui entretient et qui finance les ouvrages d'assainissement communs (stations, canalisations partagées, etc.).
MD;

$slug = 'sivu-peyreleau-le-rozier-eau-assainissement';

if (Subject::where('slug', $slug)->exists()) {
    fwrite(STDERR, "Le sujet slug={$slug} existe déjà.\n");
    exit(2);
}

$subject = Subject::create([
    'user_id' => $admin->id,
    'theme' => 'Environnement & Cadre de vie',
    'category_id' => 9,
    'sub_category_id' => 10,
    'title' => 'SIVU Peyreleau–Le Rozier — eau & assainissement',
    'slug' => $slug,
    'body' => $bodyMarkdown,
    'citizen_body' => $citizenMarkdown,
    'public_body' => null,
    'status' => 'draft',
    'visibility' => 'citoyen',
    'published_at' => null,
]);

$disk = Storage::disk('documents');
foreach ($files as $filename => $meta) {
    $srcPath = $srcDir . '/' . $filename;
    $relPath = $storageDir . '/' . $filename;

    if (! file_exists($srcPath)) {
        fwrite(STDERR, "Fichier source manquant : {$srcPath}\n");
        exit(3);
    }

    $sha256 = hash_file('sha256', $srcPath);
    if ($sha256 !== $meta['sha256']) {
        fwrite(STDERR, "SHA-256 mismatch pour {$filename}\n");
        exit(4);
    }

    $disk->put($relPath, file_get_contents($srcPath));

    SubjectDocument::create([
        'subject_id' => $subject->id,
        'filename' => $filename,
        'stored_filename' => $filename,
        'path' => $relPath,
        'mime_type' => 'application/pdf',
        'size' => filesize($srcPath),
        'title' => $meta['title'],
        'description' => $meta['description'],
        'category' => $meta['category'],
        'position' => 0,
    ]);
}

echo json_encode([
    'subject_id' => $subject->id,
    'slug' => $subject->slug,
    'title' => $subject->title,
    'documents_count' => $subject->documents()->count(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
