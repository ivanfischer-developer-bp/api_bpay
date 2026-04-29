<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Models\User;

// use App\Services\PusherService;
use GuzzleHttp\Exception\GuzzleException;
use Pusher\ApiErrorException;
use Pusher\Pusher;
use Pusher\PusherException;

use Carbon\Carbon;
use DB;

use App\Http\Controllers\ConexionSpController;

class PortalPusherController extends ConexionSpController
{
    public function emitir_aviso_cotizacion(Request $request)
    {
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/portal/emision-pusher/emitir-aviso-cotizacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];

        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;
            $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

            // ejecutar notificación a usuarios mediante pusher
            $errors = [];
            $message = '';
            $line = null;
            $code = 0;
            $params = [
                'codigo_interno' => request('codigo_interno'),
                'mensaje' => request('mensaje')
            ];
            $params_notificar = [
                'p_id_contrato' => 3,  // validaciones es el id_contrato 3.    Contratos: 1.-Afiliaciones, 2.-Expedientes, 3.-Validaciones
                'p_codigo_interno' => request('codigo_interno')
            ];
            $sp = 'sp_contrato_usuario_rol_select';
            $db = 'admin';
            array_push($extras['sps'], [$sp => $params_notificar]);
            array_push($extras['queries'], $this->get_query($db, $sp, $params_notificar));
            $usuarios_notificar = $this->ejecutar_sp_directo($db, $sp, $params_notificar);
            array_push($extras['responses'], [$sp => $usuarios_notificar]);
            // return $params_sp;

