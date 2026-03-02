<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMensajeUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mensaje_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');  // id del usuario destinatario del mensaje        
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('mensaje_id');  // id del mensaje          
            $table->foreign('mensaje_id')->references('id')->on('mensajes');
            $table->datetime('leido')->nullable()->default(null);
            $table->datetime('ejecutado')->nullable()->default(null);
            $table->datetime('mostrado')->nullable()->default(null);
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
        Schema::dropIfExists('mensaje_user');
    }
}
