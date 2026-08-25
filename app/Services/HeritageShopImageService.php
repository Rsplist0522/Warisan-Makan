<?php

namespace App\Services;

use App\Models\ShopImage;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class HeritageShopImageService
{
    public const DISK = 'public';

    public const DIRECTORY = 'heritage-shops';

    /**
     * Store a validated Heritage image on Laravel's public disk.
     */
    public function store(File|UploadedFile $file, string $directory = self::DIRECTORY): string
    {
        $path = Storage::disk(self::DISK)->putFile($directory, $file);

        if ($path === false || blank($path)) {
            throw new RuntimeException('The Heritage image could not be saved.');
        }

        return $path;
    }

    /**
     * Generate the stable, module-owned URL used by public and admin views.
     */
    public function url(ShopImage $image): string
    {
        return route('heritage-shops.images.show', [
            'heritageShop' => $image->shop_id,
            'image' => $image->id,
        ], false);
    }

    /**
     * Return a response for a stored image without exposing filesystem paths.
     * New records use the public disk; older records are read from the
     * configured legacy media disk when that file still exists.
     */
    public function response(ShopImage $image)
    {
        $disk = $this->diskForPath($image->path);

        abort_unless($disk !== null, 404);

        return $disk->response($image->path, basename($image->path), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Delete a file from the public disk and any legacy disk configured for
     * the Heritage module without touching other module files.
     */
    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);

        $legacyDiskName = config('filesystems.media_disk');
        if (is_string($legacyDiskName) && $legacyDiskName !== self::DISK) {
            Storage::disk($legacyDiskName)->delete($path);
        }
    }

    private function diskForPath(?string $path)
    {
        if (blank($path)) {
            return null;
        }

        $publicDisk = Storage::disk(self::DISK);
        if ($publicDisk->exists($path)) {
            return $publicDisk;
        }

        $legacyDiskName = config('filesystems.media_disk');
        if (is_string($legacyDiskName) && $legacyDiskName !== self::DISK) {
            $legacyDisk = Storage::disk($legacyDiskName);
            if ($legacyDisk->exists($path)) {
                return $legacyDisk;
            }
        }

        return null;
    }
}
