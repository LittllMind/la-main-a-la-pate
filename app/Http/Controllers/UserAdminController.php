<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserAdminController extends Controller
{
    private const ROLES = ['admin' => 'Administrateur', 'moderator' => 'Modérateur', 'citoyen' => 'Citoyen', 'member' => 'Membre', 'invite' => 'Invité'];

    public function index(): View
    {
        $users = User::orderBy('name')->get();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => self::ROLES,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'user' => new User(),
            'roles' => self::ROLES,
            'route' => route('admin.users.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateUser($request);

        User::create([
            'name' => $validated['name'],
            'pseudonyme' => $validated['pseudonyme'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'commune' => $validated['commune'] ?? null,
            'role' => $validated['role'],
            'email_verified_at' => now(),
            'rgpd_consent_at' => now(),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur créé.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => self::ROLES,
            'route' => route('admin.users.update', $user),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validateUser($request, $user->id);

        $user->fill([
            'name' => $validated['name'],
            'pseudonyme' => $validated['pseudonyme'],
            'email' => $validated['email'],
            'commune' => $validated['commune'] ?? null,
            'role' => $validated['role'],
        ]);

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Impossible de supprimer le dernier administrateur.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur supprimé.');
    }

    private function validateUser(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'pseudonyme' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($ignoreId)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($ignoreId)],
            'commune' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', 'in:admin,moderator,citoyen,member,invite'],
            'password' => [empty($ignoreId) ? 'required' : 'nullable', 'confirmed', Password::defaults()],
        ]);
    }
}
