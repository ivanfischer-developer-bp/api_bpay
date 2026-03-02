<?php namespace App\Http\Controllers\Internos\Emails;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Mail;
use Carbon\Carbon;

use App\Http\Controllers\Internos\Emails\EmailController;

use App\Mail\NotificacionEmailEncuestaAtencion;

class EmailsEncuestasController extends EmailController
{

    /**
     * Envia un email con la encuesta de atencion al paciente.
     *
     * @param Request $request
     * @return Response
     */
    public function enviar_email_encuesta_atencion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/enviar-email/encuestas/enviar-encuesta-atencion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'sps' => [],
            'responses' => []
        ];

        $status = null;
        $error = null;
        $message = null;
        $res = ['message' => ''];
        $code = null;
        $errors = [];

        try {
            DB::beginTransaction();

            $params = [
                'id_usuario' => request('id_usuario'),
                'email' => request('email'),
                'codigo_interno' => request('codigo_interno'),
                'nombre' => request('nombre'),
                'apellido' => request('apellido'),
                'n_persona' => request('n_persona'),
                'nro_doc' => request('nro_doc'),
                'nro_afiliado' => request('nro_afiliado'),
                'efector' => request('efector'),
                'fecha_consulta' => request('fecha_consulta'),
                'n_empresa' => request('n_empresa'),
                'sucursal' => request('sucursal'),
                'numero' => request('numero'),
                'nombre_usuario' => request('nombre_usuario')
            ];

            $params_sp = [
                'p_fec_envio' => Carbon::now()->format('Y-m-d H:i:s'),
                'p_id_usuario' => request('id_usuario'),
                'p_email_encuesta' => request('email'),
                'p_codigo_interno' => request('codigo_interno'),
            ];
            array_push($extras['sps'], ['sp_envio_encuesta_insert' => $params_sp]);
            array_push($extras['queries'], $this->get_query('validacion', 'sp_envio_encuesta_insert', $params_sp));
            $response = $this->ejecutar_sp_directo('validacion', 'sp_envio_encuesta_insert', $params_sp);
            array_push($extras['responses'], ['sp_envio_encuesta_insert' => $response]);
            // return $response;

            if(is_array($response) && array_key_exists('error', $response)){
                $status = 'fail';
                $message = 'Encuesta NO Generada, email NO enviado';
                $count = 0;
                $code = -9;
                $line = null;
                $data = null;
                $errors = ['Se produjo un error al generar la encuesta, email No enviado'];
            }else{
                $id_encuesta = $response[0]->id; 
                $data = [
                    'nombre' => request('nombre'),
                    'apellido' => request('apellido'),
                    'email' => request('email'),
                    'n_persona' => request('n_persona'),
                    'nro_doc' => request('nro_doc'),
                    'nro_afiliado' => request('nro_afiliado'),
                    'efector' => request('efector'),
                    'fecha_consulta' => Carbon::parse(request('fecha_consulta'))->format('d/m/Y'),
                    'url_no' => env('URL_FRONTEND_PUBLICO').'gracias-por-responder/'.$id_encuesta,
                    'url_encuesta' => env('URL_FRONTEND_PUBLICO').'encuesta-atencion/'.$id_encuesta,
                    'codigo_interno' => request('codigo_interno'),
                    'n_empresa' => request('n_empresa'),
                    'sucursal' => request('sucursal'),
                    'numero' => request('numero'),
                    'id_usuario' => request('id_usuario'),
                    'nombre_usuario' => request('nombre_usuario')
                ];
    
                $parametros = [
                    'codigo_interno' => $data['codigo_interno'],
                    'sucursal' => $data['sucursal'],
                    'numero' => $data['numero'],
                    'id_usuario' => $data['id_usuario'],
                    'nombre_usuario' => $data['nombre_usuario'],
                    'n_empresa' => $data['n_empresa']
                ];
                $nro_afiliado = $data['nro_afiliado'];
                $n_persona = $data['n_persona'];
                $nro_doc = $data['nro_doc'];
    
                $trimemails = [$data['email']];
    
                $status = '';
                $message = '';
                $res = ['message' => ''];
                $errors = [];
                $code = 0;
                $seguir = true;
                $n_empresa = request('n_empresa');

                // if(env('COMPANY_NAME') == 'CASA ASISTENCIAL'){  // para pruebas
                if(env('COMPANY_NAME') == 'LEAL MEDICA'){
                    if ($n_empresa != null) {
                        $fromAddress = config('mail.from.address'); // Dirección de correo configurada
                        $fromName = $n_empresa; // Nombre dinámico
                
                        if(strtoUpper($n_empresa) == 'BRINDAR SALUD ONLINE'){
                            $data['header'] = 'BRINDAR SALUD';
                        }else{
                            $data['header'] = env('HEADER_EMAIL');
                        }
                        
                        // Crear el objeto del correo y establecer el remitente dinámico
                        $email = new NotificacionEmailEncuestaAtencion('Experiencia con tu consulta', $data);
                        $email->from($fromAddress, $fromName);
                
                        // Enviar el correo
                        // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                        if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                            $mailable = new NotificacionEmailEncuestaAtencion('Experiencia con tu consulta', $data);
                            // Configurar remitente dinámico para Microsoft Graph
                            $mailable->from($fromAddress, $fromName);
                            // Envía automáticamente con fallback
                            $resultado = $this->sendEmail($trimemails, $mailable);
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
                                    'asunto' => 'Experiencia con tu consulta',
                                    'from' => $email->from[0]['address'].' ('.$email->from[0]['name'].')',
                                    'data_email' => $data
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
                                    'asunto' => 'Experiencia con tu consulta',
                                    'from' => $email->from[0]['address'].' ('.$email->from[0]['name'].')',
                                    'data_email' => $data
                                ]);
                                $message = 'Email enviado exitosamente a través de SMTP. ';
                                $error = null;
                                $status = 'ok';
                                $code = 5;
                            }
                            Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                        }

                        // Mail::to($trimemails)->send($email);
                        // if(Mail::failures()){
                        //     $seguir = false;
                        //     $status = 'fail';
                        //     $message = 'Error al enviar el correo.';
                        //     $errors = ['Error en el envío del correo. '];
                        //     $code = -3;
                        // }
                    } else {
                        $data = ['header' => env('HEADER_EMAIL')];
                        // Enviar el correo sin modificar el remitente
                        // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                        if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                            $mailable = new NotificacionEmailEncuestaAtencion('Experiencia con tu consulta', $data);
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
                            $fromAddress = config('mail.from.address'); // Dirección de correo configurada
                            $fromName = $n_empresa != null ? $n_empresa : env('HEADER_EMAIL'); // Nombre dinámico
                            $email = new NotificacionEmailEncuestaAtencion('Experiencia con tu consulta', $data);
                            $email->from($fromAddress, $fromName);
                            Mail::to($trimemails)->send($email);
                            if(Mail::failures()){
                                array_push($extras['responses'], ['smtp_result' => false]);
                                Log::channel('email')->error('Error al enviar email por SMTP', [
                                    'emails' => $trimemails,
                                    'asunto' => 'Experiencia con tu consulta',
                                    'from' => $email->from[0]['address'].' ('.$email->from[0]['name'].')',
                                    'data_email' => $data
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
                                    'asunto' => 'Experiencia con tu consulta',
                                    'from' => $email->from[0]['address'].' ('.$email->from[0]['name'].')',
                                    'data_email' => $data
                                ]);
                                $message = 'Email enviado exitosamente a través de SMTP. ';
                                $error = null;
                                $status = 'ok';
                                $code = 3;
                            }
                            Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                        }

                        
                        // Mail::to($trimemails)->send(new NotificacionEmailEncuestaAtencion('Experiencia con tu consulta', $data));
                        // if(Mail::failures()){
                        //     $seguir = false;
                        //     $status = 'fail';
                        //     $message = 'Error al enviar el correo.';
                        //     $errors = ['Error en el envío del correo. '];
                        //     $code = -4;
                        // }
                    }
                }else{
                    $data['header'] = env('HEADER_EMAIL');
                    // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                    if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                        $mailable = new NotificacionEmailEncuestaAtencion('Experiencia con tu consulta', $data);
                        // Envía automáticamente con fallback
                        $resultado = $this->sendEmail($trimemails, $mailable);
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
                        $fromAddress = config('mail.from.address'); // Dirección de correo configurada
                        $fromName = $n_empresa != null ? $n_empresa : env('HEADER_EMAIL'); // Nombre dinámico
                        $email = new NotificacionEmailEncuestaAtencion('Experiencia con tu consulta', $data);
                        $email->from($fromAddress, $fromName);
                        Mail::to($trimemails)->send($email);
                        if(Mail::failures()){
                            array_push($extras['responses'], ['smtp_result' => false]);
                            Log::channel('email')->error('Error al enviar email por SMTP', [
                                'emails' => $trimemails,
                                'asunto' => 'Experiencia con tu consulta',
                                'from' => $email->from[0]['address'].' ('.$email->from[0]['name'].')',
                                'data_email' => $data
                            ]);
                            $seguir = false;
                            $message = 'Error al enviar email por SMTP. ';
                            $error = Mail::failures();
                            array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                            $status = 'fail';
                            $code = -3;
                        }else{
                            array_push($extras['responses'], ['smtp_result' => true]);
                            Log::channel('email')->info('Email enviado exitosamente a través de SMTP', [
                                'emails' => $trimemails,
                                'asunto' => 'Experiencia con tu consulta',
                                'from' => $email->from[0]['address'].' ('.$email->from[0]['name'].')',
                                'data_email' => $data
                            ]);
                            $message = 'Email enviado exitosamente a través de SMTP. ';
                            $error = null;
                            $status = 'ok';
                            $code = 2;
                        }
                        Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                    }
                    // Mail::to($trimemails)->send(new NotificacionEmailEncuestaAtencion('Experiencia con tu consulta', $data));
                    
                    // if(Mail::failures()){
                    //     $seguir = false;
                    //     $status = 'fail';
                    //     $message = 'Error al enviar el correo.';
                    //     $errors = ['Error en el envío del correo. '];
                    //     $code = -2;
                    // }
                }
    
                // Registrar el envío del correo
                if($seguir){
                    // $emails, $funcionalidad, $nro_afiliado, $n_persona, $nro_doc
                    $res = $this->registrar_email_enviado($trimemails, 'encuestas', $nro_afiliado, $n_persona, $nro_doc, $parametros);
                    array_push($extras['queries'], $res['queries']);
                    if($res['code'] > 0){
                        $status = 'ok';
                        $message = 'Notificación enviada con éxito. ';
                        DB::commit(); 
                    }else{
                        DB::rollBack();
                        array_push($errors, $res['error']);
                        $status = 'fail';
                        $message = $message.' Error al registrar el envío del correo.';
                        $code = -2;
                    }
                }
            }

            return [
                'status' => $status,
                'errors' => $errors,
                'message' => $message,
                'code' => $code,
                'line' => null,
                'persona' => $n_persona,
                'nro_doc' => $nro_doc,
                'nro_afiliado' => $nro_afiliado,
                'emails' => $trimemails,
                'adjunto' => null,
                'extras' => $extras,
                'notificacion' => null,
                'parametros' => $parametros,
                'parametros_envio' => $data
            ];
        } catch (\Throwable $th) {
            DB::rollBack();
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
                'notificacion' => null,
                'parametros' => null,
                'parametros_envio' => null
            ];
        }


        // $this->validar_email_encuesta_atencion($request);

        // $datos = [
        //     'nombre' => $request->input('nombre'),
        //     'apellido' => $request->input('apellido'),
        //     'email' => $request->input('email'),
        //     'efector' => $request->input('efector'),
        //     'fecha_consulta' => $request->input('fecha_consulta'),
        //     'url_no' => env('URL_FRONTEND_PUBLICO').'gracias-por-responder',
        //     'url_encuesta' => env('URL_FRONTEND_PUBLICO').'encuesta-atencion'
        // ];

        // $email = new NotificacionEmailEncuestaAtencion('Experiencia con tu consulta', $datos);

        // Mail::to($request->input('email'))->send($email);

        // return response()->json(['message' => 'Encuesta de atención enviada correctamente.'], 200);
    }
}