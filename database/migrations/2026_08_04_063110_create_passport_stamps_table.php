<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('passport_stamps', function (Blueprint $table) {

            $table->id('stamp_id');

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // HeritageShop table is not ready yet
            $table->unsignedBigInteger('shop_id');

            $table->dateTime('stamp_datetime');

            $table->decimal('gps_latitude', 10, 8);

            $table->decimal('gps_longitude', 11, 8);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passport_stamps');
    }
};