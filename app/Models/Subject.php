<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use League\HTMLToMarkdown\HtmlConverter;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'theme', 'title', 'slug', 'body', 'status', 'locked_at', 'category_id', 'sub_category_id', 'visibility', 'published_at'];

    protected $casts = [
        'locked_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SubjectVersion::class)->orderBy('created_at', 'desc');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SubjectComment::class)->orderBy('created_at', 'asc');
    }
    
    public function scopeSubjectLastActivity($query)
    {
        $versionSub = SubjectVersion::query()
            ->selectRaw('MAX(created_at)')
            ->whereColumn('subject_versions.subject_id', 'subjects.id');

        $commentSub = SubjectComment::query()
            ->selectRaw('MAX(created_at)')
            ->whereColumn('subject_comments.subject_id', 'subjects.id');

        $versionSql = $versionSub->toSql();
        $commentSql = $commentSub->toSql();

        return $query
            ->select('subjects.*')
            ->selectRaw("GREATEST(
                COALESCE(subjects.updated_at, subjects.created_at),
                COALESCE(($versionSql), subjects.created_at),
                COALESCE(($commentSql), subjects.created_at)
            ) as last_activity_at", array_merge($versionSub->getBindings(), $commentSub->getBindings()));
    }

    public function images(): HasMany
    {
        return $this->hasMany(SubjectImage::class)->orderBy('position')->orderBy('created_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SubjectDocument::class)->orderBy('position')->orderBy('created_at');
    }

    public function pdfDocuments(): HasMany
    {
        return $this->hasMany(SubjectDocument::class)
            ->whereRaw("LOWER(filename) LIKE '%.pdf'")
            ->orderBy('position');
    }

    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subject_collaborators')
            ->withTimestamps();
    }

    public function publicationVotes(): HasMany
    {
        return $this->hasMany(SubjectPublicationVote::class);
    }


    public function lastSeenVersionBy(User $user): ?int
    {
        return \DB::table('subject_user_last_seen_versions')
            ->where('user_id', $user->id)
            ->where('subject_id', $this->id)
            ->value('version_id');
    }

    public function latestVersionId(): ?int
    {
        return $this->versions()->orderBy('created_at', 'desc')->orderBy('id', 'desc')->value('id');
    }

    public function hasNewVersionFor(User $user): bool
    {
        if ($this->user_id === $user->id) {
            return false;
        }

        $lastSeen = $this->lastSeenVersionBy($user);
        $latest = $this->latestVersionId();

        if ($latest === null) {
            return false;
        }

        return $lastSeen !== $latest;
    }

    public function isCollaborator(User $user): bool
    {
        return $this->collaborators()->where('user_id', $user->id)->exists();
    }

    public function startPublicationVote(): void
    {
        // Crée un vote pending pour chaque collaborateur
        foreach ($this->collaborators as $collaborator) {
            SubjectPublicationVote::firstOrCreate(
                ['subject_id' => $this->id, 'user_id' => $collaborator->id],
                ['vote' => 'pending', 'voted_at' => null]
            );
        }
    }

    public function isPublicationApproved(): bool
    {
        $votes = $this->publicationVotes;
        $collaboratorIds = $this->collaborators->pluck('id');

        if ($collaboratorIds->isEmpty()) {
            return false;
        }

        // Tous les collaborateurs doivent avoir voté 'approved'
        $approvedIds = $votes->where('vote', 'approved')->pluck('user_id');
        return $approvedIds->diff($collaboratorIds)->isEmpty()
            && $collaboratorIds->diff($approvedIds)->isEmpty();
    }

    public function canBePublished(): bool
    {
        if ($this->collaborators->isEmpty()) {
            return true; // Aucun collaborateur = l'auteur peut publier directement
        }
        return $this->isPublicationApproved();
    }

    public function renderBody(): string
    {
        $markdown = (string) $this->body;

        if (str_contains($markdown, '<') && str_contains($markdown, '>')) {
            $markdown = self::convertHtmlToMarkdown($markdown);
        }

        // Utilise GFM pour les tableaux, task lists, etc.
        $converter = new \League\CommonMark\GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return (string) $converter->convert($markdown);
    }

    public static function convertHtmlToMarkdown(string $html): string
    {
        $converter = new HtmlConverter([
            'italic_style' => '*',
            'bold_style' => '**',
            'use_autolinks' => false,
            'strip_tags' => true,
        ]);

        return $converter->convert($html);
    }

    protected static function booted(): void
    {
        static::creating(function (self $subject) {
            if (empty($subject->slug)) {
                $subject->slug = Str::slug($subject->title);
            }
        });
    }
}
