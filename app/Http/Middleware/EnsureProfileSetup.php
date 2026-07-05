<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileSetup
{
    /**
     * Redirect new users to first-time profile setup.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->requires_setup && ! $request->is('first-setup', 'first-setup/*', 'logout')) {
            return redirect()->route('first-setup');
        }

        return $next($request);
    }
}
