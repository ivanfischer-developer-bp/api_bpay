<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSolicitudSoportesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('solicitud_soportes', function (Blueprint $table) {
            $table->id();
            $table->string('fecha')->default(date('Y-m-d H:i:s'));
            $table->string('asunto');
            $table->text('mensaje');
            $table->string('adjunto')->nullable()->default(null);
            $table->unsignedBigInteger('user_id');  // id del usuario que solicita el soporte        
            $table->foreign('user_id')->references('id')->on('users');
            $table->integer('id_usuario_sqlserver')->nullable()->default(null);  // id del usuario sqlserver que solicita el soporte
            $table->string('nombre_usuario')->nullable()->default(null);
            $table->string('email_usuario')->nullable()->default(null);
            $table->string('ambiente')->default(env('AMBIENTE', 'local'));
            $table->string('estado')->default('pendiente');
            $table->string('tipo')->default('soporte');
            $table->string('leido')->nullable()->default(null); // cuando es leído se coloca una fecha
            $table->string('observaciones')->nullable()->default(null); // cuando es leído se coloca una fecha
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
        Schema::dropIfExists('solicitud_soportes');
    }
}
