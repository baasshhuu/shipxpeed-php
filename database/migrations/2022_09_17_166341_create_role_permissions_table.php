<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('role_id')->index();
            $table->bigInteger('module_id');
            $table->tinyInteger('can_view');
            $table->tinyInteger('can_add');
            $table->tinyInteger('can_edit');
            $table->tinyInteger('can_delete');
            $table->tinyInteger('allow_all');
        });
    }

    public function down()
    {
        Schema::dropIfExists('role_permissions');
    }
};
