<?php

namespace App\Http\Controllers\Externos\Salud\Validaciones;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use App\Models\User;
use Carbon\Carbon;

use App\Http\Controllers\ConexionSpController;

class ExternalInternacionController extends ConexionSpController
{

    /**
     * Crea una nueva validación de internación
     * Endpoint externo
     * ws_internacion
     * Consume: Silvio Quadri, 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_external_logged_user($user);

            $this->params = [ 
                'p_tipo_doc' => request('p_tipo_doc'), 
                'p_nro_doc' => request('p_nro_doc'), 
                'p_fecha_realizacion' => request('p_fecha_realizacion'), 
                'p_diagnostico' => request('p_diagnostico'), 
                'p_json_prestaciones' => json_encode(request('p_json_prestaciones')), 
                'p_id_usuario' => $request->user()->id, 
                'autoriza_en_guardia' => request('autoriza_en_guardia'), 
                'p_observaciones' => request('p_observaciones'), 
                'codigo_internacion' => request('codigo_internacion'), 
                'id_tipo_accion' => request('id_tipo_accion'), 
                'id_tipo_internacion' => request('id_tipo_internacion'), 
                'fecha_ingreso' => request('fecha_ingreso'), 
                'fecha_egreso' => request('fecha_egreso'), 
                'fpe' => request('fpe'), 
                'codigo_diagnostico_egreso' => request('codigo_diagnostico_egreso'), 
                'id_tipo_alta_internacion' => request('id_tipo_alta_internacion'), 
                'prioridad' => request('prioridad'), 
                'pediatrica' => request('pediatrica'), 
            ];
    
            if($request->user()->hasPermissionTo('crear validacion internacion')){
            
                // variables de respuesta
                $status = 'null';
                $message = 'sin comentarios';
                $count = -1;
                $data = null;
                $error = 'sin especificar';
                $seguir = true;

                // si es prestador sacamos el parámetro id_prestador del usuario
                if($request->user()->es_prestador()){
                    $this->params['p_prestador'] = $request->user()->id_prestador; // int
                    if($this->params['p_prestador'] == '' || $this->params['p_prestador'] == null){
                        $status = 'fail';
                        $message = 'El usuario con rol '.$user->roles[0]->name.' no tiene asignado id_prestador.';
                        $count = 0;
                        $data = null;
                        $error = 'Usuario no configurado';
                        $seguir = false;
                    }else{
                        $seguir = true;
                    }
                }
                // si es administrador debe proporcionar el parámetrop_prestador
                if($user->hasRole('administrador') || $user->hasRole('super administrador')){
                    $this->params['p_prestador'] = request('p_prestador'); // int
                    if($this->params['p_prestador'] == '' || $this->params['p_prestador'] == null){
                        $status = 'fail';
                        $message = 'Falta de parámetros en la consulta';
                        $count = 0;
                        $data = null;
                        $error = 'Como es usuario con rol '.$user->roles[0]->name.' el parámetro p_prestador es requerido.';
                        $seguir = false;
                    }else{
                        $seguir = true;
                    }
                }
                // si es gateway debe proporcionar el parámetrop_prestador
                if($user->hasRole('gateway')){
                    $this->params['p_prestador'] = request('p_prestador'); // es necesario para consultar validaciones de un prestador
                    if($this->params['p_prestador'] == '' || $this->params['p_prestador'] == null){
                        $status = 'fail';
                        $message = 'Falta de parámetros en la consulta';
                        $count = 0;
                        $data = null;
                        $error = 'Como es usuario con rol '.$user->roles[0]->name.' el parámetro p_prestador es requerido.';
                        $seguir = false;
                    }else{
                        $seguir = true;
                    }
                }
                // si no se produjeron errores por falta de parámetro ejecuta el sp
                if($seguir){
                    try {
                        // ejecuta el sp
                        $response = $this->ejecutar_sp_directo('validacion_externa', 'ws_internacion', null);
                        Log::channel('external')->info('ws_internacion consultado por '.$request->user()->name.' (id '.$request->user()->id.') con prestador '.$this->params['p_prestador']);
                        // prepara la respuesta
                        // si hubo errores al ejecutar el sp
                        if(array_key_exists('error', $response)){
                            // return 'Se produjo un error: '.$response['error'];
                            $status = 'fail';
                            $message = 'Se produjo un error al realizar la petición ';
                            $count = 0;
                            $data = null;
                            $error = $response['error']; 
                        }else{
                            // sino hubo errores al ajecutar sp y devuelve una autorización
                            if(array_key_exists('autorizacion', $response[0])){
                                // si la operación fue exitosa el estado es diferente de -1
                                if($response[0]->estado != -1){
                                    // retorna estado, sucursal(siempre es 994) y autorizacion
                                    $status = 'ok';
                                    $message = 'Transacción realizada con éxito';
                                    $count = sizeof($response);
                                    $data = $response;
                                    $error = 'No se produjeron errores';
                                }else{
                                    // si el estado es -1 hubo error en la transacción
                                    $status = 'fail';
                                    $message = 'Error en la transacción';
                                    $count = 0;
                                    $data = $response;
                                    $error = 'Error de transacción, no se realizó la operación';
                                }
                            }else{
                                // si no devuelve una autorización
                                $status = 'fail';
                                $message = 'No se realizó correctamente la transacción';
                                $count = 0;
                                $data = $response;
                                $error = 'Errores en la conexión con la base de datos.'; 
                            }
                        }
                    } catch (\Throwable $th) {
                        $status = 'fail';
                        $message = 'Error al realizar la petición.';
                        $count = 0;
                        $data = null;
                        $error = 'Line: '.$th->getLine().' - '.$th->getMessage();
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CREAR VALIDACION INTERNACION';
                $count  = 0;
                $data = null;
                $error = null;
            }
            // retorna el response
            return response()->json([
                'status' => $status,
                'count' => $count,
                'error' => $error,
                'message' => $message,
                'line' => null,
                'code' => null,
                'data' => $data,
                'params' => $this->params,
                'logged_user' => $logged_user,
                'api_software_version' => config('site.external_software_version')
            ]); 
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'error' => 'Backend failed',
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'api_software_version' => config('site.external_software_version')
            ]);
        }
        
    }

    /**
     * Genera un egreso de internación
     * Endpoint externo
     * Consume: PYXIS
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generar_egreso_automatico(Request $request)
    {
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_external_logged_user($user);
            $dias_vencimiento = 60;     //  hardcoded - Obtener los datos de algun lado!
            $vencimiento = Carbon::now()->addDays($dias_vencimiento)->format('Ymd');

            //  obtiene los datos
            $codigo_internacion= request('codigo_internacion');
            $observaciones = request('observaciones') != null ? request('observaciones') : NULL;
            $observaciones_validacion = request('observaciones_validacion') != null ? request('observaciones_validacion') : NULL;
            $observaciones_afiliado = request('observaciones_afiliado') != null ? request('observaciones_afiliado') : NULL;
            $observaciones_efector = request('observaciones_efector') != null ? request('observaciones_efector') : NULL;
            $adjunto = request('adjunto');
            $id_tipo_alta_internacion = request('id_tipo_alta_internacion');
            $fecha_egreso = request('fecha_egreso');
            $codigo_diagnostico_egreso = request('codigo_diagnostico_egreso');

            //  establece los parametros
            $this->params = [
                'id_tipo_accion' => 4,
                'observaciones' => $observaciones,
                'Comentarios' => $observaciones_validacion,
                'Comentario_Afiliado' => $observaciones_afiliado,
                'Comentario_Efector' => $observaciones_efector,
                'adjunto' => ( !empty($egreso['adjunto']) ? $egreso['adjunto'] : NULL ),
                'id_tipo_alta_internacion' => $id_tipo_alta_internacion,
                'fecha_egreso' => $fecha_egreso,
                'codigo_diagnostico_egreso' => $codigo_diagnostico_egreso,
            ];
            
            //  datos obligatorios
            if ( empty($codigo_internacion) 
                || empty($id_tipo_alta_internacion) 
                || empty($fecha_egreso) 
                || empty($codigo_diagnostico_egreso)){
                return array('code' => -1, 'message' => 'Verifique los parámetros');
            }


            //  emite la validacion y devuelve el resultado
            // $internacion_obj = new Internacion;
            // $internacion_obj->codigo_internacion = $codigo_internacion;
            // return $internacion_obj->emitir_validacion($params, 'egreso-internacion');
            if($request->user()->hasPermissionTo('generar egreso internacion')){
                // variables de respuesta
                $status = 'null';
                $message = 'sin comentarios';
                $count = -1;
                $code = null;
                $data = null;
                $error = 'sin especificar';
                $seguir = true;

                $internacion = $this->ejecutar_sp_directo('validacion_externa', 'AWEB_TraerInternacion', ['codigo_internacion' => $codigo_internacion]);
                $internacion = (array)$internacion[0];
                Log::channel('external')->info('AWEB_TraerInternacion consultado por '.$request->user()->name);
                // sreturn $internacion;
                if ( $internacion ){
                    //  si ya tiene egreso
                    if ( strtolower($internacion['estado']) != 'abierta'){
                        $code = -2;
                        $message = 'La internación está cerrada.';
                        $status = 'fail';
                        $count = 0;
                        $error = 'Intentando generar egreso de una internación cerrada';
                    }else{
                        //  establece las fechas configuradas para chequear la fecha de egreso
                        $fecha_egreso_desde = get_carbon_ymd($internacion['fecha_ingreso'])->format('Ymd');
                        $fecha_egreso_hasta = get_carbon_ymd($internacion['fecha_ingreso'])->addDays($internacion['cantidad_dias'])->format('Ymd');
                        $fecha_egreso_obj = Carbon::parse($this->params['fecha_egreso'])->format('Ymd');

                        //  la fecha de egreso debe ser mayor que la fecha de ingreso y menor que la fecha de ingreso + cantidad_dias
                        if(Carbon::parse($fecha_egreso_obj)->lt(Carbon::parse($fecha_egreso_desde))
                            // || Carbon::parse($fecha_egreso_obj)->gt(Carbon::parse($fecha_egreso_hasta))
                            ){
                            $status = 'fail';
                            $code = -3;
                            $count = 0;
                            $message = 'La fecha de egreso no puede ser menor a la fecha de ingreso';
                            $error = 'Error en fecha de ingreso y egreso informadas';
                        }else{
                            $params = [
                                //  datos de la cabecera
                                'Afiliado' => $internacion['afiliado'],
                                'Empresa' => $internacion['id_empresa'],
                                'Sucursal' => $internacion['id_sucursal'],
                                // 'Diagnostico' => ( ( $slug_origen == 'reabrir-internacion-domiciliaria' ) ? $internacion['codigo_diagnostico_ingreso'] : '' ),
                                'Comentarios' => ( !empty($observaciones) ? $observaciones : NULL ),
                                'Diagnostico' => $internacion['diagnostico'],
                                'Fecha_Vencimiento' => $vencimiento,
                                'usuario' => $logged_user['usuario'],
            
                                //  prestadores
                                'Convenio' => $internacion['id_convenio'],
                                'Prestador_Prescriptor' => $internacion['id_prescriptor'],
                                'Prestador' => $internacion['id_efector'],
            
                                //  internacion
                                'Codigo_Internacion' => $codigo_internacion,
                                'id_tipo_accion' => $this->params['id_tipo_accion'],
            
                                //  ingreso
                                'fecha_ingreso' => '', //$internacion['fecha_ingreso'],
                                'fpe' => null, // $internacion['fpe'],
                                'id_tipo_internacion' => $internacion['id_tipo_internacion'],
                                'prioridad' => NULL,
                                'pediatrica' => 0,
            
                                //  egreso
                                'codigo_diagnostico_egreso' => $codigo_diagnostico_egreso,
                                'id_tipo_alta_internacion' => strval($id_tipo_alta_internacion),
                                'fecha_egreso' => Carbon::parse($fecha_egreso)->format('Ymd'),
            
                                //  el resto :P
                                'ambulatorio' => 0, // no esta
                                'audita_nivel' => '', // no esta 
                                'auditoria_administrativa' => 0, 
                                'cantidad_dias' => 1,
                                'callcenter'             => 0,
                                'codigo_interno_edicion' => '',
                                'Comentario_Afiliado' => $observaciones_afiliado, 
                                'Comentario_Efector' => $observaciones_efector, 
                                'Control'                => 0,
                                'elegibilidad'           => 0,
                                'edicion'                => 0,
                                'fecha_prescripcion' => Carbon::now()->format('Ymd'),
                                'fecha_realizacion' => !empty($internacion) && !empty($internacion['fecha_ingreso']) ? Carbon::createFromFormat('d/m/Y', $internacion['fecha_ingreso'] )->format('Ymd') : null, // no esta
                                'Fecha_BajaPlanCronico'  => '',
                                'guardia'                => 0,
                                'ID_Suplementos'         => '',
                                'internacion' => 1, // no esta
                                // datos para emitir prestacion en internaciones
                                'n_contacto' => null,
                                'n_domicilio' => null, 
                                'p_rechazo' => 0, // no esta
                                'p_motivo' => '', // no esta
                                'zona' => null
                            ];
                            //  para las prestaciones...
                            //  de 1 a 20, establece los parametros de las prestaciones
                            for ( $i=1; $i<=20; $i++ ){
                                $k = $i - 1;
                                $params['Cantidad' . $i] = 0;
                                $params['Practica' . $i] = 0;
                                $params['Frecuencia' . $i] = null;
                                $params['Frecuencia_cantidad' . $i] = null;
                                $params['Finaliza' . $i] = null;
                                $params['Retiro' . $i] = null;
                            }
                            //  establece los parametros a pasar a la emision
                            $observaciones = NULL;
                            $adjunto = empty($adjunto) ? NULL : $adjunto;
                            // $datos_extras = [
                            //     'n_persona' => $internacion['nombre']
                            // ];
                            // return $params;
                            $validacion_emitida = $this->ejecutar_sp_directo('validacion_externa', 'AWEB_Autorizar', $params);
                            Log::channel('external')->info('AWEB_Autorizar consultado por '.$request->user()->name);
                            if ($validacion_emitida) {
                                //  si no tiene codigo interno es porque hubo un error
                                if (empty($validacion_emitida[0]->codigo_interno)) {
                                    // confeccionar el error;
                                    $sttus = 'fail';
                                    $data = $validacion_emitida[0]->Estado;
                                    $message = 'Egreso NO generado';
                                    $error = $validacion_emitida[0]->Motivo;
                                    $count = 0;
                                    $code = -4;
                                } else {
                                    //  si tiene codigo interno, lo devuelve
                                    if (!is_null($adjunto)) {
                                        //  si hay adjunto
                                        if(is_array($adjunto)){
                                            if (count($adjunto) > 0) {
                                                //  agrega el archivo
                                                foreach ($adjunto as $filename) {
                                                    $this->ejecutar_sp_directo('validacion_externa', 'AWEB_InsertarArchivoAdjunto', [
                                                        'codigo_interno' => $validacion_emitida[0]->codigo_interno,
                                                        'archivo' => $filename,
                                                        'id_usuario' => $params['usuario'],
                                                        'fecha' => Carbon::now()->format('Ymd H:i:s')
                                                    ]);
                                                    Log::channel('external')->info('AWEB_InsertarArchivoAdjunto consultado por '.$request->user()->name);
                                                }
                                            }
                                        }else{
                                            $this->ejecutar_sp_directo('validacion_externa', 'AWEB_InsertarArchivoAdjunto', [
                                                'codigo_interno' => $validacion_emitida[0]->codigo_interno,
                                                'archivo' => $adjunto,
                                                'id_usuario' => $params['usuario'],
                                                'fecha' => Carbon::now()->format('Ymd H:i:s')
                                            ]);
                                            Log::channel('external')->info('AWEB_InsertarArchivoAdjunto consultado por '.$request->user()->name);
                                        }
                                    }
                                    $status = 'ok';
                                    $data = [
                                        'id' => $validacion_emitida[0]->codigo_interno,
                                        'autorizacion_formateada' => get_agregar_ceros($validacion_emitida[0]->id_sucursal, 3) . get_agregar_ceros($validacion_emitida[0]->autorizacion, 8),
                                        'codigo_interno' => $validacion_emitida[0]->codigo_interno,
                                        'validacion_emitida' => $validacion_emitida,
                                        // 'params' => $params
                                    ];
                                    $message = 'Egreso generado con éxito';
                                    $error = 'Sin errores';
                                    $count = 1;
                                    $code = 1;
                                }
                            }else{
                                $sttus = 'fail';
                                $data = $validacion_emitida;
                                $message = 'Egreso NO generado';
                                $error = 'Error al generar egreso de internación';
                                $count = 0;
                                $code = -5;
                            }
                        }
                    }
                }else{
                    $status = 'fail';
                    $count = 0;
                    $error = 'Internacion inexistente';
                    $message = 'No existe la internación.';
                    $code = -1;
                    $data = null;
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para GENERAR EGRESO INTERNACION';
                $count  = 0;
                $data = null;
                $error = null;
            }
            // retorna el response
            return response()->json([
                'status' => $status,
                'count' => $count,
                'error' => $error,
                'message' => $message,
                'line' => null,
                'code' => $code,
                'data' => $data,
                'params' => $this->params,
                'logged_user' => $logged_user,
                'api_software_version' => config('site.external_software_version')
            ]); 
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'error' => 'Backend failed',
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'api_software_version' => config('site.external_software_version')
            ]);
        }
    }

    /**
     * Funcion privada para emitir validaciones de internación de distinto tipo
     */
    // private function emitir($datos_extras, $params, $observaciones, $adjunto, $es_internacion = TRUE)
    // {
    //     $validacion_emitida = $this->ejecutar_sp_directo('validacion_externa', 'AWEB_Autorizar', $params, FALSE, FALSE);
    //     $message = '';
    //     //  si pudo emitir, devuelve la respuesta
    //     if ($validacion_emitida) {
    //         //  si no tiene codigo interno es porque hubo un error
    //         if (empty($validacion_emitida[0]->codigo_interno)) {
    //             return [
    //                 'id' => -($validacion_emitida[0]->Estado),
    //                 'message' => $validacion_emitida[0]->Motivo,
    //             ];
    //         } //  si tiene codigo interno, lo devuelve
    //         else {
    //             //  si hay observaciones para el afiliado, lo agrega
    //             if (!empty($observaciones) && !empty($observaciones['afiliado'])) {
    //                 //  agrega la observacion al afiliado
    //                 $this->ejecutar_sp('AWEB_InsertarNovedadAfiliadoCallCenter', [
    //                     'id_empresa' => $params['Empresa'],
    //                     'afiliado' => $params['Afiliado'],
    //                     'id_usuario' => $params['usuario'],
    //                     'comentarios' => $observaciones['afiliado']
    //                 ]);
    //             }

