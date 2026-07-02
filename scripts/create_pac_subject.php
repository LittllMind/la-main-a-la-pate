<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Str;

$title = 'PAC : Roz Chauffage-clim';
$theme = 'Infrastructures';
$slugBase = Str::slug($title);
$slug = $slugBase;
$counter = 1;
while (Subject::where('slug', $slug)->exists()) {
    $slug = $slugBase . '-' . $counter++;
}

$admin = User::where('role', 'admin')->first();
if (! $admin) {
    echo "No admin found.\n";
    exit(1);
}

$body = file_get_contents('/tmp/pac_body.html');

$subject = Subject::create([
    'user_id' => $admin->id,
    'theme' => $theme,
    'title' => $title,
    'slug' => $slug,
    'body' => $body,
    'status' => 'published',
    'locked_at' => null,
]);

echo "Created subject id={$subject->id} slug={$subject->slug}\n";
echo "URL: /sujets/{$subject->slug}\n";
