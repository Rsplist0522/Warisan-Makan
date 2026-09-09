<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blind_box_favourites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blind_box_draw_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('shop_source_id')->nullable();
            $table->string('shop_name');
            $table->string('category')->nullable();
            $table->string('state')->nullable();
            $table->string('year')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('address')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'shop_name'], 'blind_box_favourites_user_shop_unique');
            $table->index(['user_id', 'removed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blind_box_favourites');
    }
};
