<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

    protected $fillable = ['category_id', 'name', 'images', 'old_price', 'price', 'description'];
    // protected $casts = ['images' => 'array'];

    
    // Define relationship to Category (assuming Category model exists)
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

}
