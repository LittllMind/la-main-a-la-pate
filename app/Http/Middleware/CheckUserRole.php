<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * Gère la requête et vérifie le rôle de l'utilisateur.
     *
     * Supporte plusieurs rôles séparés par des virgules: 'role:admin,employe'
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $roles  Rôles autorisés (séparés par virgule)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (!auth()->check()) {
            abort(401, 'Non authentifié');
        }

        $userRole = auth()->user()->role;
        $allowedRoles = array_map('trim', explode(',', $roles));

        if (!in_array($userRole, $allowedRoles)) {
            abort(403, "Accès non autorisé. Rôle requis : {$roles}");
        }

        return $next($request);
    }
}
