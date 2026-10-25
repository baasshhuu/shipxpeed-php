<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fship aggregator integration.
 * Defensive (hasTable / hasColumn) because the live DB has hand-made tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Fship warehouse ids per seller pickup address
        if (!Schema::hasTable('fship_warehouses')) {
            Schema::create('fship_warehouses', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('seller_id')->index();
                $t->string('address_hash', 20);
                $t->string('environment', 20)->default('production');
                $t->string('warehouse_name')->nullable();
                $t->string('pincode', 10)->nullable();
                $t->unsignedBigInteger('fship_warehouse_id');
                $t->unsignedBigInteger('fship_rto_warehouse_id')->nullable();
                $t->timestamps();
                $t->unique(['seller_id', 'address_hash', 'environment']);
            });
        }

        // Generic courier API log (used by Fship; other adapters can write here too)
        if (!Schema::hasTable('courier_api_logs')) {
            Schema::create('courier_api_logs', function (Blueprint $t) {
                $t->id();
                $t->string('courier', 40)->index();
                $t->string('endpoint', 80);
                $t->string('method', 8)->default('POST');
                $t->longText('request')->nullable();
                $t->longText('response')->nullable();
                $t->unsignedSmallInteger('http_status')->default(0);
                $t->boolean('success')->default(false);
                $t->unsignedInteger('duration_ms')->default(0);
                $t->unsignedBigInteger('order_id')->nullable()->index();
                $t->string('awb', 60)->nullable()->index();
                $t->timestamps();
                $t->index(['courier', 'created_at']);
            });
        }

        // logistic_providers row so serviceability/assign/cancel resolve "courier.fship"
        if (Schema::hasTable('logistic_providers') && Schema::hasColumn('logistic_providers', 'code')
            && !DB::table('logistic_providers')->where('code', 'fship')->exists()) {
            $row = ['name' => 'Fship', 'code' => 'fship', 'status' => 1, 'created_at' => now(), 'updated_at' => now()];
            if (Schema::hasColumn('logistic_providers', 'email')) {
                $row['email'] = 'fship@shipxpeed.internal';
            }
            if (Schema::hasColumn('logistic_providers', 'password')) {
                $row['password'] = bcrypt(\Illuminate\Support\Str::random(32));
            }
            if (Schema::hasColumn('logistic_providers', 'logo')) {
                $row['logo'] = 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg';
            }
            DB::table('logistic_providers')->insert($row);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('logistic_providers')) {
            DB::table('logistic_providers')->where('code', 'fship')->delete();
        }
        Schema::dropIfExists('fship_warehouses');
        Schema::dropIfExists('courier_api_logs');
    }
};
