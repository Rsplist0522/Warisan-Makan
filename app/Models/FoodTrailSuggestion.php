<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodTrailSuggestion extends Model
{
    protected $fillable = ['title', 'subtitle', 'summary', 'location', 'category', 'is_published'];

    protected $casts = ['is_published' => 'boolean'];

    public function restaurants()
    {
        return $this->belongsToMany(HeritageShop::class, 'food_trail_suggestion_stops')
            ->withPivot('stop_order')->orderBy('food_trail_suggestion_stops.stop_order');
    }
}
