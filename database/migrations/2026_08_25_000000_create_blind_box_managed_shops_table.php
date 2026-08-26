<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blind_box_managed_shops', function (Blueprint $table) {
            $table->id();
            // We store the 'stable_key' (lowercased name) to identify the shop
            $table->string('source_key')->unique(); 
            $table->string('shop_name');
            $table->string('category');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blind_box_managed_shops');
    }
};
