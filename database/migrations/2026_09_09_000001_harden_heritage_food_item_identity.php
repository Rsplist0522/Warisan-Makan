<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heritage_food_items', function (Blueprint $table): void {
            $table->string('normalized_name')->nullable()->after('name');
        });

        $seen = [];
        DB::table('heritage_food_items')
            ->select(['id', 'heritage_shop_id', 'name', 'deleted_at'])
            ->orderBy('id')
            ->each(function (object $item) use (&$seen): void {
                $normalized = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $item->name) ?? (string) $item->name));
                $group = $item->heritage_shop_id.'|'.$normalized;
                $isDuplicate = isset($seen[$group]);
                $isDeleted = $item->deleted_at !== null;

                DB::table('heritage_food_items')->where('id', $item->id)->update([
                    'normalized_name' => ($isDuplicate || $isDeleted)
                        ? '__legacy_or_deleted__'.$item->id
                        : $normalized,
                    'is_active' => $isDuplicate ? false : DB::raw('is_active'),
                ]);

                if (! $isDeleted && ! $isDuplicate) {
                    $seen[$group] = true;
                }
            });

        Schema::table('heritage_food_items', function (Blueprint $table): void {
            $table->unique(['heritage_shop_id', 'normalized_name'], 'heritage_food_shop_normalized_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('heritage_food_items', function (Blueprint $table): void {
            $table->dropUnique('heritage_food_shop_normalized_name_unique');
            $table->dropColumn('normalized_name');
        });
    }
};
