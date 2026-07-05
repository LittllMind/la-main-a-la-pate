<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class FirstTimeSetupController extends Controller
{
    /**
     * Affiche le formulaire de première configuration.
     */
    public function __invoke(Request $request)
    {
        return view('auth.first-setup');
    }

    /**
     * Traite la soumission du formulaire.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'pseudonyme' => ['nullable', 'string', 'max:100', Rule::unique('users')->ignore($user->id)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'pseudonyme' => $validated['pseudonyme'] ?? $user->pseudonyme,
            'password' => $validated['password'],
            'requires_setup' => false,
        ]);

        return redirect()->route('dashboard')->with('success', 'Votre profil est configuré !');
    }
}
