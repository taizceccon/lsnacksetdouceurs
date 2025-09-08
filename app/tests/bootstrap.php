<?php

use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php'; // chemin relatif à app/tests/

// Charger le .env.test pour GitHub CI
$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__).'/.env.test');

if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
}