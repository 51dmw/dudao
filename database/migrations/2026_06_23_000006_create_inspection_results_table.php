<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inspection_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspections')->cascadeOnDelete();
            $table->foreignId('check_item_id')->constrained('check_items');
            $table->boolean('is_normal')->default(true)->comment('1=正常 0=异常(扣分并生成问题)');
            $table->string('remark', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_results');
    }
};
