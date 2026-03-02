<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnFirmaRegistradaToProfileDoctors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('profile_doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('profile_doctors', 'firma_registrada')) {
                $table->boolean('firma_registrada')->nullable()->default(false)->after('especialidad');
            }
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
            $table->dropColumn('firma_registrada');
        });
    }
}
