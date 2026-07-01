# La Main à la Pâte

> Plateforme communautaire du village du Rozier (48150)

## À propos

La Main à la Pâte est un espace numérique participatif pour les habitants du Rozier. Il propose :

- **La Séraphothèque** — page d'accueil publique avec les informations pratiques du village
- **Le Hall** — fil d'actualités réservé aux membres
- **Les Sujets** — documents de référence collaboratifs organisés par thème, avec fil de discussion
- **La Communauté** — espaces de discussion thématiques (en préparation, routes existantes)
- **L'Espace administrateur** — panneau de gestion des contenus et routes

## Stack technique

| Composant | Technologie |
|-----------|-------------|
| Backend | PHP 8.3, Laravel 10 |
| Frontend | Tailwind CSS, Vite, Blade |
| Base de donnees | MySQL 8.0 |
| Auth | Laravel Breeze |

## Installation locale

### Prérequis

- PHP 8.3+
- Composer 2.x
- Node.js 20+
- MySQL 8.0+

### Installation

```bash
cd ~/projets/la-main-a-la-pate

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Configurer le `.env` :

```env
DB_DATABASE=la_main_a_la_pate
DB_USERNAME=root
DB_PASSWORD=...
```

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

## Tests

```bash
php artisan test
```

## Comptes de test (seeder)

| Rôle | Email | Mot de passe |
|---|---|---|
| Admin | aurelien.tisserand18@gmail.com | NewProduction18@L |
| Modérateur | moderator@lamainalapate.test | défini dans `UserRoleSeeder.php` |
| Citoyen | citoyen@lamainalapate.test | défini dans `UserRoleSeeder.php` |
| Visiteur | visiteur@lamainalapate.test | défini dans `UserRoleSeeder.php` |

**Rôles :**
- `admin` — Conseil municipal : gestion complète.
- `moderator` — Modérateur wiki : peut créer/éditer/publier les sujets de tout le monde.
- `citoyen` / `member` — Membre : peut créer ses propres sujets et lire le Hall.
- `invite` — Visiteur : accès lecture limité.

## Déploiement production

Script prêt :

```bash
bash deploy-la-main-a-la-pate.sh
```

Hébergement : Hostinger — voir `La-Main-a-la-Pate-Infra` dans Obsidian.

## Structure actuelle

- `routes/web.php` — routes publiques, Hall, Sujets, Communauté, Admin
- `app/Http/Controllers/SubjectController.php` — CRUD sujets/commentaires/versions
- `app/Http/Controllers/AdminController.php` — panneau admin et routes
- `app/Http/Controllers/DashboardController.php` — tableau de bord utilisateur
- `resources/views/subjects/` — vues de l'espace Sujets
- `resources/views/admin/` — vues de l'espace Admin
- `tests/Feature/` — tests fonctionnels (TDD)

## Documentation

- Architecture et déploiement : `[[La-Main-a-la-Pate-Infra]]` dans Obsidian
- Journal et TODO : `~/Obsidian-Vault/DevOps/La-Main-a-la-pate/Journal/`

## Licence

MIT
