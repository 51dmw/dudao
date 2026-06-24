<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->string('page_type', 20)->nullable()->after('type')
                  ->comment('页面类型 home/category/article/topic/tag/search/other');
            $table->string('device', 12)->default('all')->after('page_type')
                  ->comment('终端设备 all/pc/mobile/tablet');
            $table->text('fix_suggestion')->nullable()->after('description')
                  ->comment('整改建议');
            $table->string('recheck_result', 12)->default('pending')->after('repeat_count')
                  ->comment('复检结果 pending/pass/fail');
            $table->string('remark', 255)->nullable()->after('recheck_result')
                  ->comment('备注');
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn(['page_type', 'device', 'fix_suggestion', 'recheck_result']);
        });
    }
};
