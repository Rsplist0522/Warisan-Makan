<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { if (!Schema::hasTable('food_trail_suggestion_stops')) { Schema::create('food_trail_suggestion_stops', function (Blueprint $table) { $table->id(); $table->foreignId('food_trail_suggestion_id')->constrained()->cascadeOnDelete(); $table->foreignId('heritage_shop_id')->constrained('heritage_shops')->cascadeOnDelete(); $table->unsignedInteger('stop_order'); }); } Schema::table('food_trail_suggestion_stops', function (Blueprint $table) { $table->unique(['food_trail_suggestion_id','heritage_shop_id'], 'ftss_trail_shop_unique'); $table->unique(['food_trail_suggestion_id','stop_order'], 'ftss_trail_order_unique'); }); } public function down(): void { Schema::dropIfExists('food_trail_suggestion_stops'); } };
