<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForcedToConnectedUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('connected_users', function (Blueprint $table) {
            $table->boolean('forzado')->nullable()->after('fin_sesion')->default(false);
            $table->string('ambiente')->nullable()->after('forzado')->default(null);
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
            $table->dropColumn('forzado');
            $table->dropColumn('ambiente');
        });
    }
}
