<?php

use Symfony\Component\Dotenv\Dotenv;

// Le vendor est à la racine du projet, donc on remonte d'un niveau depuis app/
require dirname(__DIR__) . '/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    // Charger le .env.test pour CI
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env.test');
}

if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
}