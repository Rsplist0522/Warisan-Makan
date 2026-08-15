<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('moderation_activities', function (Blueprint $table): void {
            $table->foreignId('correction_request_id')
                ->nullable()
                ->after('heritage_shop_contribution_id')
                ->constrained('correction_requests')
                ->cascadeOnDelete();
        });

        Schema::table('moderation_activities', function (Blueprint $table): void {
            $table->foreignId('heritage_shop_contribution_id')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('moderation_activities', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('correction_request_id');
        });
    }
};
