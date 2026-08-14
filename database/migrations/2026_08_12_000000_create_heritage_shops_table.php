<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('heritage_shops', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('slug')->nullable()->index();
            $table->string('location')->nullable();
            $table->string('country')->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('founder')->nullable();
            $table->integer('establishment_year')->nullable();
            $table->text('heritage_story')->nullable();
            $table->string('operating_hours')->nullable();
            $table->string('source_url')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('heritage_shops');
    }
};
