<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('check_items', function (Blueprint $table) {
            $table->string('section', 40)->nullable()->after('dimension_id')->comment('章节/页面类型');
            $table->string('module', 60)->nullable()->after('section')->comment('模块');
            $table->string('terminal', 12)->default('双端')->after('module')->comment('终端：双端/PC/移动端');
        });
    }

    public function down(): void
    {
        Schema::table('check_items', function (Blueprint $table) {
            $table->dropColumn(['section', 'module', 'terminal']);
        });
    }
};
