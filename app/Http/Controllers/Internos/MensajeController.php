<?php

namespace App\Http\Controllers\Internos;

use App\Http\Controllers\Controller;
// use App\Http\Controllers\Internos\General\PusherController;
// use App\Services\PusherService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Pusher\ApiErrorException;
use Pusher\Pusher;
use Pusher\PusherException;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
// use Illuminate\Support\Facades\File as FacadeFile;
use Illuminate\Support\Facades\Response as FacadeResponse;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\User;
use App\Models\Mensaje;

use File;
use Storage;
use Carbon\Carbon;
use DB;

class MensajeController extends Controller
{

    public function enviar_mensaje(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/mensajes/enviar-mensaje',
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
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $destinatarios = request('destinatarios');
            $m = request('mensaje');

            $params = [
                'mensaje' => $m,
                'destinatarios' => $destinatarios
            ];
            try {
                $mensaje = new Mensaje;
                $mensaje->adjunto = $m['adjunto'];
                $mensaje->animacion = $m['animacion'];
                $mensaje->archivos = $m['archivos'];
                $mensaje->asunto = $m['asunto'];
                $mensaje->destinatarios = $m['destinatarios'];
                $mensaje->fecha_envio = Carbon::now();
                $mensaje->id_destinatarios = $m['id_destinatarios'];
                $mensaje->imagen = $m['imagen'];
                $mensaje->importancia = $m['importancia'];
                $mensaje->nombre_destinatarios = $m['nombre_destinatarios'];
                $mensaje->prioridad = $m['prioridad'];
                $mensaje->query_params = $m['query_params'];
                $mensaje->remitente = $m['remitente'];
                $mensaje->rich_text = $m['rich_text'];
                $mensaje->router_link = $m['router_link'];
                $mensaje->texto_enlace = $m['texto_enlace'];
                $mensaje->texto = $m['texto'];
                $mensaje->tipo = $m['tipo'];
                $mensaje->urgencia = $m['urgencia'];
                $mensaje->user_id = $m['user_id'];
                $mensaje->video = $m['video'];
                $mensaje->save();
            } catch (\Exception $e) {
                return $e;
            }

            if($mensaje){
                $mensaje->users()->attach($destinatarios);
                $data = [
                    'mensaje' => $mensaje
                ];
                $status = 'ok';
                $count = sizeof($destinatarios);
                $code = 1;
                $message = 'Mensaje guardado con éxito';
                
                $title = $mensaje['asunto'];
                $destinatarios = request('destinatarios');
                try {
                    $clientePusher = new Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), array('cluster' => env('PUSHER_APP_CLUSTER', 'us2')));
                } catch (PusherException $e) {
                    Log::warning("Error al instanciar pusher: " . $e->getMessage());
                    array_push($errors, $e->getMessage());
                }
                
