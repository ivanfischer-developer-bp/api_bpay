<?php namespace App\Http\Controllers\Internos\Emails;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use Mail;

use App\Http\Controllers\Internos\Emails\EmailController;

use App\Mail\NotificacionEmailCoberturasEspeciales;
use App\Mail\NotificacionEmailListaPrestaciones;
use App\Mail\NotificacionEmailSolicitudInformacionPreautorizaciones;
use App\Mail\NotificacionEmailValidaciones;

class EmailsValidacionesController extends EmailController
{

    public function enviar_email_validacion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/validaciones/enviar-validacion',
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
            $notificacion = request('notificacion');
            $nro_afiliado = $notificacion['nro_afiliado'] != null ? $notificacion['nro_afiliado'] : null;
            $n_persona = $notificacion['n_persona'] != null ? $notificacion['n_persona'] : null;
            $nro_doc = $notificacion['nro_doc'] != null ? $notificacion['nro_doc'] : null;
            $observaciones_notificacion = $notificacion['observaciones'] == null ? '' : $notificacion['observaciones'];
            $parametros_envio = request('parametros_envio') != null ? request('parametros_envio') : [];
            $n_empresa = isset($parametros_envio['n_empresa']) ? $parametros_envio['n_empresa'] : null;

            $path_adjunto = env('STORAGE_PATH').'reportes/validaciones/'.$notificacion['adjunto'];
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

            $parametros = [
                'codigo_interno' => isset($parametros_envio['codigo_interno']) ? $parametros_envio['codigo_interno'] : '',
                'sucursal' => isset($parametros_envio['sucursal']) ? $parametros_envio['sucursal'] : '',
                'numero' => isset($parametros_envio['numero']) ? $parametros_envio['numero'] : '',
                'id_usuario' => isset($parametros_envio['id_usuario']) ? $parametros_envio['id_usuario'] : '',
                'nombre_usuario' => isset($parametros_envio['nombre_usuario']) ? $parametros_envio['nombre_usuario'] : '',
                'n_empresa' => isset($n_empresa) ? $n_empresa : ''
            ];
        
