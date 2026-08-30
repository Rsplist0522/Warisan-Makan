<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteBranding extends Model
{
    protected $table = 'site_branding';

    protected $fillable = [
        'logo_data',
        'logo_mime_type',
        'logo_filename',
    ];

    public static function current(): ?self
    {
        return static::query()->first();
    }

    public function hasLogo(): bool
    {
        return filled($this->logo_data)
            && filled($this->logo_mime_type)
            && base64_decode($this->logo_data, true) !== false;
    }

    public function logoBytes(): string
    {
        return base64_decode($this->logo_data, true) ?: '';
    }
}
