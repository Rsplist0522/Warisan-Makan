<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('heritage_shop_contribution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['heritage_shop_contribution_id', 'created_at'], 'moderation_activity_contribution_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_activities');
    }
};
