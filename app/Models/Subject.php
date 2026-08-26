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

    protected $fillable = ['user_id', 'theme', 'title', 'slug', 'body', 'citizen_body', 'public_body', 'citizen_status', 'public_status', 'citizen_published_at', 'public_published_at', 'public_is_listed', 'status', 'locked_at', 'category_id', 'sub_category_id', 'visibility', 'published_at'];

    protected $casts = [
        'locked_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'citizen_published_at' => 'datetime',
        'public_published_at' => 'datetime',
        'public_is_listed' => 'boolean',
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

    /**
     * Filtre les sujets en fonction du niveau d'accès de l'utilisateur.
     * Guest : sujets dont la version publique est publiée.
     * Citoyen : sujets dont la version citoyenne est publiée OU version publique publiée.
     * Admin/moderator/auteur/collaborateur : tous les sujets non archivés.
     *
     * Note : ce scope ne filtre PAS public_is_listed.
     *        L'affichage dans les surfaces de découverte doit être appliqué
     *        explicitement via scopeListedInCatalogue quand c'est pertinent.
     */
    public function scopeVisibleTo($query, ?User $user)
    {
        if ($user === null) {
            return $query->where('public_status', 'published')
                ->whereNotNull('public_body');
        }

        if ($user->isModeratorOrAdmin()) {
            return $query;
        }

        // Auteur ou collaborateur : accès complet au dossier non archivé
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhereHas('collaborators', fn ($c) => $c->where('users.id', $user->id))
                ->orWhere(function ($q2) {
                    $q2->where('citizen_status', 'published')
                        ->whereNotNull('citizen_body');
                })
                ->orWhere(function ($q2) use ($user) {
                    // Citoyen n'a pas accès au statut public ; autres rôles authentifiés (hors admin/auteur/collab) non plus.
                    if ($user->role !== 'citoyen') {
                        $q2->whereRaw('1 = 0');
                        return;
                    }
                    $q2->where('public_status', 'published')
                        ->whereNotNull('public_body');
                });
        });
    }

    /**
     * Limite les sujets à ceux qui doivent apparaître dans les surfaces
     * générales de découverte (catalogue, recherche, arbres, sitemap).
     * Un sujet public mais public_is_listed=false reste visible par URL
     * directe et via ses CTA, mais n'apparaît pas ici.
     */
    public function scopeListedInCatalogue($query)
    {
        return $query->where('public_is_listed', true);
    }

    /**
     * Retourne un résumé textuel sécurisé pour l'utilisateur donné.
     */
    public function summaryFor(?User $user): string
    {
        $body = $this->bodyFor($user) ?? '';

        $text = strip_tags((new \League\CommonMark\GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]))->convert($body));

        return \Illuminate\Support\Str::limit(html_entity_decode($text, ENT_QUOTES, 'UTF-8'), 200);
    }

    /**
     * Libellé lisible d'un statut de publication.
     */
    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'published' => 'Publié',
            'hidden'    => 'Masqué',
            default     => 'Brouillon',
        };
    }

    /**
     * Couleur associée à un statut de publication (palette Tailwind).
     */
    public static function statusColor(string $status): string
    {
        return match ($status) {
            'published' => 'emerald',
            'hidden'    => 'amber',
            default     => 'slate',
        };
    }

    /**
     * Retourne le corps prévisualisable à un niveau d'audience donné,
     * indépendamment du statut de publication réel.
     * Public : public_body s'il est rempli.
     * Citoyen : citizen_body s'il est rempli, sinon public_body.
     * Pas de concaténation ; pas de retour au body de travail.
     */
    public function bodyAtLevel(VisibilityLevel $level): ?string
    {
        return match ($level) {
            VisibilityLevel::Public => filled($this->public_body) ? $this->public_body : null,
            VisibilityLevel::Citizen => filled($this->citizen_body)
                ? $this->citizen_body
                : (filled($this->public_body) ? $this->public_body : null),
            default => null,
        };
    }

    /**
     * Filtre les documents du sujet pour un niveau d'audience donné.
     */
    public function documentsAtLevel(VisibilityLevel $level): \Illuminate\Database\Eloquent\Collection
    {
        return $this->documents()->getQuery()->whereIn('visibility', match ($level) {
            VisibilityLevel::Public => [VisibilityLevel::Public->value],
            VisibilityLevel::Citizen => [VisibilityLevel::Public->value, VisibilityLevel::Citizen->value],
            default => [VisibilityLevel::Working->value],
        })->get();
    }

    /**
     * Retourne le corps à afficher selon le niveau d'accès.
     * Pas de fallback silencieux depuis un niveau supérieur.
     *
     * Exception Séraphothèque : la version publique du dossier documentaire
     * est accessible uniquement aux visiteurs non authentifiés (public unlisted
     * géré hors catalogue). Les utilisateurs authentifiés y accèdent via les
     * versions Working/Citoyen habituelles.
     */
    public function bodyFor(?User $user): ?string
    {
        if ($user !== null && ($user->isModeratorOrAdmin() || $user->id === $this->user_id || $this->isCollaborator($user))) {
            return $this->body;
        }

        if ($user !== null) {
            if ($this->citizen_status === 'published' && filled($this->citizen_body)) {
                return $this->citizen_body;
            }

            // Public général : membres/citoyens peuvent lire la version publique.
            // Séraphothèque : réservé aux guests.
            if ($this->public_status === 'published' && filled($this->public_body) && ! $this->isSeraphothequeDossier()) {
                return $this->public_body;
            }

            return null;
        }

        if ($this->public_status === 'published' && filled($this->public_body)) {
            return $this->public_body;
        }

        return null;
    }

    public function canBeViewedBy(?User $user): bool
    {
        if ($body = $this->bodyFor($user)) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($this->status === 'archived' && ! $user->isAdmin()) {
            return false;
        }

        return $user->isModeratorOrAdmin()
            || $user->id === $this->user_id
            || $this->isCollaborator($user);
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

        // Supprimer le lien #documents si aucun document visible pour cette représentation.
        // Le contrôleur a déjà préfiltré $this->body (via bodyFor) et $this->documents (via visibleTo).
        $hasVisibleDocs = $this->documents->count() > 0;
        if (!$hasVisibleDocs) {
            $markdown = preg_replace('/\\[([^\\]]+)\\]\\(#documents\\)/', '$1', $markdown);
            $markdown = preg_replace('/<a[^>]*href="[^"]*#documents"[^>]*>.*?<\\/a>/s', '', $markdown);
        }

        // Resoudre les liens narratif -> preuves dans le dossier Seraphotheque (Guest Public).
        // Le texte V7 reste inchangé ; seul le HTML est transforme cote serveur.
        if ($this->isSeraphothequeDossier() && str_contains($markdown, '→ Consulter la fiche documentaire')) {
            $markdown = $this->resolveSeraphothequeFicheLinks($markdown);
        }

        return self::renderMarkdownToHtml($markdown);
    }

    /**
     * Convertit un texte Markdown autonome en HTML public en reutilisant
     * exactement la chaine de rendu CommonMark du body (Pandoc anchors,
     * IDs stables, table wrappers). Pas de mutation du sujet.
     */
    public static function renderMarkdownToHtml(string $markdown): string
    {
        if (str_contains($markdown, '<') && str_contains($markdown, '>')) {
            $markdown = self::convertHtmlToMarkdown($markdown);
        }

        // Preprocess Pandoc-style heading identifiers {#id} into stable markers
        $ids = [];
        $markdown = preg_replace_callback(
            '/^(#{1,6}\\s+.*?)(?:\\s+\\{([^}]+)\\})\\s*$/m',
            function ($matches) use (&$ids) {
                $heading = $matches[1];
                $attrs = trim($matches[2]);
                $id = '';
                if (preg_match('/#([a-zA-Z0-9_-]+)/', $attrs, $idMatch)) {
                    $id = $idMatch[1];
                }
                if ($id) {
                    $ids[] = $id;
                    $marker = 'ANCHOR_' . $id;
                    // Use inline code backticks — CommonMark renders this as <code>...</code>
                    // which we swap back post-conversion
                    return $heading . ' `' . $marker . '`';
                }
                return $heading;
            },
            $markdown
        );

        $converter = new \League\CommonMark\GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $html = (string) $converter->convert($markdown);

        // Attach extracted IDs directly to heading elements (Pandoc semantics)
        foreach ($ids as $id) {
            $html = preg_replace(
                '/<h([1-6])>(.*?)\s*<code>ANCHOR_' . preg_quote($id, '/') . '<\/code>\s*<\/h\1>/',
                '<h$1 id="' . $id . '">$2</h$1>',
                $html
            );
        }

        // Assign explicit internal-link anchors to the next heading after each link.
        // This is generic: any `[text](#id)` in the body becomes the id of the
        // first subsequent heading that does not already have one.
        $html = self::assignInternalLinkAnchorsToHeadings($html);

        // Generate stable IDs for headings that don't already have one.
        $html = self::generateHeadingIds($html);

        return self::wrapHtmlTables($html);
    }

    /**
     * Englobe chaque <table> dans un wrapper overflow-x-auto pour mobile.
     */
    private static function wrapHtmlTables(string $html): string
    {
        return preg_replace('/(<table\b[^>]*>)(.*?)(<\/table>)/s', '<div class="overflow-x-auto">$1$2$3</div>', $html) ?? $html;
    }
    private static function assignInternalLinkAnchorsToHeadings(string $html): string
    {
        // Collect internal link anchors and their position.
        // Exclude document-item anchors (e.g. #doc-seraphotheque-pack-...): those target
        // list items rendered in the document section, not headings.
        $links = [];
        if (preg_match_all('/<a[^>]*\shref="#([^"]+)"[^>]*>/', $html, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                if (str_starts_with($match[0], 'doc-')) {
                    continue;
                }
                $links[] = ['id' => $match[0], 'pos' => $match[1]];
            }
        }

        if ($links === []) {
            return $html;
        }

        // IDs already present in the rendered HTML (headings, sections, document items, etc.).
        // Internal links targeting an existing element are already resolved and must not be
        // reassigned to a heading later in the page (e.g. V7 links to doc-* anchors).
        $existingElementIds = [];
        if (preg_match_all('/\sid="([^"]+)"/', $html, $existing)) {
            foreach ($existing[1] as $id) {
                $existingElementIds[$id] = true;
            }
        }

        // IDs already assigned to headings during this pass.
        $usedIds = [];
        if (preg_match_all('/<h[1-6][^>]*\sid="([^"]+)"/', $html, $existing)) {
            foreach ($existing[1] as $id) {
                $usedIds[$id] = true;
            }
        }

        // Find headings without id and assign each unassigned internal link to the
        // nearest subsequent heading whose target does not already exist in the DOM.
        $assigned = [];
        $linkIndex = 0;
        if (preg_match_all('/<h([1-6])([^>]*)>(.*?)<\/h\1>/s', $html, $headings, PREG_OFFSET_CAPTURE)) {
            foreach ($headings[0] as $i => $full) {
                $pos = $full[1];
                $tag = $headings[1][$i][0];
                $attrs = $headings[2][$i][0];
                $content = $headings[3][$i][0];

                // Skip headings that already have an id.
                if (preg_match('/\sid="([^"]+)"/', $attrs, $existingId)) {
                    $usedIds[$existingId[1]] = true;
                    continue;
                }

                // Advance link cursor to the first link occurring before this heading.
                while ($linkIndex < count($links) && $links[$linkIndex]['pos'] < $pos) {
                    $linkIndex++;
                }

                // Look backward for the nearest unassigned link whose target is not already resolved.
                $assignedId = null;
                for ($j = $linkIndex - 1; $j >= 0; $j--) {
                    $candidate = $links[$j]['id'];
                    if (!isset($assigned[$candidate]) && !isset($usedIds[$candidate]) && !isset($existingElementIds[$candidate])) {
                        $assignedId = $candidate;
                        break;
                    }
                }

                if ($assignedId !== null) {
                    $assigned[$assignedId] = true;
                    $usedIds[$assignedId] = true;
                    $newAttrs = $attrs ? $attrs . ' id="' . $assignedId . '"' : ' id="' . $assignedId . '"';
                    $html = substr_replace(
                        $html,
                        '<h' . $tag . $newAttrs . '>' . $content . '</h' . $tag . '>',
                        $pos,
                        strlen($full[0])
                    );
                    // Restart parsing because HTML length/offsets changed.
                    return self::assignInternalLinkAnchorsToHeadings($html);
                }
            }
        }

        return $html;
    }

    /**
     * Generate stable IDs for any remaining headings without an id.
     */
    private static function generateHeadingIds(string $html): string
    {
        $seenIds = [];
        if (preg_match_all('/<h[1-6][^>]*\sid="([^"]+)"/', $html, $existing)) {
            foreach ($existing[1] as $id) {
                $seenIds[$id] = true;
            }
        }

        // Canonical short ids for the V6 frozen public body headings.
        // These keys are the full slugified heading text; values are the
        // human-friendly anchors referenced in the body.
        $canonicalIds = [
            'comprendre-en-une-minute' => 'comprendre',
            'les-principaux-enjeux' => 'enjeux',
            'ce-qui-change-en-2026' => 'changements-2026',
            'chronologie' => 'chronologie',
            'positions-des-acteurs' => 'positions',
            'points-de-desaccord' => 'desaccords',
            'questions-ouvertes' => 'questions-ouvertes',
            'documents-et-fiches-documentaires' => 'documents',
            'comment-lire-les-sources' => 'lire-les-sources',
        ];

        return preg_replace_callback(
            '/<h([1-6])([^>]*)>(.*?)<\/h\1>/s',
            function ($matches) use (&$seenIds, $canonicalIds) {
                $tag = $matches[1];
                $attrs = $matches[2];
                $content = $matches[3];

                if (preg_match('/\sid="([^"]+)"/', $attrs, $existingId)) {
                    $seenIds[$existingId[1]] = true;
                    return $matches[0];
                }

                $text = strip_tags(str_replace('<', ' <', $content));
                $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
                $text = trim(preg_replace('/\s+/', ' ', $text));

                $slug = self::slugifyForHeadingId($text);
                $id = $canonicalIds[$slug] ?? $slug;
                if ($id === '') {
                    $id = 'heading-' . $tag;
                }

                $candidate = $id;
                $counter = 1;
                while (isset($seenIds[$candidate])) {
                    $counter++;
                    $candidate = $id . '-' . $counter;
                }
                $seenIds[$candidate] = true;

                $newAttrs = $attrs ? $attrs . ' id="' . $candidate . '"' : ' id="' . $candidate . '"';
                return '<h' . $tag . $newAttrs . '>' . $content . '</h' . $tag . '>';
            },
            $html
        ) ?? $html;
    }

    /**
     * Convert a heading text into a stable, URL-friendly ID.
     * Lowercase, ASCII only, spaces and punctuation collapsed to hyphens.
     */
    public static function slugifyForHeadingId(string $text): string
    {
        // Transliterate accented characters to ASCII approximations
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = strtolower($text);
        // Keep letters, digits, spaces and hyphens
        $text = preg_replace('/[^a-z0-9\s-]+/', '', $text);
        // Collapse whitespace and hyphens to a single hyphen
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-');
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

    /**
     * Détermine si ce subject est le dossier public documentaire Séraphothèque.
     */
    public function isSeraphothequeDossier(): bool
    {
        return $this->sub_category_id === 14 || ($this->subCategory?->slug === 'seraphotheque');
    }

    /**
     * Remplace les CTA textuels du dossier Séraphothèque par des liens Markdown
     * canoniques vers les ancres de documents, sans modifier le Markdown source.
     *
     * La détection repose sur le titre de section immédiatement précédent le CTA
     * et non sur les IDs numériques de la base. Seuls les documents chargés pour
     * l'audience courante (relation `documents` préfiltrée par le contrôleur) sont
     * pris en compte, garantissant l'absence de liens morts.
     *
     * Prend en charge les corps V7/V8 (titres simples) et V9 (titres avec
     * qualification documentaire) dès lors que les expressions-clés identifient
     * la pièce visée.
     */
    private function resolveSeraphothequeFicheLinks(string $markdown): string
    {
        $refs = [
            'Convention d’été 2025' => 'seraphotheque-pack:SERAPH-DOC-0535',
            'Projet de convention d’été 2026' => 'seraphotheque-pack:SERAPH-DOC-0239',
            'Projet de convention été 2026' => 'seraphotheque-pack:SERAPH-DOC-0239',
            'Sommation du 24 avril 2026' => 'seraphotheque-pack:SERAPH-DOC-0904',
            'Demande d’AOT du 16 juin 2026' => 'seraphotheque-pack:SERAPH-DOC-0293',
            'Correspondance avec la mairie' => 'seraphotheque-pack:SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026',
            'Projet de délibération' => 'seraphotheque-pack:SERAPH-DOC-0486',
            'Trésor public' => 'seraphotheque-pack:Email-2026-05-27-DGFIP',
            'Profession de foi' => 'seraphotheque-pack:SERAPH-DOC-PROFESSION-FOI',
            'Email du maire du 14 mai 2026' => 'seraphotheque-pack:SERAPH-DOC-1263',
        ];

        // Utilise la relation déjà filtrée par le contrôleur (public pour un guest).
        $existingRefs = $this->documents->pluck('source_reference')->map(fn ($ref) => (string) $ref)->all();

        $lines = explode("\n", $markdown);
        $currentContext = null;

        foreach ($lines as $key => $line) {
            // Détection du titre de section : tout titre de niveau 3 contenant
            // une expression-clé réinitialise le contexte au dernier correspondant.
            if (preg_match('/^#{1,6}\s+(.+)$/', $line, $headingMatch)) {
                $heading = $headingMatch[1];
                $matchedRef = null;
                foreach ($refs as $needle => $sourceRef) {
                    if (str_contains($heading, $needle)) {
                        $matchedRef = $sourceRef;
                    }
                }
                $currentContext = $matchedRef;
                continue;
            }

            if ($currentContext !== null && preg_match('/\*\*→\s*(Consulter la fiche documentaire|Lire la correspondance complète|Consulter le document|Consulter le courriel du 27 mai|Consulter l’extrait contextualisé)\*\*/', $line)) {
                $hasDoc = (bool) array_filter($existingRefs, fn ($ref) => str_contains($ref, $currentContext));
                if ($hasDoc) {
                    $anchor = 'doc-' . str_replace([':', '/'], '-', $currentContext);
                    $lines[$key] = preg_replace(
                        '/\*\*→\s*([^*]+)\*\*/',
                        "[**→ $1**](#{$anchor})",
                        $line
                    );
                }
                $currentContext = null;
            }
        }

        return implode("\n", $lines);
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
