<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heritage_shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_contribution_id')->nullable()->constrained('heritage_shop_contributions')->nullOnDelete();
            $table->string('shop_name');
            $table->string('primary_food_category')->nullable();
            $table->unsignedSmallInteger('establishment_year')->nullable();
            $table->string('founder_name')->nullable();
            $table->text('founder_background')->nullable();
            $table->string('current_owner_name')->nullable();
            $table->text('current_owner_details')->nullable();
            $table->longText('heritage_story')->nullable();
            $table->json('operating_hours')->nullable();
            $table->json('food_items')->nullable();
            $table->string('contact_number', 30)->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('supporting_media')->nullable();
            $table->string('publish_status')->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heritage_shops');
    }
};