    //             //  si hay observaciones para el efector
    //             if (!empty($observaciones) && !empty($observaciones['efector'])) {
    //                 //  agrega la observacion al efector
    //                 $this->ejecutar_sp('AWEB_InsertarNovedadPrestadorCallCenter', [
    //                     'id_prestador' => $params['Prestador'],
    //                     'id_usuario' => $params['usuario'],
    //                     'comentarios' => $observaciones['efector']
    //                 ]);
    //             }

    //             if (!is_null($adjunto)) {
    //                 //  si hay adjunto
    //                 if(is_array($adjunto)){
    //                     if (count($adjunto) > 0) {
    //                         //  agrega el archivo
    //                         foreach ($adjunto as $filename) {
    //                             $this->ejecutar_sp('AWEB_InsertarArchivoAdjunto', [
    //                                 'codigo_interno' => $validacion_emitida[0]->codigo_interno,
    //                                 'archivo' => $filename,
    //                                 'id_usuario' => $params['usuario'],
    //                                 'fecha' => Carbon::now()->format('Ymd H:i:s')
    //                             ]);
    //                         }
    //                     }
    //                 }else{
    //                     $this->ejecutar_sp('AWEB_InsertarArchivoAdjunto', [
    //                         'codigo_interno' => $validacion_emitida[0]->codigo_interno,
    //                         'archivo' => $adjunto,
    //                         'id_usuario' => $params['usuario'],
    //                         'fecha' => Carbon::now()->format('Ymd H:i:s')
    //                     ]);
    //                 }
    //             }
    //             // dd('observaciones', $observaciones, 'adjunto', $adjunto);
    //             if(isset($observaciones['insertar_en_hc']) && $observaciones['insertar_en_hc'] == 1){
    //                 if (!is_null($adjunto)) {
    //                     //  si hay adjunto
    //                     if(is_array($adjunto)){
    //                         if (count($adjunto) > 0) {
    //                             //  agrega el archivo
    //                             foreach ($adjunto as $filename) {
    //                                 $parametros = [
    //                                     'tipo_documento' => $data['tipo_documento'],
    //                                     'numero_documento' => $data['nro_doc'],
    //                                     'usuario' => Session::get('usuario.id_n_usuario'),
    //                                     'hoja' => !empty($observaciones['afiliado']) ? $observaciones['afiliado'] : '',
    //                                     'archivo' => $filename
    //                                 ];
    //                                 $hc_insert = $this->ejecutar_sp('AWEB_AgregarHojaHistoriaClinica', $parametros);
    //                                 // dd('hc_insert', $hc_insert);
    //                             }
    //                         }
    //                     }else{
    //                         $parametros = [
    //                             'tipo_documento' => $data['tipo_documento'],
    //                             'numero_documento' => $data['nro_doc'],
    //                             'usuario' => Session::get('usuario.id_n_usuario'),
    //                             'hoja' => !empty($observaciones['afiliado']) ? $observaciones['afiliado'] : '',
    //                             'archivo' => $adjunto
    //                         ];
    //                         $hc_insert = $this->ejecutar_sp('AWEB_AgregarHojaHistoriaClinica', $parametros);
    //                     }

