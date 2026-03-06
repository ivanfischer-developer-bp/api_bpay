<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use File;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;

class ABMPatologiaController extends ConexionSpController
{
    /**
     * Lista las terapéuticas especiales (patologias) disponibles en el sistema
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function listar_patologias(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/listados/patologia/listar-patologias';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_patologia_select';
        if(request('is_cobertura') != null){
            $this->params['is_cobertura'] = request('is_cobertura');
            $this->params_sp['p_isCobertura'] = request('is_cobertura');
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega una nueva terapéutica especial (patología)
     */
    public function agregar_patologia(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/listados/patologia/agregar-patologia',
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
            $permiso_requerido = 'gestionar listados';
            if($user->hasPermissionTo($permiso_requerido)){
                $params = [
                    'copago'  => ( !empty(request('copago')) && request('copago') == 1) ? 1 : 0,
                    'coseguro'  => ( !empty(request('coseguro')) && request('coseguro') == 1) ? 1 : 0,
                    'fija_valor'  => ( !empty(request('fija_valor')) && request('fija_valor') == 1) ? 1 : 0,
                    'frecuencia' => !empty(request('frecuencia')) ? strtoupper(request('frecuencia')) : 'BAJA',
                    'id_concepto' => request('id_concepto'),
                    'importe_recargo' => request('importe_recargo'),
                    'isCobertura' => ( !empty(request('isCobertura')) && request('isCobertura') == 1 ) ? 1 : 0,
                    'isCronica'  => ( !empty(request('isCronica')) && request('isCronica') == 1) ? 1 : 0,
                    'isDiscapacidad'  => ( !empty(request('isDiscapacidad')) && request('isDiscapacidad') == 1) ? 1 : 0,
                    'n_patologia'  => request('n_patologia'),
                    'porcentaje_recargo' => request('porcentaje_recargo'),
                ];
                $params_sp = [
                    'p_n_patologia'  => request('n_patologia'),
                    'p_id_concepto' => request('id_concepto'),
                    'p_importe_recargo' => request('importe_recargo'),
                    'p_porcentaje_recargo' => request('porcentaje_recargo'),
                    'p_isCronica'  => ( !empty(request('isCronica')) && request('isCronica') == 1) ? 1 : 0 ,
                    'p_isDiscapacidad'  => ( !empty(request('isDiscapacidad')) && request('isDiscapacidad') == 1) ? 1 : 0 ,
                    'p_isCobertura' => ( !empty(request('isCobertura')) && request('isCobertura') == 1 ) ? 1 : 0,
                    'p_fija_valor'  => ( !empty(request('fija_valor')) && request('fija_valor') == 1) ? 1 : 0 ,
                    'p_coseguro'  => ( !empty(request('coseguro')) && request('coseguro') == 1) ? 1 : 0 ,
                    'p_copago'  => ( !empty(request('copago')) && request('copago') == 1) ? 1 : 0 ,
                    'p_frecuencia' => !empty(request('frecuencia')) ? strtoupper(request('frecuencia')) : 'BAJA',
                ];
                
                if ( empty(request('n_patologia')) ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    array_push($extras['verificado'], ['n_patologia' => request('n_patologia')]);
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    array_push($extras['sps'], ['sp_patologia_insert' => $params_sp]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_patologia_insert', $params_sp));
                    $response = $this->ejecutar_sp_directo('afiliacion','sp_patologia_insert', $params_sp);
                    array_push($extras['responses'], ['sp_patologia_insert' => $response]);
                    if(is_array($response) && array_key_exists('error', $response)){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -3;
                    }else{
                        // guardamos los conectos
                        $ps_sp = [
                            'p_id_patologia' => $response[0]->id,
                            'p_id_concepto' => request('id_concepto'),
                            'p_id_usuario' => $id_usuario,
                            'p_fecha' => Carbon::now()->format('Ymd H:i:s')
                        ];
                        array_push($extras['sps'], ['sp_patologia_concepto_insert' => $ps_sp]);
                        array_push($extras['queries'], $this->get_query('afiliacion', 'sp_patologia_concepto_insert', $ps_sp));
                        $resp_concepto = $this->ejecutar_sp_directo('afiliacion','sp_patologia_concepto_insert', $ps_sp);
                        array_push($extras['responses'], ['sp_patologia_concepto_insert' => $resp_concepto]);
                        // guardamos los regarcos
                        $resp_importe = 'no se ejecutó el store procedure.';
                        // comentado porque no está funcionando bien el sp, duplica las entradas
                        // $p_sp = [
                        //     'p_id_patologia' => $response[0]->id,
                        //     'p_importe' => request('importe_recargo'),
                        //     'p_id_usuario' => $id_usuario,
                        //     'p_fecha' => Carbon::now()->format('Ymd H:i:s')
                        // ];
                        // array_push($extras['sps'], ['sp_patologia_recargo_insert' => $p_sp]);
                        // array_push($extras['queries'], $this->get_query('afiliacion', 'sp_patologia_recargo_insert', $p_sp));
                        // $resp_importe = $this->ejecutar_sp_directo('afiliacion','sp_patologia_recargo_insert', $p_sp);
                        // array_push($extras['responses'], ['sp_patologia_recargo_insert' => $resp_importe]);
                        
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = 1;
                        $data = [
                            'patologia' => $response,
                            'concepto' => $resp_concepto,
                            'importe' => $resp_importe
                        ];
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
     * Actualiza una terapéutica especial (patología)
     */
    public function actualizar_patologia(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/listados/patologia/actualizar-patologia',
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
            $permiso_requerido = 'gestionar listados';
            if($user->hasPermissionTo($permiso_requerido)){
                $params = [
                    'copago'  => ( !empty(request('copago')) && request('copago') == 1) ? 1 : 0,
                    'coseguro'  => ( !empty(request('coseguro')) && request('coseguro') == 1) ? 1 : 0,
                    'diagnosticos' => request('diagnosticos'),
                    'fija_valor'  => ( !empty(request('fija_valor')) && request('fija_valor') == 1) ? 1 : 0,
                    'frecuencia' => !empty(request('frecuencia')) ? strtoupper(request('frecuencia')) : 'BAJA',
                    'id_concepto' => request('id_concepto'),
                    'id_patologia' => request('id_patologia'),
                    'importe_recargo' => request('importe_recargo'),
                    'isCobertura' => ( !empty(request('isCobertura')) && request('isCobertura') == 1 ) ? 1 : 0,
                    'isCronica'  => ( !empty(request('isCronica')) && request('isCronica') == 1) ? 1 : 0,
                    'isDiscapacidad'  => ( !empty(request('isDiscapacidad')) && request('isDiscapacidad') == 1) ? 1 : 0,
                    'n_patologia'  => request('n_patologia'),
                    'porcentaje_recargo' => request('porcentaje_recargo'),
                ];
                $params_sp = [
                    'p_id_patologia' => request('id_patologia'),
                    'p_n_patologia'  => request('n_patologia'),
                    'p_id_concepto' => request('id_concepto'),
                    'p_importe_recargo' => request('importe_recargo'),
                    'p_porcentaje_recargo' => request('porcentaje_recargo'),
                    'p_isCronica'  => ( !empty(request('isCronica')) && request('isCronica') == 1) ? 1 : 0 ,
                    'p_isDiscapacidad'  => ( !empty(request('isDiscapacidad')) && request('isDiscapacidad') == 1) ? 1 : 0 ,
                    'p_isCobertura' => ( !empty(request('isCobertura')) && request('isCobertura') == 1 ) ? 1 : 0,
                    'p_fija_valor'  => ( !empty(request('fija_valor')) && request('fija_valor') == 1) ? 1 : 0 ,
                    'p_coseguro'  => ( !empty(request('coseguro')) && request('coseguro') == 1) ? 1 : 0 ,
                    'p_copago'  => ( !empty(request('copago')) && request('copago') == 1) ? 1 : 0 ,
                    'p_frecuencia' => !empty(request('frecuencia')) ? strtoupper(request('frecuencia')) : 'BAJA',
                ];
                
                if ( empty(request('id_patologia')) || empty(request('n_patologia')) ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    array_push($extras['verificado'], [
                        'id_patologia' => request('id_patologia'),
                        'n_patologia' => request('n_patologia')
                    ]);
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    array_push($extras['sps'], ['sp_patologia_update' => $params_sp]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_patologia_update', $params_sp));
                    $response = $this->ejecutar_sp_directo('afiliacion','sp_patologia_update', $params_sp);
                    array_push($extras['responses'], ['sp_patologia_update' => $response]);
                    if(is_array($response) && array_key_exists('error', $response)){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -3;
                    }else{
                        $resp_importe = 'no se ejecutó el store procedure.';
                        // comentado porque no está funcionando bien el sp, duplica las entradas
                        // $p_sp = [
                        //     'p_id_patologia' => request('id_patologia'),
                        //     'p_importe' => request('importe_recargo'),
                        //     'p_id_usuario' => $id_usuario,
                        //     'p_fecha' => Carbon::now()->format('Ymd H:i:s')
                        // ];
                        // array_push($extras['sps'], ['sp_patologia_recargo_insert' => $p_sp]);
                        // array_push($extras['queries'], $this->get_query('afiliacion', 'sp_patologia_recargo_insert', $p_sp));
                        // $resp_importe = $this->ejecutar_sp_directo('afiliacion','sp_patologia_recargo_insert', $p_sp);
                        // array_push($extras['responses'], ['sp_patologia_recargo_insert' => $resp_importe]);
                       
                        $resp_diagnosticos = [];
                        if(is_array(request('diagnosticos')) && sizeof(request('diagnosticos')) > 0){
                            foreach ( request('diagnosticos') as $diagnostico ){
                                if(!isset($diagnostico['id_vigencia'])){
                                    $params_diagnostico = [
                                        'p_id_patologia' => request('id_patologia'),
                                        'p_id_cie_item' => $diagnostico['id_cie_item'],
                                        'p_id_usuario' => $id_usuario,
                                        'p_fecha' => Carbon::now()->format('Ymd H:i:s')
                                    ];
                                    array_push($extras['sps'], ['sp_patologia_cie10_insert' => $params_diagnostico]);
                                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_patologia_cie10_insert', $params_diagnostico));
                                    $ret_relacion_diagnosticos = $this->ejecutar_sp_directo('afiliacion','sp_patologia_cie10_insert', $params_diagnostico);
                                    array_push($extras['responses'], ['sp_patologia_cie10_insert' => $ret_relacion_diagnosticos]);
                                    array_push($resp_diagnosticos, $ret_relacion_diagnosticos);
                                }
                            }
                        }

                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = 1;
                        $data = [
                            'patologia' => $response,
                            'importe' => $resp_importe,
                            'diagnosticos_agregados' => $resp_diagnosticos,
                        ];
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

    ##########################################################################################
    ################################ DIAGNÓSTICO PATOLOGIA #################################
    ##########################################################################################

    /**
     * Lista los diagnósticos de una patología
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function listar_diagnosticos_patologia(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/listados/patologia/listar-diagnosticos-patologia';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_patologia_cie10_select';
        $this->param_id_usuario = '';
        $this->tipo_id_usuario = ''; // id, usuario, email, param
        $this->param_usuario = '';
        if(request('id_patologia') != null){
            $this->params['id_patologia'] = request('id_patologia');
            $this->params_sp['p_id_patologia'] = request('id_patologia');
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Añade Diagnósticos a una patología
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_diagnostico_patologia(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/listados/patologia/agregar-diagnostico-patologia';
        $this->permiso_requerido = 'gestionar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_patologia_cie10_insert';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'p_id_usuario';
        $this->params = [
            'id_patologia' => request('id_patologia'),
            'id_cie_item' => request('id_cie_item'),
        ];
        $this->params_sp = [
            'p_id_patologia' => $this->params['id_patologia'],
            'p_id_cie_item' => $this->params['id_cie_item'],
            'p_fecha' => Carbon::now()->format('Ymd H:i:s'),
        ];
        return $this->ejecutar_sp_simple();
    }

    ##########################################################################################
    ################################ DOCUMENTACIÓN PATOLOGIA #################################
    ##########################################################################################

     /**
     * Lista la documentación de una patología
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function listar_documentacion_patologias(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/listados/patologia/listar-documentacion-patologias';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_patologia_documentacion_select';
        if(request('id_patologia') != null){
            $this->params['id_patologia'] = request('id_patologia');
            $this->params_sp['p_id_patologia'] = request('id_patologia');
        }
        return $this->ejecutar_sp_simple();
    }

     /**
     * Agrega una documentación a una patología
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_documentacion_patologia(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/listados/patologia/agregar-documentacion-patologia';
        $this->permiso_requerido = 'gestionar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_patologia_documentacion_insert';
        $this->param_id_usuario = 'p_id_usuario';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->params = [
            'id_patologia' => request('id_patologia'),
            'id_documentacion' => request('id_documentacion'),
        ];
        $this->params_sp = [
            'p_id_patologia' => $this->params['id_patologia'],
            'p_id_documentacion' => $this->params['id_documentacion'],
            'p_fecha' => Carbon::now()->format('Ymd H:i:s'),
        ];
        $ret = $this->ejecutar_sp_simple();
        $r = (array) json_decode(json_encode($ret));
        $d = $r['original']->data;
        // formateamos los valores
        if($d[0]->id < 1){
            // $this->status = 'fail';
            $r['original']->status = 'fail';
            array_push($r['original']->errors, $d[0]->msg);
            $r['original']->message = 'No se pudo agregar la documentación';
        }
        return response()->json($r['original']);
        // return $this->ejecutar_sp_simple();
    }

     /**
     * Agrega una documentación a una patología
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function quitar_documentacion_patologia(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/listados/patologia/quitar-documentacion-patologia';
        $this->permiso_requerido = 'gestionar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_baja_Insert';
        $this->param_id_usuario = 'id_usuario';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->params = [
            'id_vigencia' => request('id_vigencia'),
        ];
        $this->params_sp = [
            'id_vigencia' => $this->params['id_vigencia'],
            'id_tipo_baja' => 1,
            'n_baja' => 'Baja documentacion patologia',
            'fec_baja' => Carbon::now()->format('Ymd H:i:s'),
        ];
        return $this->ejecutar_sp_simple();
    }

    ##########################################################################################
    ################################ PRESTACIONES PATOLOGIA #################################
    ##########################################################################################

    /**
     * Obtiene un listado de presetaciones para asociar a una patología
     */
    public function listar_prestaciones_patologia(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/listados/patologia/listar-prestaciones-patologia';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_prestacion_select';
        if(request('id_prestacion') != null){
            $this->params['id_prestacion'] = request('id_prestacion');
            $this->params_sp['p_id_prestacion'] = request('id_prestacion');
        }
        if(request('id_tipo_prestacion') != null){
            $this->params['id_tipo_prestacion'] = request('id_tipo_prestacion');
            $this->params_sp['p_id_tipo_prestacion'] = request('id_tipo_prestacion');
        }
        if(request('cod_prestacion') != null){
            $this->params['cod_prestacion'] = request('cod_prestacion');
            $this->params_sp['p_cod_prestacion'] = request('cod_prestacion');
        }
        if(request('n_prestacion') != null){
            $this->params['n_prestacion'] = request('n_prestacion');
            $this->params_sp['p_n_prestacion'] = request('n_prestacion');
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Obtiene un listado de prestaciones asociadas a un diagnostico de una patología
     */
    public function buscar_prestaciones_patologia(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/listados/patologia/buscar-prestaciones-patologia';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_patologia_prestacion_select';
        $this->params['id_patologia_cie10'] = request('id_patologia_cie10');
        $this->params_sp['p_id_patologia_cie10'] = request('id_patologia_cie10');
        return $this->ejecutar_sp_simple();
    }

    /**
     * Asocia una prestacion a un diagnostico de una patologia
     */
    public function agregar_prestaciones_patologia(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/listados/patologia/agregar-prestaciones-patologia';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_patologia_prestacion_insert';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'p_id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario
        $this->params = [
            'id_patologia_cie10' => request('id_patologia_cie10'),
            'id_prestacion' => request('id_prestacion'),
        ];
        $this->params_sp = [
            'p_id_patologia_cie10' => $this->params['id_patologia_cie10'],
            'p_id_prestacion' => $this->params['id_prestacion'],
            'p_fecha' => Carbon::now()->format('Ymd H:i:s'),
        ];
        $ret = $this->ejecutar_sp_simple();
        $r = (array) json_decode(json_encode($ret));
        $d = $r['original']->data;
        if($d[0]->id < 0){
            array_push($r['original']->errors, $d[0]->msg);
            $r['original']->status = 'fail';
            $r['original']->message = 'No se pudo guardar la prestacion. Error en sp.';
            $r['original']->data = null;
            $r['original']->code = -5;
        }
        return response()->json($r['original']);
    }

    ##########################################################################################
    ################################ PERSONA PATOLOGIA #######################################
    ##########################################################################################

    /**
     * Lista las terapéuticas especiales (patologias) de una persona
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function listar_persona_patologias(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/afiliaciones/persona/listar-persona-patologias';
        $this->permiso_requerido = 'gestionar afiliaciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_persona_patologia_select';
        $this->param_id_usuario = '';
        $this->tipo_id_usuario = ''; // id, usuario, email, param
        $this->param_usuario = '';
        if(request('id_persona') != null){
            $this->params['id_persona'] = request('id_persona');
            $this->params_sp['p_id_persona'] = request('id_persona');
        }
        // necesitamos modificar los valores devueltos
        $ret = $this->ejecutar_sp_simple(); 
        // return $ret;
        // return $ret;
        $r = (array) json_decode(json_encode($ret));
        $d = $r['original']->data;
        // return $r;
        // formateamos los valores
        if(is_array($d) && sizeof($d) > 0){
            foreach ( $d as $item ){
                if($item->importe_recargo != null){
                    $item->importe_recargo = number_format($item->importe_recargo, 2, ',', '.');
                }
                if($item->porcentaje != null){
                    $item->porcentaje = number_format($item->porcentaje, 2, ',', '.');
                }
                // if($item->fec_vto != null){
                //     $item->fec_vto = Carbon::parse($item->fec_vto)->format('d/m/Y');  // se formatea en el front
                // }
                $temp_data[] = $item;
            }
            $r['original']->data = $temp_data;
            return response()->json($r['original']);
        }else{
            return response()->json($r['original']);
        }
    }

    /**
     * Guarda una patología de una persona
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function guardar_persona_patologia(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/afiliaciones/persona/guardar-persona-patologia';
        $this->permiso_requerido = 'gestionar afiliados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->param_id_usuario = '';
        $this->tipo_id_usuario = ''; // id, usuario, email, param
        $this->param_usuario = '';
        $this->params = [
            'cod_certificado' => request('cod_certificado'),
            'fec_vto' => request('fec_vto'),
            'id_patologia_cie10' => request('id_patologia_cie10'),
            'id_persona' => request('id_persona'),
            'id_persona_patologia' => request('id_persona_patologia'),
            'permanente' => request('permanente'),
            'prevalente' => request('prevalente'),
            'porcentaje' => request('porcentaje'),
            'importe_recargo' => request('importe_recargo')
        ];
        $this->params_sp = [
            'p_fec_vto' => $this->params['fec_vto'],
            'p_id_patologia_cie10' => $this->params['id_patologia_cie10'],
            'p_id_persona' => $this->params['id_persona'],
            'p_permanente' => $this->params['permanente'],
            'p_prevalente' => $this->params['prevalente'],
            'p_porcentaje' => $this->params['porcentaje'],
            'p_importe_recargo' => $this->params['importe_recargo'],
            'p_cod_certificado' => $this->params['cod_certificado']
        ];
        if($this->params['id_persona_patologia'] == null){
            $this->param_id_usuario = 'p_id_usuario';
            $this->tipo_id_usuario = 'id'; // id, usuario, email, param
            $this->params_sp['p_fecha'] = Carbon::now()->format('Ymd');
            $this->sp = 'sp_persona_patologia_insert';
        }else{
            $this->params_sp['p_id_persona_patologia'] = $this->params['id_persona_patologia'];
            $this->sp = 'sp_persona_patologia_update';
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Elimina una terapéutica especial (patología) de una persona
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function eliminar_persona_patologia(Request $request)
    {
        
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/afiliaciones/persona/eliminar-persona-patologia';
        $this->permiso_requerido = 'gestionar afiliados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_baja_Insert';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro id_usuario, id_n_usuadio
        $this->param_usuario = ''; // el parámetro cuando no es el id o el nombre
        $this->params = [
            'id_vigencia' => request('id_vigencia'),
        ];
        $this->params_sp = [
            'id_tipo_baja' => !isset($this->params['id_tipo_baja']) ? 1 : $this->params['id_tipo_baja'],
            'fec_baja' => Carbon::now()->format('Ymd H:i:s'),
            'id_vigencia' => $this->params['id_vigencia'],
            'n_baja' => !isset($this->params['observaciones']) ? 'Baja de terapéutica especial' : $this->params['observaciones'],
        ];
        return $this->ejecutar_sp_simple();
    }

}

