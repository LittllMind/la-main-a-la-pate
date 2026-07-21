<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

class SubjectCollaboratorController extends Controller
{
    // Ajoute un collaborateur à un sujet (auteur ou admin uniquement)
    public function store(Request $request, Subject $subject)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // Seul l'auteur ou un admin peut ajouter des collaborateurs
        if (!auth()->user()->isAdmin() && auth()->user()->id !== $subject->user_id) {
            abort(403);
        }

        // Ne peut pas s'ajouter soi-même
        if ($request->user_id == $subject->user_id) {
            return redirect()->route('subjects.edit', $subject)
                ->with('error', 'L\'auteur est déjà collaborateur par défaut.');
        }

        $subject->collaborators()->syncWithoutDetaching([$request->user_id]);

        $addedUser = User::find($request->user_id);

        ActivityLog::log(
            event: 'collaborator_added',
            user: auth()->user(),
            entityType: 'subject',
            entityId: $subject->id,
            description: "Ajout du collaborateur « {$addedUser?->name} » sur « {$subject->title} »",
            metadata: ['collaborator_id' => $addedUser?->id]
        );

        return redirect()->route('subjects.edit', $subject)
            ->with('success', 'Collaborateur ajouté.');
    }

    // Retire un collaborateur
    public function destroy(Request $request, Subject $subject, User $user)
    {
        if (!auth()->user()->isAdmin() && auth()->user()->id !== $subject->user_id) {
            abort(403);
        }

        $subject->collaborators()->detach($user->id);

        // Supprime aussi son vote s'il existe
        $subject->publicationVotes()->where('user_id', $user->id)->delete();

        ActivityLog::log(
            event: 'collaborator_removed',
            user: auth()->user(),
            entityType: 'subject',
            entityId: $subject->id,
            description: "Retrait du collaborateur « {$user->name} » sur « {$subject->title} »",
            metadata: ['collaborator_id' => $user->id]
        );

        return redirect()->route('subjects.edit', $subject)
            ->with('success', 'Collaborateur retiré.');
    }

    // Démarre un vote de publication
    public function startVote(Subject $subject)
    {
        // Seul l'auteur ou un admin peut lancer le vote
        if (!auth()->user()->isAdmin() && auth()->user()->id !== $subject->user_id) {
            abort(403);
        }

        if ($subject->collaborators->isEmpty()) {
            return redirect()->route('subjects.edit', $subject)
                ->with('error', 'Aucun collaborateur. Ajoutez-en pour voter.');
        }

        $subject->startPublicationVote();

        ActivityLog::log(
            event: 'vote_started',
            user: auth()->user(),
            entityType: 'subject',
            entityId: $subject->id,
            description: "Lancement du vote de publication pour « {$subject->title} »",
            metadata: ['collaborators_count' => $subject->collaborators->count()]
        );

        return redirect()->route('subjects.edit', $subject)
            ->with('success', 'Vote de publication lancé. Tous les collaborateurs doivent approuver.');
    }

    // Un collaborateur vote
    public function vote(Request $request, Subject $subject)
    {
        $request->validate([
            'vote' => 'required|in:approved,rejected',
        ]);

        // Vérifier que l'utilisateur est bien collaborateur
        if (!$subject->isCollaborator(auth()->user())) {
            abort(403);
        }

        $vote = $subject->publicationVotes()
            ->where('user_id', auth()->user()->id)
            ->first();

        if (!$vote) {
            return redirect()->route('subjects.edit', $subject)
                ->with('error', 'Aucun vote en cours pour ce sujet.');
        }

        $vote->update([
            'vote' => $request->vote,
            'voted_at' => now(),
        ]);

        ActivityLog::log(
            event: 'vote_cast',
            user: auth()->user(),
            entityType: 'subject',
            entityId: $subject->id,
            description: "Vote {$request->vote} de « " . auth()->user()->name . " » sur « {$subject->title} »",
            metadata: ['vote' => $request->vote]
        );

        // Vérifier si tous ont approuvé
        if ($subject->isPublicationApproved()) {
            $subject->update([
                'status' => 'published',
                'published_at' => now(),
            ]);

            return redirect()->route('subjects.show', $subject)
                ->with('success', 'Publication approuvée à l\'unanimité. Le sujet est maintenant public.');
        }

        return redirect()->route('subjects.edit', $subject)
            ->with('success', 'Vote enregistré. En attente des autres collaborateurs.');
    }
}
