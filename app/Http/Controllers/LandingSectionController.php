<?php

namespace App\Http\Controllers;

use App\Models\LandingSection;
use App\Models\Post;
use Illuminate\Http\Request;

class LandingSectionController extends Controller
{
    public function index()
    {
        $sections = LandingSection::orderBy('position')->get();

        return view('admin.sections.index', compact('sections'));
    }

    public function create()
    {
        return view('admin.sections.create', ['section' => new LandingSection()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:120|unique:landing_sections,key',
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:50000',
            'position' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? false;

        LandingSection::create($validated);

        return redirect()->route('admin.sections.index')->with('success', 'Section creee.');
    }

    public function edit(LandingSection $section)
    {
        return view('admin.sections.edit', compact('section'));
    }

    public function update(Request $request, LandingSection $section)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:120|unique:landing_sections,key,' . $section->id,
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:50000',
            'position' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? false;

        $section->update($validated);

        return redirect()->route('admin.sections.index')->with('success', 'Section mise a jour.');
    }

    public function toggle(LandingSection $section)
    {
        $section->update(['is_active' => ! $section->is_active]);

        return redirect()->route('admin.sections.index')->with('success', 'Etat mis a jour.');
    }

    public function destroy(LandingSection $section)
    {
        $section->delete();

        return redirect()->route('admin.sections.index')->with('success', 'Section supprimee.');
    }

    public static function sectionsForHall(): array
    {
        return [
            'sections' => LandingSection::where('is_active', true)->orderBy('position')->get(),
            'posts' => Post::where('status', 'published')
                ->whereNotNull('published_at')
                ->orderBy('published_at', 'desc')
                ->paginate(10),
        ];
    }
}
