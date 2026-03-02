<?php

namespace App\Http\Controllers\Internos\Auditorias;

use Carbon\Carbon;
use Mail;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use App\Models\User;
use App\Mail\NotificacionEmailAuditoriaEnTerreno;
use App\Http\Controllers\ConexionSpController;
use App\Http\Controllers\Internos\Internaciones\InternacionController;

class AuditoriaTerrenoController extends ConexionSpController
{
    /**
     * Busca una auditoria en terreno por su id de requerimiento
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function buscar_auditoria_terreno(Request $request)
    {
        
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/auditorias/buscar-auditoria-terreno';
        $this->permiso_requerido = '';
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'AWEB_TraerAuditoriaTerreno';
        $id_requerimiento = request('id_requerimiento');
        if(!is_numeric($id_requerimiento)){
            $id_requerimiento = intval($id_requerimiento);
        }
        $this->params = [
            'id_requerimiento' => $id_requerimiento
        ];
        $this->params_sp = [
            'id_requerimiento' => $this->params['id_requerimiento'],
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Crea una nueva solicitud de auditoria en terreno
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function solicitar_auditoria_terreno(Request $request)
    {
        $this->extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/auditorias/solicitar-auditoria-terreno',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'sps' => [],
            'responses' => [],
            'verificado' => $this->verificado
        ];
        $status = 'fail';
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($this->user_id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        $this->permiso_requerido = 'solicitar auditoria en terreno';
        try {
            if($user->hasPermissionTo($this->permiso_requerido)){
                $solicitud = request('solicitud');
                $this->params = [
                    'solicitud' => $solicitud
                ];
                $this->verificado = [
                    'codigo_internacion' => $solicitud['internacion']['codigo_internacion'],
                    'id_usuario' => $solicitud['usuario']['id_usuario'],
                    'id_usuario' => $solicitud['usuario']['apynom'],
                    'id_usuario' => $solicitud['usuario']['email'],
                    'sector' => $solicitud['sector'],
                    'piso' => $solicitud['piso'],
                    'habitacion' => $solicitud['habitacion'],
                ];
                $this->extras['verificado'] = $this->verificado;
                if(
                    empty($solicitud['internacion']['codigo_internacion'])
                    || empty($solicitud['usuario']['id_usuario'])
                    || empty($solicitud['usuario']['apynom'])
                    || empty($solicitud['usuario']['email'])
                    || empty($solicitud['sector'])
                    || empty($solicitud['piso'])
                    || empty($solicitud['habitacion'])
                ){
                    array_push($this->errors, 'Parmámetros incompletos o incorrectos.');
                    $this->response = [
                        'status' => 'fail',
                        'count' => 0,
                        'errors' => $errors,
                        'message' => 'Verifique los parámetros.',
                        'line' => null,
                        'code' => -5,
                        'data' => $data,
                        'params' => $this->params,
                        'extras' => $this->extras,
                        'logged_user' => $logged_user,
                    ];
                    return response()->json($this->response); 
                }
                    
                array_push($this->extras['sps'], ['AWEB_TraerInternacion' => ['codigo_internacion' => $solicitud['internacion']['codigo_internacion']]]);
                array_push($this->extras['queries'], $this->get_query('validacion', 'AWEB_TraerInternacion', ['codigo_internacion' => $solicitud['internacion']['codigo_internacion']]));
                $internacion = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerInternacion', ['codigo_internacion' => $solicitud['internacion']['codigo_internacion']]);
                array_push($this->extras['responses'], ['AWEB_TraerInternacion' => $internacion]);
                
                $confirmada = 0;

                $params_sp = [
                    'codigo_internacion' => $solicitud['internacion']['codigo_internacion'],
                    'fecha_requerimiento' => Carbon::now(),
                    'usuario_auditor' => $solicitud['usuario']['apynom'],
                    'usuario_auditor_email' => $solicitud['usuario']['email'],
                    'usuario_genera' => $logged_user['name'],
                    'habitacion' => empty($solicitud['habitacion']) ? NULL : $solicitud['habitacion'],
                    'piso' => empty($solicitud['piso']) ? NULL : $solicitud['piso'],
                    'sector' => empty($solicitud['sector']) ? NULL : $solicitud['sector'],
                ];

                $email_data['efector'] = $internacion[0]->efector;
                $email_data['mensaje'] = $solicitud['mensaje'];
                $email_data['es_proximo_requerimiento'] = FALSE;
                $email_data['numero_afiliado'] = empty($solicitud['afiliado']['numero']) ? NULL : $solicitud['afiliado']['numero'];
                $email_data['nombre_afiliado'] = empty($solicitud['afiliado']['nombre']) ? NULL : $solicitud['afiliado']['nombre'];
                $email_data['sexo'] = empty($solicitud['afiliado']['sexo']) ? NULL : $solicitud['afiliado']['sexo'];
                $email_data['fec_nac'] = empty($solicitud['afiliado']['fec_nac']) ? NULL : $solicitud['afiliado']['fec_nac'];
                $email_data['edad'] = empty($solicitud['afiliado']['edad']) ? NULL : $solicitud['afiliado']['edad'];
                $email_data['tipo_doc'] = empty($solicitud['afiliado']['tipo_doc']) ? NULL : $solicitud['afiliado']['tipo_doc'];
                $email_data['nro_doc'] = empty($solicitud['afiliado']['nro_doc']) ? NULL : $solicitud['afiliado']['nro_doc'];
                $email_data['numero_internacion'] = empty($solicitud['internacion']['numero']) ? NULL : $solicitud['internacion']['numero'];
                $email_data['tipo_internacion'] = empty($solicitud['internacion']['tipo']) ? NULL : $solicitud['internacion']['tipo'];
                $email_data['descripcion_diagnostico'] = empty($solicitud['diagnostico']['descripcion']) ? NULL : $solicitud['diagnostico']['descripcion'];
                $email_data['codigo_diagnostico'] = empty($solicitud['diagnostico']['codigo']) ? NULL : $solicitud['diagnostico']['codigo'];
                $email_data['codigo_internacion'] = empty($solicitud['internacion']['codigo_internacion']) ? NULL : $solicitud['internacion']['codigo_internacion'];

                // datos neesarios para notificacion pusher
                $pusher_data['id_usuario_destino'] = $solicitud['usuario']['id_usuario'];
                $pusher_data['nombre_usuario_destino'] = $solicitud['usuario']['apynom'];

                $response = $this->guardar_auditoria($confirmada, $params_sp, $email_data, $pusher_data, null);
                array_push($this->extras['responses'], ['guardar_auditoria' => $response]);
                
                if(is_array($response) && array_key_exists('error', $response) && $response['error'] != null){
                    array_push($this->errors, $response['error']);
                    $status = 'fail';
                    $message = 'Se produjo un error al realizar la petición';
                    $count = 0;
                    $data = null;
                    $code = -3;
                }else if(empty($response)){
                    $status = 'empty';
                    $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                    $count = 0;
                    $data = $response;
                    $code = -4;
                }else{
                    $status = 'ok';
                    $message = 'Transacción realizada con éxito.';
                    $count = 1;
                    $data = $response;
                    $code = 1;
                }
                $this->response = [
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => $code,
                    'data' => $data,
                    'params' => $this->params,
                    'extras' => $this->extras,
                    'logged_user' => $logged_user,
                ];
                return response()->json($this->response); 
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($this->permiso_requerido);
                $count  = -1;
                $data = null;
                array_push($this->errors, 'Error de permisos. '.$message);
                // retorna el response
                $this->response = [
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => -2,
                    'data' => $data,
                    'params' => $this->params,
                    'extras' => $this->extras,
                    'logged_user' => $logged_user,
                ];
                return response()->json($this->response); 
            }
        } catch (\Throwable $th) {
            array_push($this->errors, 'Line: '.$th->getLine().' de ConexionSpController.'.' Error: '.$th->getMessage());
            $this->response = [
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $this->params,
                'extras' => $this->extras,
                'logged_user' => $logged_user,
            ];
            return response()->json($this->response);
        }
    }

    /**
     * Actualiza una auditoría en terreno
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function actualizar_auditoria_terreno(Request $request)
    {
        $this->extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/auditorias/actualizar-auditoria-terreno',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'sps' => [],
            'responses' => [],
            'verificado' => $this->verificado
        ];
        $status = 'fail';
        $message = '';
        $res = ['message' => ''];
        $count = 1;
        $code = null;
        $data = [];
        $errors = [];
        $error = null;
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($this->user_id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        $this->permiso_requerido = 'actualizar auditorias en terreno';
        try {
            if($user->hasPermissionTo($this->permiso_requerido)){
                $auditoria = request('auditoria');
                $id_requerimiento = request('id_requerimiento');
                $this->params = [
                    'auditoria' => $auditoria,
                    'id_requerimiento' => $id_requerimiento
                ];
                $this->verificado = [
                    'confirmada' => $auditoria['confirmada'],
                    'diagnostico->cod_cie_item' => $auditoria['diagnostico']['cod_cie_item'],
                    'fecha_proxima_auditoria' => $auditoria['fecha_proxima_auditoria'],
                    'codigo_internacion' => $auditoria['codigo_internacion'],
                    'sector' => $auditoria['sector'],
                    'piso' => $auditoria['piso'],
                    'habitacion' => $auditoria['habitacion']
                ];
                $this->extras['verificado'] = $this->verificado;
                
                if(empty($auditoria) 
                    || !isset($auditoria['confirmada']) 
                    || (
                        $auditoria['confirmada'] == 1 
                        && (empty($auditoria['diagnostico']) 
                            || empty($auditoria['diagnostico']['cod_cie_item'])
                            )
                        ) 
                    || (!empty($auditoria['fecha_proxima_auditoria']) 
                        && (empty($auditoria['codigo_internacion']) 
                            || empty($auditoria['sector']) 
                            || empty($auditoria['piso']) 
                            || empty($auditoria['habitacion']) 
                        // || empty($auditoria['id_plantilla']) 
                            // || empty($auditoria['proxima_auditoria'])
                            )
                        )
                    ){
                    array_push($this->errors, 'Parmámetros incompletos o incorrectos.');
                    $this->response = [
                        'status' => 'fail',
                        'count' => 0,
                        'errors' => $errors,
                        'message' => 'Verifique los parámetros.',
                        'line' => null,
                        'code' => -5,
                        'data' => $data,
                        'params' => $this->params,
                        'extras' => $this->extras,
                        'logged_user' => $logged_user,
                    ];
                    return response()->json($this->response); 
                }
                
                $auditoria_ok = TRUE;
                $campos_ok = TRUE;
                $actualizar_ok = ['code' => 1];
                $usuario_auditor = $auditoria['usuario_auditor']; // 'SVEGA';      //  harcodeado USUARIO por el momento no puede ser un ID
                // $auditoria['codigo_diagnostico'] = $auditoria['diagnostico']['cod_cie_item'];  // en teoria no son necesarios porque no se usan 
                // $auditoria['descripcion_diagnostico'] = $auditoria['diagnostico']['n_cie_item']; // en teoria no son necesarios porque no se usan 

                get_begin_transaction('validacion');

                $confirmada = round($auditoria['confirmada']);
                // prueba de confirmacion
                // if($confirmada == 1){
                //     $this->response = [
                //         'status' => 'ok',
                //         'count' => 0,
                //         'errors' => null,
                //         'message' => 'Prueba deAuditoría Confirmada',
                //         'line' => null,
                //         'code' => 2,
                //         'data' => null,
                //         'params' => null,
                //         'extras' => null,
                //         'logged_user' => null,
                //     ];
                //     return response()->json($this->response); 
                // }

                $params_auditoria = [
                    'id_requerimiento' => $id_requerimiento,
                    'fecha_actual' => Carbon::now()->format('Ymd H:i:s'),
                    'piso' => $auditoria['piso'],
                    'habitacion' => $auditoria['habitacion'],
                    'sector' => $auditoria['sector'],
                    'proxima_auditoria' => empty($auditoria['fecha_proxima_auditoria']) ? NULL : Carbon::parse($auditoria['fecha_proxima_auditoria'])->format('Ymd'),
                    'observaciones' => empty($auditoria['observaciones']) ? '' : $auditoria['observaciones'],
                    'id_diagnostico' => !empty($auditoria['diagnostico']['cod_cie_item']) ? $auditoria['diagnostico']['cod_cie_item'] : NULL,
                    'progreso' => round($auditoria['progreso']),
                    'confirmada' => $confirmada,
                    'usuario_genera' => $auditoria['usuario_genera'],
                    'usuario_auditor' => $usuario_auditor
                ];
                array_push($this->extras['sps'], ['AWEB_ActualizarAuditoriaTerreno' => $params_auditoria]);
                array_push($this->extras['queries'], $this->get_query('validacion', 'AWEB_ActualizarAuditoriaTerreno', $params_auditoria));
                $response = $this->ejecutar_sp_directo('validacion', 'AWEB_ActualizarAuditoriaTerreno', $params_auditoria);
                array_push($this->extras['responses'], ['AWEB_ActualizarAuditoriaTerreno' => $response]);
                
                if (!isset($response) || !isset($response[0]) || !isset($response[0]->filas) || $response[0]->filas <= 0) {
                    $auditoria_ok = FALSE;
                    $code = -3;
                    $message = 'Error al actualizar auditoria en terreno';
                    array_push($this->errors, 'Error al actualizar auditoria en terreno');
                }

                if (!empty($auditoria['fecha_proxima_auditoria']) && $auditoria['confirmada'] == 0) {
                    //  establece los parametros para insertar la auditoria en terreno
                    $params = [
                        // 'adjunto' => isset($auditoria['adjunto']) ? $auditoria['adjunto'] : null,
                        // 'codigo_diagnostico' => $auditoria['diagnostico']['cod_cie_item'],
                        'codigo_internacion' => $auditoria['codigo_internacion'], //
                        // 'fecha_egreso' => isset($auditoria['fecha_egreso']) ? $auditoria['fecha_egreso'] : null,
                        'fecha_requerimiento' => Carbon::now(),  //
                        'habitacion' => empty($auditoria['habitacion']) ? NULL : $auditoria['habitacion'],  //
                        // 'id_requerimiento' => $id_requerimiento,
                        // 'observaciones' => empty($auditoria['observaciones']) ? '' : $auditoria['observaciones'],
                        'piso' => empty($auditoria['piso']) ? NULL : $auditoria['piso'],  //
                        'sector' => empty($auditoria['sector']) ? NULL : $auditoria['sector'], //
                        // 'tipo_egreso' => $auditoria['tipo_egreso'],
                        'usuario_auditor' => $usuario_auditor,  // 
                        'usuario_auditor_email' => $auditoria['mail_destino'],  //
                        'usuario_genera' => $logged_user['usuario'],   // 
                    ];
                    if(isset($auditoria['fecha_egreso']) && $auditoria['fecha_egreso'] != null){
                        $params['fecha_egreso'] = $auditoria['fecha_egreso'];
                    }
                    if(isset($auditoria['tipo_egreso']) && $auditoria['tipo_egreso'] != null){
                        $params['tipo_egreso'] = $auditoria['tipo_egreso'];
                    }
        
                    //  ejecuta el metodo y devuelve el resultado
                    $email_data['numero_afiliado'] = empty($auditoria['numero_afiliado']) ? NULL : $auditoria['numero_afiliado'];
                    $email_data['codigo_diagnostico'] = empty($auditoria['diagnostico']['cod_cie_item']) ? NULL : $auditoria['diagnostico']['cod_cie_item'];
                    $email_data['codigo_internacion'] = empty($auditoria['codigo_internacion']) ? NULL : $auditoria['codigo_internacion'];
                    $email_data['descripcion_diagnostico'] = empty($auditoria['diagnostico']['n_cie_item']) ? NULL : $auditoria['diagnostico']['n_cie_item'];
                    $email_data['edad'] = empty($auditoria['edad_afiliado']) ? NULL : $auditoria['edad_afiliado'];
                    $email_data['efector'] = $auditoria['lugar'];
                    $email_data['es_proximo_requerimiento'] = TRUE;
                    $email_data['fecha_proxima_auditoria'] = Carbon::parse($auditoria['fecha_proxima_auditoria'])->format('d/m/Y');
                    $email_data['fec_nac'] = empty($auditoria['nacimiento_afiliado']) ? NULL : $auditoria['nacimiento_afiliado'];
                    $email_data['sexo'] = empty($auditoria['sexo']) ? NULL : $auditoria['sexo'];
                    $email_data['nombre_afiliado'] = empty($auditoria['nombre']) ? NULL : $auditoria['nombre'];
                    $email_data['nro_doc'] = empty($auditoria['numero_documento']) ? NULL : $auditoria['numero_documento'];
                    $email_data['numero_internacion'] = empty($auditoria['numero_internacion']) ? NULL : $auditoria['numero_internacion'];
                    $email_data['tipo_doc'] = empty($auditoria['tipo_documento']) ? NULL : $auditoria['tipo_documento'];
                    $email_data['tipo_internacion'] = empty($auditoria['tipo_internacion']) ? NULL : $auditoria['tipo_internacion'];
                    $email_data['mensaje'] = empty($auditoria['observaciones']) ? NULL : $auditoria['observaciones'];
        
                    $pusher_data['id_usuario_destino'] = null;
                    $pusher_data['nombre_usuario_destino'] = $auditoria['usuario_auditor'];
        
                    // dd('$params, $email_data,', $params, $email_data);
                    $actualizar_ok = $this->guardar_auditoria($confirmada, $params, $email_data, $pusher_data, $id_requerimiento);
                    array_push($this->extras['responses'], ['guardar_auditoria' => $actualizar_ok]);
                }

                if ($auditoria_ok && $actualizar_ok['code'] > 0) {
                    
                    //  si queda confirmada la auditoria, se notifica al solicitante
                    if ($auditoria['confirmada'] == 1) {
                        $this->verificado = $this->verificado + [
                            'codigo_internacion' => $auditoria['codigo_internacion'],
                            'tipo_egreso' => $auditoria['tipo_egreso'],
                            'fecha_egreso' => $auditoria['fecha_egreso'],
                            'diagnostico->cod_cie_item' => $auditoria['diagnostico']['cod_cie_item'],
                        ];
                        
                        if (empty($auditoria['codigo_internacion']) 
                            || empty($auditoria['tipo_egreso'])
                            || empty($auditoria['fecha_egreso'])
                            || empty($auditoria['diagnostico']['cod_cie_item'])
                            ){
                            get_rollback('validacion');
                            array_push($this->errors, 'Parmámetros incompletos o incorrectos.');
                            $this->response = [
                                'status' => 'fail',
                                'count' => 0,
                                'errors' => $errors,
                                'message' => 'Verifique los parámetros.',
                                'line' => null,
                                'code' => -6,
                                'data' => $data,
                                'params' => $this->params,
                                'extras' => $this->extras,
                                'logged_user' => $logged_user,
                            ];
                            return response()->json($this->response); 
                        }
                        get_commit('validacion');
                        //  establece los parametros
                        $datos_internacion = [
                            'id_tipo_accion' => 4,
                            'observaciones' => ( !empty($auditoria['observaciones']) ? $auditoria['observaciones'] : NULL ),
                            'Comentarios' => (isset($auditoria['observaciones_validacion']) && $auditoria['observaciones_validacion'] != '' ? $auditoria['observaciones_validacion'] : NULL),
                            'Comentario_Afiliado' => (isset($auditoria['observaciones_afiliado']) && $auditoria['observaciones_afiliado'] != '' ? $auditoria['observaciones_afiliado'] : NULL),
                            'Comentario_Efector' => (isset($auditoria['observaciones_efector']) && $auditoria['observaciones_efector'] != '' ? $auditoria['observaciones_efector'] : NULL),
                            'adjunto' => ( !empty($auditoria['adjunto']) ? $auditoria['adjunto'] : NULL ),
                            'id_tipo_alta_internacion' => $auditoria['tipo_egreso'],
                            'fecha_egreso' => $auditoria['fecha_egreso'],
                            'codigo_diagnostico_egreso' => $auditoria['diagnostico']['cod_cie_item'],
                        ];

                        //  emite la validacion y devuelve el resultado
                        $egreso_internacion = InternacionController::emitir_internacion($auditoria['codigo_internacion'], $datos_internacion, 'egreso-internacion', 'egreso-internacion', $logged_user, $this);
                        
                        array_push($this->extras['responses'], ['InternacionController->emitir_internacion' => $egreso_internacion]);
                        $ret = (array) json_decode(json_encode($egreso_internacion));
                        $resp = $ret['original'];
                        // return $resp;
                        $errors = $errors + $resp->errors;
                        // $params = $params + $resp->params;
                        $this->extras['responses'] = $this->extras['responses'] + $resp->extras->responses;
                        $this->extras['sps'] = $this->extras['sps'] + $resp->extras->sps;
                        $this->extras['queries'] = $this->extras['queries'] + $resp->extras->queries;
                        $this->extras['verificado'] = $this->extras['verificado'] + $resp->extras->verificado;
                        $data = $data + $resp->data;
                        $message = $resp->message;
                        // esto devuelve lo siguiente
                        // [
                        //     {
                        //         estado: number,
                        //         codigo_interno: string,
                        //         id_sucursal: number,
                        //         autorizacion: number
                        //     }
                        // ]
                        
                        if(empty($resp) || $resp->code < 0){
                            get_rollback('validacion');
                            $status = 'fail';
                            $code = $resp->code;
                            $data = $data + [
                                'actualizacion' => $actualizar_ok,
                                'egreso_internacion' => $resp
                            ];
                            array_push($this->errors, 'Error en el egreso de internación');
                            $this->response = [
                                'status' => $status,
                                'count' => 0,
                                'errors' => $errors,
                                'message' => 'No se pudo realizar el egreso de la internación ni la confirmación. '.$resp->message,
                                'line' => null,
                                'code' => $code,
                                'data' => $data,
                                'params' => $this->params,
                                'extras' => $this->extras,
                                'logged_user' => $logged_user,
                            ];
                            return response()->json($this->response); 
                        }
        
                        if($resp->data['validacion_emitida'][0]->estado == 1){
                            $email = $auditoria['mail_destino']; 
                            $asunto = 'Auditoría en Terreno # ' . $id_requerimiento . ' Confirmada';
                            $parametros_mensaje = [
                                'habitacion' => $auditoria['habitacion'],
                                'id_requerimiento' => $id_requerimiento,
                                'mensaje' => '',
                                'piso' => $auditoria['piso'],
                                'prestador' => $auditoria['lugar'],
                                'sector' => $auditoria['sector'],
                                'solicitud' => '',
                                'solicitud_strong' => '',
                                'url' => '', // oh_val_url('auditorias-en-terreno/' . $id_requerimiento),
                                'usuario_auditor' => $usuario_auditor,
                            ];
                            $tipo = 'actualizar';
                            if(isset($email)){
                                try {
                                    // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                                    if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                                        $mailable = new NotificacionEmailAuditoriaEnTerreno($asunto, $tipo, $parametros_mensaje);
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
                                            array_push($this->errors, 'Error al enviar email con Microsoft Graph: '.json_encode($resultado));
                                            $status = 'fail';
                                            $code = -3;
                                        }
                                    }else{
                                        Mail::to($email)->send(new NotificacionEmailAuditoriaEnTerreno($asunto, $tipo, $parametros_mensaje));
                                        if(Mail::failures()){
                                            array_push($extras['responses'], ['smtp_result' => false]);
                                            Log::channel('email')->error('AuditoriaTerreno actualizar_auditoria_terreno Error al enviar email por SMTP', [
                                                'email' => $email,
                                                'asunto' => $asunto,
                                                'tipo' => $tipo,
                                                'parametros_mensaje' => $parametros_mensaje,
                                            ]);
                                            $message = 'Error al enviar email por SMTP. ';
                                            $error = Mail::failures();
                                            array_push($this->errors, 'Error al enviar email por SMTP: '.json_encode($error));
                                            $status = 'fail';
                                            $code = -4;
                                        }else{
                                            array_push($extras['responses'], ['smtp_result' => true]);
                                            Log::channel('email')->info('AuditoriaTerreno actualizar_auditoria_terreno Email enviado por SMTP', [
                                                'email' => $email,
                                                'asunto' => $asunto,
                                                'tipo' => $tipo,
                                                'parametros_mensaje' => $parametros_mensaje,
                                            ]);
                                            $message = 'Email enviado por SMTP. ';
                                            $error = null;
                                            $status = 'ok';
                                            $code = 2;
                                        }
                                        Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                                    }
                                    // Mail::to($email)->send(new NotificacionEmailAuditoriaEnTerreno($asunto, $tipo, $parametros_mensaje));
                                } catch (\Throwable $th) {
                                    array_push($this->errors, 'Line: '. $th->getLine().' - Error Mail: '.$th->getMessage());
                                }
                            }
                            $code = 1; 
                            $message = 'Auditoría en terreno confirmada';
                            $status = 'ok';
                            $data = $data + [
                                'actualizacion' => $actualizar_ok,
                                'egreso_internacion' => $egreso_internacion
                            ];
                        }else{
                            get_rollback('validacion');
                            $code = -7;
                            $status = 'fail';
                            array_push($this->errors, 'No se pudo realizar el egreso de la internacion ni la confirmación');
                            $message = 'No se pudo realizar el egreso de la internación ni la confirmación';
                            $data = $data + [
                                'actualizacion' => $actualizar_ok,
                                'egreso_internacion' => $egreso_internacion
                            ];
                        }
        
                    }else{
                        get_commit('validacion');
                        $status = 'ok';
                        $code = 2;
                        $message = 'Auditoría en terreno actualizada. '.$message;
                        $data = $data + [
                            'actualizacion' => $actualizar_ok,
                        ];
                    }

                } else {
                    get_rollback('validacion');
                    $code = -8;
                    $status = 'fail'; 
                    $message = 'No se pudo actualizar la auditoría en terreno';
                    array_push($this->errors, $actualizar_ok['error']);
                    $data = [
                        'actualizacion' => $actualizar_ok,
                        'egreso_internacion' => null
                    ];
                }

                $this->response = [
                    'status' => $status,
                    'errors' => $errors,
                    'message' => $message,
                    'code' => $code,
                    'count' => $count,
                    'line' => null,
                    'data' => $data,
                    'params' => $this->params,
                    'extras' => $this->extras,
                    'logged_user' => $logged_user,
                ];
                return response()->json($this->response); 
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($this->permiso_requerido);
                $count  = -1;
                $data = null;
                array_push($this->errors, 'Error de permisos. '.$message);
                // retorna el response
                $this->response = [
                    'status' => $status,
                    'errors' => $errors,
                    'message' => $message,
                    'count' => $count,
                    'code' => -2,
                    'line' => null,
                    'data' => $data,
                    'params' => $this->params,
                    'extras' => $this->extras,
                    'logged_user' => $logged_user,
                ];
                return response()->json($this->response); 
            }
        } catch (\Throwable $th) {
            array_push($this->errors, 'Line: '.$th->getLine().' de ConexionSpController.'.' Error: '.$th->getMessage());
            $this->response = [
                'status' => 'fail',
                'errors' => $errors,
                'message' => $th->getMessage(),
                'code' => -1,
                'count' => -1,
                'line' => $th->getLine(),
                'data' => null,
                'params' => $this->params,
                'extras' => $this->extras,
                'logged_user' => $logged_user,
            ];
            return response()->json($this->response);
        }
    }

    /**
     * Guardar una auditoría en terreno
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    private function guardar_auditoria($confirmada, $params, $email_data, $pusher_data, $id_requerimiento)
    {
        if($confirmada == 0){
            $status = null;
            $error = null;
            $message = null;
            $res = ['message' => ''];
            $code = null;
            array_push($this->extras['sps'], ['AUD_GenerarRequerimientoAuditoria' => $params]);
            array_push($this->extras['queries'], $this->get_query('validacion', 'AUD_GenerarRequerimientoAuditoria', $params));
            $auditoria_terreno = $this->ejecutar_sp_directo('validacion', 'AUD_GenerarRequerimientoAuditoria', $params);
            array_push($this->extras['responses'], ['AUD_GenerarRequerimientoAuditoria' => $auditoria_terreno]);
            if ($auditoria_terreno) {
                if (empty($auditoria_terreno) 
                    || empty($auditoria_terreno[0] 
                    || (is_array($auditoria_terreno) 
                        && array_key_exists('filas', $auditoria_terreno[0]) 
                        && $auditoria_terreno[0]->filas < 1))
                    || (is_array($auditoria_terreno) 
                        && array_key_exists('id_requerimiento', $auditoria_terreno[0])
                        && $auditoria_terreno[0]->id_requerimiento < 1)
                    ) {
                    //  devuelve el resultado
                    return [
                        'code' => -2,
                        'error' => 'No se guardó la auditoria en terreno',
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
                    if(array_key_exists('id_requerimiento', $auditoria_terreno)){
                        $id_requerimiento = $auditoria_terreno->id_requerimiento;
                    }
                    $parametros_mensaje = [
                        'numero_afiliado' => '',
                        'codigo_diagnostico' => '',
                        'descripcion_diagnostico' => '',
                        'edad_afiliado' => '',
                        'fec_nac' => '',
                        'habitacion' => '',
                        'id_requerimiento' => '',
                        'mensaje' => '',
                        'nombre_afiliado' => '',
                        'nro_doc' => '',
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
                        $parametros_mensaje['solicitud_strong'] = $params['usuario_genera'];
                    }
    
                    $parametros_mensaje['numero_afiliado'] = $email_data['numero_afiliado'];
                    $parametros_mensaje['codigo_diagnostico'] = $email_data['codigo_diagnostico'];
                    $parametros_mensaje['codigo_internacion'] = $email_data['codigo_internacion'];;
                    $parametros_mensaje['descripcion_diagnostico'] = $email_data['descripcion_diagnostico'];
                    $parametros_mensaje['edad_afiliado'] = $email_data['edad'];
                    $parametros_mensaje['fec_nac'] = $email_data['fec_nac'];
                    $parametros_mensaje['habitacion'] = $params['habitacion'];
                    $parametros_mensaje['id_requerimiento'] = $id_requerimiento;
                    $parametros_mensaje['nombre_afiliado'] = $email_data['nombre_afiliado'];
                    $parametros_mensaje['nro_doc'] = $email_data['nro_doc'];
                    $parametros_mensaje['numero_internacion'] = $email_data['numero_internacion'];
                    $parametros_mensaje['piso'] = $params['piso'];
                    $parametros_mensaje['prestador'] = $email_data['efector'];
                    $parametros_mensaje['sector'] = $params['sector'];
                    $parametros_mensaje['sexo'] = $email_data['sexo'];                
                    $parametros_mensaje['tipo_doc'] = $email_data['tipo_doc'];
                    $parametros_mensaje['tipo_internacion'] = $email_data['tipo_internacion'];
                    if (!empty($email_data['mensaje'])){
                        $parametros_mensaje['mensaje'] = $email_data['mensaje'];
                        $parametros_mensaje['usuario'] = $params['usuario_genera'];
                    }else{
                        $parametros_mensaje['usuario'] = '';
                        $parametros_mensaje['mensaje'] = '';
                    }
                    // $parametros_mensaje['url'] = get_val_url('auditorias-en-terreno/' . $auditoria_terreno->id_requerimiento);
                    $parametros_mensaje['url'] = '';
                    $tipo = 'solicitud';
                    try {

                        // es redundante porque tiene un fallback interno solo demuestra la configuración del .env
                        if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                            $mailable = otificacionEmailAuditoriaEnTerreno($asunto, $tipo, $parametros_mensaje);
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
                                array_push($this->errors, 'Error al enviar email con Microsoft Graph: '.json_encode($resultado));
                                $status = 'fail';
                                $code = -3;
                            }
                        }else{
                            Mail::to($email)->send(new NotificacionEmailAuditoriaEnTerreno($asunto, $tipo, $parametros_mensaje));
                            if(Mail::failures()){
                                array_push($extras['responses'], ['smtp_result' => false]);
                                Log::channel('email')->error('AuditoriaTerreno guardar_auditoria Error al enviar email por SMTP', [
                                    'email' => $email,
                                    'asunto' => $asunto,
                                    'tipo' => $tipo,
                                    'parametros_mensaje' => $parametros_mensaje,
                                ]);
                                $message = 'Error al enviar email por SMTP. ';
                                $error = Mail::failures();
                                array_push($this->errors, 'Error al enviar email por SMTP: '.json_encode($error));
                                $status = 'fail';
                                $code = -4;
                            }else{
                                array_push($extras['responses'], ['smtp_result' => true]);
                                Log::channel('email')->info('AuditoriaTerreno guardar_auditoria Email enviado por SMTP', [
                                    'email' => $email,
                                    'asunto' => $asunto,
                                    'tipo' => $tipo,
                                    'parametros_mensaje' => $parametros_mensaje,
                                ]);
                                $message = 'Email enviado por SMTP. ';
                                $error = null;
                                $status = 'ok';
                                $code = 2;
                            }
                            Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                        }
                        // Mail::to($email)->send(new NotificacionEmailAuditoriaEnTerreno($asunto, $tipo, $parametros_mensaje));
                        
                        // enviamos la notificación pusher
                        // $utiles = [
                        //     'path_url' => $parametros_mensaje['url'],
                        //     'Codigo_internacion' => $params['codigo_internacion'],
                        //     'usuario_origen' => $params['usuario_genera'],
                        //     'usuario_destino' => $params['usuario_auditor'],
                        //     'efector' => $email_data['efector'],
                        //     'sector' => $params['sector'],
                        //     'piso' => $params['piso'],
                        //     'habitacion' => $params['habitacion'],
                        //     'fecha' => $params['fecha_requerimiento']
                        // ];
                        // $mensaje = $params["usuario_genera"]
                        //     .' ha solicitado una auditoría en terreno para el médico auditor '
                        //     .$pusher_data['nombre_usuario_destino']
                        //     .' a efectuarse en '
                        //     .$email_data['efector']
                        //     .', Sector: '.$params['sector']
                        //     .', Piso: '.$params['piso']
                        //     .', Habitación: '.$params['habitacion'].'. '
                        //     .$params['usuario_genera']
                        //     .' le ha enviado el siguiente mensaje: '
                        //     .$email_data['mensaje']
                        //     .'. Si usted no es '.$pusher_data['nombre_usuario_destino'].' ignore este mensaje y comuníquese con el área de sistemas. Muchas Gracias.';
                        // $asunto = "Nueva Auditoría en Terreno solicitada. ";
                        // $id_usuario_destino = $pusher_data['id_usuario_destino'];
                        // if (Session::get('usuario')['id_usuario'] !== intval($id_usuario_destino)) {
                        //     // dd('usuario_destino', intval($id_usuario_destino), Session::get('usuario')['id_usuario'],Session::get('usuario')['id_contrato'], $mensaje, $asunto, $utiles);
                        //     $noti = $this->notificacion->insertar([
                        //         'p_id_contrato' => Session::get('usuario')['id_contrato'],
                        //         'p_id_usuario' => $id_usuario_destino,
                        //         'p_texto_notificacion' => $mensaje,
                        //         'p_asunto' => $asunto,
                        //         'p_notificado' => 0,
                        //         'p_utiles' => json_encode($utiles),
                        //         'p_id_tipo_notificacion' => 5
                        //     ]);
                        //     $this->pusherService->triggerNotification(
                        //         "auditoria-en-terreno-solicitada",
                        //         "AuditoriaEnTerrenoSolicitada",
                        //         [
                        //             'id_usuario' => $id_usuario_destino, 
                        //             'code' => 5,
                        //             'ambito' => env('ENVIRONMENT')
                        //         ]
                        //     );
                            // return[
                            //     'code' => $auditoria_terreno->id_requerimiento,
                            //     'error' => null,
                            //     'message' => 'La auditoría en terreno se generó exitosamente',
                            //     'sentMessage' => 'Usuario notificado',
                            //     // 'url' => oh_val_url('auditorias-en-terreno/' . $auditoria_terreno->id_requerimiento),
                            //     // 'pusher' => $noti,
                            //     'auditoria' => $auditoria_terreno,
                            //     'params' => $params
                            // ];
                        // }else{
                            return[
                                'code' => 2,
                                'id_requerimiento' => $id_requerimiento,
                                'error' => $error,
                                'message' => 'La auditoría en terreno se generó exitosamente. '.$message,
                                'sentMessage' => 'Usuario NO notificado via pusher',
                                // 'url' => oh_val_url('auditorias-en-terreno/' . $auditoria_terreno->id_requerimiento),
                                // 'pusher' => null,
                                'auditoria' => $auditoria_terreno,
                                'params_sp' => $params
                            ];
                        // }
    
                    } catch (\Throwable $th) {
                        return [
                            'code' => -4,
                            'error' => $error.' Line: '.$th->getLine().' - Error: '.$th->getMessage(),
                            'id_requerimiento' => $id_requerimiento,
                            'message' => 'La auditoría en terreno se generó exitosamente. '.$message,
                            'sentMessage' => 'No se pudo notificar al usuario via pusher',
                            // 'url' => oh_val_url('auditorias-en-terreno/' . $auditoria_terreno->id_requerimiento),
                            // 'pusher' => null,
                            'auditoria' => $auditoria_terreno,
                            'params_sp' => $params
                        ];
                    }
                }
            }else{
                return [
                    'code' => -3,
                    'error' => $error,
                    'id_requerimiento' => $id_requerimiento,
                    'message' => 'No se pudo generar auditoría en terreno. '.$message,
                    'sentMessage' => 'No se notificó al usuario. ',
                    'auditoria' => $auditoria_terreno,
                    'params' => $params
                ];
            }
        }else{
            return[
                'code' => 1,
                'error' => $error,
                'id_requerimiento' => $id_requerimiento,
                'message' => 'Auditoria en terreno confirmada. No se generó un nuevo requerimiento.',
                'sentMessage' => 'No se notificó al usuario',
                'auditoria' => null,
                'params' => $params
            ];
        }
    }
}