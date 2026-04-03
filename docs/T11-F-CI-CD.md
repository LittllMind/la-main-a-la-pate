## Tests T11 - Architecture des Tests

### Phase T11

| Sous-tâche | Statut | Description |
|------------|--------|-------------|
| **T11-A** | ✅ Committé | Configuration PHPUnit + factories |
| **T11-B** | ✅ Committé | Feature tests FondController (21 tests) |
| **T11-C** | ✅ Committé | Feature tests VinyleController (37 tests) |
| **T11-D** | ✅ Committé | Feature tests Mouvements Stock (36 tests) |
| **T11-E** | ✅ Committé | Integration tests Commandes (34 tests) |
| **T11-F** | ✅ Committé | GitHub Actions CI/CD |

### Structure des Tests

```
tests/
├── Feature/
│   ├── Fonds/
│   │   ├── FondControllerIndexTest.php
│   │   ├── FondControllerActionsTest.php
│   │   └── FondControllerPermissionsTest.php
│   ├── Vinyles/
│   │   ├── VinyleControllerIndexTest.php
│   │   ├── VinyleControllerActionsTest.php
│   │   └── VinyleControllerShowTest.php
│   ├── Mouvements/
│   │   ├── StockMovementControllerIndexTest.php
│   │   ├── StockMovementControllerExportTest.php
│   │   └── StockMovementServiceTest.php
│   └── Orders/
│       ├── OrderControllerIntegrationTest.php
│       └── TestOrderStockMovementCommandTest.php
└── Unit/
    └── InfrastructureTest.php
```

### CI/CD Pipeline

- **CI** : Tests automatiques sur push/PR
  - PHP 8.2
  - Tests avec MySQL
  - Code coverage
  - PHP_CodeSniffer + PHPStan

- **Deploy** : Déploiement automatique sur push vers main
  - Build des assets
  - Déploiement SSH
  - Cache optimization
  - Migrations automatiques

### Commandes Utiles

```bash
# Lancer tous les tests
php artisan test

# Lancer un groupe spécifique
php artisan test --filter=FondController

# Avec coverage
php artisan test --coverage

# En mode verbose (pour debug)
php artisan test --filter=FondController --verbose
```

### Variables GitHub Secrets Requises

| Secret | Description |
|--------|-------------|
| `DEPLOY_SSH_KEY` | Clé SSH pour déploiement |
| `DEPLOY_HOST` | Serveur de production |
| `DEPLOY_USER` | Utilisateur SSH |
| `DEPLOY_PATH` | Chemin du projet sur le serveur |
| `SLACK_WEBHOOK` | URL webhook Slack pour notifications |

