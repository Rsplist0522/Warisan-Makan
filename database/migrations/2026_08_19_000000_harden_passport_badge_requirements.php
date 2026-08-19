<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('badges')) {
            return;
        }

        $thresholds = [3, 8, 15, 25, 40];
        $badges = DB::table('badges')
            ->where('is_active', true)
            ->where('criteria_type', 'visits')
            ->orderBy('criteria_value')
            ->orderBy('badge_id')
            ->get();

        foreach ($badges as $index => $badge) {
            $threshold = $thresholds[$index] ?? max(40, (int) $badge->criteria_value);

            if ((int) $badge->criteria_value < $threshold) {
                DB::table('badges')
                    ->where('badge_id', $badge->badge_id)
                    ->update(['criteria_value' => $threshold]);
            }
        }
    }

    public function down(): void
    {
        // Badge thresholds are business data and are not lowered automatically.
    }
};
