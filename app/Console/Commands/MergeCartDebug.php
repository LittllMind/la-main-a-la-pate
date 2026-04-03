<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cart;
use App\Models\Vinyle;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class MergeCartDebug extends Command
{
    protected $signature = 'debug:merge-cart {source=tst-session-xyz}';
    protected $description = 'Debug: create anonymous cart and merge into first user cart';

    public function handle()
    {
        $source = $this->argument('source');

        Cart::where('session_id', $source)->whereNull('user_id')->delete();
        $anon = Cart::create(['session_id' => $source, 'expires_at' => now()->addHours(2)]);

        $vin = Vinyle::where('quantite', '>', 0)->first();
        if (!$vin) {
            $this->error('NO_VIN');
            return 1;
        }

        $anon->items()->create(['vinyle_id' => $vin->id, 'fond_id' => null, 'quantite' => 1, 'prix_unitaire' => $vin->prix]);

        $user = User::first();
        if (!$user) {
            $this->error('NO_USER');
            return 1;
        }

        Auth::loginUsingId($user->id);

        $before = app(\App\Services\CartService::class)->count();
        $merged = app(\App\Services\CartService::class)->mergeAnonymousCart($source, $anon->id);
        $after = app(\App\Services\CartService::class)->count();

        $this->info('SOURCE: '.$source);
        $this->info('ANON_CART_ID: '.$anon->id);
        $this->info('USER_ID: '.$user->id);
        $this->info('BEFORE: '.$before);
        $this->info('AFTER: '.$after);

        return 0;
    }
}
