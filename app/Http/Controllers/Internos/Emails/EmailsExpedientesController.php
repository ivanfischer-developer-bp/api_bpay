<?php namespace App\Http\Controllers\Internos\Emails;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use Mail;

use App\Http\Controllers\Internos\Emails\EmailController;

use App\Mail\NotificacionEmailExpediente;
use App\Mail\NotificacionEmailSolicitudPedidoPresupuestoExpediente;

class EmailsExpedientesController extends EmailController
{
    public function enviar_email_caratula_expediente(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/expedientes/enviar-caratua-expediente',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => []
        ];

        $status = null;
        $error = null;
        $message = null;
        $res = ['message' => ''];
        $code = null;
        $errors = [];
           
        $notificacion = $request->input('notificacion');

        $nro_afiliado = $request->input('nro_afiliado') != null ? $request->input('nro_afiliado') : null;
        $n_persona = $request->input('n_persona') != null ? $request->input('n_persona') : null;
        $nro_doc = $request->input('nro_doc') != null ? $request->input('nro_doc') : null;

        $path_adjunto = $notificacion['adjunto'];
        $emails = explode(',', $notificacion['emails']);
        // return $path_adjunto;
        $trimemails = [];
        foreach($emails as $email){
            $e = trim($email);
            array_push($trimemails, $e);
        }
        try {
            // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
            if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                $mailable = new NotificacionEmailExpediente($notificacion['asunto'], $path_adjunto);
                // Envía automáticamente con fallback
                $resultado = $this->sendEmail($trimemails, $mailable, [$path_adjunto]);
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
                Mail::to($trimemails)->send(new NotificacionEmailExpediente($notificacion['asunto'], $path_adjunto));
                if(Mail::failures()){
                    array_push($extras['responses'], ['smtp_result' => false]);
                    Log::channel('email')->error('Email fallido por SMTP', [
                        'emails' => $trimemails,
                        'asunto' => $notificacion['asunto'],
                        'adjunto' => $path_adjunto
                    ]);
                    $message = 'Error al enviar email por SMTP. ';
                    $error = Mail::failures();
                    array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                    $status = 'fail';
                    $code = -4;
                }else{
                    array_push($extras['responses'], ['smtp_result' => true]);
                    Log::channel('email')->info('Email enviado exitosamente a través de SMTP', [
                        'emails' => $trimemails,
                        'asunto' => $notificacion['asunto'],
                        'adjunto' => $path_adjunto
                    ]);
                    $message = 'Email enviado exitosamente a través de SMTP. ';
                    $error = null;
                    $status = 'ok';
                    $code = 2;
                }
                Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
            }
            // Mail::to($trimemails)->send(new NotificacionEmailExpediente($notificacion['asunto'], $path_adjunto));
            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            $res = $this->registrar_email_enviado($trimemails, 'expedientes', $nro_afiliado, $notificacion['asunto'], $nro_doc);
            array_push($extras['queries'], $res['queries']);
            if($res['code'] > 0){
                return[
                    'status' => $status,
                    'errors' => $error == null ? [] : [$error],
                    'message' => $message.' '.$res['message'],
                    'code' => $code,
                    'line' => null,
                    'persona' => $n_persona,
                    'nro_doc' => $nro_doc,
                    'nro_afiliado' => $nro_afiliado,
                    'emails' => $trimemails,
                    'adjunto' => $notificacion['adjunto'],
                    'extras' => $extras
                ];
            }else{
                return [
                    'status' => $status,
                    'errors' => $error == null ? [] : [$error],
                    'message' => $message.' '.$res['message'],
                    'code' => $code,
                    'line' => null,
                    'persona' => $n_persona,
                    'nro_doc' => $nro_doc,
                    'nro_afiliado' => $nro_afiliado,
                    'emails' => $trimemails,
                    'adjunto' => $notificacion['adjunto'],
                    'extras' => $extras
                ];
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return [
                'status' => 'fail',
                'errors' => $errors,
                'message' => $th->getMessage(),
                'code' => -1,
                'line' => $th->getLine(),
                'persona' => null,
                'nro_doc' => null,
                'nro_afiliado' => null,
                'emails' => null,
                'adjunto' => null,
                'extras' => $extras
            ];
        }
    }

    public function enviar_email_pedido_presupuesto_expediente(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/expedientes/enviar-pedido-presupuesto',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => []
        ];
        $status = null;
        $error = null;
        $message = null;
        $res = ['message' => ''];
        $code = null;
        $errors = [];
        $notificacion = $request->input('notificacion');

        $nro_afiliado = $request->input('nro_afiliado') != null ? $request->input('nro_afiliado') : null;
        $n_persona = $request->input('n_persona') != null ? $request->input('n_persona') : null;
        $nro_doc = $request->input('nro_doc') != null ? $request->input('nro_doc') : null;
        
        $emails = explode(',', $notificacion['emails']);
        // return $path_adjunto;
        $trimemails = [];
        foreach($emails as $email){
            $e = trim($email);
            array_push($trimemails, $e);
        }
        try {
            // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
            if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                $mailable = new NotificacionEmailSolicitudPedidoPresupuestoExpediente($notificacion['asunto'], $notificacion['link']);
                // Envía automáticamente con fallback
                $resultado = $this->sendEmail($trimemails, $mailable);
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
                Mail::to($trimemails)->send(new NotificacionEmailSolicitudPedidoPresupuestoExpediente($notificacion['asunto'], $notificacion['link']));
                if(Mail::failures()){
                    array_push($extras['responses'], ['smtp_result' => false]);
                    Log::channel('email')->error('Email fallido por SMTP', [
                        'emails' => $trimemails,
                        'asunto' => $notificacion['asunto'],
                        'link' => $notificacion['link']
                    ]);
                    $message = 'Error al enviar email por SMTP. ';
                    $error = Mail::failures();
                    array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                    $status = 'fail';
                    $code = -4;
                }else{
                    array_push($extras['responses'], ['smtp_result' => true]);
                    Log::channel('email')->info('Email enviado exitosamente a través de SMTP', [
                        'emails' => $trimemails,
                        'asunto' => $notificacion['asunto'],
                        'link' => $notificacion['link']
                    ]);
                    $message = 'Email enviado exitosamente a través de SMTP. ';
                    $error = null;
                    $status = 'ok';
                    $code = 2;
                }
                Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
            }
            // Mail::to($trimemails)->send(new NotificacionEmailSolicitudPedidoPresupuestoExpediente($notificacion['asunto'], $notificacion['link']));
            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            $res = $this->registrar_email_enviado($trimemails, 'expedientes', $nro_afiliado, $notificacion['asunto'], $nro_doc);
            array_push($extras['queries'], $res['queries']);
            if($res['code'] > 0){
                // marca como enviado el expediente
                try {
                    $expediente_obj = new Expediente;
                    $expediente_obj->ejecutar_sp('sp_expediente_presupuesto_enviado_insert', [
                        'p_id_expediente_presupuesto'=> $notificacion['id_expediente_presupuesto']
                    ]);
                } catch (\Throwable $th) {
                    array_push($errors, 'Email enviado al proveedor, pero no se pudo marcar como enviado en la base de datos');
                    return [
                        'status' => 'fail',
                        'errors' => $errors,
                        'message' => $th->getMessage(),
                        'code' => -5,
                        'line ' => $th->getLine(),
                        'persona' => null,
                        'nro_doc' => null,
                        'nro_afiliado' => null,
                        'emails' => null,
                        'adjunto' => null,
                        'extras' => $extras
                    ];
                }
                return[
                    'status' => $status,
                    'errors' => $error == null ? [] : [$error],
                    'message' => $message.' '.$res['message'].' Solicitud de pedido de presupuesto enviada con éxito.',
                    'code' => $code,
                    'code' => 1,
                    'line' => null,
                    'persona' => $n_persona,
                    'nro_doc' => $nro_doc,
                    'nro_afiliado' => $nro_afiliado,
                    'emails' => $trimemails,
                    'asjunto' => $notificacion['adjunto'],
                    'extras' => $extras
                ];
            }else{
                array_push($errors, $res['error']);
                return [
                    'status' => $status,
                    'errors' => $error == null ? [] : [$error],
                    'message' => $message.' '.$res['message'],
                    'code' => $code,
                    'line' => null,
                    'persona' => $n_persona,
                    'nro_doc' => $nro_doc,
                    'nro_afiliado' => $nro_afiliado,
                    'emails' => $trimemails,
                    'adjunto' => $notificacion['adjunto'],
                    'extras' => $extras
                ];
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return [
                'status' => 'fail',
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'persona' => null,
                'nro_doc' => null,
                'nro_afiliado' => null,
                'emails' => null,
                'adjunto' => null,
                'extras' => $extras
            ];
        }
    }

}
