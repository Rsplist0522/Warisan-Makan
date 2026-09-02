<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passport_stamps', function (Blueprint $table): void {
            $table->index(['user_id', 'shop_id'], 'passport_stamps_user_shop_idx');
            $table->index(['user_id', 'stamp_datetime'], 'passport_stamps_user_stamped_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('passport_stamps', function (Blueprint $table): void {
            $table->dropIndex('passport_stamps_user_shop_idx');
            $table->dropIndex('passport_stamps_user_stamped_at_idx');
        });
    }
};
