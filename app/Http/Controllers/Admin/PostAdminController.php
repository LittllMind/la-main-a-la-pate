<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostAdminController extends Controller
{
    /**
     * Liste tous les articles
     */
    public function index()
    {
        $posts = Post::orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.posts.index', compact('posts'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        return view('admin.posts.create');
    }

    /**
     * Sauvegarde un nouvel article
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:posts,slug',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['published_at'] = $validated['status'] === 'published'
            ? ($validated['published_at'] ?? now())
            : null;

        Post::create($validated);

        return redirect()->route('admin.posts.index')
            ->with('success', 'Article créé avec succès.');
    }

    /**
     * Formulaire d'édition
     */
    public function edit(Post $post)
    {
        return view('admin.posts.edit', compact('post'));
    }

    /**
     * Met à jour un article
     */
    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:posts,slug,' . $post->id,
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $validated['published_at'] = $validated['status'] === 'published'
            ? ($validated['published_at'] ?? now())
            : null;

        $post->update($validated);

        return redirect()->route('admin.posts.index')
            ->with('success', 'Article mis à jour avec succès.');
    }

    /**
     * Supprime un article
     */
    public function destroy(Post $post)
    {
        $post->delete();

        return redirect()->route('admin.posts.index')
            ->with('success', 'Article supprimé.');
    }
}
