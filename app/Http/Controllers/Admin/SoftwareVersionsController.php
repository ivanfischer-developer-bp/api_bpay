<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Models\User;
use App\Models\SoftwareVersions\FrontSoftwareVersion;
use App\Models\SoftwareVersions\BackSoftwareVersion;

use Carbon\Carbon;
use DB;

use App\Http\Controllers\ConexionSpController;

class SoftwareVersionsController extends ConexionSpController
{
    /**
     * Retorna las últimas versiones de software para el frontend y backend
     */
    public function buscar_ultimas_versiones(Request $request)
    {    
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'admin/sistema/software-versions/buscar-ultimas-versiones',
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
                $frontSoftwareVersion = FrontSoftwareVersion::latest()->first();
                $backSoftwareVersion = BackSoftwareVersion::latest()->first();
                
                array_push($extras['responses'], ['front' => $frontSoftwareVersion, 'back' => $backSoftwareVersion]);
                $status = 'ok';
                $message = 'Transacción realizada con éxito.';
                $count = 2; // front and back software versions
                $data = [
                    'front_software_version' => $frontSoftwareVersion,
                    'back_software_version' => $backSoftwareVersion
                ];
                $code = 1;
                
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
     * retrona un listado de versiones de software registradas, tanto para frontend como para backend, 
     * ordenadas por fecha de creación de forma descendente.
     */
    public function listar_versiones(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'admin/sistema/software-versions/listar-versiones',
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
                $destino = request('destino');
                $fecha_desde = request('fecha_desde');
                $fecha_hasta = request('fecha_hasta');
                $order = request('order', 'desc'); // por defecto descendente

                $params = [
                    'destino' => $destino,
                    'fecha_desde' => $fecha_desde,
                    'fecha_hasta' => $fecha_hasta,
                    'order' => $order
                ];
                
                array_push($extras['verificado'], ['destino' => $destino]);
                if ( empty(request('destino')) ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    // buscar el listado
                    $fecha_desde_final = !empty($fecha_desde) ? $fecha_desde : Carbon::now()->startOfYear()->format('Y-m-d');
                    $fecha_hasta_final = !empty($fecha_hasta) ? $fecha_hasta : Carbon::now()->addDays(1)->format('Y-m-d');
                    if($destino == 'front'){
                        $query = FrontSoftwareVersion::whereBetween('created_at', [$fecha_desde_final, $fecha_hasta_final])
                            ->orderBy('created_at', $order);
                        $listado = $query->get();
                    }else if($destino == 'back'){
                        $query = BackSoftwareVersion::whereBetween('created_at', [$fecha_desde_final, $fecha_hasta_final])
                            ->orderBy('created_at', $order);
                        $listado = $query->get();
                    }
                    array_push($extras['responses'], ['listado' => $listado]);
                    if($listado != null){
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = count($listado);
                        $data = [
                            'listado' => $listado,
                            'vsb_actual' => config('site.software_version')
                        ];
                        $code = 1;
                    }else{
                        $status = 'empty';
                        $message = 'No se encontraron versiones de software para los parámetros indicados.';
                        $count = 0;
                        $data = null;
                        $code = -3;
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
     * Registra una nueva versión de software para el frontend o backend. 
     * Se espera recibir en el request un campo 'destino' con valor 'front' o 'back' para indicar a qué tipo de versión corresponde, 
     * y los demás campos necesarios para crear la versión (version_number, tarea, acciones, observaciones, version_notes, ambiente, desarrollador, tiempo, publicar).
     */
    public function registrar_version(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'admin/sistema/software-versions/registrar-version',
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
            $permiso_requerido = 'registrar version software';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $destino = request('destino');
                $sv = request('software_version');
                $params = [
                    'destino' => $destino,
                    'software_version' => $sv,
                ];

                array_push($extras['verificado'], ['destino' => $destino]);
                if ( empty($destino) ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    if($params['destino'] == 'front'){
                        $version = new FrontSoftwareVersion();
                    }else if($params['destino'] == 'back'){
                        $version = new BackSoftwareVersion();
                        $version->external_version_number = $sv['external_version_number'];
                    }
                    $version->version_number = $sv['version_number'];
                    $version->tarea = $sv['tarea'];
                    $version->acciones = $sv['acciones'];
                    $version->observaciones = $sv['observaciones'];
                    $version->version_notes = $sv['version_notes'] != null ? $sv['version_notes'] : 'Modificaciones, mejoras y correcciones de fallos';
                    $version->ambientes = $sv['ambientes'];
                    $version->desarrollador = $sv['desarrollador'] != null ? $sv['desarrollador'] : 'Iván Gustavo Fischer';
                    $version->tiempo = $sv['tiempo'];
                    $version->publicar = filter_var($sv['publicar'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                    $version->fecha = $sv['fecha'] != null ? Carbon::parse($sv['fecha'])->format('Y-m-d') : Carbon::now()->format('Y-m-d');
                    $response = $version->save();
                    array_push($extras['responses'], ['save_response' => $response]);
                    if($response){
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = 1;
                        $data = $response;
                        $code = 1;
                    }else{
                        $errors = $version->getErrors() != null ? $version->getErrors() : ['Error desconocido al guardar la versión'];
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -3;
                        // Log::channel('')->error(''); // buscar canales en config/loggin.php
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
     * Actualiza una versión de software para el frontend o backend. 
     * Se espera recibir en el request un campo 'destino' con valor 'front' o 'back' para indicar a qué tipo de versión corresponde, 
     * y los demás campos necesarios para crear la versión (version_number, tarea, acciones, observaciones, version_notes, ambiente, desarrollador, tiempo, publicar).
     */
    public function actualizar_version(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'admin/sistema/software-versions/actualizar-version',
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
            $permiso_requerido = 'registrar version software';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $destino = request('destino');
                $sv = request('software_version');
                $id = request('id');
                $params = [
                    'id' => $id,
                    'destino' => $destino,
                    'software_version' => $sv,
                ];

                array_push($extras['verificado'], ['destino' => $destino]);
                if ( empty($destino) || empty($id) ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    if($params['destino'] == 'front'){
                        $version = FrontSoftwareVersion::find($id);
                    }else if($params['destino'] == 'back'){
                        $version = BackSoftwareVersion::find($id);
                    }
                    if($version == null){
                        $status = 'empty';
                        $message = 'No se encontró la versión con id '.$id;
                        $count = 0;
                        $data = null;
                        $code = -4;
                    }else{
                        if($params['destino'] == 'back'){
                            $version->external_version_number = $sv['external_version_number'];
                        }
                        $version->version_number = $sv['version_number'];
                        $version->tarea = $sv['tarea'];
                        $version->acciones = $sv['acciones'];
                        $version->observaciones = $sv['observaciones'];
                        $version->version_notes = $sv['version_notes'] != null ? $sv['version_notes'] : 'Modificaciones, mejoras y correcciones de fallos';
                        $version->ambientes = $sv['ambientes'];
                        $version->desarrollador = $sv['desarrollador'] != null ? $sv['desarrollador'] : 'Iván Gustavo Fischer';
                        $version->tiempo = $sv['tiempo'];
                        $version->publicar = filter_var($sv['publicar'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                        $version->fecha = $sv['fecha'] != null ? Carbon::parse($sv['fecha'])->format('Y-m-d') : Carbon::now()->format('Y-m-d');
                        $response = $version->save();
                        array_push($extras['responses'], ['save_response' => $response]);
                        if($response){
                            $status = 'ok';
                            $message = 'Transacción realizada con éxito.';
                            $count = 1;
                            $data = $version;
                            $code = 1;
                        }else{
                            $errors = $version->getErrors() != null ? $version->getErrors() : ['Error desconocido al actualizar la versión'];
                            $status = 'fail';
                            $message = 'Se produjo un error al realizar la petición';
                            $count = 0;
                            $data = null;
                            $code = -3;
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
}