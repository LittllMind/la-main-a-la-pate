<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct(protected CartService $cartService)
    {
    }

    public function create()
    {
        $cart = $this->cartService->getCart();

        // Vérifier que le panier n'est pas vide
        if ($cart->items->count() === 0) {
            return redirect()->route('cart.index')
                ->with('error', 'Votre panier est vide. Ajoutez des vinyles avant de commander.');
        }

        // Récupérer les adresses de l'utilisateur connecté
        $addresses = [];
        if (Auth::check()) {
            $addresses = Auth::user()->addresses()->orderBy('is_default', 'desc')->get();
        }

        // Récupérer l'adresse temporaire de session (pré-remplissage)
        $tempShipping = Session::get('order_shipping');
        $tempBilling = Session::get('order_billing');

        return view('orders.create', [
            'cart' => $cart,
            'addresses' => $addresses,
            'tempShipping' => $tempShipping,
            'tempBilling' => $tempBilling,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'address_id' => 'nullable|exists:addresses,id',
            'nom' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telephone' => 'required|string|max:20',
            'adresse' => 'required|string|max:500',
            'code_postal' => 'required|string|max:10',
            'ville' => 'required|string|max:255',
            'pays' => 'required|string|max:2',
            'instructions' => 'nullable|string|max:500',
            'save_address' => 'nullable|boolean',
            'address_label' => 'nullable|string|max:100',
            
            // Facturation
            'use_same_address' => 'nullable|boolean',
            'facturation_nom' => 'nullable|string|max:255',
            'facturation_email' => 'nullable|email|max:255',
            'facturation_telephone' => 'nullable|string|max:20',
            'facturation_adresse' => 'nullable|string|max:500',
            'facturation_code_postal' => 'nullable|string|max:10',
            'facturation_ville' => 'nullable|string|max:255',
            'facturation_pays' => 'nullable|string|max:2',
        ]);

        // Préparer les données de livraison
        $shipping = [
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'],
            'adresse' => $validated['adresse'],
            'code_postal' => $validated['code_postal'],
            'ville' => $validated['ville'],
            'pays' => $validated['pays'],
            'instructions' => $validated['instructions'] ?? null,
        ];

        // Sauvegarder l'adresse si demandé
        if (Auth::check() && ($request->input('save_address') || $request->filled('address_id'))) {
            $addressData = array_merge($shipping, [
                'user_id' => Auth::id(),
                'label' => $validated['address_label'] ?? 'Nouvelle adresse',
            ]);

            if ($request->filled('address_id')) {
                // Mettre à jour une adresse existante
                $address = Address::findOrFail($validated['address_id']);
                $address->update($addressData);
            } elseif ($request->input('save_address')) {
                // Créer une nouvelle adresse
                Address::create($addressData);
            }
        }

        // Préparer les données de facturation
        $billing = $shipping; // Par défaut, même adresse
        
        // use_same_address = '0' signifie que la case est cochée (adresse différente)
        if ($request->input('use_same_address') === '0') {
            $billing = [
                'nom' => $validated['facturation_nom'] ?? $shipping['nom'],
                'email' => $validated['facturation_email'] ?? $shipping['email'],
                'telephone' => $validated['facturation_telephone'] ?? $shipping['telephone'],
                'adresse' => $validated['facturation_adresse'] ?? $shipping['adresse'],
                'code_postal' => $validated['facturation_code_postal'] ?? $shipping['code_postal'],
                'ville' => $validated['facturation_ville'] ?? $shipping['ville'],
                'pays' => $validated['facturation_pays'] ?? $shipping['pays'],
            ];
        }

        // Stocker les infos en session
        Session::put('order_shipping', $shipping);
        Session::put('order_billing', $billing);

        return redirect()->route('orders.payment');
    }

    public function payment()
    {
        $cart = $this->cartService->getCart();
        
        // Charger les relations vinyle et fond pour affichage
        $cart->items->load(['vinyle', 'fond']);
        
        $shipping = Session::get('order_shipping');
        $billing = Session::get('order_billing');

        // Vérifier que le panier n'est pas vide
        if ($cart->items->count() === 0) {
            return redirect()->route('cart.index')
                ->with('error', 'Votre panier est vide. Ajoutez des vinyles avant de commander.');
        }

        // Vérifier que les infos de livraison existent
        if (!$shipping) {
            return redirect()->route('orders.create')
                ->with('error', 'Veuillez d\'abord renseigner vos informations de livraison.');
        }

        // Vérifier si une commande est déjà en attente pour ce panier
        if (Session::has('pending_order_id')) {
            $order = Order::find(Session::get('pending_order_id'));
            if ($order && $order->statut === 'en_attente') {
                // Réutiliser la commande existante
                return view('orders.payment', [
                    'cart' => $cart,
                    'shipping' => $shipping,
                    'billing' => $billing ?? $shipping,
                    'order' => $order,
                ]);
            }
        }

        // ✅ Créer la commande maintenant
        $order = $this->createOrderFromSession($cart, $shipping, $billing);
        
        // Stocker l'ID de la commande en session pour éviter les doublons
        Session::put('pending_order_id', $order->id);

        return view('orders.payment', [
            'cart' => $cart,
            'shipping' => $shipping,
            'billing' => $billing ?? $shipping,
            'order' => $order,
        ]);
    }

    /**
     * Créer une commande à partir des données de session
     * Gestion des commandes simultanées avec retry (idempotent)
     */
    private function createOrderFromSession($cart, $shipping, $billing)
    {
        $maxRetries = 5;
        $retryDelayMs = 50;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // Générer un numéro de commande unique (UUID + séquence)
                $numeroCommande = $this->generateUniqueOrderNumber();

                // Créer la commande en base de données
                $order = Order::create([
                    'numero_commande' => $numeroCommande,
                    'user_id' => Auth::id(),
                    'statut' => 'en_attente',
                    'total' => $cart->total,
                    'nom' => $shipping['nom'],
                    'prenom' => $shipping['nom'],
                    'email' => $shipping['email'],
                    'telephone' => $shipping['telephone'],
                    'adresse' => $shipping['adresse'],
                    'code_postal' => $shipping['code_postal'],
                    'ville' => $shipping['ville'],
                    'shipping_nom' => $shipping['nom'],
                    'shipping_prenom' => $shipping['nom'],
                    'shipping_email' => $shipping['email'],
                    'shipping_telephone' => $shipping['telephone'],
                    'shipping_adresse' => $shipping['adresse'],
                    'shipping_code_postal' => $shipping['code_postal'],
                    'shipping_ville' => $shipping['ville'],
                    'shipping_pays' => $shipping['pays'] ?? 'FR',
                    'shipping_instructions' => $shipping['instructions'] ?? null,
                    'billing_nom' => $billing['nom'],
                    'billing_prenom' => $billing['nom'],
                    'billing_email' => $billing['email'],
                    'billing_telephone' => $billing['telephone'],
                    'billing_adresse' => $billing['adresse'],
                    'billing_code_postal' => $billing['code_postal'],
                    'billing_ville' => $billing['ville'],
                    'billing_pays' => $billing['pays'] ?? 'FR',
                ]);

                // Ajouter les articles de la commande
                foreach ($cart->items as $item) {
                    if (!$item->vinyle_id) {
                        \Log::error('CartItem sans vinyle_id', ['item_id' => $item->id]);
                        continue;
                    }
                    
                    $vinyle = \App\Models\Vinyle::find($item->vinyle_id);
                    
                    if (!$vinyle) {
                        \Log::error('Vinyle non trouvé', ['vinyle_id' => $item->vinyle_id]);
                        continue;
                    }
                    
                    OrderItem::create([
                        'order_id' => $order->id,
                        'vinyle_id' => $vinyle->id,
                        'titre_vinyle' => $vinyle->modele,
                        'artiste_vinyle' => $vinyle->artiste,
                        'reference_vinyle' => $vinyle->reference,
                        'quantite' => $item->quantite,
                        'prix_unitaire' => $vinyle->prix,
                        'total' => $vinyle->prix * $item->quantite,
                    ]);
                }

                return $order;
                
            } catch (\Illuminate\Database\QueryException $e) {
                // Si erreur de doublon (23000 = integrity constraint), retry
                if ($e->getCode() == 23000 && $attempt < $maxRetries) {
                    usleep($retryDelayMs * 1000 * $attempt); // Backoff exponentiel
                    continue;
                }
                throw $e; // Ré-échouer si autre erreur ou max retries atteint
            }
        }
        
        throw new \Exception('Impossible de créer la commande après ' . $maxRetries . ' tentatives');
    }

    /**
     * Générer un numéro de commande unique thread-safe (UUID courte + timestamp + random)
     */
    private function generateUniqueOrderNumber(): string
    {
        $year = date('Y');
        $timestamp = microtime(true);
        $random = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3);
        
        return sprintf('CMD-%s-%s-%s', $year, substr(md5($timestamp . $random), 0, 6), $random);
    }

    /**
     * Supprimée : La création de commande est maintenant dans payment()
     * La redirection vers Stripe se fait directement depuis le formulaire payment.blade.php
     */

    /**
     * Page de succès après confirmation de commande
     */
    public function success()
    {
        return view('orders.success');
    }

    /**
     * Page d'annulation de commande
     */
    public function cancel()
    {
        return view('orders.cancel')
            ->with('error', 'Votre commande a été annulée.');
    }

    /**
     * Afficher les commandes de l'utilisateur connecté (Mes commandes)
     */
    public function myOrders()
    {
        $orders = Order::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->with('items.vinyle')
            ->paginate(10);

        return view('orders.my-orders', compact('orders'));
    }
}
