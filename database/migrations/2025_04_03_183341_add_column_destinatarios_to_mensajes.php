<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnDestinatariosToMensajes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mensajes', function (Blueprint $table) {
            $table->string('destinatarios')->nullable()->default(null)->after('remitente');
            $table->string('texto_enlace')->nullable()->default(null)->after('query_params');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mensajes', function (Blueprint $table) {
            $table->dropColumn('destinatarios');
            $table->dropColumn('texto_enlace');
        });
    }
}
