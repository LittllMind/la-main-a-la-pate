<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Save the current session id so we can find the anonymous cart after session regeneration
        $previousSessionId = session()->getId();

        $request->session()->regenerate();

        // Store source session id in a temporary cookie (expires when browser closes) instead of session
        // because session data might not persist through regenerate()
        \Illuminate\Support\Facades\Cookie::queue('cart_merge_source_id', $previousSessionId, 0);
        \Illuminate\Support\Facades\Cookie::queue('cart_merge_pending', 'true', 0);

        // Log for debugging cart merge flow
        \Illuminate\Support\Facades\Log::info('Cart merge scheduled on login', [
            'user_id' => Auth::id(),
            'previous_session_id' => $previousSessionId,
            'current_session_id' => session()->getId(),
        ]);

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
