<?php

namespace App\Http\Controllers\Internos\General;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;
use Illuminate\Support\Str;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

class LocalidadController extends ConexionSpController
{
    /**
     * Busca una localidad
     */
    public function buscar_localidad(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/general/localidad/buscar-localidad',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'verificado' => [],
            'queries' => []
        ];
        $status = 'fail';
        $message = '';
        $count = -1;
        $data = null;
        $errors = [];
        $params = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            if($user->hasPermissionTo('consultar listados')){
                $id_pais = request('id_pais');
                $id_provincia = request('id_provincia');
                $id_partido = request('id_partido');
                $cpostal = request('cpostal');
                $localidad = request('localidad');
                $params = [
                    'id_pais' => $id_pais,
                    'id_provincia' => $id_provincia,
                    'id_partido' => $id_partido,
                    'cpostal' => $cpostal,
                    'localidad' => $localidad
                ];
                if ( is_numeric($id_pais) && $id_pais > 0 ){
                    $filtros['p_id_pais'] = $id_pais;
                }
                if ( is_numeric($id_provincia) && $id_provincia > 0 ){
                    $filtros['p_id_provincia'] = $id_provincia;
                }
                if ( is_numeric($id_partido) && $id_partido > 0 ){
                    $filtros['p_id_partido'] = $id_partido;
                }
                if ( !empty($cpostal) ){
                    $filtros['p_cpostal'] = $cpostal;
                }
                if ( !empty($localidad) && $localidad != '' ){
                    $filtros['p_n_localidad'] = '%' . $request->input('localidad') . '%';
                }
                array_push($extras['sps'], ['sp_localidad_Select' => $filtros]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_localidad_Select', $filtros));
                $ret = $this->ejecutar_sp_directo('afiliacion', 'sp_localidad_Select', $filtros);
                array_push($extras['responses'], ['sp_localidad_Select', $ret]);

                if(isset($ret['error'])){
                    array_push($errors, $ret['error']);
                    $status = 'fail';
                    $count = 0;
                    $message = 'Error en la consulta a la base de datos.';
                }else if(!empty($ret)){
                    $status = 'ok';
                    $message = 'Registros encontrados';
                    $count = sizeof($ret);
                    $data = $ret;
                    $code = 1;
                }else{
                    $status = 'empty';
                    $message = 'No hay registros que coincidan con los parámetros de búsqueda.';
                    $count = 0;
                    $data = null;
                    $code = 2;
                }

                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => null,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CONSULTAR LISTADOS';
                $count  = 0;
                $data = null;
                array_push($errors, 'Error de permisos. '.$message);
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => null,
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
     * Agrega una localidad
     * sin completar
     * copiar de LocalidadController.php de la app
     */
    public function agregar_localidad(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/localidad/agregar-localidad',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'verificado' => [],
        ];
        $status = 'fail';
        $message = '';
        $count = -1;
        $data = null;
        $errors = [];
        $params = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            if($user->hasPermissionTo('')){
                $params = [

                ];
                array_push($extras['sps'], ['sp_' => $params]);
                $resp = $this->ejecutar_sp_directo('','sp_', $params);
                array_push($extras['responses'], ['sp_' => $resp]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_afiliado_Update', $params));
                if(isset($resp['error'])){
                    array_push($errors, $resp['error']);
                    $data = null;
                    $status = 'fail';
                    $count = 0;
                    $message = 'Error en la consulta a la base de datos.';
                }else if(!empty($resp)){
                    $status = 'ok';
                    $data = $resp;
                    $count = sizeof($resp);
                    $code = 1;
                    $message = '';
                }else{
                    $status = 'empty';
                    $data = $resp;
                    $count = 0;
                    $code = -2;
                    $message = '';
                }
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => null,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para ';
                $count  = 0;
                $data = null;
                array_push($errors, 'Error de permisos. '.$message);
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => null,
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
     * Actualiza una localidad
     * sin completar
     * copiar de LocalidadController.php de la app
     */
    public function actualizar_localidad(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/localidad/actualizar-localidad',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'verificado' => [],
        ];
        $status = 'fail';
        $message = '';
        $count = -1;
        $data = null;
        $errors = [];
        $params = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            if($user->hasPermissionTo('')){

                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => null,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para ';
                $count  = 0;
                $data = null;
                array_push($errors, 'Error de permisos');
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => null,
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