<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heritage_food_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('heritage_shop_id')->constrained('heritage_shops')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('category', 120)->nullable();
            $table->text('heritage_significance')->nullable();
            $table->string('availability', 120)->nullable();
            $table->string('price', 80)->nullable();
            $table->string('image_path', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['heritage_shop_id', 'is_active', 'display_order'], 'heritage_food_shop_active_order_idx');
        });

        // Preserve existing food_items JSON records as normalized records during
        // the migration. The JSON column remains for compatibility with older
        // integrations and contribution imports.
        DB::table('heritage_shops')->select(['id', 'food_items'])->orderBy('id')->each(function (object $shop): void {
            $items = is_string($shop->food_items) ? json_decode($shop->food_items, true) : $shop->food_items;
            if (! is_array($items)) {
                return;
            }

            $rows = [];
            foreach (array_values($items) as $order => $item) {
                if (! is_array($item) || blank($item['name'] ?? null)) {
                    continue;
                }

                $rows[] = [
                    'heritage_shop_id' => $shop->id,
                    'name' => trim((string) $item['name']),
                    'description' => filled($item['description'] ?? null) ? trim((string) $item['description']) : (filled($item['desc'] ?? null) ? trim((string) $item['desc']) : null),
                    'category' => filled($item['category'] ?? null) ? trim((string) $item['category']) : null,
                    'heritage_significance' => filled($item['heritage_significance'] ?? null) ? trim((string) $item['heritage_significance']) : null,
                    'availability' => filled($item['availability'] ?? null) ? trim((string) $item['availability']) : null,
                    'price' => filled($item['price'] ?? null) ? trim((string) $item['price']) : null,
                    'image_path' => filled($item['image_path'] ?? null) ? trim((string) $item['image_path']) : null,
                    'is_active' => ($item['is_active'] ?? true) !== false,
                    'display_order' => $order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($rows !== []) {
                DB::table('heritage_food_items')->insert($rows);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heritage_food_items');
    }
};
