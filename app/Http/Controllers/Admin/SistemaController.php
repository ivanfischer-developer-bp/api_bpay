<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;
use App\Models\ConnectedUser;

use DB;
use Carbon\Carbon;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;
use Pusher\ApiErrorException;
use Pusher\Pusher;
use Pusher\PusherException;

class SistemaController extends ConexionSpController
{
    
    /**
     * Informa a los usuarios conectados de una nueva versión de software via pusher
     * Emite notificación Pusher
     */
    public function informar_nueva_version_front(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'admin/sistema/informar-nueva-version-front',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'sps' => [],
            'responses' => [],
            'verificado' => []
        ];
        $status = '';
        $message = '';
        $count = -1;
        $params = [];
        $code = null;
        $data = null;
        $errors = [];
        $logged_user = null;
        $permiso_requerido = '';

        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

        try {
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $version = request('version');

                $params = [
                    'version' => $version
                ];

                $channel = "notificaciones-sistema";
                $event = "NuevaVersionSoftware";
                $msg = [
                    'code' => 100, 
                    'version' => $version,
                    'message' => 'Nueva versión de software disponible con mejoras, correcciones y nuevas funcionalidades. Versión: '.$version,
                    'ambito' => env('ENVIRONMENT')
                ];  // message

                try {
                    $clientePusher = new Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), array('cluster' => env('PUSHER_APP_CLUSTER', 'us2')));
                } catch (PusherException $e) {
                    Log::channel('pusher')->warning("1.- informar_nueva_version_front - Error al instanciar pusher: " . $e->getMessage());
                    array_push($errors, $e->getMessage());
                    $response = [
                        'status' => 'fail',
                        'count' => 0,
                        'errors' => $errors,
                        'message' => 'Error, Notificacion NO emitida. Error al instanciar pusher',
                        'line' => null,
                        'code' => -6,
                        'data' => [
                            'channel' => $channel,
                            'event' => $event,
                            'msg' => $msg,
                            'notificacion' => $msg,
                            'emitido' => false,
                        ],
                        'params' => $params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ];
                    return response()->json($response); 
                }

                try {
                    $clientePusher->trigger($channel, $event, $msg);
                    $response = [
                        'status' => 'ok',
                        'count' => 0,
                        'errors' => $errors,
                        'message' => 'Notificacion emitida con éxito',
                        'line' => null,
                        'code' => 1,
                        'data' => [
                            'channel' => $channel,
                            'event' => $event,
                            'msg' => $msg,
                            'notificacion' => $msg,
                            'emitido' => true,
                        ],
                        'params' => $params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ];
                    return response()->json($response); 
                } catch (PusherException $e) {
                    Log::channel('pusher')->warning("2.- informar_nueva_version_front - Error al desencadenar evento pusher: " . $e->getMessage());
                    array_push($errors, $e->getMessage());
                    $response = [
                        'status' => 'fail',
                        'count' => 0,
                        'errors' => $errors,
                        'message' => 'Error, Notificacion NO emitida',
                        'line' => null,
                        'code' => -5,
                        'data' => [
                            'channel' => $channel,
                            'event' => $event,
                            'msg' => $msg,
                            'notificacion' => $msg,
                            'emitido' => false,
                        ],
                        'params' => $params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ];
                    return response()->json($response); 
                } catch (GuzzleException $e) {
                    Log::channel('pusher')->warning("3.- informar_nueva_version_front - Error al desencadenar evento guzzle: " . $e->getMessage());
                    array_push($errors, $e->getMessage());
                    $response = [
                        'status' => 'fail',
                        'count' => 0,
                        'errors' => $errors,
                        'message' => 'Error, Notificacion NO emitida',
                        'line' => null,
                        'code' => -4,
                        'data' => [
                            'channel' => $channel,
                            'event' => $event,
                            'msg' => $msg,
                            'notificacion' => $msg,
                            'emitido' => false,
                        ],
                        'params' => $params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ];
                    return response()->json($response); 
                } catch (\Exception $e) {
                    Log::channel('pusher')->warning("4.- informar_nueva_version_front - Exception: " . $e->getMessage());
                    array_push($errors, $e->getMessage());
                    $response = [
                        'status' => 'fail',
                        'count' => 0,
                        'errors' => $errors,
                        'message' => 'Error, Notificacion NO emitida',
                        'line' => null,
                        'code' => -3,
                        'data' => [
                            'channel' => $channel,
                            'event' => $event,
                            'msg' => $msg,
                            'notificacion' => $msg,
                            'emitido' => false,
                        ],
                        'params' => $params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ];
                    return response()->json($response); 
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($this->permiso_requerido);
                $count  = -1;
                $data = null;
                array_push($errors, 'Error de permisos. '.$message);
                // retorna el response
                $response = [
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
                ];
                return response()->json($response);
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' de SistemaController.'.' Error: '.$th->getMessage());
            $response = [
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
            ];
            return response()->json($response);
        }
    }

    /**
     * Fuerza el reload del sistema a un usuario específico
     * Emite notificación Pusher
     */
    public function forzar_reload_usuario(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'admin/sistema/forzar-reload-usuario',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'sps' => [],
            'responses' => []
        ];
        $status = '';
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $this->params = [];
        $permiso_requerido = '';
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        date_default_timezone_set('America/Argentina/Cordoba');
        try {
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $id_usuario = request('id_usuario');
                $this->params = [
                    'id_usuario' => $id_usuario
                ];
                if($id_usuario != null){
                    $channel = 'notificaciones-sistema';
                    $event = 'Reload';
                    $msg = [
                        'code' => 101, 
                        'id_usuarios' => [$id_usuario],
                        'message' => 'Recarga forzada por el administrador', 
                        'ambito' => env('ENVIRONMENT')
                    ];
                    $data = [
                        'id_usuarios' => [$id_usuario],
                        'canal' => $channel,
                        'evento' => $event,
                        'msg' => $msg,
                        'mensaje' => '',
                        'notificacion' => $msg,
                        'emitido' => false,
                    ];
                    try {
                        $clientePusher = new Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), array('cluster' => env('PUSHER_APP_CLUSTER', 'us2')));
                    } catch (PusherException $e) {
                        Log::channel('pusher')->warning("1.- forzar_reload_usuario - Error al instanciar pusher: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => 'Error, Notificacion NO emitida. Error al instanciar pusher',
                            'line' => null,
                            'code' => -7,
                            'data' => [
                                'id_usuarios' => [$id_usuario],
                                'channel' => $channel,
                                'event' => $event,
                                'msg' => $msg,
                                'notificacion' => $msg,
                                'emitido' => false,
                            ],
                            'params' => $this->params,
                            'extras' => $extras,
                            'logged_user' => $logged_user,
                        ];
                        return response()->json($response); 
                    }
                    
                    try {
                        $clientePusher->trigger($channel, $event, $msg);
                        $status = 'ok';
                        $message = 'Recarga forzada por el administrador';
                        $code = 1;
                        $count = 1;
                        $data = [
                            'id_usuarios' => [$id_usuario],
                            'canal' => $channel,
                            'evento' => $event,
                            'msg' => $msg,
                            'mensaje' => 'Recarga forzada por el administrador',
                            'notificacion' => $msg,
                            'emitido' => true,
                        ];
                    } catch (PusherException $e) {
                        Log::channel('pusher')->warning("2.- forzar_reload_usuario - Error al desencadenar evento pusher: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => 'Error, Notificacion NO emitida. Error al desencadenar evento pusher',
                            'line' => null,
                            'code' => -6,
                            'data' => [
                                'id_usuarios' => [$id_usuario],
                                'channel' => $channel,
                                'event' => $event,
                                'msg' => $msg,
                                'notificacion' => $msg,
                                'emitido' => false,
                            ],
                            'params' => $this->params,
                            'extras' => $extras,
                            'logged_user' => $logged_user,
                        ];
                        return response()->json($response); 
                    } catch (GuzzleException $e) {
                        Log::channel('pusher')->warning("3.- forzar_reload_usuario - Error al desencadenar evento guzzle: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => 'Error, Notificacion NO emitida. Error al desencadenar evento guzzle',
                            'line' => null,
                            'code' => -5,
                            'data' => [
                                'id_usuarios' => [$id_usuario],
                                'channel' => $channel,
                                'event' => $event,
                                'msg' => $msg,
                                'notificacion' => $msg,
                                'emitido' => false,
                            ],
                            'params' => $this->params,
                            'extras' => $extras,
                            'logged_user' => $logged_user,
                        ];
                        return response()->json($response); 
                    } catch (\Exception $e) {
                        Log::channel('pusher')->warning("4.- forzar_reload_usuario - Exception: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => 'Error, Notificacion NO emitida.',
                            'line' => null,
                            'code' => -4,
                            'data' => [
                                'id_usuarios' => [$id_usuario],
                                'channel' => $channel,
                                'event' => $event,
                                'msg' => $msg,
                                'notificacion' => $msg,
                                'emitido' => false,
                            ],
                            'params' => $this->params,
                            'extras' => $extras,
                            'logged_user' => $logged_user,
                        ];
                        return response()->json($response); 
                    }
                }else{
                    $status = 'fail';
                    $errors = ['Parámetros incompletos o incorrectos'];
                    $message = 'Verifique los parámetros. El id del usuario es obligatorio';
                    $code = -3;
                }
                // retorna el response
                $response = [
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
                ];
                return response()->json($response);
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
                    'params' => $this->params,
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
                'params' => $this->params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Fuerza el reload del sistema a todos los usuarios conectados
     * Emite Notificación Pusher
     */
    public function forzar_reload_masivo(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'admin/sistema/forzar-reload-masivo',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'sps' => [],
            'responses' => [],
            'verificado' => []
        ];
        $status = '';
        $message = '';
        $count = -1;
        $code = null;
        $errors = [];
        $params = [];
        $logged_user = null;
        $permiso_requerido = '';
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        date_default_timezone_set('America/Argentina/Cordoba');

        $channel = 'notificaciones-sistema';
        $event = 'Reload';
        $msg = [
            'code' => 102, 
            'id_usuarios' => [],
            'message' => 'Recarga forzada por el administrador', 
            'ambito' => env('ENVIRONMENT')
        ];
        $data = [
            'channel' => $channel,
            'event' => $event,
            'msg' => $msg,
            'notificacion' => $msg,
            'emitido' => false,
        ];

        try {
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $usuarios = ConnectedUser::where('conectado', '=', 1)->get();
                $id_usuarios = [];
                if(!empty($usuarios)){
                    foreach($usuarios as $usuario){
                        if($usuario->conectado == 1 && $usuario->ambiente != 'postman'){
                            if(!empty($id_usuarios)){
                                if(in_array($id_usuarios, $usuario->id)){
                                    array_push($id_usuarios, $usuario->user_id);
                                }
                            }else{
                                array_push($id_usuarios, $usuario->user_id);
                            }
                        }
                    }
                    $msg['id_usuarios'] = $id_usuarios;
                    // emitir pusher
                    try {
                        $clientePusher = new Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), array('cluster' => env('PUSHER_APP_CLUSTER', 'us2')));
                        $status = 'ok';
                        $message = 'Recarga forzada por el administrador';
                        $code = 1;
                        $data = [
                            'id_usuarios' => $id_usuarios,
                            'canal' => $channel,
                            'evento' => $event,
                            'mensaje' => 'Recarga forzada por el administrador',
                            'msg' => $msg,
                            'usuarios' => $usuarios
                        ];
                    } catch (PusherException $e) {
                        Log::channel('pusher')->warning("1.- forzar_reload_masivo - Error al instanciar pusher: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => 'Error, Notificacion NO emitida. Error al instanciar pusher',
                            'line' => null,
                            'code' => -7,
                            'data' => [
                                'channel' => $channel,
                                'event' => $event,
                                'msg' => $msg,
                                'notificacion' => $msg,
                                'emitido' => false,
                            ],
                            'params' => $this->params,
                            'extras' => $this->extras,
                            'logged_user' => $logged_user,
                        ];
                        return response()->json($response); 
                    }
                    
                    try {
                        $clientePusher->trigger($channel, $event, $msg);
                    } catch (PusherException $e) {
                        Log::channel('pusher')->warning("2.- forzar_reload_masivo - Error al desencadenar evento pusher: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => 'Error, Notificacion NO emitida. Error al desencadenar evento pusher',
                            'line' => null,
                            'code' => -6,
                            'data' => [
                                'channel' => $channel,
                                'event' => $event,
                                'msg' => $msg,
                                'notificacion' => $msg,
                                'emitido' => false,
                            ],
                            'params' => $this->params,
                            'extras' => $this->extras,
                            'logged_user' => $logged_user,
                        ];
                        return response()->json($response); 
                    } catch (GuzzleException $e) {
                        Log::channel('pusher')->warning("3.- forzar_reload_masivo - Error al desencadenar evento guzzle: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => 'Error, Notificacion NO emitida. Error al desencadenar evento guzzle',
                            'line' => null,
                            'code' => -5,
                            'data' => [
                                'channel' => $channel,
                                'event' => $event,
                                'msg' => $msg,
                                'notificacion' => $msg,
                                'emitido' => false,
                            ],
                            'params' => $this->params,
                            'extras' => $this->extras,
                            'logged_user' => $logged_user,
                        ];
                        return response()->json($response); 
                    } catch (\Exception $e) {
                        Log::channel('pusher')->warning("4.- forzar_reload_masivo - Exception: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => 'Error, Notificacion NO emitida',
                            'line' => null,
                            'code' => -4,
                            'data' => [
                                'channel' => $channel,
                                'event' => $event,
                                'msg' => $msg,
                                'notificacion' => $msg,
                                'emitido' => false,
                            ],
                            'params' => $this->params,
                            'extras' => $this->extras,
                            'logged_user' => $logged_user,
                        ];
                        return response()->json($response); 
                    }
                }else{
                    $status = 'fail';
                    $errors = ['Sin datos suficientes'];
                    $message = 'No se encontraron usuarios conectados';
                    $code = -3;
                }
                // retorna el response
                $response = [
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => $code,
                    'data' => $data,
                    'params' => $this->params,
                    'extras' => $this->extras,
                    'logged_user' => $logged_user,
                ];
                return response()->json($response);
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
     * Cierra la sesión de un usuario enviando la aplicación al login
     * Emite notificación Pusher
     */
    public function forzar_logout_usuario(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'admin/sistema/forzar-logout-usuario',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'sps' => [],
            'responses' => []
        ];
        $status = '';
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $params = [];
        $permiso_requerido = '';
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        date_default_timezone_set('America/Argentina/Cordoba');
        try {
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $id_usuario = request('id_usuario');
                $params = [
                    'id_usuario' => $id_usuario
                ];

                if($id_usuario != null){
                    
                    $channel = 'notificaciones-sistema';
                    $event = 'Logout';
                    $msg = [
                        'code' => 102, 
                        'id_usuarios' => [$id_usuario],
                        'message' => 'Sesión cerrada por el administrador', 
                        'ambito' => env('ENVIRONMENT')
                    ];
                    $data = [
                        'id_usuarios' => [$id_usuario],
                        'channel' => $channel,
                        'event' => $event,
                        'msg' => $msg,
                        'notificacion' => $msg,
                        'emitido' => false,
                    ];
                    try {
                        $clientePusher = new Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), array('cluster' => env('PUSHER_APP_CLUSTER', 'us2')));
                    } catch (PusherException $e) {
                        Log::channel('pusher')->warning("1.- forzar_logout_usuario - Error al instanciar pusher: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => 'Error, Notificacion NO emitida. Error al instanciar pusher',
                            'line' => null,
                            'code' => -6,
                            'data' => [
                                'id_usuarios' => [$id_usuario],
                                'channel' => $channel,
                                'event' => $event,
                                'msg' => $msg,
                                'notificacion' => $msg,
                                'emitido' => false,
                            ],
                            'params' => $this->params,
                            'extras' => $extras,
                            'logged_user' => $logged_user,
                        ];
                        return response()->json($response); 
                    }
                    try {
                        $clientePusher->trigger($channel, $event, $msg);
                        $status = 'ok';
                        $message = 'Sesión cerrada por el administrador';
                        $code = 1;
                        $data = [
                            'id_usuarios' => [$id_usuario],
                            'canal' => $channel,
                            'evento' => $event,
                            'mensaje' => 'Sesión cerrada por el administrador',
                            'msg' => $msg,
                        ];
                    } catch (PusherException $e) {
                        Log::channel('pusher')->warning("2.- forzar_logout_usuario - Error al desencadenar evento pusher: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => 'Error, Notificacion NO emitida. Error al desencadenar evento pusher',
                            'line' => null,
                            'code' => -4,
                            'data' => [
                                'id_usuarios' => [$id_usuario],
                                'channel' => $channel,
                                'event' => $event,
                                'msg' => $msg,
                                'notificacion' => $msg,
                                'emitido' => false,
                            ],
                            'params' => $this->params,
                            'extras' => $extras,
                            'logged_user' => $logged_user,
                        ];
                        return response()->json($response); 
                    } catch (GuzzleException $e) {
                        Log::channel('pusher')->warning("3.- forzar_logout_usuario - Error al desencadenar evento guzzle: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => 'Error, Notificacion NO emitida. Error al desencadenar evento guzzle',
                            'line' => null,
                            'code' => -5,
                            'data' => [
                                'id_usuarios' => [$id_usuario],
                                'channel' => $channel,
                                'event' => $event,
                                'msg' => $msg,
                                'notificacion' => $msg,
                                'emitido' => false,
                            ],
                            'params' => $this->params,
                            'extras' => $extras,
                            'logged_user' => $logged_user,
                        ];
                        return response()->json($response); 
                    } catch (\Exception $e) {
                        Log::channel('pusher')->warning("4.- forzar_logout_usuario - Exception: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => 'Error, Notificacion NO emitida.',
                            'line' => null,
                            'code' => -4,
                            'data' => [
                                'id_usuarios' => [$id_usuario],
                                'channel' => $channel,
                                'event' => $event,
                                'msg' => $msg,
                                'notificacion' => $msg,
                                'emitido' => false,
                            ],
                            'params' => $this->params,
                            'extras' => $extras,
                            'logged_user' => $logged_user,
                        ];
                        return response()->json($response); 
                    }
                }else{
                    $status = 'fail';
                    $errors = ['Parámetros incompletos o incorrectos'];
                    $message = 'Verifique los parámetros. El id del usuario es obligatorio';
                    $code = -3;
                    $data = null;
                }
                // retorna el response
                $response = [
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
                ];
                return response()->json($response);
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
     * Cierra la sesión de todos los usuarios conectados enviando la aplicación al login
     * Emite notificación Pusher
     */
    public function forzar_logout_masivo(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'admin/sistema/forzar-logout-masivo',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'sps' => [],
            'responses' => []
        ];
        $status = '';
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $this->params = [];
        $permiso_requerido = '';
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        date_default_timezone_set('America/Argentina/Cordoba');
        try {
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $id_usuario = request('id_usuario');
                $this->params = [
                    'id_usuario' => $id_usuario
                ];
                $usuarios = ConnectedUser::where('conectado', '=', 1)->get();
                $id_usuarios = [];
                if(!empty($usuarios)){
                    foreach($usuarios as $usuario){
                        if($usuario->conectado == 1 && $usuario->ambiente != 'postman'){
                            if(!empty($id_usuarios)){
                                if(in_array($id_usuarios, $usuario->id)){
                                    if($usuario->user_id != $id_usuario){
                                        array_push($id_usuarios, $usuario->user_id);
                                    }
                                }
                            }else{
                                if($usuario->user_id != $id_usuario){
                                    array_push($id_usuarios, $usuario->user_id);
                                }
                            }
                        }
                    }
                    $channel = 'notificaciones-sistema';
                    $event = 'Logout';
                    $msg = [
                        'code' => 101, 
                        'id_usuarios' => $id_usuarios,
                        'message' => 'Recarga forzada por el administrador', 
                        'ambito' => env('ENVIRONMENT')
                    ];
                    $data = [
                        'channel' => $channel,
                        'event' => $event,
                        'msg' => $msg,
                        'notificacion' => $msg,
                        'emitido' => false,
                    ];
                    try {
                        $clientePusher = new Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), array('cluster' => env('PUSHER_APP_CLUSTER', 'us2')));
                    } catch (PusherException $e) {
                        Log::channel('pusher')->warning("1.- forzar_logout_masivo - Error al instanciar pusher: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => 'Error, Notificacion NO emitida. Error al instanciar pusher',
                            'line' => null,
                            'code' => -6,
                            'data' => [
                                'id_usuarios' => $id_usuarios,
                                'channel' => $channel,
                                'event' => $event,
                                'msg' => $msg,
                                'notificacion' => $msg,
                                'emitido' => false,
                            ],
                            'params' => $this->params,
                            'extras' => $extras,
                            'logged_user' => $logged_user,
                        ];
                        return response()->json($response); 
                    }
                    
                    try {
                        $clientePusher->trigger($channel, $event, $msg);
                        $status = 'ok';
                        $message = sizeof($id_usuarios).' Sesiones cerradas por el administrador';
                        $code = 1;
                        $data = [
                            'id_usuarios' => $id_usuarios,
                            'channel' => $channel,
                            'event' => $event,
                            'msg' => $msg,
                            'notificacion' => $msg,
                            'emitido' => true,
                        ];
                    } catch (PusherException $e) {
                        Log::channel('pusher')->warning("2.- forzar_logout_masivo - Error al desencadenar evento pusher: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => 'Error, Notificacion NO emitida. Error al desencadenar evento pusher',
                            'line' => null,
                            'code' => -6,
                            'data' => [
                                'id_usuarios' => $id_usuarios,
                                'channel' => $channel,
                                'event' => $event,
                                'msg' => $msg,
                                'notificacion' => $msg,
                                'emitido' => false,
                            ],
                            'params' => $this->params,
                            'extras' => $extras,
                            'logged_user' => $logged_user,
                        ];
                        return response()->json($response); 
                    } catch (GuzzleException $e) {
                        Log::channel('pusher')->warning("3.- forzar_logout_masivo - Error al desencadenar evento guzzle: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => 'Error, Notificacion NO emitida. Error al desencadenar evento guzzle',
                            'line' => null,
                            'code' => -6,
                            'data' => [
                                'id_usuarios' => $id_usuarios,
                                'channel' => $channel,
                                'event' => $event,
                                'msg' => $msg,
                                'notificacion' => $msg,
                                'emitido' => false,
                            ],
                            'params' => $this->params,
                            'extras' => $extras,
                            'logged_user' => $logged_user,
                        ];
                        return response()->json($response); 
                    } catch (\Exception $e) {
                        Log::channel('pusher')->warning("4.- forzar_logout_masivo - Exception: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => 'Error, Notificacion NO emitida.',
                            'line' => null,
                            'code' => -6,
                            'data' => [
                                'id_usuarios' => $id_usuarios,
                                'channel' => $channel,
                                'event' => $event,
                                'msg' => $msg,
                                'notificacion' => $msg,
                                'emitido' => false,
                            ],
                            'params' => $this->params,
                            'extras' => $extras,
                            'logged_user' => $logged_user,
                        ];
                        return response()->json($response); 
                    } 
                }else{
                    $status = 'fail';
                    $errors = ['Sin datos suficientes'];
                    $message = 'No se encontraron usuarios conectados';
                    $code = -3;
                    $data = null;
                }
                // retorna el response
                $response = [
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
                ];
                return response()->json($response);
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
                    'params' => $this->params,
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
                'params' => $this->params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Desconecta un usuario conectado
     * Solo coloca el campo conectado en false, forzado en true y la fecha de fin de sesión
     * Emite Notificación Pusher
     */
    public function desconectar_usuario(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'admin/sistema/desconectar-usuario',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'sps' => [],
            'responses' => [],
            'verificado' => []
        ];
        $status = '';
        $message = '';
        $count = -1;
        $params = [];
        $code = null;
        $data = null;
        $errors = [];
        $logged_user = null;
        $permiso_requerido = '';
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        date_default_timezone_set('America/Argentina/Cordoba');
        try {
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $id_sesion = request('id_sesion');
                $params = [
                    'id_sesion' => $id_sesion
                ];
                $usuario = ConnectedUser::find($id_sesion);
                if($usuario){
                    $usuario->conectado = false;
                    $usuario->fin_sesion = Carbon::now();
                    $usuario->forzado = true;
                    $usuario->save();
                    $status = 'ok';
                    $message = 'Sesión cerrada';
                    $code = 1;

                    $channel = 'notificaciones-sistema';
                    $event = 'Logout';
                    $msg = [
                        'usuarios' => [$usuario->id],
                        'code' => 110, 
                        'message' => 'Sesión cerrada por el administrador', 
                        'ambito' => env('ENVIRONMENT')
                    ];

                    $data = [
                        'conexion' => $usuario,
                        'channel' => $channel,
                        'event' => $event,
                        'msg' => $msg,
                        'notificacion' => $msg,
                        'emitido' => false,
                    ];

                    if($usuario->token != null){
                        $token = PersonalAccessToken::find($usuario->token_id);
                        $token->revoke();

                        // emitir pusher
                        try {
                            $clientePusher = new Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), array('cluster' => env('PUSHER_APP_CLUSTER', 'us2')));
                        } catch (PusherException $e) {
                            Log::channel('pusher')->warning("1.- desconectar_usuario - Error al instanciar pusher: " . $e->getMessage());
                            array_push($errors, $e->getMessage());
                            $response = [
                                'status' => 'fail',
                                'count' => 0,
                                'errors' => $errors,
                                'message' => 'Error, Notificacion NO emitida. Error al instanciar pusher',
                                'line' => null,
                                'code' => -7,
                                'data' => [
                                    'channel' => $channel,
                                    'event' => $event,
                                    'msg' => $msg,
                                    'notificacion' => $msg,
                                    'emitido' => false,
                                ],
                                'params' => $params,
                                'extras' => $extras,
                                'logged_user' => $logged_user,
                            ];
                            return response()->json($response); 
                        }
                        try {
                            $clientePusher->trigger($channel, $event, $msg);
                            $response = [
                                'status' => 'ok',
                                'count' => 1,
                                'errors' => $errors,
                                'message' => 'Notificacion emitida',
                                'line' => null,
                                'code' => 1,
                                'data' => [
                                    'conexion' => $usuario,
                                    'channel' => $channel,
                                    'event' => $event,
                                    'msg' => $msg,
                                    'notificacion' => $msg,
                                    'emitido' => true,
                                ],
                                'params' => $params,
                                'extras' => $extras,
                                'logged_user' => $logged_user,
                            ];
                            return response()->json($response);
                        } catch (PusherException $e) {
                            Log::channel('pusher')->warning("2.- desconectar_usuario - Error al desencadenar evento pusher: " . $e->getMessage());
                            array_push($errors, $e->getMessage());
                            $response = [
                                'status' => 'fail',
                                'count' => 0,
                                'errors' => $errors,
                                'message' => 'Error, Notificacion NO emitida. Error al desencadenar evento pusher',
                                'line' => null,
                                'code' => -6,
                                'data' => [
                                    'conexion' => $usuario,
                                    'channel' => $channel,
                                    'event' => $event,
                                    'msg' => $msg,
                                    'notificacion' => $msg,
                                    'emitido' => false,
                                ],
                                'params' => $params,
                                'extras' => $extras,
                                'logged_user' => $logged_user,
                            ];
                            return response()->json($response); 
                        } catch (GuzzleException $e) {
                            Log::channel('pusher')->warning("3.- desconectar_usuario - Error al desencadenar evento guzzle: " . $e->getMessage());
                            array_push($errors, $e->getMessage());
                            $response = [
                                'status' => 'fail',
                                'count' => 0,
                                'errors' => $errors,
                                'message' => 'Error, Notificacion NO emitida. Error al desencadenar evento guzzle',
                                'line' => null,
                                'code' => -5,
                                'data' => [
                                    'conexion' => $usuario,
                                    'channel' => $channel,
                                    'event' => $event,
                                    'msg' => $msg,
                                    'notificacion' => $msg,
                                    'emitido' => false,
                                ],
                                'params' => $params,
                                'extras' => $extras,
                                'logged_user' => $logged_user,
                            ];
                            return response()->json($response); 
                        } catch (\Exception $e) {
                            Log::channel('pusher')->warning("4.- desconectar_usuario - Exception: " . $e->getMessage());
                            array_push($errors, $e->getMessage());
                            $response = [
                                'status' => 'fail',
                                'count' => 0,
                                'errors' => $errors,
                                'message' => 'Error, Notificacion NO emitida',
                                'line' => null,
                                'code' => -4,
                                'data' => [
                                    'conexion' => $usuario,
                                    'channel' => $channel,
                                    'event' => $event,
                                    'msg' => $msg,
                                    'notificacion' => $msg,
                                    'emitido' => false,
                                ],
                                'params' => $params,
                                'extras' => $extras,
                                'logged_user' => $logged_user,
                            ];
                            return response()->json($response); 
                        }
                    }
                }else{
                    $status = 'fail';
                    $message = 'No se encontró la sesión';
                    $code = -3;
                }
                // retorna el response
                $response = [
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
                ];
                return response()->json($response); 
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($this->permiso_requerido);
                $count  = -1;
                $data = null;
                array_push($errors, 'Error de permisos. '.$message);
                // retorna el response
                $response = [
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
                ];
                return response()->json($response);
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' de SistemaController.'.' Error: '.$th->getMessage());
            $response = [
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
            ];
            return response()->json($response);
        }
    }

     /**
     * Desconecta todos los usuarios conectados con fechas anteriores a hoy
     * Solo coloca el campo conectado en false, forzado en true y la fecha de fin de sesión
     * NO emite notificación Pusher
     */
    public function desconectar_masivo(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'admin/sistema/desconectar-masivo',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'sps' => [],
            'responses' => [],
            'verificado' => []
        ];
        $status = '';
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $params = [];
        $permiso_requerido = '';
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        date_default_timezone_set('America/Argentina/Cordoba');
        try {
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $usuarios = ConnectedUser::where('conectado', '=', 1)->get();
                if(!empty($usuarios)){
                    foreach($usuarios as $usuario){
                        $fecha = Carbon::parse($usuario->inicio_sesion);
                        $hoy = Carbon::now();
                        $hoy->startOfDay();
                        if($fecha < $hoy && $usuario->ambiente != 'postman'){
                            $usuario->conectado = false;
                            $usuario->fin_sesion = Carbon::now();
                            $usuario->forzado = true;
                            $usuario->save();
                            if($usuario->token != null){
                                $token = PersonalAccessToken::find($usuario->token_id);
                                $token->revoke();
                            }
                        }
                    }
                    $status = 'ok';
                    $message = 'Conexiones cerradas';
                    $code = 1;
                    $data = ConnectedUser::where('conectado', '=', 1)->get();
                }else{
                    $status = 'fail';
                    $message = 'No se encontraron usuarios conectados';
                    $code = -1;
                }
                // retorna el response
                $response = [
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
                ];
                return response()->json($response); 
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($this->permiso_requerido);
                $count  = -1;
                $data = null;
                array_push($errors, 'Error de permisos. '.$message);
                // retorna el response
                $response = [
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
                ];
                return response()->json($response);
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' de SistemaController.'.' Error: '.$th->getMessage());
            $response = [
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
            ];
            return response()->json($response);
        }
    }

    /**
     * Retorna una lista de usuarios conectados
     * No emite notificación pusher
     */
    public function listar_usuarios_conectados(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'admin/sistema/listar-usuarios-conectados',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'sps' => [],
            'responses' => [],
            'verificado' => []
        ];
        $params = [];
        $status = '';
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $permiso_requerido = '';
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        try {
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                // $usuarios = ConnectedUser::where('conectado', true)->get();
                $inicio = request('inicio');
                $fin = request('fin');
                $fecha_inicio = Carbon::parse($inicio)->format('Y-m-d H:i:s');
                $fecha_fin = Carbon::parse($fin)->format('Y-m-d H:i:s');
                $fecha = request('fecha');  // inicio_sesion o fin_sesion
                $params = [
                    'inicio' => $inicio,
                    'fin' => $fin,
                    'fecha' => $fecha
                ];
                
                if($fecha_inicio && $fecha_fin && $fecha){
                    $usuarios = ConnectedUser::with(['user', 'user.roles'])->get();
                    $usuarios_filtrados = [];
                    foreach ($usuarios as $usuario) {
                        // Verificar que el usuario existe antes de acceder a sus propiedades
                        if ($usuario->user) {
                            $nombreUsuario = $usuario->user->name; // Nombre del usuario
                            $rolesUsuario = $usuario->user->roles; // Colección de roles
                            $primerRol = $usuario->user->roles->first() ? $usuario->user->roles->first()->name : 'Sin rol'; // Nombre del primer rol
                        }
                        // return [
                        //     'usuario' => $usuario->inicio_sesion,
                        //     'fecha_inicio' => $fecha_inicio,
                        //     'fecha_inicio' => $fecha_fin,
                        //     'comparacion_inicio' => $usuario->inicio_sesion > $fecha_inicio,
                        //     'comparacion_fin' => $usuario->inicio_sesion < $fecha_fin,
                        // ];
                        $usuario->inicio_sesion = $usuario->inicio_sesion ? Carbon::parse($usuario->inicio_sesion)->format('Y-m-d H:i:s') : null;
                        $usuario->fin_sesion = $usuario->fin_sesion ? Carbon::parse($usuario->fin_sesion)->format('Y-m-d H:i:s') : null;
                        
                        if($usuario->inicio_sesion && $usuario->inicio_sesion > $fecha_inicio && $usuario->inicio_sesion < $fecha_fin){
                            array_push($usuarios_filtrados, $usuario);
                        }
                    }
                    $usuarios = $usuarios_filtrados;
                } else {
                    $usuarios = ConnectedUser::with(['user', 'user.roles'])->where('conectado', true)->get();
                    $usuarios_filtrados = [];
                    foreach ($usuarios as $usuario) {
                        // Verificar que el usuario existe antes de acceder a sus propiedades
                        if ($usuario->user) {
                            $nombreUsuario = $usuario->user->name; // Nombre del usuario
                            $rolesUsuario = $usuario->user->roles; // Colección de roles
                            $primerRol = $usuario->user->roles->first() ? $usuario->user->roles->first()->name : 'Sin rol'; // Nombre del primer rol
                        }
                        $usuario->inicio_sesion = $usuario->inicio_sesion ? Carbon::parse($usuario->inicio_sesion)->format('Y-m-d H:i:s') : null;
                        array_push($usuarios_filtrados, $usuario);
                    }
                    $usuarios = $usuarios_filtrados;
                }

                if(!empty($usuarios)){
                    $data = $usuarios;
                    $count = sizeof($usuarios);
                    $status = 'ok';
                    $message = 'Sesiones encontradas';
                    $code = 1;
                }else{
                    $data = [];
                    $count = 0;
                    $status = 'empty';
                    $message = 'No hay usuarios conectados';
                    $code = 1;
                }
                // retorna el response
                $response = [
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
                ];
                return response()->json($response); 
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($this->permiso_requerido);
                $count  = -1;
                $data = null;
                array_push($errors, 'Error de permisos. '.$message);
                // retorna el response
                $response = [
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
                ];
                return response()->json($response); 
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' de SistemaController.'.' Error: '.$th->getMessage());
            $response = [
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
            ];
            return response()->json($response);
        }
    }

    /**
     * Obtiene datos para estadística de cantidad de conexiones realizadas por día
     * No emite notificación pusher
     */
    public function conexiones_realizadas(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'admin/sistema/conexiones-realizadas',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__)) - 1],
            'function' => __FUNCTION__,
            'queries' => [],
            'sps' => [],
            'responses' => []
        ];
        $status = '';
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $params = [];
        $permiso_requerido = '';
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        date_default_timezone_set('America/Argentina/Cordoba');
        try {
            if ($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)) {
                
                $fecha_inicio = Carbon::parse(request('fecha_inicio'));
                $fecha_fin = Carbon::parse(request('fecha_fin'));

                $params = [
                    'fecha_inicio' => $fecha_inicio,
                    'fecha_fin' => $fecha_fin
                ];
                if ($fecha_inicio != null && $fecha_fin != null) {
                    $conexiones = ConnectedUser::selectRaw('DATE(inicio_sesion) as fecha, user_id, COUNT(*) as cantidad_conexiones')
                        ->whereBetween('inicio_sesion', [$fecha_inicio, $fecha_fin])
                        ->groupBy(DB::raw('DATE(inicio_sesion)'), 'user_id')
                        ->orderBy('fecha')
                        ->orderBy('user_id')
                        ->get();
            
                    $status = 'ok';
                    $message = 'Estadísticas obtenidas';
                    $code = 1;
                    $data = $conexiones;
                    $count = $conexiones->count();
                } else {
                    $status = 'fail';
                    $errors = ['Parámetros incompletos o incorrectos'];
                    $message = 'Verifique los parámetros. La fecha de inicio y fin es obligatoriaes obligatoria';
                    $code = -3;
                    $data = null;
                }
                // retorna el response
                $response = [
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
                ];
                return response()->json($response);
            } else {
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol ' . strtoupper($user->roles[0]->name) . ' no tiene permiso. Se requiere permiso para ' . strtoupper($permiso_requerido);
                $count = -1;
                $data = null;
                array_push($errors, 'Error de permisos. ' . $message);
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
            array_push($errors, 'Line: ' . $th->getLine() . ' Code: ' . $th->getCode() . ' Error: ' . $th->getMessage());
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