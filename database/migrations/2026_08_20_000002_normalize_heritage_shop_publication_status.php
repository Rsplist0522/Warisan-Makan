<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('heritage_shops')
            ->where('publish_status', 'approved')
            ->update(['publish_status' => 'published']);
    }

    public function down(): void
    {
        // The old `approved` value was ambiguous and is intentionally not
        // restored because it was not a valid public publication state.
    }
};
