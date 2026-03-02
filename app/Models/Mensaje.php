<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\User;

class Mensaje extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'adjunto',
        'animacion',
        'archivos',
        'asunto',
        'destinatarios',
        'fecha_envio',
        'id_destinatarios',
        'imagen',
        'importancia',
        'nombre_destinatarios',
        'prioridad',
        'query_params',
        'remitente',
        'rich_text',
        'router_link',
        'texto_enlace',
        'texto',
        'tipo',
        'urgencia',
        'user_id',
        'video',
    ];

    /**
     * The users that belong to the message.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('leido', 'ejecutado', 'mostrado')->withTimestamps();
    }

}
