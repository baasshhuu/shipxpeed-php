<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shipping Label Config (Delhivery-style options) stored as JSON on label_settings.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('label_settings') && !Schema::hasColumn('label_settings', 'config')) {
            Schema::table('label_settings', function (Blueprint $t) {
                $t->text('config')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('label_settings') && Schema::hasColumn('label_settings', 'config')) {
            Schema::table('label_settings', function (Blueprint $t) {
                $t->dropColumn('config');
            });
        }
    }
};
