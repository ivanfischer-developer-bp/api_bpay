<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Pivot;

use App\Models\ProfileSecretary;
use App\Models\ProfileDoctor;

class ProfileDoctorProfileSecretary extends Pivot
{

}