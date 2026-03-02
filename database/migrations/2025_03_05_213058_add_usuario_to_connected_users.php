<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUsuarioToConnectedUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('connected_users', function (Blueprint $table) {
            $table->string('usuario')->nullable()->after('id_usuario_sqlserver')->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('connected_users', function (Blueprint $table) {
            $table->dropColumn('usuario');
        });
    }
}
