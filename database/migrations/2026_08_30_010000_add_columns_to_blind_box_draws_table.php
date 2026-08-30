<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add whatever columns the existing table is missing.
        Schema::table('blind_box_draws', function (Blueprint $table) {
            if (!Schema::hasColumn('blind_box_draws', 'period_date')) {
                $table->date('period_date')->nullable()->after('period');
            }
            if (!Schema::hasColumn('blind_box_draws', 'address')) {
                $table->string('address')->nullable()->after('image');
            }
            if (!Schema::hasColumn('blind_box_draws', 'shop_source_id')) {
                $table->unsignedBigInteger('shop_source_id')->nullable()->after('address');
            }
            if (!Schema::hasColumn('blind_box_draws', 'drawn_at')) {
                $table->timestamp('drawn_at')->nullable()->after('shop_source_id');
            }
        });

        // 2. Backfill drawn_at for any pre-existing rows using created_at.
        DB::statement('UPDATE blind_box_draws SET drawn_at = created_at WHERE drawn_at IS NULL');

        // 3. Backfill period_date for any pre-existing rows using the date
        //    portion of drawn_at (good enough for historical rows - the
        //    precise night-period-crossing-midnight logic only matters
        //    going forward, for draws the app creates from now on).
        DB::statement('UPDATE blind_box_draws SET period_date = DATE(drawn_at) WHERE period_date IS NULL');

        // 4. Remove any pre-existing duplicate (user_id, period, period_date)
        //    rows before we add the unique constraint, keeping the oldest
        //    row (lowest id) in each group. Uses <=> for a NULL-safe
        //    comparison since user_id is nullable.
        DB::statement('
            DELETE t1 FROM blind_box_draws t1
            INNER JOIN blind_box_draws t2
                ON t1.user_id <=> t2.user_id
               AND t1.period = t2.period
               AND t1.period_date <=> t2.period_date
               AND t1.id > t2.id
        ');

        // 5. Now safe to enforce the once-per-period rule at the DB level.
        Schema::table('blind_box_draws', function (Blueprint $table) {
            $table->unique(['user_id', 'period', 'period_date'], 'blind_box_draws_user_period_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('blind_box_draws', function (Blueprint $table) {
            $table->dropUnique('blind_box_draws_user_period_date_unique');
            $table->dropColumn(['period_date', 'address', 'shop_source_id', 'drawn_at']);
        });
    }
};