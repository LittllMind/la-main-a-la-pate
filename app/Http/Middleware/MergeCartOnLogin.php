<?php
// app/Http/Middleware/MergeCartOnLogin.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\CartService;

class MergeCartOnLogin
{
    public function handle(Request $request, Closure $next)
    {
        // Si l'utilisateur vient de se connecter
        $pending = $request->cookie('cart_merge_pending');
        $source = $request->cookie('cart_merge_source_id');

        \Illuminate\Support\Facades\Log::info('MergeCartOnLogin check', [
            'user_id' => auth()->id() ?? 'none',
            'auth_check' => auth()->check() ? 'true' : 'false',
            'pending_cookie' => $pending ?? 'MISSING',
            'source_cookie' => $source ?? 'MISSING',
            'all_cookies' => $request->cookies->all(),
        ]);

        if (auth()->check() && $pending) {
            $source = $source ?? session()->getId();
            $anonCartId = $request->cookie('anon_cart_id');

            \Illuminate\Support\Facades\Log::info('MergeCartOnLogin triggered', [
                'user_id' => auth()->id(),
                'source_session_id' => $source,
                'anon_cart_id_cookie' => $anonCartId,
                'current_session_id' => session()->getId(),
            ]);

            // Resolve CartService from container (works better in middleware context)
            $cartService = app(\App\Services\CartService::class);
            
            // Prefer cart-id based merge (more reliable than session id)
            $merged = $cartService->mergeAnonymousCart($source, $anonCartId ? intval($anonCartId) : null);

            // Clear merge cookies if merge occurred (or even if not to avoid repeated attempts)
            try {
                \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::forget('cart_merge_pending'));
                \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::forget('cart_merge_source_id'));
                \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::forget('anon_cart_id'));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Could not clear cart merge cookies', ['error' => $e->getMessage()]);
            }

            \Illuminate\Support\Facades\Log::info('MergeCartOnLogin finished', [
                'user_id' => auth()->id(),
                'merged' => $merged,
                'user_cart_count' => $cartService->count(),
            ]);
        }

        return $next($request);
    }
}
