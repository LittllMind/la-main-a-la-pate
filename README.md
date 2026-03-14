# Vinyl Stock 🎸

> Plateforme de vente et gestion de vinyles hydrodécoupés

[![Tests](https://github.com/aurelien-c/vinyl-stock/actions/workflows/ci.yml/badge.svg)](https://github.com/aurelien-c/vinyl-stock/actions/workflows/ci.yml)
[![Deploy](https://github.com/aurelien-c/vinyl-stock/actions/workflows/deploy.yml/badge.svg)](https://github.com/aurelien-c/vinyl-stock/actions/workflows/deploy.yml)
![Tests](https://img.shields.io/badge/tests-passing-brightgreen)
[![PHP](https://img.shields.io/badge/PHP-8.3+-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20)](https://laravel.com)
[![Stripe](https://img.shields.io/badge/Stripe-Ready-635BFF)](https://stripe.com)

## 🎯 À propos

Vinyl Stock est une plateforme e-commerce complète pour la gestion et la vente de vinyles hydrodécoupés. Elle intègre un **mode marché** (caisse/emport pour événements), un **kiosque public** de consultation, et un **dashboard administrateur** avec rapports et statistiques.

**Localisation** : 48150, Le Rozier, France

## 🚀 Fonctionnalités

### ✅ Terminé

- [x] **Kiosque de consultation** — Grille publique avec filtrage
- [x] **Tunnel de vente** — Panier, adresses, commandes
- [x] **Paiement Stripe** — Checkout, webhooks, confirmations
- [x] **RBAC** — Rôles (Admin/Employé/Client) sécurisés
- [x] **Mode Marché** — Caisse emport avec annulation/restock
- [x] **Rapports** — Exports PDF/CSV, stats mensuelles

### 📋 En cours / À venir

- [ ] Tests T14 — Mode Marché (validation)
- [ ] Tests T15 — Performance
- [ ] Déploiement production

## 🛠️ Stack Technique

| Composant | Technologie |
|-----------|-------------|
| Backend | PHP 8.3, Laravel 11 |
| Frontend | Vue.js 3, Inertia.js, Tailwind CSS, Vite |
| Base de données | MySQL 8.0 (production), SQLite (tests) |
| Cache | Redis (optionnel) |
| Paiement | Stripe |
| Auth | Laravel Breeze + RBAC custom |
| Médias | Spatie Media Library |

## 📚 Documentation

| Document | Description |
|----------|-------------|
| [📐 Architecture](docs/ARCHITECTURE.md) | Structure technique, flux de données, sécurité RBAC |
| [🚀 Déploiement](docs/DEPLOYMENT.md) | Guide complet déploiement Nginx + SSL |
| [📡 API](docs/API.md) | Endpoints JSON, paramètres, codes d'erreur |
| [✅ Post-Déploiement](docs/POST-DEPLOYMENT.md) | Checklist validation après mise en prod |
| [🔧 Troubleshooting](docs/TROUBLESHOOTING.md) | Solutions erreurs courantes |
| [💳 Stripe](docs/STRIPE_INSTALL.md) | Configuration paiements |
| [🗺️ Adresses](docs/ADRESSES.md) | Système d'adresses clients |

## 🧪 Tests

### Exécution rapide

```bash
# Tous les tests
php artisan test

# Par catégorie
php artisan test tests/Feature/Security/
php artisan test tests/Feature/ModeMarche/
php artisan test tests/Feature/Performance/

# Avec couverture
php artisan test --coverage
```

### Phases de sécurité (T13)

```bash
php artisan test tests/Feature/Security/SecurityTest.php
# ~20 tests — Validation RBAC, IDOR, headers sécurité
```

### Mode Marché (T14)

```bash
php artisan test tests/Feature/ModeMarche/ModeMarcheTest.php
# ~15 tests — Historique, annulation, export CSV
```

## 📋 Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|-------------|
| Admin | admin@example.com | password |
| Employé | employe@example.com | password |
| Client | client@example.com | password |

> **⚠️ Production** : Changer les mots de passe !

## 🚀 Installation locale

### Prérequis

- PHP 8.3+
- Composer 2.x
- Node.js 20+
- MySQL 8.0+ (ou SQLite pour tests)

### Installation

```bash
# 1. Cloner
git clone <repository-url> vinyl-stock
cd vinyl-stock

# 2. PHP
composer install

# 3. Node
npm ci

# 4. Configuration
cp .env.example .env
php artisan key:generate

# 5. Éditer .env
# DB_DATABASE=vinyl_stock
# DB_USERNAME=root
# DB_PASSWORD=secret
# STRIPE_KEY=sk_test_...

# 6. Base de données
php artisan migrate --seed

# 7. Assets
npm run build

# 8. Lancer
php artisan serve
```

### Health Check

```bash
./scripts/health-check.sh --verbose
```

## 📦 Structure du projet

```
vinyl-stock/
├── app/
│   ├── Http/Controllers/    # API + Web
│   │   ├── ModeMarcheController.php    # Caisse/emport
│   │   └── KiosqueController.php       # Public
│   ├── Models/
│   ├── Services/            # Logique métier
│   └── Policies/            # Autorisations RBAC
├── database/
├── resources/js/            # Vue + Inertia
├── routes/web.php           # Routes
├── tests/Feature/           # Tests intégration
└── docs/                    # Documentation
```

## 🖥️ Utilisation

### Kiosque (Client)
```
http://localhost:8000/kiosque
```
Parcourir le catalogue, ajouter au panier, commander.

### Admin
```
http://localhost:8000/admin
```
Gestion vinyles, utilisateurs, rapports, stats.

### Mode Marché (Employé)
```
http://localhost:8000/admin/marche
```
Caisse pour événements (salons, marchés).

## 📊 Métriques

| Objectif | Valeur |
|----------|--------|
| Chargement pages | < 2s |
| Conversion | > 3% |
| Uptime | 99.9% |
| Tests passants | 100% |

## 🤝 Contribuer

1. Fork
2. Branche: `git checkout -b feature/Nom`
3. Test: `php artisan test` (100% vert)
4. Commit: `git commit -m "feat: description"`
5. PR avec description

## 📄 Licence

MIT License — voir [LICENSE](LICENSE)

## 📞 Contact

- **Projet** : Vinyl Stock
- **Localisation** : 48150, Le Rozier

---

**[⬆️ Documentation](#documentation)** | **[🚀 Déployer](docs/DEPLOYMENT.md)** | **[🔧 Dépanner](docs/TROUBLESHOOTING.md)**

*Dernière mise à jour: mars 2026*
