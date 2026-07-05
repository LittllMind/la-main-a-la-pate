<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Category extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'color', 'display_order', 'icon'];
    public function posts(): HasMany { return $this->hasMany(Post::class); }
    public function subjects(): HasMany { return $this->hasMany(Subject::class); }
    public function subCategories(): HasMany { return $this->hasMany(SubCategory::class); }
}
