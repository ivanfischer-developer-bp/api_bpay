<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnAmbienteRecipeToProfileDoctors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('profile_doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('profile_doctors', 'ambiente_recipe')) {
                $table->string('ambiente_recipe', 20)->nullable()->default(null);
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
            $table->dropColumn('ambiente_recipe');
        });
    }
}
