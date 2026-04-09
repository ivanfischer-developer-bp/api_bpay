<?php

namespace App\Http\Controllers\Internos\Validaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;
use Illuminate\Support\Facades\Log;

// use App\Services\PusherService;
use GuzzleHttp\Exception\GuzzleException;
use Pusher\ApiErrorException;
use Pusher\Pusher;
use Pusher\PusherException;

use App\Models\User;
use App\Http\Controllers\TachController;

use setasign\Fpdi\Fpdi;
use Carbon\Carbon;
use Storage;


use App\Http\Controllers\ConexionSpController;

class ValidacionController extends ConexionSpController
{

    /**
     * Emite una validacion
     */
    public function emitir_validacion(Request $request)
    {
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/validaciones/emitir-validacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = null;
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $params_sp = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            $permiso_requerido = 'emitir validacion';
            if($user->hasPermissionTo($permiso_requerido)){
                
                $validacion = request('validacion');
                $esPreautorizacion = request('esPreautorizacion', false);
                $token_tach = request('token_tach', null);

                $dias_vencimiento = 60;
                $control = 0;
                $usuario = $user->usuario;
            
                $vencimiento = (!empty($validacion['fecha_vto']) && strtotime($validacion['fecha_vto'])) ? Carbon::parse($validacion['fecha_vto'])->format('Ymd') : null;
                $fecha_prescripcion = (!empty($validacion['fechaPrescripcion']) && strtotime($validacion['fechaPrescripcion'])) ? Carbon::parse($validacion['fechaPrescripcion'])->format('Ymd') : null;
                $fecha_realizacion = (!empty($validacion['fechaRealizacion']) && strtotime($validacion['fechaRealizacion'])) ? Carbon::parse($validacion['fechaRealizacion'])->format('Ymd') : null;
            
                // se usa internacionNueva ya que el parámetro internacion viene en 1 o 0
                $diagnostico = ($validacion['tipo'] == 'internacion' 
                    ? ((!empty($validacion['internacionNueva']) 
                        && !empty($validacion['internacionNueva']['diagnosticoIngreso']) 
                        && !empty($validacion['internacionNueva']['diagnosticoIngreso']['id_cie_item'])) 
                        ? $validacion['internacionNueva']['diagnosticoIngreso']['id_cie_item'] 
                        : '') 
                    : $validacion['diagnostico']['cod_cie_item']);
                $id_tipo_accion = 0;

                // setea el parámetro validacion['internacion'] según corresponda
                if($validacion['internacionNueva'] != null || $validacion['internacionEnCurso'] != null)
                {
                    if($validacion['internacionEnCurso'] != null)
                    {
                        $validacion['internacion'] = $validacion['internacionEnCurso'];
                        $diagnostico = $validacion['internacionEnCurso']['codigo_diagnostico'];
                    }
                    if($validacion['internacionNueva'] != null)
                    {
                        $validacion['internacion'] = $validacion['internacionNueva'];
                    }
                }else{
                    $validacion['internacion'] = null;
                }

                //  si es internacion, prorroga, toma el diagnostico de la prorroga
                if (isset($validacion['internacion']) 
                    && isset($validacion['internacion']['tipo_accion']) 
                    && $validacion['internacion']['tipo_accion'] == 'prorroga' 
                    && isset($validacion['internacion']['diagnosticoProrroga']) 
                    && isset($validacion['internacion']['diagnosticoProrroga']['cod_cie_item']))
                {
                    $diagnostico = $validacion['internacion']['diagnosticoProrroga']['cod_cie_item'];
                }
                //  si es internacion, alta, toma el diagnostico de la prorroga
                if (isset($validacion['internacion']) 
                    && isset($validacion['internacion']['tipo_accion']) 
                    && $validacion['internacion']['tipo_accion'] == 'alta' 
                    && isset($validacion['internacion']['diagnosticoAlta']) 
                    && isset($validacion['internacion']['diagnosticoAlta']['cod_cie_item']))
                {
                    $diagnostico = $validacion['internacion']['diagnosticoAlta']['cod_cie_item'];
                }
                //  si es internacion
                if (isset($validacion['internacion']) 
                    && isset($validacion['internacion']['tipo_accion']) 
                    && $validacion['internacion']['tipo_accion'] != '') 
                {
                    //  por el tipo de accion configura el id
                    switch ($validacion['internacion']['tipo_accion']) {
                        case 'ingreso':
                            $id_tipo_accion = 1;
                            break;
                        case 'prorroga':
                            $id_tipo_accion = 2;
                            break;
                        case 'practica':
                            $id_tipo_accion = 3;
                            break;
                        case 'prestacion':
                            $id_tipo_accion = 3;
                            break;
                        case 'alta':
                            $id_tipo_accion = 4;
                            break;
                        case 'egreso':
                            $id_tipo_accion = 4;
                            break;
                        case 'renovacion':
                            $id_tipo_accion = 5;
                            break;
                    }
                }
                $ambulatorio = $validacion['tipo'] == 'ambulatoria' ? 1 : 0;
                $guardia = $validacion['tipo'] == 'guardia' ? 1 : 0;
                $internacion = $validacion['tipo'] == 'internacion' ? 1 : 0;
                $medicamento = $validacion['tipo'] == 'medicamento' ? 1 : 0;
                if($validacion['tipo'] == 'medicamento'){
                    $ambulatorio = 1;
                }
                $tiene_pic = 0;
                if(isset($validacion['generarPIC'])){
                    if($validacion['generarPIC']){
                        $tiene_pic = 1;
                    }else{
                        $tiene_pic = 0;
                    }
                }
                
                if($user->es_prestador() == 1){
                    array_push($extras['sps'], ['sp_usuario_select' => ['p_id_usuario' => $user->id_usuario_sqlserver]]);
                    array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_select', ['p_id_usuario' => $user->id_usuario_sqlserver]));
                    $usuario_sqlserver = $this->ejecutar_sp_directo('admin', 'sp_usuario_select', ['p_id_usuario' => $user->id_usuario_sqlserver]);
                    array_push($extras['responses'], ['sp_usuario_select' => $usuario_sqlserver]);
                    $sucursal = $usuario_sqlserver[0]->id_sucursal;
                }else{
                    $sucursal = $validacion['sucursal']['id_sucursal'];
                }
                $tiene_insumos = isset($validacion['tiene_insumos']) ? $validacion['tiene_insumos'] : 0;
                $observaciones = empty($validacion['observaciones']) ? NULL : $validacion['observaciones'];
                $adjunto = empty($validacion['adjunto']) ? NULL : $validacion['adjunto'];

                $params = [
                    'validacion' => $validacion
                ];

                $fecha_ingreso = null;
                if(isset($validacion['internacion']) && isset($validacion['internacion']['tipo_accion'])
                    && ($validacion['internacion']['tipo_accion'] == 'ingreso' 
                        || $validacion['internacion']['tipo_accion'] == 'prorroga' 
                        || $validacion['internacion']['tipo_accion'] == 'renovacion'
                        || $validacion['internacion']['tipo_accion'] == 'alta'
                        || $validacion['internacion']['tipo_accion'] == 'egreso')
                    && !empty($validacion['internacion']['fecha_ingreso']) 
                    && strtotime($validacion['internacion']['fecha_ingreso'])
                ){
                    $fecha_ingreso = Carbon::parse($validacion['internacion']['fecha_ingreso'])->format('Ymd');
                }

                $id_tipo_alta_internacion = ''; // cambiado null por '' a pedido de Pablo 18-09-2025
                if(is_array($validacion['internacion'])){
                    if(isset($validacion['internacion']['tipo_accion']) 
                        && ($validacion['internacion']['tipo_accion'] == 'alta'
                            || $validacion['internacion']['tipo_accion'] == 'egreso' )
                    ){
                        $id_tipo_alta_internacion = $validacion['internacion']['tipo_egreso'];
                    }
                }
        
                $params_sp = [
                    'Sucursal' => $sucursal,
                    'Empresa' => $validacion['afiliado']['id_empresa'],
                    'Afiliado' => $validacion['afiliado']['n_afiliado'],
                    'Diagnostico' => $diagnostico,
                    'ambulatorio' => $ambulatorio,
                    'guardia' => $guardia,
                    'internacion' => $internacion,
                    'medicamento' => $medicamento,
                    'Prestador_Prescriptor' => isset($validacion['prescriptor']['id_prestadores']) ? $validacion['prescriptor']['id_prestadores'] : $validacion['prescriptor']['id_prestador'],
                    'Prestador' => isset($validacion['efector']['id_prestadores']) ? $validacion['efector']['id_prestadores'] : $validacion['efector']['id_prestador'],
                    'Convenio' => $validacion['centro']['id_convenio'],
                    'Comentarios' => ((isset($validacion['observaciones']) && isset($validacion['observaciones']['validacion']) && $validacion['observaciones']['validacion'] != '') ? $validacion['observaciones']['validacion'] : ''), // cambiado null por '' a pedido de Pablo 18-09-2025
                    'Comentario_Afiliado' => ((isset($validacion['observaciones']) && isset($validacion['observaciones']['afiliado']) && $validacion['observaciones']['afiliado'] != '') ? $validacion['observaciones']['afiliado'] : ''), // cambiado null por '' a pedido de Pablo 18-09-2025
                    'Comentario_Efector' => ((isset($validacion['observaciones']) && isset($validacion['observaciones']['efector']) && $validacion['observaciones']['efector'] != '') ? $validacion['observaciones']['efector'] : ''), // cambiado null por '' a pedido de Pablo 18-09-2025
                    'Fecha_Vencimiento' => $vencimiento,
                    'Control' => $control,
                    'usuario' => $usuario,
                    'p_rechazo' => isset($validacion['rechazo']) ? $validacion['rechazo'] : null,
                    'p_motivo' => isset($validacion['motivo']) ? $validacion['motivo'] : '', // cambiado null por '' a pedido de Pablo 18-09-2025
                    'audita_nivel' => isset($validacion['audita_nivel']) ? $validacion['audita_nivel'] : '',  // cambiado null por '' a pedido de Pablo 18-09-2025
                    'n_contacto' => isset($validacion['afiliado']['n_contacto']) ? $validacion['afiliado']['n_contacto'] : null,
                    'id_contacto' => isset($validacion['afiliado']['id_contacto']) ? $validacion['afiliado']['id_contacto'] : null,
                    'n_domicilio' => isset($validacion['afiliado']['n_domicilio']) ? $validacion['afiliado']['n_domicilio'] : null,
                    'id_domicilio' => isset($validacion['afiliado']['id_domicilio']) ? $validacion['afiliado']['id_domicilio'] : null,
                    // token
                    'n_token' => isset($validacion['n_token']) ? $validacion['n_token'] : null,
                    //  internacion
                    'Codigo_Internacion' => ((isset($validacion['internacion']) && isset($validacion['internacion']['codigo_internacion']) && $validacion['internacion']['codigo_internacion'] != '') ? $validacion['internacion']['codigo_internacion'] : ''),
                    'id_tipo_accion' => $id_tipo_accion,
                    //  ingreso
                    'id_tipo_internacion' => ((isset($validacion['internacion']) && isset($validacion['internacion']['id_tipo_internacion']) && $validacion['internacion']['id_tipo_internacion'] != '') ? $validacion['internacion']['id_tipo_internacion'] : 0),
                    'prioridad' => ((isset($validacion['internacion']) && isset($validacion['internacion']['prioridad']) && $validacion['internacion']['prioridad'] != '') ? $validacion['internacion']['prioridad'] : NULL),
                    'pediatrica' => ((isset($validacion['internacion']) && isset($validacion['internacion']['esPediatrica']) && $validacion['internacion']['esPediatrica'] == 1) ? 1 : 0),
                    // 'fecha_ingreso' => ((isset($validacion['internacion']) && isset($validacion['internacion']['tipo_accion']) && ($validacion['internacion']['tipo_accion'] == 'ingreso' || $validacion['internacion']['tipo_accion'] == 'prorroga')) ? ($validacion['internacion']['fecha_ingreso'] != 'Invalid date' ? $validacion['internacion']['fecha_ingreso'] : null) : null),
                    // 'fecha_ingreso' => ((isset($validacion['internacion']) && isset($validacion['internacion']['tipo_accion']) && ($validacion['internacion']['tipo_accion'] == 'ingreso' || $validacion['internacion']['tipo_accion'] == 'prorroga')) ? $validacion['internacion']['fecha_ingreso'] : null),
                    'fecha_ingreso' => $fecha_ingreso,
                    //  egreso
                    'id_tipo_alta_internacion' => $id_tipo_alta_internacion, 
                    'codigo_diagnostico_egreso' => ((isset($validacion['internacion']) && isset($validacion['internacion']['tipo_accion']) && $validacion['internacion']['tipo_accion'] == 'alta') ? $diagnostico : ''), // cambiado null por '' a pedido de Pablo 18-09-2025
                    // estas dos son el problema, no las toma cuando es prorroga
                    'fpe' => (
                        isset($validacion['internacion']) 
                        && isset($validacion['internacion']['tipo_accion'])
                        && ($validacion['internacion']['tipo_accion'] == 'ingreso' 
                            || $validacion['internacion']['tipo_accion'] == 'prorroga'
                            || $validacion['internacion']['tipo_accion'] == 'alta')
                        && !empty($validacion['internacion']['fecha_probable_egreso']) 
                        && strtotime($validacion['internacion']['fecha_probable_egreso'])
                    ) ? Carbon::parse($validacion['internacion']['fecha_probable_egreso'])->format('Ymd') : null,
                    'fecha_egreso' => (
                        isset($validacion['internacion']) && isset($validacion['internacion']['tipo_accion']) &&
                        ($validacion['internacion']['tipo_accion'] == 'alta' || $validacion['internacion']['tipo_accion'] == 'prorroga' || $validacion['internacion']['tipo_accion'] == 'renovacion') &&
                        !empty($validacion['internacion']['fecha_egreso']) && strtotime($validacion['internacion']['fecha_egreso'])
                    ) ? Carbon::parse($validacion['internacion']['fecha_egreso'])->format('Ymd') : '', // cambiado null por '' a pedido de Pablo 18-09-2025
                    //  prestaciones y cantidades
                    'Cantidad1'  => (!isset($validacion['prestaciones'][0])  ? 0 : $validacion['prestaciones'][0]['cantidad']),
                    'Practica1'  => (!isset($validacion['prestaciones'][0])  ? 0 : ($validacion['prestaciones'][0]['codigo_nomenclador'])),
                    'Frecuencia1'=> (!isset($validacion['prestaciones'][0])  ? null : (isset($validacion['prestaciones'][0]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][0]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad1'=> (!isset($validacion['prestaciones'][0])  ? null : (isset($validacion['prestaciones'][0]['frecuencia']['cantidad']) ? $validacion['prestaciones'][0]['frecuencia']['cantidad'] : null)),
                    'Finaliza1'=> (!isset($validacion['prestaciones'][0])  ? null : (isset($validacion['prestaciones'][0]['finaliza']) ? $validacion['prestaciones'][0]['finaliza'] : null)),
                    'Retiro1'=> (!isset($validacion['prestaciones'][0])  ? null : (isset($validacion['prestaciones'][0]['retiro']) ? $validacion['prestaciones'][0]['retiro'] : null)),
                                
                    'Cantidad2'  => (!isset($validacion['prestaciones'][1])  ? 0 : $validacion['prestaciones'][1]['cantidad']),
                    'Practica2'  => (!isset($validacion['prestaciones'][1])  ? 0 : ($validacion['prestaciones'][1]['codigo_nomenclador'])), //sacar el round para las prestaciones
                    'Frecuencia2'=> (!isset($validacion['prestaciones'][1])  ? null : (isset($validacion['prestaciones'][1]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][1]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad2'=> (!isset($validacion['prestaciones'][1])  ? null : (isset($validacion['prestaciones'][1]['frecuencia']['cantidad']) ? $validacion['prestaciones'][1]['frecuencia']['cantidad'] : null)),
                    'Finaliza2'=> (!isset($validacion['prestaciones'][1])  ? null : (isset($validacion['prestaciones'][1]['finaliza']) ? $validacion['prestaciones'][1]['finaliza'] : null)),
                    'Retiro2'=> (!isset($validacion['prestaciones'][1])  ? null : (isset($validacion['prestaciones'][1]['retiro']) ? $validacion['prestaciones'][1]['retiro'] : null)),
                    
                    'Cantidad3'  => (!isset($validacion['prestaciones'][2])  ? 0 : $validacion['prestaciones'][2]['cantidad']),
                    'Practica3'  => (!isset($validacion['prestaciones'][2])  ? 0 : ($validacion['prestaciones'][2]['codigo_nomenclador'])),
                    'Frecuencia3'=> (!isset($validacion['prestaciones'][2])  ? null : (isset($validacion['prestaciones'][2]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][2]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad3'=> (!isset($validacion['prestaciones'][2])  ? null : (isset($validacion['prestaciones'][2]['frecuencia']['cantidad']) ? $validacion['prestaciones'][2]['frecuencia']['cantidad'] : null)),
                    'Finaliza3'=> (!isset($validacion['prestaciones'][2])  ? null : (isset($validacion['prestaciones'][2]['finaliza']) ? $validacion['prestaciones'][2]['finaliza'] : null)),
                    'Retiro3'=> (!isset($validacion['prestaciones'][2])  ? null : (isset($validacion['prestaciones'][2]['retiro']) ? $validacion['prestaciones'][2]['retiro'] : null)),
                    
                    'Cantidad4'  => (!isset($validacion['prestaciones'][3])  ? 0 : $validacion['prestaciones'][3]['cantidad']),
                    'Practica4'  => (!isset($validacion['prestaciones'][3])  ? 0 : ($validacion['prestaciones'][3]['codigo_nomenclador'])),
                    'Frecuencia4'=> (!isset($validacion['prestaciones'][3])  ? null : (isset($validacion['prestaciones'][3]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][3]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad4'=> (!isset($validacion['prestaciones'][3])  ? null : (isset($validacion['prestaciones'][1]['frecuencia']['cantidad']) ? $validacion['prestaciones'][3]['frecuencia']['cantidad'] : null)),
                    'Finaliza4'=> (!isset($validacion['prestaciones'][3])  ? null : (isset($validacion['prestaciones'][3]['finaliza']) ? $validacion['prestaciones'][3]['finaliza'] : null)),
                    'Retiro4'=> (!isset($validacion['prestaciones'][3])  ? null : (isset($validacion['prestaciones'][3]['retiro']) ? $validacion['prestaciones'][3]['retiro'] : null)),
                    
                    'Cantidad5'  => (!isset($validacion['prestaciones'][4])  ? 0 : $validacion['prestaciones'][4]['cantidad']),
                    'Practica5'  => (!isset($validacion['prestaciones'][4])  ? 0 : ($validacion['prestaciones'][4]['codigo_nomenclador'])),
                    'Frecuencia5'=> (!isset($validacion['prestaciones'][4])  ? null : (isset($validacion['prestaciones'][4]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][4]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad5'=> (!isset($validacion['prestaciones'][4])  ? null : (isset($validacion['prestaciones'][4]['frecuencia']['cantidad']) ? $validacion['prestaciones'][4]['frecuencia']['cantidad'] : null)),
                    'Finaliza5'=> (!isset($validacion['prestaciones'][4])  ? null : (isset($validacion['prestaciones'][4]['finaliza']) ? $validacion['prestaciones'][4]['finaliza'] : null)),
                    'Retiro5'=> (!isset($validacion['prestaciones'][4])  ? null : (isset($validacion['prestaciones'][4]['retiro']) ? $validacion['prestaciones'][4]['retiro'] : null)),
                    
                    'Cantidad6'  => (!isset($validacion['prestaciones'][5])  ? 0 : $validacion['prestaciones'][5]['cantidad']),
                    'Practica6'  => (!isset($validacion['prestaciones'][5])  ? 0 : ($validacion['prestaciones'][5]['codigo_nomenclador'])),
                    'Frecuencia6'=> (!isset($validacion['prestaciones'][5])  ? null : (isset($validacion['prestaciones'][5]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][5]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad6'=> (!isset($validacion['prestaciones'][5])  ? null : (isset($validacion['prestaciones'][5]['frecuencia']['cantidad']) ? $validacion['prestaciones'][5]['frecuencia']['cantidad'] : null)),
                    'Finaliza6'=> (!isset($validacion['prestaciones'][5])  ? null : (isset($validacion['prestaciones'][5]['finaliza']) ? $validacion['prestaciones'][5]['finaliza'] : null)),
                    'Retiro6'=> (!isset($validacion['prestaciones'][5])  ? null : (isset($validacion['prestaciones'][5]['retiro']) ? $validacion['prestaciones'][5]['retiro'] : null)),
                    
                    'Cantidad7'  => (!isset($validacion['prestaciones'][6])  ? 0 : $validacion['prestaciones'][6]['cantidad']),
                    'Practica7'  => (!isset($validacion['prestaciones'][6])  ? 0 : ($validacion['prestaciones'][6]['codigo_nomenclador'])),
                    'Frecuencia7'=> (!isset($validacion['prestaciones'][6])  ? null : (isset($validacion['prestaciones'][6]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][6]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad7'=> (!isset($validacion['prestaciones'][6])  ? null : (isset($validacion['prestaciones'][6]['frecuencia']['cantidad']) ? $validacion['prestaciones'][6]['frecuencia']['cantidad'] : null)),
                    'Finaliza7'=> (!isset($validacion['prestaciones'][6])  ? null : (isset($validacion['prestaciones'][6]['finaliza']) ? $validacion['prestaciones'][6]['finaliza'] : null)),
                    'Retiro7'=> (!isset($validacion['prestaciones'][6])  ? null : (isset($validacion['prestaciones'][6]['retiro']) ? $validacion['prestaciones'][6]['retiro'] : null)),
                    
                    'Cantidad8'  => (!isset($validacion['prestaciones'][7])  ? 0 : $validacion['prestaciones'][7]['cantidad']),
                    'Practica8'  => (!isset($validacion['prestaciones'][7])  ? 0 : ($validacion['prestaciones'][7]['codigo_nomenclador'])),
                    'Frecuencia8'=> (!isset($validacion['prestaciones'][7])  ? null : (isset($validacion['prestaciones'][7]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][7]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad8'=> (!isset($validacion['prestaciones'][7])  ? null : (isset($validacion['prestaciones'][7]['frecuencia']['cantidad']) ? $validacion['prestaciones'][7]['frecuencia']['cantidad'] : null)),
                    'Finaliza8'=> (!isset($validacion['prestaciones'][7])  ? null : (isset($validacion['prestaciones'][7]['finaliza']) ? $validacion['prestaciones'][7]['finaliza'] : null)),
                    'Retiro8'=> (!isset($validacion['prestaciones'][7])  ? null : (isset($validacion['prestaciones'][7]['retiro']) ? $validacion['prestaciones'][7]['retiro'] : null)),
                    
                    'Cantidad9'  => (!isset($validacion['prestaciones'][8])  ? 0 : $validacion['prestaciones'][8]['cantidad']),
                    'Practica9'  => (!isset($validacion['prestaciones'][8])  ? 0 : ($validacion['prestaciones'][8]['codigo_nomenclador'])),
                    'Frecuencia9'=> (!isset($validacion['prestaciones'][8])  ? null : (isset($validacion['prestaciones'][8]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][8]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad9'=> (!isset($validacion['prestaciones'][8])  ? null : (isset($validacion['prestaciones'][8]['frecuencia']['cantidad']) ? $validacion['prestaciones'][8]['frecuencia']['cantidad'] : null)),
                    'Finaliza9'=> (!isset($validacion['prestaciones'][8])  ? null : (isset($validacion['prestaciones'][8]['finaliza']) ? $validacion['prestaciones'][8]['finaliza'] : null)),
                    'Retiro9'=> (!isset($validacion['prestaciones'][8])  ? null : (isset($validacion['prestaciones'][8]['retiro']) ? $validacion['prestaciones'][8]['retiro'] : null)),
                    
                    'Cantidad10' => (!isset($validacion['prestaciones'][9])  ? 0 : $validacion['prestaciones'][9]['cantidad']),
                    'Practica10' => (!isset($validacion['prestaciones'][9])  ? 0 : ($validacion['prestaciones'][9]['codigo_nomenclador'])),
                    'Frecuencia10'=> (!isset($validacion['prestaciones'][9])  ? null : (isset($validacion['prestaciones'][9]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][9]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad10'=> (!isset($validacion['prestaciones'][9])  ? null : (isset($validacion['prestaciones'][9]['frecuencia']['cantidad']) ? $validacion['prestaciones'][9]['frecuencia']['cantidad'] : null)),
                    'Finaliza10'=> (!isset($validacion['prestaciones'][9])  ? null : (isset($validacion['prestaciones'][9]['finaliza']) ? $validacion['prestaciones'][9]['finaliza'] : null)),
                    'Retiro10'=> (!isset($validacion['prestaciones'][9])  ? null : (isset($validacion['prestaciones'][9]['retiro']) ? $validacion['prestaciones'][9]['retiro'] : null)),
                    
                    'Cantidad11' => (!isset($validacion['prestaciones'][10]) ? 0 : $validacion['prestaciones'][10]['cantidad']),
                    'Practica11' => (!isset($validacion['prestaciones'][10]) ? 0 : ($validacion['prestaciones'][10]['codigo_nomenclador'])),
                    'Frecuencia11'=> (!isset($validacion['prestaciones'][10])  ? null : (isset($validacion['prestaciones'][10]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][10]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad11'=> (!isset($validacion['prestaciones'][10])  ? null : (isset($validacion['prestaciones'][10]['frecuencia']['cantidad']) ? $validacion['prestaciones'][10]['frecuencia']['cantidad'] : null)),
                    'Finaliza11'=> (!isset($validacion['prestaciones'][10])  ? null : (isset($validacion['prestaciones'][10]['finaliza']) ? $validacion['prestaciones'][10]['finaliza'] : null)),
                    'Retiro11'=> (!isset($validacion['prestaciones'][10])  ? null : (isset($validacion['prestaciones'][10]['retiro']) ? $validacion['prestaciones'][10]['retiro'] : null)),
                    
                    'Cantidad12' => (!isset($validacion['prestaciones'][11]) ? 0 : $validacion['prestaciones'][11]['cantidad']),
                    'Practica12' => (!isset($validacion['prestaciones'][11]) ? 0 : ($validacion['prestaciones'][11]['codigo_nomenclador'])),
                    'Frecuencia12'=> (!isset($validacion['prestaciones'][11])  ? null : (isset($validacion['prestaciones'][11]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][11]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad12'=> (!isset($validacion['prestaciones'][11])  ? null : (isset($validacion['prestaciones'][11]['frecuencia']['cantidad']) ? $validacion['prestaciones'][11]['frecuencia']['cantidad'] : null)),
                    'Finaliza12'=> (!isset($validacion['prestaciones'][11])  ? null : (isset($validacion['prestaciones'][11]['finaliza']) ? $validacion['prestaciones'][11]['finaliza'] : null)),
                    'Retiro12'=> (!isset($validacion['prestaciones'][11])  ? null : (isset($validacion['prestaciones'][11]['retiro']) ? $validacion['prestaciones'][11]['retiro'] : null)),
                    
                    'Cantidad13' => (!isset($validacion['prestaciones'][12]) ? 0 : $validacion['prestaciones'][12]['cantidad']),
                    'Practica13' => (!isset($validacion['prestaciones'][12]) ? 0 : ($validacion['prestaciones'][12]['codigo_nomenclador'])),
                    'Frecuencia13'=> (!isset($validacion['prestaciones'][12])  ? null : (isset($validacion['prestaciones'][12]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][12]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad13'=> (!isset($validacion['prestaciones'][12])  ? null : (isset($validacion['prestaciones'][12]['frecuencia']['cantidad']) ? $validacion['prestaciones'][12]['frecuencia']['cantidad'] : null)),
                    'Finaliza13'=> (!isset($validacion['prestaciones'][12])  ? null : (isset($validacion['prestaciones'][12]['finaliza']) ? $validacion['prestaciones'][12]['finaliza'] : null)),
                    'Retiro13'=> (!isset($validacion['prestaciones'][12])  ? null : (isset($validacion['prestaciones'][12]['retiro']) ? $validacion['prestaciones'][12]['retiro'] : null)),
                    
                    'Cantidad14' => (!isset($validacion['prestaciones'][13]) ? 0 : $validacion['prestaciones'][13]['cantidad']),
                    'Practica14' => (!isset($validacion['prestaciones'][13]) ? 0 : ($validacion['prestaciones'][13]['codigo_nomenclador'])),
                    'Frecuencia14'=> (!isset($validacion['prestaciones'][13])  ? null : (isset($validacion['prestaciones'][13]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][13]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad14'=> (!isset($validacion['prestaciones'][13])  ? null : (isset($validacion['prestaciones'][13]['frecuencia']['cantidad']) ? $validacion['prestaciones'][13]['frecuencia']['cantidad'] : null)),
                    'Finaliza14'=> (!isset($validacion['prestaciones'][13])  ? null : (isset($validacion['prestaciones'][13]['finaliza']) ? $validacion['prestaciones'][13]['finaliza'] : null)),
                    'Retiro14'=> (!isset($validacion['prestaciones'][13])  ? null : (isset($validacion['prestaciones'][13]['retiro']) ? $validacion['prestaciones'][13]['retiro'] : null)),
                    
                    'Cantidad15' => (!isset($validacion['prestaciones'][14]) ? 0 : $validacion['prestaciones'][14]['cantidad']),
                    'Practica15' => (!isset($validacion['prestaciones'][14]) ? 0 : ($validacion['prestaciones'][14]['codigo_nomenclador'])),
                    'Frecuencia15'=> (!isset($validacion['prestaciones'][14])  ? null : (isset($validacion['prestaciones'][14]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][14]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad15'=> (!isset($validacion['prestaciones'][14])  ? null : (isset($validacion['prestaciones'][14]['frecuencia']['cantidad']) ? $validacion['prestaciones'][14]['frecuencia']['cantidad'] : null)),
                    'Finaliza15'=> (!isset($validacion['prestaciones'][14])  ? null : (isset($validacion['prestaciones'][14]['finaliza']) ? $validacion['prestaciones'][14]['finaliza'] : null)),
                    'Retiro15'=> (!isset($validacion['prestaciones'][14])  ? null : (isset($validacion['prestaciones'][14]['retiro']) ? $validacion['prestaciones'][14]['retiro'] : null)),
                    
                    'Cantidad16' => (!isset($validacion['prestaciones'][15]) ? 0 : $validacion['prestaciones'][15]['cantidad']),
                    'Practica16' => (!isset($validacion['prestaciones'][15]) ? 0 : ($validacion['prestaciones'][15]['codigo_nomenclador'])),
                    'Frecuencia16'=> (!isset($validacion['prestaciones'][15])  ? null : (isset($validacion['prestaciones'][15]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][15]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad16'=> (!isset($validacion['prestaciones'][15])  ? null : (isset($validacion['prestaciones'][15]['frecuencia']['cantidad']) ? $validacion['prestaciones'][15]['frecuencia']['cantidad'] : null)),
                    'Finaliza16'=> (!isset($validacion['prestaciones'][15])  ? null : (isset($validacion['prestaciones'][15]['finaliza']) ? $validacion['prestaciones'][15]['finaliza'] : null)),
                    'Retiro16'=> (!isset($validacion['prestaciones'][15])  ? null : (isset($validacion['prestaciones'][15]['retiro']) ? $validacion['prestaciones'][15]['retiro'] : null)),
                    
                    'Cantidad17' => (!isset($validacion['prestaciones'][16]) ? 0 : $validacion['prestaciones'][16]['cantidad']),
                    'Practica17' => (!isset($validacion['prestaciones'][16]) ? 0 : ($validacion['prestaciones'][16]['codigo_nomenclador'])),
                    'Frecuencia17'=> (!isset($validacion['prestaciones'][16])  ? null : (isset($validacion['prestaciones'][16]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][16]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad17'=> (!isset($validacion['prestaciones'][16])  ? null : (isset($validacion['prestaciones'][16]['frecuencia']['cantidad']) ? $validacion['prestaciones'][16]['frecuencia']['cantidad'] : null)),
                    'Finaliza17'=> (!isset($validacion['prestaciones'][16])  ? null : (isset($validacion['prestaciones'][16]['finaliza']) ? $validacion['prestaciones'][16]['finaliza'] : null)),
                    'Retiro17'=> (!isset($validacion['prestaciones'][16])  ? null : (isset($validacion['prestaciones'][16]['retiro']) ? $validacion['prestaciones'][16]['retiro'] : null)),
                    
                    'Cantidad18' => (!isset($validacion['prestaciones'][17]) ? 0 : $validacion['prestaciones'][17]['cantidad']),
                    'Practica18' => (!isset($validacion['prestaciones'][17]) ? 0 : ($validacion['prestaciones'][17]['codigo_nomenclador'])),
                    'Frecuencia18'=> (!isset($validacion['prestaciones'][17])  ? null : (isset($validacion['prestaciones'][17]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][17]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad18'=> (!isset($validacion['prestaciones'][17])  ? null : (isset($validacion['prestaciones'][17]['frecuencia']['cantidad']) ? $validacion['prestaciones'][17]['frecuencia']['cantidad'] : null)),
                    'Finaliza18'=> (!isset($validacion['prestaciones'][17])  ? null : (isset($validacion['prestaciones'][17]['finaliza']) ? $validacion['prestaciones'][17]['finaliza'] : null)),
                    'Retiro18'=> (!isset($validacion['prestaciones'][17])  ? null : (isset($validacion['prestaciones'][17]['retiro']) ? $validacion['prestaciones'][17]['retiro'] : null)),
                    
                    'Cantidad19' => (!isset($validacion['prestaciones'][18]) ? 0 : $validacion['prestaciones'][18]['cantidad']),
                    'Practica19' => (!isset($validacion['prestaciones'][18]) ? 0 : ($validacion['prestaciones'][18]['codigo_nomenclador'])),
                    'Frecuencia19'=> (!isset($validacion['prestaciones'][18])  ? null : (isset($validacion['prestaciones'][18]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][18]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad19'=> (!isset($validacion['prestaciones'][18])  ? null : (isset($validacion['prestaciones'][18]['frecuencia']['cantidad']) ? $validacion['prestaciones'][18]['frecuencia']['cantidad'] : null)),
                    'Finaliza19'=> (!isset($validacion['prestaciones'][18])  ? null : (isset($validacion['prestaciones'][18]['finaliza']) ? $validacion['prestaciones'][18]['finaliza'] : null)),
                    'Retiro19'=> (!isset($validacion['prestaciones'][18])  ? null : (isset($validacion['prestaciones'][18]['retiro']) ? $validacion['prestaciones'][18]['retiro'] : null)),
                    
                    'Cantidad20' => (!isset($validacion['prestaciones'][19]) ? 0 : $validacion['prestaciones'][19]['cantidad']),
                    'Practica20' => (!isset($validacion['prestaciones'][19]) ? 0 : ($validacion['prestaciones'][19]['codigo_nomenclador'])),
                    'Frecuencia20'=> (!isset($validacion['prestaciones'][19])  ? null : (isset($validacion['prestaciones'][19]['frecuencia']['id_frecuencia']) ? $validacion['prestaciones'][19]['frecuencia']['id_frecuencia'] : null)),
                    'Frecuencia_cantidad20'=> (!isset($validacion['prestaciones'][19])  ? null : (isset($validacion['prestaciones'][19]['frecuencia']['cantidad']) ? $validacion['prestaciones'][19]['frecuencia']['cantidad'] : null)),
                    'Finaliza20'=> (!isset($validacion['prestaciones'][19])  ? null : (isset($validacion['prestaciones'][19]['finaliza']) ? $validacion['prestaciones'][19]['finaliza'] : null)),
                    'Retiro20'=> (!isset($validacion['prestaciones'][19])  ? null : (isset($validacion['prestaciones'][19]['retiro']) ? $validacion['prestaciones'][19]['retiro'] : null)),
                    
                    //  el resto :P
                    'ID_Suplementos'         => '',
                    'Fecha_BajaPlanCronico'  => '',
                    'callcenter'             => 0,
                    'elegibilidad'           => 0,
                    'edicion'                => 0,
                    'codigo_interno_edicion' => '',
                    'fecha_prescripcion' => $fecha_prescripcion,
                    'fecha_realizacion' => $fecha_realizacion,
                    'auditoria_administrativa' => $validacion['aud_admin'],
                    'zona' => $user->es_prestador() ? $user->id_zona : $validacion['zona']['id_zona'],
                    'tiene_pic' => $tiene_pic,
                    'tiene_insumos' => $tiene_insumos
                ];

                $datos = [
                    'n_persona' => $validacion['afiliado']['n_persona'],
                    'id_tipo_doc' => $validacion['afiliado']['id_tipo_doc'],
                    'nro_doc' => $validacion['afiliado']['nro_doc'],
                    'tipo_documento' => $validacion['afiliado']['abrev_tipo_doc'],
                    'token_tach' => $token_tach
                ];
                $slug_origen = 'emitir-validacion';

        // return [
        //     'tipo_egreso' => $validacion['internacion']['tipo_egreso'],
        //     'tipo_accion' => $validacion['internacion']['tipo_accion'],
        //     'validacion' => $validacion,
        //     'datos' => $datos,
        //     'params_sp' => $params_sp,
        //     'observaciones' => $observaciones
        // ];
                try {

                    // Llamar a emitir y extraer solo los datos, sin el response HTTP
                    $respuesta_http = $this->emitir($datos, $params_sp, $observaciones, $adjunto, $slug_origen, FALSE, $logged_user, $this);
                    
                    Log::channel('validaciones')->info('DEBUG: Respuesta de emitir: ' . json_encode($respuesta_http));
                    // Extraer el contenido del response JSON de Laravel
                    if ($respuesta_http instanceof \Illuminate\Http\JsonResponse) {
                        $respuesta = json_decode($respuesta_http->getContent());
                    } else {
                        $respuesta = $respuesta_http;
                    }
                    
                    // Procesar la respuesta extraída
                    $ret = (array) json_decode(json_encode($respuesta));
                    $resp = (object) $ret;
                    $codigo_interno = $resp->data->codigo_interno ?? null;
                    
                    $errors = array_merge($errors, (array) $resp->errors);
                    $params = array_merge($params, (array) $resp->params);
                    $extras['responses'] = array_merge($extras['responses'], (array) $resp->extras->responses);
                    $extras['sps'] = array_merge($extras['sps'], (array) $resp->extras->sps);
                    $extras['verificado'] = array_merge($extras['verificado'], (array) $resp->extras->verificado);
                    $extras['queries'] = array_merge($extras['queries'], (array) $resp->extras->queries);
                    // array_push($errors, $resp->errors);
                    // array_push($params, $resp->params);
                    // array_push($extras['sps'], $resp->extras->sps);
                    // array_push($extras['verificado'], $resp->extras->verificado);
                    // array_push($extras['queries'], $resp->extras->queries);
                    $data = $resp->data;
                    $message = $resp->message;
                    $status = $resp->status;
                    $code = $resp->code;
                    $count = 18; // $resp->count;

                    if($esPreautorizacion ){
                        if($codigo_interno && $validacion['id_pre_autorizacion'] != null){
                            // actualizar la preautorizacion con el codigo interno
                            $sp = 'sp_pre_autorizaciones_update';
                            $db = 'validacion';
                            $params_update = [
                                'id_pre_autorizacion' => $validacion['id_pre_autorizacion'],
                                'codigo_interno' => $codigo_interno
                            ];
                            array_push($extras['sps'], [$sp => $params_update]);
                            array_push($extras['queries'], $this->get_query($db, $sp, $params_update));
                            $preautorizacion = $this->ejecutar_sp_directo($db, $sp, $params_update);
                            array_push($extras['responses'], [$sp => $preautorizacion]);
                            $resp->data->preautorizacion = $preautorizacion;
                            if(is_array($preautorizacion) && array_key_exists('error', $preautorizacion)){
                                array_push($errors, $preautorizacion['error']);
                                Log::channel('validaciones')->error('Error al actualizar preautorizacion id '.$validacion['id_pre_autorizacion'].' con codigo interno '.$codigo_interno.': '.$preautorizacion['error']);
                            }
                            $data['preautorizacion'] = $preautorizacion;
                        }
                    }

                    return response()->json([
                        'status' => $status,
                        'count' => $count,
                        'errors' => $errors,
                        'message' => $message,
                        'line' => null,
                        'code' => $code,
                        'data' => $data,
                        'params' => $params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ]); 
                } catch (\Throwable $th) {
                    array_push($errors, 'Line: '.$th->getLine().' - '.$th->getMessage());
                    $message = $th->getMessage();
                    $line = $th->getLine();
                    return response()->json([
                        'status' => $status,
                        'count' => $count,
                        'errors' => $errors,
                        'message' => $message,
                        'line' => $line,
                        'code' => $code,
                        'data' => $data,
                        'params' => $params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ]); 
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($permiso_requerido);
                $count  = -1;
                $data = null;
                array_push($errors, 'Error de permisos. '.$message);
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => -1,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Emite una validacion
     * Función para uso externo a la clase utilizada en  InternacionController
     * @param datos_extra
     * @param params_sp parámetros para el sp AWEB_Autorizar
     * @param observaciones: string
     * @param slug_origen: string de donde proviene la emision
     * @param es_internacion: boolean
     * @param logged_user: usuario logueado
     */
    public function emitir($datos_extras, $params_sp, $observaciones, $adjunto, $slug_origen, $es_internacion, $logged_user, $controlador){
        Log::channel('validaciones')->info('');
        Log::channel('validaciones')->info('DEBUG: NUEVA EMISION DE VALIDACION ------------------------------------------');
        Log::channel('validaciones')->info('DEBUG: Iniciando método emitir() con params_sp: ' . json_encode($params_sp));
        // date_default_timezone_set('America/Argentina/Cordoba');
        $extras = [
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $errors = [];
        $params = [];

        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        // return $params_sp;
        array_push($extras['sps'], ['AWEB_Autorizar' => $params_sp]);
        array_push($extras['queries'], $controlador->get_query('validacion', 'AWEB_Autorizar', $params_sp));
        $response = $controlador->ejecutar_sp_directo('validacion','AWEB_Autorizar', $params_sp);
        array_push($extras['responses'], ['AWEB_Autorizar' => $response]);
        if(is_array($response) && array_key_exists('error', $response)){
            array_push($errors, $response['error']);
            $status = 'fail';
            $message = 'Se produjo un error al realizar la petición';
            $count = 0;
            $data = null;
            $code = -2;
            Log::channel('validaciones')->critical('Error al emitir validacion '.$response['error'].' emitida por '.$logged_user['name'].' (id '.$logged_user['id'].')');
        }else if(empty($response[0]->codigo_interno)){
            $status = 'empty';
            $message = $response[0]->Motivo;
            $count = 0;
            $data = $response[0]->Estado;
            $code = -3;
            Log::channel('validaciones')->error('Fallo de validacion emitida por '.$logged_user['name'].' (id '.$logged_user['id'].')');
        }else{
            $status = 'ok';
            $message = 'Transacción realizada con éxito.';
            $count = sizeof($response);
            // $data = $response;
            $code = 1;
            $numero_formateado = get_agregar_ceros($response[0]->id_sucursal, 3) . get_agregar_ceros($response[0]->autorizacion, 8);
            Log::channel('validaciones')->info('Validacion emitida N° '.$response[0]->autorizacion.' codigo_interno: '.$response[0]->codigo_interno.' emitida por '.$logged_user['name'].' (id '.$logged_user['id'].')');
            
            $response_pic = null;
            // si tiene pic ejecuta sp_genera_pic
            if($params_sp['tiene_pic'] == 1){
                $sp = 'sp_generar_pic';
                $db = 'validacion';
                $params_pic = [
                    'p_id_usuario' => $id_usuario,
                    'p_codigo_interno' => $response[0]->codigo_interno,
                ];
                array_push($extras['sps'], [$sp => $params_pic]);
                array_push($extras['queries'], $controlador->get_query($db, $sp, $params_pic));
                $response_pic = $controlador->ejecutar_sp_directo($db, $sp, $params_pic);
                array_push($extras['responses'], [$sp => $response_pic]);
            }
            
            if (!empty($observaciones)) {
                if(!empty($observaciones['afiliado'])){
                    //  agrega la observacion al afiliado
                    $params_obs_af = [
                        'id_empresa' => $params_sp['Empresa'],
                        'afiliado' => $params_sp['Afiliado'],
                        'id_usuario' => $params_sp['usuario'],
                        'comentarios' => $observaciones['afiliado']
                    ];
                    array_push($extras['sps'], ['AWEB_InsertarNovedadAfiliadoCallCenter' => $params_obs_af]);
                    array_push($extras['queries'], $controlador->get_query('validacion', 'AWEB_InsertarNovedadAfiliadoCallCenter', $params_obs_af));
                    $obs_af = $controlador->ejecutar_sp_directo('validacion', 'AWEB_InsertarNovedadAfiliadoCallCenter', $params_obs_af);
                    array_push($extras['responses'], ['AWEB_InsertarNovedadAfiliadoCallCenter' => $obs_af]);
                }
            }

            //  si hay observaciones para el efector
            if (!empty($observaciones)) {
                if(!empty($observaciones['efector'])){
                    //  agrega la observacion al efector
                    $params_obs_ef = [
                        'id_prestador' => $params_sp['Prestador'],
                        'id_usuario' => $params_sp['usuario'],
                        'comentarios' => $observaciones['efector']
                    ];
                    array_push($extras['sps'], ['AWEB_InsertarNovedadAfiliadoCallCenter' => $params_obs_ef]);
                    array_push($extras['queries'], $controlador->get_query('validacion', 'AWEB_InsertarNovedadAfiliadoCallCenter', $params_obs_ef));
                    $obs_ef = $controlador->ejecutar_sp_directo('validacion', 'AWEB_InsertarNovedadAfiliadoCallCenter', $params_obs_ef);
                    array_push($extras['responses'], ['AWEB_InsertarNovedadAfiliadoCallCenter' => $obs_ef]);
                }
            }
            // si hay adjuntos
            if (!is_null($adjunto)) {
                //  si hay adjunto
                if(is_array($adjunto)){
                    if (count($adjunto) > 0) {
                        //  agrega el archivo
                        foreach ($adjunto as $filename) {
                            $params_adj = [
                                'codigo_interno' => $response[0]->codigo_interno,
                                'archivo' => $filename,
                                'id_usuario' => $logged_user['usuario'],
                                'fecha' => Carbon::now()->format('Ymd H:i:s')
                            ];
                            array_push($extras['sps'], ['AWEB_InsertarArchivoAdjunto' => $params_adj]);
                            array_push($extras['queries'], $controlador->get_query('validacion', 'AWEB_InsertarArchivoAdjunto', $params_adj));
                            $adj = $controlador->ejecutar_sp_directo('validacion', 'AWEB_InsertarArchivoAdjunto', $params_adj);
                            array_push($extras['responses'], ['AWEB_InsertarArchivoAdjunto' => $adj]);
                        }
                    }
                }else{
                    $params_adj = [
                        'codigo_interno' => $response[0]->codigo_interno,
                        'archivo' => $adjunto,
                        'id_usuario' => $logged_user['usuario'],
                        'fecha' => Carbon::now()->format('Ymd H:i:s')
                    ];
                    array_push($extras['sps'], ['AWEB_InsertarArchivoAdjunto' => $params_adj]);
                    array_push($extras['queries'], $controlador->get_query('validacion', 'AWEB_InsertarArchivoAdjunto', $params_adj));
                    $adj = $controlador->ejecutar_sp_directo('validacion', 'AWEB_InsertarArchivoAdjunto', $params_adj);
                    array_push($extras['responses'], ['AWEB_InsertarArchivoAdjunto' => $adj]);
                }
            }

            $hc_insert = null;
            if(isset($observaciones['insertar_en_hc']) && $observaciones['insertar_en_hc'] == 1){
                if (!is_null($adjunto)) {
                    //  si hay adjunto
                    if(is_array($adjunto)){
                        if (count($adjunto) > 0) {
                            //  agrega el archivo
                            foreach ($adjunto as $filename) {
                                $parametros = [
                                    'tipo_documento' => $datos_extras['tipo_documento'],
                                    'numero_documento' => $datos_extras['nro_doc'],
                                    'usuario' => Session::get('usuario.id_n_usuario'),
                                    'hoja' => !empty($observaciones['afiliado']) ? $observaciones['afiliado'] : '',
                                    'archivo' => $filename
                                ];
                                array_push($extras['sps'], ['AWEB_AgregarHojaHistoriaClinica' => $parametros]);
                                array_push($extras['queries'], $controlador->get_query('validacion', 'AWEB_AgregarHojaHistoriaClinica', $parametros));
                                $hc_insert = $controlador->ejecutar_sp_directo('validacion', 'AWEB_AgregarHojaHistoriaClinica', $parametros);
                                array_push($extras['responses'], ['AWEB_AgregarHojaHistoriaClinica' => $hc_insert]);
                            }
                        }
                    }else{
                        $parametros = [
                            'tipo_documento' => $datos_extras['tipo_documento'],
                            'numero_documento' => $datos_extras['nro_doc'],
                            'usuario' => Session::get('usuario.id_n_usuario'),
                            'hoja' => !empty($observaciones['afiliado']) ? $observaciones['afiliado'] : '',
                            'archivo' => $adjunto
                        ];
                        array_push($extras['sps'], ['AWEB_AgregarHojaHistoriaClinica' => $params_adj]);
                        array_push($extras['queries'], $controlador->get_query('validacion', 'AWEB_AgregarHojaHistoriaClinica', $params_adj));
                        $hc_insert = $controlador->ejecutar_sp_directo('validacion', 'AWEB_AgregarHojaHistoriaClinica', $parametros);
                        array_push($extras['responses'], ['AWEB_AgregarHojaHistoriaClinica' => $hc_insert]);
                    }

                }else{
                    $parametros = [
                        'tipo_documento' => $datos_extras['tipo_documento'],
                        'numero_documento' => $datos_extras['nro_doc'],
                        'usuario' => Session::get('usuario.id_n_usuario'),
                        'hoja' => !empty($observaciones['afiliado']) ? $observaciones['afiliado'] : '',
                        'archivo' => null
                    ];
                    array_push($extras['sps'], ['AWEB_AgregarHojaHistoriaClinica' => $params_adj]);
                    array_push($extras['queries'], $controlador->get_query('validacion', 'AWEB_AgregarHojaHistoriaClinica', $params_adj));
                    $hc_insert = $controlador->ejecutar_sp_directo('validacion', 'AWEB_AgregarHojaHistoriaClinica', $parametros);
                    array_push($extras['responses'], ['AWEB_AgregarHojaHistoriaClinica' => $hc_insert]);
                }
                // if(!empty($hc_insert) && $hc_insert[0]->agregado == 1){
                //     $message = 'La observación  se insertó exitosamente y se añadió a la historia clínica';
                //     $code = 3;
                //     $hc_ok = TRUE;
                // }else{
                //     $message = 'La observación  se insertó exitosamente pero NO se añadió a la historia clínica';
                //     $code = -3;
                //     $hc_ok = FALSE;
                // }
            }
        
            // ejecutar notificación a usuarios mediante pusher
            $params_notificar = [
                'p_id_contrato' => 3,  // validaciones es el id_contrato 3.    Contratos: 1.-Afiliaciones, 2.-Expedientes, 3.-Validaciones
                'p_codigo_interno' => $response[0]->codigo_interno
            ];
            array_push($extras['sps'], ['sp_contrato_usuario_rol_select' => $params_notificar]);
            array_push($extras['queries'], $controlador->get_query('admin', 'sp_contrato_usuario_rol_select', $params_notificar));
            $usuarios_notificar = $controlador->ejecutar_sp_directo('admin', 'sp_contrato_usuario_rol_select', $params_notificar);
            array_push($extras['responses'], ['sp_contrato_usuario_rol_select' => $usuarios_notificar]);
            // return $params_sp;

            if (count($usuarios_notificar) > 0) {
                $id_usuarios = [];
                try {
                    $clientePusher = new Pusher(env('PUSHER_APP_KEY', ''), env('PUSHER_APP_SECRET', ''), env('PUSHER_APP_ID', ''), array('cluster' => env('PUSHER_APP_CLUSTER', 'us2')));
                } catch (PusherException $e) {
                    Log::channel('pusher')->warning("Validaciones::emitir-> Error al instanciar pusher: " . $e->getMessage());
                    return $e;
                }

                foreach ($usuarios_notificar as $usuario) {
                    // Convertir a objeto si es un array
                    if (is_array($usuario)) {
                        $usuario = (object) $usuario;
                    }
                    
                    // Validar que el objeto usuario tenga las propiedades necesarias
                    if (!is_object($usuario) || !isset($usuario->id_usuario)) {
                        Log::channel('validaciones')->warning('Usuario sin propiedades válidas en notificación: ' . json_encode($usuario));
                        continue;
                    }
                    
                    if ($id_usuario !== $usuario->id_usuario) {
                        array_push($id_usuarios, $id_usuario);
                        if ($response[0]->estado === 1) {
                            $mensaje = "Afiliado ".$datos_extras['n_persona'].' N° '.$params_sp['Afiliado'].". Autorización Nro. " . $response[0]->autorizacion . " autorizada.";

                            $utiles = [
                                'path_url' => '/validaciones',
                                'codigo' => $response[0]->codigo_interno
                            ];

                            $tipo_notificacion = 1;

                            $params_notificacion = [
                                'p_id_contrato' => $usuario->id_contrato,
                                'p_id_usuario' => $usuario->id_usuario,
                                'p_texto_notificacion' => $mensaje,
                                'p_asunto' => "Afiliado ".$datos_extras['n_persona'].' N° '.$params_sp['Afiliado'].". Autorización N° " . $response[0]->autorizacion,
                                'p_notificado' => 0,
                                'p_utiles' => json_encode($utiles),
                                'p_id_tipo_notificacion' => $tipo_notificacion
                            ];
                            array_push($extras['sps'], ['sp_notificacion_insert' => $params_notificacion]);
                            array_push($extras['queries'], $controlador->get_query('admin', 'sp_notificacion_insert', $params_notificacion));
                            $notificacion = $controlador->ejecutar_sp_directo('admin', 'sp_notificacion_insert', $params_notificacion);
                            array_push($extras['responses'], ['sp_notificacion_insert' => $notificacion]);

                            // $this->pusherService->triggerNotification(
                            //     "notificacion-push", // channel
                            //     "NotificacionEnviada", //event
                            //     [
                            //         'id_usuario' => $usuario['id_usuario'], 
                            //         'code' => 1,
                            //         'message' => 'Se ha realizado una nueva validación',
                            //         'ambito' => env('ENVIRONMENT')
                            //     ] // message
                            // );
                        } else {
                            $mensaje = "Afiliado ".$datos_extras['n_persona'].' N° '.$params_sp['Afiliado'].". Autorización Nro. " . $response[0]->autorizacion . " para auditar.";

                            $utiles = [
                                'path_url' => '/validaciones/auditar/' . $response[0]->codigo_interno,
                                'codigo' => $response[0]->codigo_interno
                            ];

                            $tipo_notificacion = 2;
                            $params_notificacion = [
                                'p_id_contrato' => $usuario->id_contrato,
                                'p_id_usuario' => $usuario->id_usuario,
                                'p_texto_notificacion' => $mensaje,
                                'p_asunto' => "Afiliado ".$datos_extras['n_persona'].' N° '.$params_sp['Afiliado'].". Autorización N° " . $response[0]->autorizacion,
                                'p_notificado' => 0,
                                'p_utiles' => json_encode($utiles),
                                'p_id_tipo_notificacion' => $tipo_notificacion
                            ];
                            array_push($extras['sps'], ['sp_notificacion_insert' => $params_notificacion]);
                            array_push($extras['queries'], $controlador->get_query('admin', 'sp_notificacion_insert', $params_notificacion));
                            $notificacion = $controlador->ejecutar_sp_directo('admin', 'sp_notificacion_insert', $params_notificacion);
                            array_push($extras['responses'], ['sp_notificacion_insert' => $notificacion]);

                            // $this->pusherService->triggerNotification(
                            //     "notificacion-push", // channel
                            //     "NotificacionEnviada", //event
                            //     [
                            //         'id_usuario' => $usuario['id_usuario'], 
                            //         'code' => 1, 
                            //         'message' => 'Se ha realizado una nueva validación',
                            //         'ambito' => env('ENVIRONMENT')
                            //     ] // message
                            // );
                        }
                    }
                }

                $channel = 'notificacion-push';
                $event = 'NotificacionEnviada';
                $msg = [
                    'id_usuarios' => $id_usuarios, // $usuario['id_usuario'], //Session::get('usuario.id_usuario'), 
                    'code' => 1,
                    'message' => 'Se ha realizado una nueva validación',
                    'ambito' => env('ENVIRONMENT')
                ];

                try {
                    $clientePusher->trigger($channel, $event, $msg);
                } catch (PusherException $e) {
                    Log::channel('pusher')->warning("Validaciones::emitir->Error al desencadenar evento pusher: " . $e->getMessage());
                    return $e;
                } catch (GuzzleException $e) {
                    Log::channel('pusher')->warning("Validaciones::emitir->Error al desencadenar evento guzzle: " . $e->getMessage());
                    return $e;
                } catch (\Exception $e) {
                    return $e;
                } 
            }
            $data = [
                'id' => $response[0]->codigo_interno,
                'message' => $message,
                'n_autorizacion' => $response[0]->autorizacion,
                'autorizacion' => $numero_formateado,
                'usuarios_notificados' => $usuarios_notificar,
                'id_contrato' => 3, // validaciones es el id_contrato 3.      Contratos: 1.-Afiliaciones, 2.-Expedientes, 3.-Validaciones
                'codigo_interno' => $response[0]->codigo_interno,
                'validacion_emitida' => $response,
                'insercion_en_historia_clinica' => $hc_insert,
                'data' => $datos_extras,
                'observaciones' => $observaciones,
                'generar_pic' => $response_pic
            ];
        
            // Código TACH para generar QR después de emisión exitosa
            Log::channel('validaciones')->info('DEBUG: Verificando condiciones TACH - status: ' . $status . ', codigo_interno: ' . ($response[0]->codigo_interno ?? 'N/A') . ', datos_extras: ' . json_encode($datos_extras));
            if($status === 'ok' && isset($response[0]->codigo_interno) && !empty($response[0]->codigo_interno)){
                $codigo_interno = $response[0]->codigo_interno;
                $token_tach = isset($datos_extras['token_tach']) ? $datos_extras['token_tach'] : null;
                if(env('GENERAR_TACH_QR') == 'SI' ){
                    if($token_tach != null && $token_tach != ''){
                        // ejecutar sp_autorizacion_qr_select con codigo_interno para obtener el coseguro y la fecha de vencimiento y el estado
                        $sp = 'sp_autorizacion_qr_select';
                        $db = 'validacion';
                        $params_qr_select = [
                            'p_codigo_interno' => $codigo_interno
                        ];
                        array_push($extras['sps'], [$sp => $params_qr_select]);
                        array_push($extras['queries'], $controlador->get_query($db, $sp, $params_qr_select));
                        $response_select = $controlador->ejecutar_sp_directo($db, $sp, $params_qr_select);
                        array_push($extras['responses'], [$sp => $response_select]);
                        Log::channel('validaciones')->info('DEBUG: Respuesta de sp_autorizacion_qr_select para codigo_interno ' . $codigo_interno . ': ' . json_encode($response_select));   


                        if($response_select[0]->coseguro == '.00' || $response_select[0]->coseguro == null){
                            $coseg = "0.00";
                            Log::channel('validaciones')->info('DEBUG: Coseguro es .00 o null, usando valor por defecto: ' . $coseg);
                        }else{
                            $coseg = $response_select[0]->coseguro;
                        }
                        $coseguro = floatval($coseg);

                        Log::channel('validaciones')->info('DEBUG: Verificando condiciones TACH - coseguro: ' .$coseguro. ' estado: ' . $response_select[0]->id_estado);
                        if($response_select[0]->id_estado == 1 && $coseguro > 0){
                            // $numero_formateado = get_numero_autorizacion_formateado($response_select[0]->sucursal, $response_select[0]->numero);
                            $company_id = env('TACH_COMPANY_ID');
                            $description = "validacion-" . $numero_formateado;
                            $quantity = 1;
                            $expiration_datetime = isset($response_select[0]->fecha_vencimiento) && $response_select[0]->fecha_vencimiento ? 
                                Carbon::parse($response_select[0]->fecha_vencimiento)->format('Y-m-d') : 
                                Carbon::now()->addDays(30)->format('Y-m-d');
                            
                            // generamos el qr y lo guardamos en la validacion
                            Log::channel('validaciones')->info('DEBUG: a punto de llamar a TachController::obtenerQrTach con codigo_interno: ' . $codigo_interno);
                            $resp_qr_tach = TachController::obtenerQrTach($codigo_interno, $company_id, $description, $quantity, $coseguro, $expiration_datetime, $token_tach);
                            Log::channel('validaciones')->info('DEBUG: Respuesta de TachController::obtenerQrTach: ' . json_encode($resp_qr_tach) . ' Tipo: ' . gettype($resp_qr_tach));
                            
                            // Si la respuesta es una string JSON, decodificarla
                            if (is_string($resp_qr_tach)) {
                                $resp_qr_tach = json_decode($resp_qr_tach, true);
                                Log::channel('validaciones')->info('DEBUG: Después de json_decode: ' . json_encode($resp_qr_tach) . ' Tipo: ' . gettype($resp_qr_tach));
                            }
                            
                            // Validar que la respuesta sea un array válido antes de acceder a sus elementos
                            if($resp_qr_tach != null && isset($resp_qr_tach['operation_id'])){
                                $sp_qr = 'sp_autorizacion_qr_update';
                                $db = 'validacion';
                                $params_qr = [
                                    'p_codigo_interno' => $codigo_interno,
                                    'p_qr_operation_id' => $resp_qr_tach['operation_id'] ?? '',
                                    'p_qr_code' => $resp_qr_tach['qr_code'] ?? '',
                                    'p_qr_status' => null
                                ];
                                array_push($extras['sps'], [$sp_qr => $params_qr]);
                                array_push($extras['queries'], $controlador->get_query($db, $sp_qr, $params_qr));
                                $qr_update = $controlador->ejecutar_sp_directo($db, $sp_qr, $params_qr);
                                array_push($extras['responses'], [$sp_qr => $qr_update]);
                                
                                Log::channel('validaciones')->info('DEBUG: QR TACH generado exitosamente para codigo_interno: ' . $codigo_interno);
                                $data['qr_tach_info'] = $resp_qr_tach;
                            }else{
                                Log::warning('DEBUG: No se pudo generar el código QR TACH para codigo_interno: ' . $codigo_interno);
                                array_push($errors, 'Error al generar QR TACH');
                            }
                        }else{
                            Log::channel('validaciones')->info('DEBUG: Validación no cumple condiciones para QR TACH (estado: ' . ($response[0]->estado ?? 'N/A') . ', coseguro: ' . ($coseguro ?? 'N/A') . ')');
                        }
                    }else{
                        Log::channel('validaciones')->info('DEBUG: No se proporcionó token TACH en los datos');
                    }
                }else{
                    Log::channel('validaciones')->info('DEBUG: Generación de TACH QR deshabilitada en configuración');
                }
            }

        }

        return [
            'status' => $status,
            'count' => $count,
            'errors' => $errors,
            'message' => $message,
            'line' => null,
            'code' => $code,
            'data' => $data,
            'params' => $params_sp,
            'extras' => $extras,
            'logged_user' => $logged_user,
        ]; 
    }

    /**
     * Actualiza los datos parciales de una validación
     */
    public function actualizar_validacion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/validaciones/actualizar-validacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = null;
        $message = '';
        $count = -1;
        $code = null;
        $data = [];
        $errors = [];
        $params = [];
        $params_sp = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

        try{

            $params = [
                'codigo_interno' => request('codigo_interno'),
                'codigo_internacion' => request('codigo_internacion'),
                'fecha_prescripcion' => request('fecha_prescripcion') !== NULL ? request('fecha_prescripcion') : NULL,
                'fecha_realizacion' => request('fecha_realizacion') !== NULL ? request('fecha_realizacion') : NULL,
                'fecha_emision' => request('fecha_emision') !== NULL ? request('fecha_emision') : NULL,
                'fecha_vencimiento' => request('fecha_vencimiento') !== NULL ? request('fecha_vencimiento') : NULL,
                'fecha_ingreso_internacion' => request('fecha_ingreso_internacion') !== NULL ? request('fecha_ingreso_internacion') : NULL,
                'fecha_vencimiento_internacion' => request('fecha_vencimiento_internacion') !== NULL ? request('fecha_vencimiento_internacion') : NULL,
                'coseguro' => request('coseguro') !== NULL ? request('coseguro') : NULL,
                'tiene_insumos' => request('tiene_insumos') !== NULL ? request('tiene_insumos') : NULL,
                'tiene_pic' => request('tiene_pic') !== NULL ? request('tiene_pic') : NULL,
                'cambiar_pic' => request('cambiar_pic') !== NULL ? filter_var(request('cambiar_pic'), FILTER_VALIDATE_BOOLEAN) : false,
                'token_tach' => request('token_tach') !== NULL ? request('token_tach') : null,
            ];
            
            $params_sp = [
                'p_codigo_interno' => $params['codigo_interno'],
                'p_codigo_internacion' => $params['codigo_internacion'],
                'p_fecha_prescripcion' => $params['fecha_prescripcion'] !== NULL ? Carbon::parse($params['fecha_prescripcion'])->format('Ymd') : NULL,
                'p_fecha_realizacion' => $params['fecha_realizacion'] !== NULL ? Carbon::parse($params['fecha_realizacion'])->format('Ymd') : NULL,
                'p_fecha_solicitud' => $params['fecha_emision'] !== NULL ? Carbon::parse($params['fecha_emision'])->format('Ymd') : NULL,
                'p_fecha_vencimiento' => $params['fecha_vencimiento'] !== NULL ? Carbon::parse($params['fecha_vencimiento'])->format('Ymd') : NULL,
                'p_fecha_ingreso_internacion' => $params['fecha_ingreso_internacion'] !== NULL ? Carbon::parse($params['fecha_ingreso_internacion'])->format('Ymd') : NULL,
                'p_fecha_vencimiento_internacion' => $params['fecha_vencimiento_internacion'] !== NULL ? Carbon::parse($params['fecha_vencimiento_internacion'])->format('Ymd') : NULL,
                'p_coseguro' => $params['coseguro'],
                'p_incluye_protesis' => $params['tiene_insumos'],
                'p_tiene_pic' => $params['tiene_pic']
            ];

            // return ['params' => $params, 'params_sp' => $params_sp];

            $db = 'validacion';
            $sp = 'sp_autorizacion_update';
            array_push($extras['sps'], [$sp => $params_sp]);
            array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
            $response = $this->ejecutar_sp_directo($db, $sp, $params_sp);
            array_push($extras['responses'], [$sp => $response]);
            $data = $response;
            if(isset($response['error'])){
                $status = 'fail';
                $message = 'No se pudo actualizar la validacion';
                $code = -6;
                array_push($errors, 'Error actualizando validacion'.$response['error']);
            }else if($response[0]->error == 0){
                $status = 'ok';
                $message = 'Validacion actualizada con éxito';
                $code = 2;
                if($params['cambiar_pic']){
                    // ejecutamos el otro sp
                    $params_sp_pic = [
                        'p_id_usuario' => $id_usuario,
                        'p_codigo_interno' => $params['codigo_interno']
                    ];
                    $sp_pic = 'sp_generar_pic';
                    array_push($extras['sps'], [$sp_pic => $params_sp_pic]);
                    array_push($extras['queries'], $this->get_query($db, $sp_pic, $params_sp_pic));
                    $response_pic = $this->ejecutar_sp_directo($db, $sp_pic, $params_sp_pic);
                    array_push($extras['responses'], [$sp_pic => $response_pic]);
                    $data['pic_cambiado'] = $response_pic;

                    if(isset($response_pic['error'])){
                        $status = 'fail';
                        $message = 'No se pudo cambiar el PIC';
                        $code = -7;
                        array_push($errors, 'Error actualizando PIC'. $response_pic['error']);
                    }else if($response_pic[0]->registros == 1){
                        $status = 'ok';
                        $message = 'Validacion actualizada y PIC actualizado';
                        $code = 2;
                    }else{
                        $status = 'fail';
                        $message = 'No se pudo cambiar el PIC';
                        $code = -4;
                        array_push($errors, 'Error actualizando PIC');
                    }
                }

            }else{
                $status = 'fail';
                $message = 'No se pudo actualizar la validacion';
                $code = -5;
                array_push($errors, 'Error actualizando validacion');
            }

            // actualizar la validacion
            
            try {
                Log::channel('validaciones')->info('DEBUG: Iniciando actualizar_validacion coseguro, data error: ' . ($data[0]->error ?? 'NULL'));
                if($response[0]->error == 0){
                    Log::channel('validaciones')->info('DEBUG: Error == 0, coseguro: ' . ($params['coseguro'] ?? 'NULL'));
                    if($params['coseguro']){
                        // obtener el codigo qr y actualizamos el codigo qr en la validacion
                        Log::channel('validaciones')->info('DEBUG: GENERAR_TACH_QR: ' . env('GENERAR_TACH_QR'));
                        if(env('GENERAR_TACH_QR') == 'SI' ){
                            Log::channel('validaciones')->info('DEBUG: Iniciando generación TACH QR');
                            $db = 'validacion'; // afiliacion, validacion, admin, alfabeta
                            $sp = 'AWEB_TraerAutorizacionCabecera';
                            $codigo_interno = $params['codigo_interno'];
                            $params_sp_validacion = [
                                'codigo_interno' => $params['codigo_interno']
                            ];
                            $sp_validacion = 'AWEB_TraerAutorizacionCabecera';

                            array_push($extras['sps'], [$sp_validacion => $params_sp_validacion]);
                            array_push($extras['queries'], $this->get_query($db, $sp_validacion, $params_sp_validacion));
                            Log::channel('validaciones')->info('DEBUG: A punto de ejecutar SP AWEB_TraerAutorizacionCabecera');
                            $validacion = $this->ejecutar_sp_directo($db, $sp_validacion, $params_sp_validacion);
                            Log::channel('validaciones')->info('DEBUG: SP ejecutado, retornando validacion: ' . json_encode($validacion));
                            // return $validacion[0];
                            array_push($extras['responses'], [$sp_validacion => $validacion]);

                            if(!empty($validacion)){
                                // Obtener token TACH (necesario para generar QR)
                                $token_tach = null;
                                try {
                                    // Aquí necesitarías obtener el token TACH, similar a como se hace en otras funciones
                                    // Por ahora, lo dejamos como null para evitar el error
                                    $token_tach = $params['token_tach'] ?? null;
                                } catch (\Exception $e) {
                                    Log::channel('tach')->error('ValidacionController::actualizar_validacion. Error obteniendo token TACH: ' . $e->getMessage());
                                    $token_tach = null;
                                }
                            
                                // logica para generar qr
                                if($token_tach != null && $token_tach != ''){
                                    if(isset($validacion[0]) && $validacion[0]->id_estado == 1 && floatval($validacion[0]->coseguro) > 0){
                                        $numero_formateado = get_numero_autorizacion_formateado($validacion[0]->id_sucursal, $validacion[0]->autorizacion);
                                        $company_id = env('TACH_COMPANY_ID');
                                        $description = "validacion-" . $numero_formateado;
                                        $quantity = 1;
                                        $coseguro = floatval($validacion[0]->coseguro);
                                        $expiration_datetime = $validacion[0]->fecha_vencimiento ? Carbon::parse($validacion[0]->fecha_vencimiento)->format('Y-m-d') : Carbon::now()->addDays(30)->format('Y-m-d');
                                        // generamos el qr y lo guardamos en la validacion
                                        Log::channel('validaciones')->info('DEBUG: a punto de llamar a TachController::obtenerQrTach con codigo_interno: ' . $codigo_interno);
                                        $resp_qr_tach = TachController::obtenerQrTach($codigo_interno, $company_id, $description, $quantity, $coseguro, $expiration_datetime, $token_tach);
                                        Log::channel('validaciones')->info('DEBUG: Respuesta de TachController::obtenerQrTach: ' . json_encode($resp_qr_tach) . ' Tipo: ' . gettype($resp_qr_tach));
                                        
                                        // Si la respuesta es una string JSON, decodificarla
                                        if (is_string($resp_qr_tach)) {
                                            $resp_qr_tach = json_decode($resp_qr_tach, true);
                                            Log::channel('validaciones')->info('DEBUG: Después de json_decode: ' . json_encode($resp_qr_tach) . ' Tipo: ' . gettype($resp_qr_tach));
                                        }
                                        
                                        $data['response_qr_tach'] = $resp_qr_tach;
                                        // return $resp_qr_tach;
                                        
                                        // Validar que la respuesta sea un objeto válido antes de acceder a sus propiedades
                                        if($resp_qr_tach != null && $resp_qr_tach['operation_id'] != null){
                                            $sp_qr = 'sp_autorizacion_qr_update';
                                            $db = 'validacion';
                                            $params_qr = [
                                                'p_codigo_interno' => $codigo_interno,
                                                'p_qr_operation_id' => $resp_qr_tach['operation_id'] ?? '',
                                                'p_qr_code' => $resp_qr_tach['qr_code'] ?? '',
                                                'p_qr_status' => null
                                            ];
                                            array_push($extras['sps'], [$sp_qr => $params_qr]);
                                            array_push($extras['queries'], $this->get_query($db, $sp_qr, $params_qr));
                                            $qr_update = $this->ejecutar_sp_directo($db, $sp_qr, $params_qr);
                                            array_push($extras['responses'], [$sp_qr => $qr_update]);

                                            $status = 'ok';
                                            $message = 'La validación se actualizó con éxito.';
                                            $code = 3;
                                        }else{
                                            Log::channel('validaciones')->warning('DEBUG: No se pudo generar el código QR TACH');
                                            $status = 'warning';
                                            $message = 'La validación se actualizó pero no se pudo generar el código QR nuevo.';
                                            $code = -10;
                                            array_push($errors, 'Error al generar QR');
                                        }
                                    }else{
                                        $sp_qr = 'sp_autorizacion_qr_update';
                                        $db = 'validacion';
                                        $params_qr = [
                                            'p_codigo_interno' => $codigo_interno,
                                            'p_qr_operation_id' => null,
                                            'p_qr_code' => null,
                                            'p_qr_status' => null
                                        ];
                                        array_push($extras['sps'], [$sp_qr => $params_qr]);
                                        array_push($extras['queries'], $this->get_query($db, $sp_qr, $params_qr));
                                        $qr_update = $this->ejecutar_sp_directo($db, $sp_qr, $params_qr);
                                        array_push($extras['responses'], [$sp_qr => $qr_update]);

                                        $status = 'ok';
                                        $message = 'La validación se actualizó con éxito.';
                                        $code = 4;
                                    }
                                }else{
                                    Log::channel('validaciones')->warning('DEBUG: No se pudo obtener el token TACH');
                                    $status = 'warning';
                                    $message = 'La validación se actualizó pero no se pudo generar el código QR nuevo.';
                                    $code = -9;
                                    array_push($errors, 'Error: falta token_tach en el request para generar QR');
                                }
                            }
                        }
                    }
                }else{
                    $status = 'fail';
                    $message = 'No se pudo actualizar la validacion';
                    $code = -7;
                    array_push($errors, 'Error actualizando validacion');
                }
            } catch (\Exception $e) {
                Log::channel('validaciones')->error('DEBUG: Excepción en actualizar_validacion: ' . $e->getMessage() . ' Línea: ' . $e->getLine());
                $status = 'fail';
                $message = 'Error interno del servidor';
                $code = -8;
                array_push($errors, 'Excepción: ' . $e->getMessage());
            }


            return response()->json([
                'status' => $status,
                'count' => 1,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => $code,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Anula una validación emitida
     */
    public function anular_validacion(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/validaciones/anular-validacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail';
        $message = '';
        $count = null;
        $code = null;
        $data = null;
        $errors = [];
        $params_sp = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            $permiso_requerido = 'anular validaciones';
            if($user->hasPermissionTo($permiso_requerido)){
                $motivo = request('motivo');
                $codigo_interno = request('codigo_interno');
                $params = [
                    'motivo' => $motivo,
                    'codigo_interno' => $codigo_interno
                ];
                //  obtiene los datos de la validacion
                array_push($extras['sps'], ['AWEB_TraerAutorizacionCabecera' => ['codigo_interno' => $codigo_interno]]);
                array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerAutorizacionCabecera', ['codigo_interno' => $codigo_interno]));
                $validacion = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerAutorizacionCabecera', ['codigo_interno' => $codigo_interno]);
                array_push($extras['responses'], ['AWEB_TraerAutorizacionCabecera' => $validacion]);
                
                //  si esta anulada, no permite la anulación
                if (!empty($validacion) && $validacion[0]->estado == 4){
                    return response()->json([
                        'status' => 'fail',
                        'count' => 0,
                        'errors' => ['Validacion ya anulada'],
                        'message' => 'La validación ya ha sido anulada con anterioridad.',
                        'line' => null,
                        'code' => -3,
                        'data' => null,
                        'params' => $params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ]); 
                }
                //  si tiene factura, no permite la anulación
                if (!empty($validacion) && !empty($validacion[0]->id_movimiento)){
                    return response()->json([
                        'status' => 'fail',
                        'count' => 0,
                        'errors' => ['Validacion facturada'],
                        'message' => 'La validación no puede ser anulada debido a que ya ha sido facturada.',
                        'line' => null,
                        'code' => -4,
                        'data' => null,
                        'params' => $params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ]); 
                }

                //  si es un prestador y la validacion no es propia
                if (!empty($validacion) && $logged_user['roles']['name'] == 'prestador' && $logged_user['usuario'] != trim(strtolower($validacion[0]->usuario))){
                    return response()->json([
                        'status' => 'fail',
                        'count' => 0,
                        'errors' => ['Validacion facturada'],
                        'message' => 'La validación no puede ser anulada debido a que no pertenece al usuario.',
                        'line' => null,
                        'code' => -5,
                        'data' => null,
                        'params' => $params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ]); 
                }

                //  si es de una internacion, es anulación, busca afiliado y chequea si ya tiene una internacion abierta
                //  no permite anular un egreso de internacion si ya hay una internacion abierta
                //  porque sino quedarían dos abiertas
                
                if (!empty($validacion[0]->codigo_internacion) && !empty($validacion[0]->id_accion_internacion) && $validacion[0]->id_accion_internacion == 4){
                    array_push($extras['sps'], ['AWEB_TraerAfiliado' => ['id_empresa' => $validacion[0]->id_empresa, 'afiliado' => $validacion[0]->afiliado]]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'AWEB_TraerAfiliado', ['id_empresa' => $validacion[0]->id_empresa, 'afiliado' => $validacion[0]->afiliado]));
                    $afiliado = $this->ejecutar_sp_directo('afiliacion', 'AWEB_TraerAfiliado', ['id_empresa' => $validacion[0]->id_empresa, 'afiliado' => $validacion[0]->afiliado]);
                    array_push($extras['responses'], ['AWEB_TraerAfiliado' => $afiliado]);
                    if ($afiliado){
                        if (!empty($afiliado[0]->codigo_internacion)){
                            return response()->json([
                                'status' => 'fail',
                                'count' => 0,
                                'errors' => ['Internacion Abierta'],
                                'message' => 'El afiliado ya tiene una internación abierta.',
                                'line' => null,
                                'code' => -6,
                                'data' => null,
                                'params' => $params,
                                'extras' => $extras,
                                'logged_user' => $logged_user,
                            ]);
                        }
                    }
                }

                //  variables de control
                $anula_validacion = FALSE;
                $anula_internacion_completa = TRUE;
                $modifica_internacion = TRUE;
                $validaciones_anuladas = [];
                $es_anulacion_internacion = FALSE;
                $cambia_estado_internacion = FALSE;
                $prefijo_slug = '';
                $slug_evento_internacion = '';
                $data = [];

                //  inicia transaccion
                get_begin_transaction('validacion');

                //  crea los parametros
                $params_sp = [
                    'codigo_interno' => $codigo_interno,
                    'id_usuario' => $logged_user['usuario'],
                    'motivo' => $motivo,
                ];

                array_push($extras['sps'], ['AWEB_AnularAutorizacion' => $params_sp]);
                array_push($extras['queries'], $this->get_query('validacion', 'AWEB_AnularAutorizacion', $params_sp));
                $anulacion = $this->ejecutar_sp_directo('validacion', 'AWEB_AnularAutorizacion', $params_sp);
                array_push($extras['responses'], ['AWEB_AnularAutorizacion' => $anulacion]);
                $data['validacion_anulada'] = $anulacion;

                //  si se realiza la anulacion
                if ($anulacion) {
                    //  cambia el flag
                    $anula_validacion = TRUE;
                    //  agrega el codigo interno a la lista de validaciones anuladas
                    $validaciones_anuladas[] = $codigo_interno;
                }else{
                    array_push($errors, 'Error al anular la autorización');
                }
                //  si es una validación de una internacion
                
                if (!empty($validacion[0]->codigo_internacion)) {
                    //  si es un egreso de internacion
                    if (!empty($validacion[0]->id_accion_internacion) && $validacion[0]->id_accion_internacion == 4) {
                        //  cambia flag y establece valores
                        $cambia_estado_internacion = TRUE;
                        $slug_evento_internacion = 'anular-egreso';

                        //  realiza la reapertura la internación
                        $params_egreso = [
                            'codigo_internacion' => $validacion[0]->codigo_internacion,
                            'usuario' => $logged_user['usuario'],
                            'motivo' => $motivo,
                        ];
                        array_push($extras['sps'], ['AWEB_ReabrirInternacion' => $params_egreso]);
                        array_push($extras['queries'], $this->get_query('validacion', 'AWEB_ReabrirInternacion', $params_egreso));
                        $reabrir_internacion = $this->ejecutar_sp_directo('validacion', 'AWEB_ReabrirInternacion', $params_egreso);
                        array_push($extras['responses'], ['AWEB_ReabrirInternacion' => $reabrir_internacion]);
                        $data['internacion_reabierta'] = $reabrir_internacion;
                        //  si no se realiza la modificación de la internacion, cambia el flag
                        if (empty($reabrir_internacion) || empty($reabrir_internacion[0]->actualizado) || $reabrir_internacion[0]->actualizado <= 0){
                            $modifica_internacion = FALSE;
                        }else{
                            array_push($errors, 'No se pudo reabrir la internación');
                        }
                    }

                    //  si es una apertura de internacion (debe anular la internacion completa)
                    if (!empty($validacion[0]->id_accion_internacion) && $validacion[0]->id_accion_internacion == 1) {
                        //  cambia flag y establece valores
                        $es_anulacion_internacion = TRUE;
                        $slug_evento_internacion = 'anular-internacion';
                        $prefijo_slug = '-internacion';

                        //  anula la internacion
                        //  obtiene las prestacion de la internacion
                        array_push($extras['sps'], ['AWEB_TraerInternacionPrestaciones' => ['codigo_internacion' => $validacion[0]->codigo_internacion, 'filtrar_anuladas' => 1]]);
                        array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerInternacionPrestaciones', ['codigo_internacion' => $validacion[0]->codigo_internacion, 'filtrar_anuladas' => 1]));
                        $internacion_prestaciones = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerInternacionPrestaciones', ['codigo_internacion' => $validacion[0]->codigo_internacion, 'filtrar_anuladas' => 1]);
                        // return $internacion_prestaciones;
                        array_push($extras['responses'], ['AWEB_TraerInternacionPrestaciones' => $internacion_prestaciones]);
                        $data['prestaciones_internacion'] = $internacion_prestaciones;

                        if($internacion_prestaciones){
                            //  por cada prestacion de la internacion
                            foreach ( $internacion_prestaciones as $internacion_prestacion )
                            {
                                //  ejecuta la consulta
                                $params_sp_anular_internacion = [
                                    'codigo_interno' => $internacion_prestacion->codigo_interno,
                                    'id_usuario' => $logged_user['usuario'],
                                    'motivo' => $motivo,
                                ];
                                // return $params_sp_anular_internacion;
                                array_push($extras['sps'], ['AWEB_AnularAutorizacion' => $params_sp_anular_internacion]);
                                array_push($extras['queries'], $this->get_query('validacion', 'AWEB_AnularAutorizacion', $params_sp_anular_internacion));
                                $anulacion = $this->ejecutar_sp_directo('validacion', 'AWEB_AnularAutorizacion', $params_sp_anular_internacion);
                                // return $anulacion;
                                array_push($extras['responses'], ['AWEB_AnularAutorizacion...'.$internacion_prestacion->codigo_interno => $anulacion]);
                                $data['prestaciones_anuladas'][$internacion_prestacion->codigo_interno] = $anulacion;
                                //  si no se realiza la anulacion, cambia el flag (con que haya uno que no se haga, se hace rollback)
                                
                                if (!$anulacion){
                                    $code = -8;
                                    $status = 'fail';
                                    array_push($errors, 'No se pudo anular la prestación');
                                }else{ //  si se anula la validacion
                                    //  agrega el codigo interno a la lista de validaciones anuladas
                                    $validaciones_anuladas[] = $internacion_prestacion->codigo_interno;
                                }
                            }
                            $data['validaciones_anuladas'] = $validaciones_anuladas;
                        }
                        //  anula la internación
                        $params_anular_internacion = [
                            'codigo_internacion' => $validacion[0]->codigo_internacion,
                            'usuario' => $logged_user['usuario'],
                            'motivo' => $params['motivo'],
                        ];
                        
                        array_push($extras['sps'], ['AWEB_AnularInternacion' => $params_anular_internacion]);
                        array_push($extras['queries'], $this->get_query('validacion', 'AWEB_AnularInternacion', $params_anular_internacion));
                        $anulacion_internacion = $this->ejecutar_sp_directo('validacion', 'AWEB_AnularInternacion', $params_anular_internacion);
                        
                        array_push($extras['responses'], ['AWEB_AnularInternacion' => $params_anular_internacion]);
                        $data['internacion_anulada'] = $anulacion_internacion;
                        // return $anulacion_internacion;
                        if ($anulacion_internacion) {
                            //  establece los valores
                            $anula_internacion_completa = $anulacion_internacion[0]->anulado == 1 ? TRUE : FALSE;
                            array_push($validaciones_anuladas,  $validacion[0]->codigo_internacion);
                        }else{
                            array_push($errors, 'No se pudo anular la internación');
                        }
                    }
                }

                //  si todos los procesos se ejecutan
                if ($anula_validacion && $anula_internacion_completa && $modifica_internacion) {
                    //  confirma la transacción
                    get_commit('validacion');

                    //  dispara el evento
                    // foreach ($validaciones_anuladas as $codigo_interno_anulado){
                    //     Event::fire(new ValidacionEstado(Session::get('usuario.id_contrato'), $codigo_interno_anulado, $slug_origen = 'anular-validacion' . $prefijo_slug));
                    // }

                    //  si se anula una internación completa o se anula un egreso, dispara el evento
                    // if ($es_anulacion_internacion || $cambia_estado_internacion){
                    //     Event::fire(new InternacionEstado(Session::get('usuario.id_contrato'), $validacion[0]->codigo_internacion, $slug_evento_internacion));
                    // }

                    //  devuelve el resultado
                    $item = $es_anulacion_internacion ? 'internación' : 'validación';
                    $status = 'ok';
                    $count = 1;
                    $message = 'La '.$item.' con código interno '.$codigo_interno.' se anuló exitosamente.';
                 } else {
                    //  cancela la transacción
                    get_rollback('validacion');

                    $item = $es_anulacion_internacion ? 'internación' : 'validación';
                    $status = 'fail';
                    $count = 1;
                    $code = -7;
                    $message = 'No se pudo anular la '.$item.' con código interno '.$codigo_interno;
                }

                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => 1,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($permiso_requerido);
                $count  = -1;
                $data = null;
                array_push($errors, 'Error de permisos. '.$message);
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => -2,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => null,
                'extras' => $extras,
                'logged_user' => null,
            ]);
        }
    }

    /**
     * Anular PIC
     */
    public function anular_pic(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/validaciones/anular-pic';
        $this->permiso_requerido = 'gestionar validaciones';
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_anular_pic';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario
        $this->verificado = [
            'codigo_interno' => request('codigo_interno')
        ];
        if(empty(request('codigo_interno'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'codigo_interno' => request('codigo_interno')
        ];
        $this->params_sp = [
            'codigo_interno' => $this->params['codigo_interno']
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Guarda un observacion de una validacion
     */
    public function guardar_observacion_validacion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/validaciones/guardar-observacion-validacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail'; // 'ok', 'fail', 'empty', unauthorized', 'warning'  
        $message = '';
        $count = 0;
        $code = 0;
        $data = null;
        $errors = [];
        $params = [];
        $params_sp = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $permiso_requerido = 'gestionar validaciones';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $params = [
                    'codigo_interno' => request('codigo_interno'),
                    'observaciones_validacion' => request('observaciones_validacion') != '' ? request('observaciones_validacion') : null,
                    'observaciones_afiliado' => request('observaciones_afiliado') != '' ? request('observaciones_afiliado') : null,
                    'observaciones_efector' => request('observaciones_efector') != '' ? request('observaciones_efector') : null
                ];
                
                $sp = 'sp_autorizacion_texto_update';
                $db = 'validacion';
                $params_sp = [
                    'codigo_interno' => $params['codigo_interno'],
                    'txt_validacion' => $params['observaciones_validacion'],
                    'txt_afiliado' => $params['observaciones_afiliado'],
                    'txt_efector' => $params['observaciones_efector'],
                    'usuario' => $logged_user['usuario']
                ];
                array_push($extras['verificado'], [$sp => ['codigo_interno' => request('codigo_interno')]]);
                if ( empty(request('codigo_interno')) ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    array_push($extras['sps'], [$sp => $params_sp]);
                    array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
                    $response = $this->ejecutar_sp_directo($db, $sp, $params_sp);
                    array_push($extras['responses'], [$sp => $response]);
                    //     if(!empty($resp) && $resp[0]->filas > 0){
                    if(is_array($response) && array_key_exists('error', $response)){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -3;
                        // Log::channel('')->error(''); // buscar canales en config/loggin.php
                    }else if(empty($response)){
                        $status = 'empty';
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                        $count = 0;
                        $data = $response;
                        $code = -4;
                        // Log::channel('')->info(''); // buscar canales en config/loggin.php
                    }else{
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = sizeof($response);
                        $data = $response;
                        $code = 1;
                    }
                }
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => $code,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($permiso_requerido);
                $count  = -1;
                $data = null;
                array_push($errors, 'Error de permisos. '.$message);
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => -2,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }
    
}

