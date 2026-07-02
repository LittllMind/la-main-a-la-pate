<?php
require '/home/aur-lien/projets/la-main-a-la-pate/vendor/autoload.php';
$app = require_once '/home/aur-lien/projets/la-main-a-la-pate/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();
$subj = App\Models\Subject::firstOrFail();
$body = file_get_contents('/home/aur-lien/projets/la-main-a-la-pate/resources/markdown-pac.md');
$body = str_replace('{SUBJECT_ID}', $subj->id, $body);
$subj->forceFill(['body' => $body])->save();
echo "Sujet #{$subj->id} mis à jour.\n";
