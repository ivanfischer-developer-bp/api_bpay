<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolicitudSoporte extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'adjunto',
        'ambiente',
        'asunto',
        'email_usuario',
        'estado',
        'fecha',
        'id_usuario_sqlserver',
        'leido',
        'mensaje',
        'nombre_usuario',
        'observaciones',
        'tipo',
        'user_id',
    ];
}