    //                 }else{
    //                     $parametros = [
    //                         'tipo_documento' => $data['tipo_documento'],
    //                         'numero_documento' => $data['nro_doc'],
    //                         'usuario' => Session::get('usuario.id_n_usuario'),
    //                         'hoja' => !empty($observaciones['afiliado']) ? $observaciones['afiliado'] : '',
    //                         'archivo' => null
    //                     ];
    //                     $hc_insert = $this->ejecutar_sp('AWEB_AgregarHojaHistoriaClinica', $parametros);
    //                 }
    //                 // if(!empty($hc_insert) && $hc_insert[0]->agregado == 1){
    //                 //     $message = 'La observación  se insertó exitosamente y se añadió a la historia clínica';
    //                 //     $code = 3;
    //                 //     $hc_ok = TRUE;
    //                 // }else{
    //                 //     $message = 'La observación  se insertó exitosamente pero NO se añadió a la historia clínica';
    //                 //     $code = -3;
    //                 //     $hc_ok = FALSE;
    //                 // }
    //             }

               

    //             // $usuarios_notificar = $this->notificacion->ejecutar_sp('sp_contrato_usuario_rol_select', [
    //             //     'p_id_contrato' => Session::get('usuario')['id_contrato'],
    //             //     'p_codigo_interno' => $validacion_emitida[0]->codigo_interno
    //             // ]);
                
