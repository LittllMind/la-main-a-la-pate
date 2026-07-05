<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'entity_type',
        'entity_id',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'json',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault([
            'name' => 'Utilisateur anonyme',
            'pseudonyme' => 'Anonyme',
        ]);
    }

    public static function log(
        string $event,
        ?User $user = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => $user?->id,
            'event_type' => $event,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public static function recent(int $limit = 100)
    {
        return self::with('user')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    public static function forUser(int $userId, int $limit = 50)
    {
        return self::with('user')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    public static function forEntity(string $type, int $id, int $limit = 50)
    {
        return self::with('user')
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
