<?php

return [
    /*
     * Content Security Policy
     */
    'csp' => [
        'enable' => true,
        'report-only' => false,
        'report-to' => '',
        'report-uri' => '',
    ],

    /*
     * X-Frame-Options
     */
    'x-frame-options' => 'DENY',

    /*
     * X-Content-Type-Options
     */
    'x-content-type-options' => 'nosniff',

    /*
     * X-XSS-Protection
     */
    'x-xss-protection' => '1; mode=block',

    /*
     * Strict-Transport-Security
     */
    'hsts' => [
        'enable' => false,
        'max-age' => 31536000,
        'include-sub-domains' => true,
        'preload' => false,
    ],

    /*
     * Referrer-Policy
     */
    'referrer-policy' => 'strict-origin-when-cross-origin',

    /*
     * Permissions-Policy
     */
    'permissions-policy' => [
        'enable' => true,
    ],
];
