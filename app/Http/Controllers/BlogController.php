<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Affiche la liste des articles publiés
     */
    public function index()
    {
        $posts = Post::published()
            ->orderBy('published_at', 'desc')
            ->paginate(10);

        return view('blog.index', compact('posts'));
    }

    /**
     * Affiche un article spécifique
     */
    public function show($slug)
    {
        $post = Post::where('slug', $slug)
            ->where(function ($query) {
                $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
            })
            ->firstOrFail();

        return view('blog.show', compact('post'));
    }
}
