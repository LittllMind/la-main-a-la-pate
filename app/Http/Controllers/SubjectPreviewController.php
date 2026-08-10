<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\VisibilityLevel;
use Illuminate\Support\Facades\Gate;

class SubjectPreviewController extends Controller
{
    public function show(Subject $subject, string $audience)
    {
        Gate::authorize('update', $subject);

        $level = VisibilityLevel::tryFrom($audience);

        if ($level === null || ! in_array($level, [VisibilityLevel::Public, VisibilityLevel::Citizen], true)) {
            abort(404);
        }

        $body = $subject->bodyAtLevel($level);

        if ($body === null) {
            abort(404);
        }

        $subject->body = $body;

        // Pour l'aperçu, on ne charge que les documents visibles au niveau simulé,
        // sans toucher à la session ou au rôle de l'administrateur.
        $subject->setRelation('documents', $subject->documentsAtLevel($level));

        return view('subjects.preview', [
            'subject' => $subject,
            'level' => $level,
        ]);
    }
}
