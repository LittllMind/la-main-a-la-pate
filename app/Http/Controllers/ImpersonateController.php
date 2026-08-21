<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ImpersonateController extends Controller
{
    /**
     * Basculer temporairement le compte admin principal dans la peau d’un rôle test.
     * Réservé à l’environnement local et au vrai admin (id 1).
     */
    public function become(Request $request, string $role)
    {
        if (! app()->environment('local')) {
            abort(403, 'Impersonation réservée au mode local.');
        }

        $user = Auth::user();
        if (! $user || $user->id !== 1) {
            abort(403, 'Seul le compte principal admin peut utiliser cette fonction.');
        }

        // S’assurer qu’un profil test existe pour ce rôle
        $testEmail = "test-{$role}@local.test";
        $target = User::firstOrCreate(
            ['email' => $testEmail],
            [
                'name' => 'Test ' . ucfirst($role),
                'username' => "test-{$role}-" . uniqid(),
                'password' => bcrypt('pass'),
                'role' => $role,
                'email_verified_at' => now(),
                'requires_setup' => false,
            ]
        );

        // Conserver l’admin original en session pour restaurer plus tard
        session(['impersonate_admin_id' => $user->id]);

        Auth::login($target);

        $redirect = $request->input('redirect', route('dashboard'));

        return redirect($redirect)->with('success', "Tu consultes désormais l'application en tant que rôle : {$role}.");
    }

    public function restore(Request $request)
    {
        if (! app()->environment('local')) {
            abort(403, 'Impersonation réservée au mode local.');
        }

        $adminId = session('impersonate_admin_id');
        if (! $adminId) {
            return redirect()->route('home')->with('error', 'Aucune impersonation active.');
        }

        $admin = User::find($adminId);
        if (! $admin || ! $admin->isAdmin()) {
            abort(404);
        }

        session()->forget('impersonate_admin_id');
        Auth::login($admin);

        return redirect($request->input('redirect', route('dashboard')))->with('success', 'Session admin principale restaurée.');
    }
}
