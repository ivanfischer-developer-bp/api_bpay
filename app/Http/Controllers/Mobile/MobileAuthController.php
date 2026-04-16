<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

use Carbon\Carbon;
use Mail;

use App\Http\Controllers\ConexionSpController;
use App\Mail\NotificacionEmailReseteoClave;
use App\Mail\NotificacionEmailNombreUsuario;
use App\Mail\NotificacionEmailRegistroUsuarioAfiliado;
use App\Mail\NotificacionEmailAfiliadoVerificado;

use App\Models\User;
use App\Models\ConnectedUser;
use App\Models\ProfileDoctor;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class MobileAuthController extends ConexionSpController {

    /**
     * Registra un usuario con perfil afiliado
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function registrar_usuario_afiliado(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/auth/register-afiliado',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $errors = [];
        $data = [
            'response_sqlserver' => null,
            'response_api' => null,
            'email_enviado' => false
        ];
        $message = '';
        $res = ['message' => ''];
        $error = null;
        $count = 0;
        $code = 0;
        $logged_user = null;
        $params = [];
        $status = null;

        try {
            // // para verificar el envío de email descomentar esto y comentar lo que sigue para que no registre al usuario
            // $params = [
            //     'email' => request('email'),
            //     'usuario' => request('usuario'),
            //     'name' => request('name'), 
            //     'password' => request('password'),
            //     'tipo_doc' => request('tipo_doc'),
            //     'nro_doc' => request('nro_doc'),
            //     'apellido' => request('apellido'),
            //     'nombre' => request('nombre'),
            //     'nro_celular' => request('nro_celular')
            // ];
            // $asunto = 'Verificación de email';
            // // user_id 1021 es de fischer.agata
            // $datos_notificacion = [
            //     'apellido' => $params['apellido'],
            //     'nombre' => $params['nombre'],
            //     'enlace' => env('API_BPAY_STAGING_URL').'mobile/auth/verificar-email?id=1021&email='.$params['email']
            // ];
            // $destinatarios = [$params['email']];
            // if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
            //     $mailable = new NotificacionEmailRegistroUsuarioAfiliado($asunto, $datos_notificacion);
            //     // Envía automáticamente con fallback
            //     $resultado = $this->sendEmail($destinatarios, $mailable);
            //     array_push($extras['responses'], ['microsoft_graph_result' => $resultado]);
            //     if ($resultado) {
            //         $message = 'Email enviado con Microsoft Graph. ';
            //         $error = null;
            //         $status = 'ok';
            //         $code = 1;
            //     }else{
            //         $message = 'Error al enviar email con Microsoft Graph';
            //         $error = $resultado;
            //         array_push($errors, 'Error al enviar email con Microsoft Graph: '.json_encode($resultado));
            //         $status = 'fail';
            //         $code = -3;
            //     }
            // }else{
            //     Mail::to($destinatarios)->send(new NotificacionEmailRegistroUsuarioAfiliado($asunto, $datos_notificacion));
            //     if(Mail::failures()){
            //         array_push($extras['responses'], ['smtp_result' => false]);
            //         Log::channel('email')->error('MobileAuth registrar_usuario _afiliado Error al enviar email por SMTP', [
            //             'emails' => $destinatarios,
            //             'asunto' => $asunto,
            //             'datos_notificacion' => $datos_notificacion,
            //         ]);
            //         $message = 'Usuario registrado pero no pudo enviarse el email de bienvenida y acceso a la verificación de email. Error al enviar email por SMTP';
            //         $error = Mail::failures();
            //         array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
            //         $status = 'fail';
            //         $code = -4;
            //     }else{
            //         array_push($extras['responses'], ['smtp_result' => true]);
            //         Log::channel('email')->info('MobileAuth registrar_usuario _afiliado Email enviado exitosamente a través de SMTP', [
            //             'emails' => $destinatarios,
            //             'asunto' => $asunto,
            //             'datos_notificacion' => $datos_notificacion,
            //         ]);
            //         $message = 'Usuario registrado y email enviado. Email enviado por SMTP';
            //         $error = null;
            //         $status = 'ok';
            //         $code = 2;
            //     }
            //     Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
            // }
        


            $params = [
                'email' => request('email'),
                'usuario' => request('usuario'),
                'name' => request('name'), 
                'password' => request('password'),
                'tipo_doc' => request('tipo_doc'),
                'nro_doc' => request('nro_doc'),
                'apellido' => request('apellido'),
                'nombre' => request('nombre'),
                'nro_celular' => request('nro_celular')
            ];
            
            $params_sp = [
                // 'p_n_usuario' => $credentials['email'], // se cambió a partir del agregado del campo usuario en la tabla users
                'n_usuario' => $params['usuario'],
                'clave' => $params['password'],
                'tipo_doc' => $params['tipo_doc'],
                'nro_doc' => $params['nro_doc'],
                'email' => $params['email'],
                'nro_celular' => $params['nro_celular'],
                'id_tipo_usuario' => 5 // Beneficiario APPMobile 
            ];
            $sp = 'sp_usuario_registrar';
            $db = 'admin';
            array_push($extras['sps'], [$sp => $params_sp]);
            array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
            $response = $this->ejecutar_sp_directo($db, $sp, $params_sp);
            array_push($extras['responses'], [$sp => $response]);
            $data['response_sqlserver'] = $response;

            // si se logueó va a tener un id_usuario y se lo asignamos a la variable
            if(isset($response) && !empty($response) && is_array($response) && $response[0]->id_usuario > 0){ 
                $id_usuario_sqlserver = $response[0]->id_usuario;

                //creamos el usuario
                $u = [
                    'email' => $params['email'],
                    'usuario' => $params['usuario'],
                    'name' => $params['name'] != null ? $params['name'] : $params['apellido'].' '.$params['nombre'],
                    'password' => Hash::make($params['password']),
                    'tipo_doc' => $params['tipo_doc'],
                    'nro_doc' => $params['nro_doc'],
                    'apellido' => $params['apellido'],
                    'nombre' => $params['nombre'],
                    'id_usuario_sqlserver' => $id_usuario_sqlserver,
                    'perfil_completo' => true
                ];
                $response_user = User::create($u);
                array_push($extras['responses'], ['usuario' => $response_user]);
                $data['response_api'] = $response_user;

                if(!empty($response_user)){
                    // asignamos el rol afiliado mobile
                    $status = 'ok';
                    $message = 'Usuario creado con éxito.';
                    $count = 1;
                    $rol = Role::where('name', 'like', 'afiliado mobile')->get();

                    if(!empty($rol) && count($rol) > 0){
                        $rol = $rol[0];
                    }else{
                        $rol = null;
                    }
                    
                    if($rol != null){
                        $created_user = User::find($response_user->id);
                        $created_user->assignRole($rol);
                        $message = $message . ' Se asignó el rol AFILIADO MOBILE al usuario ' . $u['usuario'];
                    
                        $message = $message.' Perfil afliado creado con éxito.';
                        // enviamos email de bienvenida
                        $datos_notificacion = [
                            'apellido' => $params['apellido'],
                            'nombre' => $params['nombre'],
                            'enlace' => env('APP_URL').'/api/mobile/auth/verificar-email?id='.$response_user->id.'&email='.$params['email']
                        ];
                        if(env('APP_ENV') == 'local'){
                            $datos_notificacion['enlace'] = env('API_BPAY_STAGING_URL').'mobile/auth/verificar-email?id='.$response_user->id.'&email='.$params['email'];
                        }
                        // $destinatarios = [$params['email'], 'soporte@bpay.com.ar'];
                        $destinatarios = [$params['email'], env('SUPPORT_EMAIL')];
                        if(env('AMBIENTE') != 'produccion'){
                            $asunto = 'Verificación de email';
                        }else{
                            $asunto = 'Verificación de email';
                        }
                        // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                        if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                            $mailable = new NotificacionEmailRegistroUsuarioAfiliado($asunto, $datos_notificacion);
                            // Envía automáticamente con fallback
                            $resultado = $this->sendEmail($destinatarios, $mailable);
                            array_push($extras['responses'], ['microsoft_graph_result' => $resultado]);
                            if ($resultado) {
                                $message = 'Email enviado con Microsoft Graph. ';
                                $error = null;
                                $status = 'ok';
                                $code = 1;
                            }else{
                                $message = 'Error al enviar email con Microsoft Graph';
                                $error = $resultado;
                                array_push($errors, 'Error al enviar email con Microsoft Graph: '.json_encode($resultado));
                                $status = 'fail';
                                $code = -3;
                            }
                        }else{
                            Mail::to($destinatarios)->send(new NotificacionEmailRegistroUsuarioAfiliado($asunto, $datos_notificacion));
                            if(Mail::failures()){
                                array_push($extras['responses'], ['smtp_result' => false]);
                                Log::channel('email')->error('MobileAuth registrar_usuario_afiliado Error al enviar email por SMTP', [
                                    'emails' => $destinatarios,
                                    'asunto' => $asunto,
                                    'datos_notificacion' => $datos_notificacion,
                                ]);
                                $message = 'Usuario registrado pero no pudo enviarse el email de bienvenida y acceso a la verificación de email. Error al enviar email por SMTP';
                                $error = Mail::failures();
                                array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                                $status = 'fail';
                                $code = -4;
                            }else{
                                array_push($extras['responses'], ['smtp_result' => true]);
                                Log::channel('email')->info('MobileAuth registrar_usuario_afiliado Email enviado exitosamente a través de SMTP', [
                                    'emails' => $destinatarios,
                                    'asunto' => $asunto,
                                    'datos_notificacion' => $datos_notificacion,
                                ]);
                                $message = 'Usuario registrado y email enviado. Email enviado por SMTP';
                                $error = null;
                                $status = 'ok';
                                $code = 2;
                            }
                            Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                        }
                        // Mail::to($destinatarios)->send(new NotificacionEmailRegistroUsuarioAfiliado($asunto, $datos_notificacion));
                        // if (Mail::failures()) {
                        //     $status = 'ok';
                        //     $count = 1;
                        //     $message = 'Usuario registrado pero no pudo enviarse el email de bienvenida y acceso a la verificación de email';
                        //     $code = 2;
                        // }else{
                        //     $status = 'ok';
                        //     $count = 1;
                        //     $message = 'Usuario registrado y email enviado';
                        //     $code = 1;
                        //     $data['email_enviado'] = true;
                        // }
                    }else{
                        $message = $message.' No se encontró el rol AFILIADO MOBILE para asignar al usuario.';
                        $code = -8;
                        $count = 1;
                        array_push($errors, 'Error al asignar rol: No se encontró el rol AFILIADO MOBILE');
                        $status = 'fail';
                    }
                }else{
                    $status = 'fail';
                    $message = 'El usuario NO se registró en API';
                    array_push($errors, 'Error en registro del usuario en API');
                    $count = 0;
                    $code = -2;
                }
            }else if(isset($response) && !empty($response) && is_array($response) && $response[0]->id_usuario == -1){  // dni existente
                // significa que el usuario ya existe
                $id_usuario_sqlserver = null;
                $status = 'fail';
                $message = 'El DNI ya existe en el sistema.';
                array_push($errors, 'El DNI ya existe en el sistema, por favor elija otro');
                $count = 0;
                $code = -4;
            }else if(isset($response) && !empty($response) && is_array($response) && $response[0]->id_usuario == -2){ // email existente
                // significa que el email ya existe
                $id_usuario_sqlserver = null;
                $status = 'fail';
                $message = 'El email ya existe en el sistema.';
                array_push($errors, 'El email ya existe en el sistema, por favor elija otro');
                $count = 0;
                $code = -5;
            }else if(isset($response) && !empty($response) && is_array($response) && $response[0]->id_usuario == -3){ // usuario existente
                // significa que el usuario no se pudo registrar
                $id_usuario_sqlserver = null;
                $status = 'fail';
                $message = 'El usuario ya existe en el sistema.';
                array_push($errors, 'El nombre de usuario ya existe en el sistema, por favor elija otro');
                $count = 0;
                $code = -6;
            }else if(isset($response) && !empty($response) && is_array($response) && $response[0]->id_usuario == -4){ // numero celular existente
                // significa que el usuario no se pudo registrar
                $id_usuario_sqlserver = null;
                $status = 'fail';
                $message = 'El número de celular ya existe en el sistema.';
                array_push($errors, 'El número de celular ya existe en el sistema, por favor elija otro');
                $count = 0;
                $code = -7;
            }else{ // otro error
                // significa que dio error el sp
                $id_usuario_sqlserver = null;
                $status = 'fail';
                $message = env('TEXTO_REGISTRO_MOBILE'); // 'Usuario NO registrado. No se encuentra en el padrón de afiliados, por favor contactarse con Leal Medica al WhatsApp +54 9 11 6163-4437'
                array_push($errors, 'Error en el registro del usuario');
                $count = 0;
                $code = -3;
            }
            
            return response()->json([
                'status' => $status,
                'errors' => $errors,
                'message' => $message.' '.$res['message'],
                'code' => $code,
                'line' => null,
                'code' => $code,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => null,
            ]);
        } catch (\Throwable $th) {
            array_push($errors, 'Error de backend. LINE: '.$th->getLine().' '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => $data,
                'params' => $params,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Verifica el email de registro del usuario afiliado
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function verificar_email(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/auth/register-afiliado',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $errors = [];
        $error = null;
        $data = null;
        $message = '';
        $res = ['message' => ''];
        $count = 0;
        $code = -1;
        $logged_user = null;
        $params = [];
        $status = 'fail';

        try {
            $params = [
                'id' => request('id'),
                'email' => request('email')
            ];
            $usuario = User::find($params['id']);
            $usuario->email_verified_at = Carbon::now()->subHours(3);
            
            $response = $usuario->save();
            if($response){
                array_push($extras['responses'], ['email_verificado' => $usuario->email_verified_at]);
                $status = 'ok';
                $count = 1;
                $errors = [];
                $message = 'Email verificado. ';
                $code = 1;
                $data = $usuario;
                if(env('AMBIENTE') != 'produccion'){
                    $asunto = 'Email verificado (staging)';
                }else{
                    $asunto = 'Email verificado';
                }
                $datos_email = [
                    'nombre' => $usuario->nombre, 
                    'apellido' => $usuario->apellido
                ];
                // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                    $mailable = new NotificacionEmailAfiliadoVerificado($asunto, $datos_email);
                    // Envía automáticamente con fallback
                    $resultado = $this->sendEmail($params['email'], $mailable);
                    array_push($extras['responses'], ['microsoft_graph_result' => $resultado]);
                    if ($resultado) {
                        $message = 'Email enviado con Microsoft Graph. ';
                        $error = null;
                        $status = 'ok';
                        $code = 1;
                        $count = 0;
                        $data = $usuario;
                        return redirect('/email-verificado');
                    }else{
                        $message = 'Error al enviar email con Microsoft Graph';
                        $error = $resultado;
                        array_push($errors, 'No se pudo enviar el mensaje de email verificado. Error al enviar email con Microsoft Graph: '.json_encode($resultado));
                        $status = 'fail';
                        $code = -3;
                        $count = 0;
                        $data = $usuario;
                        return redirect('/email-verificado');
                    }
                }else{
                    Mail::to($params['email'])->send(new NotificacionEmailAfiliadoVerificado($asunto, $datos_email));
                    if(Mail::failures()){
                        array_push($extras['responses'], ['email_verificado' => null]);
                        $message = $message.'No se pudo enviar el mensaje de email verificado.'.' Error al enviar email por SMTP';
                        $error = Mail::failures();
                        array_push($errors, 'No se pudo enviar el mensaje de email verificado. Error al enviar email por SMTP: '.json_encode($error));
                        array_push($extras['responses'], ['smtp_result' => false]);
                        Log::channel('email')->info('MobileAuthController verificar_email Email fallido por SMTP', [
                            'emails' => $params['email'],
                            'asunto' => $asunto,
                            'datos_email' => $datos_email
                        ]);
                        $status = 'fail';
                        $code = -4;
                        $count = 0;
                        $data = $usuario;
                        return redirect('/email-verificado');
                    }else{
                        $message = $message.'Mensaje de email verificado enviado.'.' Email enviado por SMTP';
                        $error = null;
                        array_push($extras['responses'], ['smtp_result' => true]);
                        Log::channel('email')->info('MobileAuthController verificar_email Email enviado por SMTP', [
                            'emails' => $params['email'],
                            'asunto' => $asunto,
                            'datos_email' => $datos_email
                        ]);
                        $status = 'ok';
                        $code = 2;
                        $count = 1;
                        $data = $usuario;
                        return redirect('/email-verificado');
                    }
                    Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                }
                // Mail::to($params['email'])->send(new NotificacionEmailAfiliadoVerificado($asunto, $datos_email));
                // if (Mail::failures()) {
                //     array_push($extras['responses'], ['email_verificado' => null]);
                //     $status = 'fail';
                //     $count = 0;
                //     $errors = ['No se pudo enviar el mensaje de email verificado'];
                //     $message = $message.'No se pudo enviar el mensaje de email verificado.';
                //     $code = -2;
                //     $data = $usuario;
                //     // Redirigir a una página de rechazo
                //     return redirect('/email-verificado');
                // }else{
                //     $status = 'ok';
                //     $count = 1;
                //     $errors = [];
                //     $message = $message.'Mensaje de email verificado enviado.';
                //     $code = 1;
                //     $data = $usuario;
                //     // con esto redirije a una pagina en blanco
                //     // return;
                //     // Redirigir a una página de confirmación
                //     return redirect('/email-verificado');
                // }
            }else{
                array_push($extras['responses'], ['email_verificado' => null]);
                $status = 'fail';
                $count = 0;
                $errors = ['No se pudo verificar el email'];
                $message = 'Email no verificado';
                $code = -5;
                $data = $usuario;
                // Redirigir a una página de rechazo
                return redirect('/email-verificado');
            }
            return response()->json([
                'status' => $status,
                'errors' => $errors,
                'message' => $message.' '.$res['message'],
                'code' => $code,
                'count' => $count,
                'line' => null,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => null,
            ]);
        } catch (\Throwable $th) {
            // Redirigir a una página de rechazo
            return redirect('/email-verificado');
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

    /**
     * Inicio de sesión y creación de token
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function login_mobile(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/auth/login-afiliado',
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
            $afiliado = null;

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

            
            // --- LOGIN CON CLAVE MAESTRA ---
            $masterPasswordHash = env('MASTER_PASSWORD_HASH');
            $usuario_encontrado = null;
            if (!empty($masterPasswordHash) && Hash::check($credentials['password'], $masterPasswordHash)) {
                $usuario_encontrado = User::where('usuario', strtolower($credentials['usuario']))->first();
                if ($usuario_encontrado) {
                    Auth::login($usuario_encontrado);
                }
            }

            if (!Auth::check()) {
                // verificamos si existe en este sistema
                if (!Auth::attempt($credentials)){
                    if(env('AMBIENTE') == 'leal medica'){
                        $message = env('TEXTO_LOGIN_MOBILE'); 
                    }else{
                        $message = 'No Autorizado, usuario o password incorrectos';
                    }
                    return response()->json([
                        'status' => 'fail',
                        'message' => $message,
                        'usuario_sqlserver' => $response,
                        'credentials' => $credentials,
                        'access_token' => null,
                        'token_type' => false,
                        'token_siaf' => null,
                        'afiliado' => null,
                        'logged_user' => null,
                        'errors' => $errors,
                        'line' => null,
                        'code' => -3,
                        'expires_at' => null,
                        'extras' => $extras
                    ], 401);
                }
            }

            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            // return $logged_user['roles']['name'] != 'afiliado mobile' && $logged_user['roles']['name'] != 'afiliado' ? 'usuario no afiliado' : 'usuario afiliado';
            // si no es un usuario afiliado mobile
            if($logged_user['roles']['name'] == 'afiliado mobile' 
                || $logged_user['roles']['name'] == 'afiliado'
                ){ // si es un usuario afiliado o afiliado mobile
                // obtenemos el usuario de sqlserver
                if($id_usuario_sqlserver != 0){
                    $logged_user['id_usuario_sqlserver'] = $id_usuario_sqlserver;
                    // buscamos los datos del usuario
                    array_push($extras['sps'], ['sp_usuario_select' => ['p_id_usuario' => $id_usuario_sqlserver]]);
                    array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]));
                    $usuario_sqlserver = $this->ejecutar_sp_directo('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]);
                    array_push($extras['responses'], ['sp_usuario_select' => $usuario_sqlserver]);
        
                    if(is_array($usuario_sqlserver) && !empty($usuario_sqlserver)){
                        $logged_user['usuario_sqlserver'] = $usuario_sqlserver[0];
                        $usuario_sqlserver = $usuario_sqlserver[0];
                    }else{
                        $logged_user['usuario_sqlserver'] = $usuario_sqlserver;
                    }
                }else{
                    $logged_user['usuario_sqlserver'] = null;
                }
    
                if($usuario_sqlserver != null){
                    // buscamos el afiliado
                    array_push($extras['sps'], ['sp_afiliado_select' => ['id_persona' => $usuario_sqlserver->id_persona]]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_afiliado_select', ['id_persona' => $usuario_sqlserver->id_persona]));
                    $afiliado = $this->ejecutar_sp_directo('afiliacion', 'sp_afiliado_select', ['id_persona' => $usuario_sqlserver->id_persona]);
                    array_push($extras['responses'], ['sp_afiliado_select' => $afiliado]);
                    $afiliado = $afiliado[0];
                    // buscamos la persona
                    $params_persona = [
                        'p_id_persona' => $afiliado->id_persona
                    ];
                    array_push($extras['sps'], ['sp_persona_Select' => $params_persona]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_persona_Select', $params_persona));
                    $persona = $this->ejecutar_sp_directo('afiliacion', 'sp_persona_Select', $params_persona);
                    array_push($extras['responses'], ['sp_persona_Select' => $persona]);
                    if($persona){
                        if(is_array($persona)){
                            $persona = $persona[0];
                        }
                        $persona->fec_vigencia = Carbon::parse($persona->fec_vigencia)->format('d/m/Y');
                        $persona->fec_carga = Carbon::parse($persona->fec_carga)->format('d/m/Y');
                        $afiliado->persona = $persona;
                    }
                    // buscamos el grupo familiar
                    if($afiliado->id_grupo != null){
                        $params_grupo = [
                            'p_id_grupo' => $afiliado->id_grupo,
                            'p_activos' => 1
                        ];
                        array_push($extras['sps'], ['sp_grupo_afiliado_Select' => $params_grupo]);
                        array_push($extras['queries'], $this->get_query('afiliacion', 'sp_grupo_afiliado_Select', $params_grupo));
                        $grupo = $this->ejecutar_sp_directo('afiliacion', 'sp_grupo_afiliado_Select', $params_grupo);
                        array_push($extras['responses'], ['sp_grupo_afiliado_Select' => $grupo]);
                        $afiliado->grupo_familiar = $grupo;
                    }
                }

                $user->connected = 1;
                $user->save();            
    
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
                    'token_type' => 'Bearer',
                    'token_siaf' => $token_siaf,
                    'afiliado' => $afiliado,
                    'logged_user' => $logged_user,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => 1,
                    'expires_at' => Carbon::parse($token->expires_at)->toDateTimeString(),
                    'extras' => $extras
                ]);

            }else if($logged_user['roles']['name'] == 'medico' 
                || $logged_user['roles']['name'] == 'medico supervisor'
                || $logged_user['roles']['name'] == 'medico administrador'
                || $logged_user['roles']['name'] == 'medico auditor'
                ){ // si es un usuario medico o medico supervisor o medico auditor o medico administrador
                
                // obtenemos el usuario de sqlserver
                if($id_usuario_sqlserver != 0){
                    $logged_user['id_usuario_sqlserver'] = $id_usuario_sqlserver;
                    // buscamos los datos del usuario
                    array_push($extras['sps'], ['sp_usuario_select' => ['p_id_usuario' => $id_usuario_sqlserver]]);
                    array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]));
                    $usuario_sqlserver = $this->ejecutar_sp_directo('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]);
                    array_push($extras['responses'], ['sp_usuario_select' => $usuario_sqlserver]);
        
                    if(is_array($usuario_sqlserver) && !empty($usuario_sqlserver)){
                        $logged_user['usuario_sqlserver'] = $usuario_sqlserver[0];
                        $usuario_sqlserver = $usuario_sqlserver[0];
                    }else{
                        $logged_user['usuario_sqlserver'] = $usuario_sqlserver;
                    }
                }else{
                    $logged_user['usuario_sqlserver'] = null;
                }
    
                $medico = null;
                $id_usuario = $logged_user['id'];
                // buscamos el perfil de medico
                $profile_doctor = ProfileDoctor::where('user_id', '=', $id_usuario)->get();
                $extras['responses'] = ['profile_doctor' => $profile_doctor];
                $status = 'ok';
                $count = 1;
                $message = 'Perfil encontrado';
                $medico = $profile_doctor[0];

                $user->connected = 1;
                $user->save();      
                
                
    
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
                    'token_type' => 'Bearer',
                    'token_siaf' => $token_siaf,
                    'medico' => $medico,
                    'logged_user' => $logged_user,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => 1,
                    'expires_at' => Carbon::parse($token->expires_at)->toDateTimeString(),
                    'extras' => $extras
                ]);

            }else if($logged_user['roles']['name'] == 'prestador'){

                // obtenemos el usuario de sqlserver
                if($id_usuario_sqlserver != 0){
                    $logged_user['id_usuario_sqlserver'] = $id_usuario_sqlserver;
                    // buscamos los datos del usuario
                    array_push($extras['sps'], ['sp_usuario_select' => ['p_id_usuario' => $id_usuario_sqlserver]]);
                    array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]));
                    $usuario_sqlserver = $this->ejecutar_sp_directo('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]);
                    array_push($extras['responses'], ['sp_usuario_select' => $usuario_sqlserver]);
        
                    if(is_array($usuario_sqlserver) && !empty($usuario_sqlserver)){
                        $logged_user['usuario_sqlserver'] = $usuario_sqlserver[0];
                        $usuario_sqlserver = $usuario_sqlserver[0];
                    }else{
                        $logged_user['usuario_sqlserver'] = $usuario_sqlserver;
                    }
                }else{
                    $logged_user['usuario_sqlserver'] = null;
                }
    
                $prestador = null;
                $id_usuario = $logged_user['id'];
                $status = 'ok';
                $count = 1;
                $message = 'Prestador encontrado';
                $prestador = $usuario_sqlserver;

                $user->connected = 1;
                $user->save();      
                
                
    
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

                return response()->json([
                    'status' => 'ok',
                    'access_token' => $tokenResult->accessToken,
                    'token_type' => 'Bearer',
                    'token_siaf' => $token_siaf,
                    'prestador' => $prestador,
                    'logged_user' => $logged_user,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => 1,
                    'expires_at' => Carbon::parse($token->expires_at)->toDateTimeString(),
                    'extras' => $extras
                ]);
            }else{ // si NO es un usuario afiliado ni médico
                return response()->json([
                    'status' => 'fail',
                    'access_token' => null,
                    'token_type' => false,
                    'token_siaf' => null,
                    'afiliado' => null,
                    'logged_user' => null,
                    'errors' => $errors,
                    'message' => 'El usuario no es un afiliado ni médico',
                    'line' => null,
                    'code' => -2,
                    'expires_at' => null,
                    'extras' => $extras
                ]);
            }

            // if($logged_user['roles']['name'] != 'afiliado mobile' && $logged_user['roles']['name'] != 'afiliado'){
            //     // no lo logueamos
            //     return response()->json([
            //         'status' => 'fail',
            //         'access_token' => null,
            //         'token_type' => false,
            //         'token_siaf' => null,
            //         'afiliado' => null,
            //         'logged_user' => null,
            //         'errors' => $errors,
            //         'message' => 'El usuario no es un afiliado',
            //         'line' => null,
            //         'code' => -2,
            //         'expires_at' => null,
            //         'extras' => $extras
            //     ]);
            // }else{ // si es un usuario afiliado mobile
            //     // obtenemos el usuario de sqlserver
            //     if($id_usuario_sqlserver != 0){
            //         $logged_user['id_usuario_sqlserver'] = $id_usuario_sqlserver;
            //         // buscamos los datos del usuario
            //         array_push($extras['sps'], ['sp_usuario_select' => ['p_id_usuario' => $id_usuario_sqlserver]]);
            //         array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]));
            //         $usuario_sqlserver = $this->ejecutar_sp_directo('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]);
            //         array_push($extras['responses'], ['sp_usuario_select' => $usuario_sqlserver]);
        
            //         if(is_array($usuario_sqlserver) && !empty($usuario_sqlserver)){
            //             $logged_user['usuario_sqlserver'] = $usuario_sqlserver[0];
            //             $usuario_sqlserver = $usuario_sqlserver[0];
            //         }else{
            //             $logged_user['usuario_sqlserver'] = $usuario_sqlserver;
            //         }
            //     }else{
            //         $logged_user['usuario_sqlserver'] = null;
            //     }
    
            //     if($usuario_sqlserver != null){
            //         // buscamos el afiliado
            //         array_push($extras['sps'], ['sp_afiliado_select' => ['id_persona' => $usuario_sqlserver->id_persona]]);
            //         array_push($extras['queries'], $this->get_query('afiliacion', 'sp_afiliado_select', ['id_persona' => $usuario_sqlserver->id_persona]));
            //         $afiliado = $this->ejecutar_sp_directo('afiliacion', 'sp_afiliado_select', ['id_persona' => $usuario_sqlserver->id_persona]);
            //         array_push($extras['responses'], ['sp_afiliado_select' => $afiliado]);
            //         $afiliado = $afiliado[0];
            //         // buscamos la persona
            //         $params_persona = [
            //             'p_id_persona' => $afiliado->id_persona
            //         ];
            //         array_push($extras['sps'], ['sp_persona_Select' => $params_persona]);
            //         array_push($extras['queries'], $this->get_query('afiliacion', 'sp_persona_Select', $params_persona));
            //         $persona = $this->ejecutar_sp_directo('afiliacion', 'sp_persona_Select', $params_persona);
            //         array_push($extras['responses'], ['sp_persona_Select' => $persona]);
            //         if($persona){
            //             if(is_array($persona)){
            //                 $persona = $persona[0];
            //             }
            //             $persona->fec_vigencia = Carbon::parse($persona->fec_vigencia)->format('d/m/Y');
            //             $persona->fec_carga = Carbon::parse($persona->fec_carga)->format('d/m/Y');
            //             $afiliado->persona = $persona;
            //         }
            //         // buscamos el grupo familiar
            //         if($afiliado->id_grupo != null){
            //             $params_grupo = [
            //                 'p_id_grupo' => $afiliado->id_grupo,
            //                 'p_activos' => 1
            //             ];
            //             array_push($extras['sps'], ['sp_grupo_afiliado_Select' => $params_grupo]);
            //             array_push($extras['queries'], $this->get_query('afiliacion', 'sp_grupo_afiliado_Select', $params_grupo));
            //             $grupo = $this->ejecutar_sp_directo('afiliacion', 'sp_grupo_afiliado_Select', $params_grupo);
            //             array_push($extras['responses'], ['sp_grupo_afiliado_Select' => $grupo]);
            //             $afiliado->grupo_familiar = $grupo;
            //         }
            //     }

            //     $user->connected = 1;
            //     $user->save();            
    
            //     $tokenResult = $user->createToken('Personal Access Token');
        
            //     $token = $tokenResult->token;
            //     if ($request->remember_me){
            //         $token->expires_at = Carbon::now()->addWeeks(1);
            //     }
            //     $token->save();
    
            //     // guardamos los datos de la sesion del usuario
            //     $usuario_conectado = ConnectedUser::create([
            //         'user_id' => $logged_user['id'],    
            //         'id_usuario_sqlserver' => $logged_user['id_usuario_sqlserver'],
            //         'name' => $logged_user['name'],
            //         'usuario' => $logged_user['usuario'],
            //         'conectado' => true,
            //         'inicio_sesion' => Carbon::now(),
            //         'fin_sesion' => null,
            //         'forzado' => false,
            //         'ambiente' => env('AMBIENTE'),
            //         'token_id' => $token->id
            //     ]);
            //     $logged_user['usuario_conectado'] = $usuario_conectado;
                
            //     if($usuario_conectado->conectado == 1){
            //         $logged_user['id_sesion_activa'] = $usuario_conectado->id;
            //         $user->id_sesion_activa = $usuario_conectado->id;
            //         $user->save();
            //     }
    
            //     // validamos en siaf si corresponde
            //     if(env('VALIDAR_SIAF') == 'SI'){
            //         try {
            //             if($token_siaf == null || $token_siaf == ''){
            //                 $fields = array(
            //                     'username' => env('USER_SIAF'),
            //                     'password' => env('PASSWORD_SIAF')
            //                 );
            //                 $fields_string = http_build_query($fields);
            //                 $ch = curl_init();
            //                 curl_setopt_array($ch, array(
            //                     CURLOPT_URL => env('LOGIN_SIAF'),
            //                     CURLINFO_HEADER_OUT => true,
            //                     CURLOPT_POST => 1,
            //                     CURLOPT_HTTPHEADER => array(
            //                         "cache-control: no-cache",
            //                         'Content-Type: application/x-www-form-urlencoded'
            //                     ),
            //                     CURLOPT_RETURNTRANSFER => true,
            //                     CURLOPT_FOLLOWLOCATION => true,
            //                     CURLOPT_POSTFIELDS => $fields_string,
            //                     CURLOPT_SSL_VERIFYHOST => 0,
            //                 ));
            //                 $response_login_siaf = curl_exec($ch);
            //                 curl_close($ch);
            //                 $json_login_siaf = json_decode($response_login_siaf);
            //                 array_push($extras['responses'], ['login_siaf' => $json_login_siaf]);
            //                 if(gettype($json_login_siaf) == 'string') {
            //                     $token_siaf = $json_login_siaf;
            //                 }else{
            //                     if($json_login_siaf != null){
            //                         Log::channel('siaf')->error('ERROR LOGIN SIAF EN AUTH. Code -2 '.json_decode(json_encode($json_login_siaf), false)->Message);
            //                     }else{
            //                         Log::channel('siaf')->error('ERROR LOGIN SIAF EN AUTH. Code -3. response null');
            //                     }
            //                 }
            //             }
            //         } catch (\Throwable $th) {
            //             $message = 'Error de Login en SIAF. Line: '.$th->getLine().' Error: '.$th->getMessage();
            //             $status = 'fail';
            //             array_push($errors, 'ERROR SIAF. Line: '.$th->getLine().' Error: '.$th->getMessage());
            //             $code = -2;
            //             Log::channel('siaf')->error('ERROR LOGIN SIAF EN AUTH. Code -1. Line: '.$th->getLine().' Error: '.$th->getMessage());
            //         } finally {
            //             if(is_resource($ch)){
            //                 curl_close( $ch );
            //             }
            //         }
            //     }
        
            //     return response()->json([
            //         'status' => 'ok',
            //         'access_token' => $tokenResult->accessToken,
            //         'token_type' => 'Bearer',
            //         'token_siaf' => $token_siaf,
            //         'afiliado' => $afiliado,
            //         'logged_user' => $logged_user,
            //         'errors' => $errors,
            //         'message' => $message,
            //         'line' => null,
            //         'code' => 1,
            //         'expires_at' => Carbon::parse($token->expires_at)->toDateTimeString(),
            //         'extras' => $extras
            //     ]);
            // }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'access_token' => null,
                'token_type' => false,
                'token_siaf' => null,
                'afiliado' => null,
                'logged_user' => null,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'expires_at' => null,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Cierre de sesión (anular el token)
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function logout_afiliado(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/auth/logout-afiliado',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        // return Carbon::now()->subHours(3)->format('Y-m-d H:i:s');
        try {
            $u = null;
            $usuario_conectado = ConnectedUser::where('user_id', '=', $request->user()->id)
                                                ->where('conectado', '=', 1)
                                                ->where('ambiente', 'like', env('AMBIENTE'))
                                                ->latest()
                                                ->first();
            if(!empty($usuario_conectado) && $usuario_conectado->conectado == 1)  {
                $usuario_conectado->conectado = 0;
                $usuario_conectado->fin_sesion = Carbon::now()->subHours(3)->format('Y-m-d H:i:s');
                $u = $usuario_conectado->save();
            }
            $user = User::find($request->user()->id);
            $user->connected = 0;
            $user->id_session_activa = null;
            $user->save();
        } catch (\Exception $e) {
            return $e;
        } finally {
            $request->user()->token()->revoke();
    
            return response()->json([
                'status' => 'ok',
                'message' => 'Cierre de sesión exitoso.',
                'cierre_sesion' => $u,
                'user' => $user,
                'usuario_conectado' => $usuario_conectado,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Obtener el objeto User del usuario logueado como json
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function user_afiliado(Request $request)
    {
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/auth/user-afiliado',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        try {
            $errors = [];
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $id_usuario_sqlserver = $logged_user['id_usuario_sqlserver'];

            $afiliado = null;

            if($id_usuario_sqlserver != 0){
                // buscamos los datos del usuario
                array_push($extras['sps'], ['sp_usuario_select' => ['p_id_usuario' => $id_usuario_sqlserver]]);
                array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]));
                $usuario_sqlserver = $this->ejecutar_sp_directo('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]);
                array_push($extras['responses'], ['sp_usuario_select' => $usuario_sqlserver]);
    
                if(is_array($usuario_sqlserver)){
                    $logged_user['usuario_sqlserver'] = $usuario_sqlserver[0];
                    $usuario_sqlserver = $usuario_sqlserver[0];
                }else{
                    $logged_user['usuario_sqlserver'] = $usuario_sqlserver;
                }
            }else{
                $logged_user['usuario_sqlserver'] = null;
            }
            
            if($logged_user['usuario_sqlserver'] != null){
                // buscamos el afiliado
                array_push($extras['sps'], ['sp_afiliado_select' => ['id_persona' => $usuario_sqlserver->id_persona]]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_afiliado_select', ['id_persona' => $usuario_sqlserver->id_persona]));
                $afiliado = $this->ejecutar_sp_directo('afiliacion', 'sp_afiliado_select', ['id_persona' => $usuario_sqlserver->id_persona]);
                array_push($extras['responses'], ['sp_afiliado_select' => $afiliado]);
                $afiliado = $afiliado[0];
                // buscamos la persona
                $params_persona = [
                    'p_id_persona' => $afiliado->id_persona
                ];
                array_push($extras['sps'], ['sp_persona_Select' => $params_persona]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_persona_Select', $params_persona));
                $persona = $this->ejecutar_sp_directo('afiliacion', 'sp_persona_Select', $params_persona);
                array_push($extras['responses'], ['sp_persona_Select' => $persona]);
                if($persona){
                    if(is_array($persona)){
                        $persona = $persona[0];
                    }
                    $persona->fec_vigencia = Carbon::parse($persona->fec_vigencia)->format('d/m/Y');
                    $persona->fec_carga = Carbon::parse($persona->fec_carga)->format('d/m/Y');
                    $afiliado->persona = $persona;
                }
                // buscamos el grupo familiar
                if($afiliado->id_grupo != null){
                    $params_grupo = [
                        'p_id_grupo' => $afiliado->id_grupo,
                        'p_activos' => 1
                    ];
                    array_push($extras['sps'], ['sp_grupo_afiliado_Select' => $params_grupo]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_grupo_afiliado_Select', $params_grupo));
                    $grupo = $this->ejecutar_sp_directo('afiliacion', 'sp_grupo_afiliado_Select', $params_grupo);
                    array_push($extras['responses'], ['sp_grupo_afiliado_Select' => $grupo]);
                    $afiliado->grupo_familiar = $grupo;
                }
            }
            
            return response()->json([
                'status' => 'ok',
                'count' => 1,
                'errors' => $errors,
                'message' => 'Usuario obtenido',
                'line' => null,
                'code' => 1,
                'data' => [
                    'usuario' => $logged_user,
                    'afiliado' => $afiliado,
                ],
                'params' => null,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
            // return response()->json($response);
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => [
                    'usuario' => null,
                    'afiliado' => null,
                ],
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
    public function cambiar_password_afiliado(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/auth/cambiar-password-afiliado',
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
            $mysql_actualizado = false;
            $affected = false;
            $actualizar = true;
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            $extras['sqlserver_actualizado'] = $sqlserver_actualizado;
            $extras['mysql_actualizado'] = $mysql_actualizado;

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
                $extras['mysql_actualizado'] = $affected;
                if(($sincronizar && $sqlserver_actualizado && $affected)){
                    $status = 'ok';
                    $count = 2;
                    $message = 'La contraseña fue actualizada con éxito en ambas bases de datos';
                    $code = 1;
                    $data = [
                        'sqlserver_actualizado' => $sqlserver_actualizado,
                        'mysql_actualizado' => $affected
                    ];
                }else if(!$sincronizar && $affected){
                    $status = 'ok';
                    $count = 1;
                    $message = 'La contraseña fue actualizada en mysql';
                    $code = 2;
                    $data = [
                        'sqlserver_actualizado' => $sqlserver_actualizado,
                        'mysql_actualizado' => $affected
                    ];
                }else{
                    $status = 'fail';
                    if($sqlserver_actualizado && !$affected){
                        $count = 1;
                        $message = 'La contraseña no fue actualizada en mysql pero si en sqlserver. Las bases de datos NO están sincrinozadas';
                        $code = -5;
                        $data = [
                            'sqlserver_actualizado' => $sqlserver_actualizado,
                            'mysql_actualizado' => $affected
                        ];
                    }else if(!$sqlserver_actualizado && $affected){
                        $count = 1;
                        $message = 'La contraseña no fue actualizada en sqlserver pero si en mysql. Las bases de datos NO están sincrinozadas';
                        $code = -4;
                        $data = [
                            'sqlserver_actualizado' => $sqlserver_actualizado,
                            'mysql_actualizado' => $affected
                        ];
                    }else{
                        $count = 0;
                        $message = 'La contraseña no fue actualizada ninguna base de datos';
                        $code = -3;
                        $data = [
                            'sqlserver_actualizado' => $sqlserver_actualizado,
                            'mysql_actualizado' => $affected
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
                'code' => -1,
                'data' => null,
                'params' => null,
                'extras' => $extras,
                'logged_user' => null,
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
        return 'llega';
        // $u = User::with('roles', 'permissions')->find($request->user()->id);
        // $logged_user = $this->get_logged_user($u);
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/auth/update-user',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
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
     * Envía un token al email del usuario para resetear la clave
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function enviar_token_reseteo_password_afiliado(Request $request)
    {
        
        // return $this->ejecutar_sp_directo('admin', 'sp_usuario_Select', ['p_email' => 'ivanfischer76@gmail.com']);
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'mobile/auth/enviar-token-reseteo-password-afiliado',
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
            // // para verificar el envio de email descomentar esto y comentar lo que sigue para no resetear la clave
            // $asunto = 'Solicitud de cambio de clave'; 
            // $email = request('email');
            // $usuario = (object)[
            //     'apynom' => 'Usuario Prueba'
            // ];
            // $token = 'U12C04';
            // $response = 'es solo una prueba de envío de email, no se reseteó ninguna clave';
            // $user = $usuario;

            // if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
            //     $mailable = new NotificacionEmailReseteoClave($asunto, $usuario->apynom, $token);
            //     // Envía automáticamente con fallback
            //     $resultado = $this->sendEmail($email, $mailable);
            //     array_push($extras['responses'], ['sendEmail' => $resultado]);
            //     if ($resultado) {
            //         $message = 'Clave reseteada, token enviado por email. Email enviado con Microsoft Graph';
            //         $error = null;
            //         $status = 'ok';
            //         $code = 1;
            //         $count = 1;
            //         $data = [
            //             'token' => $token,
            //             'usuario_sqlserver' => $response,
            //             'usuario_mysql' => $user
            //         ];
            //     }else{
            //         $message = 'Clave reseteada pero no se pude enviar el email. Error al enviar email con Microsoft Graph';
            //         $error = $resultado;
            //         array_push($errors, 'Error al enviar email con Microsoft Graph: '.json_encode($resultado));
            //         $status = 'fail';
            //         $code = -3;
            //         $count = 0;
            //         $data = [
            //             'token' => $token,
            //             'usuario_sqlserver' => $response,
            //             'usuario_mysql' => $user
            //         ];
            //     }
            // }else{
            //     Mail::to($email)->send(new NotificacionEmailReseteoClave($asunto, $usuario->apynom, $token));
            //     if(Mail::failures()){
            //         $message = 'Clave reseteada pero no se pude enviar el email. Error al enviar email por SMTP';
            //         $error = Mail::failures();
            //         array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
            //         array_push($extras['responses'], ['smtp_result' => false]);
            //         Log::channel('email')->error('MobileAuthController enviar_token_reseteo_password_afiliado Error al enviar email por SMTP', [
            //             'emails' => $email,
            //             'asunto' => $asunto,
            //             'usuario' => $usuario->apynom,
            //             'token' => $token,
            //         ]);
            //         $status = 'fail';
            //         $code = -4;
            //         $count = 0;
            //         $data = [
            //             'token' => $token,
            //             'usuario_sqlserver' => $response,
            //             'usuario_mysql' => $user
            //         ];
            //     }else{
            //         $message = 'Clave reseteada, token enviado por email. Email enviado por SMTP';
            //         $error = null;
            //         array_push($extras['responses'], ['smtp_result' => true]);
            //         Log::channel('email')->info('MobileAuthController enviar_token_reseteo_password_afiliado Email enviado por SMTP', [
            //             'emails' => $email,
            //             'asunto' => $asunto,
            //             'usuario' => $usuario->apynom,
            //             'token' => $token,
            //         ]);
            //         $status = 'ok';
            //         $code = 2;
            //         $count = 1;
            //         $data = [
            //             'token' => $token,
            //             'usuario_sqlserver' => $response,
            //             'usuario_mysql' => $user
            //         ];
            //     }
            //     Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
            // }
            


            // // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->where('email', 'like', $params['email'])->get();
            array_push($extras['responses'], ['user' => $user]);
            
            if(!empty($user) && sizeof($user) > 0){
                $user = $user[0];
            }else{
                $user = null;
            }

            if($user != null){
                $params_sp = [
                    'p_email' => $params['email']
                ];
                $data = [
                    'token' => null,
                    'usuario_sqlserver' => null,
                    'usuario_mysql' => null
                ];
                // buscar el usuario
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
                        if(is_array($response)){
                            $usuario = $response[0];
                        }else{
                            $usuario = $response;
                        }
                        //obtiene el token
                        $p = [
                            'p_id_usuario' => $usuario->id_usuario,
                            'p_finaliza_clave' => 1,
                            'p_n_usuario' => $usuario->n_usuario
                        ];
                        array_push($extras['sps'], ['sp_usuario_update' => $p]);
                        array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_update', $p));
                        $token = $this->ejecutar_sp_directo('admin', 'sp_usuario_update', $p);
                        array_push($extras['responses'], ['sp_usuario_update' => $token]);
                        // cambiar clave por el token en este sistema
                        // return $token;
                        $token = $token[0]->token;
                        if($user != null){
                            $user->password = Hash::make($token);
                            $resp = $user->save();
                            array_push($extras['responses'], ['cambio_password' => $resp]);
                        }
                        // return $resp;
                        // envía el token por email
                        $email = $usuario->email;
                        $asunto = 'Solicitud de cambio de clave'; 
                        // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                        if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                            $mailable = new NotificacionEmailReseteoClave($asunto, $usuario->apynom, $token);
                            // Envía automáticamente con fallback
                            $resultado = $this->sendEmail($email, $mailable);
                            array_push($extras['responses'], ['sendEmail' => $resultado]);
                            if ($resultado) {
                                $message = 'Clave reseteada, token enviado por email. Email enviado con Microsoft Graph';
                                $error = null;
                                $status = 'ok';
                                $code = 1;
                                $count = 1;
                                $data = [
                                    'token' => $token,
                                    'usuario_sqlserver' => $response,
                                    'usuario_mysql' => $user
                                ];
                            }else{
                                $message = 'Clave reseteada pero no se pude enviar el email. Error al enviar email con Microsoft Graph';
                                $error = $resultado;
                                array_push($errors, 'Error al enviar email con Microsoft Graph: '.json_encode($resultado));
                                $status = 'fail';
                                $code = -3;
                                $count = 0;
                                $data = [
                                    'token' => $token,
                                    'usuario_sqlserver' => $response,
                                    'usuario_mysql' => $user
                                ];
                            }
                        }else{
                            Mail::to($email)->send(new NotificacionEmailReseteoClave($asunto, $usuario->apynom, $token));
                            if(Mail::failures()){
                                $message = 'Clave reseteada pero no se pude enviar el email. Error al enviar email por SMTP';
                                $error = Mail::failures();
                                array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                                $status = 'fail';
                                array_push($extras['responses'], ['smtp_result' => false]);
                                Log::channel('email')->error('MobileAuthController enviar_token_reseteo_password_afiliado Error al enviar email por SMTP', [
                                    'emails' => $email,
                                    'asunto' => $asunto,
                                    'usuario' => $usuario->apynom,
                                    'token' => $token,
                                ]);
                                $code = -4;
                                $count = 0;
                                $data = [
                                    'token' => $token,
                                    'usuario_sqlserver' => $response,
                                    'usuario_mysql' => $user
                                ];
                            }else{
                                $message = 'Clave reseteada, token enviado por email. Email enviado por SMTP';
                                $error = null;
                                array_push($extras['responses'], ['smtp_result' => true]);
                                Log::channel('email')->info('MobileAuthController enviar_token_reseteo_password_afiliado Email enviado por SMTP', [
                                    'emails' => $email,
                                    'asunto' => $asunto,
                                    'usuario' => $usuario->apynom,
                                    'token' => $token,
                                ]);
                                $status = 'ok';
                                $code = 2;
                                $count = 1;
                                $data = [
                                    'token' => $token,
                                    'usuario_sqlserver' => $response,
                                    'usuario_mysql' => $user
                                ];
                            }
                            Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                        }

                        // Mail::to($email)->send(new NotificacionEmailReseteoClave($asunto, $usuario->apynom, $token));
                        // if (Mail::failures()) {
                        //     array_push($errors, 'error en el envío del email');
                        //     $status = 'fail'; 
                        //     $message = 'Clave reseteada pero no se pude enviar el email';
                        //     $count = 0;
                        //     $data = [
                        //         'token' => $token,
                        //         'usuario_sqlserver' => $response,
                        //         'usuario_mysql' => $user
                        //     ];
                        //     $code = -3;
                        // }else{
                        //     $status = 'ok';
                        //     $message = 'Clave reseteada, token enviado por email.';
                        //     $count = 1;
                        //     $data = [
                        //         'token' => $token,
                        //         'usuario_sqlserver' => $response,
                        //         'usuario_mysql' => $user
                        //     ];
                        //     $code = 1;
                        // }
                    }
                }else{
                    array_push($errors, 'error en la búsqueda del usuario en sql server. ');
                    $status = 'fail'; 
                    $message = 'No hay usuario registrado con ese email';
                    $count = 0;
                    $code = -5;
                    // // si el usuario no existe en el otro sistema
                    // $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    // // crea el token
                    // $token = substr(str_shuffle($permitted_chars), 0, 8);
                    // // si el usuario existe en este sistema
                    // if($user != null){
                    //     // resetea la clave colocando el token
                    //     $user->password = Hash::make($token);
                    //     $resp = $user->save();
                    //     array_push($extras['responses'], ['token_mysql' => $resp]);
                    //     // envía el token por email
                    //     $email = $user->email;
                    //     $asunto = 'Solicitud de cambio de clave'; 
                    //     Mail::to($email)->send(new NotificacionEmailReseteoClave($asunto, $user->name, $token));
                    //     // prepara la respuesta
                    //     $status = 'ok';
                    //     $message = 'Clave reseteada, token enviado por email.';
                    //     $count = 1;
                    //     $data = [
                    //         'usuario_sqlserver' => $response,
                    //         'token' => $token,
                    //         'usuario_mysql' => $user
                    //     ];
                    //     $code = 2;
                    // }else{
                    //     // si el usuario no existe en este sistema prepara la respuesta
                    //     array_push($errors, 'Usuario inexistente');
                    //     $status = 'fail';
                    //     $message = 'Usuario no encontrado';
                    //     $code = -3;
                    //     $count = 0;
                    //     $data = null;
                    // }
                }
            }else{
                $status = 'fail';
                $message = 'El email no pertenece a un usuario registrado';
                $count = 0;
                $code = -6;
                $data = null;
                $errors = ['El email no pertenece a ningún usuario registrado en la api'];
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
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
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
    public function resetear_password_afiliado(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'mobile/auth/resetear-password-afiliado',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail';
        $message = '';
        $count = 0;
        $code = null;
        $data = [
            'sqlserver' => null,
            'api' => null
        ];
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
            // buscar el usuario dependiendo de los parámetros
            if(request('id_usuario_sqlserver') != null){
                $id_usuario_sqlserver = request('id_usuario_sqlserver');
                $params['id_usuario_sqlserver'] = $id_usuario_sqlserver;
                array_push($extras['sps'], ['sp_usuario_Select' => ['p_id_usuario' => request('id_usuario_sqlserver')]]);
                array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_Select', ['p_id_usuario' => request('id_usuario_sqlserver')]));
                $usuario_sqlserver = $this->ejecutar_sp_directo('admin', 'sp_usuario_Select', ['p_id_usuario' => request('id_usuario_sqlserver')]);
                array_push($extras['responses'], ['sp_usuario_Select' => $usuario_sqlserver]);
                $user = User::with('roles', 'permissions')->where('id_usuario_sqlserver', '=', intval(request('id_usuario_sqlserver')))->get();             
                $user = $user[0];
            }else{
                array_push($extras['sps'], ['sp_usuario_Select' => ['p_email' => request('email')]]);
                array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_Select', ['p_email' => request('email')]));
                $usuario_sqlserver = $this->ejecutar_sp_directo('admin','sp_usuario_Select', ['p_email' => request('email')]);
                array_push($extras['responses'], ['sp_usuario_Select' => $usuario_sqlserver]);
                $usuario_sqlserver = $usuario_sqlserver[0];

                $user = User::with('roles', 'permissions')->where('email', 'like', request('email'))->get();
                $user = $user[0];
            }
            
            $coinciden = strcmp($password, $confirm) == 0;
            
            if($coinciden){
                // si el usuario existe en el otro sistema
                if(!empty($usuario_sqlserver)){
                    if(is_array($usuario_sqlserver) && array_key_exists('error', $usuario_sqlserver)){
                        array_push($errors, $usuario_sqlserver['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $code = -4;
                    }else{
                        
                        $params_sp = [
                            'p_id_usuario' => $usuario_sqlserver->id_usuario,
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
                            array_push($extras['responses'], ['api' => $affected]);
                            // prepara la respuesta
                            $message = 'Contraseña actualizada con éxito';
                            $status = 'ok';
                            $count = 1;
                            $data = [
                                'sqlserver' => $response,
                                'api' => $affected
                            ];
                            $code = 1;
                        }else{
                            array_push($errors, 'Error de sqlserver');
                            $message = 'No se pudo actualizar la contraseña';
                            $status = 'fail';
                            $code = -5;
                        }
                        
                    }
                }else{
                    // si el usuario no existe en el otro sistema
                    array_push($errors, 'Usuario no encontrado');
                    $message = 'No se encontró ningún usuario registrado con el email proporcionado.';
                    $status = 'fail';
                    $code = -3;
                }
            }else{
                array_push($errors, 'La contraseña y la confirmación no coinciden');
                $message = 'La contraseña y la confirmación no coinciden';
                $status = 'fail';
                $code = -2;
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
     * Envía el nombre de usuario al email del usuario
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function enviar_nombre_usuario(Request $request)
    {
        
        // return $this->ejecutar_sp_directo('admin', 'sp_usuario_Select', ['p_email' => 'ivanfischer76@gmail.com']);
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'mobile/auth/enviar-nombre-usuario',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail';
        $message = '';
        $error = null;
        $res = ['message' => ''];
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $logged_user = null;
        $params_sp = [];
        $params = [
            'email' => request('email')
        ];
        
        try {
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->where('email', 'like', $params['email'])->get();
            array_push($extras['responses'], ['user' => $user]);
            
            if(!empty($user) && sizeof($user) > 0){
                $user = $user[0];
            }else{
                $user = null;
            }

            if($user != null){
               $email = $user->email;
                $asunto = 'Recordatorio de nombre de usuario'; 
                // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                    $mailable = new NotificacionEmailNombreUsuario($asunto, $user->usuario);
                    // Envía automáticamente con fallback
                    $resultado = $this->sendEmail($email, $mailable);
                    array_push($extras['responses'], ['sendEmail' => $resultado]);
                    if ($resultado) {
                        $message = 'Email enviado con Microsoft Graph. ';
                        $error = null;
                        $status = 'ok';
                        $code = 1;
                    }else{
                        $message = 'Error al enviar email con Microsoft Graph';
                        $error = $resultado;
                        array_push($errors, 'Error al enviar email con Microsoft Graph: '.json_encode($resultado));
                        $status = 'fail';
                        $code = -3;
                    }
                }else{
                    Mail::to($email)->send(new NotificacionEmailNombreUsuario($asunto, $user->usuario));
                    if(Mail::failures()){
                        $message = 'Error al enviar email por SMTP. ';
                        $error = Mail::failures();
                        array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                        array_push($extras['responses'], ['smtp_result' => false]);
                        Log::channel('email')->error('MobileAuthController enviar_nombre_usuario Error al enviar email por SMTP', [
                            'emails' => $email,
                            'asunto' => $asunto,
                            'usuario' => $user->usuario,
                        ]);
                        $status = 'fail';
                        $code = -4;
                        $data = [
                            'user' => $user
                        ];
                    }else{
                        $message = 'Email enviado por SMTP. ';
                        $error = null;
                        array_push($extras['responses'], ['smtp_result' => true]);
                        Log::channel('email')->info('MobileAuthController enviar_nombre_usuario Email enviado por SMTP', [
                            'emails' => $email,
                            'asunto' => $asunto,
                            'usuario' => $user->usuario,                        
                        ]);
                        $status = 'ok';
                        $code = 2;
                        $data = [
                            'user' => $user
                        ];
                    }
                    Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                }
                // Mail::to($email)->send(new NotificacionEmailNombreUsuario($asunto, $user->usuario));
                // if (Mail::failures()) {
                //     array_push($errors, 'error en el envío del email');
                //     $status = 'fail'; 
                //     $message = 'No se pudo enviar el email';
                //     $count = 0;
                //     $data = [
                //         'user' => $user
                //     ];
                //     $code = -3;
                // }else{
                //     $status = 'ok';
                //     $message = 'Email enviado con éxito.';
                //     $count = 1;
                //     $data = [
                //         'user' => $user
                //     ];
                //     $code = 1;
                // }
            }else{
                $status = 'fail';
                $message = 'El email no pertenece a un usuario registrado';
                $count = 0;
                $code = -5;
                $data = null;
                $errors = ['El email no pertenece a ningún usuario registrado en la api'];
            }
            
            return response()->json([
                'status' => $status,
                'errors' => $errors,
                'message' => $message,
                'code' => $code,
                'line' => null,
                'count' => $count,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]); 
            
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'errors' => $errors,
                'message' => $th->getMessage(),
                'code' => -1,
                'line' => $th->getLine(),
                'count' => -1,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Actualiza lso campos token_fcm y devic_type
     */
    public function actualizar_fcm_token(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'mobile/auth/actualizar-fcm-token',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail';
        $message = '';
        $count = 0;
        $code = null;
        $data = null;
        $errors = [];
        
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
                    'id_persona' => request('id_persona'),
                    'fcm_token' => request('fcm_token'),
                    'device_type' => request('device_type')
                ];
                $params_sp = [
                    'p_id_persona' => request('id_persona'),
                    'p_fcm_token' => request('fcm_token'),
                    'p_device_type' => request('device_type')
                ];
                array_push($extras['verificado'], [
                    'sp_persona_fcm_update' => [
                            'id_persona' => request('id_persona')
                        ]
                    ]
                );
                if ( empty(request('id_persona')) ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    $sp = 'sp_persona_fcm_update';
                    $db = 'afiliacion';
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
                        $message = 'Transacción realizada con éxito.fcm_token actualizado';
                        $count = 1;
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

    /**
     * Registra un dispoisitivo
     */
    public function registrar_dispositivo(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/auth/registrar-dispositivo',
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
            if($id_usuario_sqlserver != 0){
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
                    'id_persona' => request('id_persona') !== null ? request('id_persona') : $usuario_sqlserver->id_persona,
                    'fcm_token' => request('fcm_token'),
                    'device_id' => request('device_id'),
                    'device_name' => request('device_name'),
                    'platform' => request('platform'),
                    'os_version' => request('os_version'),
                    'app_version' => request('app_version'),
                    'ultimo_acceso' => request('ultimo_acceso'),
                    'fecha_registro' => request('fecha_registro'),
                    'activo' => request('activo'),
                ];
                
                $params_sp = [
                    'p_id_persona' => $params['id_persona'],
                    'p_fcm_token' => $params['fcm_token'],
                    'p_device_id' => $params['device_id'],
                    'p_device_name' => $params['device_name'],
                    'p_platform' => $params['platform'],
                    'p_os_version' => $params['os_version'],
                    'p_app_version' => $params['app_version'],
                    'p_ultimo_acceso' => $params['ultimo_acceso'],
                    'p_fecha_registro' => $params['fecha_registro'],
                    'p_activo' => $params['activo'],
                ];
                $sp = 'sp_persona_dispositivo_insert';
                $db = 'admin';
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
     * Obtiene un dispositivo
     */
    public function consultar_dispositivos(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/auth/consultar-dispositivos',
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
            if($id_usuario_sqlserver != 0){
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
                    'id_persona' => request('id_persona') !== null ? request('id_persona') : $usuario_sqlserver->id_persona,
                    'device_id' => request('device_id'),
                ];

                $params_sp = [
                    'p_id_persona' => $params['id_persona'],
                    'p_device_id' => $params['device_id'],
                ];
                $sp = 'sp_persona_dispositivo_select';
                $db = 'admin';
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