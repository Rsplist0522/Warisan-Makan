<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Throwable;

class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'uploaded_by_user_id',
        'attachable_type',
        'attachable_id',
        'media_type',
        'r2_object_key',
        'original_name',
        'mime_type',
        'file_size_bytes',
        'caption',
        'display_order',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'display_order' => 'integer',
            'is_primary' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function attachable()
    {
        return $this->morphTo();
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function getUrlAttribute(): string
    {
        $diskName = config('filesystems.media_disk');
        $disk = Storage::disk($diskName);

        if ($diskName === 'r2') {
            try {
                return $disk->temporaryUrl($this->r2_object_key, now()->addMinutes(15));
            } catch (Throwable) {
            }
        }

        return $disk->url($this->r2_object_key);
    }
}
