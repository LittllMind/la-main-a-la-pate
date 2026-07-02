<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SubjectImage extends Model
{
    use HasFactory;

    protected $fillable = ['subject_id', 'filename', 'path', 'mime_type', 'alt', 'position'];

    protected $casts = [
        'position' => 'integer',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
