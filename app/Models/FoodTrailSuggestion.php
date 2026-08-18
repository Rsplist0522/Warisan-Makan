<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodTrailSuggestion extends Model
{
    protected $fillable = ['title', 'subtitle', 'summary', 'location', 'category', 'is_published'];

    protected $casts = ['is_published' => 'boolean'];
}
