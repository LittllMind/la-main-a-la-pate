<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SubjectPdfController extends Controller
{
    public function show(Subject $subject)
    {
        Gate::authorize('view', $subject);

        $pdf = Pdf::loadView('subjects.pdf.show', [
            'subject' => $subject,
            'body' => $subject->renderBody(),
        ]);

        return $pdf->stream("{$subject->slug}.pdf");
    }

    public function download(Subject $subject)
    {
        Gate::authorize('view', $subject);

        $pdf = Pdf::loadView('subjects.pdf.show', [
            'subject' => $subject,
            'body' => $subject->renderBody(),
        ]);

        return $pdf->download("{$subject->slug}.pdf");
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Subject::class);

        $query = Subject::orderBy('title');
        $slugs = $request->input('subjects');

        if ($slugs) {
            $query->whereIn('slug', is_array($slugs) ? $slugs : explode(',', $slugs));
        }

        $subjects = $query->get();

        if ($subjects->isEmpty()) {
            abort(404, 'Aucun sujet a exporter.');
        }

        $pdf = Pdf::loadView('subjects.pdf.index', [
            'subjects' => $subjects,
        ]);

        $filename = 'sujets-' . now()->format('Y-m-d') . '.pdf';

        return $request->input('download') === '1'
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }
}
