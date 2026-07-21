<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectUserLastSeenVersion extends Model
{
    use HasFactory;

    protected $table = 'subject_user_last_seen_versions';

    protected $fillable = ['user_id', 'subject_id', 'version_id', 'seen_at'];

    protected $casts = [
        'seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(SubjectVersion::class);
    }
}
