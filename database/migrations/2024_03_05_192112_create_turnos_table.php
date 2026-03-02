<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTurnosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_medico');            
            $table->foreign('id_medico')->references('id')->on('profile_doctors');
            $table->unsignedBigInteger('id_secretaria');            
            $table->foreign('id_secretaria')->references('id')->on('profile_secretaries');
            $table->unsignedBigInteger('id_afiliado');
            $table->string('title');
            $table->datetime('date')->nullable()->default(null);
            $table->datetime('start');
            $table->datetime('end')->nullable()->default(null);
            $table->string('centro')->nullable()->default(null);
            $table->string('consultorio')->nullable()->default(null);
            $table->string('nombre_afiliado')->nullable()->default(null);
            $table->string('numero_afiliado')->nullable()->default(null);
            $table->string('nombre_medico')->nullable()->default(null);
            $table->string('estado')->nullable()->default(null);
            $table->string('observaciones')->nullable()->default(null);
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
        Schema::dropIfExists('turnos');
    }
}
