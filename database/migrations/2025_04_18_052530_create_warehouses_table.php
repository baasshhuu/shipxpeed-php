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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->unsignedBigInteger('seller_id');
            $table->string('address_title', 100);
            $table->string('name', 100);
            $table->string('phone', 15);
            $table->string('alt_phone', 15)->nullable();
            $table->string('email', 100);
            $table->string('pincode', 10);
            $table->string('city', 50);
            $table->string('state', 50);
            $table->string('country', 50);
            $table->string('address_line1', 255);
            $table->string('address_line2', 255)->nullable();
            $table->tinyInteger('is_default')->default(0); // 0 = No, 1 = Yes
            $table->timestamps(); // created_at and updated_at
            $table->softDeletes(); // deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
