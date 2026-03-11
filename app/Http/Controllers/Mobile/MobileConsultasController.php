<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

use App\Http\Controllers\ConexionSpController;

use App\Models\User;
use App\Models\ConnectedUser;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class MobileConsultasController extends ConexionSpController {

    /**
     * Consulta el estado de los reintegros
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function consultar_reintegro(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/consultas/consultar-reintegro',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $errors = [];
        $data = null;
        $status = null;
        $code = 0;
        $count = 0;
        $line = null;
        $params = [];
        $message = null;
        $logged_user = null;

        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $id_usuario_sqlserver = $logged_user['id_usuario_sqlserver'];

        try {
            $params = [
                'fecha_desde' => request('fecha_desde'),
                'fecha_hasta' => request('fecha_hasta'),
                'id_persona' => request('id_persona')
            ];
            
            if($logged_user['roles']['name'] != 'afiliado mobile' && $logged_user['roles']['name'] != 'afiliado'){
                return response()->json([
                    'status' => 'unauthorized',
                    'count' => 0,
                    'errors' => $errors,
                    'message' => 'El usuario no es un afiliado',
                    'line' => $line,
                    'code' => -2,
                    'data' => $data,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]);
            }else{

                if($id_usuario_sqlserver != 0){
                    if($params['id_persona'] != null){
                        $params_sp = [
                            'p_id_persona' => $params['id_persona'],
                            'p_fd' => $params['fecha_desde'],
                            'p_fh' => $params['fecha_hasta']
                        ];
                    }else{
                        // buscamos los datos del usuario
                        array_push($extras['sps'], ['sp_usuario_select' => ['p_id_usuario' => $id_usuario_sqlserver]]);
                        array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]));
                        $usuario_sqlserver = $this->ejecutar_sp_directo('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]);
                        array_push($extras['responses'], ['sp_usuario_select' => $usuario_sqlserver]);
            
                        if(is_array($usuario_sqlserver)){
                            $usuario_sqlserver = $usuario_sqlserver[0];
                        }
                        
                        $params_sp = [
                            'p_id_persona' => $usuario_sqlserver->id_persona,
                            'p_fd' => $params['fecha_desde'],
                            'p_fh' => $params['fecha_hasta']
                        ];
                    }
    
                    array_push($extras['sps'], ['sp_estado_reintegro' => $params_sp]);
                    array_push($extras['queries'], $this->get_query('validacion', 'sp_estado_reintegro', $params_sp));
                    $response = $this->ejecutar_sp_directo('validacion', 'sp_estado_reintegro', $params_sp);
                    array_push($extras['responses'], ['sp_estado_reintegro' => $response]);

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
                }else{
                    $status = 'fail';
                    $code = -3;
                    $count = 0;
                    $message = 'El usuario logueado no tiene id_usuario_sqlserver';
                    array_push($errors, 'El logueado no tiene id_usuario_sqlserver');
                }

                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => $line,
                    'code' => $code,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]);
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Consulta las recetas realizadas para el afiliado
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function consultar_recetas(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/consultas/consultar-recetas',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $errors = [];
        $data = null;
        $status = null;
        $code = 0;
        $count = 0;
        $line = null;
        $params = [];
        $message = null;
        $logged_user = null;

        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $id_usuario_sqlserver = $logged_user['id_usuario_sqlserver'];

        try {
            $doc_tipo = request('doc_tipo');
            $doc_numero = request('doc_numero');
            $fecha_desde = request('fecha_desde') != null && request('fecha_desde') != '' ? request('fecha_desde') : null;
            $fecha_hasta = request('fecha_hasta') != null && request('fecha_hasta') != '' ? request('fecha_hasta') : null;

            if($fecha_desde != null){
                $desde = Carbon::parse($fecha_desde)->startOfDay();
            }else{
                $desde = null;
            }
            
            if($fecha_hasta != null){
                $hasta = Carbon::parse($fecha_hasta)->endOfDay();
            }else{
                $hasta = null;
            }

            $this->params = [
                'doc_tipo' => $doc_tipo,
                'doc_numero' => $doc_numero,
                'fecha_desde' => request('fecha_desde'),
                'fecha_hasta' => request('fecha_hasta'),
                'desde' => $desde,
                'hasta' => $hasta
            ];
            
            $this->params_sp = [
                'doc_tipo' => $doc_tipo,
                'doc_numero' => $doc_numero,
                'fecha_desde' => $desde,
                'fecha_hasta' => $hasta
            ];
            if($logged_user['roles']['name'] != 'afiliado mobile' && $logged_user['roles']['name'] != 'afiliado'){
                return response()->json([
                    'status' => 'unauthorized',
                    'count' => 0,
                    'errors' => $errors,
                    'message' => 'El usuario no es un afiliado',
                    'line' => $line,
                    'code' => -2,
                    'data' => $data,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]);
            }else{
                array_push($extras['verificado'], [
                    'sp_HC_Receta_select' => [
                        'doc_tipo' => request('doc_tipo'),
                        'doc_numero' => request('doc_numero')
                    ]
                ]);
                if ( empty(request('doc_tipo')) || empty(request('doc_numero')) || request('doc_tipo') == '' || request('doc_numero') == ''){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -3;
                }else{
                    array_push($extras['queries'], $this->get_query('validacion', 'sp_HC_Receta_select', $this->params_sp));
                    array_push($extras['sps'], ['sp_HC_Receta_select' => $this->params_sp]);
                    $response = $this->ejecutar_sp_directo('validacion', 'sp_HC_Receta_select', $this->params_sp);
                    array_push($extras['responses'], ['sp_HC_Receta_select' => $response]);

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
                    'line' => $line,
                    'code' => $code,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]);
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Consulta las validaciones realizadas para el afiliado
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function consultar_validaciones(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/consultas/consultar-validaciones',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $errors = [];
        $data = null;
        $status = null;
        $code = 0;
        $count = 0;
        $line = null;
        $params = [];
        $message = null;
        $logged_user = null;

        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $id_usuario_sqlserver = $logged_user['id_usuario_sqlserver'];

        try {
            $id_estado = request('id_estado');
            $filtrar_por_fechas = request('filtrar_por_fechas');
            $fecha_desde = request('fecha_desde');
            $fecha_hasta = request('fecha_hasta');
            $nro_afiliado = request('nro_afiliado');
            $codigo_interno = request('codigo_interno');

            $params = [
                'id_estado' => $id_estado,
                'filtrar_por_fechas' => $filtrar_por_fechas,
                'fecha_desde' => $fecha_desde,
                'fecha_hasta' => $fecha_hasta,
                'nro_afiliado' => $nro_afiliado,
                'codigo_interno' => $codigo_interno
            ];

            if($logged_user['roles']['name'] != 'afiliado mobile' && $logged_user['roles']['name'] != 'afiliado'){
                return response()->json([
                    'status' => 'unauthorized',
                    'count' => 0,
                    'errors' => $errors,
                    'message' => 'El usuario no es un afiliado',
                    'line' => $line,
                    'code' => -2,
                    'data' => $data,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]);
            }else{
                array_push($extras['verificado'], [
                    'AWEB_TraerAutorizaciones' => [
                            'nro_afiliado' => $nro_afiliado
                        ]
                    ]
                );
                if ( empty($nro_afiliado) || $nro_afiliado == '' || $nro_afiliado == null){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $errors = ['Parámetros insuficientes o incorrectos'];
                    $count = 0;
                    $data = null;
                    $code = -3;
                }else{
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
                    
                    if(isset($nro_afiliado)){
                        $this->params['afiliado'] = $nro_afiliado;
                    }
                    if(isset($codigo_interno)){
                        $this->params['codigo_interno'] = $codigo_interno;
                    }
                    
                    array_push($extras['sps'], ['AWEB_TraerAutorizaciones' => $this->params]);
                    array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerAutorizaciones', null));
                    $data = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerAutorizaciones', null);
                    array_push($extras['responses'], ['AWEB_TraerAutorizaciones' => $data]);
                    if(isset($data['error'])){
                        array_push($errors, $data['error']);
                        $status = 'fail';
                        $message = 'Fallo al realizar la consulta a la base de datos';
                    }else{
                        if(sizeof($data) == 0){
                            $count = sizeof($data);
                            $status = 'empty';
                            $message = 'No se encontraron registros con los parámetros otorgados';
                        }else{
                            $count = sizeof($data);
                            $status = 'ok';
                            $message = 'Registros obtenidos';
                        }
                    }
                }

                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => $line,
                    'code' => $code,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]);
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Consulta las prestaciones de una validación, el detalle
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function consultar_prestaciones_validacion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/consultas/consultar-prestaciones',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $errors = [];
        $data = null;
        $status = null;
        $code = 0;
        $count = 0;
        $line = null;
        $params = [];
        $message = null;
        $logged_user = null;

        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $id_usuario_sqlserver = $logged_user['id_usuario_sqlserver'];

        try {
            $codigo_interno = request('codigo_interno');

            $params = [
                'codigo_interno' => $codigo_interno,
            ];

            if($logged_user['roles']['name'] != 'afiliado mobile' && $logged_user['roles']['name'] != 'afiliado'){
                return response()->json([
                    'status' => 'unauthorized',
                    'count' => 0,
                    'errors' => $errors,
                    'message' => 'El usuario no es un afiliado',
                    'line' => $line,
                    'code' => -2,
                    'data' => $data,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]);
            }else{
                array_push($extras['verificado'], [
                    'AWEB_TraerAutorizaciones' => [
                        'codigo_interno' => $codigo_interno
                        ]
                    ]
                );
                if ( empty($codigo_interno) || $codigo_interno == '' || $codigo_interno == null){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $errors = ['Parámetros insuficientes o incorrectos'];
                    $count = 0;
                    $data = null;
                    $code = -3;
                }else{

                    $this->params['codigo_interno'] = $codigo_interno;
                    
                    array_push($extras['sps'], ['AWEB_TraerAutorizacionDetalle' => $this->params]);
                    array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerAutorizacionDetalle', null));
                    $data = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerAutorizacionDetalle', null);
                    array_push($extras['responses'], ['AWEB_TraerAutorizacionDetalle' => $data]);

                    if(isset($data['error'])){
                        array_push($errors, $data['error']);
                        $status = 'fail';
                        $code = -5;
                        $message = 'Fallo al realizar la consulta a la base de datos';
                    }else{
                        if(sizeof($data) == 0){
                            $count = sizeof($data);
                            $status = 'empty';
                            $code = -4;
                            $message = 'No se encontraron registros con los parámetros otorgados';
                        }else{
                            $count = sizeof($data);
                            $status = 'ok';
                            $code = 1;
                            $message = 'Registros obtenidos';
                        }
                    }
                }

                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => $line,
                    'code' => $code,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]);
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Consultar el estado de un afiliado
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function consultar_elegibilidad_afiliado(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/consultas/elegibilidad-afiliado',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $errors = [];
        $data = null;
        $status = null;
        $code = 0;
        $count = 0;
        $line = null;
        $params = [];
        $message = null;
        $logged_user = null;

        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $id_usuario_sqlserver = $logged_user['id_usuario_sqlserver'];

        try {
            $params = [
                'nro_afiliado' => request('nro_afiliado'),
                'tipo_doc' => request('tipo_doc'),
                'nro_doc' => request('nro_doc')
            ];
            if($logged_user['roles']['name'] != 'afiliado mobile' && $logged_user['roles']['name'] != 'afiliado'){
                return response()->json([
                    'status' => 'unauthorized',
                    'count' => 0,
                    'errors' => $errors,
                    'message' => 'El usuario no es un afiliado',
                    'line' => $line,
                    'code' => -2,
                    'data' => $data,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]);
            }else{
                if($id_usuario_sqlserver != 0){
                    // buscamos los datos del usuario
                    array_push($extras['sps'], ['sp_usuario_select' => ['p_id_usuario' => $id_usuario_sqlserver]]);
                    array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]));
                    $usuario_sqlserver = $this->ejecutar_sp_directo('admin', 'sp_usuario_select', ['p_id_usuario' => $id_usuario_sqlserver]);
                    array_push($extras['responses'], ['sp_usuario_select' => $usuario_sqlserver]);
        
                    if(is_array($usuario_sqlserver)){
                        $usuario_sqlserver = $usuario_sqlserver[0];
                    }

                    array_push($extras['verificado'], [
                        'ws_elegibilidad' => [
                            'nro_afiliado' => request('nro_afiliado'),
                            'tipo_doc' => request('tipo_doc'),
                            'nro_doc' => request('nro_doc')
                        ]
                    ]);
                    if ( empty(request('nro_afiliado')) && empty(request('tipo_doc')) && empty(request('nro_doc')) ){
                        array_push($errors, 'Parámetros incompletos o incorrectos');
                        $status = 'fail';
                        $message = 'Verifique los parámetros';
                        $count = 0;
                        $data = null;
                        $code = -4;
                    }else{
                        $params_sp = [
                            'p_n_afiliado' => request('nro_afiliado'),
                            'p_tipo_doc' => request('tipo_doc'),
                            'p_nro_doc' => is_string(request('nro_doc')) ? intval(request('nro_doc')) : request('nro_doc'), // si es string lo convierte a entero, sino lo pasa como viene
                        ];
                        array_push($extras['sps'], ['ws_elegibilidad' => $params_sp]);
                        array_push($extras['queries'], $this->get_query('validacion', 'ws_elegibilidad', $params_sp));
                        $response = $this->ejecutar_sp_directo('validacion', 'ws_elegibilidad', $params_sp);
                        array_push($extras['responses'], ['ws_elegibilidad' => $response]);
                        if(is_array($response) && array_key_exists('error', $response)){
                            array_push($errors, $response['error']);
                            $status = 'fail';
                            $message = 'Se produjo un error al realizar la petición';
                            $count = 0;
                            $data = null;
                            $code = -5;
                            // Log::channel('')->error(''); // buscar canales en config/loggin.php
                        }else if(empty($response)){
                            $status = 'empty';
                            $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                            $count = 0;
                            $data = $response;
                            $code = -6;
                            // Log::channel('')->info(''); // buscar canales en config/loggin.php
                        }else{
                            $status = 'ok';
                            $message = 'Transacción realizada con éxito.';
                            $count = sizeof($response);
                            $data = $response;
                            $code = 1;
                        }
                    }

                }else{
                    $status = 'fail';
                    $code = -3;
                    $count = 0;
                    $message = 'El usuario logueado no tiene id_usuario_sqlserver';
                    array_push($errors, 'El logueado no tiene id_usuario_sqlserver');
                }

                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => $line,
                    'code' => $code,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]);
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }
}