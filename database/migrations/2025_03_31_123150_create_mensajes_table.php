<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMensajesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->datetime('fecha_envio');
            $table->string('remitente')->comment('nombre del usuario remitente o sistema'); // nombre del remitente o 'sistema'
            $table->unsignedBigInteger('user_id')->comment('id del usuario que emitio el mensaje');  // id del usuario que emitio el mensaje          
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('asunto')->nullable()->default(null); 
            $table->text('texto')->nullable()->default(null);
            $table->text('rich_text')->nullable()->default(null)->comment('para almacenar texto con formato en un futuro');  // para almacenar texto con formato en un futuro.
            $table->string('tipo')->nullable()->default(null)->comment('Notificación, Comunicación, Recordatorio, Alerta, Requerimiento, Consulta, Reclamo, Sugerencia'); // Notificación, Comunicación, Recordatorio, Alerta, Requerimiento, Consulta, Reclamo, Sugerencia
            $table->string('urgencia')->nullable()->default(null)->comment('urgente o normal'); // urgente, normal
            $table->string('prioridad')->nullable()->default(null)->comment('prioritario o null'); // prioritario, 
            $table->string('importancia')->nullable()->default(null)->comment('alta, media, baja'); // alta, media, baja
            $table->string('router_link')->nullable()->default(null)->comment('una ruta dentro del front ejemplo: "/config/profile"'); // una ruta dentro del front
            $table->string('query_params')->nullable()->default(null)->comment('parametros de la ruta. ej: {"origen": "alfabeta-consultar"}'); // parametros de la ruta. ej: {origen: 'alfabeta-consultar'}
            $table->string('adjunto')->nullable()->default(null)->comment('nombre del archivo adjunto'); // nombre del archivo adjunto
            $table->string('archivos')->nullable()->default(null)->comment('nombre de archivos separados por coma'); // nombre de archivos separados por coma
            $table->binary('imagen')->nullable()->default(null)->comment('imagen con formato blob'); // imagen con formato blob
            $table->string('video')->nullable()->default(null)->comment('nombre de un archivo de video'); // nombre de un archivo de video
            $table->text('animacion')->nullable()->default(null)->comment('reservado para ejecutar comandos de animaciones o algo'); // reservado para ejecutar comandos de animaciones o algo
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
        Schema::dropIfExists('mensajes');
    }
}