    //             // if (count($usuarios_notificar) > 0) {
    //             //     foreach ($usuarios_notificar as $usuario) {
    //             //         if (Session::get('usuario')['id_usuario'] !== $usuario->id_usuario) {

    //             //             if ($validacion_emitida[0]->estado === 1) {
    //             //                 $mensaje = "Afiliado ".$data['n_persona'].' N° '.$params['Afiliado'].". Autorización Nro. " . $validacion_emitida[0]->autorizacion . " autorizada.";

    //             //                 $utiles = [
    //             //                     'path_url' => '/validaciones',
    //             //                     'codigo' => $validacion_emitida[0]->codigo_interno
    //             //                 ];

    //             //                 $tipo_notificacion = 1;

    //             //                 $this->notificacion->insertar([
    //             //                     'p_id_contrato' => $usuario->id_contrato,
    //             //                     'p_id_usuario' => $usuario->id_usuario,
    //             //                     'p_texto_notificacion' => $mensaje,
    //             //                     'p_asunto' => "Afiliado ".$data['n_persona'].' N° '.$params['Afiliado'].". Autorización N° " . $validacion_emitida[0]->autorizacion,
    //             //                     'p_notificado' => 0,
    //             //                     'p_utiles' => json_encode($utiles),
    //             //                     'p_id_tipo_notificacion' => $tipo_notificacion
    //             //                 ]);

