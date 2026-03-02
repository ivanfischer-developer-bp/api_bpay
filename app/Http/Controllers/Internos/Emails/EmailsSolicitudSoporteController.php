<?php namespace App\Http\Controllers\Internos\Emails;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;
use Pusher\ApiErrorException;
use Pusher\Pusher;
use Pusher\PusherException;

use Mail;
use Carbon\Carbon;

use App\Http\Controllers\Internos\Emails\EmailController;
use App\Mail\NotificacionSolicitudSoporte;
use App\Models\SolicitudSoporte;
use App\Models\User;

class EmailsSolicitudSoporteController extends EmailController
{
    public function enviar_solicitud_soporte(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/soporte/enviar-solicitud-soporte',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
        ];
        $errors = [];
        $error = null;
        $message = null;
        $res = ['message' => ''];
        $status = null;
        $code = null;
        $params = [];
           
        $adjunto = request('adjunto');
        $asunto = request('asunto');
        $mensaje = request('mensaje');
        $tipo = request('tipo') != null ? request('tipo') : 'soporte';

        $params = [
            'adjunto' => $adjunto,
            'asunto' => $asunto,
            'mensaje' => $mensaje,
            'tipo' => $tipo
        ];

        if($adjunto != null){
            // $path_adjunto = storage_path() . '/uploads/' .$adjunto;
            $path_adjunto = env('UPLOADS_PATH_EXTERNO') .$adjunto;
        }else{
            $path_adjunto = '';
        }

        $email = [env('SUPPORT_EMAIL')];
        // $email = ['ivanfischer76@gmail.com'];

        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;
            $id_usuario_sqlserver = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
            $nombre_usuario = $user->name;

            $fromAddress = $user->email != null ? $user->email : config('mail.from.address'); // Dirección de correo configurada
            $fromName = $nombre_usuario; // Nombre dinámico

            // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
            if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                $mailable = new NotificacionSolicitudSoporte($asunto, $mensaje, $path_adjunto, $nombre_usuario, $fromAddress);
                // Envía automáticamente con fallback
                // El parámetro $fromAddress se usa como replyTo, no como remitente real
                // Microsoft Graph con Client Credentials no permite cambiar el remitente
                $resultado = $this->sendEmail($email, $mailable, [$path_adjunto], $fromAddress, $fromName);
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
                $mailable = new NotificacionSolicitudSoporte($asunto, $mensaje, $path_adjunto, $nombre_usuario, $fromAddress);
                $mailable->from($fromAddress, $fromName);
                Mail::to($email)->send($mailable);
                if(Mail::failures()){
                    array_push($extras['responses'], ['smtp_result' => false]);
                    Log::channel('email')->error('Error al enviar email por SMTP', [
                        'emails' => $email,
                        'asunto' => $asunto,
                        'mensaje' => $mensaje,
                        'adjunto' => $path_adjunto,
                        'nombre_usuario' => $nombre_usuario,
                    ]);
                    $message = 'Error al enviar email por SMTP. ';
                    $error = Mail::failures();
                    array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                    $status = 'fail';
                    $code = -4;
                }else{
                    array_push($extras['responses'], ['smtp_result' => true]);
                    Log::channel('email')->info('Email enviado por SMTP', [
                        'emails' => $email,
                        'asunto' => $asunto,
                        'mensaje' => $mensaje,
                        'adjunto' => $path_adjunto,
                        'nombre_usuario' => $nombre_usuario,
                    ]);
                    $message = 'Email enviado exitosamente por SMTP. ';
                    $error = null;
                    $status = 'ok';
                    $code = 2;
                }
                Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
            }

            // Mail::to($email)->send(new NotificacionSolicitudSoporte($asunto, $mensaje, $path_adjunto, $nombre_usuario) );

            $solicitud = new SolicitudSoporte();
            $solicitud->fecha = Carbon::now();
            $solicitud->asunto = $asunto;
            $solicitud->mensaje = $mensaje;
            $solicitud->adjunto = $adjunto;
            $solicitud->user_id = $user->id;
            $solicitud->id_usuario_sqlserver = $id_usuario_sqlserver;
            $solicitud->nombre_usuario = $nombre_usuario;
            $solicitud->email_usuario = $user->email;
            $solicitud->ambiente = env('AMBIENTE');
            $solicitud->estado = 'pendiente';
            $solicitud->tipo = $tipo;
            $solicitud->leido = null;
            $s = $solicitud->save();

            // emitimos una notificacion pusher
            try {
                $clientePusher = new Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), array('cluster' => env('PUSHER_APP_CLUSTER', 'us2')));
            } catch (PusherException $e) {
                Log::warning("Error al instanciar pusher: " . $e->getMessage());
                return $e;
            }
            $ambito = '';
            switch (env('ENVIRONMENT')) {
                case 'casa':
                    $ambito = 'QA';
                    break;
                case 'basa':
                    $ambito = 'LEAL MEDICA';
                    break;
                case 'sanos':
                    $ambito = 'SANOS SALUD';
                    break;
                case 'osceara':
                    $ambito = 'OSCEARA';
                    break;
                default:
                    $ambito = 'INDEFINIDO';
                    break;
            }

            $channel = "notificaciones-sistema";
            $event = "SolicitudSoporteCreada";
            $msg = [
                'id_usuario' => $user->id, 
                'id_solicitud' => $solicitud->id,
                'tipo' => $tipo,
                'code' => 120, 
                'message' => 'SOLICITUD DE: '.$user->usuario.' ASUNTO: '.$asunto,
                'ambito' => $ambito
            ];
            $msg_pusher = '';
            try {
                $clientePusher->trigger($channel, $event, $msg);
                $msg_pusher = ' Notificación emitida por Pusher. ';
            } catch (PusherException $e) {
                Log::warning("Error al desencadenar evento pusher: " . $e->getMessage());
                return $e;
            } catch (GuzzleException $e) {
                Log::warning("Error al desencadenar evento guzzle: " . $e->getMessage());
                return $e;
            } catch (\Exception $e) {
                return $e;
            }

            if($s == 1){
                $message = $message.' Solicitud de Soporte enviada con éxito. Solicitud registrada.'.$msg_pusher;
                $status = 'ok';
                $code = 3;
            }else{
                array_push($errors, ' Error guardar solicitud de soporte.');
                $message = $message.' Solicitud de Soporte enviada con éxito. Solicitud NO registrada.'.$msg_pusher;
                $status = 'ok';
                $code = 2;
            }

            return[
                'status' => $status,
                'errors' => $error == null ? [] : [$error],
                'message' => $message.' '.$res['message'],
                'code' => $code,
                'line' => null,
                'params' => $params,
                'solicitud' => $solicitud,
                'emails' => $email,
                'adjunto' => $path_adjunto,
                'extras' => $extras
            ];
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return [
                'status' => 'fail',
                'errors' => $errors,
                'message' => 'Fallo en el envío o registro de la solicitud de soporte.',
                'code' => -1,
                'line' => $th->getLine(),
                'params' => $params,
                'solicitud' => null,
                'emails' => $email,
                'adjunto' => null,
                'extras' => $extras
            ];
        }
        
    }
}