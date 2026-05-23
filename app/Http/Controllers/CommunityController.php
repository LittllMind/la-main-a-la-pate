<?php

namespace App\Http\Controllers;

use App\Models\Space;
use App\Models\Topic;
use App\Models\Reply;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    public function index()
    {
        $spaces = Space::orderBy('display_order')->get();
        return view('community.index', compact('spaces'));
    }

    public function show($slug)
    {
        $space = Space::where('slug', $slug)->firstOrFail();
        $topics = Topic::where('space_id', $space->id)
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return view('community.show', compact('space', 'topics'));
    }

    public function storeTopic(Request $request, $slug)
    {
        $space = Space::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $topic = Topic::create([
            'space_id' => $space->id,
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'body' => $validated['body'],
        ]);

        return redirect()->route('community.topic.show', [$space->slug, $topic->slug])
            ->with('success', 'Sujet cree.');
    }

    public function showTopic($spaceSlug, $topicSlug)
    {
        $space = Space::where('slug', $spaceSlug)->firstOrFail();
        $topic = Topic::where('space_id', $space->id)
            ->where('slug', $topicSlug)
            ->firstOrFail();

        $topic->increment('view_count');
        $replies = $topic->replies()->orderBy('created_at')->get();

        return view('community.topic', compact('space', 'topic', 'replies'));
    }

    public function storeReply(Request $request, $spaceSlug, $topicSlug)
    {
        $space = Space::where('slug', $spaceSlug)->firstOrFail();
        $topic = Topic::where('space_id', $space->id)
            ->where('slug', $topicSlug)
            ->firstOrFail();

        $validated = $request->validate([
            'body' => 'required|string',
        ]);

        Reply::create([
            'topic_id' => $topic->id,
            'user_id' => auth()->id(),
            'body' => $validated['body'],
        ]);

        return redirect()->route('community.topic.show', [$space->slug, $topic->slug])
            ->with('success', 'Reponse ajoutee.');
    }
}
