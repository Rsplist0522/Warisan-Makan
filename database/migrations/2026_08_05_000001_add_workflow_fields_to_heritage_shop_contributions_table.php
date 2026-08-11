<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heritage_shop_contributions', function (Blueprint $table) {
            $table->foreignId('reviewed_by_user_id')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('review_started_at')->nullable()->after('reviewed_by_user_id');
            $table->text('admin_feedback')->nullable()->after('review_started_at');
            $table->timestamp('resubmitted_at')->nullable()->after('admin_feedback');
            $table->timestamp('withdrawn_at')->nullable()->after('resubmitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('heritage_shop_contributions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropColumn([
                'review_started_at',
                'admin_feedback',
                'resubmitted_at',
                'withdrawn_at',
            ]);
        });
    }
};
