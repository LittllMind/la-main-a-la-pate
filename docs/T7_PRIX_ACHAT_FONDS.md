# T7 : Afficher et éditer le prix d'achat dans Fonds

## 🎯 Objectif
Permettre l'affichage et la mise à jour du prix d'achat des fonds depuis l'interface admin.

## ✅ Réalisé

### Controller (`FondController`)
- Méthode `update()` modifiée pour accepter `prix_achat` optionnel
- Validation numérique `min:0`
- Sécurité : édition réservée aux admins (`isAdmin()`)

### Vue (`fonds/index.blade.php`)
- Colonne "Prix d'achat" éditable inline (admin seulement)
- Input numérique avec step 0.01
- Employés : affichage en lecture seule
- Style cohérent violet/rose

### Sécurité
- CSRF token sur tous les formulaires
- Validation backend des données
- Restriction admin pour modification prix

## 📝 Changelog

| Fichier | Modification |
|---------|--------------|
| `app/Http/Controllers/FondController.php` | Ajout `prix_achat` dans validation et update |
| `resources/views/fonds/index.blade.php` | Formulaire inline pour éditer prix d'achat (admin) |

## 🧪 Test

```bash
# Connexion en admin
http://127.0.0.1:8000/fonds

# Vérifier :
# - Colonne "Prix d'achat" éditable avec input + bouton
#- Calcul valeur stock automatique
# - Total footer mis à jour
```
