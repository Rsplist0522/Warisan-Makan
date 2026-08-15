<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('heritage_shop_id')->constrained()->cascadeOnDelete();
            $table->string('field_name', 80);
            $table->text('current_value');
            $table->text('suggested_value');
            $table->text('reason');
            $table->json('evidence_paths')->nullable();
            $table->string('status', 40)->default('pending');
            $table->text('admin_comment')->nullable();
            $table->text('additional_information')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('review_started_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['heritage_shop_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correction_requests');
    }
};
