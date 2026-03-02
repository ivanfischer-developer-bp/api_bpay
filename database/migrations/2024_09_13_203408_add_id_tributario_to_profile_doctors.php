<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdTributarioToProfileDoctors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('profile_doctors', function (Blueprint $table) {
            $table->string('idTributario')->nullable()->default(null)->after('cuit');
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
            $table->dropColumn('idTributario');
        });
    }
}
