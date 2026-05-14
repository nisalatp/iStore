<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description'];
    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        $images = [
            'face' => asset('images/categories/face.jpg'),
            'eyes' => asset('images/categories/eyes.jpg'),
            'lips' => asset('images/categories/lips.jpg'),
            'skincare' => asset('images/categories/skincare.jpg')
        ];
        
        return $images[$this->slug] ?? asset('images/products/product-1.jpg');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
