<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('heritage_shops')
            ->where('source_url', 'like', '%example.com%')
            ->delete();
    }

    public function down()
    {
        // This migration is intentionally irreversible because legacy sample data
        // is being permanently removed from the production dataset.
    }
};
