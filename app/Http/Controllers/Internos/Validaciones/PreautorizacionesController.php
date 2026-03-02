<?php

namespace App\Http\Controllers\Internos\Validaciones    ;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

use App\Http\Controllers\ConexionSpController;

use App\Models\User;
use App\Models\ConnectedUser;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PreautorizacionesController extends ConexionSpController 
{

    /**
     * Obtiene un listado de preautorizaciones
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function listar_preautorizaciones(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/validaciones/preautorizaciones/listar-preautorizaciones',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $errors = [];
        $data = null;
        $status = null;
        $code = 0;
        $count = 0;
        $line = null;
        $params = [];
        $message = null;
        $logged_user = null;

        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario_sqlserver = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

        try {

            $permiso_requerido = '';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                if($id_usuario_sqlserver != 0){
                    $fecha_desde = request('fecha_desde') != null && request('fecha_desde') != 'null' ? Carbon::parse(request('fecha_desde'))->format('Ymd') : null;
                    $fecha_hasta = request('fecha_hasta') != null && request('fecha_hasta') != 'null' ? Carbon::parse(request('fecha_hasta'))->format('Ymd') : null;
                    
                    $sp = 'sp_pre_autorizaciones_select';
                    $db = 'validacion';

                    $params = [
                        'id_pre_autorizacion' => request('id_pre_autorizacion'),
                        'id_persona' => request('id_persona'),
                        'fecha_desde' => request('fecha_desde'),
                        'fecha_hasta' => request('fecha_hasta'),
                        'id_estado_preautorizacion' => request('id_estado_preautorizacion')
                    ];

                    $params_sp = [
                        'id_pre_autorizacion' => request('id_pre_autorizacion') != null ? intval(request('id_pre_autorizacion')) : null,
                        'id_persona' => request('id_persona') != null ? intval(request('id_persona')) : null,
                        'fec_desde' => $fecha_desde,
                        'fec_hasta' => $fecha_hasta,
                        'id_estado_preautorizacion' => request('id_estado_preautorizacion') != null ? intval(request('id_estado_preautorizacion')) : null,
                    ];
                    
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
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = sizeof($response);
                        $data = $response;
                        $code = 1;
                    }
                }else{
                    $status = 'fail';
                    $code = -3;
                    $count = 0;
                    $message = 'El usuario logueado no tiene id_usuario_sqlserver';
                    array_push($errors, 'El usuario logueado no tiene id_usuario_sqlserver');
                }

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
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Cambia el estado de una preautorización
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function cambiar_estado_preautorizacion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/validaciones/preautorizaciones/cambiar-estado-preautorizacion',
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
            $permiso_requerido = '';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $params = [
                    'id_pre_autorizacion' => request('id_pre_autorizacion'),
                    'id_estado_preautorizacion' => request('id_estado_preautorizacion')
                ];
                
                $sp = 'sp_pre_autorizaciones_estado_update';
                $db = 'validacion';
                $params_sp = [
                    'id_pre_autorizacion' => $params['id_pre_autorizacion'],
                    'id_estado_preautorizacion' => $params['id_estado_preautorizacion'],
                ];
                array_push($extras['verificado'], [
                    $sp => [
                        'id_pre_autorizacion' => request('id_pre_autorizacion'),
                        'id_estado_preautorizacion' => request('id_estado_preautorizacion')
                    ]
                ]);
                if ( empty(request('id_pre_autorizacion')) || empty(request('id_estado_preautorizacion')) ){
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