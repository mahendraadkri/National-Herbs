<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'slug',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // Bind routes by slug instead of id
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

}
