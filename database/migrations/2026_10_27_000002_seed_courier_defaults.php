<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Starting defaults chosen by the owner on 27 Oct 2026.
 * Insert-only: anything already set on the Courier Status page is left as is.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('courier_defaults')) {
            return;
        }

        // Same key format as the Shipclues adapter: "Shipclues_" + cleaned courier name.
        $key = function (string $name) {
            if (class_exists(\App\Support\CourierBrand::class) && method_exists(\App\Support\CourierBrand::class, 'cleanName')) {
                $name = \App\Support\CourierBrand::cleanName($name);
            }
            return 'Shipclues_' . trim(preg_replace('/[^A-Za-z0-9]+/', '_', $name), '_');
        };

        $defaults = [
            'Delhivery'                        => 1,
            'Delhivery_Air'                    => 1,
            $key('Bluedart Surface-Prime')     => 1,
            $key('XpressBees500')              => 1,
            $key('Ekart Prime 1kg')            => 1,
            $key('Delhivery Prime 500g')       => 0,
            $key('Delhivery Premium 0.25')     => 0,
        ];

        $now = now();
        foreach ($defaults as $courier => $on) {
            DB::table('courier_defaults')->insertOrIgnore([
                'courier_key' => $courier,
                'is_default'  => $on,
                'is_removed'  => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    public function down(): void
    {
    }
};
