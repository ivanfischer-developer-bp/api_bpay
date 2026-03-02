<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfileSecretaries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profile_secretaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');            
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('apellido')->nullable()->default(null);
            $table->string('nombre')->nullable()->default(null);
            $table->string('tipoDoc')->nullable()->default(null);
            $table->string('nroDoc')->nullable()->default(null);
            $table->string('sexo')->nullable()->default(null);
            $table->string('email')->nullable()->default(null);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profile_secretaries');
    }
}
