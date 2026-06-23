<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('问题编号 如 #001');
            $table->foreignId('website_id')->constrained('websites')->cascadeOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained('inspections')->nullOnDelete();
            $table->enum('level', ['P0', 'P1', 'P2', 'P3'])->index();
            $table->enum('type', ['product', 'operation', 'ad', 'content', 'seo', 'ux']);
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->string('page_url', 255)->nullable();
            $table->foreignId('reporter_id')->constrained('users');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete()
                  ->comment('null=待指派');
            $table->dateTime('due_at')->nullable()->index();
            $table->enum('status', ['pending', 'processing', 'verifying', 'closed', 'evaluating'])
                  ->default('pending')->index();
            $table->unsignedTinyInteger('repeat_count')->default(0)->comment('验收打回次数');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
