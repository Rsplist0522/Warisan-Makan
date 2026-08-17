<?php

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
            $table->string('status', 40)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('review_started_at')->nullable();
            $table->text('admin_feedback')->nullable();
            $table->timestamp('resubmitted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heritage_shop_contributions');
    }
};