    //             //                 $this->pusherService->triggerNotification(
    //             //                     "notificacion-push", // channel
    //             //                     "NotificacionEnviada", //event
    //             //                     [
    //             //                         'id_usuario' => $usuario->id_usuario, 
    //             //                         'code' => 1,
    //             //                         'ambito' => env('ENVIRONMENT')
    //             //                     ] // message
    //             //                 );
    //             //             } else {
    //             //                 $mensaje = "Afiliado ".$data['n_persona'].' N° '.$params['Afiliado'].". Autorización Nro. " . $validacion_emitida[0]->autorizacion . " para auditar.";

    //             //                 $utiles = [
    //             //                     'path_url' => '/validaciones/auditar/' . $validacion_emitida[0]->codigo_interno,
    //             //                     'codigo' => $validacion_emitida[0]->codigo_interno
    //             //                 ];

    //             //                 $tipo_notificacion = 2;

    //             //                 // if ($usuario->n_rol === 'AUDITOR MEDICO' || $usuario->n_rol === 'AUDITOR-MEDICO') {
    //             //                     $this->notificacion->insertar([
    //             //                         'p_id_contrato' => $usuario->id_contrato,
    //             //                         'p_id_usuario' => $usuario->id_usuario,
    //             //                         'p_texto_notificacion' => $mensaje,
    //             //                         'p_asunto' => "Afiliado ".$data['n_persona'].' N° '.$params['Afiliado'].". Autorización N° " . $validacion_emitida[0]->autorizacion,
    //             //                         'p_notificado' => 0,
    //             //                         'p_utiles' => json_encode($utiles),
    //             //                         'p_id_tipo_notificacion' => $tipo_notificacion
    //             //                     ]);

    //             //                     $this->pusherService->triggerNotification(
    //             //                         "notificacion-push", // channel
    //             //                         "NotificacionEnviada", //event
    //             //                         [
    //             //                             'id_usuario' => $usuario->id_usuario, 
    //             //                             'code' => 1, 
    //             //                             'ambito' => env('ENVIRONMENT')
    //             //                         ] // message
    //             //                     );
    //             //                 // }
    //             //             }
    //             //         }
    //             //     }
    //             // }
                
    //             return [
    //                 'id' => $validacion_emitida[0]->codigo_interno,
    //                 'message' => $message,
    //                 'autorizacion' => get_agregar_ceros($validacion_emitida[0]->id_sucursal, 3) . get_agregar_ceros($validacion_emitida[0]->autorizacion, 8),
    //                 // 'usuarios_notificados' => $usuarios_notificar,
    //                 'id_contrato' => Session::get('usuario')['id_contrato'],
    //                 'codigo_interno' => $validacion_emitida[0]->codigo_interno
    //             ];
    //         }
    //     }

    //     return ['id' => -1, 'message' => 'Error'];
    // }
}





 