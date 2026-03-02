<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTratamientoToProfileDoctors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('profile_doctors', function (Blueprint $table) {
            $table->string('tratamiento')->nullable()->default(null)->after('nombre');
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
            $table->dropColumn('tratamiento');
        });
    }
}
