<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostPublicController extends Controller
{
    public function index()
    {
        return view('home', LandingSectionController::sectionsForHall());
    }

    public function show($slug)
    {
        $post = Post::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return view('posts.show', compact('post'));
    }

    public function adminIndex()
    {
        $posts = Post::with('category')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return view('admin.posts.index', compact('posts'));
    }

    public function create()
    {
        $categories = Category::orderBy('display_order')->get();
        return view('admin.posts.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'status' => 'required|in:draft,pending,published',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['user_id'] = auth()->id();

        Post::create($validated);

        return redirect()->route('admin.posts.index')->with('success', 'Article cree.');
    }

    public function edit(Post $post)
    {
        $categories = Category::orderBy('display_order')->get();
        return view('admin.posts.edit', compact('post', 'categories'));
    }

    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'status' => 'required|in:draft,pending,published',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $post->update($validated);

        return redirect()->route('admin.posts.index')->with('success', 'Article mis a jour.');
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return redirect()->route('admin.posts.index')->with('success', 'Article supprime.');
    }

    public function publish(Post $post)
    {
        $post->update(['status' => 'published', 'published_at' => now()]);
        return redirect()->route('admin.posts.index')->with('success', 'Article publie.');
    }
}
