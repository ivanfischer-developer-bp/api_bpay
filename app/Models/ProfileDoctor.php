<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\ProfileSecretary;
use App\Models\Turno;

class ProfileDoctor extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // 'id',
        'user_id',
        'apellido',
        'nombre',
        'tratamiento',
        'tipoDoc',
        'nroDoc',
        'especialidad',
        'sexo',
        'fechaNacimiento',
        'email',
        'telefono',
        'pais',
        'firmalink',
        'matricula_tipo',
        'matricula_numero',
        'matricula_provincia',
        'cuit',
        'id_convenio',
        'idTributario',
        'horario',
        'diasAtencion',
        'datosContacto',
        'nombreConsultorio',
        'direccionConsultorio',
        'informacionAdicional',
        'ambiente_recipe',
        'firma_registrada',
        'idRefeps'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'firma_registrada' => 'integer',
    ];

    // protected $with = ['ProfileDoctorProfileSecretary'];

    public function secretarias() {
        return $this->belongsToMany(ProfileSecretary::class);
    }

    public function turnos() {
        return $this->hasMany(Turno::class);
    }

    public function users(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
