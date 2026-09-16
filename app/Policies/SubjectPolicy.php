<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    public function view(?User $user, Subject $subject): bool
    {
        return $subject->canBeViewedBy($user);
    }

    public function viewAny(?User $user): bool
    {
        return true; // La scope VisibleTo filtre les résultats
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'moderator', 'citoyen', 'member', 'super_admin'], true);
    }

    public function update(User $user, Subject $subject): bool
    {
        if ($subject->isSuperAdminOnly()) {
            return $user->isSuperAdmin();
        }

        if ($subject->isCollaboratorsOnly()) {
            return $user->isSuperAdmin()
                || $user->id === $subject->user_id
                || $subject->isCollaborator($user);
        }

        return $this->canManage($user, $subject) || $subject->isCollaborator($user);
    }

    public function delete(User $user, Subject $subject): bool
    {
        if ($subject->isSuperAdminOnly()) {
            return $user->isSuperAdmin();
        }

        if ($subject->isCollaboratorsOnly()) {
            return $user->isSuperAdmin()
                || $user->id === $subject->user_id;
        }

        return $user->isAdmin() || $user->id === $subject->user_id;
    }

    public function publish(User $user, Subject $subject): bool
    {
        if ($subject->isSuperAdminOnly()) {
            return $user->isSuperAdmin();
        }

        if ($subject->isCollaboratorsOnly()) {
            return $user->isSuperAdmin()
                || $user->id === $subject->user_id
                || $subject->isCollaborator($user);
        }

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
