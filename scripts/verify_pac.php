<?php

use App\Models\Subject;

$s = Subject::where('slug', 'pac-roz-chauffage-clim')->first();
echo 'Titre: ' . $s->title . "\n";
echo 'Theme: ' . $s->theme . "\n";
echo 'Status: ' . $s->status . "\n";
echo 'Images OK: ' . (str_contains($s->body, '/images/subjects/pac-roz-chauffage-clim/1.jpeg') ? 'oui' : 'non') . "\n";
echo substr(strip_tags($s->body), 0, 200) . "\n";
