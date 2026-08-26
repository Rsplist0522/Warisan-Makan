<?php

namespace App\Services;

use App\Models\ShopImage;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use RuntimeException;

class HeritageShopImageService
{
    /**
     * Retained for compatibility with older records and callers. New files use
     * the configured HeritageShop disk returned by diskName().
     */
    public const DISK = 'public';

    public const DIRECTORY = 'heritage-shops';

    /**
     * Return the disk used for all newly uploaded HeritageShop images.
     */
    public function diskName(): string
    {
        $disk = config('heritage_shop.image_disk', 'r2');

        return is_string($disk) && $disk !== '' ? $disk : 'r2';
    }

    /**
     * Return the module-owned root directory for new images.
     */
    public function directory(): string
    {
        $directory = config('heritage_shop.image_directory', self::DIRECTORY);

        return is_string($directory) && trim($directory) !== ''
            ? trim($directory, '/')
            : self::DIRECTORY;
    }

    /**
     * Store a validated Heritage image on shared object storage by default.
     */
    public function store(File|UploadedFile $file, ?string $directory = null): string
    {
        $path = Storage::disk($this->diskName())->putFile($directory ?: $this->directory(), $file);

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
        ]);
    }

    /**
     * Return a response for a stored image without exposing filesystem paths.
     * New records use the configured HeritageShop disk; older records are read
     * from the public and legacy media disks when that file still exists.
     */
        public function response(ShopImage $image)
    {
        return $this->responseForPath($image->path);
    }

    public function responseForPath(string $path, ?string $downloadName = null)
    {
        $disk = $this->diskForPath($path);

        abort_unless($disk !== null, 404);

        return $disk->response($path, $downloadName ?: basename($path), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }



    /**
     * Delete a file from the configured disk and known legacy disks without
     * touching files outside the HeritageShop path supplied by the caller.
     */
    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        foreach ($this->candidateDiskNames() as $diskName) {
            Storage::disk($diskName)->delete($path);
        }
    }

    public function exists(?string $path): bool
    {
        return $this->diskForPath($path) !== null;
    }

    private function diskForPath(?string $path): ?FilesystemAdapter
    {
        if (blank($path)) {
            return null;
        }

        foreach ($this->candidateDiskNames() as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    /**
     * New storage first, then the prior public and media disks for migrations.
     * The unique list also prevents duplicate I/O when settings overlap.
     */
    private function candidateDiskNames(): array
    {
        return array_values(array_unique(array_filter([
            $this->diskName(),
            self::DISK,
            config('filesystems.media_disk'),
        ], fn ($diskName): bool => is_string($diskName) && $diskName !== '')));
    }
}
