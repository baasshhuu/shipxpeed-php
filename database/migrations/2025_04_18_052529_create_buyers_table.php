<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('buyers', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 50)->unique();
            $table->string('name', 100);
            $table->string('phone', 15);
            $table->string('alt_phone', 15)->nullable();
            $table->string('email', 100);
            $table->string('gstin', 15)->nullable();
            $table->string('address_line1', 255);
            $table->string('address_line2', 255)->nullable();
            $table->string('pincode', 10);
            $table->string('city', 50);
            $table->string('state', 50);
            $table->string('country', 50);
            $table->tinyInteger('is_billing_same')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buyers');
    }
};
