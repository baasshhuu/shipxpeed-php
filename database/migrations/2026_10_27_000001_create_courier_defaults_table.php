<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Default ON/OFF per courier for all sellers (Admin > Courier Status, "All Sellers (default)"). */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('courier_defaults')) {
            Schema::create('courier_defaults', function (Blueprint $t) {
                $t->id();
                $t->string('courier_key', 120)->unique();
                $t->boolean('is_default')->default(true);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_defaults');
    }
};
