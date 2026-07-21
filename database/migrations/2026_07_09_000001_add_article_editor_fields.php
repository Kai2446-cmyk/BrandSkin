<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (! Schema::hasColumn('articles', 'hero_caption')) {
                $table->string('hero_caption', 500)->nullable()->after('image');
            }

            if (! Schema::hasColumn('articles', 'read_count')) {
                $table->unsignedInteger('read_count')->default(0)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (Schema::hasColumn('articles', 'hero_caption')) {
                $table->dropColumn('hero_caption');
            }
        });
    }
};
