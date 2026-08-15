<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $duplicateColumns = [
        'name' => 'shop_name',
        'category' => 'primary_food_category',
        'founder' => 'founder_name',
        'description' => 'heritage_story',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('heritage_shops')) {
            return;
        }

        foreach ($this->duplicateColumns as $duplicate => $canonical) {
            if (! Schema::hasColumn('heritage_shops', $duplicate)
                || ! Schema::hasColumn('heritage_shops', $canonical)) {
                continue;
            }

            $conflicts = DB::table('heritage_shops')
                ->whereNotNull($duplicate)
                ->whereNotNull($canonical)
                ->whereColumn($duplicate, '<>', $canonical)
                ->count();

            if ($conflicts > 0) {
                throw new RuntimeException("Cannot drop heritage_shops.{$duplicate}: {$conflicts} rows differ from {$canonical}.");
            }

            DB::table('heritage_shops')
                ->whereNull($canonical)
                ->whereNotNull($duplicate)
                ->update([$canonical => DB::raw($duplicate)]);
        }

        if (Schema::hasColumn('heritage_shops', 'location')) {
            $conflicts = DB::table('heritage_shops')
                ->select(['id', 'location', 'address', 'city', 'state'])
                ->whereNotNull('location')
                ->orderBy('id')
                ->get()
                ->filter(function ($shop): bool {
                    $location = trim((string) $shop->location);
                    $derived = trim(implode(', ', array_filter([
                        $shop->address,
                        $shop->city,
                        $shop->state,
                    ])));

                    return $location !== '' && $derived !== '' && $location !== $derived;
                })
                ->count();

            if ($conflicts > 0) {
                throw new RuntimeException("Cannot drop heritage_shops.location: {$conflicts} rows differ from address/city/state.");
            }

            DB::table('heritage_shops')
                ->whereNull('address')
                ->whereNotNull('location')
                ->update(['address' => DB::raw('location')]);
        }

        Schema::table('heritage_shops', function (Blueprint $table): void {
            if (Schema::hasColumn('heritage_shops', 'name')) {
                $table->dropIndex(['name']);
                $table->dropColumn('name');
            }

            foreach (['location', 'category', 'description', 'founder'] as $column) {
                if (Schema::hasColumn('heritage_shops', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('heritage_shops', function (Blueprint $table): void {
            if (! Schema::hasColumn('heritage_shops', 'name')) {
                $table->string('name')->nullable()->index()->after('shop_name');
            }
            if (! Schema::hasColumn('heritage_shops', 'location')) {
                $table->string('location')->nullable()->after('slug');
            }
            if (! Schema::hasColumn('heritage_shops', 'category')) {
                $table->string('category')->nullable()->after('country');
            }
            if (! Schema::hasColumn('heritage_shops', 'description')) {
                $table->text('description')->nullable()->after('category');
            }
            if (! Schema::hasColumn('heritage_shops', 'founder')) {
                $table->string('founder')->nullable()->after('description');
            }
        });

        DB::table('heritage_shops')->update([
            'name' => DB::raw('shop_name'),
            'category' => DB::raw('primary_food_category'),
            'founder' => DB::raw('founder_name'),
            'description' => DB::raw('heritage_story'),
        ]);
    }
};
