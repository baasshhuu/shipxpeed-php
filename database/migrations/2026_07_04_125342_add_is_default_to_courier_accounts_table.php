<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_accounts', function (Blueprint $table) {
            $table->boolean('is_default')->default(0)->after('mode_type');
        });
    }

    public function down(): void
    {
        Schema::table('courier_accounts', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
