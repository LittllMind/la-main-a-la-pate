<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Analytics LMALP — privacy-first, server-side
    |--------------------------------------------------------------------------
    */

    // Active ou désactive totalement l'enregistrement d'événements.
    'enabled' => env('ANALYTICS_ENABLED', false),

    // Clé secrète utilisée pour le HMAC journalier du visitor_key.
    'hmac_key' => env('ANALYTICS_HMAC_KEY'),

    // Environnements exclus automatiquement (testing, staging, local...).
    'excluded_environments' => array_filter(explode(',', env('ANALYTICS_EXCLUDED_ENVIRONMENTS', 'testing'))),

    // Durée de rétention des événements bruts en jours.
    'retention_days' => (int) env('ANALYTICS_RETENTION_DAYS', 90),

    // Routes ne devant jamais être tracées (patterns).
    'excluded_path_prefixes' => [
        'admin',
        'login',
        'register',
        'logout',
        'first-setup',
        'recherche',
        'assets',
        'build',
        'storage',
        'images',
        'favicon',
        'robots.txt',
        'sitemap',
        'sitemap.xml',
        'up',
    ],

    // Méthodes HTTP autorisées.
    'allowed_methods' => ['GET'],

    // Rapport quotidien Markdown
    'daily_report' => [
        'directory' => env(
            'ANALYTICS_DAILY_REPORT_DIRECTORY',
            storage_path('app/private/analytics/daily')
        ),
    ],
];
