<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\ProfileDoctor;

class ProfileSecretary extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'apellido',
        'nombre',
        'tipoDoc',
        'nroDoc',
        'sexo',
        'email',
    ];

    // protected $with = ['ProfileDoctorProfileSecretary'];

    public function doctors() {
        return $this->belongsToMany(ProfileDoctor::class);
    }
}
