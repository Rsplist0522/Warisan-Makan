<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HeritageShop extends Model
{
    protected $fillable = [
        'name','slug','location','country','category','description',
        'founder','establishment_year','heritage_story','operating_hours','participating_since','highlight','source_url'
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = Str::slug($model->name . '-' . ($model->establishment_year ?? ''));
            }
        });
    }
}
