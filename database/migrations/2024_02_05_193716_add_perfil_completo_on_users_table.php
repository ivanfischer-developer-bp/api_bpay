<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerfilCompletoOnUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('perfil_completo')->nullable()->after('usuario')->default(false);
            $table->string('apellido')->nullable()->after('perfil_completo')->default(null);
            $table->string('nombre')->nullable()->after('apellido')->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('perfil_completo');
            $table->dropColumn('apellido');
            $table->dropColumn('nombre');
        });
    }
}
