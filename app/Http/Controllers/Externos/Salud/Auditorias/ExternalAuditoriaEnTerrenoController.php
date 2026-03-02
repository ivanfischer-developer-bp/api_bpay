<?php

namespace App\Http\Controllers\Externos\Salud\Auditorias;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;
use Mail;
use App\Mail\NotificacionEmailAuditoriaEnTerreno;

use App\Models\User;
use App\Http\Controllers\ConexionSpController;

class ExternalAuditoriaEnTerrenoController extends ConexionSpController
{

    /**
     * Actualiza un reporte de auditoria en terreno
     * Consume: pyxis
     * 
     */
    public function actualizar(Request $request)
    {
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_external_logged_user($user);
            //  obtiene los datos
            $auditoria = [
                'codigo_diagnostico' => request('codigo_diagnostico'),
                'codigo_internacion' => request('codigo_internacion'),
                'confirmada' => request('confirmada'),
                'descripcion_diagnostico' => request('descripcion_diagnostico'),
                'edad_afiliado' => request('edad_afiliado'),
                'email_auditor' => request('email_auditor'),
                'email_solicitante' => request('email_solicitante'),
                'fecha_actual' => request('fecha_actual'),
                'habitacion' => request('habitacion'),
                'id_requerimiento' => request('id_requerimiento'),
                'lugar' => request('lugar'),
                'nacimiento_afiliado' => request('nacimiento_afiliado'),
                'nombre_afiliado' => request('nombre_afiliado'),
                'nombre_auditor' => request('nombre_auditor'),
                'numero_afiliado' => request('numero_afiliado'),
                'numero_documento' => request('numero_documento'),
                'numero_internacion' => request('numero_internacion'),
                'observaciones' => request('observaciones'),
                'piso' => request('piso'),
                'progreso' => request('progreso') != null ? request('progreso') : "100",
                'proxima_auditoria' => request('proxima_auditoria'),
                'sector' => request('sector'),
                'sexo' => request('sexo'),
                'tipo_documento' => request('tipo_documento'),
                'tipo_internacion' => request('tipo_internacion'),
            ]; 
            // return $auditoria;
            if($request->user()->hasPermissionTo('actualizar auditorias en terreno')){
                // variables de respuesta
                $status = 'null';
                $message = 'sin comentarios';
                $res = ['message' => ''];
                $count = -1;
                $data = null;
                $code = null;
                $error = '';
                $seguir = true;
                
                //  datos obligatorios
                if (empty($auditoria) 
                    || !isset($auditoria['confirmada']) 
                    || ($auditoria['confirmada'] == 1 
                        && (empty($auditoria['descripcion_diagnostico']) 
                        || empty($auditoria['codigo_diagnostico']))) 
                    || (!empty($auditoria['proxima_auditoria']) 
                        && (empty($auditoria['numero_internacion']) 
                        && empty($auditoria['piso']) 
                        && empty($auditoria['habitacion']) 
                        && empty($auditoria['sector'])
                        && empty($auditoria['lugar']))
                    )){
                    return [
                        'code' => -1, 
                        'message' => 'Complete los campos requeridos.'
                    ];
                }
                //  variable de control
                $auditoria_ok = TRUE;
                $solicitud_ok = ['code' => -5, 'message' => 'fallo en solicitud de auditoria'];
        
                $msg = '';
                $code = -1;
                //  inicia transaccion
                get_begin_transaction('validacion');
        
                $confirmada = round($auditoria['confirmada']);
                //  actualiza los datos
                $ret_auditoria = $this->ejecutar_sp_directo('validacion_externa', 'AWEB_ActualizarAuditoriaTerreno', [
                    'id_requerimiento' => $auditoria['id_requerimiento'],
                    'fecha_actual' => $auditoria['fecha_actual'] != null ? Carbon::parse($auditoria['fecha_actual']) : Carbon::now()->format('Ymd H:i:s'),
                    'piso' => $auditoria['piso'],
                    'habitacion' => $auditoria['habitacion'],
                    'sector' => $auditoria['sector'],
                    'proxima_auditoria' => empty($auditoria['proxima_auditoria']) ? NULL : $auditoria['proxima_auditoria'],
                    'observaciones' => empty($auditoria['observaciones']) ? '' : $auditoria['observaciones'],
                    'id_diagnostico' => !empty($auditoria['codigo_diagnostico']) ? $auditoria['codigo_diagnostico'] : NULL,
                    'progreso' => round($auditoria['progreso']),
                    'confirmada' => round($auditoria['confirmada']),
                    'usuario_genera' => empty($auditoria['email_solicitante']) ? NULL : $auditoria['email_solicitante'],
                    'usuario_auditor' => empty($auditoria['email_auditor']) ? NULL : $auditoria['email_auditor']
                ]);
                Log::channel('external')->info('AWEB_ActualizarAuditoriaTerreno consultado por '.$request->user()->name.' (id '.$request->user()->id.')');
                //  si da error, cambia mensaje de devolucion
                if (!isset($ret_auditoria) || !isset($ret_auditoria[0]) || !isset($ret_auditoria[0]->filas) || $ret_auditoria[0]->filas <= 0) {
                    $auditoria_ok = FALSE;
                    $code = -1;
                    $msg = 'Error al actualizar auditoria en terreno';
                }
        
                //  si tiene fecha de proxima auditoria y la auditoria esta confirmada,
                //  genera la próxima auditoria
                if (!empty($auditoria['proxima_auditoria']) && $auditoria['confirmada'] == 0) {
                    //  establece los parametros para insertar la auditoria en terreno
                    $params = [
                        'codigo_internacion' => $auditoria['codigo_internacion'],
                        'fecha_requerimiento' => $auditoria['proxima_auditoria'],
                        'habitacion' => empty($auditoria['habitacion']) ? NULL : $auditoria['habitacion'],
                        'piso' => empty($auditoria['piso']) ? NULL : $auditoria['piso'],
                        'sector' => empty($auditoria['sector']) ? NULL : $auditoria['sector'],
                        'usuario_auditor' => $auditoria['nombre_auditor'],
                        'usuario_auditor_email' => $auditoria['email_auditor'],
                        'usuario_genera' => $auditoria['email_solicitante'],  
                        'usuario_logueado' => $request->user()->name.' (id '.$request->user()->id.')'
                    ];
        
                    //  ejecuta el metodo y devuelve el resultado
                    $email_data['codigo_diagnostico'] = empty($auditoria['codigo_diagnostico']) ? NULL : $auditoria['codigo_diagnostico'];
                    $email_data['codigo_internacion'] = empty($auditoria['codigo_internacion']) ? NULL : $auditoria['codigo_internacion'];
                    $email_data['descripcion_diagnostico'] = empty($auditoria['descripcion_diagnostico']) ? NULL : $auditoria['descripcion_diagnostico'];
                    $email_data['edad_afiliado'] = empty($auditoria['edad_afiliado']) ? NULL : $auditoria['edad_afiliado'];
                    $email_data['email'] = $auditoria['email_auditor'];
                    $email_data['es_proximo_requerimiento'] = TRUE;
                    $email_data['fecha_proxima_auditoria'] = Carbon::parse($auditoria['proxima_auditoria'])->format('d/m/Y');
                    $email_data['fec_nac'] = empty($auditoria['nacimiento_afiliado']) ? NULL : $auditoria['nacimiento_afiliado'];
                    $email_data['habitacion'] = empty($auditoria['habitacion']) ? NULL : $auditoria['habitacion'];
                    $email_data['id_requerimiento'] = empty($auditoria['id_requerimiento']) ? NULL : $auditoria['id_requerimiento'];
                    $email_data['lugar'] = $auditoria['lugar'];
                    $email_data['nombre_afiliado'] = empty($auditoria['nombre_afiliado']) ? NULL : $auditoria['nombre_afiliado'];
                    $email_data['nro_doc'] = empty($auditoria['numero_documento']) ? NULL : $auditoria['numero_documento'];
                    $email_data['numero_afiliado'] = empty($auditoria['numero_afiliado']) ? NULL : $auditoria['numero_afiliado'];
                    $email_data['numero_internacion'] = empty($auditoria['numero_internacion']) ? NULL : $auditoria['numero_internacion'];
                    $email_data['observaciones'] = $auditoria['observaciones'];
                    $email_data['piso'] = empty($auditoria['piso']) ? NULL : $auditoria['piso'];
                    $email_data['sector'] = empty($auditoria['sector']) ? NULL : $auditoria['sector'];
                    $email_data['sexo'] = empty($auditoria['sexo']) ? NULL : $auditoria['sexo'];
                    $email_data['tipo_doc'] = empty($auditoria['tipo_documento']) ? NULL : $auditoria['tipo_documento'];
                    $email_data['tipo_internacion'] = empty($auditoria['tipo_internacion']) ? NULL : $auditoria['tipo_internacion'];

                    $solicitud_ok = $this->guardar_auditoria($params, $email_data, $logged_user, $auditoria['confirmada']);
                }
                //  si queda confirmada la auditoria, se notifica al solicitante
                if ($auditoria['confirmada'] == 1) {
                    $email = $auditoria['email_solicitante']; 
                    $asunto = 'Auditoría en Terreno # ' . $auditoria['id_requerimiento'] . ' Confirmada';
                    $parametros_mensaje = [
                        'id_requerimiento' => $auditoria['id_requerimiento'],
                        'habitacion' => $auditoria['habitacion'],
                        'mensaje' => '',
                        'piso' => $auditoria['piso'],
                        'prestador' => $auditoria['lugar'],
                        'sector' => $auditoria['sector'],
                        'solicitud' => '',
                        'solicitud_strong' => '',
                        'usuario_auditor' => $auditoria['email_auditor'],
                    ];
                    $tipo = 'actualizar';
                    $solicitud_ok = [
                        'code' => $auditoria['id_requerimiento'],
                        'message' => 'Auditoría en Tereno confirmada'
                    ];
                    try {
                        // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                        if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                            $mailable = new NotificacionEmailAuditoriaEnTerreno($asunto, $tipo, $parametros_mensaje);
                            // Envía automáticamente con fallback
                            $resultado = $this->sendEmail($email, $mailable);
                
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
                            Mail::to($email)->send(new NotificacionEmailAuditoriaEnTerreno($asunto, $tipo, $parametros_mensaje));
                            if(Mail::failures()){
                                Log::channel('email')->info('ExternalAuditoriaEnTerreno actualizar Email fallido por SMTP', [
                                    'emails' => $email,
                                    'asunto' => $notificacion['asunto'],
                                    'tipo' => $tipo,
                                    'parametros_mensaje' => $parametros_mensaje
                                ]);
                                $message = 'Error al enviar email por SMTP. ';
                                $error = Mail::failures();
                                array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                                $status = 'fail';
                                $code = -4;
                            }else{
                                Log::channel('email')->info('ExternalAuditoriaEnTerreno actualizar Email enviado exitosamente a través de SMTP', [
                                    'emails' => $email,
                                    'asunto' => $notificacion['asunto'],
                                    'tipo' => $tipo,
                                    'parametros_mensaje' => $parametros_mensaje
                                ]);
                                $message = 'Email enviado por SMTP. ';
                                $error = null;
                                $status = 'ok';
                                $code = 2;
                            }
                        }
                        // Mail::to($email)->send(new NotificacionEmailAuditoriaEnTerreno($asunto, $tipo, $parametros_mensaje));
                    } catch (\Throwable $th) {
                        $msg = $msg .' - '. $th->getMessage();
                        $code = -5;
                        array_push($errors, 'Error al enviar email: '. $th->getMessage());
                    }
                }
                //  dependiendo del resultado, completa o cancela la transaccion y devuelve mensaje
                if ($auditoria_ok && $solicitud_ok['code'] > 0) {
                    get_commit('validacion');
                    $status = 'ok';
                    $count = 1;
                    $message = $auditoria['confirmada'] == 0 ? 'Auditoría en terreno actualizada correctamente':  'Auditoría en terreno confirmada correctamente'.$message;
                    $data = $data = [
                        'auditoria' => $ret_auditoria,
                        'solicitud' => $solicitud_ok
                    ];
                    // $code = 1;
                } else {
                    get_rollback('validacion');
                    if(!$auditoria_ok){
                        $error = 'auditoria no guardada';
                    }
                    if($solicitud_ok['code'] <= 0){
                        $error = $error.' solicitud fallida.';
                    }
                    $status = 'fail';
                    $count = 1;
                    $message = 'Auditoría en terreno NO actualizada';
                    $data = [
                        'auditoria' => $ret_auditoria,
                        'solicitud' => $solicitud_ok
                    ];
                    $code = -6;
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para ACTUALIZAR AUDITORIA EN TERRENO';
                $count  = 0;
                $code = -2;
                $data = null;
                $error = 'error de permisos';
            }
    
            return response()->json([
                'status' => $status,
                'error' => $error,
                'message' => $message,
                'code' => $code,
                'count' => $count,
                'line' => null,
                'data' => $data,
                'params' => $auditoria,
                'logged_user' => $logged_user,
                'api_software_version' => config('site.external_software_version')
            ]); 
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'fail',
                'error' => 'Backend failed',
                'message' => $th->getMessage(),
                'code' => -1,
                'count' => 0,
                'line' => $th->getLine(),
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'api_software_version' => config('site.external_software_version')
            ]);
        }
    }

    /**
     * Función privada para solicitar o actualizar una auditoría en terreno
     * Consume: pyxis
     */
    private function guardar_auditoria($params, $email_data, $logged_user, $confirmada)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $status = null;
        $error = null;
        $message = null;
        $res = ['message' => ''];
        $code = null;
        //  si falta algun dato, devuelve error de parámetros 
        if (empty($params['codigo_internacion'])
            || empty($params['usuario_genera']) 
            || empty($params['usuario_auditor']) 
            || empty($params['piso']) 
            || empty($params['habitacion']) 
            || empty($params['sector']) 
            || empty($params['fecha_requerimiento']))
            {
            return [
                'code' => -3, 
                'message' => 'Verifique los parámetros'
            ];
        }
        //  devuelve los resultados de la consulta
        if($confirmada == 0){
            $auditoria_terreno = $this->ejecutar_sp_directo('validacion_externa', 'AUD_GenerarRequerimientoAuditoria', $params);
            Log::channel('external')->info('AUD_GenerarRequerimientoAuditoria consultado por '.$params['usuario_logueado']);
            if ($auditoria_terreno) {
                //  si no tiene id_requerimiento es porque hubo un error
                if (empty($auditoria_terreno) || empty($auditoria_terreno[0]->id_requerimiento)) {
                    //  devuelve el resultado
                    return [
                        'code' => -4,
                        'message' => 'No se pudo generar auditoría en terreno',
                        'sentMessage' => 'No se notificó al usuario',
                        'auditoria' => $auditoria_terreno,
                        'params' => $params
                    ];
                } else {
                    $auditoria_terreno = $auditoria_terreno[0];
                    //  establece los datos para enviar el mail
                    $email = $params['usuario_auditor_email'];
                    $asunto = 'Requerimiento de Auditoría en Terreno.';
                    $parametros_mensaje = [
                        'codigo_internacion' => '',
                        'diagnostico' => '',
                        'edad_afiliado' => '',
                        'fec_nac' => '',
                        'habitacion' => '',
                        'id_requerimiento' => '',
                        'mensaje' => '',
                        'nombre_afiliado' => '',
                        'nro_doc' => '',
                        'numero_afiliado' => '',
                        'numero_internacion' => '',
                        'piso' => '',
                        'prestador' => '',
                        'sector' => '',
                        'sexo' => '',
                        'solicitud' => '',
                        'solicitud_strong' => '',
                        'tipo_doc' => '',
                        'tipo_internacion' => '',
                        'url' => '',
                        'usuario_auditor' => '',
                    ];
                    //  dependiendo el tipo de solicitud
                    if ($email_data['es_proximo_requerimiento']) {
                        $parametros_mensaje['solicitud'] = 'Se ha generado un requerimiento de auditoría en terreno para la fecha ';
                        $parametros_mensaje['solicitud_strong'] = $email_data['fecha_proxima_auditoria'];
                    } else {
                        $parametros_mensaje['solicitud'] = 'Tiene una auditoría en terreno pendiente solicitada por';
                        $parametros_mensaje['solicitud_strong'] = $logged_user->name;
                    }

                    $parametros_mensaje['codigo_internacion'] = $email_data['codigo_internacion'];
                    $parametros_mensaje['codigo_diagnostico'] = $email_data['codigo_diagnostico'];
                    $parametros_mensaje['descripcion_diagnostico'] = $email_data['descripcion_diagnostico'];
                    $parametros_mensaje['edad_afiliado'] = $email_data['edad_afiliado'];
                    $parametros_mensaje['fec_nac'] = $email_data['fec_nac'];
                    $parametros_mensaje['habitacion'] = $params['habitacion'];
                    $parametros_mensaje['id_requerimiento'] = $auditoria_terreno->id_requerimiento;
                    $parametros_mensaje['numero_afiliado'] = $email_data['numero_afiliado'];
                    $parametros_mensaje['nombre_afiliado'] = $email_data['nombre_afiliado'];
                    $parametros_mensaje['mensaje'] = $email_data['observaciones'];
                    $parametros_mensaje['nro_doc'] = $email_data['nro_doc'];
                    $parametros_mensaje['numero_internacion'] = $email_data['numero_internacion'];
                    $parametros_mensaje['piso'] = $params['piso'];
                    $parametros_mensaje['prestador'] = $email_data['lugar'];
                    $parametros_mensaje['sector'] = $params['sector'];
                    $parametros_mensaje['sexo'] = $email_data['sexo'];                
                    $parametros_mensaje['tipo_doc'] = $email_data['tipo_doc'];
                    $parametros_mensaje['tipo_internacion'] = $email_data['tipo_internacion'];
                    $parametros_mensaje['usuario'] = $logged_user['name'];
                    
                    // $parametros_mensaje['url'] = get_val_url('auditorias-en-terreno/' . $auditoria_terreno->id_requerimiento);
                    $parametros_mensaje['url'] = '';
                    $tipo = 'solicitud';
                    try {
                        // dd('tipo', $tipo, 'parametros_mensaje', $parametros_mensaje, 'email', $email, 'auditoria-en-terreno', $auditoria_terreno);
                        
                        // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                        if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                            $mailable = new NotificacionEmailAuditoriaEnTerreno($asunto, $tipo, $parametros_mensaje);
                            // Envía automáticamente con fallback
                            $resultado = $this->sendEmail($email, $mailable);
                
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
                            Mail::to($email)->send(new NotificacionEmailAuditoriaEnTerreno($asunto, $tipo, $parametros_mensaje));
                            if(Mail::failures()){
                                Log::channel('email')->info('ExternalAuditoriaEnTerreno guardar_auditoria Email fallido por SMTP', [
                                    'emails' => $email,
                                    'asunto' => $notificacion['asunto'],
                                    'tipo' => $tipo,
                                    'parametros_mensaje' => $parametros_mensaje
                                ]);
                                $message = 'Error al enviar email por SMTP. ';
                                $error = Mail::failures();
                                array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                                $status = 'fail';
                                $code = -4;
                            }else{
                                Log::channel('email')->info('ExternalAuditoriaEnTerreno guardar_auditoria Email enviado exitosamente a través de SMTP', [
                                    'emails' => $email,
                                    'asunto' => $notificacion['asunto'],
                                    'tipo' => $tipo,
                                    'parametros_mensaje' => $parametros_mensaje
                                ]);
                                $message = 'Email enviado por SMTP. ';
                                $error = null;
                                $status = 'ok';
                                $code = 2;
                            }
                            Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                        }
                        // Mail::to($email)->send(new NotificacionEmailAuditoriaEnTerreno($asunto, $tipo, $parametros_mensaje));

                        return[
                            'code' => $auditoria_terreno->id_requerimiento,
                            'message' => 'La auditoría en terreno se generó exitosamente. '.$message,
                            'sentMessage' => 'Usuario notificado',
                            'url' => '',
                        ];
                    } catch (\Throwable $th) {
                        return [
                            'code' => $auditoria_terreno->id_requerimiento,
                            'message' => 'La auditoría en terreno se generó exitosamente. '.$message,
                            'error' => $error,
                            'sentMessage' => 'No se pudo notificar al usuario',
                            'url' => '',
                        ];
                    }
                }
            }
        }
        
        return [
            'code' => -1, 
            'message' => 'No se pudo generar la auditoría en terreno',
            'sentMessage' => 'No se notificó al usuario'
        ];
    }
}