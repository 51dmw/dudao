<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dimensions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->string('code', 20)->unique()->comment('product/content/ux/ad/exec');
            $table->unsignedTinyInteger('max_score');
            $table->smallInteger('sort')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dimensions');
    }
};
