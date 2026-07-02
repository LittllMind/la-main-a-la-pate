<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    public function view(?User $user, Subject $subject): bool
    {
        return in_array($subject->status, ['published', 'archived']) || $this->canManage($user, $subject);
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
        return $this->canManage($user, $subject);
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->role === 'admin' || $user->id === $subject->user_id;
    }

    public function publish(User $user, Subject $subject): bool
    {
        return $this->canManage($user, $subject);
    }

    private function canManage(?User $user, Subject $subject): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->isAdmin()
            || $user->isModerator()
            || $user->id === $subject->user_id;
    }
}