                $canal = "nuevo-mensaje";
                $evento = "NuevoMensaje";
                try {
                    foreach ($destinatarios as $key => $id_usuario) {
                        $notificacion = [
                            'id_usuario' => $id_usuario, 
                            'code' => 110, 
                            'message' => 'Nuevo Mensaje: '.$mensaje.', recibido de: '.$mensaje->remitente,
                            'ambito' => env('ENVIRONMENT')
                        ];
                        $clientePusher->trigger($canal, $evento, $notificacion);
                    }
                    $data = [
                        'mensaje' => $mensaje,
                        'pusher' => [
                            'channel' => $canal,
                            'event' => $evento,
                            'notificacion' => $notificacion,
                            'usuarios_notificados' => $destinatarios
                        ]
                    ];
                } catch (PusherException $e) {
                    Log::warning("Error al desencadenar evento pusher: " . $e->getMessage());
                    array_push($errors, $e->getMessage());
                } catch (GuzzleException $e) {
                    Log::warning("Error al desencadenar evento guzzle: " . $e->getMessage());
                    array_push($errors, $e->getMessage());
                }
                
            }else{
                $data = null;
                $status = 'fail';
                $count = 0;
                $code = -2;
                $message = 'No se pudo guradar el mensaje';
                $errors = ['Error al guardar el mensaje'];
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
                'code' => -1,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    public function listar_mensajes(Request $request)
    {
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/mensajes/listar-mensajes',
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
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $paginacion = request('paginacion');

            $params = [
                'paginacion' => $paginacion
            ];

            $enviados = [];
            $recibidos = [];
            if($user->es_super_admin()){
                if(request('usuario') != null){
                    try {
                        $enviados = Mensaje::where('user_id', $user['id'])
                                            ->orderBy('mensajes.fecha_envio', 'desc')
                                            // ->paginate($paginacion);
                                            ->get();
                    } catch (\Throwable $th) {
                        array_push($errors, $th->getMessage());
                    }
                }else{
                    try {
                        $enviados = Mensaje::orderBy('mensajes.fecha_envio', 'desc')
                                            // ->paginate($paginacion);
                                            ->get();
                    } catch (\Throwable $th) {
                        array_push($errors, $th->getMessage());
                    }
                }
            }else{
                try {
                    $enviados = Mensaje::where('user_id', $user['id'])
                                        ->orderBy('mensajes.fecha_envio', 'desc')
                                        // ->paginate($paginacion);
                                        ->get();
                } catch (\Throwable $th) {
                    array_push($errors, $th->getMessage());
                }
            }
            try {
                $recibidos = DB::table('mensajes')
                                ->join('mensaje_user', 'mensaje_user.mensaje_id', '=', 'mensajes.id')
                                ->select('mensajes.*', 'mensaje_user.user_id', 'mensaje_user.leido', 'mensaje_user.ejecutado', 'mensaje_user.mostrado')
                                ->where('mensaje_user.user_id', '=', $user['id'])
                                ->orderBy('mensajes.fecha_envio', 'desc')
                                // ->paginate($paginacion);
                                ->get();
                    
            } catch (\Throwable $th) {
                array_push($errors, $th->getMessage());
            }
            
            $data = [
                'enviados' => $enviados,
                'recibidos' => $recibidos
            ];
            
            if(sizeof($errors) == 0){
                $status = 'ok';
                $code = 1;
                $message = 'Mensajes obtenidos';
            }else{
                $status = 'fail';
                $code = -2;
                $message = 'Fallo al buscar mensajes';
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
                'code' => -1,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    public function marcar_mensaje_como(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/mensajes/marcar-mensaje-como',
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
        $params = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $id_usuario = request('id_usuario');
            $id_mensaje = request('id_mensaje');
            $campo = request('campo'); // l
            $params = [
                'id_usuario' => $id_usuario,
                'id_mensaje' => $id_mensaje,
                "campo" => $campo
            ];
            // DB::insert('insert into users (id, name) values (?, ?)', [1, 'Dayle']);
            // DB::table('users')->insert(['email' => 'john@example.com', 'votes' => 0]);
            try {
                $mensaje = DB::table('mensaje_user')
                    ->select('*')
                    ->where('user_id', '=', $id_usuario)
                    ->where('mensaje_id', '=', $id_mensaje)
                    ->get();
                $men = (array) $mensaje[0];
                $extras['responses'] = $mensaje;
                if($men[$campo] == null){
                    $mensaje = DB::table('mensaje_user')
                            ->select('*')
                            ->where('user_id', '=', $id_usuario)
                            ->where('mensaje_id', '=', $id_mensaje)
                            ->update([$campo => Carbon::now()->format('Y-m-d H:i:s')]);
                    if($mensaje){
                        $data = Carbon::now()->format('Y-m-d H:i:s');
                        $message = 'Datos actualizados';
                        $code = 1;
                    }
                }else{
                    $data = $men[$campo];
                    $message = 'El campo ya está marcado';
                    $code = 2;
                }
                $status = 'ok';
            } catch (\Throwable $th) {
                array_push($errors, $th->getMessage());
                $data = null;
                $status = 'fail';
                $message = 'Datos NO actualizados';
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
                'code' => -1,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    public function buscar_mensajes_no_leidos(Request $request)
    {
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/mensajes/buscar-mensajes-no-leidos',
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
        $params = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        try {
            date_default_timezone_set('America/Argentina/Cordoba');

            $recibidos = DB::table('mensajes')
                            ->join('mensaje_user', 'mensaje_user.mensaje_id', '=', 'mensajes.id')
                            ->select('mensajes.*', 'mensaje_user.user_id', 'mensaje_user.leido', 'mensaje_user.ejecutado', 'mensaje_user.mostrado')
                            ->where('mensaje_user.user_id', '=', $user['id'])
                            ->where('mensaje_user.leido', '=', null)
                            ->orderBy('mensajes.fecha_envio', 'desc')
                            ->get();
                            
            $count = sizeof($recibidos);
            $data = $recibidos;
            if($count > 0){
                $status = 'ok';
                $code = 1;
                $message = 'Tiene '.$count.' mensajes sin leer';
            }else{
                $status = 'empty';
                $code = 0;
                $message = 'No tiene mensajes sin leer';
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
                'code' => -1,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    public function buscar_estado_mensaje(Request $request)
    {
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/mensajes/buscar-estado-mensaje',
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
        $params = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $id_mensaje = request('id_mensaje');
            $params = [
                'id_mensaje' => $id_mensaje
            ];

            try {
                $mensajes = DB::table('mensajes')
                                ->join('mensaje_user', 'mensaje_user.mensaje_id', '=', 'mensajes.id')
                                ->join('users', 'mensaje_user.user_id', '=', 'users.id')
                                ->select('mensajes.id', 'mensajes.asunto', 'mensaje_user.user_id', 'users.name', 'mensaje_user.leido', 'mensaje_user.mostrado', 'mensaje_user.ejecutado')
                                ->where('mensaje_user.mensaje_id', '=', $id_mensaje)
                                ->orderBy('leido', 'desc')
                                ->get();

                $leidos = 0;
                $mostrados = 0;
                $ejecutados = 0;
                $estados = [];
                $asunto = $mensajes[0]->asunto;
                foreach ($mensajes as $mensaje) {
                    if($mensaje->leido != null){
                        $leidos++;
                    }
                    if($mensaje->mostrado != null){
                        $mostrados++;
                    }
                    if($mensaje->ejecutado != null){
                        $ejecutados++;
                    }
                    array_push($estados, [
                        'user_id' => $mensaje->user_id,
                        'name' => $mensaje->name,
                        'leido' => $mensaje->leido,
                        'mostrado' => $mensaje->mostrado,
                        'ejecutado' => $mensaje->ejecutado,
                    ]);
                }
            } catch (\Exception $e) {
                return $e;
            }
                            
            $count = sizeof($mensajes);
            $data = [
                'leidos' => $leidos,
                'mostrados' => $mostrados,
                'ejecutados' => $ejecutados,
                'enviados' => $count,
                'id' => $id_mensaje,
                'asunto' => $asunto,
                'estados' => $estados,
            ];
            if($count > 0){
                $status = 'ok';
                $code = 1;
                $message = 'Mensaje encontrado';
            }else{
                $status = 'empty';
                $code = 0;
                $message = 'Mensaje NO encontrado';
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
                'code' => -1,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }
            
}