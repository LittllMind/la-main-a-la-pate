<?php

namespace App\Http\Controllers;

use App\Models\UserShortcut;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserShortcutController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'url' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:20'],
        ]);

        $request->user()->shortcuts()->create([
            'label' => $data['label'],
            'url' => $data['url'],
            'icon' => $data['icon'] ?: '🔗',
            'position' => $request->user()->shortcuts()->count(),
        ]);

        return redirect()->route('dashboard')->with('status', 'Raccourci ajoute.');
    }

    public function destroy(Request $request, UserShortcut $shortcut): RedirectResponse
    {
        if ($shortcut->user_id !== $request->user()->id) {
            abort(403);
        }

        $shortcut->delete();

        return redirect()->route('dashboard')->with('status', 'Raccourci supprime.');
    }
}
