<?php

namespace App\Http\Controllers\Internos\Validaciones;

use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;

use App\Http\Controllers\ConexionSpController;

class ValidacionesController extends ConexionSpController
{
    /**
     * Busca una validacion que coincida con los parámetros dados
     */
    public function buscar_validaciones(Request $request)
    {
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/validaciones/buscar-validaciones',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];
        try {
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $id_usuario_sqlserver_default = 1;
            $id_usuario_sqlserver = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $id_usuario_sqlserver_default;

            $this->params = [];
            // variables de respuesta
            $status = 'fail';
            $count = -1;
            $errors = [];
            $message = 'Error en la consulta';
            $line = null;
            $code = 0;
            $data = null;

            if($user->hasPermissionTo('consultar validaciones')){
                $codigo_interno = request('codigo_interno');
                $tipo_validacion = request('tipo_validacion');
                $id_estado = request('id_estado');
                $filtrar_por_fechas = request('filtrar_por_fechas');
                $fecha_desde = request('fecha_desde');
                $fecha_hasta = request('fecha_hasta');
                $nro_afiliado = request('nro_afiliado');
                $nombre_afiliado = request('nombre_afiliado');
                $tipo_doc_afiliado = request('tipo_doc_afiliado');
                $nro_doc_afiliado = request('nro_doc_afiliado');
                $nro_autorizacion = request('nro_autorizacion');
                $id_sucursal = request('id_sucursal');
                $codigo_internacion = request('codigo_internacion');
                $nombre_prestador = request('nombre_prestador');
                $p_borradas = request('p_borradas');
                $id_tipo_internacion = request('id_tipo_internacion');
                
                // return [
                //     'codigo_interno' => $codigo_interno, 
                //     'tipo_validacion' => $tipo_validacion, 
                //     'id_estado' => $id_estado,
                //     'filtrar_por_fechas' => $filtrar_por_fechas, 
                //     'fecha_desde' => $fecha_desde, 
                //     'fecha_hasta' => $fecha_hasta, 
                //     'nro_afiliado' => $nro_afiliado, 
                //     'nombre_afiliado' => $nombre_afiliado, 
                //     'tipo_doc_afiliado' => $tipo_doc_afiliado, 
                //     'nro_doc_afiliado' => $nro_doc_afiliado, 
                //     'nro_autorizacion' => $nro_autorizacion, 
                //     'id_usuario' => $id_usuario, 
                //     'id_sucursal' => $id_sucursal, 
                //     'codigo_internacion' => $codigo_internacion, 
                //     'nombre_prestador' => $nombre_prestador, 
                //     'p_borradas' => $p_borradas, 
                //     'id_tipo_internacion' => $id_tipo_internacion, 
                // ];

                $this->params = [];
                
                if (isset($id_estado)){
                    $this->params['id_estado'] = intval($id_estado);
                }
                
                if (isset($filtrar_por_fechas) && $filtrar_por_fechas == 'true') {
                    $this->params['fecha_desde'] = carbon::parse($fecha_desde)->format('Ymd');
                    if (!isset($fecha_hasta)){
                        $this->params['fecha_hasta'] = Carbon::now()->addDays(1)->format('Ymd');
                    }else{
                        $this->params['fecha_hasta'] = carbon::parse($fecha_hasta)->format('Ymd');
                    }
                    // return [$filtrar_por_fechas, $fecha_desde, $fecha_hasta];
                }
                
                if (isset($nro_afiliado)){
                    $this->params['afiliado'] = $nro_afiliado;
                }
                
                if (isset($nombre_afiliado)){
                    $this->params['nombre'] = $nombre_afiliado;
                }
                
                if (isset($tipo_doc_afiliado)){
                    $this->params['p_tipo_doc'] = $tipo_doc_afiliado;
                }
                
                if (isset($nro_doc_afiliado)){
                    $this->params['p_nro_doc'] = $nro_doc_afiliado;
                }
                
                if (isset($nro_autorizacion)){
                    $this->params['autorizacion'] = $nro_autorizacion;
                }

                if (isset($id_usuario)){
                    $this->params['id_usuario'] = $id_usuario_sqlserver;
                }
                
                if (isset($id_sucursal)){
                    $this->params['id_sucursal'] = $id_sucursal;
                }
                
                if (isset($codigo_internacion)){
                    $this->params['codigo_internacion'] = $codigo_internacion;
                }
                
                if (isset($codigo_interno)){
                    $this->params['codigo_interno'] = $codigo_interno;
                }
                
                if (isset($nombre_prestador)){
                    $this->params['efector'] = $nombre_prestador;
                }

                if (isset($p_borradas)){
                    $this->params['p_borradas'] = $p_borradas == 'true' ? 1 : 0;
                }

                // return $this->params;
                //  ejecuta la consulta
                if (isset($codigo_interno)) {
                    array_push($extras['sps'], ['AWEB_TraerAutorizacionCabecera' => $this->params]);
                    array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerAutorizacionCabecera', null));
                    $response = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerAutorizacionCabecera', null);
                    array_push($extras['responses'], ['AWEB_TraerAutorizacionCabecera' => $response]);

                    if(isset($response['error'])){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Fallo al realizar la consulta a la base de datos';
                    }else if($response) {
                        if(is_array($response) && sizeof($response) > 0){
                            $validacion = $response[0];
                            $validacion->numero_formateado = get_agregar_ceros($validacion->id_sucursal, 3) . get_agregar_ceros($validacion->autorizacion, 8);
                            $data = $validacion;
                            $count = sizeof($response);
                            $code = 1;
                            $status = 'ok';
                            $message = 'Registros obtenidos';
                        }else{
                            $data = null;
                            $count = 0;
                            $status = 'fail';
                            $message = 'No se encontró la validación';
                            $code = -4;
                            array_push($errors, 'No se encuentra la validación');
                        }
                    }else{
                        $data = null;
                        $count = 0;
                        $status = 'fail';
                        $message = 'No se encontró la validación';
                        $code = -3;
                        array_push($errors, 'No se encuentra la validación');
                    }
                } else {
                    if ($tipo_validacion == 1) {
                        //  si es prestador
                        if ($user->id_prestador != null){ 
                            // $this->params['id_prestador'] = Session::get('usuario.id_prestador');
                            $this->params['id_prestador'] = $user->id_prestador;
                        }
                        if(!$user->es_supervisor() && $user->id_usuario_sqlserver != null){
                            $this->params['id_usuario'] = $id_usuario_sqlserver;
                        }
                        array_push($extras['sps'], ['AWEB_TraerAutorizaciones' => $this->params]);
                        array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerAutorizaciones', null));
                        $data = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerAutorizaciones', null);
                        // $data[0]->internacion_domiciliaria = 1;  // simular una internacion domiciliaria
                        array_push($extras['responses'], ['AWEB_TraerAutorizaciones' => $data]);
                        if(isset($data['error'])){
                            array_push($errors, $data['error']);
                            $status = 'fail';
                            $code = -12;
                            $message = 'Fallo al realizar la consulta a la base de datos';
                        }else if(sizeof($data) == 0){
                            $count = 0;
                            $status = 'empty';
                            $code = -11;
                            $message = 'No se encontraron registros con los parámetros otorgados';
                        }else{
                            $count = sizeof($data);
                            $status = 'ok';
                            $code = 4;
                            $message = 'Registros obtenidos';
                        }
                    } else if ($tipo_validacion == 2) {
                        if (!empty($this->params['tipo_documento'])) {
                            $this->params['doc_tipo'] = get_traer_abrev_tipo_documento($this->params['tipo_documento']);
                            unset($this->params['tipo_documento']);
                        }
                        if (!empty($this->params['numero_documento'])) {
                            $this->params['doc_numero'] = $this->params['numero_documento'];
                            unset($this->params['numero_documento']);
                        }
                        if (isset($this->params['nombre'])) {
                            $this->params['nombre_afiliado'] = $this->params['nombre'];
                            unset($this->params['nombre']);
                        }
                        if (isset($filtros->estado) && is_numeric($filtros->estado)){
                            $this->params['estado'] = $filtros->estado;
                        }

                        $this->params['id_usuario'] = $logged_user['usuario'];

                        if (isset($this->params['id_estado'])){
                            unset($this->params['id_estado']);
                        }

                        if (!empty($id_tipo_internacion) && $id_tipo_internacion != ''){
                            $this->params['id_tipo_internacion'] = $id_tipo_internacion;
                        }
                        array_push($extras['sps'], ['AWEB_TraerInternaciones' => $this->params]);
                        array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerInternaciones', null));
                        $data = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerInternaciones', null);
                        array_push($extras['responses'], ['AWEB_TraerInternaciones' => $data]);
                        if(isset($data['error'])){
                            array_push($errors, $data['error']);
                            $status = 'fail';
                            $code = -6;
                            $message = 'Fallo al realizar la consulta a la base de datos';
                        }else if(sizeof($data) == 0){
                            $code = -5;
                            $count = 0;
                            $status = 'empty';
                            $message = 'No se encontraron registros con los parámetros otorgados';
                        }else{
                            $code = 2;
                            $count = sizeof($data);
                            $status = 'ok';
                            $message = 'Registros obtenidos';
                        }
                    } else if ($tipo_validacion == 3) {
                        //  modifica los numeros
                        if (!empty($this->params['autorizacion'])) {
                            $this->params['numero'] = $this->params['autorizacion'];
                            unset($this->params['autorizacion']);
                        }
                        array_push($extras['sps'], ['AWEB_TraerRecetariosEmitidos' => $this->params]);
                        array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerRecetariosEmitidos', null));
                        $data = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerRecetariosEmitidos', null);
                        array_push($extras['responses'], ['AWEB_TraerRecetariosEmitidos' => $data]);
                        if(isset($data['error'])){
                            array_push($errors, $data['error']);
                            $status = 'fail';
                            $code = -8;
                            $message = 'Fallo al realizar la consulta a la base de datos';
                        }else if(sizeof($data) == 0){
                            $code = -7;
                            $count = 0;
                            $status = 'empty';
                            $message = 'No se encontraron registros con los parámetros otorgados';
                        }else{
                            $code = 3;
                            $count = sizeof($data);
                            $status = 'ok';
                            $message = 'Registros obtenidos';
                        }
                    } else if ($tipo_validacion == 4) {
                        array_push($extras['sps'], ['AWEB_TraerAuditoriasTerreno' => $this->params]);
                        array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerAuditoriasTerreno', null));
                        $data = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerAuditoriasTerreno', null);
                        array_push($extras['responses'], ['AWEB_TraerAuditoriasTerreno' => $data]);
                        if(isset($data['error'])){
                            array_push($errors, $data['error']);
                            $status = 'fail';
                            $code = -10;
                            $message = 'Fallo al realizar la consulta a la base de datos';
                        }else if(sizeof($data) == 0){
                            $code = -9;
                            $count = 0;
                            $status = 'empty';
                            $message = 'Esta Validación No tiene Auditorías de Terreno solicitadas';
                        }else{
                            $count = sizeof($data);
                            $status = 'ok';
                            $message = 'Registros obtenidos';
                        }
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CONSULTAR VALIDACIONES';
                $count  = 0;
                $data = null;
                $code = -2;
                array_push($errors, 'No autorizado');
            }
            // retorna el response
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => $line,
                'code' => $code,
                'data' => $data,
                'params' => $this->params,
                'extras' => $extras,
                'logged_user' => $logged_user
            ]); 
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
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
                'logged_user' => null
            ]);
        }
    }

}

