<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contribution_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('heritage_shop_contribution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('reason', 50);
            $table->json('snapshot');
            $table->timestamps();

            $table->unique(['heritage_shop_contribution_id', 'version_number'], 'contribution_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribution_versions');
    }
};
