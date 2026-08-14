<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('heritage_shops', function (Blueprint $table) {
            if (!Schema::hasColumn('heritage_shops', 'name')) {
                $table->string('name')->nullable()->index()->after('shop_name');
            }
            if (!Schema::hasColumn('heritage_shops', 'slug')) {
                $table->string('slug')->nullable()->index()->after('name');
            }
            if (!Schema::hasColumn('heritage_shops', 'location')) {
                $table->string('location')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('heritage_shops', 'country')) {
                $table->string('country')->nullable()->after('location');
            }
            if (!Schema::hasColumn('heritage_shops', 'category')) {
                $table->string('category')->nullable()->after('country');
            }
            if (!Schema::hasColumn('heritage_shops', 'description')) {
                $table->text('description')->nullable()->after('category');
            }
            if (!Schema::hasColumn('heritage_shops', 'founder')) {
                $table->string('founder')->nullable()->after('description');
            }
            if (!Schema::hasColumn('heritage_shops', 'source_url')) {
                $table->string('source_url')->nullable()->unique()->after('operating_hours');
            }
        });
    }

    public function down()
    {
        Schema::table('heritage_shops', function (Blueprint $table) {
            foreach (['source_url', 'founder', 'description', 'category', 'country', 'location', 'slug', 'name'] as $column) {
                if (Schema::hasColumn('heritage_shops', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
