<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

    protected $fillable = ['category_id', 'name', 'images', 'old_price', 'price', 'description', 'slug'];
    protected $casts = ['images' => 'array'];

    
    // Define relationship
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Show URLs like /products/my-product instead of /products/1:
    public function getRouteKeyName()
    {
        return 'slug';
    }

}
