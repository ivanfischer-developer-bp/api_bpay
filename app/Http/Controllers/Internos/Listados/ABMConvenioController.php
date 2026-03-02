<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;


class ABMConvenioController extends ConexionSpController
{

    /**
     * Busca un convenio por alguno de los parámetros
     * Obtiene un listado de los convenios que coinciden con los parámetros dados
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function buscar_convenio(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/listados/convenio/buscar-convenio';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_convenio_Select';
        if(request('cuit') != null){
            $this->params['cuit'] = request('cuit');
            $this->params_sp['p_cuit'] = request('cuit');
        }
        if(request('id_empresa') != null){
            $this->params['id_empresa'] = request('id_empresa');
            $this->params_sp['p_id_empresa'] = request('id_empresa');
        }
        if(request('id_convenio') != null){
            $this->params['id_convenio'] = request('id_convenio');
            $this->params_sp['p_convenio'] = request('id_convenio');
        }
        if(request('nrofinanciador') != null){
            $this->params['nrofinanciador'] = request('nrofinanciador');
            $this->params_sp['nrofinanciador'] = request('nrofinanciador');
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega un nuevo convenio
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_convenio(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/listados/convenio/agregar-convenio';
        $this->permiso_requerido = 'gestionar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_convenio_Insert';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro id_usuario, id_n_usuario
        $this->verificado = [
            'n_convenio' => request('n_convenio'), 
            'id_empresa' => request('id_empresa')
        ];
        if(empty(request('n_convenio')) || empty(request('id_empresa'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }
        if(request('n_convenio') != null){
            $this->params['n_convenio'] = request('n_convenio');
            $this->params_sp['n_convenio'] = request('n_convenio');
        }
        if(request('id_empresa') != null){
            $this->params['id_empresa'] = request('id_empresa');
            $this->params_sp['id_empresa'] = request('id_empresa');
        }
        if(request('nrofinanciador') != null){
            $this->params['nrofinanciador'] = request('nrofinanciador');
            $this->params_sp['nrofinanciador'] = request('nrofinanciador');
        }
        $this->params_sp['fecha'] = Carbon::now()->format('Ymd H:i:s');
        return $this->ejecutar_sp_simple();
    }

    /**
     * Actualiza los datos de un convenio
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function actualizar_convenio(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/listados/convenio/actualizar-convenio';
        $this->permiso_requerido = 'gestionar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_convenio_Update';
        $this->verificado = [
            'id_convenio' => request('id_convenio'),
            'n_convenio' => request('n_convenio'), 
            'id_empresa' => request('id_empresa')
        ];
        if(empty(request('n_convenio')) || empty(request('id_empresa')) || empty(request('id_convenio'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params['id_convenio'] = request('id_convenio');
        $this->params_sp['id_convenio'] = request('id_convenio');
        if(request('n_convenio') != null){
            $this->params['n_convenio'] = request('n_convenio');
            $this->params_sp['n_convenio'] = request('n_convenio');
        }
        if(request('id_empresa') != null){
            $this->params['id_empresa'] = request('id_empresa');
            $this->params_sp['id_empresa'] = request('id_empresa');
        }
        if(request('nrofinanciador') != null){
            $this->params['nrofinanciador'] = request('nrofinanciador');
            $this->params_sp['nrofinanciador'] = request('nrofinanciador');
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Sincroniza un convenio actualizándolo si existe y creándolo si no
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function sincronizar_convenio(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/listados/convenios/sincronizar-convenio',
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
            $permiso_requerido = 'gestionar listados';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $params = [
                    'nrofinanciador' => request('nrofinanciador'),
                    'n_convenio' => request('n_convenio'),
                    'id_empresa' => request('id_empresa')
                ];
                
                $params_sp = [];
                $data = [
                    'agregado' => null,
                    'actualizado' => null,
                    'convenios' => []
                ];
                // verifica los parametros
                if ( empty(request('nrofinanciador')) || empty(request('n_convenio')) || empty(request('id_empresa'))){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    array_push($extras['verificado'], [
                        'nrofinanciador' => request('nrofinanciador'),
                        'n_convenio' => request('n_convenio'), 
                        'id_empresa' => request('id_empresa')
                    ]);
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -4;
                }else{
                    // busca si hay un convenio con ese nrofinanciador
                    $params_sp = [
                        'nrofinanciador' => request('nrofinanciador')
                    ];
                    array_push($extras['sps'], ['sp_convenio_Select' => $params_sp]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_convenio_Select', $params_sp));
                    $response_convenio = $this->ejecutar_sp_directo('afiliacion', 'sp_convenio_Select', $params_sp);
                    array_push($extras['responses'], ['sp_convenio_Select' => $response_convenio]);
                
                    if(!empty($response_convenio)){
                        $response_convenio = $response_convenio[0];
                    }

                    if(is_array($response_convenio) && array_key_exists('error', $response_convenio)){
                        // si da error se devuelve el error y no se sigue adelante
                        array_push($errors, $response_convenio['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al sincronizar el convenio';
                        $count = 0;
                        $data = null;
                        $code = -3;
                    }else if(empty($response_convenio)){
                        // si no hay convenio se crea
                        $params_sp = [
                            'nrofinanciador' => request('nrofinanciador'),
                            'n_convenio' => request('n_convenio'),
                            'id_empresa' => request('id_empresa')
                        ];
                        array_push($extras['sps'], ['sp_convenio_Insert' => $params_sp]);
                        array_push($extras['queries'], $this->get_query('afiliacion', 'sp_convenio_Insert', $params_sp));
                        $response = $this->ejecutar_sp_directo('afiliacion', 'sp_convenio_Insert', $params_sp);
                        array_push($extras['responses'], ['sp_convenio_Insert' => $response]);
                        
                        if(is_array($response) && array_key_exists('error', $response)){
                            array_push($errors, $response['error']);
                            $status = 'fail';
                            $message = 'Se produjo un error al agregar el convenio';
                            $count = 0;
                            $data['agregado'] = null;
                            $code = -5;
                            // Log::channel('')->error(''); // buscar canales en config/loggin.php
                        }else if(empty($response)){
                            $status = 'empty';
                            $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                            $count = 0;
                            $data['agregado'] = $response;
                            $code = -6;
                            // Log::channel('')->info(''); // buscar canales en config/loggin.php
                        }else{
                            $status = 'ok';
                            $message = 'Convenio agregado con éxito.';
                            $count = sizeof($response);
                            $data['agregado'] = $response;
                            $code = 1;
                        } 
                    }else{
                        // si se encuentra un convenio se actualiza
                        $params_sp = [
                            'id_convenio' => $response_convenio->id_convenio,
                            'nrofinanciador' => request('nrofinanciador'),
                            'n_convenio' => request('n_convenio'),
                            'id_empresa' => request('id_empresa')
                        ];
                        array_push($extras['sps'], ['sp_convenio_Update' => $params_sp]);
                        array_push($extras['queries'], $this->get_query('afiliacion', 'sp_convenio_Update', $params_sp));
                        $response = $this->ejecutar_sp_directo('afiliacion', 'sp_convenio_Update', $params_sp);
                        array_push($extras['responses'], ['sp_convenio_Update' => $response]);
                        if(is_array($response) && array_key_exists('error', $response)){
                            array_push($errors, $response['error']);
                            $status = 'fail';
                            $message = 'Se produjo un error al actualizar el convenio';
                            $count = 0;
                            $data['actualizado'] = null;
                            $code = -7;
                            // Log::channel('')->error(''); // buscar canales en config/loggin.php
                        }else if(empty($response)){
                            $status = 'empty';
                            $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                            $count = 0;
                            $data['actualizado'] = $response;
                            $code = -8;
                            // Log::channel('')->info(''); // buscar canales en config/loggin.php
                        }else{
                            $status = 'ok';
                            $message = 'Convenio actualizado con éxito.';
                            $count = sizeof($response);
                            $data['actualizado'] = $response;
                            $code = 1;
                        }

                    }
                }
                $params_sp = [];
                
                array_push($extras['sps'], ['sp_convenio_Select' => $params_sp]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_convenio_Select', $params_sp));
                $convenios = $this->ejecutar_sp_directo('afiliacion', 'sp_convenio_Select', $params_sp);
                array_push($extras['responses'], ['sp_convenio_Select' => $convenios]);
                $data['convenios'] = $convenios;

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