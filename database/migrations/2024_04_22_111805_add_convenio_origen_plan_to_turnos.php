<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConvenioOrigenPlanToTurnos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->integer('id_convenio')->nullable()->default(null)->after('observaciones');
            $table->string('nombre_convenio')->nullable()->default(null)->after('id_convenio');
            $table->integer('id_origen')->nullable()->default(null)->after('nombre_convenio');
            $table->string('nombre_origen')->nullable()->default(null)->after('id_origen');
            $table->integer('id_plan')->nullable()->default(null)->after('nombre_origen');
            $table->string('nombre_plan')->nullable()->default(null)->after('id_plan');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn('id_convenio');
            $table->dropColumn('nombre_convenio');
            $table->dropColumn('id_origen');
            $table->dropColumn('nombre_plan');
            $table->dropColumn('id_origen');
            $table->dropColumn('nombre_plan');
        });
    }
}
