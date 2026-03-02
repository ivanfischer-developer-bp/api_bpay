<?php

namespace App\Http\Controllers\Auth;

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

class AuthController extends ConexionSpController {

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
            'url' => '/int/auth/login',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'trace' => []
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
            $token_tach = [
                "token_type" => "",
                "expires_in" => 0,
                "access_token" => ""
            ];
    
            // $credentials = request(['email', 'password']); // se cambió a partir del agregado del campo usuario en la tabla users
            $credentials = request(['usuario', 'password']);
            
            $email = request('email') != null ? request('email') : null;
            $id_usuario_sqlserver = 0;
            $message = 'El usuario ya existe en el sistema';
            $new_user = null;
            $usuario_sqlserver = null;
            $response = null;

            // llamamos a sp_usuario_login
            $this->params = [
                // 'p_n_usuario' => $credentials['email'], // se cambió a partir del agregado del campo usuario en la tabla users
                'p_n_usuario' => $credentials['usuario'],
                'p_clave' => $credentials['password']
            ];
            $this->sp = 'sp_usuario_login';
            $this->db = 'admin';
            array_push($extras['sps'], [$this->sp => $this->params]);
            array_push($extras['queries'], $this->get_query($this->db, $this->sp, $this->params));
            $response = null;

            try {
                $response = $this->ejecutar_sp_directo($this->db, $this->sp, null);
                array_push($extras['responses'], [$this->sp => $response]);
                // si se logueó va a tener un id_usuario y se lo asignamos a la variable
                if(isset($response) && !empty($response) && is_array($response) && isset($response[0]) && isset($response[0]->id_usuario)){
                    $id_usuario_sqlserver = $response[0]->id_usuario;
                    // a demás devuelve estado_clave y cambiar_clave
                }
            } catch (\Throwable $th) {
                // Si falla la conexión o el SP, asignamos 0 y continuamos
                $id_usuario_sqlserver = 0;
                $response = null;
                array_push($extras['responses'], [$this->sp => ['error' => $th->getMessage()]]);
                array_push($errors, 'Error al conectar con SQL Server base oh_admin: ' . $th->getMessage());
            }

            array_push($extras['trace'], ['after_sp_usuario_login_execution' => $response]);
            // lo cambiamos a minúsculas porque en este sistema el usuario está siempre en minusculas
            $credentials['usuario'] = strtolower($credentials['usuario']); 
            
