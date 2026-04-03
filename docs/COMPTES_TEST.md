# Comptes de Test RBAC

## 🔐 Utilisateurs de test créés (2026-03-05)

### Admin
- **Email** : admin@example.com
- **Mot de passe** : password
- **Rôle** : admin
- **Accès** : Toutes les routes admin (/vinyles, /stats, /fonds, /ventes)

### Employé
- **Email** : employe@example.com
- **Mot de passe** : password
- **Rôle** : employe
- **Accès** : Routes employé (à définir)

### Client
- **Email** : client@example.com
- **Mot de passe** : password
- **Rôle** : client
- **Accès** : Routes client (panier, commandes, adresses)

## 🧪 Tests à effectuer

### Accès Admin
- [ ] Se connecter avec admin@example.com / password
- [ ] Vérifier l'accès à `/vinyles` (CRUD complet)
- [ ] Vérifier l'accès à `/stats` (statistiques)
- [ ] Vérifier l'accès à `/fonds` (gestion des fonds)
- [ ] Vérifier l'accès à `/ventes` (gestion des ventes)
- [ ] Vérifier que `/kiosque` est accessible

### Accès Employé
- [ ] Se connecter avec employe@example.com / password
- [ ] Vérifier les restrictions d'accès

### Accès Client
- [ ] Se connecter avec client@example.com / password
- [ ] Vérifier l'accès au kiosque
- [ ] Vérifier l'accès au panier
- [ ] Vérifier l'accès aux commandes

## 📝 Notes
- Tous les comptes utilisent le mot de passe `password` pour faciliter les tests
- À utiliser uniquement en environnement de développement local
