# Système de Gestion des Adresses

## 📋 Vue d'ensemble

Le système d'adresses permet aux utilisateurs enregistrés de sauvegarder leurs adresses de livraison et facturation pour commander plus rapidement.

## ✨ Fonctionnalités

### 1. **Gestion complète des adresses**
- ✅ CRUD complet (Créer, Lire, Mettre à jour, Supprimer)
- ✅ Adresses multiples par utilisateur
- ✅ Label personnalisable (Maison, Travail, etc.)
- ✅ Adresse par défaut avec badge visuel

### 2. **Intégration au tunnel de commande**
- ✅ Menu déroulant pour sélectionner une adresse existante
- ✅ Pré-remplissage automatique des champs
- ✅ Option pour sauvegarder une nouvelle adresse pendant la commande
- ✅ Support d'adresses de livraison et facturation différentes

### 3. **Sécurité**
- ✅ Authentification requise
- ✅ Chaque utilisateur ne voit que SES adresses
- ✅ Protection contre la suppression de l'adresse par défaut

## 📁 Structure

### Modèle `Address`
```php
- id
- user_id (foreign key)
- label (Maison, Travail, etc.)
- nom
- email
- telephone
- adresse
- code_postal
- ville
- pays (FR, BE, CH, etc.)
- instructions (optionnel)
- is_default (boolean)
- timestamps
```

### Routes
```
GET    /addresses              → Liste des adresses
GET    /addresses/create       → Formulaire de création
POST   /addresses              → Sauvegarder nouvelle adresse
GET    /addresses/{id}         → Détails d'une adresse
GET    /addresses/{id}/edit    → Formulaire d'édition
PUT    /addresses/{id}         → Mettre à jour une adresse
DELETE /addresses/{id}         → Supprimer une adresse
POST   /addresses/{id}/set-default → Définir comme par défaut
```

### Contrôleur `AddressController`
- `index()` : Liste toutes les adresses de l'utilisateur
- `create()` : Affiche le formulaire de création
- `store()` : Sauvegarde une nouvelle adresse
- `edit()` : Affiche le formulaire d'édition
- `update()` : Met à jour une adresse existante
- `destroy()` : Supprime une adresse (sauf si par défaut)
- `setDefault()` : Définit une adresse comme par défaut

## 🎨 Interface Utilisateur

### Page de gestion (`/addresses`)
- Grille responsive des adresses
- Badge "Par défaut" en violet
- Boutons d'action (Modifier, Supprimer, Définir par défaut)
- Bouton "+ Nouvelle adresse"

### Formulaire de commande (`/orders/create`)
- Menu déroulant des adresses existantes
- Chargement automatique via Alpine.js
- Checkbox "Sauvegarder cette adresse"
- Champ label pour la nouvelle adresse

## 🔧 Utilisation

### Pour les utilisateurs

1. **Ajouter une adresse**
   - Se connecter → Cliquer sur l'icône 📍 dans le menu
   - Cliquer sur "+ Nouvelle adresse"
   - Remplir le formulaire et cocher "Adresse par défaut" si besoin

2. **Utiliser une adresse existante**
   - Pendant la commande, sélectionner l'adresse dans le menu déroulant
   - Les champs se remplissent automatiquement

3. **Modifier une adresse**
   - Cliquer sur l'icône ✏️ sur la carte d'adresse
   - Modifier les informations et enregistrer

### Pour les développeurs

```php
// Récupérer les adresses d'un utilisateur
$addresses = Auth::user()->addresses;

// Adresse par défaut
$default = Auth::user()->defaultAddress;

// Créer une adresse
Address::create([
    'user_id' => Auth::id(),
    'label' => 'Maison',
    'nom' => 'Jean Dupont',
    'email' => 'jean@example.com',
    'telephone' => '06 12 34 56 78',
    'adresse' => '123 Rue de la Paix',
    'code_postal' => '75001',
    'ville' => 'Paris',
    'pays' => 'FR',
    'is_default' => true,
]);

// Définir comme adresse par défaut
$address->setAsDefault();
```

## 🎯 Prochaines améliorations possibles

- [ ] Validation automatique des codes postaux par pays
- [ ] Géocodage des adresses (Google Maps API)
- [ ] Import/export d'adresses (vCard)
- [ ] Adresses favorites (au-delà de la par défaut)
- [ ] Historique des modifications

## 📝 Notes techniques

- **Migration** : `2026_03_05_115625_create_addresses_table.php`
- **Relations** : User hasMany Address, Address belongsTo User
- **Cascade** : Suppression en cascade quand un utilisateur est supprimé
- **Index** : Index sur `user_id` pour performances

---

**Dernière mise à jour** : 2026-03-05
**Statut** : ✅ Opérationnel
