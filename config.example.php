<?php
/**
 * Copiez ce fichier vers config.php et renseignez vos identifiants O2switch.
 */
return [
    // 'mysql' en production O2switch — 'sqlite' uniquement pour tester en local
    'driver' => 'mysql',

    'mysql' => [
        'host' => 'localhost',
        'name' => 'NOM_DE_LA_BASE',
        'user' => 'UTILISATEUR_MYSQL',
        'pass' => 'MOT_DE_PASSE_MYSQL',
        'charset' => 'utf8mb4',
    ],

    'sqlite_path' => __DIR__ . '/data/app.db',

    // Laissez vide : détecté automatiquement. Exemple : https://www.villamariolubeach.com
    'base_url' => '',

    'default_lang' => 'en',

    // Utilisé uniquement à la première installation
    'admin_user' => 'admin',
    'admin_password' => 'VillaMariolu2027',
];
