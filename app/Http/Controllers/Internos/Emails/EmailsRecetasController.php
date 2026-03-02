<?php 
namespace App\Http\Controllers\Internos\Emails;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use Mail;

use App\Http\Controllers\Internos\Emails\EmailController;

use App\Mail\NotificacionEmailCertificadoGenerado;
use App\Mail\NotificacionEmailRecetaGenerada;
use App\Mail\NotificacionEmailPrescripcionGenerada;

class EmailsRecetasController extends EmailController
{
    /**
     * Envía por email la receta generada al afiliado
     */
    public function enviar_email_receta_generada(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/recetas/enviar-receta-generada',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => []
        ];

        try {
            $status = null;
            $error = null;
            $message = null;
            $res = ['message' => ''];
            $code = null;
            $errors = [];
            //  obtiene los datos
            $notificacion = $request->input('notificacion');
            $nro_afiliado = $notificacion['nro_afiliado'] != null ? $notificacion['nro_afiliado'] : null;
            $n_persona = $notificacion['n_persona'] != null ? $notificacion['n_persona'] : null;
            $nro_doc = $notificacion['nro_doc'] != null ? $notificacion['nro_doc'] : null;
            $observaciones_notificacion = $notificacion['observaciones'] == null ? '' : $notificacion['observaciones'];
            $links_recetas = $notificacion['array'] == null ? [] : $notificacion['array'];
            $path_adjunto = env('STORAGE_PATH').'reportes/recetas_afiliados/'.$notificacion['adjunto'];
            $trimemails = [];
            if(!is_array($notificacion['emails'])){
                $emails = explode(',', $notificacion['emails']);
            }else{
                $emails = $notificacion['emails'];
            }
            foreach($emails as $email){
                $e = trim($email);
                array_push($trimemails, $e);
            }
            if($notificacion['adjunto'] != null){
                // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                    $mailable = new NotificacionEmailRecetaGenerada($notificacion['asunto'], $path_adjunto, $observaciones_notificacion, []);
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
                    Mail::to($trimemails)->send(new NotificacionEmailRecetaGenerada($notificacion['asunto'], $path_adjunto, $observaciones_notificacion, []));
                    if(Mail::failures()){
                        array_push($extras['responses'], ['smtp_result' => false]);
                        Log::channel('email')->error('Email fallido por SMTP', [
                            'emails' => $trimemails,
                            'asunto' => $notificacion['asunto'],
                            'adjunto' => $path_adjunto,
                            'observaciones' => $observaciones_notificacion
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
                            'adjunto' => $path_adjunto,
                            'observaciones' => $observaciones_notificacion
                        ]);
                        $message = 'Email enviado exitosamente a través de SMTP. ';
                        $error = null;
                        $status = 'ok';
                        $code = 2;
                    }
                    Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                }
                // Mail::to($trimemails)->send(new NotificacionEmailRecetaGenerada($notificacion['asunto'], $path_adjunto, $observaciones_notificacion, []));
            }else{
                // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                    $mailable = new NotificacionEmailRecetaGenerada($notificacion['asunto'], '', $observaciones_notificacion, $links_recetas);
                    // Envía automáticamente con fallback
                    $resultado = $this->sendEmail($trimemails, $mailable);
                    array_push($extras['responses'], ['microsoft_graph_result' => $resultado]);
                    if ($resultado) {
                        $message = 'Email enviado con Microsoft Graph. ';
                        $error = null;
                        $status = 'ok';
                        $code = 3;
                    }else{
                        $message = 'Error al enviar email con Microsoft Graph';
                        $error = $resultado;
                        array_push($errors, 'Error al enviar email con Microsoft Graph: '.json_encode($resultado));
                        $status = 'fail';
                        $code = -5;
                    }
                }else{
                    Mail::to($trimemails)->send(new NotificacionEmailRecetaGenerada($notificacion['asunto'], '', $observaciones_notificacion, $links_recetas));
                    if(Mail::failures()){
                        array_push($extras['responses'], ['smtp_result' => false]);
                        Log::channel('email')->error('Email fallido por SMTP', [
                            'emails' => $trimemails,
                            'asunto' => $notificacion['asunto'],
                            'observaciones' => $observaciones_notificacion,
                            'links_recetas' => $links_recetas
                        ]);
                        $message = 'Error al enviar email por SMTP. ';
                        $error = Mail::failures();
                        array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                        $status = 'fail';
                        $code = -6;
                    }else{
                        array_push($extras['responses'], ['smtp_result' => true]);
                        Log::channel('email')->info('Email enviado exitosamente a través de SMTP', [
                            'emails' => $trimemails,
                            'asunto' => $notificacion['asunto'],
                            'observaciones' => $observaciones_notificacion,
                            'links_recetas' => $links_recetas
                        ]);
                        $message = 'Email enviado exitosamente a través de SMTP. ';
                        $error = null;
                        $status = 'ok';
                        $code = 4;
                    }
                    Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                }
                // Mail::to($trimemails)->send(new NotificacionEmailRecetaGenerada($notificacion['asunto'], '', $observaciones_notificacion, $links_recetas));
            }
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
                'extras' => $extras,
                'notificacion' => $notificacion
            ];
            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            // $res = $this->registrar_email_enviado($trimemails, 'recetas', $nro_afiliado, $n_persona, $nro_doc);
            // array_push($extras['queries'], $res['queries']);
            // if($res['code'] > 0){
            //     return[
            //         'status' => 'ok',
            //         'errors' => $errors,
            //         'message' => 'Notificación enviada con éxito.',
            //         'line' => null,
            //         'code' => 1,
            //         'persona' => $n_persona,
            //         'nro_doc' => $nro_doc,
            //         'nro_afiliado' => $nro_afiliado,
            //         'emails' => $trimemails,
            //         'adjunto' => $notificacion['adjunto'],
            //         'extras' => $extras
            //     ];
            // }else{
            //     array_push($errors, $res['error']);
            //     return [
            //         'status' => 'fail',
            //         'errors' => $errors,
            //         'message' => $res['message'],
            //         'line' => null,
            //         'code' => -2,
            //         'persona' => $n_persona,
            //         'nro_doc' => $nro_doc,
            //         'nro_afiliado' => $nro_afiliado,
            //         'emails' => $trimemails,
            //         'adjunto' => $notificacion['adjunto'],
            //         'extras' => $extras
            //     ];
            // }
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
                'extras' => $extras
            ];
        }
    }

    /**
     * Envía por email la prescripción generada al afiliado
     */
    public function enviar_email_prescripcion_generada(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/recetas/enviar-prescripcion-generada',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => []
        ];

        try {
            $status = null;
            $error = null;
            $message = null;
            $res = ['message' => ''];
            $code = null;
            $errors = [];
            //  obtiene los datos
            $notificacion = $request->input('notificacion');
            $nro_afiliado = $notificacion['nro_afiliado'] != null ? $notificacion['nro_afiliado'] : null;
            $n_persona = $notificacion['n_persona'] != null ? $notificacion['n_persona'] : null;
            $nro_doc = $notificacion['nro_doc'] != null ? $notificacion['nro_doc'] : null;
            $observaciones_notificacion = $notificacion['observaciones'] == null ? '' : $notificacion['observaciones'];
            $links_recetas = $notificacion['array'] == null ? [] : $notificacion['array'];
            $path_adjunto = env('STORAGE_PATH').'reportes/prescripciones_afiliados/'.$notificacion['adjunto'];
            $trimemails = [];
            if(!is_array($notificacion['emails'])){
                $emails = explode(',', $notificacion['emails']);
            }else{
                $emails = $notificacion['emails'];
            }
            foreach($emails as $email){
                $e = trim($email);
                array_push($trimemails, $e);
            }
            if($notificacion['adjunto'] != null){
                // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                    $mailable = new NotificacionEmailPrescripcionGenerada($notificacion['asunto'], $path_adjunto, $observaciones_notificacion, []);
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
                    Mail::to($trimemails)->send(new NotificacionEmailPrescripcionGenerada($notificacion['asunto'], $path_adjunto, $observaciones_notificacion, []));
                    if(Mail::failures()){
                        array_push($extras['responses'], ['smtp_result' => false]);
                        Log::channel('email')->error('Email fallido por SMTP', [
                            'emails' => $trimemails,
                            'asunto' => $notificacion['asunto'],
                            'adjunto' => $path_adjunto,
                            'observaciones' => $observaciones_notificacion
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
                            'adjunto' => $path_adjunto,
                            'observaciones' => $observaciones_notificacion
                        ]);
                        $message = 'Email enviado exitosamente a través de SMTP. ';
                        $error = null;
                        $status = 'ok';
                        $code = 2;
                    }
                    Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                }
                // Mail::to($trimemails)->send(new NotificacionEmailPrescripcionGenerada($notificacion['asunto'], $path_adjunto, $observaciones_notificacion, []));
            }else{
                // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                    $mailable = new NotificacionEmailPrescripcionGenerada($notificacion['asunto'], '', $observaciones_notificacion, $links_recetas);
                    // Envía automáticamente con fallback
                    $resultado = $this->sendEmail($trimemails, $mailable);
                    array_push($extras['responses'], ['microsoft_graph_result' => $resultado]);
                    if ($resultado) {
                        $message = 'Email enviado con Microsoft Graph. ';
                        $error = null;
                        $status = 'ok';
                        $code = 3;
                    }else{
                        $message = 'Error al enviar email con Microsoft Graph';
                        $error = $resultado;
                        array_push($errors, 'Error al enviar email con Microsoft Graph: '.json_encode($resultado));
                        $status = 'fail';
                        $code = -5;
                    }
                }else{
                    Mail::to($trimemails)->send(new NotificacionEmailPrescripcionGenerada($notificacion['asunto'], '', $observaciones_notificacion, $links_recetas));
                    if(Mail::failures()){
                        array_push($extras['responses'], ['smtp_result' => false]);
                        Log::channel('email')->error('Email fallido por SMTP', [
                            'emails' => $trimemails,
                            'asunto' => $notificacion['asunto'],
                            'observaciones' => $observaciones_notificacion,
                            'links_recetas' => $links_recetas
                        ]);
                        $message = 'Error al enviar email por SMTP. ';
                        $error = Mail::failures();
                        array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                        $status = 'fail';
                        $code = -6;
                    }else{
                        array_push($extras['responses'], ['smtp_result' => true]);
                        Log::channel('email')->info('Email enviado exitosamente a través de SMTP', [
                            'emails' => $trimemails,
                            'asunto' => $notificacion['asunto'],
                            'observaciones' => $observaciones_notificacion,
                            'links_recetas' => $links_recetas
                        ]);
                        $message = 'Email enviado exitosamente a través de SMTP. ';
                        $error = null;
                        $status = 'ok';
                        $code = 4;
                    }
                    Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                }
                // Mail::to($trimemails)->send(new NotificacionEmailPrescripcionGenerada($notificacion['asunto'], '', $observaciones_notificacion, $links_recetas));
            }
            // if(Mail::failures()){
            //     array_push($errors, 'No se pudo enviar el email. '.Mail::failures());
            //     $status = 'fail';
            //     $message = 'No se pudo enviar el email.';
            //     $code = -2;
            // }
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
                'extras' => $extras,
                'notificacion' => $notificacion
            ];
            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            // $res = $this->registrar_email_enviado($trimemails, 'recetas', $nro_afiliado, $n_persona, $nro_doc);
            // array_push($extras['queries'], $res['queries']);
            // if($res['code'] > 0){
            //     return[
            //         'status' => 'ok',
            //         'errors' => $errors,
            //         'message' => 'Notificación enviada con éxito.',
            //         'line' => null,
            //         'code' => 1,
            //         'persona' => $n_persona,
            //         'nro_doc' => $nro_doc,
            //         'nro_afiliado' => $nro_afiliado,
            //         'emails' => $trimemails,
            //         'adjunto' => $notificacion['adjunto'],
            //         'extras' => $extras
            //     ];
            // }else{
            //     array_push($errors, $res['error']);
            //     return [
            //         'status' => 'fail',
            //         'errors' => $errors,
            //         'message' => $res['message'],
            //         'line' => null,
            //         'code' => -2,
            //         'persona' => $n_persona,
            //         'nro_doc' => $nro_doc,
            //         'nro_afiliado' => $nro_afiliado,
            //         'emails' => $trimemails,
            //         'adjunto' => $notificacion['adjunto'],
            //         'extras' => $extras
            //     ];
            // }
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
                'extras' => $extras
            ];
        }
    }

    /**
     * Envía por email el certificado generado al afiliado
     */
    public function enviar_email_certificado_generado(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/recetas/enviar-certificado-generado',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => []
        ];

        try {
            $status = null;
            $error = null;
            $message = null;
            $res = ['message' => ''];
            $code = null;
            $errors = [];
            //  obtiene los datos
            $notificacion = $request->input('notificacion');
            $nro_afiliado = $notificacion['nro_afiliado'] != null ? $notificacion['nro_afiliado'] : null;
            $n_persona = $notificacion['n_persona'] != null ? $notificacion['n_persona'] : null;
            $nro_doc = $notificacion['nro_doc'] != null ? $notificacion['nro_doc'] : null;
            $observaciones_notificacion = $notificacion['observaciones'] == null ? '' : $notificacion['observaciones'];
            $links_recetas = $notificacion['array'] == null ? [] : $notificacion['array'];
            $path_adjunto = env('STORAGE_PATH').'reportes/certificados_recetas/'.$notificacion['adjunto'];
            $trimemails = [];
            if(!is_array($notificacion['emails'])){
                $emails = explode(',', $notificacion['emails']);
            }else{
                $emails = $notificacion['emails'];
            }
            foreach($emails as $email){
                $e = trim($email);
                array_push($trimemails, $e);
            }
            if($notificacion['adjunto'] != null){
                // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                    $mailable = new NotificacionEmailCertificadoGenerado($notificacion['asunto'], $path_adjunto, $observaciones_notificacion, []);
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
                    Mail::to($trimemails)->send(new NotificacionEmailCertificadoGenerado($notificacion['asunto'], $path_adjunto, $observaciones_notificacion, []));
                    if(Mail::failures()){
                        array_push($extras['responses'], ['smtp_result' => false]);
                        Log::channel('email')->error('Email fallido por SMTP', [
                            'emails' => $trimemails,
                            'asunto' => $notificacion['asunto'],
                            'adjunto' => $path_adjunto,
                            'observaciones' => $observaciones_notificacion
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
                            'adjunto' => $path_adjunto,
                            'observaciones' => $observaciones_notificacion
                        ]);
                        $message = 'Email enviado exitosamente a través de SMTP. ';
                        $error = null;
                        $status = 'ok';
                        $code = 2;
                    }
                    Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                }
                // Mail::to($trimemails)->send(new NotificacionEmailCertificadoGenerado($notificacion['asunto'], $path_adjunto, $observaciones_notificacion, []));
            }else{
                // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                    $mailable = new NotificacionEmailCertificadoGenerado($notificacion['asunto'], '', $observaciones_notificacion, $links_recetas);
                    // Envía automáticamente con fallback
                    $resultado = $this->sendEmail($trimemails, $mailable);
                    array_push($extras['responses'], ['microsoft_graph_result' => $resultado]);
                    if ($resultado) {
                        $message = 'Email enviado con Microsoft Graph. ';
                        $error = null;
                        $status = 'ok';
                        $code = 3;
                    }else{
                        $message = 'Error al enviar email con Microsoft Graph';
                        $error = $resultado;
                        array_push($errors, 'Error al enviar email con Microsoft Graph: '.json_encode($resultado));
                        $status = 'fail';
                        $code = -5;
                    }
                }else{
                    Mail::to($trimemails)->send(new NotificacionEmailCertificadoGenerado($notificacion['asunto'], '', $observaciones_notificacion, $links_recetas));
                    if(Mail::failures()){
                        array_push($extras['responses'], ['smtp_result' => false]);
                        Log::channel('email')->error('Email fallido por SMTP', [
                            'emails' => $trimemails,
                            'asunto' => $notificacion['asunto'],
                            'observaciones' => $observaciones_notificacion,
                            'links_recetas' => $links_recetas
                        ]);
                        $message = 'Error al enviar email por SMTP. ';
                        $error = Mail::failures();
                        array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                        $status = 'fail';
                        $code = -6;
                    }else{
                        array_push($extras['responses'], ['smtp_result' => true]);
                        Log::channel('email')->info('Email enviado exitosamente a través de SMTP', [
                            'emails' => $trimemails,
                            'asunto' => $notificacion['asunto'],
                            'observaciones' => $observaciones_notificacion,
                            'links_recetas' => $links_recetas
                        ]);
                        $message = 'Email enviado exitosamente por SMTP. ';
                        $error = null;
                        $status = 'ok';
                        $code = 4;
                    }
                    Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                }
                // Mail::to($trimemails)->send(new NotificacionEmailCertificadoGenerado($notificacion['asunto'], '', $observaciones_notificacion, $links_recetas));
            }
            // if(Mail::failures()){
            //     array_push($errors, 'No se pudo enviar el email. '.Mail::failures());
            //     $status = 'fail';
            //     $message = 'No se pudo enviar el email.';
            //     $code = -2;
            // }   
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
                'extras' => $extras,
                'notificacion' => $notificacion
            ];
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
                'extras' => $extras
            ];
        }
    }
}