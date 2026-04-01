<?php namespace App\Http\Controllers\Internos\Emails;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;
use Illuminate\Support\Facades\Log;

use App\Traits\SendsEmailsTrait;

use File;
use Mail;
use Session;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;

use App\Http\Controllers\ConexionSpController;
use App\Mail\NotificacionEmailAfiliados;

class EmailController extends ConexionSpController
{
    use SendsEmailsTrait;
    /**
     * Registra en la base de datos los datos de los emails enviados
     * @param email Array de strings. Emails de destino. Requerido.
     * @param funcionalidad String. Funcion desde donde se llama a esta función. Requerido. 
     * @param nro_afiliado número de afiliado, puede ser null. Opcional
     * @param n_persona String. Nombre de la persona a la que se envía el email, puede ser null. Opcional
     * @param nro_doc número de documento de la persona a la que se envía el email, puede ser null. Opcional
     * @param parametros Array. Parámetros opcionales: codigo_interno, sucursal y numero de validacion/internacion. Opcional
     */
    protected function registrar_email_enviado($emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc, $parametros = [])
    {
        // dd('params', $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc);
        $codigo_interno = isset($parametros['codigo_interno']) ? $parametros['codigo_interno'] : null;
        $sucursal = isset($parametros['sucursal']) ? $parametros['sucursal'] : null;
        $numero = isset($parametros['numero']) ? $parametros['numero'] : null;
        $id_usuario = isset($parametros['id_usuario']) ? $parametros['id_usuario'] : null;
        $nombre_usuario = isset($parametros['nombre_usuario']) ? $parametros['nombre_usuario'] : null;

        $emails_enviados = 0;
        try {
            foreach($emails as $email){
                $this->params = [
                    'p_fec_envio' => Carbon::now(),
                    'p_id_usuario' => $id_usuario,
                    'p_n_usuario' => $nombre_usuario,
                    'p_email_enviado' => $email,
                    'p_funcionalidad' => $funcionalidad,
                    'p_n_afiliado' => $nro_afiliado,
                    'p_n_persona' => $n_persona,
                    'p_nro_doc' => $nro_doc,
                    'p_codigo_interno' => $codigo_interno,
                    'p_sucursal' => $sucursal,
                    'p_numero' => $numero
                ];
                $mail_enviado = $this->ejecutar_sp_directo('afiliacion', 'sp_envio_email_insert', null);
                $err = '';
                if(is_array($mail_enviado) && isset($mail_enviado['error'])){
                    $err = $mail_enviado['error'];
                }else if(!empty($mail_enviado) && $mail_enviado[0]->id > 0){
                    $emails_enviados++;
                }
            }
            if(sizeof($emails) == $emails_enviados && $err == ''){
                return [
                    'registro_envio_email_response' => $mail_enviado,
                    'code' => 1,
                    'message' => 'Envío registrado con éxito',
                    'error' => null,
                    'queries' => $this->get_query('validacion', 'sp_envio_email_insert', $this->params),
                    'sps' => ['sp_envio_email_insert' => $this->params]
                ];
            }else{
                return [
                    'code' => -1,
                    'message' => 'No se pudo registrar el envío de email',
                    'error' => 'No se pudo registrar todos los emails. '.$err,
                    'queries' => $this->get_query('validacion', 'sp_envio_email_insert', $this->params),
                    'registro_envio_email_response' => $mail_enviado,
                    'sps' => ['sp_envio_email_insert' => $this->params]
                ];
            }
            
        } catch (\Throwable $th) {
            return [
                'code' => -2,
                'message' => 'Error al registrar envío de email en la base de datos.',
                'error' => $th->getMessage().' Line: '.$th->getLine(),
                'queries' => $this->get_query('validacion', 'sp_envio_email_insert', $this->params),
                'registro_envio_email_response' => null,
                'sps' => null
            ];
        }
    }

