<?php

use App\Models\SiteBranding;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('branding:import-logo {path : Path to a PNG, JPEG, WebP, GIF, or SVG logo}', function (string $path): int {
    $absolutePath = preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/]{2}|\/)/', $path)
        ? $path
        : base_path($path);

    if (! File::isFile($absolutePath)) {
        $this->error("Logo file not found: {$path}");

        return Command::FAILURE;
    }

    $mimeType = File::mimeType($absolutePath);
    $allowedTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/gif', 'image/svg+xml'];

    if (! in_array($mimeType, $allowedTypes, true)) {
        $this->error('Use a PNG, JPEG, WebP, GIF, or SVG image.');

        return Command::FAILURE;
    }

    if (File::size($absolutePath) > 2 * 1024 * 1024) {
        $this->error('The logo must be 2 MB or smaller.');

        return Command::FAILURE;
    }

    $branding = SiteBranding::current() ?? new SiteBranding();
    $branding->fill([
        'logo_data' => base64_encode(File::get($absolutePath)),
        'logo_mime_type' => $mimeType,
        'logo_filename' => basename($absolutePath),
    ])->save();

    $this->info('Logo saved in the database. It is now used across the shared user and admin sidebars.');

    return Command::SUCCESS;
})->purpose('Import the global Warisan Makan logo into the database');
