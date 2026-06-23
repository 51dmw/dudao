<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained('websites')->cascadeOnDelete();
            $table->foreignId('inspector_id')->constrained('users');
            $table->date('inspect_date')->index();
            $table->decimal('score_product', 4, 1)->default(0);
            $table->decimal('score_content', 4, 1)->default(0);
            $table->decimal('score_ux', 4, 1)->default(0);
            $table->decimal('score_ad', 4, 1)->default(0);
            $table->decimal('score_exec', 4, 1)->default(0);
            $table->decimal('score_adjust', 4, 1)->default(0)->comment('问题整改加减分');
            $table->decimal('total_score', 5, 1)->default(0);
            $table->char('grade', 1)->nullable();
            $table->enum('status', ['draft', 'submitted', 'reviewed'])->default('draft');
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
