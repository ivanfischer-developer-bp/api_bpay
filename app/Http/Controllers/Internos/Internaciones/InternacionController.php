<?php

namespace App\Http\Controllers\Internos\Internaciones;

use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;

use App\Http\Controllers\ConexionSpController;
use App\Http\Controllers\Internos\Validaciones\ValidacionController;

class InternacionController extends ConexionSpController
{
    /**
     * Busca si un afiliado tiene una internación activa
     */
    public function buscar_internacion_abierta(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/internaciones/buscar-internacion-abierta';
        $this->permiso_requerido = 'consultar validaciones';
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'AWEB_TraerAfiliado';
        if(request('id_empresa') != null){
            $this->params['id_empresa'] = request('id_empresa');
            $this->params_sp['id_empresa'] = request('id_empresa');
        }
        if(request('nro_afiliado') != null){
            $this->params['afiliado'] = request('nro_afiliado');
            $this->params_sp['afiliado'] = request('nro_afiliado');
        }
        return $this->ejecutar_sp_simple();
    }

     /**
     * Busca los datos de una internación
     */
    public function buscar_internacion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/internaciones/buscar-internacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail'; // 'ok', 'fail', 'empty', unauthorized', 'warning'  
        $message = '';
        $count = -1;
        $code = null;
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
            $permiso_requerido = 'consultar validaciones';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                if(request('id_empresa') != null){
                    $params['id_empresa'] = request('id_empresa');
                    $params_sp['id_empresa'] = request('id_empresa');
                }
                if(request('id_usuario') != null){
                    $params['id_usuario'] = request('id_usuario');
                    $params_sp['id_usuario'] = request('id_usuario');
                }
                if(request('numero_afiliado') != null){
                    $params['afiliado'] = request('numero_afiliado');
                    $params_sp['afiliado'] = request('numero_afiliado');
                }
                if(request('tipo_documento') != null){
                    $params['tipo_documento'] = request('tipo_documento');
                    $params_sp['tipo_documento'] = request('tipo_documento');
                }
                if(request('numero_documento') != null){
                    $params['numero_documento'] = request('numero_documento');
                    $params_sp['numero_documento'] = request('numero_documento');
                }
                if(request('codigo_internacion') != null){
                    $params['codigo_internacion'] = request('codigo_internacion');
                    $params_sp['codigo_internacion'] = request('codigo_internacion');
                }
                
