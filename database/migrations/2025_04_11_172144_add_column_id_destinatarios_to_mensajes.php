<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnIdDestinatariosToMensajes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mensajes', function (Blueprint $table) {
            $table->text('nombre_destinatarios')->nullable()->default(null)->after('destinatarios')->comment('nombre de los destinatarios separados por ;');
            $table->text('id_destinatarios')->nullable()->default(null)->after('nombre_destinatarios')->comment('ids de los destinatarios separados por coma');
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
            $table->dropColumn('nombre_destinatarios');
            $table->dropColumn('id_destinatarios');
        });
    }
}
