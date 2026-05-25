<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function about()
    {
        return view('pages.about');
    }

    public function seraphotheque()
    {
        return view('pages.seraphotheque');
    }

    public function contactForm()
    {
        return view('pages.contact');
    }

    public function contactSubmit(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        Contact::create($validated);

        return redirect()->route('contact')->with('success', 'Votre message a bien ete envoye. Nous vous repondrons dans les meilleurs delais.');
    }

    public function legal()
    {
        return view('pages.legal');
    }

    public function privacy()
    {
        return view('pages.privacy');
    }
}
