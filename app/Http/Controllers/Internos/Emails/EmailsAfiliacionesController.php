<?php namespace App\Http\Controllers\Internos\Emails;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use Mail;
use Carbon\Carbon;

use App\Http\Controllers\Internos\Emails\EmailController;
// use App\Traits\SendsEmailsTrait;

use App\Mail\NotificacionEmailAfiliados;
use App\Mail\NotificacionEmailCoberturasEspecialesAfiliado;
use App\Mail\NotificacionEmailCuentaCorriente;
use App\Mail\NotificacionEmailDocumentacionAfiliado;
use App\Mail\NotificacionEmailFactura;
use App\Mail\NotificacionEmailHistoriaClinica;
use App\Mail\NotificacionEmailInformacionAfiliado;
use App\Mail\NotificacionEmailRecibo;
use App\Mail\NotificacionEmailSituacionTerapeutica;

class EmailsAfiliacionesController extends EmailController
{
    // use SendsEmailsTrait;

    public function enviar_email_facturas(Request $request)
    {
        $errors = [];
        $notificacion = $request->input('notificacion');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/afiliaciones/enviar-facturas',
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

        $nro_afiliado = $request->input('nro_afiliado') != null ? $request->input('nro_afiliado') : null;
        $n_persona = $request->input('n_persona') != null ? $request->input('n_persona') : null;
        $nro_doc = $request->input('nro_doc') != null ? $request->input('nro_doc') : null;
        // dd('parametros', $notificacion, $nro_afiliado, $n_persona, $nro_doc);
        $path_adjunto = $notificacion['adjunto'];
        $emails = explode(',', $notificacion['emails']);
        $trimemails = [];
        foreach($emails as $email){
            $e = trim($email);
            array_push($trimemails, $e);
        }
        try {
            // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
            if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                $mailable = new NotificacionEmailFactura($notificacion['asunto'], $path_adjunto);
                // Envía automáticamente con fallback
                // El Mailable adjunta automáticamente en build()
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
                    $status = 'fail';
                    $code = -3;
                }
            }else{
                Mail::to($trimemails)->send(new NotificacionEmailFactura($notificacion['asunto'], $path_adjunto));
                if(Mail::failures()){
                    array_push($extras['responses'], ['smtp_result' => false]);
                    Log::channel('email')->error('Email fallido por SMTP', [
                        'emails' => $trimemails,
                        'asunto' => $notificacion['asunto'],
                        'adjunto' => $path_adjunto
                    ]);
                    $message = 'Error al enviar email por SMTP. ';
                    $error = Mail::failures();
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

            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            $res = $this->registrar_email_enviado($trimemails, 'facturas', $nro_afiliado, $n_persona, $nro_doc);
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
                'persona' => $n_persona,
                'nro_doc' => $nro_doc,
                'nro_afiliado' => $nro_afiliado,
                'emails' => $trimemails,
                'extras' => $extras
            ];
        }
    }

    public function enviar_email_cuenta_corriente(Request $request)
    {
        $errors = [];
        $notificacion = $request->input('notificacion');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/afiliaciones/enviar-cuenta-corriente',
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

        $nro_afiliado = $request->input('nro_afiliado') != null ? $request->input('nro_afiliado') : null;
        $n_persona = $request->input('n_persona') != null ? $request->input('n_persona') : null;
        $nro_doc = $request->input('nro_doc') != null ? $request->input('nro_doc') : null;
        // dd('parametros', $notificacion, $nro_afiliado, $n_persona, $nro_doc);
        $path_adjunto = $notificacion['adjunto'];
        $emails = explode(',', $notificacion['emails']);
        $trimemails = [];
        foreach($emails as $email){
            $e = trim($email);
            array_push($trimemails, $e);
        }
        try {
            // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
            if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                $mailable = new NotificacionEmailCuentaCorriente($notificacion['asunto'], $path_adjunto);
                // Envía automáticamente con fallback
                // El Mailable adjunta automáticamente en build()
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
                    $status = 'fail';
                    $code = -3;
                }
            }else{
                Mail::to($trimemails)->send(new NotificacionEmailCuentaCorriente($notificacion['asunto'], $path_adjunto));
                if(Mail::failures()){
                    array_push($extras['responses'], ['smtp_result' => false]);
                    Log::channel('email')->error('Email fallido por SMTP', [
                        'emails' => $trimemails,
                        'asunto' => $notificacion['asunto'],
                        'adjunto' => $path_adjunto
                    ]);
                    $message = 'Error al enviar email por SMTP. ';
                    $error = Mail::failures();
                    $status = 'fail';
                    $code = -4;
                }else{
                    $message = 'Email enviado por SMTP. ';
                    $error = null;
                    $status = 'ok';
                    $code = 2;
                }
                Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
            }
            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            $res = $this->registrar_email_enviado($trimemails, 'cuenta corriente', $nro_afiliado, $n_persona, $nro_doc);
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
                'persona' => $n_persona,
                'nro_doc' => $nro_doc,
                'nro_afiliado' => $nro_afiliado,
                'emails' => $trimemails,
                'extras' => $extras
            ];
        }
    }

    public function enviar_email_recibos(Request $request)
    {
        $errors = [];
        $notificacion = $request->input('notificacion');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/afiliaciones/enviar-recibos',
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

        $nro_afiliado = $request->input('nro_afiliado') != null ? $request->input('nro_afiliado') : null;
        $n_persona = $request->input('n_persona') != null ? $request->input('n_persona') : null;
        $nro_doc = $request->input('nro_doc') != null ? $request->input('nro_doc') : null;

        $path_adjunto = $notificacion['adjunto'];
        $emails = explode(',', $notificacion['emails']);
        $trimemails = [];
        foreach($emails as $email){
            $e = trim($email);
            array_push($trimemails, $e);
        }
        try {
            // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
            if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                $mailable = new NotificacionEmailRecibo($notificacion['asunto'], $path_adjunto);
                // Envía automáticamente con fallback
                // El Mailable adjunta automáticamente en build()
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
                    $status = 'fail';
                    $code = -3;
                }
            }else{
                Mail::to($trimemails)->send(new NotificacionEmailRecibo($notificacion['asunto'], $path_adjunto));
                if(Mail::failures()){
                    array_push($extras['responses'], ['smtp_result' => false]);
                    Log::channel('email')->error('Email fallido por SMTP', [
                        'emails' => $trimemails,
                        'asunto' => $notificacion['asunto'],
                        'adjunto' => $path_adjunto
                    ]);
                    $message = 'Error al enviar email por SMTP. ';
                    $error = Mail::failures();
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
            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            $res = $this->registrar_email_enviado($trimemails, 'recibos', $nro_afiliado, $n_persona, $nro_doc);
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
                    'extras' => $extras
                ];
            }else{
                array_push($errors,  $res['error']);
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
                'persona' => $n_persona,
                'nro_doc' => $nro_doc,
                'nro_afiliado' => $nro_afiliado,
                'emails' => $trimemails,
                'extras' => $extras
            ];
        }
    }

    public function enviar_email_certificado_afiliacion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/afiliaciones/enviar-certificado-afiliacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => []
        ] ;
        try {
            $notificacion = $request->input('notificacion');

            $status = null;
            $error = null;
            $message = null;
            $res = ['message' => ''];
            $code = null;
            $errors = [];

            $nro_afiliado = $notificacion['nro_afiliado'] != null ? $notificacion['nro_afiliado'] : null;
            $n_persona = $notificacion['n_persona'] != null ? $notificacion['n_persona'] : null;
            $nro_doc = $notificacion['nro_doc'] != null ? $notificacion['nro_doc'] : null;
            // $path_adjunto = $notificacion['adjunto'];
            // Usar storage_path() para la ruta correcta del archivo
            $path_adjunto = storage_path('app/public/reportes/certificados_afiliacion/'.$notificacion['adjunto']);
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
                $mailable = new NotificacionEmailAfiliados($notificacion['asunto'], $path_adjunto);
                // Envía automáticamente con fallback
                // El Mailable adjunta automáticamente el archivo en build()
                $resultado = $this->sendEmail($trimemails, $mailable, [$path_adjunto]);
                array_push($extras['responses'], ['microsoft_graph_result' => $resultado]);
                if ($resultado) {
                    $message = 'Email enviado con Microsoft Graph. ';
                    $error = null;
                    $status = 'ok';
                    $code = 1;
                }else{
                    $message = 'Error al enviar email con Microsoft Graph. ';
                    $error = $resultado;
                    $status = 'fail';
                    $code = -3;
                }
            }else{
                Mail::to($trimemails)->send(new NotificacionEmailAfiliados($notificacion['asunto'], $path_adjunto));
                if(Mail::failures()){
                    array_push($extras['responses'], ['smtp_result' => false]);
                    Log::channel('email')->error('Email fallido por SMTP', [
                        'emails' => $trimemails,
                        'asunto' => $notificacion['asunto'],
                        'adjunto' => $path_adjunto
                    ]);
                    $message = 'Error al enviar email por SMTP. ';
                    $error = Mail::failures();
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
                    $code = -2;
                }
                Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
            }
        
            // Mail::to($trimemails)->send(new NotificacionEmailAfiliados($notificacion['asunto'], $path_adjunto));
            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            $res = $this->registrar_email_enviado($trimemails, 'certificado afiliacion', $nro_afiliado, $n_persona, $nro_doc);
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
                'code' => -1,
                'line' => $th->getLine(),
                'persona' => $n_persona,
                'nro_doc' => $nro_doc,
                'nro_afiliado' => $nro_afiliado,
                'emails' => $trimemails,
                'adjunto' => $notificacion['adjunto'],
                'extras' => $extras
            ];
        }
    }

    public function enviar_email_coberturas_especiales_afiliado(Request $request)
    {
        $errors = [];
        $notificacion = request('notificacion');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/afiliaciones/enviar-coberturas-especiales-afiliado',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => []
        ] ;

        $status = null;
        $error = null;
        $message = null;
        $res = ['message' => ''];
        $code = null;

        $nro_afiliado = $notificacion['nro_afiliado'] != null ? $notificacion['nro_afiliado'] : null;
        $n_persona = $notificacion['n_persona'] != null ? $notificacion['n_persona'] : null;
        $nro_doc = $notificacion['nro_doc'] != null ? $notificacion['nro_doc'] : null;
        $path_adjunto = storage_path('app/public/reportes/coberturas_especiales_afiliado/'.$notificacion['adjunto']);
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
        
        try {
            // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
            if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                $mailable = new NotificacionEmailCoberturasEspecialesAfiliado($notificacion['asunto'], $path_adjunto);
                // Envía automáticamente con fallback
                // El Mailable adjunta automáticamente en build()
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
                    $status = 'fail';
                    $code = -3;
                }
            }else{
                Mail::to($trimemails)->send(new NotificacionEmailCoberturasEspecialesAfiliado($notificacion['asunto'], $path_adjunto));
                if(Mail::failures()){
                    array_push($extras['responses'], ['smtp_result' => false]);
                    Log::channel('email')->error('Email fallido por SMTP', [
                        'emails' => $trimemails,
                        'asunto' => $notificacion['asunto'],
                        'adjunto' => $path_adjunto
                    ]);
                    $message = 'Error al enviar email por SMTP. ';
                    $error = Mail::failures();
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
            $res = $this->registrar_email_enviado($trimemails, 'coberturas especiales', $nro_afiliado, $n_persona, $nro_doc);
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
                'code' => -1,
                'line' => $th->getLine(),
                'persona' => $n_persona,
                'nro_doc' => $nro_doc,
                'nro_afiliado' => $nro_afiliado,
                'emails' => $trimemails,
                'adjunto' => $notificacion['adjunto'],
                'extras' => $extras
            ];
        }
    }

    public function enviar_email_situacion_terapeutica(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/afiliaciones/enviar-situacion-terapeutica-afiliado',
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

        try {
            $notificacion = $request->input('notificacion');
            $errors = [];
            $nro_afiliado = $request->input('nro_afiliado') != null ? $request->input('nro_afiliado') : null;
            $n_persona = $request->input('n_persona') != null ? $request->input('n_persona') : null;
            $nro_doc = $request->input('nro_doc') != null ? $request->input('nro_doc') : null;
            $path_adjunto = storage_path('app/public/reportes/situacion_terapeutica/'.$notificacion['adjunto']);

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
                $mailable = new NotificacionEmailSituacionTerapeutica($notificacion['asunto'], $path_adjunto);
                // Envía automáticamente con fallback
                // El Mailable adjunta automáticamente en build()
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
                    $status = 'fail';
                    $code = -3;
                }
            }else{
                Mail::to($trimemails)->send(new NotificacionEmailSituacionTerapeutica($notificacion['asunto'], $path_adjunto));
                if(Mail::failures()){
                    array_push($extras['responses'], ['smtp_result' => false]);
                    Log::channel('email')->error('Email fallido por SMTP', [
                        'emails' => $trimemails,
                        'asunto' => $notificacion['asunto'],
                        'adjunto' => $path_adjunto
                    ]);
                    $message = 'Error al enviar email por SMTP. ';
                    $error = Mail::failures();
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
            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            $res = $this->registrar_email_enviado($trimemails, 'situacion-terapeutica-afiliado', $nro_afiliado, $notificacion['asunto'], $nro_doc);
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

    public function enviar_email_informacion_afiliados(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/afiliaciones/enviar-informacion-afiliados',
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
        $notificacion = request('notificacion');
        $nro_afiliado = $notificacion['nro_afiliado'] != null ? $notificacion['nro_afiliado'] : null;
        $n_persona = $notificacion['n_persona'] != null ? $notificacion['n_persona'] : null;
        $nro_doc = $notificacion['nro_doc'] != null ? $notificacion['nro_doc'] : null;
        $path_adjunto = storage_path('app/public/reportes/informes_afiliados/'.$notificacion['adjunto']);
        // $path_adjunto = env('STORAGE_PATH').'reportes/informes_afiliados/'.$notificacion['adjunto'];
        $options = $notificacion['options'];
        if(isset($notificacion['options']) && isset($notificacion['options']['tipo'])){
            $tipo = $notificacion['options']['tipo'];
        }else{
            $tipo = 'datos';
        }

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
        
        try {
            // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
            if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                $mailable = new NotificacionEmailInformacionAfiliado($notificacion['asunto'], $path_adjunto);
                // Envía automáticamente con fallback
                // El Mailable adjunta automáticamente en build()
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
                    $status = 'fail';
                    $code = -3;
                }
            }else{
                Mail::to($trimemails)->send(new NotificacionEmailInformacionAfiliado($notificacion['asunto'], $path_adjunto));
                if(Mail::failures()){
                    array_push($extras['responses'], ['smtp_result' => false]);
                    Log::channel('email')->error('Email fallido por SMTP', [
                        'emails' => $trimemails,
                        'asunto' => $notificacion['asunto'],
                        'adjunto' => $path_adjunto
                    ]);
                    $message = 'Error al enviar email por SMTP. ';
                    $error = Mail::failures();
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
            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            $res = $this->registrar_email_enviado($trimemails, 'informacion afiliado', $nro_afiliado, $n_persona, $nro_doc);
            array_push($extras['queries'], $res['queries']);
            if($res['code'] > 0){
                return[
                    'status' => $status,
                    'errors' => $error == null ? [] : [$error],
                    'message' => $message.' Notificación de Información de '.$tipo.' del afiliado enviada con éxito. '.$res['message'],
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
                'code' => -1,
                'line' => $th->getLine(),
                'persona' => $n_persona,
                'nro_doc' => $nro_doc,
                'nro_afiliado' => $nro_afiliado,
                'emails' => $trimemails,
                'adjunto' => $notificacion['adjunto'],
                'extras' => $extras
            ];
        }
    }
    
    public function enviar_email_historia_clinica_afiliado(Request $request)
    {
        $notificacion = request('notificacion');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/afiliaciones/enviar-historia-clinica-afiliado',
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
        $nro_afiliado = $notificacion['nro_afiliado'] != null ? $notificacion['nro_afiliado'] : null;
        $n_persona = $notificacion['n_persona'] != null ? $notificacion['n_persona'] : null;
        $nro_doc = $notificacion['nro_doc'] != null ? $notificacion['nro_doc'] : null;
        // $path_adjunto = env('STORAGE_PATH').'reportes/historias_clinicas/'.$notificacion['adjunto'];
        $path_adjunto = storage_path('app/public/reportes/historias_clinicas/'.$notificacion['adjunto']);
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
        
        try {
            // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
            if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                $mailable = new NotificacionEmailHistoriaClinica($notificacion['asunto'], $path_adjunto);
                // Envía automáticamente con fallback
                // El Mailable adjunta automáticamente en build()
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
                    $status = 'fail';
                    $code = -3;
                }
            }else{
                Mail::to($trimemails)->send(new NotificacionEmailHistoriaClinica($notificacion['asunto'], $path_adjunto));
                if(Mail::failures()){
                    array_push($extras['responses'], ['smtp_result' => false]);
                    Log::channel('email')->error('Email fallido por SMTP', [
                        'emails' => $trimemails,
                        'asunto' => $notificacion['asunto'],
                        'adjunto' => $path_adjunto
                    ]);
                    $message = 'Error al enviar email por SMTP. ';
                    $error = Mail::failures();
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
            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            $res = $this->registrar_email_enviado($trimemails, 'historia clinica afiliado', $nro_afiliado, $n_persona, $nro_doc);
            array_push($extras['queries'], $res['queries']);
            if($res['code'] > 0){
                return[
                    'status' => $status,
                    'errors' => $error == null ? [] : [$error],
                    'message' => $message.' '.$res['message'],
                    'code' => $code,
                    'line' => null,
                    'n_persona' => $n_persona,
                    'nro_doc' => $nro_doc,
                    'nro_afiliado' => $nro_afiliado,
                    'emails' => $trimemails,
                    'adjunto' => $notificacion['adjunto'],
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
                'code' => -1,
                'line' => $th->getLine(),
                'persona' => $n_persona,
                'nro_doc' => $nro_doc,
                'nro_afiliado' => $nro_afiliado,
                'emails' => $trimemails,
                'adjunto' => $notificacion['adjunto'],
                'extras' => $extras
            ];
        }
    }

    public function enviar_email_pedido_documentacion_afiliado(Request $request)
    {
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/afiliaciones/enviar-pedido-documentacion-afiliado',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => []
        ];
        $errors = [];
        $status = null;
        $error = null;
        $message = null;
        $res = ['message' => ''];
        $code = null;

        $notificacion = request('notificacion');
        $data = $notificacion['objeto'];
        $data['fec_nac'] = Carbon::parse($data['fec_nac'])->format('d/m/Y');
        $data['fec_pedido'] = Carbon::parse($data['fec_pedido'])->format('d/m/Y');
        $data['fec_vto'] = Carbon::parse($data['fec_vto'])->format('d/m/Y');      
        $nro_afiliado = $notificacion['nro_afiliado'] != null ? $notificacion['nro_afiliado'] : null;
        $n_persona = $notificacion['n_persona'] != null ? $notificacion['n_persona'] : null;
        $nro_doc = $notificacion['nro_doc'] != null ? $notificacion['nro_doc'] : null;
        // $path_adjunto = env('STORAGE_PATH').'reportes/historias_clinicas/'.$notificacion['adjunto'];
        // $path_adjunto = storage_path('app/public/reportes/historias_clinicas/'.$notificacion['adjunto']);
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
        
        try {
            // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
            if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                $mailable = new NotificacionEmailDocumentacionAfiliado($notificacion['asunto'], $data);
                // Envía automáticamente con fallback
                // El Mailable adjunta automáticamente en build() si es necesario
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
                    $status = 'fail';
                    $code = -3;
                }
            }else{
                Mail::to($trimemails)->send(new NotificacionEmailDocumentacionAfiliado($notificacion['asunto'], $data));
                if(Mail::failures()){
                    array_push($extras['responses'], ['smtp_result' => false]);
                    Log::channel('email')->error('Email fallido por SMTP', [
                        'emails' => $trimemails,
                        'asunto' => $notificacion['asunto'],
                        'data' => $data
                    ]);
                    $message = 'Error al enviar email por SMTP. ';
                    $error = Mail::failures();
                    $status = 'fail';
                    $code = -4;
                }else{
                    array_push($extras['responses'], ['smtp_result' => true]);
                    Log::channel('email')->info('Email enviado exitosamente a través de SMTP', [
                        'emails' => $trimemails,
                        'asunto' => $notificacion['asunto'],
                        'data' => $data
                    ]);
                    $message = 'Email enviado por SMTP. ';
                    $error = null;
                    $status = 'ok';
                    $code = 2;
                }
                Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
            }
            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            $res = $this->registrar_email_enviado($trimemails, 'pedido documentacion afiliado', $nro_afiliado, $n_persona, $nro_doc);
            array_push($extras['queries'], $res['queries']);
            if($res['code'] > 0){
                return[
                    'status' => $status,
                    'errors' => $error == null ? [] : [$error],
                    'message' => $message.' '.$res['message'],
                    'code' => $code,
                    'line' => null,
                    'n_persona' => $n_persona,
                    'nro_doc' => $nro_doc,
                    'nro_afiliado' => $nro_afiliado,
                    'emails' => $trimemails,
                    'adjunto' => $notificacion['adjunto'],
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
                'code' => -1,
                'line' => $th->getLine(),
                'persona' => $n_persona,
                'nro_doc' => $nro_doc,
                'nro_afiliado' => $nro_afiliado,
                'emails' => $trimemails,
                'adjunto' => $notificacion['adjunto'],
                'extras' => $extras
            ];
        }
    }

    
}