<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->string('scope', 40)->default('full')->after('inspect_date')
                  ->comment('巡检范围：full=全站，或某章节名(如 首页)');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn('scope');
        });
    }
};
