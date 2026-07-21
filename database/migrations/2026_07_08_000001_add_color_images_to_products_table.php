<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'color_images')) {
            Schema::table('products', function (Blueprint $table) {
                $table->json('color_images')->nullable()->after('colors');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'color_images')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('color_images');
            });
        }
    }
};
