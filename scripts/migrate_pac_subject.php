<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Subject;
use App\Models\SubjectImage;
use Illuminate\Support\Facades\Storage;

$slug = 'pac-roz-chauffage-clim';
$subject = Subject::where('slug', $slug)->first();

if (! $subject) {
    echo "Sujet non trouvé.\n";
    exit(1);
}

$disk = Storage::disk('subject_images');
$baseDir = public_path("images/subjects/{$slug}");
$map = [
    ['file' => '1.jpeg', 'alt' => 'Isolation par l extérieur — parties communes'],
    ['file' => '2.jpeg', 'alt' => 'Observatoire DPE / Audit ADEME'],
    ['file' => '3.jpeg', 'alt' => 'Nouveaux double-seuils des étiquettes énergie'],
    ['file' => '4.jpeg', 'alt' => 'Déperditions thermiques dans le bâtiment'],
    ['file' => '5.jpeg', 'alt' => 'Montants et consommations annuels d energie'],
];

foreach ($map as $index => $meta) {
    $srcPath = "subjects/{$slug}/{$meta['file']}";
    $destPath = "subjects/{$subject->id}/{$meta['file']}";

    $existing = SubjectImage::where('subject_id', $subject->id)->where('filename', $meta['file'])->first();

    if (! $existing) {
        $disk->makeDirectory("subjects/{$subject->id}");

        if ($disk->exists($srcPath)) {
            $disk->move($srcPath, $destPath);
            if (! $disk->exists($destPath)) {
                echo "Échec déplacement {$meta['file']}, tentative copie.\n";
                $disk->put($destPath, $disk->get($srcPath));
            }
        } elseif (file_exists($baseDir . '/' . $meta['file'])) {
            $disk->put($destPath, file_get_contents($baseDir . '/' . $meta['file']));
        } else {
            echo "Fichier manquant : {$meta['file']}\n";
            continue;
        }

        $fullPath = $disk->path($destPath);

        SubjectImage::create([
            'subject_id' => $subject->id,
            'filename' => $meta['file'],
            'path' => $destPath,
            'mime_type' => mime_content_type($fullPath) ?: 'image/jpeg',
            'alt' => $meta['alt'],
            'position' => $index + 1,
        ]);

        echo "Image {$meta['file']} attachée.\n";
        continue;
    }

    // Forcer le déplacement au bon endroit si besoin
    $disk->makeDirectory("subjects/{$subject->id}");
    if ($disk->exists($srcPath) && ! $disk->exists($destPath)) {
        $disk->move($srcPath, $destPath);
        $existing->update(['path' => $destPath]);
        echo "Image {$meta['file']} déplacée vers subjects/{$subject->id}.\n";
    } else {
        echo "Image {$meta['file']} déjà attachée.\n";
    }

    if ($existing->alt !== $meta['alt'] || (int) $existing->position !== ($index + 1)) {
        $existing->update([
            'alt' => $meta['alt'],
            'position' => $index + 1,
        ]);
    }
}

$markdown = file_get_contents(resource_path('markdown-pac.md'));
$markdown = str_replace('{SUBJECT_ID}', $subject->id, $markdown);

$subject->update(['body' => $markdown]);

echo "Sujet #{$subject->id} mis à jour.\n";
