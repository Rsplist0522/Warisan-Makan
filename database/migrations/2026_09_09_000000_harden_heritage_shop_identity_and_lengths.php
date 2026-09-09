<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heritage_shops', function (Blueprint $table): void {
            $table->string('address', 500)->nullable()->change();
            $table->char('duplicate_key', 64)->nullable()->after('source_url');
            $table->unsignedBigInteger('version')->default(1)->after('duplicate_key');
        });

        Schema::table('shop_images', function (Blueprint $table): void {
            $table->string('path', 500)->change();
        });

        $seen = [];
        DB::table('heritage_shops')
            ->select(['id', 'shop_name', 'address', 'city', 'state'])
            ->orderBy('id')
            ->each(function (object $shop) use (&$seen): void {
                $normalize = static fn (mixed $value): string => mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $value) ?? (string) $value));
                $key = hash('sha256', json_encode([
                    $normalize($shop->shop_name),
                    $normalize($shop->address),
                    $normalize($shop->city),
                    $normalize($shop->state),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                // Preserve historical duplicate rows without deleting user
                // data. The first row holds the canonical key, so all future
                // writes are still protected by the unique constraint.
                DB::table('heritage_shops')->where('id', $shop->id)->update([
                    'duplicate_key' => isset($seen[$key]) ? null : $key,
                ]);
                $seen[$key] = true;
            });

        Schema::table('heritage_shops', function (Blueprint $table): void {
            $table->unique('duplicate_key', 'heritage_shops_duplicate_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('heritage_shops', function (Blueprint $table): void {
            $table->dropUnique('heritage_shops_duplicate_key_unique');
            $table->dropColumn(['duplicate_key', 'version']);
            $table->string('address')->nullable()->change();
        });

        Schema::table('shop_images', function (Blueprint $table): void {
            $table->string('path')->change();
        });
    }
};
