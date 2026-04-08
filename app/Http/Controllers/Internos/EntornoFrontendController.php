<?php

namespace App\Http\Controllers\Internos;

use Illuminate\Http\Request;
use Illuminate\Http\Response;


use App\Http\Controllers\ConexionSpController;
use App\Models\EntornoFrontend;

use App\Models\User;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;
use Pusher\ApiErrorException;
use Pusher\Pusher;
use Pusher\PusherException;

class EntornoFrontendController extends ConexionSpController
{

    public function cargar_entorno(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'admin/sistema/entorno-frontend/cargar-entorno',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'responses' => [],
        ];
        $status = 'fail'; // 'ok', 'fail', 'empty', unauthorized', 'warning'  
        $message = '';
        $count = 0;
        $code = 0;
        $data = null;
        $errors = [];
        $params = [];
    
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

        $data = EntornoFrontend::where('entorno', config('site.entorno_frontend'))->first();
       

        if($data){
            $status = 'ok';
            $message = 'Entorno cargado correctamente.';
            $count = 1;
            $code = 1;
        }else{
            $status = 'empty';
            $code = -1;
            $message = 'No se encontró el entorno.';
            $errors = ['No se encontró el entorno.'];
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
    }

    /**
     * 
     */
    public function actualizar_entorno(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'admin/sistema/entorno-frontend/cargar-entorno',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'responses' => [],
        ];
        $status = 'fail'; // 'ok', 'fail', 'empty', unauthorized', 'warning'  
        $message = '';
        $count = 0;
        $code = 0;
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
            if($user->hasRole('super administrador')){
                $entorno = request('entorno');
                $entorno['imprimir_validaciones_rechazadas'] = $entorno['imprimir_validaciones_rechazadas'] ? 1 : 0;
                $entorno['mostrar_mensajes_conexion_pusher'] = $entorno['mostrar_mensajes_conexion_pusher'] ? 1 : 0;
                
                $params = [
                    'entorno' => $entorno
                ];
                // actualizar las variables de entorno en la base de datos
                $entorno_frontend = EntornoFrontend::where('entorno', config('site.entorno_frontend'))->first();
                $entorno_frontend['ambiente'] = $entorno['ambiente'];
                $entorno_frontend['modelo_formulario_cronicos'] = $entorno['modelo_formulario_cronicos'];
                $entorno_frontend['imprimir_validaciones_rechazadas'] = $entorno['imprimir_validaciones_rechazadas'];
                $entorno_frontend['empresa'] = $entorno['empresa'];
                $entorno_frontend['mostrarPdf'] = $entorno['mostrarPdf'];
                $entorno_frontend['ambiente_recipe'] = $entorno['ambiente_recipe'];
                $entorno_frontend['restringir_postdatar'] = $entorno['restringir_postdatar'];
                $entorno_frontend['restringir_postdatar_prescripcion'] = $entorno['restringir_postdatar_prescripcion'];
                $entorno_frontend['consultar_vademecum'] = $entorno['consultar_vademecum'];
                $entorno_frontend['cantidad_prescripciones_por_receta'] = $entorno['cantidad_prescripciones_por_receta'];
                $entorno_frontend['mostrarSpinnerGlobal'] = $entorno['mostrarSpinnerGlobal'];
                $entorno_frontend['mostrar_mensajes_conexion_pusher'] = $entorno['mostrar_mensajes_conexion_pusher'];
                $entorno_frontend['ambiente_padron_externo'] = $entorno['ambiente_padron_externo'];
                $entorno_frontend['consultar_padron_externo'] = $entorno['consultar_padron_externo'];
                $entorno_frontend['mostrar_consultar_padron_externo'] = $entorno['mostrar_consultar_padron_externo'];
                $entorno_frontend['actualizar_afiliado_desde_padron_externo'] = $entorno['actualizar_afiliado_desde_padron_externo'];
                $entorno_frontend['mostrar_boton_buscar_afiliado_en_padron_externo'] = $entorno['mostrar_boton_buscar_afiliado_en_padron_externo'];
                $guardado = $entorno_frontend->save();
                if($guardado){
                    $status = 'ok';
                    $message = 'Entorno actualizado correctamente.';
                    $count = 1;
                    $code = 1;
                    // disparar evento Pusher ConfiguracionActualizada al canal configuracion-actualizada con { ambito: 'osceara' }
                    $channel = "configuracion-actualizada";
                    $event = "ConfiguracionActualizada";
                    $msg = [
                        'code' => 110, 
                        'message' => 'Cambio en la configuración del entorno. Se recomienda recargar la aplicación para aplicar los cambios.',
                        'ambito' => env('ENVIRONMENT')
                    ];  // message

                    try {
                        $clientePusher = new Pusher(env('PUSHER_APP_KEY', ''), env('PUSHER_APP_SECRET', ''), env('PUSHER_APP_ID', ''), array('cluster' => env('PUSHER_APP_CLUSTER', 'us2')));
                    } catch (PusherException $e) {
                        Log::channel('pusher')->warning("1.- configuracion-actualizada - Error al instanciar pusher: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => $message.' Notificación NO emitida. Error al instanciar pusher',
                            'line' => null,
                            'code' => -6,
                            'data' => [
                                'entorno_guardado' => $guardado,
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
                            'message' => $message.' Notificacion emitida con éxito',
                            'line' => null,
                            'code' => 1,
                            'data' => [
                                'entorno_guardado' => $guardado,
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
                        Log::channel('pusher')->warning("2.- configuracion-actualizada - Error al desencadenar evento pusher: " . $e->getMessage());
                        array_push($errors, $e->getMessage());
                        $response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => $message.' Notificación NO emitida',
                            'line' => null,
                            'code' => -5,
                            'data' => [
                                'entorno_guardado' => $guardado,
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
                            'message' => $message.' Notificación NO emitida',
                            'line' => null,
                            'code' => -4,
                            'data' => [
                                'entorno_guardado' => $guardado,
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
                            'message' => $message.' Notificación NO emitida',
                            'line' => null,
                            'code' => -3,
                            'data' => [
                                'entorno_guardado' => $guardado,
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
                    $status = 'fail';
                    $message = 'No se pudo actualizar el entorno. Notificación NO emitida.';
                    $count = -1;
                    $code = -1;
                    $data = [
                        'entorno_guardado' => $guardado,
                        'emitido' => false
                    ];
                    array_push($errors, 'No se pudo actualizar el entorno.');  
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
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no puede ejecutar esta acción';
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