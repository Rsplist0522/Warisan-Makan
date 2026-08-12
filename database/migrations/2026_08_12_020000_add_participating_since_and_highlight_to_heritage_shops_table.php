<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('heritage_shops', function (Blueprint $table) {
            if (!Schema::hasColumn('heritage_shops', 'participating_since')) {
                $table->string('participating_since')->nullable();
            }
            if (!Schema::hasColumn('heritage_shops', 'highlight')) {
                $table->string('highlight')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('heritage_shops', function (Blueprint $table) {
            if (Schema::hasColumn('heritage_shops', 'participating_since')) {
                $table->dropColumn('participating_since');
            }
            if (Schema::hasColumn('heritage_shops', 'highlight')) {
                $table->dropColumn('highlight');
            }
        });
    }
};
