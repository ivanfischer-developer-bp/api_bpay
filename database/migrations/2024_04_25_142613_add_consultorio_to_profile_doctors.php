<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConsultorioToProfileDoctors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('profile_doctors', function (Blueprint $table) {
            $table->string('cuit')->nullable()->default(null)->after('matricula_provincia');
            $table->string('horario')->nullable()->default(null)->after('cuit');
            $table->string('diasAtencion')->nullable()->default(null)->after('horario');
            $table->string('datosContacto')->nullable()->default(null)->after('diasAtencion');
            $table->string('nombreConsultorio')->nullable()->default(null)->after('datosContacto');
            $table->string('direccionConsultorio')->nullable()->default(null)->after('nombreConsultorio');
            $table->string('informacionAdicional')->nullable()->default(null)->after('direccionConsultorio');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('profile_doctors', function (Blueprint $table) {
            $table->dropColumn('cuit');
            $table->dropColumn('horario');
            $table->dropColumn('diasAtencion');
            $table->dropColumn('datosContacto');
            $table->dropColumn('nombreConsultorio');
            $table->dropColumn('direccionConsultorio');
            $table->dropColumn('informacionAdicional');
        });
    }
}
