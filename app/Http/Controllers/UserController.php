<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the specified user profile.
     */
    public function show(User $user)
    {
        $currentUser = auth()->user();
        
        // Règles d'accès:
        // - Soi-même: toujours OK
        // - Employé/Admin: OK (pour support client)
        // - Autre client: interdit
        if ($currentUser->id !== $user->id && !$currentUser->isAdmin() && !$currentUser->isEmploye()) {
            abort(403);
        }

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        // Vérifier que l'utilisateur peut éditer ce profil
        if (auth()->id() !== $user->id && !auth()->user()->isAdmin()) {
            abort(403);
        }

        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        // Vérifier que l'utilisateur peut mettre à jour ce profil
        if (auth()->id() !== $user->id && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
        ];

        // Seul l'admin peut changer le rôle
        if (auth()->user()->isAdmin()) {
            $rules['role'] = 'required|in:admin,employe,client';
        }

        $validated = $request->validate($rules);

        // Préserver le rôle si non-admin
        if (!auth()->user()->isAdmin()) {
            $validated['role'] = $user->role;
        }

        $user->update($validated);

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'Profil mis à jour avec succès.');
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request, User $user)
    {
        // Vérifier que l'utilisateur peut changer ce mot de passe
        if (auth()->id() !== $user->id && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Mot de passe mis à jour avec succès.');
    }
}
