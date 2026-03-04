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
use App\Http\Controllers\ConexionSpController;

use DB;


class PruebasController extends ConexionSpController
{

    public function probar_sp(Request $request)
    {
        try {
            $sp = request('sp');
            $params = request('params', []);
            $db = request('db', 'afiliacion');
            $conexion = new Conexion($db);
            return $conexion->ejecutar_sp($sp, $params);
        } catch (\Exception $e) {
            return $e;
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
}