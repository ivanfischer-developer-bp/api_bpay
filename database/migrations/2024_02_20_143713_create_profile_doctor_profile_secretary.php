<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfileDoctorProfileSecretary extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profile_doctor_profile_secretary', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_doctor_id');            
            $table->foreign('profile_doctor_id')->references('id')->on('profile_doctors');
            $table->unsignedBigInteger('profile_secretary_id');            
            $table->foreign('profile_secretary_id')->references('id')->on('profile_secretaries');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profile_doctor_profile_secretary');
    }
}
