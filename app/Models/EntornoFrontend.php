<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntornoFrontend extends Model
{
    protected $connection = 'api_bpay';

    protected $table = 'config_entornos';

    protected $fillable = [
        'ambiente',
        'modelo_formulario_cronicos',
        'imprimir_validaciones_rechazadas',
        'empresa',
        'mostrarPdf',
        'ambiente_recipe',
        'restringir_postdatar',
        'restringir_postdatar_prescripcion',
        'consultar_vademecum',
        'cantidad_prescripciones_por_receta',
        'mostrarSpinnerGlobal',
        'conectar_pusher',
        'mostrar_mensajes_conexion_pusher',
        'ambiente_padron_externo',
        'consultar_padron_externo',
        'mostrar_consultar_padron_externo',
        'actualizar_afiliado_desde_padron_externo',
        'mostrar_boton_buscar_afiliado_en_padron_externo',
        'entorno',
    ];

    protected $casts = [
        'imprimir_validaciones_rechazadas' => 'boolean',
        'conectar_pusher' => 'boolean',
        'mostrar_mensajes_conexion_pusher' => 'boolean',
        'cantidad_prescripciones_por_receta' => 'integer',
    ];
}
