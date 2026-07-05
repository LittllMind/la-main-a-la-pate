<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Logout;

class LogLogoutActivity
{
    public function handle(Logout $event): void
    {
        ActivityLog::log(
            event: 'logout',
            user: $event->user,
            description: 'Déconnexion',
            metadata: [
                'email' => $event->user?->email,
                'username' => $event->user?->username,
            ]
        );
    }
}
