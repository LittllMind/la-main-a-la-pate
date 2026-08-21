<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SubjectDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'filename',
        'stored_filename',
        'path',
        'disk',
        'mime_type',
        'size',
        'title',
        'description',
        'document_date',
        'document_type',
        'author',
        'recipient',
        'source_reference',
        'representation_type',
        'redacted',
        'establishes',
        'limitations',
        'category',
        'position',
        'visibility',
        'source_sha256',
    ];

    protected $casts = [
        'position' => 'integer',
        'size'     => 'integer',
        'visibility' => VisibilityLevel::class,
        'redacted' => 'boolean',
        'document_date' => 'date',
        'representation_type' => RepresentationType::class,
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function url(): string
    {
        return route('subjects.documents.download', [$this->subject->slug, $this->id]);
    }

    public function scopeVisibleTo($query, ?User $user)
    {
        return $query->where(function ($q) use ($user) {
            if ($user === null) {
                $q->where('visibility', VisibilityLevel::Public->value);
                return;
            }

            if ($user->isModeratorOrAdmin()) {
                return;
            }

            $q->whereIn('visibility', [
                VisibilityLevel::Citizen->value,
                VisibilityLevel::Public->value,
            ]);
        });
    }

    public function visibleTo(?User $user): bool
    {
        return $this->visibility?->visibleTo($user) ?? false;
    }

    public function isSecure(): bool
    {
        return $this->disk === 'documents';
    }

    public function icon(): string
    {
        return match ($this->extension()) {
            'pdf'  => '📄',
            'doc', 'docx' => '📝',
            'xls', 'xlsx', 'csv' => '📊',
            'mp3', 'wav', 'ogg' => '🔊',
            'jpg', 'jpeg', 'png', 'gif', 'webp' => '🖼️',
            'zip', 'rar', '7z' => '📦',
            default => '📎',
        };
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->filename, PATHINFO_EXTENSION));
    }

    public function humanSize(): string
    {
        $bytes = $this->size;
        if ($bytes < 1024) return $bytes . ' octets';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' Ko';
        if ($bytes < 1024 * 1024 * 1024) return round($bytes / (1024 * 1024), 1) . ' Mo';
        return round($bytes / (1024 * 1024 * 1024), 1) . ' Go';
    }

    public function isPreviewable(): bool
    {
        return in_array($this->extension(), ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    public function redactedBadge(): ?string
    {
        return $this->redacted ? 'Version expurgée' : null;
    }

    public function previewUrl(): ?string
    {
        // Tous les documents attachés sont désormais privés/chiffrés,
        // aucune URL directe publique n'est exposée.
        return null;
    }
}
