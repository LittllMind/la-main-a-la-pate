<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Empêche le clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');
        
        // Empêche le MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // Protection XSS (pour anciens navigateurs)
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Content Security Policy basique
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';");

        return $response;
    }
}
