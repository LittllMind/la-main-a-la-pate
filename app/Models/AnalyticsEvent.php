<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'event_type',
        'visitor_key',
        'path',
        'document_key',
        'referrer_host',
        'user_agent_family',
        'device_family',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
