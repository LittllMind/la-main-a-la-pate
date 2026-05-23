<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Space extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'description', 'icon', 'display_order'];
    public function topics(): HasMany { return $this->hasMany(Topic::class); }
    public function users(): BelongsToMany { return $this->belongsToMany(User::class, 'space_user')->withPivot('notify')->withTimestamps(); }
}
