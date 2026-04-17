<?php

namespace App\Http\Controllers\Internos;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;



class ProgramasEspecialesController extends ConexionSpController
{

    public function buscar_programas_especiales(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/programas-especiales/buscar-programas-especiales',
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
            $permiso_requerido = 'gestionar programas especiales'; // permiso requerido para ejecutar esta función
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $params = [
                    'id_persona' => request('id_persona'),
                    'id_programa' => request('id_programa'),
                    'fecha_desde' => request('fecha_desde') != null && request('fecha_desde') != '' && request('fecha_desde') != 'null' ? request('fecha_desde') : null,
                    'fecha_hasta' => request('fecha_hasta') != null && request('fecha_hasta') != '' && request('fecha_hasta') != 'null' ? request('fecha_hasta') : null
                ];
                
                $db = 'afiliacion';
                $sp = 'sp_persona_programa_select';
                $params_sp = [
                    'p_id_persona' => $params['id_persona'],
                    'p_id_programa' => $params['id_programa'],
                    'p_fec_desde' => $params['fecha_desde'],
                    'p_fec_hasta' => $params['fecha_hasta']
                ];
                array_push($extras['verificado'], [$sp => ['fecha_desde' => $params['fecha_desde'], 'fecha_hasta' => $params['fecha_hasta']]]);
                if ( $params['fecha_desde'] == null || $params['fecha_hasta'] == null ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    $status = 'fail';
                    $message = 'Verifique los parámetros, Fecha desde y Fecha hasta son obligatorios para esta consulta.';
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

    /**
     * Lista los programas especiales para su configuración
     */
    public function listar_programas_especiales(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/programas-especiales/listar-programas-especiales';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_programa_especial_select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega un nuevo programa especial al listado de programas especiales
     */
    public function agregar_programa_especial(Request $request)
    {    
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/programas-especiales/agregar-programa-especial';
        $this->permiso_requerido = '';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_programa_especial_insert';
        $this->verificado = [
            $this->sp => [
                'n_programa_especial' => request('n_programa_especial')
            ]
        ];
        
        if(empty(request('n_programa_especial'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'n_programa_especial' => request('n_programa_especial'),
            'id_patologia' => request('id_patologia'),
            'baja' => request('baja') !== NULL ? request('baja') : 0,
        ];
        $this->params_sp = [
            'p_n_programa_especial' => $this->params['n_programa_especial'],
            'p_id_patologia' => $this->params['id_patologia'],
            'p_baja' => $this->params['baja']
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Actualiza los datos de un programa especial existente
     */
    public function actualizar_programa_especial(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/programas-especiales/actualizar-programa-especial';
        $this->permiso_requerido = '';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_programa_especial_update';
        $this->verificado = [
            $this->sp => [
                'id_programa_especial' => request('id_programa_especial'),
                'n_programa_especial' => request('n_programa_especial')
            ]
        ];
        if(empty(request('n_programa_especial')) || empty(request('id_programa_especial'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'id_programa_especial' => request('id_programa_especial'),
            'n_programa_especial' => request('n_programa_especial'),
            'id_patologia' => request('id_patologia'),
            'baja' => request('baja') !== NULL ? request('baja') : 0,
        ];
        $this->params_sp = [
            'p_id_programa_especial' => $this->params['id_programa_especial'],
            'p_n_programa_especial' => $this->params['n_programa_especial'],
            'p_id_patologia' => $this->params['id_patologia'],
            'p_baja' => $this->params['baja']
        ];
        return $this->ejecutar_sp_simple();
    }
}