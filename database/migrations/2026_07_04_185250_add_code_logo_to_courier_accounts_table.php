<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_accounts', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');
            $table->string('logo')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('courier_accounts', function (Blueprint $table) {
            $table->dropColumn(['code', 'logo']);
        });
    }
};
