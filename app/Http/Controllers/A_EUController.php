<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\ConexionSpController;
use Illuminate\Support\Facades\Log;
// Log::emergency($message);
// Log::alert($message);
// Log::critical($message);
// Log::error($message);
// Log::warning($message);
// Log::notice($message);
// Log::info($message);
// Log::debug($message);

class AEUController extends ConexionSpController
{
    /**
     * Este controlador no tiene ninguna ruta asignada
     * existe sólo a los fine de brindar un ejemplo de uso de los métodos
     * de la clase ConexionSpController.
     * IMPORTANTE: extender el controlador de la clase ConexionSpController
     * e importarlo con use App\Http\Controllers\ConexionSpController; como se muestar arriba
     * 
     * Así se conforma el constructor de la clase
     * __construct($db = null, $sp = null, $params = null, $options = [])
     * 
     * Éstas son las propiedades protegidas de la clase (sólo accesibles por clases que la heredan)
     * @var resultado: Aquí se almacena lo que devuelve el sp.
     * @var errors = Array de mensajes de error
     * @var error = String mensaje de error
     * @var error_message_ok String mensaje de error personalizado en caso de éxito, si no se especifica se retorna 'No se  produjeron errores'
     * @var message_ok String mensaje de éxito, si no se especifica se retorna 'Registros encontrados.'
     * @var message_fail String mensaje en caso de fallas, si no se especifica se retorna 'Se ha producido un error.'
     * @var message_empty String mensaje en caso de no encontrar registros, si no se especifica se retorna 'No se encontraron registros.'
     * 
     * Éstas son las propiedades públicas de la clase (accesibles por todas las clases)
     * @var db String base de datos a consultar. valores posibles 'aflilacion', 'validacion', 'admin'
     * @var sp String nombre del procedimiento almacenado a consultar
     * @var params Array de parámetros para el procedimiento almacenado
     * @var options Array de opciones entre las que se encuentran error, error_message_ok, message_ok, message_fail
     * y message_empty para personalizar los mensajes que se emitirán en cada caso, explicado más arriba.
     * 
     * La función get_response devuelve siempre un json con las siguientes claves:
     * @return status String indica el estado de la consulta puede ser:
     *  'ok' si todo salió bien,
     *  'empty' si no se obtuvieron registros o
     *  'fail' si hubo errores en la conexión con la base o en la ejecución del sp.
     * @return message mensaje de respuesta
     * si status es 'empty' se devuelve 'No se encontraron registros.' o message_empty si está seteada,
     * si status es 'ok' devuelve 'Registros encontrados.' o message_ok si está seteada,
     * si status es 'fail' devuelve 'Se ha producido un error.' si message_fail está seteado
     * @return count Integer devuelve la cantidad de registros devueltos por el sp.
     * @return params Array son los parámetros pasados al sp para su ejecución
     * @return data Array de datos del sp, tal cual vienen como respuesta
     * @return errors Array de errores producidos, en caso de que status sea fail, aquí se encontrará una descripción
     */


