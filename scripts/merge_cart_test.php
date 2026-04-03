<?php

use App\Models\Cart;
use App\Models\Vinyle;
use App\Models\User;

$source = 'tst-session-xyz';

// Cleanup existing
Cart::where('session_id', $source)->whereNull('user_id')->delete();

$anon = Cart::create(['session_id' => $source, 'expires_at' => now()->addHours(2)]);

$vin = Vinyle::where('quantite', '>', 0)->first();
if (!$vin) {
    echo "NO_VIN\n";
    return;
}

$anon->items()->create(['vinyle_id' => $vin->id, 'fond_id' => null, 'quantite' => 1, 'prix_unitaire' => $vin->prix]);

echo "ANON CART CREATED id={$anon->id}\n";

$user = User::first();
if (!$user) {
    echo "NO_USER\n";
    return;
}

echo "USING USER id={$user->id}\n";

\Illuminate\Support\Facades\Auth::loginUsingId($user->id);

echo 'AUTH? ' . (\Illuminate\Support\Facades\Auth::check() ? 'yes' : 'no') . "\n";

echo 'USER CART BEFORE: ' . app(App\Services\CartService::class)->count() . "\n";

app(App\Services\CartService::class)->mergeAnonymousCart($source);

echo 'USER CART AFTER: ' . app(App\Services\CartService::class)->count() . "\n";

echo "DONE\n";
