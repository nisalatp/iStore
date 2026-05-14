<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'price', 'stock_quantity', 'category_id', 'description', 'sku'];
    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        $images = [
            asset('images/products/product-1.jpg'),
            asset('images/products/product-2.jpg'),
            asset('images/products/product-3.jpg'),
            asset('images/products/product-4.jpg'),
            asset('images/products/product-5.jpg'),
            asset('images/products/product-6.jpg'),
            asset('images/products/product-7.jpg'),
            asset('images/products/product-8.jpg'),
            asset('images/products/product-9.jpg'),
            asset('images/products/product-10.jpg')
        ];
        
        // Use the product ID to consistently pick an image
        return $images[$this->id % count($images)];
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function wishlistItems()
    {
        return $this->hasMany(WishlistItem::class);
    }
}
