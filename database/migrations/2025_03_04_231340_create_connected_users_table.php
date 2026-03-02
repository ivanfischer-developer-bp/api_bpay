<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConnectedUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('connected_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');            
            $table->foreign('user_id')->references('id')->on('users');
            $table->integer('id_usuario_sqlserver')->nullable();
            $table->string('name')->nullable();
            $table->boolean('conectado')->nullable()->default(false);
            $table->string('inicio_sesion')->nullable();
            $table->string('fin_sesion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('connected_users');
    }
}
