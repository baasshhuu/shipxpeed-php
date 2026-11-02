<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Links a reverse (return) order to the delivered forward order it was created from. */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('xp_returns')) {
            Schema::create('xp_returns', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('seller_id')->index();
                $t->unsignedBigInteger('return_order_id')->unique();
                $t->unsignedBigInteger('original_order_id')->index();
                $t->string('reason', 120)->nullable();
                $t->string('assign_status', 30)->nullable();   // auto_assigned | manual | failed
                $t->string('assign_message', 500)->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_returns');
    }
};
