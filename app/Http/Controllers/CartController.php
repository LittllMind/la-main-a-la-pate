<?php
// app/Http/Controllers/CartController.php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /** @var CartService */
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Afficher le panier
     */
    public function index()
    {
        $cart = $this->cartService->getCart();

        return view('cart.index', [
            'cart'        => $cart,
            'stockErrors' => $this->cartService->checkStock(), // retourne un tableau de messages
        ]);
    }

    /**
     * Ajouter un vinyle au panier
     */
    public function add(Request $request)
    {
        $data = $request->validate([
            'vinyle_id' => 'required|integer|exists:vinyles,id',
            'quantite'  => 'required|integer|min:1',
            'fond'      => 'nullable|string|in:standard,miroir,dore',
        ]);

        $fondType = $data['fond'] ?? 'standard';

        // On laisse CartService s'occuper de retrouver le Fond correspondant
        $this->cartService->addVinyle(
            $data['vinyle_id'],
            $data['quantite'],
            $fondType
        );

        return back()->with('success', 'Vinyle ajouté au panier !');
    }

    /**
     * Mettre à jour la quantité d'un item
     */
    public function update(Request $request, int $itemId)
    {
        $validated = $request->validate([
            'quantite' => 'required|integer|min:1',
        ]);

        try {
            $this->cartService->updateQuantite($itemId, $validated['quantite']);

            return redirect()->back()->with('success', 'Quantité mise à jour');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Supprimer un item du panier
     */
    public function remove(int $itemId)
    {
        try {
            $this->cartService->removeItem($itemId);

            return redirect()->back()->with('success', 'Article supprimé');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Vider le panier
     */
    public function clear()
    {
        $this->cartService->clear();

        return redirect()
            ->route('cart.index')
            ->with('success', 'Panier vidé');
    }

    /**
     * Nombre total d'articles (pour badge par ex.)
     */
    public function count()
    {
        return response()->json([
            'count' => $this->cartService->count(),
        ]);
    }
}
