<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Every API v1 call (seller, endpoint, status, timing, trimmed request/response) for support and debugging. */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('api_v1_logs')) {
            return;
        }
        Schema::create('api_v1_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('seller_id')->index();
            $t->string('method', 8);
            $t->string('path', 190)->index();
            $t->unsignedSmallInteger('status')->nullable()->index();
            $t->unsignedInteger('duration_ms')->nullable();
            $t->string('ip', 45)->nullable();
            $t->text('request')->nullable();
            $t->text('response')->nullable();
            $t->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_v1_logs');
    }
};
