<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;
use Pusher\ApiErrorException;
use Pusher\Pusher;
use Pusher\PusherException;

use App\Models\Conexion;
use App\Models\User;
use App\Http\Controllers\ConexionSpController;

use DB;


class PruebasController extends ConexionSpController
{

    /** 
     * Prueba un store procedure directamente
     */    
    public function probar_sp(Request $request)
    {
        try {
            $query = null;
            $sp = request('sp');
            $params = request('params', []);
            $db = request('db', 'afiliacion');
            $conexion = new Conexion($db);

            $query = $this->get_query($db, $sp, $params);
            $response = $conexion->ejecutar_sp($sp, $params);
            $data = [
                'query' => $query,
                'response' => $response
            ];
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'query' => $query,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Prueba codigo de otros lugares, este metodo va cambiando segun la necesidad
     */
    public function probar_codigo_post(Request $request){
        // asignaciones generales
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/pruebas/probar-codigo',
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
        $params_sp = [];
        
        try {
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find(1);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;
            $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

            // codigo a probar ...

            array_push($extras['sps'], ['AWEB_TraerEstadosValidaciones' => null]);
            array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerEstadosValidaciones', null));
            $estados = $this->ejecutar_sp_directo('validacion','AWEB_TraerEstadosValidaciones', null);
            array_push($extras['responses'], ['AWEB_TraerEstadosValidaciones' => $estados]);

            $resolucion['id_estado'] = 1;

            $estadoSelec = '';
            foreach ($estados as $estado) {
                if ($estado->id_estado == $resolucion['id_estado']) {
                    $estadoSelec = $estado->n_estado;
                }
            }

            return response()->json([
                'extras' => $extras
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => ['Line: ' . $th->getLine() . ' - Error: ' . $th->getMessage()],
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params_sp' => $params_sp,
                'extras' => $extras,
            ]);
        }
    }

    /**
     * Prueba codigo de otros lugares, este metodo va cambiando segun la necesidad
     */
    public function probar_codigo_get(Request $request){
        // asignaciones generales
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/pruebas/probar-codigo',
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
        $params_sp = [];
        
        try {
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find(1);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;
            $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

            // codigo a probar ...

            array_push($extras['sps'], ['AWEB_TraerEstadosValidaciones' => null]);
            array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerEstadosValidaciones', null));
            $estados = $this->ejecutar_sp_directo('validacion','AWEB_TraerEstadosValidaciones', null);
            array_push($extras['responses'], ['AWEB_TraerEstadosValidaciones' => $estados]);

            $resolucion['id_estado'] = 1;

            $estadoSelec = '';
            foreach ($estados as $estado) {
                if ($estado->id_estado == $resolucion['id_estado']) {
                    $estadoSelec = $estado->n_estado;
                }
            }

            return response()->json([
                'extras' => $extras
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => ['Line: ' . $th->getLine() . ' - Error: ' . $th->getMessage()],
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params_sp' => $params_sp,
                'extras' => $extras,
            ]);
        }
    }

    /**
     * prueba de emisión de pusher con una sola instancia
     */
    public function emitir_pusher(Request $request)
    {
        $id_usuario = request('id_usuario');
        $channel = request('channel');
        $event = request('event');
        $msg = request('msg');
        
        // return response()->json([
        //     'id_usuario' => $id_usuario,
        //     'channel' => $channel,
        //     'event' => $event,
        //     'msg' => $msg
        // ]);

        // auditoria en terreno
        // $channel = "auditoria-en-terreno-solicitada";
        // $event = "AuditoriaEnTerrenoSolicitada";
        // $msg = [
        //     'id_usuario' => $id_usuario, 
        //     'code' => 5,
        //     'message' => 'Se ha realizado una nueva solicitud de auditoría en terreno',
        //     'ambito' => env('ENVIRONMENT')
        // ];

        // validacion
        // $utiles = [
        //     'path_url' => '/validaciones/auditar/32A9A6DF-0D1B-4009-8669-A1D973C2D536',
        //     'codigo' => '32A9A6DF-0D1B-4009-8669-A1D973C2D536'
        // ];
        // $params_notificacion = [
        //     'p_id_contrato' => 3,
        //     'p_id_usuario' => 10,
        //     'p_texto_notificacion' => "Afiliado ROCCO, EDUARDO GUILLERMO N° 105378200. Autorización Nro. 76523 para auditar.",
        //     'p_asunto' => "Afiliado ROCCO, EDUARDO GUILLERMO N° 105378200. Autorización Nro. 76523",
        //     'p_notificado' => 0,
        //     'p_utiles' => json_encode($utiles),
        //     'p_id_tipo_notificacion' => 2
        // ];
        // $response = $this->ejecutar_sp_directo('admin', 'sp_notificacion_insert', $params_notificacion);
        // $channel = "notificacion-push";
        // $event = "NotificacionEnviada";
        // $msg = [
        //     'id_usuarios' => [$id_usuario], 
        //     'code' => 1, 
        //     'message' => 'Se ha realizado una nueva validación',
        //     'ambito' => env('ENVIRONMENT')
        // ];  // message

        // recien nacido
        // $channel = "afiliaciones";
        // $event = "RecienNacido";
        // $msg = [
        //     'id_usuario' => [$id_usuario], 
        //     'code' => 3, 
        //     'message' => 'Se ha creado un nuevo expediente de pre-afiliación',
        //     'ambito' => env('ENVIRONMENT')
        // ];

        // pase expediente
        // $channel = "expedientes-pase";
        // $event = "PaseExpediente";
        // $msg = [
        //     'id_usuarios' => [$id_usuario], 
        //     'code' => 4, 
        //     'message' => 'Se ha realizado un nuevo pase en expedientes',
        //     'ambito' => env('ENVIRONMENT')
        // ];

        // expediente de insumos
        // $channel = "iniciar-expediente-insumos";
        // $event = "NotificacionIniciarExpedienteInsumos";
        // $msg = [
        //     'id_usuarios' => [$id_usuario], 
        //     'code' => 6, 
        //     'message' => 'Se ha generado un nuevo expediente de insumos',
        //     'ambito' => env('ENVIRONMENT')
        // ];

        // nuevo mensaje del sistema
        // $mensaje = 'Nuevo Mensaje: Subir archivo de firma, recibido de: Sistema';
        // $channel = "nuevo-mensaje";
        // $event = "NuevoMensaje";
        // $msg = [
        //     'id_usuarios' => [$id_usuario], 
        //     'code' => 110, 
        //     'message' => $mensaje,
        //     'ambito' => env('ENVIRONMENT')
        // ];

        try {
            $clientePusher = new Pusher(env('PUSHER_APP_KEY', ''), env('PUSHER_APP_SECRET', ''), env('PUSHER_APP_ID', ''), array('cluster' => env('PUSHER_APP_CLUSTER', 'us2')));
        } catch (PusherException $e) {
            Log::channel('pusher')->warning("Pruebas::Error al instanciar pusher: " . $e->getMessage());
            return $e;
        }

        try {
            $clientePusher->trigger($channel, $event, $msg);
            return response()->json([
                'id_usuario' => $id_usuario,
                'channel' => $channel,
                'event' => $event,
                'msg' => $msg
            ]);
        } catch (PusherException $e) {
            Log::warning("Error al desencadenar evento pusher: " . $e->getMessage());
            return $e;
        } catch (GuzzleException $e) {
            Log::warning("Error al desencadenar evento guzzle: " . $e->getMessage());
            return $e;
        } catch (\Exception $e) {
            return $e;
        }

    }

    public function emitir_cambio_version(Request $request)
    {
        $version = request('version');

        $channel = "notificaciones-sistema";
        $event = "NuevaVersionSoftware";
        $msg = [
            'code' => 100, 
            'version' => $version,
            'message' => 'Nueva versión de software disponible con mejoras, correcciones y nuevas funcionalidades. Versión: '.$version,
            'ambito' => env('ENVIRONMENT')
        ];  // message

        try {
            $clientePusher = new Pusher(env('PUSHER_APP_KEY', ''), env('PUSHER_APP_SECRET', ''), env('PUSHER_APP_ID', ''), array('cluster' => env('PUSHER_APP_CLUSTER', 'us2')));
        } catch (PusherException $e) {
            Log::channel('pusher')->warning("Pruebas::Error al instanciar pusher: " . $e->getMessage());
            // array_push($errors, $e->getMessage());
            return $e;
        }

        try {
            $clientePusher->trigger($channel, $event, $msg);
            return response()->json([
                'channel' => $channel,
                'event' => $event,
                'msg' => $msg
            ]);
        } catch (PusherException $e) {
            Log::warning("Error al desencadenar evento pusher: " . $e->getMessage());
            return $e;
        } catch (GuzzleException $e) {
            Log::warning("Error al desencadenar evento guzzle: " . $e->getMessage());
            return $e;
        } catch (\Exception $e) {
            return $e;
        }
        
    }

    public function ejemplo_agregar_pusher(Request $request)
    {
        $errors = [];

        $destinatarios = request('destinatarios');  // puede ser reemplazado por usuarios_a_notificar

        try {
            $clientePusher = new Pusher(env('PUSHER_APP_KEY', ''), env('PUSHER_APP_SECRET', ''), env('PUSHER_APP_ID', ''), array('cluster' => env('PUSHER_APP_CLUSTER', 'us2')));
        } catch (PusherException $e) {
            Log::channel('pusher')->warning("Pruebas:: Error al instanciar pusher: " . $e->getMessage());
            // array_push($errors, $e->getMessage());
        }
        
        $channel = "nuevo-mensaje";
        $event = "nuevoMensaje";
        try {
            foreach ($destinatarios as $key => $id_usuario) {
                $msg = [
                    'id_usuario' => $id_usuario, 
                    'code' => 110, 
                    'message' => 'mensaje a enviar',
                    'ambito' => env('ENVIRONMENT')
                ];
                $clientePusher->trigger($channel, $event, $msg);
            }
            $data = [
                'otra_cosa' => 'otra cosa que se quiera devolver en data',
                'pusher' => [
                    'channel' => $channel,
                    'event' => $event,
                    'notificacion' => $msg,
                    'usuarios_notificados' => $destinatarios
                ]
            ];
        } catch (PusherException $e) {
            Log::warning("Error al desencadenar evento pusher: " . $e->getMessage());
            array_push($errors, $e->getMessage());
        } catch (GuzzleException $e) {
            Log::warning("Error al desencadenar evento guzzle: " . $e->getMessage());
            array_push($errors, $e->getMessage());
        } catch (\Exception $e) {
            return $e;
        }
    }

    /**
     * Función de prueba para invocar el servicio SOAP de OSEF
     * 
     * Parámetros esperados:
     * - usuario_osef: Usuario para autenticación en OSEF
     * - password_osef: Password para autenticación en OSEF
     * - tipo_consulta: 'autorizacion' | 'afiliado' (default: autorizacion)
     * - numero_autorizacion: Para consulta de autorización
     * - numero_afiliado: Para consulta de afiliado
     * - delegacion: Delegación (default: 0)
     * - plan: Plan (optional, default: 0)
     * - gravamen: Gravamen (optional, default: 0)
     * 
     * Ejemplos de invocación:
     * POST /int/afiliaciones/afiliado/prueba-osef
     * {
     *   "usuario_osef": "USUARIO",
     *   "password_osef": "PASSWORD",
     *   "tipo_consulta": "autorizacion",
     *   "numero_autorizacion": 123456,
     *   "delegacion": 0
     * }
     * 
     * o
     * 
     * {
     *   "usuario_osef": "USUARIO",
     *   "password_osef": "PASSWORD",
     *   "tipo_consulta": "afiliado",
     *   "numero_afiliado": "001234567"
     * }
     */
    public function prueba_osef(Request $request)
    {
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/pruebas/prueba-osef',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
        ];
        
        $status = 'fail';
        $message = '';
        $count = -1;
        $data = null;
        $errors = [];
        $params = [];
        $logged_user = null;

        try {
            // Obtiene usuario de la petición
            $requestUser = $request->user();
            
            if (!$requestUser || empty($requestUser->id)) {
                return response()->json([
                    'status' => 'unauthorized',
                    'count' => -1,
                    'errors' => ['Usuario no autenticado'],
                    'message' => 'No hay usuario autenticado en la solicitud',
                    'line' => null,
                    'code' => -1,
                    'data' => null,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]);
            }

            $user = User::with('roles', 'permissions')->find($requestUser->id);
            if (!$user) {
                return response()->json([
                    'status' => 'fail',
                    'count' => -1,
                    'errors' => ['Usuario autenticado no encontrado en base de datos'],
                    'message' => 'No se pudo cargar el usuario autenticado',
                    'line' => null,
                    'code' => -1,
                    'data' => null,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]);
            }

            $logged_user = $this->get_logged_user($user);
            
            // Valida que tenga permiso de administración o de gestión de afiliados
            if (!$user->hasPermissionTo('gestionar afiliados') && !$user->hasRole('admin')) {
                return response()->json([
                    'status' => 'unauthorized',
                    'count' => -1,
                    'errors' => ['Error de permisos'],
                    'message' => 'No puede acceder a esta ruta. Se requiere permiso para GESTIONAR AFILIADOS',
                    'line' => null,
                    'code' => -1,
                    'data' => null,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]);
            }

            // Obtiene parámetros
            $usuario_osef = request('usuario_osef');
            $password_osef = request('password_osef');
            $tipo_consulta = request('tipo_consulta', 'autorizacion'); // Default: autorizacion
            $numero_autorizacion = request('numero_autorizacion');
            $numero_afiliado = request('numero_afiliado');
            $delegacion = request('delegacion', 0);
            $plan = request('plan', 0);
            $gravamen = request('gravamen', 0);

            $params = [
                'usuario_osef' => !empty($usuario_osef) ? '***' : null,
                'password_osef' => !empty($password_osef) ? '***' : null,
                'tipo_consulta' => $tipo_consulta,
                'numero_autorizacion' => $numero_autorizacion,
                'numero_afiliado' => $numero_afiliado,
                'delegacion' => $delegacion,
                'plan' => $plan,
                'gravamen' => $gravamen,
            ];

            // Valida parámetros obligatorios
            if (empty($usuario_osef) || empty($password_osef)) {
                return response()->json([
                    'status' => 'fail',
                    'count' => -1,
                    'errors' => ['usuario_osef y password_osef son obligatorios'],
                    'message' => 'Parámetros insuficientes',
                    'line' => null,
                    'code' => -2,
                    'data' => null,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]);
            }

            // Usa el helper WsOsef para realizar la consulta
            require_once base_path('app/Http/Helpers/wsosef.php');
            
            $resultado_soap = null;

            if ($tipo_consulta === 'autorizacion') {
                // Valida número de autorización
                if (empty($numero_autorizacion)) {
                    return response()->json([
                        'status' => 'fail',
                        'count' => -1,
                        'errors' => ['Para tipo_consulta=autorizacion, numero_autorizacion es obligatorio'],
                        'message' => 'Parámetro numero_autorizacion requerido',
                        'line' => null,
                        'code' => -3,
                        'data' => null,
                        'params' => $params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ]);
                }

                // Consulta autorización en OSEF
                $resultado_soap = \App\Http\Helpers\WsOsef::consultarAutorizacion(
                    $usuario_osef,
                    $password_osef,
                    intval($numero_autorizacion),
                    intval($delegacion),
                    $numero_afiliado ?? ''
                );

            } else if ($tipo_consulta === 'afiliado') {
                // Valida número de afiliado
                if (empty($numero_afiliado)) {
                    return response()->json([
                        'status' => 'fail',
                        'count' => -1,
                        'errors' => ['Para tipo_consulta=afiliado, numero_afiliado es obligatorio'],
                        'message' => 'Parámetro numero_afiliado requerido',
                        'line' => null,
                        'code' => -4,
                        'data' => null,
                        'params' => $params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ]);
                }

                // Consulta afiliado en OSEF
                $resultado_soap = \App\Http\Helpers\WsOsef::consultarAfiliado(
                    $usuario_osef,
                    $password_osef,
                    $numero_afiliado,
                    intval($plan),
                    intval($gravamen)
                );

            } else {
                return response()->json([
                    'status' => 'fail',
                    'count' => -1,
                    'errors' => ['tipo_consulta inválido. Use: autorizacion | afiliado'],
                    'message' => 'Parámetro tipo_consulta inválido',
                    'line' => null,
                    'code' => -5,
                    'data' => null,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]);
            }

            // Formatea y retorna la respuesta SOAP
            if ($resultado_soap['success']) {
                $data = \App\Http\Helpers\WsOsef::formatearRespuesta($resultado_soap);
                $status = 'ok';
                $count = 1;
                $message = 'Consulta realizada exitosamente';
            } else {
                $data = [
                    'error' => $resultado_soap['error'],
                    'soap_request' => $resultado_soap['soap_request'],
                    'soap_response' => $resultado_soap['soap_response'],
                ];
                $status = 'fail';
                $count = 0;
                $message = 'Error en la consulta a OSEF: ' . $resultado_soap['error'];
                array_push($errors, $resultado_soap['error']);
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

        } catch (\Throwable $th) {
            $errors = ['Line: ' . $th->getLine() . ' - Error: ' . $th->getMessage()];
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