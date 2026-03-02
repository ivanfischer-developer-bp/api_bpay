<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfileDoctorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profile_doctors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');            
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('apellido');
            $table->string('nombre');
            $table->string('tipoDoc');
            $table->string('nroDoc');
            $table->string('especialidad');
            $table->string('sexo');
            $table->string('fechaNacimiento');
            $table->string('email');
            $table->string('telefono');
            $table->string('pais')->default('AR');
            $table->string('firmalink')->nullable()->default(null);
            $table->string('matricula_tipo');
            $table->string('matricula_numero');
            $table->string('matricula_provincia');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profile_doctors');
    }
}

