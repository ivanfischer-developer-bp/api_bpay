<?php namespace App\Http\Controllers\Internos\Emails;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use Mail;

use App\Http\Controllers\Internos\Emails\EmailController;

use App\Mail\NotificacionEmailListadoTurnosMedicos;

class EmailsConsultorioController extends EmailController
{
    public function enviar_email_listado_turnos_medicos(Request $request)
    {
        $notificacion = request('notificacion');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/consultorio/enviar-listado-turnos-medicos',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
        ] ;

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
        $path_adjunto = storage_path('app/public/reportes/listados_turnos_medicos/'.$notificacion['adjunto']);
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
        $medico = $n_persona;
        try {
            // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
            if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                $mailable = new NotificacionEmailListadoTurnosMedicos($notificacion['asunto'], $path_adjunto, $medico);
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
                Mail::to($trimemails)->send(new NotificacionEmailListadoTurnosMedicos($notificacion['asunto'], $path_adjunto, $medico));
                if(Mail::failures()){
                    array_push($extras['responses'], ['smtp_result' => false]);
                    Log::channel('email')->error('Error al enviar email por SMTP', [
                        'emails' => $trimemails,
                        'asunto' => $notificacion['asunto'],
                        'adjunto' => $path_adjunto,         
                        'medico' => $medico,         
                    ]);
                    $message = 'Error al enviar email por SMTP. ';
                    $error = Mail::failures();
                    array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                    $status = 'fail';
                    $code = -4;
                }else{
                    array_push($extras['responses'], ['smtp_result' => true]);
                    Log::channel('email')->info('Email enviado por SMTP', [
                        'emails' => $trimemails,
                        'asunto' => $notificacion['asunto'],
                        'adjunto' => $path_adjunto,
                        'medico' => $medico,
                    ]);
                    $message = 'Email enviado por SMTP. ';
                    $error = null;
                    $status = 'ok';
                    $code = 2;
                }
                Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
            }
            // Mail::to($trimemails)->send(new NotificacionEmailListadoTurnosMedicos($notificacion['asunto'], $path_adjunto, $medico));
            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            $res = $this->registrar_email_enviado($trimemails, 'listado turnos medicos', $nro_afiliado, $n_persona, $nro_doc);
            array_push($extras['queries'], $res['queries']);
            if($res['code'] > 0){
                return[
                    'status' => $status,
                    'errors' => $error == null ? [] : [$error],
                    'message' => 'Notificación de Listado de turnos médicos enviada con éxito. '.$message.' '.$res['message'],
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