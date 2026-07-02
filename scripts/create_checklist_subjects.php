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

$subjects = json_decode(file_get_contents('/home/aur-lien/projets/la-main-a-la-pate/resources/subjects-checklist.json'), true);

foreach ($subjects as $item) {
    $title = $item['title'];
    $slug = Str::slug($title);
    $subject = Subject::updateOrCreate(
        ['slug' => $slug],
        [
            'user_id' => $admin->id,
            'theme' => $item['theme'],
            'title' => $title,
            'body' => $item['body'],
            'status' => 'draft',
        ]
    );
    echo "Local #{$subject->id} {$subject->slug} ({$subject->status})\n";
}
