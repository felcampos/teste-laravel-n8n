<?php

return [
    /*
    |--------------------------------------------------------------------------
    | n8n Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para integração com n8n
    |
    */

    // URL base do n8n
    'base_url' => env('N8N_BASE_URL', 'https://webhook.felipecampos.dev'),

    // Webhooks por ambiente
    'webhooks' => [
        'teste' => env('N8N_WEBHOOK_TESTE', '/webhook-test/n8n'),
        'producao' => env('N8N_WEBHOOK_PRODUCAO', '/webhook/n8n'),
    ],

    // Timeout padrão para requisições
    'timeout' => env('N8N_TIMEOUT', 30),

    // Configurações de retry
    'retry' => [
        'attempts' => env('N8N_RETRY_ATTEMPTS', 3),
        'delay' => env('N8N_RETRY_DELAY', 1000), // em millisegundos
    ],

    // Headers padrão
    'headers' => [
        'Content-Type' => 'application/json',
        'User-Agent' => 'Laravel-App/' . app()->version(),
    ],
];