<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Rate card: price for each additional 500 g (first 500 g stays in prepaid_price). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('zone_price_Settings') && !Schema::hasColumn('zone_price_Settings', 'additional_price')) {
            Schema::table('zone_price_Settings', function (Blueprint $t) {
                $t->decimal('additional_price', 10, 2)->nullable()->after('prepaid_price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('zone_price_Settings', 'additional_price')) {
            Schema::table('zone_price_Settings', function (Blueprint $t) {
                $t->dropColumn('additional_price');
            });
        }
    }
};
