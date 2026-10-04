<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'parent_id'];

    // Bikin slug otomatis
    protected static function booted() {
        static::creating(fn ($c) => $c->slug = Str::slug($c->name) . '-' . uniqid());
    }

    public function parent() {
        return $this->belongsTo(Category::class, 'parent_id');
    }
    public function children() {
        return $this->hasMany(Category::class, 'parent_id');
    }
    public function products() {
        return $this->hasMany(Product::class);
    }
}