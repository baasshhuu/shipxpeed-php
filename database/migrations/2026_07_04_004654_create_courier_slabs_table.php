<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_slabs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('courier_account_id');
            $table->string('name');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->foreign('courier_account_id')->references('id')->on('courier_accounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_slabs');
    }
};
