<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('permission_modules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->integer('module_id')->unique();
            $table->tinyInteger('can_add');
            $table->tinyInteger('can_edit');
            $table->tinyInteger('can_delete');
            $table->tinyInteger('can_view');
            $table->tinyInteger('allow_all');
        });
    }

    public function down()
    {
        Schema::dropIfExists('permission_modules');
    }
};
