<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Login;

class LogLoginActivity
{
    public function handle(Login $event): void
    {
        ActivityLog::log(
            event: 'login',
            user: $event->user,
            description: 'Connexion réussie',
            metadata: [
                'email' => $event->user->email,
                'username' => $event->user->username,
                'role' => $event->user->role,
            ]
        );
    }
}
