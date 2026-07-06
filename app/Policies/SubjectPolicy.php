<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    public function view(?User $user, Subject $subject): bool
    {
        // Draft = auteur + admin uniquement
        if ($subject->status === 'draft') {
            return $this->canManage($user, $subject);
        }

        // Archived = admin uniquement
        if ($subject->status === 'archived') {
            return $user !== null && $user->isAdmin();
        }

        // Published : dépend de la visibilité
        if ($subject->status === 'published') {
            return match ($subject->visibility) {
                'public'   => $user !== null,                  // connecté obligatoire
                'citoyen'  => $user !== null,                  // connecté n'importe quel rôle
                'admin'    => $user !== null && $user->isAdmin(),
                default    => false,
            };
        }

        return false;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'moderator', 'citoyen', 'member']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'moderator', 'citoyen', 'member']);
    }

    public function update(User $user, Subject $subject): bool
    {
        return $this->canManage($user, $subject) || $subject->isCollaborator($user);
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->role === 'admin' || $user->id === $subject->user_id;
    }

    public function publish(User $user, Subject $subject): bool
    {
        return $this->canManage($user, $subject) || $subject->isCollaborator($user);
    }

    private function canManage(?User $user, Subject $subject): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->isAdmin()
            || $user->isModerator()
            || $user->id === $subject->user_id
            || $subject->isCollaborator($user);
    }
}
