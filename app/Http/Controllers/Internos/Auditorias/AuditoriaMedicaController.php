<?php

namespace App\Http\Controllers\Internos\Auditorias;

use Carbon\Carbon;
use Mail;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use GuzzleHttp\Exception\GuzzleException;

use Pusher\ApiErrorException;
use Pusher\Pusher;
use Pusher\PusherException;

use App\Models\User;
use App\Mail\NotificacionOrdenCompra;
use App\Http\Controllers\ConexionSpController;

class AuditoriaMedicaController extends ConexionSpController
{
    /**
     * Audita una validación
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function auditar_validacion(Request $request)
    {

        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail'; // 'ok', 'fail', 'empty', unauthorized', 'warning'  
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $params_sp = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $permiso_requerido = 'auditar validaciones';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){

                $codigo_interno = request('codigo_interno');
                $resolucion = request('resolucion');
                $prestaciones = request('prestaciones');

                $params = [
                    'codigo_interno' => $codigo_interno,
                    'resolucion' => $resolucion,
                    'prestaciones' => $prestaciones
                ];
                $params_sp = [
                    'usuario' => $user->usuario,
                    'estado' => $resolucion['id_estado'],
                    'resolucion' => isset($resolucion['observaciones']) && $resolucion['observaciones'] != null ? $resolucion['observaciones'] : 'S/O',
                    'codigo_interno' => $codigo_interno,
                    'edicion' => isset($resolucion['edicion']) && $resolucion['edicion'] != null ? $resolucion['edicion'] : 0,
                    'codigo_diagnostico' => isset($resolucion['codigo_nuevo_diagnostico']) && $resolucion['codigo_nuevo_diagnostico'] != null ? $resolucion['codigo_nuevo_diagnostico'] : null,
                ];
                array_push($extras['verificado'], ['AUD_Auditar' => ['resolucion["id_estado"]' => $resolucion['id_estado']]]);
                if ( empty($resolucion['id_estado']) ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    array_push($extras['sps'], ['AUD_Auditar' => $params_sp]);
                    array_push($extras['queries'], $this->get_query('validacion', 'AUD_Auditar', $params_sp));
                    $response = $this->ejecutar_sp_directo('validacion', 'AUD_Auditar', $params_sp);
                    array_push($extras['responses'], ['AUD_Auditar' => $response]);
                    if(is_array($response) && array_key_exists('error', $response)){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -3;
                        // Log::channel('')->error(''); // buscar canales en config/loggin.php
                    }else if(empty($response)){
                        $status = 'empty';
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                        $count = 0;
                        $data = $response;
                        $code = -4;
                        // Log::channel('')->info(''); // buscar canales en config/loggin.php
                    }else{
                        $prestaciones_result = [];
                        foreach($prestaciones as $prestacion){
                            $params_sp = [
                                'id_item' => $prestacion['id_autorizaciones_item'],
                                'cantidad' => $prestacion['cantidad'],
                                'id_estado' => $prestacion['id_estado'],
                                'comentarios' => $prestacion['comentarios'] ? $prestacion['comentarios'] : null,
                                'extra_capita' => $prestacion['extra_capita'] == 1 ? $prestacion['extra_capita'] : 0
                            ];
                            array_push($extras['sps'], ['aud_auditar_item' => $params_sp]);
                            array_push($extras['queries'], $this->get_query('validacion', 'aud_auditar_item', $params_sp));
                            $validacion_item = $this->ejecutar_sp_directo('validacion', 'aud_auditar_item', $params_sp);
                            array_push($extras['responses'], ['aud_auditar_item' => $validacion_item]);
                            array_push($prestaciones_result, $validacion_item);
                        }

                        $params_sp = [
                            'p_id_contrato' => 3, // contrato 3 es de validaciones
                            'p_codigo_interno' => $codigo_interno
                        ];
                        array_push($extras['sps'], ['sp_contrato_usuario_rol_select' => $params_sp]);
                        array_push($extras['queries'], $this->get_query('admin', 'sp_contrato_usuario_rol_select', $params_sp));
                        $usuarios_notificar = $this->ejecutar_sp_directo('admin','sp_contrato_usuario_rol_select', $params_sp);
                        array_push($extras['responses'], ['sp_contrato_usuario_rol_select' => $usuarios_notificar]);
                        
                        $notificados = [];
                        $channel = "notificacion-push";
                        $event = "NotificacionEnviada";
                        $msg = [];

                        if (count($usuarios_notificar) > 0) {
                            $params_sp = [
                                'codigo_interno' => $codigo_interno
                            ];
                            array_push($extras['sps'], ['AWEB_TraerAutorizacionCabecera' => $params_sp]);
                            array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerAutorizacionCabecera', $params_sp));
                            $validacion_cabecera = $this->ejecutar_sp_directo('validacion','AWEB_TraerAutorizacionCabecera', $params_sp);
                            array_push($extras['responses'], ['AWEB_TraerAutorizacionCabecera' => $validacion_cabecera]);

                            array_push($extras['sps'], ['AWEB_TraerEstadosValidaciones' => null]);
                            array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerEstadosValidaciones', null));
                            $estados = $this->ejecutar_sp_directo('validacion','AWEB_TraerEstadosValidaciones', null);
                            array_push($extras['responses'], ['AWEB_TraerEstadosValidaciones' => $estados]);

                            $estadoSelec = '';
                            foreach ($estados as $estado) {
                                if ($estado->id_estado == $resolucion['id_estado']) {
                                    $estadoSelec = $estado->n_estado;
                                }
                            }

                            if (isset($resolucion['observaciones'])) {
                                $mensaje = 'La validación N° ' . $validacion_cabecera[0]->autorizacion . ' está en estado ' . $estadoSelec
                                    . ' y tiene las siguientes observaciones: ' . $resolucion['observaciones'];
                            } else {
                                $mensaje = 'La validación N° ' .  $validacion_cabecera[0]->autorizacion . ' está en estado ' . $estadoSelec
                                    . ' y no tiene observaciones.';
                            }

                            $utiles = [
                                'path_url' => '/validaciones',
                                'codigo' => $codigo_interno
                            ];

                            try {
                                $clientePusher = new Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), array('cluster' => env('PUSHER_APP_CLUSTER', 'us2')));
                            } catch (PusherException $e) {
                                Log::warning("Error al instanciar pusher: " . $e->getMessage());
                                array_push($errors, $e->getMessage());
                            }

                            $notificados = [];
                            
                            foreach ($usuarios_notificar as $usuario) {
                                if ($id_usuario !== $usuario->id_usuario) {
                                    $params_sp_notificar = [
                                        'p_id_contrato' => 3, // validaciones es el contrato 3
                                        'p_id_usuario' => $logged_user['id_usuario_sqlserver'],
                                        'p_texto_notificacion' => $mensaje,
                                        'p_asunto' => "Afiliado ".$validacion_cabecera[0]->nombre_afiliado.' N° '.$validacion_cabecera[0]->afiliado.". Autorización N° " . $validacion_cabecera[0]->autorizacion. ' '.$estadoSelec,
                                        'p_notificado' => 0,
                                        'p_utiles' => json_encode($utiles),
                                        'p_id_tipo_notificacion' => 1
                                    ];
                                    array_push($extras['sps'], ['sp_notificacion_insert' => $params_sp_notificar]);
                                    array_push($extras['queries'], $this->get_query('admin', 'sp_notificacion_insert', $params_sp_notificar));
                                    $notificacion = $this->ejecutar_sp_directo('admin', 'sp_notificacion_insert', $params_sp_notificar);
                                    array_push($extras['responses'], ['sp_notificacion_insert' => $notificacion]);
                                    
                                    $channel = "notificacion-push";
                                    $event = "NotificacionEnviada";
                                    $msg = [
                                        'id_usuario' => $usuario->id_usuario, 
                                        'code' => 1,
                                        'ambito' => env('ENVIRONMENT')
                                    ];

                                    try {
                                        // $channel, $event, $msg
                                        $clientePusher->trigger($channel, $event, $msg);
                                    } catch (PusherException $e) {
                                        Log::warning("Error al desencadenar evento pusher: " . $e->getMessage());
                                        array_push($errors, $e->getMessage());
                                    } catch (GuzzleException $e) {
                                        Log::warning("Error al desencadenar evento guzzle: " . $e->getMessage());
                                        array_push($errors, $e->getMessage());
                                    }
                                    array_push($notificados, $notificacion);
                                }
                            }
                        }

                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = sizeof($response);
                        $data = [
                            'auditado' => $response,
                            'prestaciones' => $prestaciones_result,
                            'pusher' => count($usuarios_notificar) > 0 ? [
                                'channel' => $channel,
                                'event' => $event,
                                'msg' => $msg,
                                'usuarios_notificados' => $notificados
                            ] : null
                        ];
                        $code = 1;
                    }
                }
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => $code,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($permiso_requerido);
                $count  = -1;
                $data = null;
                array_push($errors, 'Error de permisos. '.$message);
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => -2,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }

    }

    /**
     * Pre Autoriza una compra de insumos
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function autorizar_insumos(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/auditorias/autorizar-insumos',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail'; // 'ok', 'fail', 'empty', unauthorized', 'warning'  
        $message = '';
        $res = ['message' => ''];
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $params_sp = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $permiso_requerido = '';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $codigo_interno = request('codigo_interno');
                $params = [
                    'codigo_interno' => $codigo_interno,
                    'numero_afiliado' => request('numero_afiliado'),
                    'nombre_afiliado' => request('nombre_afiliado')
                ];
                $params_sp = [
                    'codigo_interno' => $codigo_interno,
                    'id_usuario' => $id_usuario
                ];
                array_push($extras['verificado'], ['sp_autoriza_insumos' => ['codigo_interno' => request('codigo_interno')]]);
                if ( empty(request('codigo_interno')) ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    $data = [
                        'autoriza_insumos' => '',
                        'email_enviado' => false,
                        'usuarios_notificados' => []
                    ];
                    array_push($extras['sps'], ['sp_autoriza_insumos' => $params_sp]);
                    array_push($extras['queries'], $this->get_query('validacion', 'sp_autoriza_insumos', $params_sp));
                    $response = $this->ejecutar_sp_directo('validacion', 'sp_autoriza_insumos', $params_sp);
                    array_push($extras['responses'], ['sp_autoriza_insumos' => $response]);
                    if(is_array($response) && array_key_exists('error', $response)){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -3;
                        // Log::channel('')->error(''); // buscar canales en config/loggin.php
                    }else if(empty($response)){
                        $status = 'empty';
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                        $count = 0;
                        $data = $response;
                        $code = -4;
                        // Log::channel('')->info(''); // buscar canales en config/loggin.php
                    }else{
                        $data['autoriza_insumos'] = $response[0];
                        $code = 1;
                        $message = 'Se realizó la pre autorización de CX.';
                        $status = 'ok';
                        $error = null;
                        $count = 1;
                        $datos = [
                            'razon_social' => $response[0]->razon_social,
                            'numero_oc' => $response[0]->numero_oc,
                            'nro_cotizacion' => $response[0]->nro_cotizacion,
                            'url_proveedor' => $response[0]->url_proveedor,
                            'numero_afiliado' => $params['numero_afiliado'],
                            'nombre_afiliado' => $params['nombre_afiliado'],
                        ];
                        
                        if($response[0]->email != null){
                            if(env('ENVIRONMENT') == 'casa'){
                                $emails = [
                                    $response[0]->email,
                                    env('CASAFAX')
                                ];
                            }else{
                                $emails = [
                                    $response[0]->email
                                ];
                            }
                        }else{
                            $emails = null;
                        }
                        if($emails != null && sizeof($emails) > 0){
                            return $emails;

                            // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                            if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                                $mailable = new NotificacionOrdenCompra('Notificación de Orden de Compra Emitida', $datos);
                                // Envía automáticamente con fallback
                                $resultado = $this->sendEmail($emails, $mailable);
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
                                    array_push($errors, 'Error al enviar el correo electrónico de notificación de orden de compra.');
                                    $status = 'fail';
                                    $code = -3;
                                }
                            }else{
                                Mail::to($emails)->send(new NotificacionOrdenCompra('Notificación de Orden de Compra Emitida', $datos));
                                if(Mail::failures()){
                                    $error = Mail::failures();
                                    array_push($errors, 'Error al enviar email por SMTP: '.json_encode($error));
                                    array_push($errors, 'Error al enviar el correo electrónico de notificación de orden de compra.');
                                    array_push($extras['responses'], ['smtp_result' => false]);
                                    Log::channel('email')->error('Email fallido por SMTP', [
                                        'emails' => $emails,
                                        'asunto' => 'Notificación de Orden de Compra Emitida',
                                        'datos' => $datos
                                    ]);
                                    $status = 'warning';
                                    $message = ' No se pudo enviar la notificación por correo electrónico.';
                                    $count = 0;
                                    $data['email_enviado'] = false;
                                    $code = -6;
                                }else{
                                    array_push($extras['responses'], ['smtp_result' => true]);
                                    Log::channel('email')->info('Email enviado por SMTP', [
                                        'emails' => $emails,
                                        'asunto' => 'Notificación de Orden de Compra Emitida',
                                        'datos' => $datos
                                    ]);
                                    $data['email_enviado'] = true;
                                    $code = 2;
                                    $message = $message.' Se envió la notificación por correo electrónico.';
                                    $status = 'ok';
                                    $count = 1;
                                }
                                Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                            }
                            // Mail::to($emails)->send(new NotificacionOrdenCompra('Notificación de Orden de Compra Emitida', $datos));
                            // if(Mail::failures()){
                            //     array_push($errors, 'Error al enviar el correo electrónico de notificación de orden de compra.');
                            //     $status = 'warning';
                            //     $message = ' No se pudo enviar la notificación por correo electrónico.';
                            //     $count = 0;
                            //     $data['email_enviado'] = false;
                            //     $code = -6;
                            // }else{
                            //     $data['email_enviado'] = true;
                            //     $code = 2;
                            //     $message = $message.' Se envió la notificación por correo electrónico.';
                            //     $status = 'ok';
                            //     $count = 1;
                            // }
                        }else{
                            array_push($errors, 'No se encontró un email a donde enviar una notificación.');
                            $status = 'warning';
                            $message = 'No se encontró un email a donde enviar una notificación.';
                            $count = 0;
                            $data['email_enviado'] = false;
                            $code = -7;
                        }


                        $params_sp = [
                            'p_id_contrato' => 3, // contrato 3 es de validaciones
                            'p_codigo_interno' => $codigo_interno
                        ];
                        array_push($extras['sps'], ['sp_contrato_usuario_rol_select' => $params_sp]);
                        array_push($extras['queries'], $this->get_query('admin', 'sp_contrato_usuario_rol_select', $params_sp));
                        $usuarios_notificar = $this->ejecutar_sp_directo('admin','sp_contrato_usuario_rol_select', $params_sp);
                        array_push($extras['responses'], ['sp_contrato_usuario_rol_select' => $usuarios_notificar]);
                        
                        if (count($usuarios_notificar) > 0) {
                            $utiles = [
                                'path_url' => '',
                                'codigo' => $codigo_interno
                            ];
                            $id_usuarios = [];
                            foreach ($usuarios_notificar as $usuario) {
                                if ($id_usuario !== $usuario->id_usuario) {
                                    array_push($id_usuarios, $usuario->id_usuario);
                                    $params_notificacion = [
                                        'p_id_contrato' => $usuario->id_contrato,
                                        'p_id_usuario' => $usuario->id_usuario,
                                        'p_texto_notificacion' => 'Se realizó la pre autorización de CX',
                                        'p_asunto' => "Afiliado ".$params['nombre_afiliado'].' N° '.$params['numero_afiliado'].". Orden de Compra N° " . $response[0]->numero_oc,
                                        'p_notificado' => 0,
                                        'p_utiles' => json_encode($utiles),
                                        'p_id_tipo_notificacion' => 1
                                    ];
                                    array_push($extras['sps'], ['sp_notificacion_insert' => $params_notificacion]);
                                    array_push($extras['queries'], $this->get_query('admin', 'sp_notificacion_insert', $params_notificacion));
                                    $notificacion = $this->ejecutar_sp_directo('admin', 'sp_notificacion_insert', $params_notificacion);
                                    array_push($extras['responses'], ['sp_notificacion_insert' => $notificacion]);
                                }
                            }
                            if(sizeof($id_usuarios) > 0){
                                try {
                                    $pusher = new Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), array('cluster' => 'us2'));
                                } catch (\Exception $e) {
                                    Log::channel('pusher')->warning("Error al instanciar pusher: " . $e->getMessage());
                                    array_push($errors, "Error al instanciar pusher: " . $e->getMessage());
                                    $data['usuarios_notificados'] = [];
                                }
                                try {
                                    $pusher->trigger(
                                        "notificacion-push", // channel
                                        "NotificacionEnviada", //event
                                        [
                                            'id_usuarios' => $id_usuarios, 
                                            'code' => 1, 
                                            'ambito' => env('ENVIRONMENT')
                                        ] // message
                                    );
                                    $message = $message.' Se envió la notificación push.';
                                    $count = 1;
                                    $data['usuarios_notificados'] = $id_usuarios;
                                    $code = 3;
                                } catch (PusherException $e) {
                                    Log::channel('pusher')->warning("Error al desencadenar evento pusher: " . $e->getMessage());
                                    array_push($errors, "Error al desencadenar evento pusher: " . $e->getMessage());
                                    $data['usuarios_notificados'] = [];
                                } catch (GuzzleException $e) {
                                    Log::channel('pusher')->warning("Error al desencadenar evento guzzle: " . $e->getMessage());
                                    array_push($errors, "Error al desencadenar evento guzzle: " . $e->getMessage());
                                    $data['usuarios_notificados'] = [];
                                } catch (\Exception $e) {
                                    Log::channel('pusher')->warning("Exception: " . $e->getMessage());
                                    array_push($errors, "Exception: " . $e->getMessage());
                                    $data['usuarios_notificados'] = [];
                                }
                            }
                        }
                    }
                }
                return response()->json([
                    'status' => $status,
                    'errors' => $errors,
                    'message' => $message,
                    'code' => $code,
                    'count' => $count,
                    'line' => null,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($permiso_requerido);
                $count  = -1;
                $data = null;
                array_push($errors, 'Error de permisos. '.$message);
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'errors' => $errors,
                    'message' => $message,
                    'code' => -2,
                    'count' => $count,
                    'line' => null,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'errors' => $errors,
                'message' => $th->getMessage(),
                'code' => -1,
                'count' => -1,
                'line' => $th->getLine(),
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }
}