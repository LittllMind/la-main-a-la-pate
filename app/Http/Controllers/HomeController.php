<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()) {
            return redirect()->intended(route('dashboard'));
        }

        // Landing publique minimale et neutre : aucun catalogue, aucun Hall, aucun Login exposé.
        return view('landing');
    }
}