            $id_usuarios = [];
            if (count($usuarios_notificar) > 0) {
                try {
                    $clientePusher = new Pusher(env('PUSHER_APP_KEY', ''), env('PUSHER_APP_SECRET', ''), env('PUSHER_APP_ID', ''), array('cluster' => env('PUSHER_APP_CLUSTER', 'us2')));
                } catch (PusherException $e) {
                    Log::channel('pusher')->error("Portal::emitir-aviso-cotizacion-> Error al instanciar pusher: " . $e->getMessage());
                    $status = 'fail';
                    $count = count($id_usuarios);
                    $message = 'Error al instanciar pusher';
                    $code = -5;
                    $data = [
                        'message' => $message,
                        'usuarios_notificados' => $usuarios_notificar,
                        'id_contrato' => 3, // validaciones es el id_contrato 3.      Contratos: 1.-Afiliaciones, 2.-Expedientes, 3.-Validaciones
                        'codigo_interno' => $response[0]->codigo_interno,
                        'channel' => 'aviso-cotizacion-channel',
                        'event' => 'AvisoCotizacionEvent',
                        'msg' => null
                    ];
                    return [
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
                }

                foreach ($usuarios_notificar as $usuario) {
                    // Convertir a objeto si es un array
                    if (is_array($usuario)) {
                        $usuario = (object) $usuario;
                    }
                    
                    // Validar que el objeto usuario tenga las propiedades necesarias
                    if (!is_object($usuario) || !isset($usuario->id_usuario)) {
                        Log::channel('pusher')->error('Usuario sin propiedades válidas en notificación: ' . json_encode($usuario));
                        continue;
                    }
                    
                    if ($id_usuario !== $usuario->id_usuario) {
                        array_push($id_usuarios, $usuario->id_usuario);
                    }
                }
            }

            $channel = 'aviso-cotizacion-channel';
            $event = 'AvisoCotizacionEvent';
            $msg = [
                'id_usuarios' => $id_usuarios, // $usuario['id_usuario'], //Session::get('usuario.id_usuario'), 
                'code' => 200,
                'message' => $params['mensaje'],
                'ambito' => env('ENVIRONMENT')
            ];

            if (count($usuarios_notificar) > 0) {
                try {
                    $clientePusher->trigger($channel, $event, $msg);
                    $status = 'ok';
                    $count = count($id_usuarios);
                    $message = 'Notificación enviada a ' . count($id_usuarios) . ' usuarios.';
                    $code = 1;
                    $data = [
                        'message' => $message,
                        'usuarios_notificados' => $usuarios_notificar,
                        'id_contrato' => 3, // validaciones es el id_contrato 3.      Contratos: 1.-Afiliaciones, 2.-Expedientes, 3.-Validaciones
                        'codigo_interno' => $params['codigo_interno'],
                        'channel' => $channel,
                        'event' => $event,
                        'msg' => $msg
                    ];
                } catch (PusherException $e) {
                    Log::channel('pusher')->error("Portal::emitir-aviso-cotizacion->Error al desencadenar evento pusher: " . $e->getMessage());
                    array_push($errors, 'Error al desencadenar evento pusher: ' . $e->getMessage());
                    $status = 'fail';
                    $count = count($id_usuarios);
                    $message = 'Error al desencadenar evento pusher';
                    $code = -4;
                    $data = [
                        'message' => $params['mensaje'],
                        'usuarios_notificados' => null,
                        'id_contrato' => 3, // validaciones es el id_contrato 3.      Contratos: 1.-Afiliaciones, 2.-Expedientes, 3.-Validaciones
                        'codigo_interno' => $params['codigo_interno'],
                        'channel' => $channel,
                        'event' => $event,
                        'msg' => $msg
                    ];
                } catch (GuzzleException $e) {
                    Log::channel('pusher')->error("Portal::emitir-aviso-cotizacion->Error al desencadenar evento guzzle: " . $e->getMessage());
                    array_push($errors, 'Error guzzle al desencadenar evento guzzle: ' . $e->getMessage());
                    $status = 'fail';
                    $count = count($id_usuarios);
                    $message = 'Error guzzle al desencadenar evento pusher';
                    $code = -3;
                    $data = [
                        'message' => $params['mensaje'],
                        'usuarios_notificados' => null,
                        'id_contrato' => 3, // validaciones es el id_contrato 3.      Contratos: 1.-Afiliaciones, 2.-Expedientes, 3.-Validaciones
                        'codigo_interno' => $params['codigo_interno'],
                        'channel' => $channel,
                        'event' => $event,
                        'msg' => $msg
                    ];
                } catch (\Exception $e) {
                    Log::channel('pusher')->error("Portal::emitir-aviso-cotizacion->Error general al desencadenar evento pusher: " . $e->getMessage());
                    array_push($errors, 'Error general al desencadenar evento pusher: ' . $e->getMessage());
                    $status = 'fail';
                    $count = count($id_usuarios);
                    $message = 'Error general al desencadenar evento pusher';
                    $code = -2;
                    $data = [
                        'message' => $params['mensaje'],
                        'usuarios_notificados' => null,
                        'id_contrato' => 3, // validaciones es el id_contrato 3.      Contratos: 1.-Afiliaciones, 2.-Expedientes, 3.-Validaciones
                        'codigo_interno' => $params['codigo_interno'],
                        'channel' => $channel,
                        'event' => $event,
                        'msg' => $msg
                    ];
                }
            }else{
                $status = 'empty';
                $message = 'No se encontraron usuarios para notificar';
                $count = 0;
                $code = -6;
                $data = [
                    'message' => $params['mensaje'],
                    'usuarios_notificados' => null,
                    'id_contrato' => 3, // validaciones es el id_contrato 3.      Contratos: 1.-Afiliaciones, 2.-Expedientes, 3.-Validaciones
                    'codigo_interno' => $params['codigo_interno'],
                    'channel' => $channel,
                    'event' => $event,
                    'msg' => $msg
                ];

            }

            return [
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
        } catch (\Throwable $th) {
            Log::channel('pusher')->error("Portal::emitir-aviso-cotizacion->Error no identificado al desencadenar evento pusher: " . $th->getMessage());
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'params' => $params,
                'extras' => $extras
            ]);
        }
    }

}