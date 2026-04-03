# 🧪 Tester Stripe - Projet Vinyls

## ✅ Configuration actuelle (2026-03-05 16:45)

### Éléments en place :
- [x] Package `stripe/stripe-php` installé
- [x] Clés API configurées dans `.env`
- [x] Migration `payments` exécutée
- [x] Modèle `Payment` créé
- [x] Contrôleur `PaymentController` créé
- [x] Routes de paiement configurées
- [x] Vues `success.blade.php` et `cancel.blade.php` créées
- [x] Script `scripts/stripe-webhook.sh` créé

---

## 🚀 Comment tester le paiement

### 1️⃣ Lancer le webhook en local

```bash
cd /home/aur-lien/.picoclaw/workspace/vinyles-stock
./scripts/stripe-webhook.sh
```

Ou manuellement :
```bash
stripe login
stripe listen --forward-to http://localhost:8000/stripe/webhook
```

**Important** : Copiez le secret webhook affiché (ex: `whsec_xxxxx`) et ajoutez-le dans `.env` :
```env
STRIPE_WEBHOOK_SECRET=whsec_xxxxx
```

Puis redémarrez le serveur Laravel :
```bash
php artisan serve
```

---

### 2️⃣ Créer une commande de test

1. Connectez-vous avec `client@example.com` / `password`
2. Allez sur `/kiosque`
3. Ajoutez un vinyle au panier
4. Allez sur `/panier`
5. Cliquez sur "Commander"
6. Remplissez le formulaire de livraison
7. Sur la page de récapitulatif, cliquez sur "Payer avec Stripe"

---

### 3️⃣ Tester le paiement

Stripe Checkout s'ouvrira avec :
- **Carte de test succès** : `4242 4242 4242 4242`
- **CVC** : `123`
- **Date** : N'importe quelle date future
- **Code postal** : `12345`

Après paiement :
- ✅ Redirection vers `/payment/success`
- ✅ La commande passe en statut `paid`
- ✅ Le paiement est enregistré en BDD
- ✅ Le panier est vidé

---

### 4️⃣ Tester le webhook

Dans un autre terminal, lancez :
```bash
stripe trigger checkout.session.completed
```

Vous devriez voir :
- ✅ L'événement reçu par votre endpoint `/stripe/webhook`
- ✅ La commande mise à jour automatiquement
- ✅ Le panier vidé

---

## 🔍 Vérifier que tout fonctionne

### En base de données :
```sql
-- Voir les paiements
SELECT * FROM payments ORDER BY created_at DESC LIMIT 5;

-- Voir les commandes payées
SELECT * FROM orders WHERE status = 'paid' ORDER BY created_at DESC LIMIT 5;
```

### Dans les logs Laravel :
```bash
tail -f storage/logs/laravel.log | grep -i stripe
```

---

## 🐛 Dépannage

### Erreur "Invalid API key"
→ Vérifiez que `STRIPE_SECRET` dans `.env` commence par `sk_test_`

### Erreur "Invalid signature"
→ Le secret webhook dans `.env` ne correspond pas à celui de Stripe CLI
→ Relancez `stripe listen` et copiez le nouveau secret

### La commande ne passe pas en "paid"
→ Vérifiez que le webhook est bien lancé
→ Regardez les logs : `storage/logs/laravel.log`

### Erreur dans PaymentController
→ Vérifiez que `CartService` est bien injecté dans le contrôleur
→ Exécutez : `php artisan config:clear && php artisan route:clear`

---

## 📊 Progression Stripe

- [x] 3.1 Documentation d'installation créée
- [x] 3.2 Package Stripe installé
- [x] 3.3 Migration `payments` corrigée
- [x] **3.4 Clés API configurées** ✅
- [x] **3.5 Contrôleur `PaymentController` créé** ✅
- [ ] 3.6 Créer le checkout session Stripe (déjà fait dans le contrôleur)
- [ ] 3.7 Gérer les webhooks Stripe (déjà fait dans le contrôleur)
- [x] **3.8 Vues de confirmation créées** ✅
- [ ] 3.9 Tester le paiement en mode test

**Prochaine étape** : Lancer `./scripts/stripe-webhook.sh` et tester un paiement complet !
