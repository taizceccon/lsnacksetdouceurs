<?php

use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    // Charge le .env.test pour la CI
    (new Dotenv())->bootEnv(__DIR__ . '/../.env.test');
}

if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
}