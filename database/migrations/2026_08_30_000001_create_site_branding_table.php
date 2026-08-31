<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_branding', function (Blueprint $table): void {
            $table->id();
            $table->mediumText('logo_data')->nullable();
            $table->string('logo_mime_type', 100)->nullable();
            $table->string('logo_filename', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_branding');
    }
};
