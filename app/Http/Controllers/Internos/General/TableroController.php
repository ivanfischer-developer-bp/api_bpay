<?php

namespace App\Http\Controllers\Internos\General;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
// use Illuminate\Support\Facades\File as FacadeFile;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use App\Services\Afiliados\PadronExternoOsceara;

use File;
use Storage;
use Carbon\Carbon;

class TableroController extends ConexionSpController
{
    /**
     * Consulta datos para el tablero de comandos
     */
    public function buscar_datos(Request $request)
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
            $permiso_requerido = '';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $params = [
                    'consulta' => request('consulta'),
                    'fecha_desde' => request('fecha_desde'),
                    'fecha_hasta' => request('fecha_hasta'),
                ];

                switch (request('consulta')) {
                    case 'validaciones':
                        $response = $this->buscar_autorizaciones($extras, $errors, $params, $logged_user);
                        if(is_array($response['data']) && $response['data'] != null){
                            $data = [
                                'labels' => array_column($response['data'], 'labels'),
                                'autorizadas' => array_column($response['data'], 'autorizadas'),
                                'rechazadas' => array_column($response['data'], 'rechazadas'),
                                'diferidas' => array_column($response['data'], 'diferidas'),
                            ];
                        }else{
                            $data = $data;
                        }
                        break;
                    case 'recetas':
                        $response = $this->buscar_recetas($extras, $errors, $params, $logged_user);
                        if(is_array($response['data']) && $response['data'] != null){
                            $data = [
                                'labels' => array_column($response['data'], 'labels'),
                                'datasets' => array_column($response['data'], 'datasets'),
                            ];
                        }else{
                            $data = $data;
                        }
                        break;
                    case 'afiliados':
                        $response = $this->buscar_afiliados($extras, $errors, $params, $logged_user);
                        if(is_array($response['data']) && $response['data'] != null){
                            $data = [
                                'labels' => array_column($response['data'], 'labels'),
                                'datasets' => array_column($response['data'], 'datasets'),
                            ];
                        }else{
                            $data = $data;
                        }
                        break;

                }
                $extras = $response['extras'];
                $errors = $response['errors'];
                $status = $response['status'];
                $message = $response['message'];
                $count = $response['count'];
                $code = $response['code'];
                
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

    /**
     * Busca la cantidad de validaciones autorizadas entre las fechas indicadas, agrupadas por mes. Retorna un array con los meses y la cantidad de validaciones emitidas en cada mes.
     */
    private function buscar_autorizaciones($extras, $errors, $params, $logged_user){
        $status = 'fail';
        $message = '';
        $data = null;
        $count = 0;
        $errors = [];
        $code = 0; 
        try {
            $sp = 'sp_tc_autorizaciones_estados';
            $db = 'validacion';
            $params_sp = [
                'fecha_desde' => $params['fecha_desde'],
                'fecha_hasta' => $params['fecha_hasta'],
            ];
            array_push($extras['sps'], [$sp => $params_sp]);
            array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
            $response = $this->ejecutar_sp_directo($db, $sp, $params_sp);
            array_push($extras['responses'], [$sp => $response]);
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
            
            return [
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => $code,
                'data' => $data,
                'extras' => $extras,
            ]; 
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Error: '.$th->getMessage());
            return [
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => 'Error al obtener las validaciones emitidas.',
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'extras' => $extras,
            ];
        }
    }

    /**
     * Busca la cantidad de recetas emitidas entre las fechas indicadas, agrupadas por mes. Retorna un array con los meses y la cantidad de recetas emitidas en cada mes.
     */
    private function buscar_recetas($extras, $errors, $params, $logged_user){
        $status = 'fail';
        $message = '';
        $data = null;
        $count = 0;
        $code = 0;
        // return [
        //     'status' => 'ok',
        //     'count' => 12,
        //     'errors' => $errors,
        //     'message' => 'Transacción realizada con éxito.',
        //     'line' => null,
        //     'code' => 1,
        //     'data' => [
        //         'labels' => ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
        //         'datasets' => [54, 80, 65, 90, 75, 85, 95, 70, 60, 100, 110, 120],
        //     ],
        //     'extras' => $extras,
        // ]; 
        try {
            $sp = 'sp_tc_recetas';
            $db = 'validacion';
            $params_sp = [
                'fecha_desde' => $params['fecha_desde'],
                'fecha_hasta' => $params['fecha_hasta'],
            ];
            array_push($extras['sps'], [$sp => $params_sp]);
            array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
            $response = $this->ejecutar_sp_directo($db, $sp, $params_sp);
            array_push($extras['responses'], [$sp => $response]);
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
            return [
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => $code,
                'data' => $data,
                'extras' => $extras,
            ]; 
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Error: '.$th->getMessage());
            return [
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => 'Error al obtener las validaciones emitidas.',
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'extras' => $extras,
            ];
        }
    }

    /**
     * Busca la cantidad de afiliados registrados entre las fechas indicadas, agrupadas por mes. Retorna un array con los meses y la cantidad de validaciones emitidas en cada mes.
     */
    private function buscar_afiliados($extras, $errors, $params, $logged_user){
        $status = 'fail';
        $message = '';
        $data = null;
        $count = 0;
        $errors = [];
        $code = 0;
        // return [
        //     'status' => 'ok',
        //     'count' => 12,
        //     'errors' => $errors,
        //     'message' => 'Transacción realizada con éxito.',
        //     'line' => null,
        //     'code' => 1,
        //     'data' => [
        //         'labels' => ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
        //         'datasets' => [243, 150, 200, 170, 220, 190, 240, 210, 230, 180, 250, 260],
        //     ],
        //     'extras' => $extras,
        // ]; 
        try {
            $sp = 'sp_tc_afiliados';
            $db = 'afiliacion';
            $params_sp = [];
            array_push($extras['sps'], [$sp => $params_sp]);
            array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
            $response = $this->ejecutar_sp_directo($db, $sp, $params_sp);
            array_push($extras['responses'], [$sp => $response]);
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
            
            return [
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => $code,
                'data' => $data,
                'extras' => $extras,
            ]; 
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Error: '.$th->getMessage());
            return [
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => 'Error al obtener las validaciones emitidas.',
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'extras' => $extras,
            ];
        }
    }
}