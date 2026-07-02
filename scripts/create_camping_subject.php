<?php
require '/home/aur-lien/projets/la-main-a-la-pate/vendor/autoload.php';
$app = require_once '/home/aur-lien/projets/la-main-a-la-pate/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Str;

$admin = User::firstOrCreate(
    ['email' => 'aurelien.tisserand18@gmail.com'],
    [
        'name' => 'Administrateur',
        'password' => bcrypt('NewProduction18@L'),
        'email_verified_at' => now(),
    ]
);

$title = 'Camping municipal : bénévolat et masse salariale';
$slug = Str::slug($title);
$body = file_get_contents('/home/aur-lien/projets/la-main-a-la-pate/resources/markdown-camping.md');

$subject = Subject::updateOrCreate(
    ['slug' => $slug],
    [
        'user_id' => $admin->id,
        'theme' => 'Gestion communale',
        'title' => $title,
        'body' => $body,
        'status' => 'published',
    ]
);

echo "Sujet local #{$subject->id} — /sujets/{$subject->slug}\n";
