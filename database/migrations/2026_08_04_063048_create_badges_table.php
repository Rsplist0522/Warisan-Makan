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
        Schema::create('badges', function (Blueprint $table) {

            $table->id('badge_id');

            $table->string('badge_name', 100);

            $table->text('description');

            $table->string('icon')->nullable();

            $table->string('criteria_type');

            $table->integer('criteria_value');

            $table->integer('points')->default(0);
            
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};