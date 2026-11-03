<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ShipCorrect aggregator integration.
 * Adds the logistic_providers row (code = shipcorrect) so serviceability, booking and
 * cancel resolve "courier.shipcorrect". The courier_api_logs table comes from the
 * Fship migration. Defensive because the live DB has hand-made tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('logistic_providers') || !Schema::hasColumn('logistic_providers', 'code')) {
            return;
        }
        if (DB::table('logistic_providers')->where('code', 'shipcorrect')->exists()) {
            return;
        }
        $row = ['name' => 'ShipCorrect', 'code' => 'shipcorrect', 'status' => 1, 'created_at' => now(), 'updated_at' => now()];
        if (Schema::hasColumn('logistic_providers', 'email')) {
            $row['email'] = 'shipcorrect@shipxpeed.internal';
        }
        if (Schema::hasColumn('logistic_providers', 'password')) {
            $row['password'] = bcrypt(\Illuminate\Support\Str::random(32));
        }
        if (Schema::hasColumn('logistic_providers', 'logo')) {
            $row['logo'] = 'brands/courier-default.svg';
        }
        DB::table('logistic_providers')->insert($row);
    }

    public function down(): void
    {
        if (Schema::hasTable('logistic_providers')) {
            DB::table('logistic_providers')->where('code', 'shipcorrect')->delete();
        }
    }
};
