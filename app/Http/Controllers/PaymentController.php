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
                'success_url' => config('app.url') . '/payment/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.url') . '/payment/cancel',
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
        try {
            $session = Session::retrieve($request->session_id);

            // Vérifier que le paiement est réussi
            if ($session->payment_status === 'paid') {
                // Récupérer le paiement depuis la base de données
                $payment = Payment::where('stripe_session_id', $request->session_id)->first();

                if ($payment) {
                    // Mettre à jour le paiement si ce n'est pas déjà fait
                    if ($payment->status !== 'success') {
                        $payment->update([
                            'status' => 'success',
                            'paid_at' => now(),
                            'stripe_response' => $session->toArray(),
                        ]);

                        // Mettre à jour la commande
                        $payment->order->update([
                            'status' => 'paid',
                        ]);

                        // ✅ Vider le panier après paiement confirmé
                        $cartService = app(\App\Services\CartService::class);
                        $cartService->clear();
                    }

                    return view('payment.success', compact('payment'));
                }
            }

            // Paiement non confirmé ou échoué
            return redirect()->route('kiosque.index')->with('error', 'Paiement non confirmé');
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Session Stripe invalide ou expirée
            Log::error('Session Stripe invalide: ' . $e->getMessage());
            return redirect()->route('kiosque.index')->with('error', 'Session de paiement expirée ou invalide');
        }
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
                'statut' => 'completed',
                'validee_at' => now(),
            ]);

            // ✅ Vider le panier après paiement confirmé via webhook
            $cartService = app(\App\Services\CartService::class);
            $cartService->clear();

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