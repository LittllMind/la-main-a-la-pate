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
        return route('subjects.documents.view', [$this->subject->slug, $this->id]);
    }

    public function downloadUrl(): string
    {
        return route('subjects.documents.download', [$this->subject->slug, $this->id]);
    }

    public function hasStoredFile(): bool
    {
        return filled($this->path) && Storage::disk($this->disk)->exists($this->path);
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

    /**
     * Groupe de classification publique déterministe pour les documents du dossier Séraphothèque.
     * Basé d'abord sur la taxonomie documentaire (document_type), puis sur la
     * source_reference / doc_id en fallback ; aucune migration DB.
     */
    public function seraphothequeGroup(): string
    {
        $type = strtolower((string) $this->document_type);

        if (str_contains($type, 'source de presse')) {
            return 'press';
        }

        if (str_contains($type, 'source primaire')) {
            return 'primary';
        }

        if (str_contains($type, 'dossier documentaire')) {
            return 'dossier';
        }

        if (str_contains($type, 'comparatif documentaire')) {
            return 'comparatif';
        }

        if (str_contains($type, 'document de contexte')) {
            return 'context';
        }

        if (str_contains($type, 'synthèse')) {
            return 'synthesis';
        }

        // Fallback historique sur source_reference (V7/V8 sans document_type).
        $ref = (string) $this->source_reference;

        $primary = [
            'SERAPH-DOC-0535',      // Bail été 2025 signé
            'SERAPH-DOC-0239',      // Projet convention été 2026
            'SERAPH-DOC-0904',      // Sommation du 24 avril 2026
            'SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026', // Correspondance mairie (V8 fallback)
            'SERAPH-DOC-1263',      // Email maire 14 mai 2026
            'SERAPH-DOC-0293',      // Demande AOT 16 juin 2026
        ];
        foreach ($primary as $needle) {
            if (str_contains($ref, $needle)) {
                return 'primary';
            }
        }

        $positions = [
            'SERAPH-DOC-0997',      // Lettre ouverte Séraphothèque
            'Email-2026-07-01',     // Information déplacement portants
            'SERAPH-DOC-0486',      // Projet délibération AOT
        ];
        foreach ($positions as $needle) {
            if (str_contains($ref, $needle)) {
                return 'positions';
            }
        }

        if (str_contains($ref, 'COMP-2025-2026')) {
            return 'synthesis';
        }

        if (str_contains($ref, 'SERAPH-DOC-PROFESSION-FOI')) {
            return 'context';
        }

        return 'other';
    }

    /**
     * Ordre déterministe au sein du groupe Séraphothèque (pas seulement position DB).
     *
     * Quand la taxonomie documentaire V8/V9 est renseignée (source primaire,
     * dossier documentaire, comparatif documentaire, document de contexte,
     * synthèse…), on applique l'ordre adapté à cette taxonomie. Sinon, on
     * conserve l'ordre canonique V7 pour ne pas casser les tests historiques.
     */
    public function seraphothequeOrder(): int
    {
        $ref = (string) $this->source_reference;
        $type = strtolower((string) $this->document_type);

        $isV9Taxonomy = str_contains($type, 'source de presse')
            || str_contains($type, 'source primaire')
            || str_contains($type, 'dossier documentaire')
            || str_contains($type, 'comparatif documentaire')
            || str_contains($type, 'document de contexte')
            || str_contains($type, 'synthèse');

        if ($isV9Taxonomy) {
            return match (true) {
                str_contains($ref, 'SERAPH-DOC-0535') => 1,
                str_contains($ref, 'SERAPH-DOC-0239') => 2,
                str_contains($ref, 'SERAPH-DOC-0904') => 3,
                str_contains($ref, 'SERAPH-DOC-0293') => 4,
                str_contains($ref, 'SERAPH-DOC-0997') => 5,
                str_contains($ref, 'SERAPH-DOC-0486') => 6,
                str_contains($ref, 'Email-2026-05-27-DGFIP') => 7,
                str_contains($ref, 'SERAPH-DOC-PRESSE-JDM-2026-06-11') => 8,
                str_contains($ref, 'SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026') => 10,
                str_contains($ref, 'COMP-2025-2026') => 20,
                str_contains($ref, 'SERAPH-DOC-PROFESSION-FOI') => 30,
                str_contains($ref, 'SERAPH-DOC-1263') => 100,
                str_contains($ref, 'Email-2026-07-01') => 101,
                str_contains($ref, 'Email-2026-04-03-PUBLIC') => 102,
                default => 99,
            };
        }

        return match (true) {
            str_contains($ref, 'SERAPH-DOC-0535') => 1,
            str_contains($ref, 'SERAPH-DOC-0239') => 2,
            str_contains($ref, 'SERAPH-DOC-0904') => 3,
            str_contains($ref, 'SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026') => 4,
            str_contains($ref, 'SERAPH-DOC-1263') => 5,
            str_contains($ref, 'SERAPH-DOC-0293') => 6,
            str_contains($ref, 'SERAPH-DOC-0997') => 7,
            str_contains($ref, 'Email-2026-07-01') => 8,
            str_contains($ref, 'SERAPH-DOC-0486') => 9,
            str_contains($ref, 'COMP-2025-2026') => 10,
            str_contains($ref, 'SERAPH-DOC-PROFESSION-FOI') => 11,
            default => 99,
        };
    }

    public function isEmail(): bool
    {
        if ($this->document_type === 'email') {
            return true;
        }

        // Patch V8-C / V9 : certains messages publics sont reclassifiés comme
        // sources primaires sans perdre leur comportement email.
        if ($this->mime_type === 'application/json' && in_array($this->source_reference, [
            'seraphotheque-pack:Email-2026-04-03-PUBLIC',
            'seraphotheque-pack:Email-2026-05-27-DGFIP',
            'gmail:19e686ddc7c6d792',
        ], true)) {
            return true;
        }

        return false;
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
