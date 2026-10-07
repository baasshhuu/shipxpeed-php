<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('courier_id');
            $table->unsignedBigInteger('logistic_provider_id')->nullable();
            $table->string('name');
            $table->enum('courier_type', ['B2B', 'B2C'])->default('B2C');
            $table->enum('mode_type', ['Air', 'Surface'])->default('Surface');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->foreign('courier_id')->references('id')->on('couriers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_accounts');
    }
};
