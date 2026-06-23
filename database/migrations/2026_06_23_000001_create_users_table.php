<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 替换 Laravel 自带的 users 迁移：删除 database/migrations 里默认的
// 0001_01_01_000000_create_users_table.php 后使用本文件。
return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('email', 120)->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'supervisor', 'pm', 'operator', 'seo', 'manager'])
                  ->default('supervisor')->index();
            $table->boolean('is_active')->default(true)->comment('1=在职 0=离职');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