    /**
     * Plantilla para crear una función rapidamente
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function boilerplate_ejecutar_sp_simple(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = '';
        $this->permiso_requerido = '';
        $this->db = ''; // afiliacion, validacion, admin, alfabeta
        $this->sp = '';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro: id_usuario, id_n_usuario, p_id_usuario
        $this->param_usuario = 'servega@gmail.com'; // el parámetro cuando no es el id o el nombre
        $this->verificado = [
            $this->sp => [
                '' => request(''), 
                '' => request('')
            ]
        ];
        if(empty(request('')) || empty(request(''))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            '' => request(''),
            '' => request('') !== NULL ? request('') : NULL,
        ];
        $this->params_sp = [
            '' => $this->params[''],
            'p_fecha' => Carbon::now()->format('Ymd H:i:s'),
        ];
        if(request('') != null){
            $this->params[''] = request('');
            $this->params_sp[''] = request('');
        }
        return $this->ejecutar_sp_simple();
    }
        // si es necesario modificar la respuesta se usa el siguiente código en vez del return de arriba
        // $ret = $this->ejecutar_sp_simple();
        // $r = (array) json_decode(json_encode($ret));
        // $d = $r['original']->data;
        // // formateamos los valores
        // $temp_data = [];
        // foreach ( $d as $item ){
		// 	   if($item->importe_recargo != null){
        //        $item->importe_recargo = number_format($item->importe_recargo, 2, ',', '.');
        //     }
        //     if($item->porcentaje != null){
        //         $item->porcentaje = number_format($item->porcentaje, 2, ',', '.');
        //     }
		// 	   array_push($temp_data, $item);
		// }
        // $r['original']->data = $temp_data;
        // return response()->json($r['original']);

        // si es necesario hacer alguna consulta previa hacemos la consulta y luego la agregamos al response
        // $ret = $this->ejecutar_sp_simple();
        // $r = (array) json_decode(json_encode($ret));
        // $d = $r['original'];
        // // formateamos los valores
        // array_unshift($d->extras->sps, ['sp_notificacion_select' => ['p_id' => $this->params['id']]]);
        // array_unshift($d->extras->queries, $this->get_query('admin', 'sp_notificacion_select', ['p_id' => $this->params['id']]));
        // array_unshift($d->extras->responses, ['sp_notificacion_select' => $notificacion]);     
        // $r['original'] = $d;
        
        // return response()->json($r['original']);
    // }


    /**
     * Plantilla para crear una función rápidamente
     */
    public function boilerplate_ejecutar_sp_directo(Request $request)
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

                ];
                
                $sp = '';
                $db = '';
                $params_sp = [

                ];
                array_push($extras['verificado'], [$sp => ['' => request('')]]);
                if ( empty(request('')) ){
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

    /**
     * Ejemplo para rutas mobile
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function boilerplate_mobile(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/',
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
        $id_usuario_sqlserver = $logged_user['id_usuario_sqlserver'];

        try {

            if($logged_user['roles']['name'] != 'afiliado mobile' && $logged_user['roles']['name'] != 'afiliado'){
                return response()->json([
                    'status' => 'unauthorized',
                    'count' => 0,
                    'errors' => $errors,
                    'message' => 'El usuario no es un afiliado',
                    'line' => $line,
                    'code' => -2,
                    'data' => $data,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]);
            }else{
                if($id_usuario_sqlserver != 0){
                    // buscamos los datos del usuario
                    $sp = 'sp_usuario_select';
                    $db = 'admin';
                    $params_sp = [
                        'p_id_usuario' => $id_usuario_sqlserver
                    ];
                    // buscamos los datos del usuario
                    array_push($extras['sps'], [$sp => $params_sp]);
                    array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
                    $usuario_sqlserver = $this->ejecutar_sp_directo($db, $sp, $params_sp);
                    array_push($extras['responses'], [$sp => $usuario_sqlserver]);
        
                    if(is_array($usuario_sqlserver)){
                        $usuario_sqlserver = $usuario_sqlserver[0];
                    }
                    
                    $params = [

                    ];
                
                    $params_sp = [];
                    $sp = '';
                    $db = '';
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
                    array_push($errors, 'El logueado no tiene id_usuario_sqlserver');
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
     * Ejemplo para un endpoint no implementado
     */
    public function boilerplate_endpoint_no_implementado(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '';
        return $this->endpoint_no_implementado();   
    }

    /**
     * Plantilla para funciones de api mobile
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function nombre_funcion_mobile(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/',
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
        $id_usuario_sqlserver = $logged_user['id_usuario_sqlserver'];

        try {
            $params = [

            ];
            if($logged_user['roles']['name'] != 'afiliado mobile' && $logged_user['roles']['name'] != 'afiliado'){
                return response()->json([
                    'status' => 'unauthorized',
                    'count' => 0,
                    'errors' => $errors,
                    'message' => 'El usuario no es un afiliado',
                    'line' => $line,
                    'code' => -2,
                    'data' => $data,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]);
            }else{
                if($id_usuario_sqlserver != 0){
                    // buscamos los datos del usuario
                    $extras['sps']['sp_usuario_select'] = ['p_id_usuario' => $id_usuario_sqlserver];
                    array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]));
                    $usuario_sqlserver = $this->ejecutar_sp_directo('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]);
                    $extras['responses']['sp_usuario_select'] = $usuario_sqlserver;
        
                    if(is_array($usuario_sqlserver)){
                        $usuario_sqlserver = $usuario_sqlserver[0];
                    }

                    array_push($extras['verificado'], ['sp_' => ['' => request('')]]);
                    if ( empty(request('')) ){
                        array_push($errors, 'Parámetros incompletos o incorrectos');
                        $status = 'fail';
                        $message = 'Verifique los parámetros';
                        $count = 0;
                        $data = null;
                        $code = -4;
                    }else{

                        $extras['sps']['sp_'] = $params_sp;
                        array_push($extras['queries'], $this->get_query('', 'sp_', $params_sp));
                        $response = $this->ejecutar_sp_directo('', 'sp_', $params_sp);
                        $extras['responses']['sp_'] = $response;
                        if(is_array($response) && array_key_exists('error', $response)){
                            array_push($errors, $response['error']);
                            $status = 'fail';
                            $message = 'Se produjo un error al realizar la petición';
                            $count = 0;
                            $data = null;
                            $code = -5;
                            // Log::channel('')->error(''); // buscar canales en config/loggin.php
                        }else if(empty($response)){
                            $status = 'empty';
                            $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                            $count = 0;
                            $data = $response;
                            $code = -6;
                            // Log::channel('')->info(''); // buscar canales en config/loggin.php
                        }else{
                            $status = 'ok';
                            $message = 'Transacción realizada con éxito.';
                            $count = sizeof($response);
                            $data = $response;
                            $code = 1;
                        }
                    }

                }else{
                    $status = 'fail';
                    $code = -3;
                    $count = 0;
                    $message = 'El usuario logueado no tiene id_usuario_sqlserver';
                    array_push($errors, 'El logueado no tiene id_usuario_sqlserver');
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
}
