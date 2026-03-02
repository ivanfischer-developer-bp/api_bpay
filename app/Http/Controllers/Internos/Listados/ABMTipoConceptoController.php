<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;

use DB;


class ABMTipoConceptoController extends ConexionSpController
{

    /**
     * Obtiene un listado de los tipos de conceptos disponibles en el sistema
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function listar_tipos_conceptos(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/tipo-concepto/listar-tipos-conceptos';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_tipo_concepto_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Obtiene un listado de gravámenes
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function buscar_gravamenes(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/tipo-concepto/buscar-gravamenes';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_tipo_concepto_gravamen_Select';
        $this->params = [
            'id_tipo_concepto' => request('id_tipo_concepto')
        ];
        $this->params_sp = [
            'id_tipo_concepto' => $this->params['id_tipo_concepto']
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega un nuevo tipo de concepto
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_tipo_concepto(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/listados/tipo-concepto/agregar-tipo-concepto',
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
        $params_sp = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $permiso_requerido = 'realizar configuraciones';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $params = [
                    'n_tipo_concepto' => request('n_tipo_concepto'),
                    'facturable' => request('facturable'),
                    'debito' => request('debito'),
                    'id_usuario' => $id_usuario,
                    'fecha' => request('fec_vigencia'),
                    'gravamenes' => request('gravamenes')
                ];
                $params_sp = [
                    'n_tipo_concepto' => $params['n_tipo_concepto'],
                    'facturable' => ( !empty($item['facturable']) && $params['facturable'] == 1 ) ? 1 : 0,
                    'debito' => ( !empty($params['debito']) && $params['debito'] == 1 ) ? 1 : 0,
                    'id_usuario' => $id_usuario,
                    'fecha' => $params['fecha']
                ];
                array_push($extras['verificado'], [
                    'sp_tipo_concepto_Insert' => [
                        'n_tipo_concepto' => request('n_tipo_concepto')
                        ]
                    ]
                );
                if ( empty(request('n_tipo_concepto')) ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    DB::beginTransaction();

                    array_push($extras['sps'], ['sp_tipo_concepto_Insert' => $params_sp]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_tipo_concepto_Insert', $params_sp));
                    $response = $this->ejecutar_sp_directo('afiliacion', 'sp_tipo_concepto_Insert', $params_sp);
                    array_push($extras['responses'], ['sp_tipo_concepto_Insert' => $response]);

                    if(is_array($response) && array_key_exists('error', $response)){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -3;
                        DB::rollBack();
                        // Log::channel('')->error(''); // buscar canales en config/loggin.php
                    }else if(empty($response)){
                        $status = 'empty';
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                        $count = 0;
                        $data = $response;
                        $code = -4;
                        DB::rollBack();
                        // Log::channel('')->info(''); // buscar canales en config/loggin.php
                    }else{
                        $gravamen_ok = true;
                        if ( !empty($params['gravamenes']) && count($params['gravamenes']) > 0 ){
                            foreach ( $params['gravamenes'] as $id_gravamen ){
                                $params_sp_gravamen = [
                                    'id_tipo_concepto' => $response[0]->id_tipo_concepto,
                                    'id_gravamen' => $id_gravamen,
                                    'id_usuario' => $id_usuario,
                                    'fecha' => Carbon::now()->format('Ymd H:i:s')
                                ];
                                array_push($extras['sps'], ['sp_tipo_concepto_gravamen_Insert' => $params_sp_gravamen]);
                                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_tipo_concepto_gravamen_Insert', $params_sp_gravamen));
                                $ret_tipo_concepto_gravamen = $this->ejecutar_sp_directo('afiliacion', 'sp_tipo_concepto_gravamen_Insert', $params_sp_gravamen);
                                array_push($extras['responses'], ['sp_tipo_concepto_gravamen_Insert' => $ret_tipo_concepto_gravamen]);

                                if ( empty($ret_tipo_concepto_gravamen) 
                                    || empty($ret_tipo_concepto_gravamen[0]) 
                                    || $ret_tipo_concepto_gravamen[0]->id_tipo_concepto_gravamen < 1
                                    ){
                                    $gravamen_ok = false;
                                }
                            }
                        }

                        if ( $gravamen_ok){
                            $status = 'ok';
                            $message = 'Transacción realizada con éxito.';
                            $count = 1;
                            $data = [
                                'tipo_concepto_response' => $response,
                                'gravamen_response' => $ret_tipo_concepto_gravamen
                            ];
                            $code = 1;
                            DB::commit();
                        } else{
                            $status = 'fail';
                            $message = 'No se pudo agregar el gravamen al tipo de concepto';
                            $count = 0;
                            $data = [
                                'tipo_concepto_response' => $response,
                                'gravamen_response' => $ret_tipo_concepto_gravamen
                            ];
                            $code = -6;
                            array_push($errors, 'No se pudo agregar el gravamen al tipo de concepto');
                            DB::rollBack();
                        }
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
                'code' => -1,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }  
    }

    /**
     * Actualiza un tipo de concepto existente
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function actualizar_tipo_concepto(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '',
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
        $params_sp = [];
        $gravamenes_finalizados = [];
        $gravamenes_insertados = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $permiso_requerido = 'realizar configuraciones';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $params = [
                    'id_tipo_concepto' => request('id_tipo_concepto'),  
                    'n_tipo_concepto' => request('n_tipo_concepto'),
                    'facturable' => request('facturable'),
            	    'debito' => request('debito'),
                    'gravamenes' => request('gravamenes')
                ];
                $params_sp = [
                    'id_tipo_concepto' => $params['id_tipo_concepto'],  
                    'n_tipo_concepto' => $params['n_tipo_concepto'],
                    'facturable' => ( !empty($params['facturable']) && $params['facturable'] == 1 ) ? 1 : 0,
            	    'debito' => ( !empty($params['debito']) && $params['debito'] == 1 ) ? 1 : 0,
                ];

                array_push($extras['sps'], ['sp_tipo_concepto_Update' => $params_sp]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_tipo_concepto_Update', $params_sp));
                $response = $this->ejecutar_sp_directo('afiliacion', 'sp_tipo_concepto_Update', $params_sp);
                array_push($extras['responses'], ['sp_tipo_concepto_Update' => $response]);

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
                
                    array_push($extras['sps'], ['sp_tipo_concepto_gravamen_Select' => ['id_tipo_concepto' => $params['id_tipo_concepto']]]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_tipo_concepto_gravamen_Select', ['id_tipo_concepto' => $params['id_tipo_concepto']]));
                    $gravamenes_actuales = $this->ejecutar_sp_directo('afiliacion', 'sp_tipo_concepto_gravamen_Select', ['id_tipo_concepto' => $params['id_tipo_concepto']]);
                    array_push($extras['responses'], ['sp_tipo_concepto_gravamen_Select' => $gravamenes_actuales]);

                    if(!empty($gravamenes_actuales) && is_array($gravamenes_actuales) && count($gravamenes_actuales) > 0){
                        foreach ($gravamenes_actuales as $gravamen_actual) {
                            if (!in_array($gravamen_actual->id_gravamen, $params['gravamenes'])) {
                                // Llama al stored procedure ssp_finalizar
                                $params_sp_finalizar = [
                                    'id_tipo_baja' => 1,
                                    'id_usuario' => $id_usuario,
                                    'fec_baja' => Carbon::now()->format('Ymd H:i:s'),
                                    'id_vigencia' => $gravamen_actual->id_vigencia,
                                    'n_baja' => 'cambio de datos',
                                ];
                                array_push($extras['sps'], ['sp_baja_Insert' => $params_sp_finalizar]);
                                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_baja_Insert', $params_sp_finalizar));
                                $baja = $this->ejecutar_sp_directo('afiliacion', 'sp_baja_Insert', $params_sp_finalizar);
                                array_push($extras['responses'], ['sp_baja_Insert' => $baja]);
                                if(is_array($baja) && !empty($baja) && $baja[0]->id_baja > 0){
                                    array_push($gravamenes_finalizados, $gravamen_actual);
                                }
                            }
                        }
                    }

                    foreach ($params['gravamenes'] as $id_gravamen) {
                        if (!in_array($id_gravamen, $gravamenes_actuales)) {
                            // Llama al stored procedure ssp_finalizar
                            $params_sp_gravamen = [
                                'id_tipo_concepto' => $params['id_tipo_concepto'],
                                'id_gravamen' => $id_gravamen,
                                'id_usuario' => $id_usuario,
                                'fecha' => Carbon::now()->format('Ymd H:i:s')
                            ];
                            array_push($extras['sps'], ['sp_tipo_concepto_gravamen_Insert' => $params_sp_gravamen]);
                            array_push($extras['queries'], $this->get_query('afiliacion', 'sp_tipo_concepto_gravamen_Insert', $params_sp_gravamen));
                            $gravamen_response = $this->ejecutar_sp_directo('afiliacion', 'sp_tipo_concepto_gravamen_Insert', $params_sp_gravamen);
                            array_push($extras['responses'], ['sp_tipo_concepto_gravamen_Insert' => $gravamen_response]);
                            if(is_array($gravamen_response) && !empty($gravamen_response) && $gravamen_response[0]->id_tipo_concepto_gravamen > 0){
                                array_push($gravamenes_insertados, $gravamen_response[0]->id_tipo_concepto_gravamen);
                            }
                        }
                    }

                    $status = 'ok';
                    $message = 'Transacción realizada con éxito.';
                    $count = 1;
                    $data = [
                        'tipo_concepto_actualizado' => $response,
                        'gravamenes_finalizados' => $gravamenes_finalizados,
                        'gravamenes_insertados' => $gravamenes_insertados
                    ];
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

}