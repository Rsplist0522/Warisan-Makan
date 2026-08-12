<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heritage_shop_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contribution_title')->nullable();
            $table->string('shop_name')->nullable();
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
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heritage_shop_contributions');
    }
};