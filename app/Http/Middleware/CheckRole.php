<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Vous devez être connecté pour accéder à cette page.');
        }

        $user = Auth::user();

        // Si aucun rôle n'est spécifié, on autorise l'accès
        if (empty($roles)) {
            return $next($request);
        }

        // Parse les rôles séparés par des virgules (ex: "admin,employe" → ['admin', 'employe'])
        $allowedRoles = [];
        foreach ($roles as $roleGroup) {
            $allowedRoles = array_merge($allowedRoles, array_map('trim', explode(',', $roleGroup)));
        }

        // Vérifier si l'utilisateur a l'un des rôles requis
        if (!in_array($user->role, $allowedRoles)) {
            // Pour les requêtes AJAX/API/JSON, retourner 403
            // Détection élargie pour les tests et API
            $isJsonRequest = $request->expectsJson() 
                || $request->ajax() 
                || $request->header('X-Requested-With') === 'XMLHttpRequest'
                || $request->isJson()
                || str_contains($request->header('Accept', ''), 'application/json');
                
            if ($isJsonRequest) {
                return response()->json(['error' => 'Accès refusé. Vous n\'avez pas les permissions nécessaires.'], 403);
            }
            
            return redirect()->route('kiosque.index')->with('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à cette page.');
        }

        return $next($request);
    }
}