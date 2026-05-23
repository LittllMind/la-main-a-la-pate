<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Topic extends Model
{
    use HasFactory;
    protected $fillable = ['space_id', 'user_id', 'title', 'slug', 'body', 'is_pinned', 'is_locked', 'view_count'];
    public function space(): BelongsTo { return $this->belongsTo(Space::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function replies(): HasMany { return $this->hasMany(Reply::class); }
}
