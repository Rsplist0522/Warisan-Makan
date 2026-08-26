<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heritage_shop_contributions', function (Blueprint $table) {
            $table->uuid('submission_token')->nullable()->after('user_id');
            $table->unique(['user_id', 'submission_token'], 'hsc_user_submission_token_unique');
        });
    }

    public function down(): void
    {
        Schema::table('heritage_shop_contributions', function (Blueprint $table) {
            $table->dropUnique('hsc_user_submission_token_unique');
            $table->dropColumn('submission_token');
        });
    }
};
