<?php
require '/home/aur-lien/projets/la-main-a-la-pate/vendor/autoload.php';
$app = require_once '/home/aur-lien/projets/la-main-a-la-pate/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$i = App\Models\SubjectImage::where('subject_id',1)->first();
echo urldecode($i->url())."\n";
$s = App\Models\Subject::find(1);
echo substr($s->body, 0, 300)."\n";
