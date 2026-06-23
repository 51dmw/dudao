<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('check_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dimension_id')->constrained('dimensions')->cascadeOnDelete();
            $table->string('name', 80);
            $table->unsignedTinyInteger('points');
            $table->enum('default_level', ['P0', 'P1', 'P2', 'P3'])->default('P2')
                  ->comment('异常时默认问题等级');
            $table->smallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_items');
    }
};
