<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Fillable([
    'name',
    'username',
    'email',
    'phone',
    'city',
    'bio',
    'password',
    'google_id',
    'profile_photo',
    'language',
    'role',
    'status',
    'deactivated_at',
    'deactivated_by_user_id',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function heritageShopContributions()
    {
        return $this->hasMany(HeritageShopContribution::class);
    }

    public function correctionRequests()
    {
        return $this->hasMany(CorrectionRequest::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class, 'uploaded_by_user_id');
    }

    public function deactivatedBy()
    {
        return $this->belongsTo(self::class, 'deactivated_by_user_id');
    }

    public function deactivatedUsers()
    {
        return $this->hasMany(self::class, 'deactivated_by_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool
    {
        return $this->status !== 'inactive';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'inactive';
    }

    public function profilePhotoUrl(): ?string
    {
        if (! $this->profile_photo) {
            return null;
        }

        if (str_starts_with($this->profile_photo, 'http://') || str_starts_with($this->profile_photo, 'https://')) {
            return $this->profile_photo;
        }

        $diskName = config('filesystems.media_disk', 'public');
        $disk = Storage::disk($diskName);

        if ($diskName === 'r2') {
            try {
                return $disk->temporaryUrl($this->profile_photo, now()->addMinutes(15));
            } catch (Throwable) {
            }
        }

        if ($disk->exists($this->profile_photo)) {
            return $disk->url($this->profile_photo);
        }

        if ($diskName === 'local' || $diskName === 'public') {
            return asset('storage/'.$this->profile_photo);
        }

        return $disk->url($this->profile_photo);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
