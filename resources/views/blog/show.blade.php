@extends('layouts.blog')

@section('title', $post->title . ' — La Main à la Pâte')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <article>
        <header class="mb-8 text-center">
            <div class="flex items-center justify-center gap-3 mb-4">
                <span class="text-sm font-medium px-3 py-1 rounded-full bg-purple-900 text-purple-300">
                    {{ $post->published_at->format('d M Y') }}
                </span>
                <span class="text-sm text-gray-500">
                    {{ $post->published_at->diffForHumans() }}
                </span>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">
                {{ $post->title }}
            </h1>
            @if($post->excerpt)
            <p class="text-xl text-gray-400 italic max-w-2xl mx-auto">
                {{ $post->excerpt }}
            </p>
            @endif
        </header>

        <div class="prose prose-invert prose-lg max-w-none prose-headings:text-white prose-p:text-gray-300 prose-strong:text-white prose-a:text-purple-400 prose-a:no-underline hover:prose-a:underline prose-blockquote:border-l-purple-500 prose-blockquote:bg-gray-800 prose-blockquote:py-2 prose-blockquote:px-4 prose-blockquote:rounded-r">
            {!! nl2br(e($post->content)) !!}
        </div>

        <footer class="mt-12 pt-8 border-t border-gray-700">
            <div class="flex items-center justify-between">
                <a href="{{ route('blog.index') }}" class="inline-flex items-center text-gray-400 hover:text-white transition">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Tous les articles
                </a>

                <a href="https://t.me/share/url?url={{ urlencode(route('blog.show', $post->slug)) }}&text={{ urlencode($post->title) }}" target="_blank" class="inline-flex items-center text-blue-400 hover:text-blue-300 transition" title="Partager sur Telegram">
                    <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                    </svg>
                    Partager sur Telegram
                </a>
            </div>
        </footer>
    </article>
</div>
@endsection
