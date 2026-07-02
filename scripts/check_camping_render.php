<?php
require '/home/aur-lien/projets/la-main-a-la-pate/vendor/autoload.php';
$app = require_once '/home/aur-lien/projets/la-main-a-la-pate/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();
$s = App\Models\Subject::where('slug','camping-municipal-benevolat-et-masse-salariale')->first();
echo $s ? substr($s->renderBody(), 0, 600) : "not found";