            // if(env('COMPANY_NAME') == 'CASA ASISTENCIAL'){  // para pruebas
            if(env('COMPANY_NAME') == 'LEAL MEDICA'){
                if ($n_empresa != null && $n_empresa != '') {
                    $fromAddress = config('mail.from.address'); // Dirección de correo configurada
                    $fromName = $n_empresa; // Nombre dinámico
            
                    if(strtoUpper($n_empresa) == 'BRINDAR SALUD ONLINE'){
                        $datos = ['header' => 'BRINDAR SALUD'];
                    }else{
                        $datos = ['header' => env('HEADER_EMAIL')];
                    }

                    // Crear el objeto del correo y establecer el remitente dinámico
                    $email = new NotificacionEmailValidaciones($notificacion['asunto'], $path_adjunto, $observaciones_notificacion, $datos);
                    $email->from($fromAddress, $fromName);
            
                    // Enviar el correo
                    // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                        if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                            $mailable = new NotificacionEmailValidaciones($notificacion['asunto'], $path_adjunto, $observaciones_notificacion, $datos);
                            // Configurar remitente dinámico para Microsoft Graph
                            $mailable->from($fromAddress, $fromName);
                            // Envía automáticamente con fallback
                            $resultado = $this->sendEmail($trimemails, $mailable, [$path_adjunto]);
                            array_push($extras['responses'], ['microsoft_graph_result' => $resultado]);
                            if ($resultado) {
                                $message = 'Email enviado con Microsoft Graph. ';
                                $error = null;
                                $status = 'ok';
                                $code = 6;
                            }else{
                                $seguir = false;
                                $message = 'Error al enviar email con Microsoft Graph';
                                $error = $resultado;
                                array_push($errors, 'Error al enviar email con Microsoft Graph: '.json_encode($resultado));
                                $status = 'fail';
                                $code = -8;
                            }
                        }else{
                            Mail::to($trimemails)->send($email);
                            if(Mail::failures()){
                                array_push($extras['responses'], ['smtp_result' => false]);
                                Log::channel('email')->error('Error al enviar email por SMTP', [
                                    'emails' => $trimemails,
                                    'asunto' => $notificacion['asunto'],
                                    'adjunto' => $path_adjunto,
                                    'observaciones' => $observaciones_notificacion,
                                    'parametros_envio' => $parametros_envio
                                ]);
                                $seguir = false;
                                $message = 'Error al enviar email por SMTP. ';
                                $error = Mail::failures();
                                array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                                $status = 'fail';
                                $code = -7;
                            }else{
                                array_push($extras['responses'], ['smtp_result' => true]);
                                Log::channel('email')->info('Email enviado exitosamente a través de SMTP', [
                                    'emails' => $trimemails,
                                    'asunto' => $notificacion['asunto'],
                                    'adjunto' => $path_adjunto,
                                    'observaciones' => $observaciones_notificacion,
                                    'parametros_envio' => $parametros_envio
                                ]);
                                $message = 'Email enviado exitosamente por SMTP. ';
                                $error = null;
                                $status = 'ok';
                                $code = 5;
                            }
                            Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                        }
                    // Mail::to($trimemails)->send($email);
                } else {
                    $datos = ['header' => env('HEADER_EMAIL')];
                    // Enviar el correo sin modificar el remitente
                    // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                        if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                            $mailable = new NotificacionEmailValidaciones($notificacion['asunto'], $path_adjunto, $observaciones_notificacion, $datos);
                            // Envía automáticamente con fallback
                            $resultado = $this->sendEmail($trimemails, $mailable);
                            array_push($extras['responses'], ['microsoft_graph_result' => $resultado]);
                            if ($resultado) {
                                $message = 'Email enviado con Microsoft Graph. ';
                                $error = null;
                                $status = 'ok';
                                $code = 4;
                            }else{
                                $seguir = false;
                                $message = 'Error al enviar email con Microsoft Graph';
                                $error = $resultado;
                                array_push($errors, 'Error al enviar email con Microsoft Graph: '.json_encode($resultado));
                                $status = 'fail';
                                $code = -6;
                            }
                        }else{
                            Mail::to($trimemails)->send(new NotificacionEmailValidaciones($notificacion['asunto'], $path_adjunto, $observaciones_notificacion, $datos));
                            if(Mail::failures()){
                                array_push($extras['responses'], ['smtp_result' => false]);
                                Log::channel('email')->error('Error al enviar email por SMTP', [
                                    'emails' => $trimemails,
                                    'asunto' => $notificacion['asunto'],
                                    'adjunto' => $path_adjunto,
                                    'observaciones' => $observaciones_notificacion,
                                    'parametros_envio' => $parametros_envio
                                ]);
                                $seguir = false;
                                $message = 'Error al enviar email por SMTP. ';
                                $error = Mail::failures();
                                array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                                $status = 'fail';
                                $code = -5;
                            }else{
                                array_push($extras['responses'], ['smtp_result' => true]);
                                Log::channel('email')->info('Email enviado exitosamente a través de SMTP', [
                                    'emails' => $trimemails,
                                    'asunto' => $notificacion['asunto'],
                                    'adjunto' => $path_adjunto,
                                    'observaciones' => $observaciones_notificacion,
                                    'parametros_envio' => $parametros_envio
                                ]);
                                $message = 'Email enviado exitosamente por SMTP. ';
                                $error = null;
                                $status = 'ok';
                                $code = 3;
                            }
                            Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                        }
                    // Mail::to($trimemails)->send(new NotificacionEmailValidaciones($notificacion['asunto'], $path_adjunto, $observaciones_notificacion, $datos));
                }
            }else{
                $datos = ['header' => env('HEADER_EMAIL')];
                // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                    $mailable = new NotificacionEmailValidaciones($notificacion['asunto'], $path_adjunto, $observaciones_notificacion, $datos);
                    // Envía automáticamente con fallback
                    $resultado = $this->sendEmail($trimemails, $mailable, [$path_adjunto]);
                    array_push($extras['responses'], ['microsoft_graph_result' => $resultado]);
                    if ($resultado) {
                        $message = 'Email enviado con Microsoft Graph. ';
                        $error = null;
                        $status = 'ok';
                        $code = 1;
                    }else{
                        $seguir = false;
                        $message = 'Error al enviar email con Microsoft Graph';
                        $error = $resultado;
                        array_push($errors, 'Error al enviar email con Microsoft Graph: '.json_encode($resultado));
                        $status = 'fail';
                        $code = -4;
                    }
                }else{
                    Mail::to($trimemails)->send(new NotificacionEmailValidaciones($notificacion['asunto'], $path_adjunto, $observaciones_notificacion, $datos));
                    if(Mail::failures()){
                        $seguir = false;
                        array_push($extras['responses'], ['smtp_result' => false]);
                        Log::channel('email')->error('Error al enviar email por SMTP', [
                            'emails' => $trimemails,
                            'asunto' => $notificacion['asunto'],
                            'adjunto' => $path_adjunto,
                            'observaciones' => $observaciones_notificacion,
                            'parametros_envio' => $parametros_envio
                        ]);
                        $message = 'Error al enviar email por SMTP. ';
                        $error = Mail::failures();
                        array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                        $status = 'fail';
                        $code = -3;
                    }else{
                        array_push($extras['responses'], ['smtp_result' => true]);
                        Log::channel('email')->info('Email enviado exitosamente a través de SMTP', [
                            'emails' => $trimemails,
                            'asunto' => $notificacion['asunto'],
                            'adjunto' => $path_adjunto,
                            'observaciones' => $observaciones_notificacion,
                            'parametros_envio' => $parametros_envio
                        ]);
                        $message = 'Email enviado exitosamente por SMTP. ';
                        $error = null;
                        $status = 'ok';
                        $code = 2;
                    }
                    Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                }
            }

            // Registrar el envío del correo
            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            $res = $this->registrar_email_enviado($trimemails, 'validaciones', $nro_afiliado, $n_persona, $nro_doc, $parametros);
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
                    'extras' => $extras,
                    'notificacion' => $notificacion,
                    'parametros' => $parametros,
                    'parametros_envio' => $parametros_envio
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
                    'extras' => $extras,
                    'notificacion' => $notificacion,
                    'parametros' => $parametros,
                    'parametros_envio' => $parametros_envio
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
                'extras' => $extras,
                // 'notificacion' => $notificacion,
                // 'parametros' => $parametros,
                // 'parametros_envio' => $parametros_envio
            ];
        }
    }

    public function enviar_email_lista_prestaciones(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/validaciones/enviar-lista-prestaciones',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => []
        ];
        
        try {
            $notificacion = request('notificacion');

            $status = null;
            $error = null;
            $message = null;
            $res = ['message' => ''];
            $code = null;
            $errors = [];
            
            $nro_afiliado = $notificacion['nro_afiliado'] != null ? $notificacion['nro_afiliado'] : null;
            $n_persona = $notificacion['n_persona'] != null ? $notificacion['n_persona'] : null;
            $nro_doc = $notificacion['nro_doc'] != null ? $notificacion['nro_doc'] : null;
            $path_adjunto = env('STORAGE_PATH').'reportes/prestaciones/'.$notificacion['adjunto'];

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

            // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
            if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                $mailable = new NotificacionEmailListaPrestaciones($notificacion['asunto'], $path_adjunto);
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
                Mail::to($trimemails)->send(new NotificacionEmailListaPrestaciones($notificacion['asunto'], $path_adjunto));
                if(Mail::failures()){
                    array_push($extras['responses'], ['smtp_result' => false]);
                    Log::channel('email')->error('Error al enviar email por SMTP', [
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
                    $message = 'Email enviado por SMTP. ';
                    $error = null;
                    $status = 'ok';
                    $code = 2;
                }
                Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
            }
            // Mail::to($trimemails)->send(new NotificacionEmailListaPrestaciones($notificacion['asunto'], $path_adjunto));

            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            $res = $this->registrar_email_enviado($trimemails, 'validaciones', $nro_afiliado, $notificacion['asunto'], $nro_doc);
            
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
            return [
                'status' => 'fail',
                'errors' => $res['error'],
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

    public function enviar_email_solicitud_informacion_preautorizacion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/validaciones/enviar-solicitud-informacion-preautorizacion',
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
            $notificacion = request('notificacion');
            $nro_afiliado = $notificacion['nro_afiliado'] != null ? $notificacion['nro_afiliado'] : null;
            $n_persona = $notificacion['n_persona'] != null ? $notificacion['n_persona'] : null;
            $nro_doc = $notificacion['nro_doc'] != null ? $notificacion['nro_doc'] : null;
            $observaciones_notificacion = $notificacion['observaciones'] == null ? '' : $notificacion['observaciones'];
            $parametros_envio = request('parametros_envio') != null ? request('parametros_envio') : [];
            $n_empresa = isset($parametros_envio['n_empresa']) ? $parametros_envio['n_empresa'] : null;

            $path_adjunto = null;
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

            $parametros = [
            //     'codigo_interno' => isset($parametros_envio['codigo_interno']) ? $parametros_envio['codigo_interno'] : '',
            //     'sucursal' => isset($parametros_envio['sucursal']) ? $parametros_envio['sucursal'] : '',
            //     'numero' => isset($parametros_envio['numero']) ? $parametros_envio['numero'] : '',
            //     'id_usuario' => isset($parametros_envio['id_usuario']) ? $parametros_envio['id_usuario'] : '',
            //     'nombre_usuario' => isset($parametros_envio['nombre_usuario']) ? $parametros_envio['nombre_usuario'] : '',
            //     'n_empresa' => isset($n_empresa) ? $n_empresa : ''
            ];
        
            // if(env('COMPANY_NAME') == 'CASA ASISTENCIAL'){  // para pruebas
            if(env('COMPANY_NAME') == 'LEAL MEDICA'){
                if ($n_empresa != null && $n_empresa != '') {
                    $fromAddress = config('mail.from.address'); // Dirección de correo configurada
                    $fromName = $n_empresa; // Nombre dinámico
            
                    if(strtoUpper($n_empresa) == 'BRINDAR SALUD ONLINE'){
                        $datos = ['header' => 'BRINDAR SALUD'];
                    }else{
                        $datos = ['header' => env('HEADER_EMAIL')];
                    }

                    // Crear el objeto del correo y establecer el remitente dinámico
                    $email = new NotificacionEmailSolicitudInformacionPreautorizaciones($notificacion['asunto'], $observaciones_notificacion, $datos);
                    $email->from($fromAddress, $fromName);
            
                    // Enviar el correo
                    // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                    if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                        $mailable = new NotificacionEmailSolicitudInformacionPreautorizaciones($notificacion['asunto'], $observaciones_notificacion, $datos);
                        // Configurar remitente dinámico para Microsoft Graph
                        $mailable->from($fromAddress, $fromName);
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
                        Mail::to($trimemails)->send($email);
                        if(Mail::failures()){
                            array_push($extras['responses'], ['smtp_result' => false]);
                            Log::channel('email')->error('Error al enviar email por SMTP', [
                                'emails' => $trimemails,
                                'asunto' => $notificacion['asunto'],
                                'observaciones' => $observaciones_notificacion,
                                'datos' => $datos,
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
                                'observaciones' => $observaciones_notificacion,
                                'datos' => $datos,
                            ]);
                            $message = 'Email enviado exitosamente por SMTP. ';
                            $error = null;
                            $status = 'ok';
                            $code = 2;
                        }
                        Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                    }
                    // Mail::to($trimemails)->send($email);
                } else {
                    $datos = ['header' => env('HEADER_EMAIL')];
                    // Enviar el correo sin modificar el remitente
                    // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                    if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                        $mailable = new NotificacionEmailSolicitudInformacionPreautorizaciones($notificacion['asunto'], $observaciones_notificacion, $datos);
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
                        Mail::to($trimemails)->send(new NotificacionEmailSolicitudInformacionPreautorizaciones($notificacion['asunto'], $observaciones_notificacion, $datos));
                        if(Mail::failures()){
                            array_push($extras['responses'], ['smtp_result' => false]);
                            Log::channel('email')->error('Error al enviar email por SMTP', [
                                'emails' => $trimemails,
                                'asunto' => $notificacion['asunto'],
                                'observaciones' => $observaciones_notificacion,
                                'datos' => $datos,
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
                                'datos' => $datos,
                            ]);
                            $message = 'Email enviado exitosamente a través de SMTP. ';
                            $error = null;
                            $status = 'ok';
                            $code = 4;
                        }
                        Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                    }
                    // Mail::to($trimemails)->send(new NotificacionEmailSolicitudInformacionPreautorizaciones($notificacion['asunto'], $observaciones_notificacion, $datos));
                }
            }else{
                $datos = ['header' => env('HEADER_EMAIL')];
                // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                    $mailable = new NotificacionEmailSolicitudInformacionPreautorizaciones($notificacion['asunto'], $observaciones_notificacion, $datos);
                    // Envía automáticamente con fallback
                    $resultado = $this->sendEmail($trimemails, $mailable);
                    array_push($extras['responses'], ['microsoft_graph_result' => $resultado]);
                    if ($resultado) {
                        $message = 'Email enviado con Microsoft Graph. ';
                        $error = null;
                        $status = 'ok';
                        $code = 5;
                    }else{
                        $message = 'Error al enviar email con Microsoft Graph';
                        $error = $resultado;
                        array_push($errors, 'Error al enviar email con Microsoft Graph: '.json_encode($resultado));
                        $status = 'fail';
                        $code = -7;
                    }
                }else{
                    Mail::to($trimemails)->send(new NotificacionEmailSolicitudInformacionPreautorizaciones($notificacion['asunto'], $observaciones_notificacion, $datos));
                    if(Mail::failures()){
                        array_push($extras['responses'], ['smtp_result' => false]);
                        Log::channel('email')->error('Error al enviar email por SMTP', [
                            'emails' => $trimemails,
                            'asunto' => $notificacion['asunto'],
                            'observaciones' => $observaciones_notificacion,
                            'datos' => $datos,
                        ]);
                        $message = 'Error al enviar email por SMTP. ';
                        $error = Mail::failures();
                        array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                        $status = 'fail';
                        $code = -8;
                    }else{
                        array_push($extras['responses'], ['smtp_result' => true]);
                        Log::channel('email')->info('Email enviado exitosamente a través de SMTP', [
                            'emails' => $trimemails,
                            'asunto' => $notificacion['asunto'],
                            'observaciones' => $observaciones_notificacion,
                            'datos' => $datos,
                        ]);
                        $message = 'Email enviado exitosamente a través de SMTP. ';
                        $error = null;
                        $status = 'ok';
                        $code = 6;
                    }
                    Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                }
                // Mail::to($trimemails)->send(new NotificacionEmailSolicitudInformacionPreautorizaciones($notificacion['asunto'], $observaciones_notificacion, $datos));
            }

            
            // Registrar el envío del correo
            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            // $res = $this->registrar_email_enviado($trimemails, 'validaciones', $nro_afiliado, $n_persona, $nro_doc, $parametros);
            // array_push($extras['queries'], $res['queries']);
            $res['code'] = 1; // forzar exito
            
            if($res['code'] <= 0){
                array_push($errors, $res['error']);
            }
            
            return [
                'status' => $status,
                'errors' => $errors,
                'message' => $message.' '.$res['message'],
                'code' => $code,
                'line' => null,
                'persona' => $n_persona,
                'nro_doc' => $nro_doc,
                'nro_afiliado' => $nro_afiliado,
                'emails' => $trimemails,
                'adjunto' => $notificacion['adjunto'],
                'extras' => $extras,
                'notificacion' => $notificacion,
                'parametros' => $parametros,
                'parametros_envio' => $parametros_envio
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
                'adjunto' => null,
                'extras' => $extras,
                // 'notificacion' => $notificacion,
                // 'parametros' => $parametros,
                // 'parametros_envio' => $parametros_envio
            ];
        }
    }
}