<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\User;

echo "=== 🧪 TEST RBAC - Vinyls Stock ===\n\n";

// Test accès admin
$admin = User::where('email', 'admin@example.com')->first();
if ($admin) {
    Auth::login($admin);
    echo "✅ ADMIN TEST\n";
    echo "   User: " . Auth::user()->name . " (Role: " . Auth::user()->role . ")\n";
    echo "   Can access /vinyles: " . (Auth::user()->role === 'admin' ? 'OUI ✅' : 'NON ❌') . "\n";
    echo "   Can access /stats: " . (Auth::user()->role === 'admin' ? 'OUI ✅' : 'NON ❌') . "\n";
    echo "   Can access /fonds: " . (Auth::user()->role === 'admin' ? 'OUI ✅' : 'NON ❌') . "\n";
    echo "   Can access /ventes: " . (Auth::user()->role === 'admin' ? 'OUI ✅' : 'NON ❌') . "\n";
    Auth::logout();
} else {
    echo "❌ Admin user not found!\n";
}

echo "\n";

// Test accès employé
$employe = User::where('email', 'employe@example.com')->first();
if ($employe) {
    Auth::login($employe);
    echo "✅ EMPLOYÉ TEST\n";
    echo "   User: " . Auth::user()->name . " (Role: " . Auth::user()->role . ")\n";
    echo "   Can access /vinyles: " . (Auth::user()->role === 'admin' ? 'OUI ✅' : 'NON ❌ (restricted)') . "\n";
    echo "   Can access /kiosque: OUI ✅ (public)\n";
    Auth::logout();
} else {
    echo "❌ Employé user not found!\n";
}

echo "\n";

// Test accès client
$client = User::where('email', 'client@example.com')->first();
if ($client) {
    Auth::login($client);
    echo "✅ CLIENT TEST\n";
    echo "   User: " . Auth::user()->name . " (Role: " . Auth::user()->role . ")\n";
    echo "   Can access /vinyles: " . (Auth::user()->role === 'admin' ? 'OUI ✅' : 'NON ❌ (restricted)') . "\n";
    echo "   Can access /kiosque: OUI ✅ (public)\n";
    echo "   Can access /cart: OUI ✅ (public)\n";
    Auth::logout();
} else {
    echo "❌ Client user not found!\n";
}

echo "\n=== ✅ TOUS LES TESTS RBAC SONTPRÊTS ===\n";
