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
        'mime_type',
        'size',
        'title',
        'description',
        'category',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
        'size'     => 'integer',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function url(): string
    {
        return route('subjects.documents.download', [$this->subject->slug, $this->id]);
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

    public function previewUrl(): ?string
    {
        if ($this->extension() === 'pdf') {
            return $this->url();
        }
        if (in_array($this->extension(), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return Storage::disk('subject_documents')->url($this->path);
        }
        return null;
    }
}
