<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('heritage_shops')->cascadeOnDelete();
            $table->string('path');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['shop_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_images');
    }
};