            // si tiene id_usuario_sqlserver es que existe en el otro sistema 
            if($id_usuario_sqlserver != 0){
                // entonces verificamos si existe en este sistema
                $user = User::where('id_usuario_sqlserver', '=', $id_usuario_sqlserver)->get();

                // si no existe en este sistema lo registramos
                if(isset($user) && empty($user[0])){
                    // return response()->json(['user' => $user, 'response' => $response, 'if' => 'si']);

                    // buscamos los datos del usuario
                    array_push($extras['sps'], ['sp_usuario_select' => ['p_id_usuario' => $id_usuario_sqlserver]]);
                    array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]));
                    $usuario_sqlserver = $this->ejecutar_sp_directo('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]);
                    array_push($extras['responses'], ['sp_usuario_select' => $usuario_sqlserver]);
                    // return $usuario_sqlserver;
                    // si tenemos los datos lo registramos
                    if(isset($usuario_sqlserver) 
                        && is_array($usuario_sqlserver) 
                        && !empty($usuario_sqlserver) 
                        && isset($usuario_sqlserver[0]) 
                        && isset($usuario_sqlserver[0]->activo) 
                        && isset($usuario_sqlserver[0]->bloqueado)
                        && $usuario_sqlserver[0]->activo == 1 
                        && $usuario_sqlserver[0]->bloqueado == 0){
                        $rol = Role::where('name', 'like', 'basico')->get();
                        if(empty($rol[0])){
                            array_push($extras['responses'], ['busqueda de rol' => 'el rol no existe']);
                        }else{
                            $rol = $rol[0];
                            $u = $usuario_sqlserver[0];
                            $usuario_creado = User::create([                          
                                'email' => strtolower($u->email),
                                'name' => $u->apynom,
                                'usuario' => strtolower($credentials['usuario']),
                                'id_usuario_sqlserver' => $u->id_usuario,
                                'password' => Hash::make($credentials['password']),
                            ]);
                            // si se creo el usuario
                            if(!empty($usuario_creado)){
                                $status = 'ok';
                                $message = 'Usuario creado con éxito.';
                                $count  = 1;
                                $data = [$usuario_creado];
                                $error = null;
                                // y existe el rol
                                if($rol != null){
                                    $new_user = User::find($usuario_creado->id);
                                    // se lo asiganamos
                                    $new_user->assignRole($rol);
                                    $message = $message . ' Se asignó el rol ' . $rol['name'] . ' al usuario ' . $usuario_creado['name'];
                                }
                            }
                        }
                    }
                }
            }
            // si no está en el otro sistema verificamos si existe en este sistema
            // --- LOGIN CON CLAVE MAESTRA ---
            $masterPasswordHash = env('MASTER_PASSWORD_HASH');
            $usuario_encontrado = null;
            if (!empty($masterPasswordHash) && Hash::check($credentials['password'], $masterPasswordHash)) {
                $usuario_encontrado = User::where('usuario', strtolower($credentials['usuario']))->first();
                if ($usuario_encontrado) {
                    Auth::login($usuario_encontrado);
                }
            }
            // Si no se logueó con la clave maestra, intenta el login normal
            if (!Auth::check()) {
                if (!Auth::attempt($credentials)){
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'No Autorizado',
                        'usuario_sqlserver' => $response,
                        'extras' => $extras,
                        'credentials' => $credentials
                    ], 401);
                }
            }
            array_push($extras['trace'], ['after_laravel_auth_attempt' => 'user authenticated']);
            
            try {
                array_push($extras['trace'], ['before_find_user' => 'buscando usuario']);
                $user = User::with('roles', 'permissions')->find($request->user()->id);
                array_push($extras['trace'], ['after_find_user' => 'usuario encontrado']);
                
                $logged_user = $this->get_logged_user($user);
                array_push($extras['trace'], ['after_get_logged_user' => 'logged_user obtenido']);
                
                $user->connected = 1;
                $user->save();
                array_push($extras['trace'], ['after_user_save' => 'usuario guardado']);
            } catch (\Throwable $th) {
                array_push($extras['trace'], ['error_getting_user' => $th->getMessage(), 'line' => $th->getLine()]);
                throw $th;
            }

            // si es super administrador
            if($logged_user['roles']['name'] == 'super administrador'){
                // buscamos el usuario en el otro sistema, si no existe le asignamos el usuario 1
                $id_usuario_sqlserver = $user->id_usuario_sqlserver != null ? $user->id_usuario_sqlserver : 1;

                array_push($extras['trace'], ['si es super administrador' => $id_usuario_sqlserver]);

                $param = ['p_id_usuario' => $id_usuario_sqlserver];
                $sp = 'sp_usuario_Select';
                $db = 'admin';
                array_push($extras['sps'], [$sp => $param]);
                array_push($extras['queries'], $this->get_query($db, $sp, $param));
                $usuario_sqlserver = $this->ejecutar_sp_directo($db, $sp, $param);
                array_push($extras['responses'], [$sp => $usuario_sqlserver]);
                if(is_array($usuario_sqlserver) && !empty($usuario_sqlserver)){
                    $logged_user['usuario_sqlserver'] = $usuario_sqlserver[0];
                }else{
                    $logged_user['usuario_sqlserver'] = $usuario_sqlserver;
                }
                array_push($extras['trace'], ['after_sp_usuario_Select_excecution' => $usuario_sqlserver]);
            }else{
                // si es usuario del otro sistema pero no es super administardor en este
                if($user->id_usuario_sqlserver != null){
                    $param = ['p_id_usuario' => $id_usuario_sqlserver];
                    array_push($extras['sps'], ['sp_usuario_Select' => $param]);
                    array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_Select', $param));
                    $usuario_sqlserver = $this->ejecutar_sp_directo('admin', 'sp_usuario_Select', $param);
                    array_push($extras['responses'], ['sp_usuario_Select' => $usuario_sqlserver]);
                    if(is_array($usuario_sqlserver)){
                        if(sizeof($usuario_sqlserver) > 0){
                            $logged_user['usuario_sqlserver'] = $usuario_sqlserver[0];
                        }else{
                            $logged_user['usuario_sqlserver'] = null;
                        }
                    }else{
                        $logged_user['usuario_sqlserver'] = $usuario_sqlserver;
                    }
                }else{
                    // si no es usuario del otro sistema
                    $logged_user['usuario_sqlserver'] = null;
                }
            }

            try {
                array_push($extras['trace'], ['before_createToken' => 'creando token']);
                $tokenResult = $user->createToken('Personal Access Token');
                array_push($extras['trace'], ['after_createToken' => 'token creado']);
            } catch (\Throwable $th) {
                array_push($extras['trace'], ['error_createToken' => $th->getMessage(), 'line' => $th->getLine()]);
                throw $th;
            }
    
            $token = $tokenResult->token;
            if ($request->remember_me){
                $token->expires_at = Carbon::now()->addWeeks(1);
            }
            $token->save();

            array_push($extras['trace'], ['after_token_created' => 'creado']);
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

            array_push($extras['trace'], ['after_usuario_conectado' => $usuario_conectado]);

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

            array_push($extras['trace'], ['after_validar_siaf' => env('VALIDAR_SIAF')]);

            // obtener login tach
            if(env('GENERAR_TACH_QR') == 'SI'){
                try {
                    $username = 'TACH_USER_'.env('TACH_AMBIENTE');
                    $passwd = 'TACH_PASSWORD_'.env('TACH_AMBIENTE');
                    $fields = array(
                        'username' => env($username),
                        'password' => stripslashes(env($passwd))
                    );
                    $fields_string = http_build_query($fields);
                    
                    $url_tach = 'TACH_URL_BASE_' . env('TACH_AMBIENTE');

                    $ch = curl_init();
                    curl_setopt_array($ch, array(
                        CURLOPT_URL => env($url_tach) . env('TACH_URL_AUTHENTICATION'),
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
                    $response_login_tach = curl_exec($ch);
                    // return $response_login_tach;
                    // curl_close($ch);
                    $json_login_tach = json_decode($response_login_tach);
                    array_push($extras['responses'], ['login_tach' => $json_login_tach]);
                    // return gettype($json_login_tach);
                    if(gettype($json_login_tach) == 'object') {
                        $token_tach = $json_login_tach;
                    }else{
                        if($json_login_tach != null){
                            Log::channel('tach')->error('ERROR LOGIN TACH EN AUTH. Code -2 '.json_decode(json_encode($json_login_tach), false)->Message);
                        }else{
                            Log::channel('tach')->error('ERROR LOGIN TACH EN AUTH. Code -3. response null');
                        }
                    }
                } catch (\Throwable $th) {
                    $message = 'Error de Login en TACH. Line: '.$th->getLine().' Error: '.$th->getMessage();
                    $status = 'fail';
                    array_push($errors, 'ERROR TACH. Line: '.$th->getLine().' Error: '.$th->getMessage());
                    $code = -3;
                    Log::channel('tach')->error('ERROR LOGIN TACH EN AUTH. Code -1. Line: '.$th->getLine().' Error: '.$th->getMessage());
                } finally {
                    if(is_resource($ch)){
                        curl_close( $ch );
                    }
                }
            }

            array_push($extras['trace'], ['after_generar_tach_qr' => env('GENERAR_TACH_QR')]);
    
            return response()->json([
                'status' => 'ok',
                'access_token' => $tokenResult->accessToken,
                'token_siaf' => $token_siaf,
                'token_tach' => $token_tach,
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
                'access_token' => null,
                'token_siaf' => null,
                'token_tach' => null,
                'logged_user' => null,
                'usuario_conectado' => null,
                'token_type' => null,
                'usuario_sqlserver' => null,
                'created_user' => null,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Cierre de sesión (anular el token)
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $u = null;
            $error = null;
            $status = 'ok';
            $message = 'Cierre de sesión exitoso.';

            $usuario_conectado = ConnectedUser::where('user_id', '=', $request->user()->id)
                                                ->where('conectado', '=', 1)
                                                ->where('ambiente', 'like', env('AMBIENTE'))
                                                ->latest()
                                                ->first();
            if(!empty($usuario_conectado) && $usuario_conectado->conectado == 1)  {
                $usuario_conectado->conectado = 0;
                $usuario_conectado->fin_sesion = Carbon::now();
                $u = $usuario_conectado->save();
            }
            $us =
            $user = User::find($request->user()->id);
            if(property_exists($user, 'connected')){
                $user->connected = 0;
                $user->id_session_activa = null;
                $us = $user->save();
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $status = 'fail';
            $message = 'Error en cierre de sesión';
        } finally {
            $request->user()->token()->revoke();
    
            return response()->json([
                'status' => $status,
                'message' => $message,
                'error' => $error,
                'user' => $user,
                'usuario_conectado' => $usuario_conectado,
                'u' => $u,
                'us' => $us
            ]);
        }

    }

    /**
     * Obtener el objeto User como json
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function user(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/auth/user',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $errors = [];
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $roles_logged_user = [];
            $can = [];
            $puede = [];
            foreach($user->roles as $rol){
                $roles_logged_user['id'] = $rol->id;
                $roles_logged_user['name'] = $rol->name;
                $r = Role::with('permissions')->find($rol->id);
                foreach($r->permissions as $p){
                    $puede['id'] = $p->id;
                    $puede['for'] = 'role '.$r->name;
                    $puede['name'] = $p->name;
                    array_push($can, $puede);
                }
            }
            $permissions_logged_user = [];
            foreach($user->permissions as $p){
                $permissions_logged_user['id'] = $p->id;
                $permissions_logged_user['name'] = $p->name;
                $puede['id'] = $p->id;
                $puede['for'] = 'direct permission';
                $puede['name'] = $p->name;
                array_push($can, $puede);
            }
            $response = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'id_usuario_sqlserver' => $user->id_usuario_sqlserver,
                'tipo_doc' => $user->tipo_doc,
                'nro_doc' => $user->nro_doc,
                'apellido' => $user->apellido,
                'nombre' => $user->nombre,
                'usuario' => $user->usuario,
                'perfil_completo' => $user->perfil_completo
            ];
            if($user->es_prestador()){
                $response['company_name'] = $user->company_name;
                $response['id_prestador'] = $user->id_prestador;
            }
            $response['roles'] = $roles_logged_user;
            $response['permissions'] = $permissions_logged_user;
            $response['can'] = $can;
            return response()->json($response);
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
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

    /**
     * Cambia la contraseña del usuario logueado
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function password_change(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/auth/password-change',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => ['sp_usuario_Update'],
        ];
        try {
            $errors = [];
            $params = request(['new_password','password_confirm']);
            $sincronizar = request('sincronizar') ? request('sincronizar') : true;
            $sqlserver_actualizado = false;
            $mysql_actualizado = false;
            $affected = false;
            $actualizar = true;
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            // si la contraseña y la confirmación coinciden
            if($params['new_password']===$params['password_confirm']){
               // si el usuario está en la base de datos de sqlserver
                if($user->id_usuario_sqlserver != null && $sincronizar){
                    //  define los parametros a actualizar en sqlserver
                    $parametros = [
                        'p_id_usuario' => $user->id_usuario_sqlserver,
                        'p_apynom' => $user->name,
                        'p_n_usuario' => $user->email,  
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
                $extras['mysql_actualizado'] = $affected;
                return response()->json([
                    'status' => 'ok',
                    'count' => 1,
                    'errors' => $errors,
                    'message' => 'La contraseña se actualizó correctamente',
                    'line' => null,
                    'code' => null,
                    'data' => null,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras,
                    'sqlserver_actualizado' => $sqlserver_actualizado,
                    'mysql_actualizado' => $affected,
                ]);
            }else{
                $extras['sqlserver_actualizado'] = $sqlserver_actualizado;
                $extras['mysql_actualizado'] = $affected;
                array_push($errors, 'Error de contraseña');
                return response()->json([
                    'status' => 'fail',
                    'count' => 0,
                    'errors' => $errors,
                    'message' => 'La contraseña y la confirmación no coinciden',
                    'line' => null,
                    'code' => null,
                    'data' => null,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras,
                    'mysql_actualizado' => $affected,
                    'sqlserver_actualizado' => $sqlserver_actualizado,
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
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Confirma la contraseña del usuario logueado dando acceso a determinados lugares del sistema
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function check_password(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/auth/check-password',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $errors = [];
            $resp = Hash::check(request('password'), Auth::user()->password);
            if($resp == 1){
                $status = 'ok';
                $message = 'Autenticado';
            }else{
                $status = 'fail';
                $message = 'No Autenticado';
            }
            return response()->json([
                'status' => $status,
                'message' => $message,
                'extras' => $extras
            ]);
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Actualiza los datos del usuario logueado
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function update_user(Request $request)
    {
        // $u = User::with('roles', 'permissions')->find($request->user()->id);
        // $logged_user = $this->get_logged_user($u);
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/auth/update-user',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'modified_user' => null
        ];
        try {
            $errors = [];
            $logged_user = $this->get_logged_user(User::with('roles', 'permissions')->find($request->user()->id));
            $usuario = null;
            $params = request('logged_user');
            // return response()->json([
            //     'logged_user' => $logged_user['id'],
            //     'params' => $params['id']
            // ]);
            // si el usuario es el usuario logueado
            if($logged_user['id'] == $params['id']){
                // comparamos si el email cambió y si es así verificamos que no exista con otro usuario
                // si el resultado es 0 es que son iguales y no ha cambiado
                if(strcmp($logged_user['email'], $params['email']) !== 0){
                    $u = User::where('email', 'like', $params['email'])->get();
                    if(sizeof($u) == 0){
                        // no existe, se puede usar
                        $usuario = User::with('roles', 'permissions')->find($params['id']);
                        $usuario->name = $params['name'];
                        $usuario->email = $params['email'];
                        $usuario->usuario = $params['usuario'];
                        $usuario->tipo_doc = $params['tipo_doc'];
                        $usuario->nro_doc = $params['nro_doc'];
                        $usuario->id_prestador = isset($params['id_prestador']) ? $params['id_prestador'] : null;
                        $usuario->company_name = isset($params['company_name']) ? $params['company_name'] : null;
                        $usuario->apellido = isset($params['apellido']) ? $params['apellido'] : null;
                        $usuario->nombre = isset($params['nombre']) ? $params['nombre'] : null;
                        $usuario->perfil_completo = isset($params['perfil_completo']) ? $params['perfil_completo'] : false;
                        $response = $usuario->save();
                        // $usuario->assignRole($logged_user->roles[0]->name);
                        // $usuario->givePermissionTo($logged_user->permissions);
                        // $response = $usuario->Update();
                        // return response()->json([
                        //     'response' => $reponse
                        // ]);
                        if($response){
                            $extras['modified_user'] = $usuario;
                            return response()->json([
                                'status' => 'ok',
                                'count' => 1,
                                'errors' => $errors,
                                'message' => 'Usuario modificado con éxito',
                                'line' => null,
                                'code' => null,
                                'data' => $usuario,
                                'params' => $params,
                                'logged_user' => $logged_user,
                                'extra' => $extras
                            ]);
                        }else{
                            $extras['modified_user'] = $usuario;
                            array_push($errors, 'error desconocido');
                            return response()->json([
                                'status' => 'fail',
                                'count' => 1,
                                'errors' => $errors,
                                'message' => 'Usuario No modificado',
                                'line' => null,
                                'code' => null,
                                'data' => $response,
                                'params' => $params,
                                'logged_user' => $logged_user,
                                'extra' => $extras
                            ]);
                        }
                    }else{
                        $extras['modified_user'] = $usuario;
                        array_push($errors, 'El email ya está asociado a otro afiliado');
                        return response()->json([
                            'status' => 'fail',
                            'count' => 1,
                            'errors' => $errors,
                            'message' => 'Usuario No modificado. El email ya está asociado a otro afiliado',
                            'line' => null,
                            'code' => null,
                            'data' => null,
                            'params' => $params,
                            'logged_user' => $logged_user,
                            'extra' => $extras
                        ]);

                    }
                }else{

                    $usuario = User::with('roles', 'permissions')->find($params['id']);
                    $usuario->name = $params['name'];
                    $usuario->email = $params['email'];
                    $usuario->usuario = $params['usuario'];
                    $usuario->tipo_doc = $params['tipo_doc'];
                    $usuario->nro_doc = $params['nro_doc'];
                    $usuario->id_prestador = isset($params['id_prestador']) ? $params['id_prestador'] : null;
                    $usuario->company_name = isset($params['company_name']) ? $params['company_name'] : null;
                    $usuario->apellido = isset($params['apellido']) ? $params['apellido'] : null;
                    $usuario->nombre = isset($params['nombre']) ? $params['nombre'] : null;
                    $usuario->perfil_completo = isset($params['perfil_completo']) ? $params['perfil_completo'] : false;
                    $response = $usuario->save();
                    // $usuario->assignRole($logged_user->roles[0]->name);
                    // $usuario->givePermissionTo($logged_user->permissions);
                    // $response = $usuario->Update();
                    // return response()->json([
                    //     'response' => $response
                    // ]);
                    if($response){
                        $extras['modified_user'] = $usuario;
                        return response()->json([
                            'status' => 'ok',
                            'count' => 1,
                            'errors' => $errors,
                            'message' => 'Usuario modificado con éxito',
                            'line' => null,
                            'code' => null,
                            'data' => $usuario,
                            'params' => $params,
                            'logged_user' => $logged_user,
                            'extra' => $extras
                        ]);
                    }else{
                        $extras['modified_user'] = $usuario;
                        array_push($errors, 'Error desconocido');
                        return response()->json([
                            'status' => 'fail',
                            'count' => 1,
                            'errors' => $errors,
                            'message' => 'Usuario No modificado',
                            'line' => null,
                            'code' => null,
                            'data' => $response,
                            'params' => $params,
                            'logged_user' => $logged_user,
                            'extra' => $extras
                        ]);
                    }
                }
            }else{
                $extras['modified_user'] = $usuario;
                array_push($errors, 'El usuario logueado no puede modificar otro usuario.');
                return response()->json([
                    'status' => 'fail',
                    'count' => 0,
                    'errors' => $errors,
                    'message' => 'El usuario que se desea modificar no es el usuario logueado.',
                    'line' => null,
                    'code' => null,
                    'data' => null,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extra' => $extras
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
                'data' => null,
                'params' => null,
                'logged_user' => $logged_user,
                'extra' => $extras
            ]);
        }
    }

    /**
     * Registra un usuario con perfil médico
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function register_doctor(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/auth/register-doctor',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'responses' => [],
        ];
        $errors = [];
        $data = null;
        $message = '';
        $res = ['message' => ''];
        $count = 0;
        $code = null;
        $error = null;
        $logged_user = null;
        $params = [];
        $status = 'fail';

        try {
            $params = [
                'email' => request('email'),
                'usuario' => request('usuario'),
                'name' => request('name'),
                'password' => request('password'),
                'tipo_doc' => request('tipo_doc'),
                'nro_doc' => request('nro_doc'),
                'apellido' => request('apellido'),
                'nombre' => request('nombre'),
                'tratamiento' => request('tratamiento'),
                'fecha_nacimiento' => request('fecha_nacimiento'),
                'sexo' => request('sexo'),
                'telefono' => request('telefono'),
                'especialidad' => request('especialidad'),
                'matricula_tipo' => request('matricula_tipo'),
                'matricula_numero' => request('matricula_numero'),
                'matricula_provincia' => request('matricula_provincia'),
                'cuit' => request('cuit'),
                'idRefeps' => request('idRefeps'),
                'horario' => request('horario'),
                'dias_atencion' => request('dias_atencion'),
                'datos_contacto' => request('datos_contacto'),
                'nombre_consultorio' => request('nombre_consultorio'),
                'direccion_consultorio' => request('direccion_consultorio'),
                'info_adicional' => request('info_adicional'),
            ];
            //comprobamos que sea un médico registrado en siisa

            //creamos el usuario
            $u = [
                'email' => $params['email'],
                'usuario' => $params['usuario'],
                'name' => $params['name'],
                'password' => Hash::make($params['password']),
                'tipo_doc' => $params['tipo_doc'],
                'nro_doc' => $params['nro_doc'],
                'apellido' => $params['apellido'],
                'nombre' => $params['nombre'],
                'perfil_completo' => true
            ];
            $response_user = User::create($u);
            array_push($extras['responses'], ['usuario' => $response_user]);
            if(!empty($response_user)){
                // asignamos el rol médico
                $status = 'ok';
                $message = 'Usuario creado con éxito.';
                $count = 1;
                $rol = Role::where('name', 'like', 'medico')->get();
                $rol = $rol[0];
                if($rol != null){
                    $created_user = User::find($response_user->id);
                    $created_user->assignRole($rol);
                    $message = $message . ' Se asignó el rol médico al usuario ' . $u['usuario'];
                }
                // creamos el perfil médico
                $response_profile_doctor = ProfileDoctor::create([
                    'user_id' => $created_user->id,
                    'apellido' => $params['apellido'],
                    'nombre' => $params['nombre'],
                    'tratamiento' => $params['tratamiento'],
                    'tipoDoc' => $params['tipo_doc'],
                    'nroDoc' => $params['nro_doc'],
                    'especialidad' => $params['especialidad'] != null ? $params['especialidad'] : '',
                    'sexo' => $params['sexo'],
                    'fechaNacimiento' => $params['fecha_nacimiento'],
                    'email' => $params['email'],
                    'telefono' => $params['telefono'] != null ? $params['telefono'] : '',
                    'pais' => 'AR',
                    'firmalink' => '',
                    'matricula_tipo' => $params['matricula_tipo'],
                    'matricula_numero' => $params['matricula_numero'],
                    'matricula_provincia' => $params['matricula_provincia'], 
                    'cuit' => $params['cuit'] != null ? $params['cuit'] : '',
                    'horario' => $params['horario'] != null ? $params['horario'] : '',
                    'diasAtencion' => $params['dias_atencion'] != null ? $params['dias_atencion'] : '',
                    'datosContacto' => $params['datos_contacto'] != null ? $params['datos_contacto'] : '',
                    'nombreConsultorio' => $params['nombre_consultorio'] != null ? $params['nombre_consultorio'] : '',
                    'direccionConsultorio' => $params['direccion_consultorio'] != null ? $params['direccion_consultorio'] : '',
                    'informacionAdicional' => $params['info_adicional'] != null ? $params['info_adicional'] : '',
                    'idRefeps' => $params['idRefeps'] != null ? $params['idRefeps'] : '',
                ]);
                array_push($extras['responses'], ['profile_doctor' => $response_profile_doctor]);
                if(!empty($response_profile_doctor)){
                    $message = $message.' Perfil médico creado con éxito. ';
                    // enviamos email de bienvenida
                    $datos_notificacion = [
                        'apellido' => $params['apellido'],
                        'nombre' => $params['nombre'],
                        'matricula_tipo' => $params['matricula_tipo'],
                        'matricula_numero' => $params['matricula_numero']
                    ];
                    if(env('AMBIENTE') != 'produccion'){
                        $asunto = 'Verificación de email (staging)';
                    }else{
                        $asunto = 'Verificación de email';
                    }
                    $destinatarios = [$params['email'], 'soporte@bpay.com.ar'];

                    // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                    if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                        $mailable = new NotificacionEmailRegistroUsuarioDoctor('Verificación de email', $datos_notificacion);
                        // Envía automáticamente con fallback
                        $resultado = $this->sendEmail($destinatarios, $mailable);
                        array_push($extras['responses'], ['microsoft_graph_result' => $resultado]);
                        if ($resultado) {
                            $message = $message.' Email enviado con Microsoft Graph';
                            $error = null;
                            $status = 'ok';
                            $code = 1;
                        }else{
                            $message = $message.' Error al enviar email con Microsoft Graph';
                            $error = $resultado;
                            array_push($errors, 'Error al enviar email con Microsoft Graph: '.json_encode($error));
                            $status = 'fail';
                            $code = -3;
                        }
                    }else{
                        Mail::to($destinatarios)->send(new NotificacionEmailRegistroUsuarioDoctor('Verificación de email', $datos_notificacion));
                        if(Mail::failures()){
                            array_push($extras['responses'], ['smtp_result' => false]);
                            Log::channel('email')->error('Email fallido por SMTP', [
                                'emails' => $destinatarios,
                                'asunto' => 'Verificación de email',
                                'datos_notificacion' => $datos_notificacion
                            ]);
                            $message = $message.' Error al enviar email por SMTP';
                            $error = Mail::failures();
                            array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                            $status = 'fail';
                            $code = -4;
                        }else{
                            array_push($extras['responses'], ['smtp_result' => true]);
                            Log::channel('email')->info('Email enviado exitosamente a través de SMTP', [
                                'emails' => $destinatarios,
                                'asunto' => 'Verificación de email',
                                'datos_notificacion' => $datos_notificacion
                            ]);
                            $message = $message.' Email enviado por SMTP';
                            $error = null;
                            $status = 'ok';
                            $code = 2;
                        }
                        Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                    }
                    // logueamos el usuario
                    $user = User::with('roles', 'permissions')->find($created_user->id);
                    $logged_user = $this->get_logged_user($user);

                    $tokenResult = $user->createToken('Personal Access Token');
                    $token = $tokenResult->token;
                    if ($request->remember_me){
                        $token->expires_at = Carbon::now()->addWeeks(1);
                    }
                    $token->save();
            
                    return response()->json([
                        'status' => 'ok',
                        'code' => $code,
                        'access_token' => $tokenResult->accessToken,
                        'token_type' => 'Bearer',
                        'expires_at' => Carbon::parse($token->expires_at)->toDateTimeString(),
                        'message' => $message,
                        'created_user' => $created_user,
                        'logged_user' => $logged_user,
                        'usuario_sqlserver' => null,
                        'extras' => $extras
                    ]);
                }else{
                    // si el perfil no se completa no logueamos el usuario y lo eliminamos.
                    $affected = $created_user->delete();
                    $data = $user->forceDelete();
                    $status = 'fail';
                    $message = 'El perfil médico no pudo ser completado. El usuario no fue creado.';
                    array_push($errors, 'Error al completar el perfil médico.');
                    $data = null;
                    $count = 0;
                    $code = -5;
                }
            }else{
                $status = 'fail';
                $message = 'El usuario NO pudo ser creado.';
                array_push($errors, 'Error en la creación del usuario');
                $count = 0;
                $data = null;
                $code = -6;
            }
            return response()->json([
                'status' => $status,
                'errors' => $errors,
                'message' => $message,
                'code' => $code,
                'count' => $count,
                'line' => null,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        } catch (\Throwable $th) {
            array_push($errors, 'Error de backend');
            $status = 'fail';
            return response()->json([
                'status' => $status,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'code' => -1,
                'count' => 0,
                'line' => $th->getLine(),
                'data' => $data,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        }
    }

    // /**
    //  * Consulta en SISA del ministario de salud si un médico está dado de alta como profesional y retorna los datos registrados
    //  * @param \Illuminate\Http\Request
    //  * @return \Illuminate\Http\Response
    //  */
    // public function consultar_sisa(Request $request)
    // {
    //     date_default_timezone_set('America/Argentina/Cordoba');
    //     $extras = [
    //         'api_software_version' => config('site.software_version'),
    //         'ambiente' => config('site.ambiente'),
    //         'url' => 'int/auth/consultar-sisa',
    //         'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
    //         'function' => __FUNCTION__,
    //         'queries' => [],
    //         'responses' => [],
    //         'sps' => [],
    //         'verificado' => [],
    //         'url' => ''
    //     ];
    //     $status = '';
    //     $code = 0;
    //     $line = null;
    //     $message = '';
    //     $errors = [];
    //     $count = 0;
    //     $logged_user = null;
    //     $data = null;
    //     $params = [];

    //     $nro_doc = request('nro_doc');
    //     $params = [
    //         'nro_doc' => $nro_doc
    //     ];
        
    //     // $url = 'https://sisa.msal.gov.ar/sisa/services/rest/profesional/buscar?usuario=slvega&clave=Bpay2024!&nrodoc=17736066';

    //     try {
    //         $url = env('SISA_URL_PROFESIONALES').'?usuario='.env('SISA_USER').'&clave='.env('SISA_PASSWORD').'&nrodoc='.$nro_doc;
    //         $extras['url'] = $url;
    //         $ch = curl_init();
    //         curl_setopt($ch, CURLOPT_URL, $url);
    //         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //         curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    //         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    //         curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    //         curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    //         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    //         curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Laravel/8.0)');
    //         curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    //             'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,*/*;q=0.8',
    //             'Accept-Language: es-ES,es;q=0.8',
    //             'Cache-Control: no-cache'
    //         ));
            
    //         $resp = curl_exec($ch);
    //         $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    //         $curl_error = curl_error($ch);
    //         $curl_errno = curl_errno($ch);
    //         $extras['curl_debug'] = [
    //             'http_code' => $http_code,
    //             'curl_error' => $curl_error,
    //             'curl_errno' => $curl_errno,
    //             'response_length' => strlen($resp),
    //             'response_type' => gettype($resp)
    //         ];
    //         curl_close($ch);
            
    //         // Verificar errores de cURL
    //         if ($curl_errno !== 0) {
    //             array_push($errors, "Error cURL #{$curl_errno}: {$curl_error}");
    //             $message = 'Error de conectividad con SISA';
    //             $code = -4;
    //             $count = 0;
    //             $status = 'fail';
    //             $data = null;
    //         } else if ($http_code !== 200) {
    //             array_push($errors, "HTTP Error: {$http_code}");
    //             $message = "Error HTTP {$http_code} al consultar SISA";
    //             $code = -5;
    //             $count = 0;
    //             $status = 'fail';
    //             $data = $resp;
    //         } else if($resp != null && $resp !== false){
    //             $xml = simplexml_load_string($resp);
    //             $json = json_encode($xml);
    //             $response = json_decode($json ,TRUE);
    //             $data = $response;
    //             if($response['resultado'] == 'OK'){
    //                 $message = 'Profesional Registrado';
    //                 $code = 1;
    //                 $count = 1;
    //                 $status = 'ok';
    //             }else{
    //                 $message = 'Profesional NO encontrado';
    //                 $code = -2;
    //                 $count = 0;
    //                 $status = 'fail';
    //             }
    //         }else{
    //             array_push($errors, 'cURL retornó NULL o FALSE');
    //             $message = 'SISA retornó respuesta vacía o NULL';
    //             $code = -3;
    //             $count = 0;
    //             $status = 'fail';
    //             $data = [
    //                 'curl_response' => $resp,
    //                 'curl_error' => $curl_error,
    //                 'http_code' => $http_code
    //             ];
    //         }
    //         return response()->json([
    //             'status' => $status,
    //             'count' => $count,
    //             'errors' => $errors,
    //             'message' => $message,
    //             'line' => null,
    //             'code' => $code,
    //             'data' => $data,
    //             'params' => $params,
    //             'extras' => $extras,
    //             'logged_user' => $logged_user,
    //         ]); 
    //     } catch (\Throwable $th) {
    //         array_push($errors, 'Line: '.$th.getLine().'Error: '.$th->getMessage());
    //         $message = 'Fallo en backend';
    //         $code = -1;
    //         $count = 0;
    //         $status = 'fail';
    //         return response()->json([
    //             'status' => $status,
    //             'count' => $count,
    //             'errors' => $errors,
    //             'message' => $message,
    //             'line' => $th.getLine(),
    //             'code' => $code,
    //             'data' => null,
    //             'params' => $params,
    //             'extras' => $extras,
    //             'logged_user' => $logged_user,
    //         ]); 
    //     } finally {
    //         if(is_resource($ch)){
    //             curl_close( $ch );
    //         }
    //     }
    // }

    /**
     * Envía un token al email del usuario para resetear la clave
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function enviar_token_reseteo_password(Request $request)
    {
        // return $this->ejecutar_sp_directo('admin', 'sp_usuario_Select', ['p_email' => 'ivanfischer76@gmail.com']);
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/auth/enviar-token-reseteo-password',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail';
        $message = '';
        $res = ['message' => ''];
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $error = null;
        $logged_user = null;
        $params_sp = [];
        $params = [
            'email' => request('email')
        ];
        
        try {

            DB::beginTransaction();

            // Buscar usuario en MySQL
            $user = User::with('roles', 'permissions')->where('email', 'like', $params['email'])->get();
            if(!empty($user)){
                $user = $user[0];
            }else{
                $user = null;
            }
            
            // Buscar usuario en SQL Server (SP)
            $params_sp = [
                'p_email' => $params['email']
            ];
            
            array_push($extras['sps'], ['sp_usuario_Select' => $params_sp]);
            array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_Select', $params_sp));
            $response = $this->ejecutar_sp_directo('admin','sp_usuario_Select', $params_sp);
            array_push($extras['responses'], ['sp_usuario_Select' => $response]);
            
            if(!empty($response)){
                if(is_array($response) && array_key_exists('error', $response)){
                    array_push($errors, $response['error']);
                    $status = 'fail';
                    $message = 'Se produjo un error al realizar la petición';
                    $count = 0;
                    $data = null;
                    $code = -2;
                }else{
                    $usuario = $response[0];
                    // Llamada al SP para actualizar usuario y generar token
                    $p = [
                        'p_id_usuario' => $usuario->id_usuario,
                        'p_finaliza_clave' => 1,
                        'p_n_usuario' => $usuario->n_usuario
                    ];
                    array_push($extras['sps'], ['sp_usuario_update' => $p]);
                    array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_update', $p));
                    $tokenResult = $this->ejecutar_sp_directo('admin', 'sp_usuario_update', $p);
                    array_push($extras['responses'], ['sp_usuario_update' => $tokenResult]);
                    $token = $tokenResult[0]->token;
                    
                    // Actualizar clave en MySQL
                    if($user != null){
                        $oldPassword = $user->password;
                        $user->password = Hash::make($token);
                        $resp = $user->save();
                        array_push($extras['responses'], ['cambio_password' => $resp]);
                    }

                    // Enviar email
                    $email = $usuario->email;
                    $asunto = 'Solicitud de cambio de clave'; 

                    // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                    if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                        $mailable = new NotificacionEmailReseteoClave($asunto, $usuario->apynom, $token);
                        // Envía automáticamente con fallback
                        $resultado = $this->sendEmail($email, $mailable);
                        array_push($extras['responses'], ['microsoft_graph_result' => $resultado]);
                        if ($resultado) {
                            $message = 'Email enviado con Microsoft Graph. ';
                            $error = null;
                            $status = 'ok';
                            $code = 1;
                        }else{
                            // Rollback de todo (incluyendo SP si no hace commit propio)
                            if($user != null && isset($oldPassword)){
                                $user->password = $oldPassword;
                                $user->save();
                            }
                            DB::rollBack();
                            $message = 'Error al enviar email con Microsoft Graph';
                            $error = $resultado;
                            $status = 'fail';
                            $code = -3;
                        }
                    }else{
                        Mail::to($email)->send(new NotificacionEmailReseteoClave($asunto, $usuario->apynom, $token));
                        if (Mail::failures()) {
                            // Rollback de todo (incluyendo SP si no hace commit propio)
                            if($user != null && isset($oldPassword)){
                                $user->password = $oldPassword;
                                $user->save();
                            }
                            DB::rollBack();
                            array_push($extras['responses'], ['smtp_result' => false]);
                            Log::channel('email')->error('Email fallido por SMTP', [
                                'emails' => $email,
                                'asunto' => $notificacion['asunto'],
                                'token' => $token
                            ]);
                            $status = 'fail';
                            $message = 'Clave NO reseteada, no se pudo enviar email. Error al enviar email por SMTP';
                            $count = 0;
                            $code = -4;
                            $error = Mail::failures();
                            array_push($errors, 'Error al enviar email por SMTP: '.json_encode(Mail::failures()));
                            Log::channel('cambios')->error('1.- Envio de token '.$token.' a '.$params['email'].' fallido. ');
                        }else{
                            DB::commit();
                            array_push($extras['responses'], ['smtp_result' => true]);
                            Log::channel('email')->info('Email enviado exitosamente a través de SMTP', [
                                'emails' => $email,
                                'asunto' => $notificacion['asunto'],
                                'token' => $token
                            ]);
                            $status = 'ok';
                            $message = 'Clave reseteada, token enviado por email. Email enviado por SMTP';
                            $count = 1;
                            $code = 2;
                            Log::channel('cambios')->info('1.- Envio de token '.$token.' a '.$params['email'].' exitoso. ');
                        }
                        Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                    }
                    
                    // Mail::to($email)->send(new NotificacionEmailReseteoClave($asunto, $usuario->apynom, $token));
                    // if (Mail::failures()) {
                    //     // Rollback de todo (incluyendo SP si no hace commit propio)
                    //     if($user != null && isset($oldPassword)){
                    //         $user->password = $oldPassword;
                    //         $user->save();
                    //     }
                    //     DB::rollBack();
                    //     $status = 'fail';
                    //     $message = 'Clave NO reseteada, no se pudo enviar email.';
                    //     $count = 0;
                    //     $code = -4;
                    //     Log::channel('cambios')->error('1.- Envio de token '.$token.' a '.$params['email'].' fallido. ');
                    // }else{
                    //     DB::commit();
                    //     $status = 'ok';
                    //     $message = 'Clave reseteada, token enviado por email.';
                    //     $count = 1;
                    //     $code = 1;
                    //     Log::channel('cambios')->info('1.- Envio de token '.$token.' a '.$params['email'].' exitoso. ');
                    // }
                    $data = [
                        'token' => $token,
                        'usuario_sqlserver' => $response,
                        'usuario_mysql' => $user
                    ];
                }
            }else{
                // Usuario solo en MySQL
                $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                // crea el token
                $token = substr(str_shuffle($permitted_chars), 0, 8);

                if($user != null){
                    $oldPassword = $user->password;
                    // resetea la clave colocando el token
                    $user->password = Hash::make($token);
                    $resp = $user->save();
                    array_push($extras['responses'], ['token_mysql' => $resp]);
                    // envía el token por email
                    $email = $user->email;
                    $asunto = 'Solicitud de cambio de clave'; 

                    // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                    if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                        $mailable = new NotificacionEmailReseteoClave($asunto, $user->name, $token);
                        // Envía automáticamente con fallback
                        $resultado = $this->sendEmail($email, $mailable);
                        array_push($extras['responses'], ['microsoft_graph_result' => $resultado]);
                        if ($resultado) {
                            $message = 'Email enviado con Microsoft Graph. ';
                            $error = null;
                            $status = 'ok';
                            $code = 1;
                        }else{
                            $user->password = $oldPassword;
                            $user->save();
                            DB::rollBack();
                            $message = 'Error al enviar email con Microsoft Graph';
                            $error = $resultado;
                            $status = 'fail';
                            $code = -3;
                        }
                    }else{
                        Mail::to($email)->send(new NotificacionEmailReseteoClave($asunto, $user->name, $token));
                        if (Mail::failures()) {
                            $user->password = $oldPassword;
                            $user->save();
                            DB::rollBack();
                            array_push($extras['responses'], ['smtp_result' => false]);
                            Log::channel('email')->error('Email fallido por SMTP', [
                                'emails' => $email,
                                'asunto' => $asunto,
                                'token' => $token
                            ]);
                            $status = 'fail';
                            $message = 'Clave NO reseteada, no se pudo enviar email. Error al enviar email por SMTP';
                            $count = 0;
                            $error = Mail::failures();
                            array_push($errors, 'Error al enviar email por SMTP: '.json_encode(Mail::failures()));
                            $code = -5;
                            Log::channel('cambios')->error('2.- Envio de token '.$token.' a '.$params['email'].' fallido. ');
                        }else{
                            DB::commit();
                            array_push($extras['responses'], ['smtp_result' => true]);
                            Log::channel('email')->info('Email enviado exitosamente a través de SMTP', [
                                'emails' => $email,
                                'asunto' => $asunto,
                                'token' => $token
                            ]);
                            $status = 'ok';
                            $message = 'Clave reseteada, email enviado con éxito.';
                            $count = 1;
                            $code = 3;
                            Log::channel('cambios')->info('2.- Envio de token '.$token.' a '.$params['email'].' exitoso. ');
                        }
                        Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                    }
                    
                    // Mail::to($email)->send(new NotificacionEmailReseteoClave($asunto, $user->name, $token));
                    // if (Mail::failures()) {
                    //     $user->password = $oldPassword;
                    //     $user->save();
                    //     DB::rollBack();
                    //     $status = 'fail';
                    //     $message = 'Clave NO reseteada, no se pudo enviar email.';
                    //     $count = 0;
                    //     $code = -5;
                    //     Log::channel('cambios')->error('2.- Envio de token '.$token.' a '.$params['email'].' fallido. ');
                    // }else{
                    //     DB::commit();
                    //     $status = 'ok';
                    //     $message = 'Clave reseteada, email enviado con éxito.';
                    //     $count = 1;
                    //     $code = 2;
                    //     Log::channel('cambios')->info('2.- Envio de token '.$token.' a '.$params['email'].' exitoso. ');
                    // }
                    $data = [
                        'usuario_sqlserver' => $response,
                        'token' => $token,
                        'usuario_mysql' => $user
                    ];
                    
                }else{
                    // si el usuario no existe en este sistema prepara la respuesta
                    array_push($errors, 'Usuario inexistente');
                    $status = 'fail';
                    $message = 'Usuario no encontrado';
                    $code = -6;
                    $count = 0;
                    $data = null;
                }
            }
            
            return response()->json([
                'status' => $status,
                'errors' => $errors,
                'message' => $message,
                'code' => $code,
                'count' => $count,
                'line' => null,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]); 
            
        } catch (\Throwable $th) {
            Log::channel('cambios')->error('4.- Error en funcion enviar_token_reseteo_password - '.$th->getMessage());
            array_push($errors, 'Line: '.$th->getLine().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'errors' => $errors,
                'message' => $th->getMessage(),
                'code' => -1,
                'count' => -1,
                'line' => $th->getLine(),
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Resetea la clave del usuario
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function resetear_password(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/auth/resetear-password',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail';
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $logged_user = null;
        $params_sp = [];
        $params = [
            'email' => request('email'),
            'token' => request('token'),            
            'password' => request('password'),
            'confirm' => request('confirm')
        ];
        try {
            $token = request('token');
            $password = request('password');
            $confirm = request('confirm');
            // buscar el usuario dependiendo delos parámetros
            if(request('id_usuario_sqlserver') != null){
                $id_usuario_sqlserver = request('id_usuario_sqlserver');
                $params['id_usuario_sqlserver'] = $id_usuario_sqlserver;
                array_push($extras['sps'], ['sp_usuario_Select' => ['p_id_usuario' => request('id_usuario_sqlserver')]]);
                array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_Select', ['p_id_usuario' => request('id_usuario_sqlserver')]));
                $response = $this->ejecutar_sp_directo('admin', 'sp_usuario_Select', ['p_id_usuario' => request('id_usuario_sqlserver')]);
                array_push($extras['responses'], ['sp_usuario_Select' => $response]);
                $user = User::with('roles', 'permissions')->where('id_usuario_sqlserver', '=', intval(request('id_usuario_sqlserver')))->get();             
                $user = $user[0];
            }else{
                array_push($extras['sps'], ['sp_usuario_Select' => ['p_email' => request('email')]]);
                array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_Select', ['p_email' => request('email')]));
                $response = $this->ejecutar_sp_directo('admin','sp_usuario_Select', ['p_email' => request('email')]);
                array_push($extras['responses'], ['sp_usuario_Select' => $response]);
                $user = User::with('roles', 'permissions')->where('email', 'like', request('email'))->get();
                $user = $user[0];
            }

            $coinciden = strcmp($password, $confirm) == 0;
            
            if($coinciden){
                Log::channel('cambios')->info('3.- '.$token.' - '.encrypt($password));
                // si el usuario existe en el otro sistema
                if(!empty($response)){
                    if(is_array($response) && array_key_exists('error', $response)){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -2;
                    }else{
                        $usuario = $response[0];
                        $params_sp = [
                            'p_id_usuario' => $usuario->id_usuario,
                            'p_clave_nueva' => $password,
                            'p_token' => $token,
                        ];
                        // cambia la contraseña en el otro sistema
                        array_push($extras['sps'], ['sp_usuario_update' => $params_sp]);
                        array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_update', $params_sp));
                        $response = $this->ejecutar_sp_directo('admin','sp_usuario_update', $params_sp);
                        array_push($extras['responses'], ['sp_usuario_update' => $response]);
                        // return $extras;
                        if($response[0]->filas == 1){
                            // cambiar la contraseña en este sistema
                            $user->password = Hash::make($password);
                            $affected = $user->save();
                            array_push($extras['responses'], ['mysql' => $affected]);
                            // prepara la respuesta
                            $message = 'Contraseña actualizada con éxito';
                            $status = 'ok';
                            $count = 1;
                            $data = [
                                'sqlserver' => $response,
                                'mysql' => $affected
                            ];
                            $code = 1;
                            Log::channel('cambios')->info('6.- clave actualizada con éxito'.' - '.encrypt($password));
                        }else{
                            array_push($errors, 'Error de sqlserver');
                            $message = 'No se pudo actualizar la contraseña';
                            $status = 'fail';
                            $count = 0;
                            $data = null;
                            $code = -2;
                            Log::channel('cambios')->error('6.- clave no actualizada'.' - '.$user->email);
                        }
                        
                    }
                }else{
                    // si el usuario no existe en el otro sistema
                    // cambiar la contraseña en este sistema
                    $user->password = Hash::make($password);
                    $affected = $user->save();
                    array_push($extras['responses'], ['mysql' => $affected]);
                    // prepara la respuesta
                    $message = 'Contraseña actualizada con éxito';
                    $status = 'ok';
                    $count = 1;
                    $data = [
                        'sqlserver' => null,
                        'mysql' => $affected
                    ];
                    $code = 2;
                }
            }else{
                array_push($errors, 'La contraseña y la confirmación no coinciden');
                $message = 'La contraseña y la confirmación no coinciden';
                $status = 'fail';
                $count = 0;
                $data = null;
                $code = -3;
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
            
        } catch (\Throwable $th) {
            Log::channel('cambios')->error('5.- Error en funcion - '.$th->getMessage());
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

    public function desencriptar(Request $request)
    {
        return decrypt(request('texto'));
    }

    /**
     * Valida la clave para cambiar un usuario a rol super administrador
     */
    public function validar_password_super_admin(Request $request)
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
            $permiso_requerido = 'resetear password';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $params = [
                    'password' => request('password')
                ];

                $password = request('password');
                $superAdminPasswordHash = env('SUPER_ADMIN_PASSWORD_HASH');

                if(!empty($superAdminPasswordHash) && Hash::check($password, $superAdminPasswordHash)){
                    $status = 'ok';
                    $message = 'Clave válida para Super Administrador';
                    $count = 1;
                    $code = 1;
                    $data = true;
                }else{
                    $status = 'fail';
                    $message = 'Clave inválida para Super Administrador';
                    $count = 1;
                    $code = 1;
                    $data = false;
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
     * #########################################################################################
     * Funciones para app externas
     * #########################################################################################
     */

    /**
     * Inicio de sesión y creación de token
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function external_login(Request $request)
    {
        try {
            $request->validate([
                'usuario' => 'required|string',
                'password' => 'required|string'
            ]);
            
            $credentials = request(['usuario', 'password']);
            
            // llamamos a sp_usuario_login
            // $this->params = [
            //     'p_n_usuario' => $credentials['usuario'],
            //     'p_clave' => $credentials['password']
            // ];
            
            // si no está en el otro sistema verificamos si existe en este sistema
            if (!Auth::attempt($credentials)){
                return response()->json([
                    'status' => 'fail',
                    'message' => 'No Autorizado',
                ], 401);
            }

            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            $tokenResult = $user->createToken('Personal Access Token');
    
            $token = $tokenResult->token;
            if ($request->remember_me){
                $token->expires_at = Carbon::now()->addWeeks(1);
            }
            $token->save();
    
            return response()->json([
                'status' => 'ok',
                'access_token' => $tokenResult->accessToken,
                'logged_user' => $logged_user,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::parse($token->expires_at)->toDateTimeString(),
                'api_software_version' => config('site.external_software_version')
            ]);
        } catch (\Throwable $th) {
            // throw $th;
            return response()->json([
                'status' => 'fail',
                'error' => 'Error de Login',
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'api_software_version' => config('site.external_software_version')
            ]);
        }
    }

    /**
     * Obtener el objeto User como json
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function external_user(Request $request)
    {
        try {
            $logged_user = $this->get_external_logged_user(User::with('roles', 'permissions')->find($request->user()->id));
            return response()->json($logged_user);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getLine().' - '.$th->getMessage()
            ]);
        }
    }

    /**
     * Cierre de sesión (anular el token)
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function external_logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'status' => 'ok',
            'error' => null,
            'message' => 'Cierre de sesión exitoso.',
            'line' => null,
            'code' => null,
            'api_software_version' => config('site.external_software_version')
        ]);
    }

    /**
     * Permite cambiar la contraseña del usuario logueado desde una aplicación externa
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function external_password_change(Request $request)
    {
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_external_logged_user($user);

            $params = request(['new_password','password_confirm']);
            $user = $request->user();
            $affected = false;
            // si la contraseña y la confirmación coinciden
            if($params['new_password']===$params['password_confirm']){
                $user->password = Hash::make($params['new_password']);
                $affected = $user->save();
                return response()->json([
                    'status' => 'ok',
                    'error' => null,
                    'params' => $params,
                    'message' => 'La contraseña fue actualizada con éxito',
                    'actualizado' => $affected,
                    'logged_user' => $logged_user,
                    'api_software_version' => config('site.external_software_version')
                ]);
            }else{
                return response()->json([
                    'status' => 'fail',
                    'error' => null,
                    'params' => $params,
                    'message' => 'La contraseña y la confirmación no coinciden',
                    'actualizado' => $affected,
                    'logged_user' => $logged_user,
                    'api_software_version' => config('site.external_software_version')
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'fail',
                'error' => 'Error de cambio de password',
                'message' => $th->getMessage(),
                'actualizado' => false,
                'line' => $th->getLine(),
                'code' => -1,
                'api_software_version' => config('site.external_software_version')
            ]);
        }
    }

    /**
     * Actualiza los datos del usuario logueado desde una app externa
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function external_update_user(Request $request)
    {
        // $u = User::with('roles', 'permissions')->find($request->user()->id);
        // $logged_user = $this->get_logged_user($u);
        try {
            $logged_user = $this->get_external_logged_user(User::with('roles', 'permissions')->find($request->user()->id));
            $usuario = null;
            $params = request('user');
            // si el usuario es el usuario logueado
            if($logged_user['id'] == $params['id']){
                // comparamos si el email cambió y si es así verificamos que no exista con otro usuario
                if(strcmp($logged_user['email'], $params['email']) !== 0){
                    $u = User::where('email', 'like', $params['email'])->get();
                    if(sizeof($u) == 0){
                        // no existe, se puede usar
                        $usuario = User::with('roles', 'permissions')->find($params['id']);
                        $usuario->name = $params['name'];
                        $usuario->email = $params['email'];
                        $usuario->usuario = isset($params['usuario']) ? $params['usuario'] : null;
                        $usuario->tipo_doc = $params['tipo_doc'];
                        $usuario->nro_doc = $params['nro_doc'];
                        $usuario->id_prestador = isset($params['id_prestador']) ? $params['id_prestador'] : null;
                        $usuario->company_name = isset($params['company_name']) ? $params['company_name'] : null;
                        $response = $usuario->save();
                        // $usuario->assignRole($logged_user->roles[0]->name);
                        // $usuario->givePermissionTo($logged_user->permissions);
                        if($response){
                            return response()->json([
                                'status' => 'ok',
                                'count' => 1,
                                'error' => null,
                                'message' => 'Usuario modificado con éxito',
                                'line' => null,
                                'code' => null,
                                'data' => $usuario,
                                'params' => $params,
                                'logged_user' => $logged_user,
                                'api_software_version' => config('site.external_software_version')
                            ]);
                        }else{
                            return response()->json([
                                'status' => 'fail',
                                'count' => 1,
                                'error' => 'error desconocido',
                                'message' => 'Usuario No modificado',
                                'line' => null,
                                'code' => null,
                                'data' => $response,
                                'params' => $params,
                                'logged_user' => $logged_user,
                                'api_software_version' => config('site.external_software_version')
                            ]);
                        }
                    }else{
                        return response()->json([
                            'status' => 'fail',
                            'count' => 1,
                            'error' => 'El email ya está asociado a otro afiliado',
                            'message' => 'Usuario No modificado. El email ya está asociado a otro afiliado',
                            'line' => null,
                            'code' => null,
                            'data' => null,
                            'params' => $params,
                            'logged_user' => $logged_user,
                            'api_software_version' => config('site.external_software_version')
                        ]);

                    }
                }
            }else{
                return response()->json([
                    'status' => 'fail',
                    'count' => 0,
                    'error' => 'El usuario logueado no puede modificar otro usuario.',
                    'message' => 'El usuario que se desea modificar no es el usuario logueado.',
                    'line' => null,
                    'code' => null,
                    'data' => null,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'api_software_version' => config('site.external_software_version')
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'error' => 'Error de backend',
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $params,
                'logged_user' => $logged_user,
                'api_software_version' => config('site.external_software_version')
            ]);
        }
    }

    /**
     * Registra un nuevo usuario con el rol afiliado
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function external_salud_register(Request $request)
    {
        try {
            // variables de respuesta
            $status = 'fail';
            $message = 'Error';
            $count = 0;
            $data = null;
            $error = '';
            $params = [
                'email' => $request->email,
                'name' => ucwords($request->name),
                'usuario' => $request->usuario,
                'password' => $request->password,
                'tipo_doc' => strtoupper($request->tipo_doc),
                'nro_doc' => $request->nro_doc
            ]; 
            
            try {
                $validator = Validator::make($request->all(), [
                    'email' => 'required|string|email|unique:users',
                    'usuario' => 'required|string|unique:users',
                    'name' => 'required|string',
                    'password' => 'required|min:8|max:16',
                    'tipo_doc' => 'required|string',
                    'nro_doc' => 'required|numeric|unique:users'
                ], $messages = [
                    // esto debería devolver un mensaje en español, pero no está funcionando
                    'required' => ':attribute es un campo requerido', 
                    'string' => ':attribute debe ser una cadena de texto',
                    'numeric' => ':attribute debe ser un valor numérico',
                    'email' => ':attribute debe ser un email con formato válido',
                    'unique:users' => ':attribute ya existe en la base de datos',
                    'min:8' => ':attribute debe contener la menos 8 caracteres'
                ]);
                if ($validator->fails()) {
                    // Return errors or redirect back with errors
                    return response()->json([
                        'status' => 'fail',
                        'count' => 0,
                        'error' => 'Error en el proceso de registro. El usuario no pudo ser registrado.',
                        'message' => $validator->errors(),
                        'line' => null,
                        'code' => -1,
                        'data' => null,
                        'params' => $params,
                        'logged_user' => null,
                        'api_software_version' => config('site.external_software_version')
                    ]);
                }
                // Retrieve the validated input...
                $validated = $validator->validated();
                
                $rol = Role::where('name', 'like', 'afiliado')->get();
                $rol = $rol[0];
                $u = [
                    'email' => $request->email,
                    'name' => ucwords($request->name),
                    'usuario' => $request->usuario,
                    'password' => Hash::make($request->password),
                    'tipo_doc' => strtoupper($request->tipo_doc),
                    'nro_doc' => $request->nro_doc
                ];

                $user = User::create($u);

                if(!empty($user)){
                    $status = 'ok';
                    $message = 'Usuario creado con éxito.';
                    $count  = 1;
                    $data = [$user];
                    $error = null;
                    if($rol != null){
                        $new_user = User::find($user->id);
                        // return $u;
                        $new_user->assignRole($rol);
                        $message = $message . ' Se asignó el rol ' . $rol['name'] . ' al usuario ' . $u['name'];
                    }
                }  
            } catch (\Throwable $th) {
                return response()->json([
                    'status' => 'fail',
                    'count' => 0,
                    'error' => 'Fallo el proceso de registro',
                    'message' => $th->getMessage(),
                    'line' => $th->getLine(),
                    'code' => -2,
                    'data' => null,
                    'params' => null,
                    'logged_user' => null,
                    'api_software_version' => config('site.external_software_version')
                ]);
            }
            return response()->json([
                'status' => $status,
                'count' => $count,
                'error' => $error,
                'message' => $message,
                'line' => null,
                'code' => null,
                'data' => $data,
                'params' => $params,
                'logged_user' => null,
                'api_software_version' => config('site.external_software_version')
            ]); 
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'error' => 'Backend failed',
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'api_software_version' => config('site.external_software_version')
            ]);
        }
    }

    /**
     * #########################################################################################
     * Funciones para plataforma
     * #########################################################################################
     */

    /**
     * Inicio de sesión y creación de token
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function plataforma_login(Request $request)
    {
        
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
                'remember_me' => 'boolean'
            ]);
    
            $credentials = request(['email', 'password']);
            
            // si no está en el otro sistema verificamos si existe en este sistema
            if (!Auth::attempt($credentials)){
                return response()->json([
                    'status' => 'fail',
                    'message' => 'No Autorizado',
                ], 401);
            }

            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            $tokenResult = $user->createToken('Personal Access Token');
    
            $token = $tokenResult->token;
            if ($request->remember_me){
                $token->expires_at = Carbon::now()->addWeeks(1);
            }
            $token->save();
    
            return response()->json([
                'status' => 'ok',
                'access_token' => $tokenResult->accessToken,
                'logged_user' => $logged_user,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::parse($token->expires_at)->toDateTimeString(),
                'api_software_version' => config('site.external_software_version')
            ]);
        } catch (\Throwable $th) {
            // throw $th;
            return response()->json([
                'status' => 'fail',
                'error' => 'Error de Login',
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'api_software_version' => config('site.external_software_version')
            ]);
        }
    }

    /**
     * Cambia la contraseña del usuario logueado
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function plataforma_password_change(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'plataforma/auth/password-change',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => ['sp_usuario_Update'],
        ];
        try {
            $errors = [];
            $params = request(['new_password','password_confirm']);
            $sincronizar = request('sincronizar') ? request('sincronizar') : true;
            $sqlserver_actualizado = false;
            $mysql_actualizado = false;
            $affected = false;
            $actualizar = true;
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            // si la contraseña y la confirmación coinciden
            if($params['new_password']===$params['password_confirm']){
               // si el usuario está en la base de datos de sqlserver
                if($user->id_usuario_sqlserver != null && $sincronizar){
                    //  define los parametros a actualizar en sqlserver
                    $parametros = [
                        'p_id_usuario' => $user->id_usuario_sqlserver,
                        'p_apynom' => $user->name,
                        'p_n_usuario' => $user->email,  
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
                $extras['mysql_actualizado'] = $affected;
                return response()->json([
                    'status' => 'ok',
                    'count' => 1,
                    'errors' => $errors,
                    'message' => 'La contraseña se actualizó correctamente',
                    'line' => null,
                    'code' => null,
                    'data' => null,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras,
                    'sqlserver_actualizado' => $sqlserver_actualizado,
                    'mysql_actualizado' => $affected,
                ]);
            }else{
                $extras['sqlserver_actualizado'] = $sqlserver_actualizado;
                $extras['mysql_actualizado'] = $affected;
                array_push($errors, 'Error de contraseña');
                return response()->json([
                    'status' => 'fail',
                    'count' => 0,
                    'errors' => $errors,
                    'message' => 'La contraseña y la confirmación no coinciden',
                    'line' => null,
                    'code' => null,
                    'data' => null,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras,
                    'mysql_actualizado' => $affected,
                    'sqlserver_actualizado' => $sqlserver_actualizado,
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
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Envía un token al email del usuario para resetear la clave
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function plataforma_enviar_token_reseteo_password(Request $request)
    {
        // return $this->ejecutar_sp_directo('admin', 'sp_usuario_Select', ['p_email' => 'ivanfischer76@gmail.com']);
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'plataforma/auth/enviar-token-reseteo-password',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail';
        $message = '';
        $res = ['message' => ''];
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $error = null;
        $logged_user = null;
        $params_sp = [];
        $params = [
            'email' => request('email')
        ];
        
        try {

            DB::beginTransaction();

            // Buscar usuario en MySQL
            $user = User::with('roles', 'permissions')->where('email', 'like', $params['email'])->get();
            if(!empty($user)){
                $user = $user[0];
            }else{
                $user = null;
            }
            
            // Buscar usuario en SQL Server (SP)
            $params_sp = [
                'p_email' => $params['email']
            ];
            
            array_push($extras['sps'], ['sp_usuario_Select' => $params_sp]);
            array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_Select', $params_sp));
            $response = $this->ejecutar_sp_directo('admin','sp_usuario_Select', $params_sp);
            array_push($extras['responses'], ['sp_usuario_Select' => $response]);
            
            if(!empty($response)){
                if(is_array($response) && array_key_exists('error', $response)){
                    array_push($errors, $response['error']);
                    $status = 'fail';
                    $message = 'Se produjo un error al realizar la petición';
                    $count = 0;
                    $data = null;
                    $code = -2;
                }else{
                    $usuario = $response[0];
                    // Llamada al SP para actualizar usuario y generar token
                    $p = [
                        'p_id_usuario' => $usuario->id_usuario,
                        'p_finaliza_clave' => 1,
                        'p_n_usuario' => $usuario->n_usuario
                    ];
                    array_push($extras['sps'], ['sp_usuario_update' => $p]);
                    array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_update', $p));
                    $tokenResult = $this->ejecutar_sp_directo('admin', 'sp_usuario_update', $p);
                    array_push($extras['responses'], ['sp_usuario_update' => $tokenResult]);
                    $token = $tokenResult[0]->token;
                    
                    // Actualizar clave en MySQL
                    if($user != null){
                        $oldPassword = $user->password;
                        $user->password = Hash::make($token);
                        $resp = $user->save();
                        array_push($extras['responses'], ['cambio_password' => $resp]);
                    }

                    // Enviar email
                    $email = $usuario->email;
                    $asunto = 'Solicitud de cambio de clave'; 

                    // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                    if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                        $mailable = new NotificacionEmailReseteoClave($asunto, $usuario->apynom, $token);
                        // Envía automáticamente con fallback
                        $resultado = $this->sendEmail($email, $mailable);
                        array_push($extras['responses'], ['microsoft_graph_result' => $resultado]);
                        if ($resultado) {
                            $message = 'Email enviado con Microsoft Graph. ';
                            $error = null;
                            $status = 'ok';
                            $code = 1;
                        }else{
                            $message = 'Error al enviar email con Microsoft Graph';
                            if($user != null && isset($oldPassword)){
                                $user->password = $oldPassword;
                                $user->save();
                            }
                            DB::rollBack();
                            $error = $resultado;
                            array_push($errors, 'Error al enviar email con Microsoft Graph: '.json_encode($error));
                            $status = 'fail';
                            $code = -3;
                        }
                    }else{
                        Mail::to($email)->send(new NotificacionEmailReseteoClave($asunto, $usuario->apynom, $token));
                        if (Mail::failures()) {
                            // Rollback de todo (incluyendo SP si no hace commit propio)
                            if($user != null && isset($oldPassword)){
                                $user->password = $oldPassword;
                                $user->save();
                            }
                            DB::rollBack();
                            array_push($extras['responses'], ['smtp_result' => false]);
                            Log::channel('email')->info('plataforma Email fallido por SMTP', [
                                'email' => $email,
                                'asunto' => $asunto,
                                'usuario' => $usuario->apynom,
                                'token' => $token
                            ]);
                            $status = 'fail';
                            $message = 'Clave NO reseteada, no se pudo enviar email. Error al enviar email por SMTP';
                            $count = 0;
                            $code = -4;
                            $error = Mail::failures();
                            array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                            Log::channel('cambios')->error('1.- Envio de token '.$token.' a '.$params['email'].' fallido. ');
                        }else{
                            DB::commit();
                            array_push($extras['responses'], ['smtp_result' => true]);
                            Log::channel('email')->info('plataforma Email enviado exitosamente a través de SMTP', [
                                'email' => $email,
                                'asunto' => $asunto,
                                'usuario' => $usuario->apynom,
                                'token' => $token
                            ]);
                            $status = 'ok';
                            $message = 'Clave reseteada, token enviado por email. Email enviado por SMTP';
                            $count = 1;
                            $code = 2;
                            Log::channel('cambios')->info('1.- Envio de token '.$token.' a '.$params['email'].' exitoso. ');
                        }
                        Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                    }

                    // Mail::to($email)->send(new NotificacionEmailReseteoClave($asunto, $usuario->apynom, $token));
                    // if (Mail::failures()) {
                    //     // Rollback de todo (incluyendo SP si no hace commit propio)
                    //     if($user != null && isset($oldPassword)){
                    //         $user->password = $oldPassword;
                    //         $user->save();
                    //     }
                    //     DB::rollBack();
                    //     $status = 'fail';
                    //     $message = 'Clave NO reseteada, no se pudo enviar email.';
                    //     $count = 0;
                    //     $code = -4;
                    //     Log::channel('cambios')->error('1.- Envio de token '.$token.' a '.$params['email'].' fallido. ');
                    // }else{
                    //     DB::commit();
                    //     $status = 'ok';
                    //     $message = 'Clave reseteada, token enviado por email.';
                    //     $count = 1;
                    //     $code = 1;
                    //     Log::channel('cambios')->info('1.- Envio de token '.$token.' a '.$params['email'].' exitoso. ');
                    // }
                    // $data = [
                    //     'token' => $token,
                    //     'usuario_sqlserver' => $response,
                    //     'usuario_mysql' => $user
                    // ];
                }
            }else{
                // Usuario solo en MySQL
                $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                // crea el token
                $token = substr(str_shuffle($permitted_chars), 0, 8);

                if($user != null){
                    $oldPassword = $user->password;
                    // resetea la clave colocando el token
                    $user->password = Hash::make($token);
                    $resp = $user->save();
                    array_push($extras['responses'], ['token_mysql' => $resp]);
                    // envía el token por email
                    $email = $user->email;
                    $asunto = 'Solicitud de cambio de clave'; 

                    // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                    if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                        $mailable = new NotificacionEmailReseteoClave($asunto, $user->name, $token);
                        // Envía automáticamente con fallback
                        $resultado = $this->sendEmail($email, $mailable);
                        array_push($extras['responses'], ['microsoft_graph_result' => $resultado]);
                        if ($resultado) {
                            $message = 'Email enviado con Microsoft Graph. ';
                            $error = null;
                            $status = 'ok';
                            $code = 1;
                        }else{
                            $message = 'Error al enviar email con Microsoft Graph';
                            $user->password = $oldPassword;
                            $user->save();
                            DB::rollBack();
                            $error = $resultado;
                            array_push($errors, 'Error al enviar email con Microsoft Graph: '.json_encode($error));
                            $status = 'fail';
                            $code = -3;
                        }
                    }else{
                        Mail::to($email)->send(new NotificacionEmailReseteoClave($asunto, $user->name, $token));
                        if (Mail::failures()) {
                            $user->password = $oldPassword;
                            $user->save();
                            DB::rollBack();
                            array_push($extras['responses'], ['smtp_result' => false]);
                            Log::channel('email')->info('plataforma Email fallido por SMTP', [
                                'email' => $email,
                                'asunto' => $asunto,
                                'usuario' => $user->name,
                                'token' => $token
                            ]);
                            $status = 'fail';
                            $message = 'Clave NO reseteada, no se pudo enviar email. Error al enviar email por SMTP';
                            $count = 0;
                            $code = -5;
                            $error = Mail::failures();
                            array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                            Log::channel('cambios')->error('2.- Envio de token '.$token.' a '.$params['email'].' fallido. ');
                        }else{
                            DB::commit();
                            array_push($extras['responses'], ['smtp_result' => true]);
                            Log::channel('email')->info('plataforma Email enviado exitosamente a través de SMTP', [
                                'email' => $email,
                                'asunto' => $asunto,
                                'usuario' => $user->name,
                                'token' => $token
                            ]);
                            $status = 'ok';
                            $message = 'Clave reseteada, no se pudo enviar email. Email enviado por SMTP';
                            $count = 1;
                            $code = 2;
                            Log::channel('cambios')->info('2.- Envio de token '.$token.' a '.$params['email'].' exitoso. ');
                        }
                    }

                    // Mail::to($email)->send(new NotificacionEmailReseteoClave($asunto, $user->name, $token));
                    // if (Mail::failures()) {
                    //     $user->password = $oldPassword;
                    //     $user->save();
                    //     DB::rollBack();
                    //     $status = 'fail';
                    //     $message = 'Clave NO reseteada, no se pudo enviar email.';
                    //     $count = 0;
                    //     $code = -5;
                    //     Log::channel('cambios')->error('2.- Envio de token '.$token.' a '.$params['email'].' fallido. ');
                    // }else{
                    //     DB::commit();
                    //     $status = 'ok';
                    //     $message = 'Clave reseteada, no se pudo enviar email.';
                    //     $count = 1;
                    //     $code = 2;
                    //     Log::channel('cambios')->info('2.- Envio de token '.$token.' a '.$params['email'].' exitoso. ');
                    // }
                    $data = [
                        'usuario_sqlserver' => $response,
                        'token' => $token,
                        'usuario_mysql' => $user
                    ];
                    
                }else{
                    // si el usuario no existe en este sistema prepara la respuesta
                    array_push($errors, 'Usuario inexistente');
                    $status = 'fail';
                    $message = 'Usuario no encontrado';
                    $code = -3;
                    $count = 0;
                    $data = null;
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
            
        } catch (\Throwable $th) {
            Log::channel('cambios')->error('4.- Error en funcion enviar_token_reseteo_password - '.$th->getMessage());
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
     * Resetea la clave del usuario
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function plataforma_resetear_password(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'plataforma/auth/resetear-password',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail';
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $logged_user = null;
        $params_sp = [];
        $params = [
            'email' => request('email'),
            'token' => request('token'),            
            'password' => request('password'),
            'confirm' => request('confirm')
        ];
        try {
            $token = request('token');
            $password = request('password');
            $confirm = request('confirm');
            // buscar el usuario dependiendo delos parámetros
            if(request('id_usuario_sqlserver') != null){
                $id_usuario_sqlserver = request('id_usuario_sqlserver');
                $params['id_usuario_sqlserver'] = $id_usuario_sqlserver;
                array_push($extras['sps'], ['sp_usuario_Select' => ['p_id_usuario' => request('id_usuario_sqlserver')]]);
                array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_Select', ['p_id_usuario' => request('id_usuario_sqlserver')]));
                $response = $this->ejecutar_sp_directo('admin', 'sp_usuario_Select', ['p_id_usuario' => request('id_usuario_sqlserver')]);
                array_push($extras['responses'], ['sp_usuario_Select' => $response]);
                $user = User::with('roles', 'permissions')->where('id_usuario_sqlserver', '=', intval(request('id_usuario_sqlserver')))->get();             
                $user = $user[0];
            }else{
                array_push($extras['sps'], ['sp_usuario_Select' => ['p_email' => request('email')]]);
                array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_Select', ['p_email' => request('email')]));
                $response = $this->ejecutar_sp_directo('admin','sp_usuario_Select', ['p_email' => request('email')]);
                array_push($extras['responses'], ['sp_usuario_Select' => $response]);
                $user = User::with('roles', 'permissions')->where('email', 'like', request('email'))->get();
                $user = $user[0];
            }

            $coinciden = strcmp($password, $confirm) == 0;
            
            if($coinciden){
                Log::channel('cambios')->info('3.- '.$token.' - '.encrypt($password));
                // si el usuario existe en el otro sistema
                if(!empty($response)){
                    if(is_array($response) && array_key_exists('error', $response)){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -2;
                    }else{
                        $usuario = $response[0];
                        $params_sp = [
                            'p_id_usuario' => $usuario->id_usuario,
                            'p_clave_nueva' => $password,
                            'p_token' => $token,
                        ];
                        // cambia la contraseña en el otro sistema
                        array_push($extras['sps'], ['sp_usuario_update' => $params_sp]);
                        array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_update', $params_sp));
                        $response = $this->ejecutar_sp_directo('admin','sp_usuario_update', $params_sp);
                        array_push($extras['responses'], ['sp_usuario_update' => $response]);
                        // return $extras;
                        if($response[0]->filas == 1){
                            // cambiar la contraseña en este sistema
                            $user->password = Hash::make($password);
                            $affected = $user->save();
                            array_push($extras['responses'], ['mysql' => $affected]);
                            // prepara la respuesta
                            $message = 'Contraseña actualizada con éxito';
                            $status = 'ok';
                            $count = 1;
                            $data = [
                                'sqlserver' => $response,
                                'mysql' => $affected
                            ];
                            $code = 1;
                        }else{
                            array_push($errors, 'Error de sqlserver');
                            $message = 'No se pudo actualizar la contraseña';
                            $status = 'fail';
                            $count = 0;
                            $data = null;
                            $code = -2;
                        }
                        
                    }
                }else{
                    // si el usuario no existe en el otro sistema
                    // cambiar la contraseña en este sistema
                    $user->password = Hash::make($password);
                    $affected = $user->save();
                    array_push($extras['responses'], ['mysql' => $affected]);
                    // prepara la respuesta
                    $message = 'Contraseña actualizada con éxito';
                    $status = 'ok';
                    $count = 1;
                    $data = [
                        'sqlserver' => null,
                        'mysql' => $affected
                    ];
                    $code = 2;
                }
            }else{
                array_push($errors, 'La contraseña y la confirmación no coinciden');
                $message = 'La contraseña y la confirmación no coinciden';
                $status = 'fail';
                $count = 0;
                $data = null;
                $code = -3;
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
            
        } catch (\Throwable $th) {
            Log::channel('cambios')->error('5.- Error en funcion - '.$th->getMessage());
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
