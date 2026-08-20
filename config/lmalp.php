<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration admin LMALP
    |--------------------------------------------------------------------------
    |
    | Identifiants du compte administrateur créé par le seeder.
    | Source : variables d'environnement uniquement. Jamais de fallback.
    |
    */

    'admin_email'    => env('LMALP_ADMIN_EMAIL'),
    'admin_password' => env('LMALP_ADMIN_PASSWORD'),
];
