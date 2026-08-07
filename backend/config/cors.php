<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Origine del frontend (SPA Vite). In dev con il proxy Vite le richieste
    // sono same-origin, quindi il CORS non viene quasi mai esercitato; resta
    // configurato per l'accesso diretto all'API dal browser.
    'allowed_origins' => array_filter(explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Autenticazione Bearer token (niente cookie): credentials disabilitati.
    'supports_credentials' => false,
];
