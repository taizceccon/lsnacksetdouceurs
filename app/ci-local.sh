#!/bin/bash
set -e

# Copier le .env.test
cp .env.test .env

# Installer dépendances
composer install --prefer-dist --no-interaction

# Drop & create DB
php bin/console doctrine:database:drop --force --env=test || true
php bin/console doctrine:database:create --env=test

# Créer le schéma
php bin/console doctrine:schema:create --env=test

# Lancer les tests
APP_ENV=test php bin/phpunit --testdox