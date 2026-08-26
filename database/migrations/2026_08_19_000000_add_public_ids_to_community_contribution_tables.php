<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->addPublicId('heritage_shop_contributions');
        $this->addPublicId('correction_requests');
    }

    public function down(): void
    {
        $this->dropPublicId('correction_requests');
        $this->dropPublicId('heritage_shop_contributions');
    }

    private function addPublicId(string $table): void
    {
        Schema::table($table, function (Blueprint $table): void {
            $table->uuid('public_id')->nullable()->after('id');
        });

        DB::table($table)
            ->whereNull('public_id')
            ->orderBy('id')
            ->select('id')
            ->chunkById(500, function ($records) use ($table): void {
                foreach ($records as $record) {
                    DB::table($table)
                        ->where('id', $record->id)
                        ->update(['public_id' => (string) Str::uuid()]);
                }
            });

        Schema::table($table, function (Blueprint $table): void {
            $table->unique('public_id');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE {$table} MODIFY public_id CHAR(36) NOT NULL");
        }
    }

    private function dropPublicId(string $table): void
    {
        Schema::table($table, function (Blueprint $table): void {
            $table->dropUnique("{$table}_public_id_unique");
            $table->dropColumn('public_id');
        });
    }
};
