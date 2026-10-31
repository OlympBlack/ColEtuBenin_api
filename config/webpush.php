<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Web Push (VAPID)
    |--------------------------------------------------------------------------
    |
    | Clés générées avec Minishlink\WebPush\VAPID::createVapidKeys().
    | La clé publique est exposée au frontend (GET /api/push/public-key),
    | la clé privée doit rester secrète côté serveur.
    |
    */

    'public_key' => env('VAPID_PUBLIC_KEY'),

    'private_key' => env('VAPID_PRIVATE_KEY'),

    'subject' => env('VAPID_SUBJECT', 'mailto:contact@coletubenin.lavoixdabouloussi.org'),

    'ttl' => (int) env('WEBPUSH_TTL', 2419200),

    'urgency' => env('WEBPUSH_URGENCY', 'high'),
];
