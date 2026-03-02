<?php

namespace App\Http\Controllers\Internos\Validaciones;

use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;

use App\Http\Controllers\ConexionSpController;

class MovimientosValidacionController extends ConexionSpController
{

    public function consultar_movimientos_validacion(Request $request){
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/validaciones/movimientos/consultar-movimientos-validacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => ['AWEB_TraerAutorizacionMovimientos'],
            'responses' => [],
            'queries' => []
        ];
        try {
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            // variables de respuesta
            $status = 'null';
            $count = -1;
            $errors = [];
            $message = 'sin comentarios';
            $line = null;
            $code = null;
            $data = null;

            $codigo_interno = request('codigo_interno');
            $this->params = [
                'p_codigo_interno' => $codigo_interno
            ];
                //  ejecuta la consulta
            array_push($extras['sps'], ['AWEB_TraerAutorizacionMovimientos' => $this->params]);
            array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerAutorizacionMovimientos', null));
            $response =  $this->ejecutar_sp_directo('validacion','AWEB_TraerAutorizacionMovimientos', null);
            array_push($extras['responses'], ['AWEB_TraerAutorizacionMovimientos' => $response]);
            if(isset($response['error'])){
                array_push($errors, $response['error']);
                $status = 'fail';
                $message = 'Fallo al realizar la consulta a la base de datos';
            }else{
                $count = sizeof($response);
                $status = 'ok';
                $message = 'Registros encontrados';
                $data = $response;
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
                'logged_user' => $logged_user,
                'extras' => $extras
            ]); 
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }

    public function insertar_movimiento_validacion(Request $request){
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/validaciones/movimientos/insertar-movimiento-validacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => ['AWEB_InsertarAutorizacionMovimientos'],
            'responses' => [],
            'queries' => [],
            'verificado' => []
        ];
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            $permiso_requerido = '';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                // variables de respuesta
                $status = 'null';
                $count = -1;
                $errors = [];
                $message = 'sin comentarios';
                $line = null;
                $code = null;
                $data = null;

                // obtenemos los datos de la petición
                $codigo_interno = request('codigo_interno');
                $movimiento = request('movimiento');
                //  variables de control
                $observacion_ok = FALSE;
                $adjunto_ok = TRUE;
                $hc_ok = TRUE;

                // preparamos los parámetros para el SP
                $this->params = [
                    'codigo_interno' => $codigo_interno,
                    'movimiento' => $movimiento
                ];
                $params_sp = [
                    'codigo_interno' => $codigo_interno,
                    'texto' => $movimiento['observacion'],
                    'id_usuario' => $id_usuario, 
                    'fecha' => Carbon::now()->format('Ymd H:i:s'),
                    'titulo' => $movimiento['titulo']
                ];

                    //  ejecuta la consulta
                array_push($extras['sps'], ['AWEB_InsertarValidacionTexto' => $params_sp]);
                array_push($extras['queries'], $this->get_query('validacion', 'AWEB_InsertarValidacionTexto', $params_sp));
                $observacion =  $this->ejecutar_sp_directo('validacion','AWEB_InsertarValidacionTexto', $params_sp);
                array_push($extras['responses'], ['AWEB_InsertarValidacionTexto' => $observacion]);
                //  si inserta la observacion
                if (!empty($observacion) && !empty($observacion[0]->insertado)){
                    $observacion_ok = TRUE;
                    $code = 1;
                    $message = 'La observación se añadió exitosamente';
                    $status = 'ok';
                }else{
                    $code = -3;
                    $status = 'fail';
                    $message = 'Fallo al realizar la inserción en la base de datos';
                }

                
                if($observacion_ok){
                    //  si hay adjunto
                    if (!empty($movimiento['adjunto'])) {
                        $adjunto_ok = FALSE;
                        //  agrega el archivo
                        $params_adjunto = [
                            'codigo_interno' => $codigo_interno,
                            'archivo' => $movimiento['adjunto'],
                            'id_usuario' => $id_usuario, 
                            'fecha' => Carbon::now()->format('Ymd H:i:s')
                        ];
                        array_push($extras['sps'], ['AWEB_InsertarArchivoAdjunto' => $params_adjunto]);
                        array_push($extras['queries'], $this->get_query('validacion', 'AWEB_InsertarArchivoAdjunto', $params_adjunto));
                        $adjunto =  $this->ejecutar_sp_directo('validacion','AWEB_InsertarArchivoAdjunto', $params_adjunto);
                        array_push($extras['responses'], ['AWEB_InsertarArchivoAdjunto' => $adjunto]);
                        //  si inserta la observacion
                        if (!empty($adjunto) && !empty($adjunto[0]->insertado)){
                            $adjunto_ok = TRUE;
                            $code = 2;
                            $message = $message.' El adjunto se añadió exitosamente.';
                            $status = 'ok';
                        }else{
                            $status = 'fail';
                            $message = $message.' El adjunto NO se añadió.';
                            $code = -4; 
                        }
                    }

                    if($movimiento['insertar_en_hc'] == 1){
                        $parametros = [
                            'tipo_documento' => $movimiento['tipo_documento'],
                            'numero_documento' => $movimiento['numero_documento'],
                            'usuario' => $id_usuario,
                            'hoja' => $movimiento['hoja'],
                            'archivo' => $movimiento['adjunto']
                        ];
                        array_push($extras['sps'], ['AWEB_AgregarHojaHistoriaClinica' => $parametros]);
                        array_push($extras['queries'], $this->get_query('validacion', 'AWEB_AgregarHojaHistoriaClinica', $parametros));
                        $hc_insert =  $this->ejecutar_sp_directo('validacion','AWEB_AgregarHojaHistoriaClinica', $parametros);
                        array_push($extras['responses'], ['AWEB_AgregarHojaHistoriaClinica' => $hc_insert]);
                        if(!empty($hc_insert) && $hc_insert[0]->agregado == 1){
                            $status = 'ok';
                            $message = $message.' La observación se añadió a la historia clínica';
                            $code = 3;
                            $hc_ok = TRUE;
                        }else{
                            $status = 'warning';
                            $message = $message.' La observación NO se añadió a la historia clínica';
                            $code = -5;
                            $hc_ok = FALSE;
                        }
                    }
                }

                $data = [
                    'observacion' => $observacion_ok,
                    'adjunto' => $adjunto_ok,
                    'hc' => $hc_ok
                ];
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
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => null,
                'extras' => $extras,
                'logged_user' => null
            ]);
        }
    }

    public function actualizar_movimiento_validacion(Request $request){
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/validaciones/movimientos/actualizar-movimiento-validacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail'; // 'ok', 'fail', 'empty', unauthorized', 'warning'  
        $message = '';
        $count = 0;
        $code = 0;
        $data = null;
        $errors = [];
        $params = [];
        $params_sp = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $permiso_requerido = 'gestionar validaciones';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $params = [
                    'id_texto' => request('id_texto'),
                    'observacion' => request('observacion'),
                ];
                
                $sp = 'sp_autorizacion_texto_movimiento_update';
                $db = 'validacion';
                $params_sp = [
                    'id_texto' => $params['id_texto'],
                    'texto' => $params['observacion'],
                    'usuario' => $logged_user['usuario']
                ];
                array_push($extras['verificado'], [
                    $sp => [
                        'id_texto' => request('id_texto'),
                        'texto' => request('observacion')
                    ]
                ]);
                if ( empty(request('id_texto')) || empty(request('observacion')) ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    array_push($extras['sps'], [$sp => $params_sp]);
                    array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
                    $response = $this->ejecutar_sp_directo($db, $sp, $params_sp);
                    array_push($extras['responses'], [$sp => $response]);
                    // if(!empty($res) && !empty($res[0]) && $res[0]->filas == 1){
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
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = sizeof($response);
                        $data = $response;
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
            array_push($errors, 'Line: '.$th->getLine().' Error: '.$th->getMessage());
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
}