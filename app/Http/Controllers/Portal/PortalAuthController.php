<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;
use Mail;

use App\Http\Controllers\ConexionSpController;
use App\Mail\NotificacionEmailReseteoClave;
use App\Mail\NotificacionEmailRegistroUsuarioDoctor;

use App\Models\User;
use App\Models\ProfileDoctor;
use App\Models\ConnectedUser;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PortalAuthController extends ConexionSpController {

    /**
     * Inicio de sesión y creación de token
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/portal/auth/login',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            
            $request->validate([
                // 'email' => 'required|string|email', // se cambió a partir del agregado del campo usuario en la tabla users
                'usuario' => 'required|string',
                'password' => 'required|string',
                'remember_me' => 'boolean'
            ]);

            $errors = [];

            $token_siaf = '';
    
            // $credentials = request(['email', 'password']); // se cambió a partir del agregado del campo usuario en la tabla users
            $credentials = request(['usuario', 'password']);
            
            $email = request('email') != null ? request('email') : null;
            $id_usuario_sqlserver = 0;
            $message = 'El usuario ya existe en el sistema';
            $new_user = null;
            $usuario_sqlserver = null;

            // llamamos a sp_usuario_login
            $this->params = [
                // 'p_n_usuario' => $credentials['email'], // se cambió a partir del agregado del campo usuario en la tabla users
                'p_n_usuario' => $credentials['usuario'],
                'p_clave' => $credentials['password']
            ];
            array_push($extras['sps'], ['sp_usuario_login' => $this->params]);
            array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_login', $this->params));
            $response = $this->ejecutar_sp_directo('admin', 'sp_usuario_login', null);
            array_push($extras['responses'], ['sp_usuario_login' => $response]);
            // si se logueó va a tener un id_usuario y se lo asignamos a la variable
            if(isset($response) && !empty($response) && is_array($response)){
                $id_usuario_sqlserver = $response[0]->id_usuario;
                // a demás devuelve estado_clave y cambiar_clave
            }
            // lo cambiamos a minúsculas porque en este sistema el usuario está siempre en minusculas
            $credentials['usuario'] = strtolower($credentials['usuario']); 
            
            // si tiene id_usuario_sqlserver es que existe en el otro sistema 
            if($id_usuario_sqlserver != 0){
                // entonces verificamos si existe en este sistema
                $user = User::where('id_usuario_sqlserver', '=', $id_usuario_sqlserver)->get();
                if(isset($user) && empty($user[0])){
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'No Autorizado',
                        'usuario_sqlserver' => $response,
                        'extras' => $extras,
                        'credentials' => $credentials
                    ], 401);
                }
                // // si no existe en este sistema lo registramos
                // if(isset($user) && empty($user[0])){
                //     // return response()->json(['user' => $user, 'response' => $response, 'if' => 'si']);

                //     // buscamos los datos del usuario
                //     array_push($extras['sps'], ['sp_usuario_select' => ['p_id_usuario' => $id_usuario_sqlserver]]);
                //     array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]));
                //     $usuario_sqlserver = $this->ejecutar_sp_directo('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]);
                //     array_push($extras['responses'], ['sp_usuario_select' => $usuario_sqlserver]);
                //     // return $usuario_sqlserver;
                //     // si tenemos los datos lo registramos
                //     if(isset($usuario_sqlserver) && !empty($usuario_sqlserver) && $usuario_sqlserver[0]->activo == 1 && $usuario_sqlserver[0]->bloqueado == 0){
                //         $rol = Role::where('name', 'like', 'basico')->get();
                //         if(empty($rol[0])){
                //             array_push($response, ['busqueda de rol' => 'el rol no existe']);
                //         }else{
                //             $rol = $rol[0];
                //             $u = $usuario_sqlserver[0];
                //             $usuario_creado = User::create([                          
                //                 'email' => strtolower($u->email),
                //                 'name' => $u->apynom,
                //                 'usuario' => strtolower($credentials['usuario']),
                //                 'id_usuario_sqlserver' => $u->id_usuario,
                //                 'password' => Hash::make($credentials['password']),
                //             ]);
                //             // si se creo el usuario
                //             if(!empty($usuario_creado)){
                //                 $status = 'ok';
                //                 $message = 'Usuario creado con éxito.';
                //                 $count  = 1;
                //                 $data = [$usuario_creado];
                //                 $error = null;
                //                 // y existe el rol
                //                 if($rol != null){
                //                     $new_user = User::find($usuario_creado->id);
                //                     // se lo asiganamos
                //                     $new_user->assignRole($rol);
                //                     $message = $message . ' Se asignó el rol ' . $rol['name'] . ' al usuario ' . $usuario_creado['name'];
                //                 }
                //             }
                //         }
                //     }
                // }
                // autenticar
                // return response()->json(['user' => $user, 'response' => $response, 'if' => 'no']);
            }
            // si no está en el otro sistema verificamos si existe en este sistema
            if (!Auth::attempt($credentials)){
                return response()->json([
                    'status' => 'fail',
                    'message' => 'No Autorizado',
                    'usuario_sqlserver' => $response,
                    'extras' => $extras,
                    'credentials' => $credentials
                ], 401);
            }
            
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $user->connected = 1;
            $user->save();

            // si es super administrador
            if($logged_user['roles']['name'] == 'super administrador'){
                // buscamos el usuario en el otro sistema, si no existe le asignamos el usuario 1
                $id_usuario_sqlserver = $user->id_usuario_sqlserver != null ? $user->id_usuario_sqlserver : 1;

                $param = ['p_id_usuario' => $id_usuario_sqlserver];
                array_push($extras['sps'], ['sp_usuario_Select' => $param]);
                array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_Select', $param));
                $usuario_sqlserver = $this->ejecutar_sp_directo('admin', 'sp_usuario_Select', $param);
                array_push($extras['responses'], ['sp_usuario_Select' => $usuario_sqlserver]);
                if(is_array($usuario_sqlserver)){
                    $logged_user['usuario_sqlserver'] = $usuario_sqlserver[0];
                }else{
                    $logged_user['usuario_sqlserver'] = $usuario_sqlserver;
                }
            }else{
                // si es usuario del otro sistema pero no es super administardor en este
                if($user->id_usuario_sqlserver != null){
                    $param = ['p_id_usuario' => $id_usuario_sqlserver];
                    array_push($extras['sps'], ['sp_usuario_Select' => $param]);
                    array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_Select', $param));
                    $usuario_sqlserver = $this->ejecutar_sp_directo('admin', 'sp_usuario_Select', $param);
                    array_push($extras['responses'], ['sp_usuario_Select' => $usuario_sqlserver]);
                    if(is_array($usuario_sqlserver)){
                        $logged_user['usuario_sqlserver'] = $usuario_sqlserver[0];
                    }else{
                        $logged_user['usuario_sqlserver'] = $usuario_sqlserver;
                    }
                }else{
                    // si no es usuario del otro sistema
                    $logged_user['usuario_sqlserver'] = null;
                }
            }

            $tokenResult = $user->createToken('Personal Access Token');
    
            $token = $tokenResult->token;
            if ($request->remember_me){
                $token->expires_at = Carbon::now()->addWeeks(1);
            }
            $token->save();

            // guardamos los datos de la sesion del usuario
            $usuario_conectado = ConnectedUser::create([
                'user_id' => $logged_user['id'],    
                'id_usuario_sqlserver' => $logged_user['id_usuario_sqlserver'],
                'name' => $logged_user['name'],
                'usuario' => $logged_user['usuario'],
                'conectado' => true,
                'inicio_sesion' => Carbon::now(),
                'fin_sesion' => null,
                'forzado' => false,
                'ambiente' => env('AMBIENTE'),
                'token_id' => $token->id
            ]);
            $logged_user['usuario_conectado'] = $usuario_conectado;
            
            if($usuario_conectado->conectado == 1){
                $logged_user['id_sesion_activa'] = $usuario_conectado->id;
                $user->id_sesion_activa = $usuario_conectado->id;
                $user->save();
            }

            // validamos en siaf si corresponde
            if(env('VALIDAR_SIAF') == 'SI'){
                try {
                    if($token_siaf == null || $token_siaf == ''){
                        $fields = array(
                            'username' => env('USER_SIAF'),
                            'password' => env('PASSWORD_SIAF')
                        );
                        $fields_string = http_build_query($fields);
                        $ch = curl_init();
                        curl_setopt_array($ch, array(
                            CURLOPT_URL => env('LOGIN_SIAF'),
                            CURLINFO_HEADER_OUT => true,
                            CURLOPT_POST => 1,
                            CURLOPT_HTTPHEADER => array(
                                "cache-control: no-cache",
                                'Content-Type: application/x-www-form-urlencoded'
                            ),
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_POSTFIELDS => $fields_string,
                            CURLOPT_SSL_VERIFYHOST => 0,
                        ));
                        $response_login_siaf = curl_exec($ch);
                        curl_close($ch);
                        $json_login_siaf = json_decode($response_login_siaf);
                        array_push($extras['responses'], ['login_siaf' => $json_login_siaf]);
                        if(gettype($json_login_siaf) == 'string') {
                            $token_siaf = $json_login_siaf;
                        }else{
                            if($json_login_siaf != null){
                                Log::channel('siaf')->error('ERROR LOGIN SIAF EN AUTH. Code -2 '.json_decode(json_encode($json_login_siaf), false)->Message);
                            }else{
                                Log::channel('siaf')->error('ERROR LOGIN SIAF EN AUTH. Code -3. response null');
                            }
                        }
                    }
                } catch (\Throwable $th) {
                    $message = 'Error de Login en SIAF. Line: '.$th->getLine().' Error: '.$th->getMessage();
                    $status = 'fail';
                    array_push($errors, 'ERROR SIAF. Line: '.$th->getLine().' Error: '.$th->getMessage());
                    $code = -2;
                    Log::channel('siaf')->error('ERROR LOGIN SIAF EN AUTH. Code -1. Line: '.$th->getLine().' Error: '.$th->getMessage());
                } finally {
                    if(is_resource($ch)){
                        curl_close( $ch );
                    }
                }
            }
    
            return response()->json([
                'status' => 'ok',
                'access_token' => $tokenResult->accessToken,
                'token_siaf' => $token_siaf,
                'logged_user' => $logged_user,
                'usuario_conectado' => $usuario_conectado,
                'token_type' => 'Bearer',
                'message' => $message,
                'usuario_sqlserver' => $usuario_sqlserver,
                'created_user' => $new_user,
                'expires_at' => Carbon::parse($token->expires_at)->toDateTimeString(),
                'extras' => $extras
            ]);
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Cambia la contraseña del usuario logueado
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function cambiar_password(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/portal/auth/cambiar-password',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        try {
            $status = null;
            $errors = [];
            $count = null;
            $message = null;
            $code = null;
            $data = null;
            $params = request(['new_password','password_confirm']);
            $sincronizar = request('sincronizar') ? boolval(request('sincronizar')) : true;
            $sqlserver_actualizado = false;
            $api_bpay_actualizado = false;
            $affected = false;
            $actualizar = true;
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            $extras['sqlserver_actualizado'] = $sqlserver_actualizado;
            $extras['api_bpay_actualizado'] = $api_bpay_actualizado;

            $this->params = [
                'new_password' => $params['new_password'],
                'password_confirm' => $params['password_confirm'],
                'sincronizar' => request('sincronizar')
            ];

            array_push($extras['verificado'], [
                'new_password' => $params['new_password'],
                'password_confirm' => $params['password_confirm'],
                'son_iguales' => $params['new_password'] === $params['password_confirm'] ? true : false
            ]);
            // si la contraseña y la confirmación coinciden
            if($params['new_password'] === $params['password_confirm']){
               // si el usuario está en la base de datos de sqlserver
                if($user->id_usuario_sqlserver != null && $sincronizar){
                    //  define los parametros a actualizar en sqlserver
                    $parametros = [
                        'p_id_usuario' => $user->id_usuario_sqlserver,
                        'p_apynom' => $user->name,
                        'p_n_usuario' => $user->usuario,  
                        'p_email' => $user->email,
                        'p_activo' => 1,
                        'p_id_area' => NULL,
                        'p_supervisor' => NULL,
                        'p_desactivar' => NULL,
                        'p_activar' => NULL,
                        'p_bloqueado' => 0,
                        'p_finaliza_clave' => NULL,
                        'p_clave_nueva' => $params['new_password'],
                        'p_id_usuario_envia' => $user->id,
                        'p_atributos' => NULL,
                    ];
                    //  actualiza los datos del usuario
                    $ret = $this->ejecutar_sp_directo('admin', 'sp_usuario_Update', $parametros);

                    array_push($extras['sps'], ['sp_usuario_Update' => $parametros]);
                    array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_Update', $parametros));
                    $ret = $this->ejecutar_sp_directo('admin', 'sp_usuario_Update', $parametros);
                    array_push($extras['responses'], ['sp_usuario_Update' => $ret]);
                    if ( !empty($ret) && !empty($ret[0]) && $ret[0]->filas > 0 ){
                        // si se actualizó correctamente cambia la bandera
                        $sqlserver_actualizado = true;
                    }else{ 
                        // sino modifica la bandera para que no se realice el cambio en la base de datos
                        $actualizar = false;
                    }
                }
                // si se puede actualizar, ya sea porque se actualizó sqlserver o porque el usuario no está en sqlserver
                if($actualizar){
                    $user->password = Hash::make($params['new_password']);
                    $affected = $user->save();
                }
                // retorna la respuesta
                $extras['sqlserver_actualizado'] = $sqlserver_actualizado;
                $extras['api_bpay_actualizado'] = $affected;
                if(($sincronizar && $sqlserver_actualizado && $affected)){
                    $status = 'ok';
                    $count = 2;
                    $message = 'La contraseña fue actualizada con éxito en ambas bases de datos';
                    $code = 1;
                    $data = [
                        'sqlserver_actualizado' => $sqlserver_actualizado,
                        'api_bpay_actualizado' => $affected
                    ];
                }else if(!$sincronizar && $affected){
                    $status = 'ok';
                    $count = 1;
                    $message = 'La contraseña fue actualizada en mysql';
                    $code = 2;
                    $data = [
                        'sqlserver_actualizado' => $sqlserver_actualizado,
                        'api_bpay_actualizado' => $affected
                    ];
                }else{
                    $status = 'fail';
                    if($sqlserver_actualizado && !$affected){
                        $count = 1;
                        $message = 'La contraseña no fue actualizada en mysql pero si en sqlserver. Las bases de datos NO están sincrinozadas';
                        $code = -5;
                        $data = [
                            'sqlserver_actualizado' => $sqlserver_actualizado,
                            'api_bpay_actualizado' => $affected
                        ];
                    }else if(!$sqlserver_actualizado && $affected){
                        $count = 1;
                        $message = 'La contraseña no fue actualizada en sqlserver pero si en mysql. Las bases de datos NO están sincrinozadas';
                        $code = -4;
                        $data = [
                            'sqlserver_actualizado' => $sqlserver_actualizado,
                            'api_bpay_actualizado' => $affected
                        ];
                    }else{
                        $count = 0;
                        $message = 'La contraseña no fue actualizada ninguna base de datos';
                        $code = -3;
                        $data = [
                            'sqlserver_actualizado' => $sqlserver_actualizado,
                            'api_bpay_actualizado' => $affected
                        ];
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
                    'params' => $this->params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]);
            }else{
                array_push($errors, 'Error de contraseña');
                return response()->json([
                    'status' => 'fail',
                    'count' => 0,
                    'errors' => $errors,
                    'message' => 'La contraseña y la confirmación no coinciden',
                    'line' => null,
                    'code' => -2,
                    'data' => null,
                    'params' => $this->params,
                    'logged_user' => $logged_user,
                    'extras' => $extras,
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
                'code' => $th->getCode(),
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Resetea el password del usuario en sqlserver y en api_bpay si el usuario existe en ambas bases
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function password_reset(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/portal/auth/password-reset',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        try{
            $usuario = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($usuario);
            $id_usuario_sqlserver_default = $usuario->id_usuario_sqlserver != null ? $usuario->id_usuario_sqlserver : 1;
            
            $affected = false;
            $actualizar = true;
            $sqlserver_actualizado = false;
            // variables de respuesta
            $status = 'fail';
            $message = 'Error. No se encontraron registros.';
            $count = 0;
            $code = null;
            $usuario_sqlserver = null;
            $user = null;
            $data = [
                'usuario_sqlserver' => $usuario_sqlserver,
                'usuario_api' => $user,
                'api_bpay_actualizado' => $affected,
                'sqlserver_actualizado' => $sqlserver_actualizado
            ];
            $errors = [];
            
            $params = [
                'id_usuario_sqlserver' => request('id_usuario_sqlserver'),
                'password' => request('password')
            ];
            // return response()->json(['params' => $params]);
            $password = $params['password'] !== null ? $params['password'] : 'abcd1234';
            $id_usuario_sqlserver = $params['id_usuario_sqlserver'];

            $sp = 'sp_usuario_select';
            $db = 'admin';
            $parametros = [
                'p_id_usuario' => $id_usuario_sqlserver
            ];
            array_push($extras['sps'], ['sp_usuario_select' => $parametros]);
            array_push($extras['queries'], $this->get_query($db, $sp, $parametros));
            $usuario_sqlserver = $this->ejecutar_sp_directo($db, $sp, $parametros);
            array_push($extras['responses'], [$sp => $usuario_sqlserver]);
            
            if(is_array($usuario_sqlserver)){
                if(sizeof($usuario_sqlserver) > 0){
                    $usuario_sqlserver = $usuario_sqlserver[0];
                }
            }

            $user = User::where('id_usuario_sqlserver', '=', $id_usuario_sqlserver)->first();
            $id = isset($user) ? $user->id : null;

            if(!isset($usuario_sqlserver) || empty($usuario_sqlserver) || $usuario_sqlserver->id_usuario == null){
                $status = 'fail';
                $message = 'Error. No se encontró el usuario en SQL Server.';
                $code = -5;
                $errors =  ['Usuario no encontrado en SQL Server'];
            }else if($params['id_usuario_sqlserver'] == null){
                $status = 'fail';
                $message = 'Error. El parámetro id_usuario_sqlserver es obligatorio.';
                $code = -7;
                $errors =  ['Parámetros insuficientes o incorrectos'];
            }else if(!isset($password)){
                $status = 'fail';
                $message = 'Error. El campo password no puede estar vacío.';
                $code = -6;
                $errors =  ['Parámetros insuficientes o incorrectos'];
            }else{
                try {
                    if($id_usuario_sqlserver != null){
                        //  define los parametros a actualizar en sqlserver
                        $parametros = [
                            'p_id_usuario' => $id_usuario_sqlserver,
                            'p_apynom' => $usuario_sqlserver->apynom,
                            'p_n_usuario' => $usuario_sqlserver->n_usuario,  
                            'p_email' => $usuario_sqlserver->email,
                            'p_activo' => 1,
                            'p_id_area' => NULL,
                            'p_supervisor' => NULL,
                            'p_desactivar' => NULL,
                            'p_activar' => NULL,
                            'p_bloqueado' => 0,
                            'p_finaliza_clave' => NULL,
                            'p_clave_nueva' => $params['password'],
                            'p_id_usuario_envia' => $id_usuario_sqlserver_default,
                            'p_atributos' => NULL,
                        ];
                        //  actualiza los datos del usuario
                        array_push($extras['sps'], ['sp_usuario_Update' => $parametros]);
                        array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_Update', $parametros));
                        $ret = $this->ejecutar_sp_directo('admin', 'sp_usuario_Update', $parametros);
                        array_push($extras['responses'], ['sp_usuario_Update' => $ret]);
                        // $ret = $this->ejecutar_sp_directo('admin', 'sp_usuario_Update', $parametros);
                        if ( !empty($ret) && !empty($ret[0]) && $ret[0]->filas > 0 ){
                            // si se actualizó correctamente cambia la bandera
                            $sqlserver_actualizado = true;
                        }else{ 
                            // sino modifica la bandera para que no se realice el cambio en la base de datos
                            $actualizar = false;
                        }
                        // si se puede actualizar, ya sea porque se actualizó sqlserver o porque el usuario no está en sqlserver
                        if($actualizar){
                            if($user != null){
                                $user->password = Hash::make($params['password']);
                                $affected = $user->save();
                                array_push($extras['responses'], ['api_bpay' => $affected]);
                            }else{
                                $affected = false;
                                array_push($extras['responses'], ['api_bpay' => $affected]);
                            }
                        }
                    }

                    if($sqlserver_actualizado && $affected){
                        $status = 'ok';
                        $message = 'Contraseña reseteada en ambas bases de datos';
                        $count = 1;
                        $code = 1;
                        $data = [
                            'usuario_sqlserver' => $usuario_sqlserver,
                            'usuario' => $user,
                            'api_bpay_actualizado' => $affected,
                            'sqlserver_actualizado' => $sqlserver_actualizado
                        ];
                    }else if($sqlserver_actualizado && $user == null){
                        $status = 'ok';
                        $message = 'Contraseña reseteada en sqlserver, No tiene usuario en api_bpay';
                        $count = 1;
                        $code = 2;
                        $data = [
                            'usuario_sqlserver' => $usuario_sqlserver,
                            'usuario_api' => $user,
                            'api_bpay_actualizado' => $affected,
                            'sqlserver_actualizado' => $sqlserver_actualizado
                        ];
                    }else if($sqlserver_actualizado && $user != null && !$affected){
                        $status = 'fail';
                        $message = 'No se pudo resetear la contraseña en api_bpay.';
                        $count = 0;
                        $code = -8;
                        $data = [
                            'usuario_sqlserver' => $usuario_sqlserver,
                            'usuario_apia' => $user,
                            'api_bpay_actualizado' => $affected,
                            'sqlserver_actualizado' => $sqlserver_actualizado
                        ];
                    }else{
                        $status = 'fail';
                        $message = 'No se pudo resetear la contraseña.';
                        $count = 0;
                        $code = -4;
                        $data = [
                            'usuario_sqlserver' => $usuario_sqlserver,
                            'usuario_apia' => $user,
                            'api_bpay_actualizado' => $affected,
                            'sqlserver_actualizado' => $sqlserver_actualizado
                        ];
                    }
                    
                } catch (\Throwable $th) {
                    $status = 'fail';
                    $message = 'No se pudo resetear la contraseña';
                    array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
                    $code = -3;
                }
            }
            
            // return $error;
            $extras['api_bpay_actualizado'] = $affected;
            $extras['sqlserver_actualizado'] = $sqlserver_actualizado;
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
                'logged_user' => $logged_user
            ]);
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user
            ]);
        } 
    }
}