<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\SubjectImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SubjectImageController extends Controller
{
    public function index(Subject $subject)
    {
        Gate::authorize('update', $subject);

        return view('subjects.images.index', [
            'subject' => $subject->load('images'),
        ]);
    }

    public function store(Request $request, Subject $subject)
    {
        Gate::authorize('update', $subject);

        $request->validate([
            'image' => 'required|image|max:10240',
            'alt' => 'nullable|string|max:255',
        ]);

        $file = $request->file('image');
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . Str::random(4) . '.' . $file->getClientOriginalExtension();
        $path = "subjects/{$subject->id}/{$filename}";

        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        $maxPosition = SubjectImage::where('subject_id', $subject->id)->max('position') ?? 0;

        $image = SubjectImage::create([
            'subject_id' => $subject->id,
            'filename' => $filename,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'alt' => $request->input('alt'),
            'position' => $maxPosition + 1,
        ]);

        return redirect()
            ->route('subjects.images.index', $subject->slug)
            ->with('success', 'Image ajoutee.')
            ->withFragment('image-' . $image->id);
    }

    public function update(Request $request, Subject $subject, SubjectImage $image)
    {
        Gate::authorize('update', $subject);

        $validated = $request->validate([
            'alt' => 'nullable|string|max:255',
            'position' => 'required|integer|min:0',
        ]);

        $image->update($validated);

        return redirect()
            ->route('subjects.images.index', $subject->slug)
            ->with('success', 'Image mise a jour.');
    }

    public function destroy(Subject $subject, SubjectImage $image)
    {
        Gate::authorize('update', $subject);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return redirect()
            ->route('subjects.images.index', $subject->slug)
            ->with('success', 'Image supprimee.');
    }
}
