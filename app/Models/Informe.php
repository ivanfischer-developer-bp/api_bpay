<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Informe extends Model
{
    use HasFactory;

   /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_informe',
        'nombre',
        'descripcion',
        'stored_procedure',
        'parametros',
        'retorno',
        'visible_prestador',
        'report',
        'bi',
        'cabecera',
        'nombre_archivo',
        'n_archivo_extension'
    ];
    
}
