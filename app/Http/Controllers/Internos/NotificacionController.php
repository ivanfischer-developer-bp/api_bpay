<?php

namespace App\Http\Controllers\Internos;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
// use Illuminate\Support\Facades\File as FacadeFile;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use File;
use Storage;
use Carbon\Carbon;

class NotificacionController extends ConexionSpController
{
    /**
     * Busca las notificaciones
     */
    public function buscar_notificaciones(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/notificaciones/buscar-notificaciones';
        $this->permiso_requerido = '';
        $this->db = 'admin'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_notificacion_select';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'p_id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario
        
        if(request('last_3') != null){
            $this->params['last_3'] = request('last_3');
            $this->params_sp['p_last_3'] = request('last_3');
        }
        if(request('id_contrato') != null){
            $this->params['id_contrato'] = request('id_contrato');
            $this->params_sp['p_id_contrato'] = request('id_contrato');
        }
        if(request('notificado') != null){
            $this->params['notificado'] = request('notificado');
            $this->params_sp['p_notificado'] = request('notificado');
        }
        if(request('id') != null){
            $this->params['id'] = request('id');
            $this->params_sp['p_id'] = request('id');
        }
    
        $usuario = $this->get_logged_user($request->user());
        if($usuario['id_usuario_sqlserver'] == null){
            return response()->json([
                'status' => 'empty',
                'count' => 0,
                'errors' => [],
                'message' => 'El usuario no tiene identificación en sqlserver',
                'line' => null,
                'code' => 2,
                'data' => [],
                'params' => $this->params,
                'extras' => $this->extras,
                'logged_user' => $usuario,
            ]);
        }
        
        // formateamos los valores    
        $ret = $this->ejecutar_sp_simple();
        $r = (array) json_decode(json_encode($ret));
        $d = $r['original'];
        if(sizeof($d->data) > 1000){
            $d->count = sizeof($d->data);
            $data = array_slice($d->data, 0, 1000);
            $d->data = $data;
        }
        $r['original'] = $d;
        
        return response()->json($r['original']);
    }

    /**
     * Marca una notificación como leída cambiando su parametro notificado  1
     */
    public function marcar_como_leida(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/notificaciones/marcar-como-leida',
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
                    'id' => request('id'),
                ];
                $db = 'admin'; // afiliacion, validacion, admin, alfabeta
                $sp = 'sp_notificacion_select';
                $params_sp = [
                    'p_id' => $params['id'],
                ];
                array_push($extras['verificado'], [$sp => ['p_id' => request('id')]]);
                if ( empty(request('id')) ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    
                    array_push($extras['sps'], [$sp => $params_sp]);
                    array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
                    $notificacion = $this->ejecutar_sp_directo($db, $sp, $params_sp);
                    array_push($extras['responses'], [$sp => $notificacion]);
                    if(is_array($notificacion) && array_key_exists('error', $notificacion)){
                        array_push($errors, $notificacion['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -3;
                        // Log::channel('')->error(''); // buscar canales en config/loggin.php
                    }else if(empty($notificacion)){
                        $status = 'empty';
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                        $count = 0;
                        $data = $notificacion;
                        $code = -4;
                        // Log::channel('')->info(''); // buscar canales en config/loggin.php
                    }else{
                        // procedemos a marcar como leída la notificación
                        $notificacion = $this->ejecutar_sp_directo('admin', 'sp_notificacion_select', $this->params_sp);
                        $notificacion = $notificacion[0];
                        $db = 'admin';
                        $sp = 'sp_notificacion_update';
                        $params_sp = [
                            'p_id_contrato' => $notificacion->id_contrato,
                            'p_id_usuario' => $notificacion->id_usuario,
                            'p_texto_notificacion' => $notificacion->texto_notificacion,
                            'p_notificado' => 1,
                            'p_id' => $params['id'],
                            'p_asunto' => $notificacion->asunto
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

    /**
     * Elimina una notificacion
     */
    public function eliminar_notificacion(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/notificaciones/eliminar-notificacion';
        $this->permiso_requerido = '';
        $this->db = 'admin'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_notificacion_eliminar';
        $this->params = [
            'id_notificacion' => request('id_notificacion')
        ];
        $this->params_sp = [
            'p_id' => $this->params['id_notificacion'],
            'p_notificado' => 99
        ];
        return $this->ejecutar_sp_simple();
    }
}