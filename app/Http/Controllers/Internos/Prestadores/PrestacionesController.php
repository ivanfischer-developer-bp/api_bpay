<?php

namespace App\Http\Controllers\Internos\Prestadores;

use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;

use App\Http\Controllers\ConexionSpController;

class PrestacionesController extends ConexionSpController
{

    public function consultar_prestaciones_validacion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/validaciones/consultar-prestaciones-validacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => ['AWEB_TraerAutorizacionDetalle'],
            'responses' => [],
            'queries' => []
        ];
        try {
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            // variables de respuesta
            $status = 'null';
            $count = -1;
            $errors = [];
            $message = 'sin comentarios';
            $line = null;
            $code = null;
            $data = null;

            $codigo_interno = request('codigo_interno');
            $this->params = [
                'codigo_interno' => $codigo_interno
            ];
            //  ejecuta la consulta
            array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerAutorizacionDetalle', null));
            $response =  $this->ejecutar_sp_directo('validacion','AWEB_TraerAutorizacionDetalle', null);
            array_push($extras['responses'], ['AWEB_TraerAutorizacionDetalle' => $response]);
            if(isset($response['error'])){
                array_push($errors, $response['error']);
                $status = 'fail';
                $message = 'Fallo al realizar la consulta a la base de datos';
            }else{
                $count = sizeof($response);
                $status = 'ok';
                $message = 'Registros encontrados';
                $data = $response;
            }
            // retorna el response
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => $line,
                'code' => $code,
                'data' => $data,
                'params' => $this->params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]); 
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }

    public function consultar_prestaciones_internacion(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/internaciones/consultar-prestaciones-internacion';
        $this->permiso_requerido = 'consultar validaciones';
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'AWEB_TraerInternacionPrestaciones';
        
        $this->params = [
            'codigo_internacion' => request('codigo_internacion')
        ];
        $this->params_sp = [
            'codigo_internacion' => $this->params['codigo_internacion']
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Busca validaciones para asociar a una internacion
     */
    public function buscar_asociables_internacion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/internaciones/buscar-asociables-internacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail';  // 'ok', 'fail', 'empty', unauthorized', 'warning'  
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
                $params = [
                    'codigo_internacion' => request('codigo_internacion')
                ];
                
                $params_sp = [
                    'codigo_internacion' => request('codigo_internacion')
                ];

                if ( empty(request('codigo_internacion')) ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    array_push($extras['verificado'], ['codigo_internacion' => request('codigo_internacion')]);
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    array_push($extras['sps'], ['AWEB_TraerInternacion' => $params_sp]);
                    array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerInternacion', $params_sp));
                    $internacion = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerInternacion', $params_sp);
                    array_push($extras['responses'], ['AWEB_TraerInternacion' => $internacion]);
                    if(is_array($internacion)){
                        $internacion = $internacion[0];
                    }
                    if(is_array($internacion) && array_key_exists('error', $internacion)){
                        array_push($errors, $internacion['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -3;
                        // Log::channel('')->error(''); // buscar canales en config/loggin.php
                    }else if(empty($internacion)){
                        $status = 'empty';
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                        $count = 0;
                        $data = $internacion;
                        $code = -4;
                        // Log::channel('')->info(''); // buscar canales en config/loggin.php
                    }else{
                        $filtros = [
                            'afiliado' => $internacion->afiliado,
                            'traer_asociables' => 1,
                        ];
                        array_push($extras['sps'], ['AWEB_TraerAutorizaciones' => $filtros]);
                        array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerAutorizaciones', $filtros));
                        $response = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerAutorizaciones', $filtros);
                        array_push($extras['responses'], ['AWEB_TraerAutorizaciones' => $response]);
                        if(is_array($response) && array_key_exists('error', $response)){
                            array_push($errors, $response['error']);
                            $status = 'fail';
                            $message = 'Se produjo un error al realizar la petición';
                            $count = 0;
                            $data = null;
                            $code = -6;
                            // Log::channel('')->error(''); // buscar canales en config/loggin.php
                        }else if(empty($response)){
                            $status = 'empty';
                            $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                            $count = 0;
                            $data = $response;
                            $code = -7;
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