    /**
     * Genera un certificado de los emails enviados
     */
    public function generar_certificado_emails_enviados(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/generar-certifiado-emails-enviados',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];
        try {
            // obtenemos el usuario de la petición y sus permisos
            // $user = User::with('roles', 'permissions')->find($request->user()->id);
            // $logged_user = $this->get_logged_user($user);

            $this->params = [];
            // variables de respuesta
            $status = 'null';
            $count = -1;
            $errors = [];
            $message = 'sin comentarios';
            $line = null;
            $code = null;
            $data = null;

            $accion = request('accion');
            $codigo_interno = request('codigo_interno');
            $nombre_afiliado = request('nombre_afiliado');
            $nro_afiliado = request('nro_afiliado');

            if($accion == null || $accion == ''){
                $accion = 'mostrar';
            }

            $this->params = [
                'accion' => $accion,
                'codigo_interno' => $codigo_interno,
                'nombre_afiliado' => $nombre_afiliado,
                'nro_afiliado' => $nro_afiliado
            ];

            array_push($extras['queries'], $this->get_query('afiliacion', 'sp_envio_email_select', ['codigo_interno' => $codigo_interno]));
            array_push($extras['sps'], ['sp_envio_email_select' => ['codigo_interno' => $codigo_interno]]);
            $comunicaciones = $this->ejecutar_sp_directo('afiliacion', 'sp_envio_email_select', ['codigo_interno' => $codigo_interno]);
            array_push($extras['responses'], ['sp_envio_email_select' => $comunicaciones]);
            
            $pdf = new Fpdi('portrait', 'mm', 'A4');
            //  font setup
            $font = 'Arial';
            //  -------------------
            $pdf->SetMargins(15, 15);
            $pdf->AddPage('portrait');
            // logo
            $pdf->Image(storage_path('app/public/images/certificado-comunicacion.jpg'), 0, 0, 210, 297, 'JPG');
            $pdf->Cell(180, 70, '', 0, 1); // espacio en blanco para ubicar el texto
            
            $pdf->Cell(180, 3, '', 0, 1); // espacio en blanco entre textos
            $pdf->SetFont($font, '', 12);
            $pdf->Cell(180, 21, utf8_decode('Referencia: '.$nombre_afiliado.' - Afiliado n° '.$nro_afiliado), 0, 2, 'R');
            $pdf->Cell(180, 14, utf8_decode('Por medio del presente se deja constancia de las comunicaciones realizadas:'), 0, 2, 'L');
            
            $pdf->SetFont($font, 'B', 9);
            $pdf->Cell(20, 7, utf8_decode('Solicitud'), 0, 0, 'L'); // solicitud => id_envio_email
            $pdf->Cell(70, 7, utf8_decode('Cuenta'), 0, 0, 'L'); // cuenta => email_enviado
            $pdf->Cell(40, 7, utf8_decode('Fecha Envío'), 0, 0, 'L'); // fecha envio => fec_envio
            $pdf->Cell(25, 7, utf8_decode('Tipo de Envío'), 0, 0, 'L'); // tipo de envio => funcionalidad
            $pdf->Cell(25, 7, utf8_decode('Número'), 0, 1, 'L'); // número => numero formateado con sucursal y numero
            $pdf->Cell(180, 0.01, '', 1, 1, 'C'); // línea

            $pdf->SetFont($font, '', 8);
            foreach($comunicaciones as $comunicacion){
                $pdf->Cell(20, 7, utf8_decode($comunicacion->id_envio_email), 0, 0, 'L'); // solicitud => id_envio_email
                $pdf->Cell(70, 7, utf8_decode($comunicacion->email_enviado), 0, 0, 'L'); // cuenta => email_enviado
                $pdf->Cell(40, 7, utf8_decode($comunicacion->fec_envio), 0, 0, 'L'); // fecha envio => fec_envio
                $pdf->Cell(25, 7, utf8_decode($comunicacion->funcionalidad), 0, 0, 'L'); // tipo de envio => funcionalidad
                $numero_formateado = get_agregar_ceros($comunicacion->sucursal, 3).get_agregar_ceros($comunicacion->numero, 8);
                $pdf->Cell(25, 7, utf8_decode($numero_formateado), 0, 1, 'L'); // número => numero formateado con sucursal y numero
                $pdf->Cell(180, 0.001, '', 1, 1, 'C'); // línea
            }

            if ($accion == 'enviar') {
                $file_path = env('STORAGE_PATH').'reportes/certificados_emails_enviados/';
                $filename = 'certificado-email-enviado.pdf';
                $file = $file_path.$filename;
                $extras['path'] = $file;
                $pdf->Output($file, "F");
                $data = $filename;
                $status = 'ok';
                $count = 1;
                $message = 'Archivo generado satisfactoriamente';
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => 1,
                    'data' => $data,
                    'params' => $this->params,
                    'extras' => $extras,
                    // 'logged_user' => $logged_user != null ? $logged_user : null
                ]);
            }else{
                $file_path = env('STORAGE_PATH').'reportes/certificados_emails_enviados/';
                $filename = 'certificado-email-enviado.pdf';
                $file = $file_path.$filename;
                $pdf->Output($file, "F");
                return FacadeResponse::make(Storage::disk('certificados_emails_enviados')->get($filename), 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; ' . $filename,
                ]);
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Fallo de backend');
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => null,
                'extras' => $extras,
                // 'logged_user' => null
            ]);
        }
    }

    /**
     * Envía un email con el certificado de notificaciones
     */
    public function enviar_email_certificado_emails_enviados(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/enviar-certificado-emails-enviados',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
        ] ;
        try {
            $notificacion = $request->input('notificacion');
            $errors = [];
            $status = null;
            $error = null;
            $message = null;
            $res = ['message' => ''];
            $code = null;

            $nro_afiliado = $notificacion['nro_afiliado'] != null ? $notificacion['nro_afiliado'] : null;
            $n_persona = $notificacion['n_persona'] != null ? $notificacion['n_persona'] : null;
            $nro_doc = $notificacion['nro_doc'] != null ? $notificacion['nro_doc'] : null;
            // $path_adjunto = $notificacion['adjunto'];
            $path_adjunto = env('STORAGE_PATH').'reportes/certificados_emails_enviados/'.$notificacion['adjunto'];
            // return $path_adjunto;
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
                Mail::to($trimemails)->send(new NotificacionEmailAfiliados($notificacion['asunto'], $path_adjunto));
                if(Mail::failures()){
                    array_push($extras['responses'], ['smtp_result' => false]);
                    Log::channel('email')->error('Error al enviar email por SMTP', [
                        'emails' => $trimemails,
                        'asunto' => $notificacion['asunto'],
                        'adjunto' => $path_adjunto,
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
                    ]);
                    $message = 'Email enviado por SMTP. ';
                    $error = null;
                    $status = 'ok';
                    $code = 2;
                }
                Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
            }

            // Mail::to($trimemails)->send(new NotificacionEmailAfiliados($notificacion['asunto'], $path_adjunto));
            // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
            $res = $this->registrar_email_enviado($trimemails, 'certificado notificaciones', $nro_afiliado, $n_persona, $nro_doc);
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
                'line' => $th->getLine(),
                'code' => -1,
                'persona' => $n_persona,
                'nro_doc' => $nro_doc,
                'nro_afiliado' => $nro_afiliado,
                'emails' => $trimemails,
                'adjunto' => $notificacion['adjunto'],
                'extras' => $extras
            ];
        }
    }

    /**
     * Retorna una lista de emails enviados al afiliado
     */
    public function listar_emails_enviados(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/enviar-email/listar-emails-enviados';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_envio_email_select';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario
        $this->verificado = [
            'nro_afiliado' => request('nro_afiliado')
        ];
        if(empty(request('nro_afiliado'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'fecha_desde' => request('fecha_desde'),
            'fecha_hasta' => request('fecha_hasta'),
            'nro_afiliado' => request('nro_afiliado')
        ];
        $this->params_sp = [
            'nro_afiliado' => $this->params['nro_afiliado'],
        ];
        if(request('fecha_desde') != null){
            $this->params_sp['fecha_desde'] = Carbon::parse(request('fecha_desde'))->format('Ymd');
        }
        if(request('fecha_hasta') != null){
            $this->params_sp['fecha_hasta'] = Carbon::parse(request('fecha_hasta'))->format('Ymd');
        }
        return $this->ejecutar_sp_simple();
    }

}
