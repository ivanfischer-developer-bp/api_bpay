<?php namespace App\Http\Controllers\Internos\Emails;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use Mail;

use App\Http\Controllers\Internos\Emails\EmailController;

use App\Mail\NotificacionEmailRegistroUsuarioAfiliado;

class EmailsUsuariosController extends EmailController
{
    public function enviar_email_registro_doctor(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/auth/enviar-email-registro-doctor',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => []
        ];

        $status = null;
        $error = null;
        $message = null;
        $res = ['message' => ''];
        $code = null;
        $errors = [];

        $email = request('email');
        $n_persona = request('apellido').' '.request('nombre');
        $nro_doc = request('nro_doc') != null ? request('nro_doc') : null;
        // dd('parametros', $notificacion, $nro_afiliado, $n_persona, $nro_doc);
        $data = [
            'nombre' => request('nombre'),
            'apellido' => request('apellido'),
            'matricula_tipo' => request('matricula_tipo'),
            'matricula_numero' => request('matricula_numero')
        ];
        try {
            if(env('AMBIENTE') != 'produccion'){
                $asunto = 'Verificación de email (staging)';
            }else{
                $asunto = 'Verificación de email';
            }

            // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
            if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                $mailable = new NotificacionEmailRegistroUsuarioDoctor($asunto, $data);
                // Envía automáticamente con fallback
                $resultado = $this->sendEmail($email, $mailable);
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
                Mail::to($email)->send(new NotificacionEmailRegistroUsuarioDoctor($asunto, $data));
                if(Mail::failures()){
                    array_push($extras['responses'], ['smtp_result' => false]);
                    Log::channel('email')->error('Error al enviar email por SMTP', [
                        'emails' => $email,
                        'asunto' => $asunto,
                        'data' => $data
                    ]);
                    $message = 'Error al enviar email por SMTP. ';
                    $error = Mail::failures();
                    array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                    $status = 'fail';
                    $code = -4;
                }else{
                    array_push($extras['responses'], ['smtp_result' => true]);
                    Log::channel('email')->info('Email enviado exitosamente a través de SMTP', [
                        'emails' => $email,
                        'asunto' => $asunto,
                        'data' => $data
                    ]);
                    $message = 'Email enviado exitosamente por SMTP. ';
                    $error = null;
                    $status = 'ok';
                    $code = 2;
                }
                Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
            }

            // Mail::to($email)->send(new NotificacionEmailRegistroUsuarioDoctor($asunto, $data));
            return[
                'status' => $status,
                'errors' => $error == null ? [] : [$error],
                'message' => $message.' '.$res['message'],
                'code' => $code,
                'line' => null,
                'persona' => $n_persona,
                'nro_doc' => $nro_doc,
                'nro_afiliado' => null,
                'emails' => $email,
                'extras' => $extras           
            ];
            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            // $res = $this->registrar_email_enviado([$email], 'registro_doctor', null, $n_persona, $nro_doc);
            // array_push($extras['queries'], $res['queries']);
            // if($res['code'] > 0){
            //     return[
            //         'status' => 'ok',
            //         'errors' => [],
            //         'message' => 'Notificación enviada con éxito.',
            //         'line' => null,
            //         'code' => 1,
            //         'persona' => $n_persona,
            //         'nro_doc' => $nro_doc,
            //         'nro_afiliado' => null,
            //         'emails' => $email,
            //         'extras' => $extras           
            //     ];
            // }else{
            //     return [
            //         'status' => 'fail',
            //         'errors' => $res['error'],
            //         'message' => $res['message'],
            //         'line' => null,
            //         'code' => -2,
            //         'persona' => $n_persona,
            //         'nro_doc' => $nro_doc,
            //         'nro_afiliado' => null,
            //         'emails' => $email,
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
                'persona' => $n_persona,
                'nro_doc' => $nro_doc,
                'nro_afiliado' => $nro_afiliado,
                'emails' => $trimemails,
                'extras' => $extras
            ];
        }
    }

    public function enviar_email_registro_afiliado(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/auth/enviar-email-registro-afiliado',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => []
        ];
        $n_persona = request('apellido').' '.request('nombre');
        $nro_doc = request('nro_doc') != null ? request('nro_doc') : null;
        $email = request('email');
        
        $data = [
            'nombre' => request('nombre'),
            'apellido' => request('apellido'),
            'enlace' => request('enlace'),
        ];

        $status = null;
        $error = null;
        $message = null;
        $res = ['message' => ''];
        $code = null;
        $errors = [];
        
        try {
            if(env('AMBIENTE') != 'produccion'){
                $asunto = 'Verificación de email (staging)';
            }else{
                $asunto = 'Verificación de email';
            }
            try {
                // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                    $mailable = new NotificacionEmailRegistroUsuarioAfiliado($asunto, $data);
                    // Envía automáticamente con fallback
                    $resultado = $this->sendEmail($email, $mailable);
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
                    Mail::to($email)->send(new NotificacionEmailRegistroUsuarioAfiliado($asunto, $data));
                    if(Mail::failures()){
                        array_push($extras['responses'], ['smtp_result' => false]);
                        Log::channel('email')->error('Error al enviar email por SMTP', [
                            'emails' => $email,
                            'asunto' => $asunto,
                            'data' => $data
                        ]);
                        $message = 'Error al enviar email por SMTP. ';
                        $error = Mail::failures();
                        array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                        $status = 'fail';
                        $code = -4;
                    }else{
                        array_push($extras['responses'], ['smtp_result' => true]);
                        Log::channel('email')->info('Email enviado exitosamente a través de SMTP', [
                            'emails' => $email,
                            'asunto' => $asunto,
                            'data' => $data
                        ]);
                        $message = 'Email enviado exitosamente por SMTP. ';
                        $error = null;
                        $status = 'ok';
                        $code = 2;
                    }
                }
                // Mail::to($email)->send(new NotificacionEmailRegistroUsuarioAfiliado($asunto, $data));
            } catch (\Exception $e) {
                return $e;
            }
            return[
                'status' => $status,
                'errors' => $error == null ? [] : [$error],
                'message' => $message.' '.$res['message'],
                'code' => $code,
                'line' => null,
                'persona' => $n_persona,
                'nro_doc' => $nro_doc,
                'emails' => $email,
                'extras' => $extras           
            ];
            // if (Mail::failures()) {
            // }else{
            //     return[
            //         'status' => 'ok',
            //         'errors' => [],
            //         'message' => 'Notificación enviada con éxito.',
            //         'line' => null,
            //         'code' => 1,
            //         'persona' => $n_persona,
            //         'nro_doc' => $nro_doc,
            //         'emails' => $email,
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
                'persona' => $n_persona,
                'nro_doc' => $nro_doc,
                'emails' => $email,
                'extras' => $extras
            ];
        }
    }
}