                $db = 'validacion'; // afiliacion, validacion, admin, alfabeta
                $sp = 'AWEB_TraerInternacion';
                array_push($extras['sps'], [$sp => $params_sp]);
                array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
                $response = $this->ejecutar_sp_directo($db, $sp, $params_sp);
                array_push($extras['responses'], [$sp => $response]);
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
                    $count = sizeof($response);
                    if(is_array($response) && sizeof($response) == 1){
                        $response = $response[0];
                        $count = 1;
                        if(strtolower($response->estado) == 'abierta'){
                            $response->fecha_egreso = '';
                            if($response->fecha_ingreso != null){
                                $response->fecha_egreso_desde = get_carbon_ymd($response->fecha_ingreso)->format('Y-m-d');
                                $response->fecha_egreso_hasta = get_carbon_ymd($response->fecha_ingreso)->addDays($response->cantidad_dias)->format('Y-m-d');
                            }else{
                                $response->fecha_egreso_desde = null;
                                $response->fecha_egreso_hasta = null;
                                // $r['original']->errors = [];
                                array_push($errors, 'La fecha de ingreso de la internación es nula');
                                $message = 'La fecha de ingreso de la internación es nula';
                                $status = 'warning';
                                $code = -4;
                            }
                            $response->numero_formateado = null;
                        }
                    }
                    $status = 'ok';
                    $message = 'Transacción realizada con éxito.';
                    $data = $response;
                    $code = 1;
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
     * Busca las acciones de una internacion
     */
    public function consultar_acciones_internacion(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/internaciones/consultar-acciones-internacion';
        $this->permiso_requerido = 'consultar validaciones';
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_internacion_accion_select';
        $this->params = [
            'codigo_internacion' => request('codigo_internacion'),
        ];
        $this->params_sp = [
            'codigo_internacion' => $this->params['codigo_internacion'],
        ];
        if(request('id_accion') != null){
            $this->params['id_accion'] = request('id_accion');
            $this->params_sp['id_accion'] = request('id_accion') == 0 ? null : request('id_accion');
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Emite una validacion de internacion
     */
    public function emitir_validacion_internacion(Request $request)
    {
        // se emite desde validaciones
    }

    /**
     * Genera el egreso de una internación
     */
    public function generar_egreso_internacion(Request $request)
    {
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/internaciones/generar-egreso-internacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail';
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
            date_default_timezone_set('America/Argentina/Cordoba');
            $permiso_requerido = 'gestionar internaciones';
            
            if($user->hasPermissionTo($permiso_requerido)){
                
                $egreso = request('egreso');
                $codigo_internacion = request('codigo_internacion');

                $params = [
                    'codigo_internacion' => $codigo_internacion,
                    'egreso' => $egreso
                ];
                
                if ( empty(request('egreso'))
                    || empty($egreso['tipo_egreso']) 
                    || empty($egreso['fecha_egreso']) 
                    || empty($egreso['diagnostico']) 
                    || empty($egreso['diagnostico']['cod_cie_item'])
                ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    array_push($extras['verificado'], [
                        'tipo_egreso' => $egreso['tipo_egreso'],
                        'fecha_egreso' => $egreso['fecha_egreso'],
                        'diagnostico' => $egreso['diagnostico']['cod_cie_item']
                    ]);
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    $fecha_egreso = !empty($egreso['fecha_egreso']) ? Carbon::parse($egreso['fecha_egreso'])->format('Y-m-d H:i:s') : Carbon::now()>format('Y-m-d H:i:s');
                    $datos_internacion = [
                        'id_tipo_accion' => 4,
                        'observaciones' => ( !empty($egreso['observaciones']) ? $egreso['observaciones'] : NULL ),
                        'Comentarios' => ((isset($egreso['observaciones']) && isset($egreso['observaciones']['validacion']) && $egreso['observaciones']['validacion'] != '') ? $egreso['observaciones']['validacion'] : NULL),
                        'Comentario_Afiliado' => ((isset($egreso['observaciones']) && isset($egreso['observaciones']['afiliado']) && $egreso['observaciones']['afiliado'] != '') ? $egreso['observaciones']['afiliado'] : NULL),
                        'Comentario_Efector' => ((isset($egreso['observaciones']) && isset($egreso['observaciones']['efector']) && $egreso['observaciones']['efector'] != '') ? $egreso['observaciones']['efector'] : NULL),
                        'adjunto' => ( !empty($egreso['adjunto']) ? $egreso['adjunto'] : NULL ),
                        'id_tipo_alta_internacion' => $egreso['tipo_egreso'],
                        'fecha_egreso' => $fecha_egreso,
                        'codigo_diagnostico_egreso' => $egreso['diagnostico']['cod_cie_item'],
                    ];
                    $slug_origen = 'egreso-internacion';
                    $slug_origen_validacion = 'egreso-automatico-internacion';
                    
                    $response_emitir = $this->emitir_internacion($codigo_internacion, $datos_internacion, $slug_origen, $slug_origen_validacion, $logged_user, $this);
                    array_push($extras['responses'], ['emitir_internacion' => $response_emitir]);
                    
                    $response = json_decode(json_encode($response_emitir))->original;
                    
                    $extras['queries'] = array_merge($extras['queries'], $response->extras->queries);
                    $extras['sps'] = array_merge($extras['sps'], $response->extras->sps);
                    $extras['responses'] = array_merge($extras['responses'], $response->extras->responses);
                    $extras['verificado'] = array_merge($extras['verificado'], $response->extras->verificado);
                    // if(sizeof($errors) > 0 && sizeof($response->errors) > 0){
                    //     array_merge($errors, $response->errors);
                    // }else{
                    //     $errors = $response->errors;
                    // }

                    if($response->code < 0){
                        $status = 'fail';
                        $message = $response->message;
                        $count = 0;
                        $data = null;
                        $code = $response->code != null ? $response->code : -3;
                    }else if(empty($response)){
                        $status = 'empty';
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                        $count = 0;
                        $data = $response->data;
                        $code = $response->code != null ? $response->code : -4;
                    }else{
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = 1;
                        $data = $response->data;
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
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Genera una prórroga de una internacion
     */
    public function generar_prorroga_automatica(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/internaciones/emitir-prestaciones-internacion',
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
            date_default_timezone_set('America/Argentina/Cordoba');
            $permiso_requerido = 'gestionar internaciones';
            if($user->hasPermissionTo($permiso_requerido)){
                $codigo_internacion = request('codigo_internacion');
                $internacion = request('internacion');
                $prorroga = request('prorroga');
                $params = [
                    'codigo_internacion' => $codigo_internacion,
                    'internacion' => $internacion,
                    'prorroga' => $prorroga
                ];
                $internacion['prestacion']['prestaciones'] = $prorroga['prestaciones'];
                $internacion['afiliado'] = $prorroga['afiliado'];
                $params_sp = [
                    'id_tipo_accion' => 2,
                    'observaciones' => ( !empty($prorroga['observaciones']) ? $prorroga['observaciones'] : NULL ),
                    'Comentarios' => ((isset($prorroga['observaciones']) && isset($prorroga['observaciones']['validacion']) && $prorroga['observaciones']['validacion'] != '') ? $prorroga['observaciones']['validacion'] : NULL),
                    'Comentario_Afiliado' => ((isset($prorroga['observaciones']) && isset($prorroga['observaciones']['afiliado']) && $prorroga['observaciones']['afiliado'] != '') ? $prorroga['observaciones']['afiliado'] : NULL),
                    'Comentario_Efector' => ((isset($prorroga['observaciones']) && isset($prorroga['observaciones']['efector']) && $prorroga['observaciones']['efector'] != '') ? $prorroga['observaciones']['efector'] : NULL),
                    'adjunto' => ( !empty($prorroga['adjunto']) ? $prorroga['adjunto'] : NULL ),
                    'cantidad_dias' => $prorroga['cantidad_dias'],
                    'prorroga_internacion' => $internacion,
                    'aud_admin' => !empty($prorroga['aud_admin']) ? $prorroga['aud_admin'] : null,
                    'motivo_aud_admin' => !empty($prorroga['motivo_aud_admin']) ? $prorroga['motivo_aud_admin'] : null
                ];
                
                array_push($extras['verificado'], [
                    'codigo_internacion' => $codigo_internacion,
                    'prorroga' => !empty($prorroga),  // verifica que prestacion exista y no esté vacío
                    'cantidad_dias' => !empty($prorroga['cantidad_dias']) ? $prorroga['cantidad_dias'] : false, // verifica que prestaciones exista y tenga al menos 1 elemento
                ]);
                if ( empty($codigo_internacion) 
                    || empty($prorroga) 
                    || empty($prorroga['cantidad_dias']) ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    $response = $this->emitir_internacion($codigo_internacion, $params_sp, 'prorroga-internacion', 'prorroga-internacion', $logged_user, $this);
                    
                    // return $response;

                    $ret = (array) json_decode(json_encode($respuesta));
                    $resp = $ret['original'];

                    $errors = $errors + $resp->errors;
                    $params = $params + $resp->params;
                    $extras['responses'] = $extras['responses'] + $resp->extras->responses;
                    $extras['sps'] = $extras['sps'] + $resp->extras->sps;
                    $extras['queries'] = $extras['queries'] + $resp->extras->queries;
                    $extras['verificado'] = $extras['verificado'] + $resp->extras->verificado;
                    $data = $resp->data;
                    $message = $resp->message;
                    $status = $resp->status;
                    $code = $resp->code;
                    $count = $resp->count; 
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
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Genera una prestacion en una internacion abierta
     */
    public function emitir_prestaciones_internacion(Request $request)
    {
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/internaciones/emitir-prestaciones-internacion',
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
            date_default_timezone_set('America/Argentina/Cordoba');
            $permiso_requerido = 'gestionar internaciones';
            if($user->hasPermissionTo($permiso_requerido)){
                $codigo_internacion = request('codigo_internacion');
                $prestacion = request('prestacion');
                $params = [
                    'codigo_internacion' => $codigo_internacion,
                    'prestacion' => $prestacion
                ];
                
                $params_sp = [
                    'id_tipo_accion' => 3,
                    'observaciones' => ( !empty($prestacion['observaciones']) ? $prestacion['observaciones'] : NULL ),
                    'Comentarios' => ((isset($prestacion['observaciones']) && isset($prestacion['observaciones']['validacion']) && $prestacion['observaciones']['validacion'] != '') ? $prestacion['observaciones']['validacion'] : NULL),
                    'Comentario_Afiliado' => ((isset($prestacion['observaciones']) && isset($prestacion['observaciones']['afiliado']) && $prestacion['observaciones']['afiliado'] != '') ? $prestacion['observaciones']['afiliado'] : NULL),
                    'Comentario_Efector' => ((isset($prestacion['observaciones']) && isset($prestacion['observaciones']['efector']) && $prestacion['observaciones']['efector'] != '') ? $prestacion['observaciones']['efector'] : NULL),
                    'adjunto' => ( !empty($prestacion['adjunto']) ? $prestacion['adjunto'] : NULL ),
                    'id_prescriptor' => $prestacion['prescriptor']['id_prestadores'],
                    'id_efector' => $prestacion['efector']['id_prestadores'],
                    'id_convenio' => $prestacion['centro']['id_convenio'],
                    'zona' => $prestacion['zona'],
                    'aud_admin' => !empty($prestacion['aud_admin']) ? $prestacion['aud_admin'] : null,
                    'motivo_aud_admin' => !empty($prestacion['motivo_aud_admin']) ? $prestacion['motivo_aud_admin'] : null,
                    'prestaciones' => $prestacion['prestaciones'],
                    'afiliado' => $prestacion['afiliado'],  // el modelo afiliado
                ];

                array_push($extras['verificado'], [
                    'codigo_internacion' => $codigo_internacion,
                    'prestacion' => !empty($prestacion),  // verifica que prestacion exista y no esté vacío
                    'prestaciones' => !empty($prestacion['prestaciones']) ? sizeof($prestacion['prestaciones']) : false, // verifica que prestaciones exista y tenga al menos 1 elemento
                    'prescriptor' => !empty($prestacion['prescriptor']), // verifica que prescriptor exista y no esté vacío
                    'efector' => !empty($prestacion['efector']), // verifica que efector exista y no esté vacío
                    'centro' => !empty($prestacion['centro']), // verifica que centro exista y no esté vacío
                    'prescriptor->id_prestadores' => $prestacion['prescriptor']['id_prestadores'],
                    'efector->id_prestadores' => $prestacion['efector']['id_prestadores'],
                    'centro->id_convenio' => $prestacion['centro']['id_convenio'],
                ]);
                if ( empty($codigo_internacion) 
                    || empty($prestacion) 
                    || empty($prestacion['prestaciones']) 
                    || count($prestacion['prestaciones']) <= 0 
                    || empty($prestacion['prescriptor']) 
                    || empty($prestacion['efector']) 
                    || empty($prestacion['centro']) 
                    || empty($prestacion['prescriptor']['id_prestadores']) 
                    || empty($prestacion['efector']['id_prestadores']) 
                    || empty($prestacion['centro']['id_convenio'])  ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    $response = $this->emitir_internacion($codigo_internacion, $params_sp, 'prestaciones-internacion', 'prestaciones-internacion', $logged_user, $this);
                    
                    return $response;

                    $ret = (array) json_decode(json_encode($respuesta));
                    $resp = $ret['original'];

                    $errors = $errors + $resp->errors;
                    $params = $params + $resp->params;
                    $extras['responses'] = $extras['responses'] + $resp->extras->responses;
                    $extras['sps'] = $extras['sps'] + $resp->extras->sps;
                    $extras['queries'] = $extras['queries'] + $resp->extras->queries;
                    $extras['verificado'] = $extras['verificado'] + $resp->extras->verificado;
                    $data = $resp->data;
                    $message = $resp->message;
                    $status = $resp->status;
                    $code = $resp->code;
                    $count = $resp->count; 
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
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Abre una internación vencida
     */
    public function abrir_internacion_vencida(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/internaciones/abrir-internacion-vencida';
        $this->permiso_requerido = 'gestionar internaciones';
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_internacion_abrir';
        $this->params = [
            'codigo_internacion' => request('codigo_internacion')
        ];
        $this->params_sp = [
            'codigo_internacion' => $this->params['codigo_internacion']
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Asocia una validacion a una internacion
     */
    public function asociar_validacion(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/internaciones/asociar-validacion';
        $this->permiso_requerido = 'gestionar internaciones';
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'AWEB_AsociarValidacionInternacion';
        $this->verificado = [
            'codigo_internacion' => request('codigo_internacion'), 
            'codigo_interno' => request('codigo_interno')
        ];
        if(empty(request('codigo_internacion')) || empty(request('codigo_interno'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'codigo_internacion' => request('codigo_internacion'),
            'codigo_interno' => request('codigo_interno')
        ];
        $this->params_sp = [
            'codigo_internacion' => $this->params['codigo_internacion'],
            'codigo_interno' => $this->params['codigo_interno']
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Emite una internacion
     * Funcion para uso externo a la clase utilizada en AuditoriaTerrenoController
     * y tambien utilizada por la funcion generar_egreso_internacion de esta clase
     */
    public function emitir_internacion($codigo_internacion, $datos_internacion, $slug_origen, $slug_origen_validacion, $logged_user, $controlador)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail';
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $params = [];
        $params_sp = ['codigo_internacion' => $codigo_internacion];
        
        $slug_origen_validacion = empty($slug_origen_validacion) ? $slug_origen : $slug_origen_validacion;
        $dias_vencimiento = 60;
        $vencimiento = Carbon::now()->addDays($dias_vencimiento)->format('Ymd');
        
        array_push($extras['sps'], ['AWEB_TraerInternacion' => $params_sp]);
        array_push($extras['queries'], $controlador->get_query('validacion', 'AWEB_TraerInternacion', $params_sp));
        $resp_internacion = $controlador->ejecutar_sp_directo('validacion', 'AWEB_TraerInternacion', $params_sp);
        array_push($extras['responses'], ['AWEB_TraerInternacion' => $resp_internacion]);
        if(is_array($resp_internacion) && sizeof($resp_internacion) == 1){
            $resp_internacion = $resp_internacion[0];
        }
        
        if($resp_internacion){
            //  si ya tiene egreso
            if(strtolower($resp_internacion->estado) != 'abierta' && $slug_origen != 'reabrir-internacion-domiciliaria'){
                
                return response()->json([
                    'status' => 'fail',
                    'count' => 0,
                    'errors' => ['Internación Cerrada'],
                    'message' => 'La internación está cerrada.',
                    'line' => null,
                    'code' => -40,
                    'data' => [],
                    'params' => $datos_internacion,
                    'extras' => $extras,
                    'logged_user' => null,
                ]); 
            }
            $cantidad_dias = $slug_origen == 'prorroga-internacion' ? $datos_internacion['cantidad_dias'] : 1;
            if($slug_origen == 'prestaciones-internacion'){
                $id_convenio = $datos_internacion['id_convenio'];
            }else{
                $id_convenio = $resp_internacion->id_convenio;
            }
            // $id_convenio = $slug_origen == 'prestaciones-internacion' ? $datos_internacion['id_convenio'] : $resp_internacion->id_convenio;
            if ( !isset($datos_internacion['prestaciones']) ){
                $datos_internacion['prestaciones'] = [];
            }
            //  si es un egreso de internacion
            if($slug_origen == 'egreso-internacion'){
                //  establece las fechas configuradas para chequear la fecha de egreso
                $fecha_egreso_desde = get_carbon_ymd($resp_internacion->fecha_ingreso)->format('Ymd');
                $fecha_egreso_hasta = get_carbon_ymd($resp_internacion->fecha_ingreso)->addDays($resp_internacion->cantidad_dias)->format('Ymd');
                $fecha_egreso_obj = Carbon::parse($datos_internacion['fecha_egreso'])->format('Ymd');

                //  se validan las fechas de las prórrogas solo si no es un cierre automático
                //  y si tiene permisos para no exigir prórrogas
                //  la fecha de egreso debe ser mayor que la fecha de ingreso y menor que la fecha de ingreso + cantidad_dias
                array_push($extras['verificado'], [
                    'origen_validacion' => $slug_origen_validacion,
                    'fecha_egreso menor que fecha_ingreso' => Carbon::parse($fecha_egreso_obj)->lt(Carbon::parse($fecha_egreso_desde)),
                    'fecha_egreso mayor que fecha_ingreso mas cantidad_dias' => Carbon::parse($fecha_egreso_obj)->gt(Carbon::parse($fecha_egreso_hasta))
                ]);
                if($slug_origen_validacion != 'egreso-automatico-internacion-vencida'
                    && $slug_origen_validacion != 'egreso-automatico-internacion'
                    // && !oh_tiene_permiso('validaciones.internaciones.no-exigir-prorrogas-en-egreso')
                    && (Carbon::parse($fecha_egreso_obj)->lt(Carbon::parse($fecha_egreso_desde))
                        || Carbon::parse($fecha_egreso_obj)->gt(Carbon::parse($fecha_egreso_hasta))
                    )
                ){
                    return response()->json([
                        'status' => 'fail',
                        'count' => 0,
                        'errors' => ['Error de fechas. Fecha Ingreso: '.$fecha_egreso_desde.' Fecha Egreso: '.$fecha_egreso_obj],
                        'message' => 'La fecha de egreso no puede ser menor a la fecha de ingreso ni mayor al ingreso mas la cantidad de practicas 430101 solicitadas y autorizadas. Fecha Ingreso: '.Carbon::parse($fecha_egreso_desde)->format('d/m/Y').' Tipo Internación: '.$resp_internacion->tipo_internacion,
                        'line' => null,
                        'code' => -20,
                        'data' => [],
                        'params' => $datos_internacion,
                        'extras' => $extras,
                        'logged_user' => null,
                    ]);  
                }
            }
            // return $datos_internacion['id_tipo_alta_internacion'];
            //  establece los parametros para emitir la validación
            $params = [
                //  datos de la cabecera
                'Sucursal' => $resp_internacion->id_sucursal,
                'Empresa' => $resp_internacion->id_empresa,
                'Afiliado' => $resp_internacion->afiliado, // numero de afiliado
                // 'Diagnostico' => ( ( $slug_origen == 'reabrir-internacion-domiciliaria' ) ? $resp_internacion->codigo_diagnostico_ingreso : '' ),
                'Diagnostico' => ( ( $slug_origen == 'reabrir-internacion-domiciliaria' ) ? $resp_internacion->codigo_diagnostico_ingreso : (isset($resp_internacion->diagnostico) ? $resp_internacion->diagnostico : '') ),
                'Comentarios' => ( !empty($datos_internacion['Comentarios']) ? $datos_internacion['Comentarios'] : NULL ),
                'Fecha_Vencimiento' => $vencimiento,
                'usuario' => $logged_user['usuario'],

                //  prestadores
                'Prestador_Prescriptor' => ( ( $slug_origen == 'prestaciones-internacion' ) ? $datos_internacion['id_prescriptor'] : $resp_internacion->id_prescriptor ),
                'Prestador' => ( ( $slug_origen == 'prestaciones-internacion' ) ? $datos_internacion['id_efector'] : $resp_internacion->id_efector ),
                'Convenio' => $id_convenio,

                //  internacion
                'Codigo_Internacion' => ( ( $slug_origen == 'reabrir-internacion-domiciliaria' ) ? '' : $codigo_internacion ),
                'id_tipo_accion' => $datos_internacion['id_tipo_accion'],

                //  ingreso
                'id_tipo_internacion' => $resp_internacion->id_tipo_internacion,
                'prioridad' => ( ( $slug_origen == 'reabrir-internacion-domiciliaria' ) ? 'urgente' : NULL ),
                'pediatrica' => 0,
                'fecha_ingreso' => ( ( $slug_origen == 'reabrir-internacion-domiciliaria' ) ? $datos_internacion['fecha_ingreso'] : '' ),
                'fpe' => ( ( $slug_origen == 'reabrir-internacion-domiciliaria' ) ? $datos_internacion['fecha_probable_egreso'] : NULL ),

                //  egreso
                'id_tipo_alta_internacion' => ( ( $slug_origen == 'egreso-internacion' ) ? $datos_internacion['id_tipo_alta_internacion'] : NULL ),
                'fecha_egreso' => ( ( $slug_origen == 'egreso-internacion' ) ? $datos_internacion['fecha_egreso'] : NULL ),
                'codigo_diagnostico_egreso' => ( ( $slug_origen == 'egreso-internacion' ) ? ( empty($datos_internacion['codigo_diagnostico_egreso']) ? $resp_internacion->codigo_diagnostico_ingreso : $datos_internacion['codigo_diagnostico_egreso'] ) : NULL ),

                //  el resto :P
                'Control'                => 0,
                'guardia'                => 0,
                'callcenter'             => 0,
                'elegibilidad'           => 0,
                'edicion'                => 0,
                'ID_Suplementos'         => '',
                'Fecha_BajaPlanCronico'  => '',
                'codigo_interno_edicion' => '',
                'cantidad_dias' => $resp_internacion->cantidad_dias,
                // datos par emitir prestacion en internaciones
                'ambulatorio' => 0, // no esta
                'internacion' => 1, // no esta
                'Comentario_Afiliado' => ( !empty($datos_internacion['Comentario_Afiliado']) ? $datos_internacion['Comentario_Afiliado'] : NULL ), 
                'Comentario_Efector' => ( !empty($datos_internacion['Comentario_Efector']) ? $datos_internacion['Comentario_Efector'] : NULL ), 
                'p_rechazo' => 0, // no esta
                'p_motivo' => '', // no esta
                'audita_nivel' => '', // no esta 
                'n_contacto' => !empty($datos_internacion['afiliado']) && !empty($datos_internacion['afiliado']['n_contacto']) ? $datos_internacion['afiliado']['n_contacto'] : null,
                'n_domicilio' => !empty($datos_internacion['afiliado']) && !empty($datos_internacion['afiliado']['n_domicilio']) ? $datos_internacion['afiliado']['n_domicilio'] : null, 
                'fecha_prescripcion' => Carbon::now()->format('Ymd'),
                'fecha_realizacion' => !empty($resp_internacion->fecha_ingreso) ? Carbon::parse($resp_internacion->fecha_ingreso)->format('Ymd') : null, // no esta
                'auditoria_administrativa' => !empty($datos_internacion['aud_admin']) ? $datos_internacion['aud_admin'] : 0, 
                'zona' => !empty($datos_internacion['zona']) ? $datos_internacion['zona'] : null
            ];
            
            for ( $i=1; $i<=20; $i++ ){
                $k = $i - 1;
                if(!empty($datos_internacion['prorroga_internacion']) && $k < sizeof($datos_internacion['prorroga_internacion']['prestacion']['prestaciones'])){
                    if(!empty($datos_internacion['prorroga_internacion']) && !empty($datos_internacion['prorroga_internacion']['prestacion']) && !empty($datos_internacion['prorroga_internacion']['prestacion']['prestaciones'])){
                        $params['Cantidad' . $i] = $datos_internacion['prorroga_internacion']['prestacion']['prestaciones'][$k]['cantidad'];
                        $params['Practica' . $i] = $datos_internacion['prorroga_internacion']['prestacion']['prestaciones'][$k]['codigo_nomenclador'];
                        $params['Frecuencia' . $i] = !empty($datos_internacion['prorroga_internacion']['prestacion']['prestaciones'][$k]['frecuencia']) ? $datos_internacion['prorroga_internacion']['prestacion']['prestaciones'][$k]['frecuencia'] : null;
                        $params['Frecuencia_cantidad' . $i] = !empty($datos_internacion['prorroga_internacion']['prestacion']['prestaciones'][$k]['frecuencia_cantidad']) ? $datos_internacion['prorroga_internacion']['prestacion']['prestaciones'][$k]['frecuencia_cantidad'] : null;
                        $params['Finaliza' . $i] = !empty($datos_internacion['prorroga_internacion']['prestacion']['prestaciones'][$k]['finaliza']) ? $datos_internacion['prorroga_internacion']['prestacion']['prestaciones'][$k]['finaliza'] : null;
                        $params['Retiro' . $i] = !empty($datos_internacion['prorroga_internacion']['prestacion']['prestaciones'][$k]['retiro']) ? $datos_internacion['prorroga_internacion']['prestacion']['prestaciones'][$k]['retiro'] : null;
                    }
                }else if(!empty($datos_internacion['prestaciones']) && $k < sizeof($datos_internacion['prestaciones'])){
                    // return $datos_internacion;
                    $params['Cantidad' . $i] = !empty($datos_internacion['prestaciones'][$k]['cantidad']) ? $datos_internacion['prestaciones'][$k]['cantidad'] : null;
                    $params['Practica' . $i] = !empty($datos_internacion['prestaciones'][$k]['codigo_nomenclador']) ? $datos_internacion['prestaciones'][$k]['codigo_nomenclador'] : null;
                    $params['Frecuencia' . $i] =  !empty($datos_internacion['prestaciones'][$k]['frecuencia']['id_frecuencia']) ? $datos_internacion['prestaciones'][$k]['frecuencia']['id_frecuencia'] : null;
                    $params['Frecuencia_cantidad' . $i] = !empty($datos_internacion['prestaciones'][$k]['frecuencia']['cantidad']) ? $datos_internacion['prestaciones'][$k]['frecuencia']['cantidad'] : null;
                    $params['Finaliza' . $i] = !empty($datos_internacion['prestaciones'][$k]['finaliza']) ? $datos_internacion['prestaciones'][$k]['finaliza'] : null;
                    $params['Retiro' . $i] = !empty($datos_internacion['prestaciones'][$k]['retiro']) ? $datos_internacion['prestaciones'][$k]['retiro'] : null;
                }else{
                    $params['Cantidad' . $i] = 0;
                    $params['Practica' . $i] = 0;
                    $params['Frecuencia' . $i] = null;
                    $params['Frecuencia_cantidad' . $i] = null;
                    $params['Finaliza' . $i] = null;
                    $params['Retiro' . $i] = null;
                }
            }
            
            $observaciones = NULL;
            $adjunto = empty($datos_internacion['adjunto']) ? NULL : $datos_internacion['adjunto'];
            $datos_extras = [
                'n_persona' => $resp_internacion->nombre,
                'id_tipo_doc' => $resp_internacion->id_tipo_documento,
                'nro_doc' => $resp_internacion->documento,
                'tipo_documento' => $resp_internacion->tipo_documento
            ];
            
            $validacion_emitida = ValidacionController::emitir($datos_extras, $params, $observaciones, $adjunto, $slug_origen_validacion, TRUE, $logged_user, $controlador);
            $response = json_decode(json_encode($validacion_emitida));
            array_push($extras['responses'], ['emitir_validacion', $validacion_emitida]);
            $extras['queries'] = array_merge($extras['queries'], $response->extras->queries);
            $extras['sps'] = array_merge($extras['sps'], $response->extras->sps);
            $extras['responses'] = array_merge($extras['responses'], $response->extras->responses);
            $extras['verificado'] = array_merge($extras['verificado'], $response->extras->verificado);
            
            $data = $response->data;
            $status = $response->status;
            $message = $response->message;
            $count = $response->count;
            $errors = $response->errors;
            // if(sizeof($errors) > 0 && sizeof($response->errors) > 0){
            //     array_merge($errors, $response->errors);
            // }else{
            //     $errors = $response->errors;
            // }
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => 10,
                'data' => $data,
                'params' => [
                    'params_internacion' => $params_sp,
                    'params_emitir_validacion' =>$params
                ],
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);  
        }else{
            $code = -10;
            $message = 'No existe la internación.';
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' =>  $code,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }
}


