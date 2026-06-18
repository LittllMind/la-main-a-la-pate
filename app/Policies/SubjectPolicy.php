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

    public function create(User $user): bool
    {
        return $user->role === 'admin' || $user->role === 'citoyen';
    }

    public function update(User $user, Subject $subject): bool
    {
        return $this->canManage($user, $subject);
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->role === 'admin' || $user->id === $subject->user_id;
    }

    private function canManage(?User $user, Subject $subject): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->role === 'admin' || $user->id === $subject->user_id;
    }
}
