# 📦 Installation de Stripe - Projet Vinyls

## ✅ Étape 1 : Installation du package

cd /home/aur-lien/.picoclaw/workspace/vinyles-stock

# 1. Installer le package
composer require stripe/stripe-php



Exécutez la commande suivante dans le terminal :

```bash
cd /home/aur-lien/.picoclaw/workspace/vinyles-stock
composer require stripe/stripe-php


# 2. Créer le modèle et la migration
php artisan make:model Payment -m

# 3. Créer le contrôleur
php artisan make:controller PaymentController

# 4. Exécuter la migration
php artisan migrate

# 5. Vider le cache
php artisan config:clear
php artisan route:clear
```

## 🔑 Étape 2 : Configuration des clés API

### 2.1 Obtenir les clés Stripe

1. Connectez-vous sur [Stripe Dashboard](https://dashboard.stripe.com/)
2. Allez dans **Developers > API keys**
3. Copiez les clés :
   - **Clé publique test** : `pk_test_...`
   - **Clé secrète test** : `sk_test_...`

### 2.2 Ajouter au fichier .env

Ouvrez le fichier `.env` et ajoutez :

```env
STRIPE_KEY=pk_test_VOTRE_CLE_PUBLIQUE
STRIPE_SECRET=sk_test_VOTRE_CLE_SECRETE
STRIPE_WEBHOOK_SECRET=whsec_VOTRE_SECRET_WEBHOOK
```

### 2.3 Mettre à jour config/services.php

```php
// config/services.php
'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook' => [
        'secret' => env('STRIPE_WEBHOOK_SECRET'),
        'tolerance' => 300,
    ],
],
```

## 📝 Étape 3 : Créer le modèle Payment

```bash
php artisan make:model Payment -m
```

Migration `database/migrations/*_create_payments_table.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('stripe_session_id')->unique();
            $table->string('status'); // pending, success, failed
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('eur');
            $table->text('stripe_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
```

Modèle `app/Models/Payment.php` :

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'stripe_session_id',
        'status',
        'amount',
        'currency',
        'stripe_response',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'stripe_response' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
```

## 🎮 Étape 4 : Créer le PaymentController

```bash
php artisan make:controller PaymentController
```

Contenu de `app/Http/Controllers/PaymentController.php` :

```php
<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Stripe\Event;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Créer une session de paiement Stripe
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        // Vérifier que l'utilisateur est propriétaire de la commande
        if ($order->user_id !== auth()->id()) {
            abort(403, 'Commande non autorisée');
        }

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'eur',
                            'product_data' => [
                                'name' => 'Commande #' . $order->id,
                                'description' => 'Vinyles Hydrodécoupés',
                            ],
                            'unit_amount' => (int) ($order->total * 100), // Stripe utilise les centimes
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => route('payment.success', ['session_id' => '{CHECKOUT_SESSION_ID}']),
                'cancel_url' => route('payment.cancel'),
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => auth()->id(),
                ],
            ]);

            // Créer un enregistrement de paiement en attente
            Payment::create([
                'user_id' => auth()->id(),
                'order_id' => $order->id,
                'stripe_session_id' => $session->id,
                'status' => 'pending',
                'amount' => $order->total,
                'currency' => 'eur',
            ]);

            return redirect($session->url);

        } catch (\Exception $e) {
            Log::error('Erreur Stripe checkout: ' . $e->getMessage());
            return redirect()->route('orders.payment')->with('error', 'Erreur lors de l\'initialisation du paiement');
        }
    }

    /**
     * Succès du paiement
     */
    public function success(Request $request)
    {
        $session = Session::retrieve($request->session_id);

        if ($session->payment_status === 'paid') {
            $payment = Payment::where('stripe_session_id', $request->session_id)->first();

            if ($payment) {
                $payment->update([
                    'status' => 'success',
                    'paid_at' => now(),
                    'stripe_response' => $session->toArray(),
                ]);

                // Mettre à jour la commande
                $payment->order->update([
                    'status' => 'paid',
                ]);

                return view('payment.success', compact('payment'));
            }
        }

        return redirect()->route('kiosque')->with('error', 'Paiement non confirmé');
    }

    /**
     * Annulation du paiement
     */
    public function cancel()
    {
        return view('payment.cancel');
    }

    /**
     * Webhook Stripe pour les événements asynchrones
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook.secret')
            );
        } catch (\UnexpectedValueException $e) {
            // Payload invalide
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Signature invalide
            return response('Invalid signature', 400);
        }

        // Gérer l'événement
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->handleCheckoutCompleted($session);
                break;

            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->handlePaymentSucceeded($paymentIntent);
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->handlePaymentFailed($paymentIntent);
                break;

            default:
                Log::info('Événement Stripe non géré: ' . $event->type);
        }

        return response('Webhook reçu', 200);
    }

    private function handleCheckoutCompleted($session)
    {
        $payment = Payment::where('stripe_session_id', $session->id)->first();

        if ($payment && $payment->status === 'pending') {
            $payment->update([
                'status' => 'success',
                'paid_at' => now(),
            ]);

            $payment->order->update([
                'status' => 'paid',
            ]);

            Log::info('Paiement confirmé via webhook: ' . $session->id);
        }
    }

    private function handlePaymentSucceeded($paymentIntent)
    {
        Log::info('Paiement réussi: ' . $paymentIntent->id);
    }

    private function handlePaymentFailed($paymentIntent)
    {
        Log::error('Paiement échoué: ' . $paymentIntent->id);
    }
}
```

## 🛣️ Étape 5 : Ajouter les routes

Dans `routes/web.php` :

```php
// Routes de paiement Stripe
Route::middleware(['auth'])->group(function () {
    Route::post('/payment/checkout', [PaymentController::class, 'checkout'])->name('payment.checkout');
    Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');
});

// Webhook Stripe (doit être public)
Route::post('/stripe/webhook', [PaymentController::class, 'webhook'])->name('stripe.webhook');
```

## 🎨 Étape 6 : Créer les vues

### 6.1 Vue de succès `resources/views/payment/success.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Paiement réussi')

@section('content')
<div class="max-w-2xl mx-auto text-center py-12">
    <div class="text-6xl mb-6">🎉</div>
    <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent mb-4">
        Paiement réussi !
    </h1>
    <p class="text-gray-300 text-lg mb-8">
        Merci pour votre commande. Votre paiement a été confirmé.
    </p>

    <div class="bg-gray-800 rounded-2xl p-6 mb-8">
        <div class="grid grid-cols-2 gap-4 text-left">
            <div>
                <span class="text-gray-400">Numéro de commande</span>
                <p class="text-white font-semibold">#{{ $payment->order->id }}</p>
            </div>
            <div>
                <span class="text-gray-400">Montant payé</span>
                <p class="text-white font-semibold">{{ number_format($payment->amount, 2, ',', ' ') }} €</p>
            </div>
            <div>
                <span class="text-gray-400">Date</span>
                <p class="text-white font-semibold">{{ $payment->paid_at->format('d/m/Y H:i') }}</p>
            </div>
            <div>
                <span class="text-gray-400">Statut</span>
                <p class="text-green-400 font-semibold">Payé</p>
            </div>
        </div>
    </div>

    <a href="{{ route('kiosque') }}" 
        class="inline-block bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold py-3 px-8 rounded-xl transition">
        Retour au catalogue
    </a>
</div>
@endsection
```

### 6.2 Vue d'annulation `resources/views/payment/cancel.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Paiement annulé')

@section('content')
<div class="max-w-2xl mx-auto text-center py-12">
    <div class="text-6xl mb-6">😔</div>
    <h1 class="text-4xl font-bold text-gray-300 mb-4">
        Paiement annulé
    </h1>
    <p class="text-gray-400 text-lg mb-8">
        Votre paiement a été annulé. Aucune commande n'a été passée.
    </p>

    <a href="{{ route('kiosque') }}" 
        class="inline-block bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold py-3 px-8 rounded-xl transition">
        Retour au catalogue
    </a>
</div>
@endsection
```
✅ ✅ OPTION 1 (RECOMMANDÉE EN LOCAL) : Utiliser Stripe CLI
👉 C’est la méthode la plus simple et propre en développement.
1️⃣ Installe Stripe CLI (si pas déjà fait)
https://stripe.com/docs/stripe-cli
2️⃣ Connecte-toi :
stripe login
3️⃣ Lance l’écoute des webhooks :
stripe listen --forward-to localhost:8000/stripe/webhook
Stripe va te donner quelque chose comme :
Ready! Your webhook signing secret is whsec_123456789
✅ C’est CE secret que tu mets dans ton .env
STRIPE_WEBHOOK_SECRET=whsec_123456789
👉 Stripe va alors :

recevoir les events sur leurs serveurs
les renvoyer automatiquement vers ton localhost

✅ Tu peux garder ton endpoint dans le Dashboard en mode test
✅ Pas besoin de domaine public

🚀 Maintenant : connecter Stripe à ton projet local
Depuis la racine de ton projet (là où tourne ton serveur sur localhost:8000) :
stripe login
Une page navigateur va s’ouvrir → autorise.

✅ Ensuite lance l’écoute des webhooks :
stripe listen --forward-to localhost:8000/stripe/webhook
Tu verras quelque chose comme :
Ready! Your webhook signing secret is whsec_xxxxxxxxx

⚠️ IMPORTANT
👉 Ce secret est différent de celui du Dashboard Stripe.👉 En local, tu dois utiliser celui donné par stripe listen.
Ajoute-le dans ton .env :
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxx
Puis redémarre ton serveur.

🧪 Pour tester immédiatement
Tu peux simuler un paiement :
stripe trigger checkout.session.completed
Si tout est bien configuré :
✅ Stripe CLI enverra l’événement✅ Ton endpoint /stripe/webhook le recevra✅ Tu verras la requête arriver dans ton terminal

## 🧪 Étape 7 : Tester le webhook en local

Pour tester les webhooks Stripe en local, utilisez Stripe CLI :

```bash
# Installer Stripe CLI
# macOS: brew install stripe/stripe-cli/stripe
# Linux: voir https://github.com/stripe/stripe-cli

# Se connecter
stripe login

# Forwarder les webhooks vers localhost
stripe listen --forward-to http://localhost:8000/stripe/webhook
```

## 📋 Checklist finale

- [ ] Package `stripe/stripe-php` installé
- [ ] Clés API configurées dans `.env`
- [ ] Migration exécutée (`php artisan migrate`)
- [ ] Routes ajoutées
- [ ] Contrôleur créé
- [ ] Vues créées
- [ ] Webhook testé

## 🔗 Liens utiles

- [Documentation Stripe Laravel](https://stripe.com/docs/payments/checkout/laravel)
- [Stripe PHP Library](https://github.com/stripe/stripe-php)
- [Stripe Test Cards](https://stripe.com/docs/testing#cards)
  - Succès : `4242 4242 4242 4242`
  - Échec : `4000 0000 0000 0002`
