<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShortcutsTable extends Migration
{
    public function up()
    {
        Schema::connection('pgsql-authentication')->create('shortcuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->string('image');
            $table->unique(['user_id', 'role_id', 'permission_id']);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::connection('pgsql-authentication')->dropIfExists('shortcuts');
    }
}
