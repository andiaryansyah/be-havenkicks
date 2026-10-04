<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = ['category_id','name','slug','price','original_price','discount_percent','stock','rating','image'];

    protected static function booted() {
        static::creating(function ($p) {
            if (empty($p->slug)) {
                $p->slug = Str::slug($p->name) . '-' . uniqid();
            }
        });
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }
    
    // FIX: Jangan panggil $this->category->parent() disini, bikin error kalau category null
    public function parentCategory() {
        return $this->belongsTo(Category::class, 'category_id')->with('parent');
    }

    public function variants() {
        return $this->hasMany(ProductVariant::class);
    }

    public function images()
{
    return $this->hasMany(ProductImage::class);
}
}