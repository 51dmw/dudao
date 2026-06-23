<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained('websites')->cascadeOnDelete();
            $table->enum('type', ['product', 'operation'])->index()->comment('产品池/运营池');
            $table->string('module', 60)->nullable();
            $table->string('problem', 255);
            $table->text('suggestion');
            $table->enum('priority', ['P0', 'P1', 'P2', 'P3'])->default('P2');
            $table->enum('benefit', ['high', 'medium', 'low'])->nullable();
            $table->enum('status', ['pending', 'evaluating', 'accepted', 'rejected', 'done'])
                  ->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suggestions');
    }
};
