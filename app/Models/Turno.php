<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\ProfileDoctor;

class Turno extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'centro',
        'consultorio',
        'date',
        'end',
        'estado',
        'id_afiliado',
        'id_convenio',
        'id_medico',
        'id_origen',
        'id_plan',
        'id_secretaria',
        'nombre_afiliado',
        'nombre_convenio',
        'nombre_medico',
        'nombre_origen',
        'nombre_plan',
        'numero_afiliado',
        'observaciones',
        'slot_duration',
        'slot_duration_desc',
        'start',
        'title',
    ];

        /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'datetime:Y-m-d\TH:i:s',
        'start' => 'datetime:Y-m-d\TH:i:s',
        'end' => 'datetime:Y-m-d\TH:i:s',
    ];  
    
    public function doctors() {
        return $this->belongsTo(ProfileDoctor::class);
    }
}
