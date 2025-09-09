# LSnacks et Douceurs
Une application Symfony pour gérer les snacks et douceurs.

## Prérequis

- Docker & Docker Compose
- PHP 8.3
- Composer
- MySQL 8.0 / MongoDB 6.0 (via Docker)

---

## Installation avec Docker

1. **Cloner le projet**
```bash
git clone https://github.com/taizceccon/lsnacksetdouceurs.git
cd lsnacksetdouceurs
Construire et lancer les conteneurs

docker-compose up -d
Installer les dépendances PHP


docker exec -it <nom_du_container_php> bash
composer install
Créer et préparer la base de données


php bin/console doctrine:database:create --env=dev
php bin/console doctrine:schema:update --force --env=dev
Exécution des tests
Pour lancer PHPUnit dans l’environnement de test :


APP_ENV=test php bin/phpunit --testdox
Note : La base de données de test sera configurée automatiquement si tu utilises le workflow CI.

Docker
Le projet utilise Docker pour les services :

MySQL sur le port 3306

MongoDB sur le port 27017

PHP 8.3 avec les extensions nécessaires

Pour rebuild les conteneurs :


docker-compose build --no-cache
docker-compose up -d
CI/CD
Le workflow GitHub Actions est configuré pour :

Installer PHP & Composer

Configurer MySQL et MongoDB pour les tests

Exécuter PHPUnit sur la branche main ou dev