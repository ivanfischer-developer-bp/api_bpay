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

class MobileReintegroController extends ConexionSpController {

    /**
     * Consulta el estado de los reintegros
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_reintegro(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/reintegros/agregar-reintegro',
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
                'p_fecha' => request('p_fecha'),
                'p_id_tipo_reintegro' => request('p_id_tipo_reintegro'),
                'p_cuit_prestador' => request('p_cuit_prestador'),
                'p_n_prestador' => request('p_n_prestador'),
                'p_referencia' => request('p_referencia'),
                'p_letra' => request('p_letra'),
                'p_sucursal' => request('p_sucursal'),
                'p_numero' => request('p_numero'),
                'p_importe' => request('p_importe'),
                'p_json_detalles' => request('p_json_detalles'),
                'p_id_persona' => request('p_id_persona'),
                'p_cbu' => request('p_cbu'),
                'p_id_usuario' => request('p_id_usuario'),
                'p_tiene_adjunto' => request('p_tiene_adjunto')
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
                    $json = json_encode(request('p_json_detalles'));
                    $params_sp = [
                        'p_fecha' => request('p_fecha'),
                        'p_id_tipo_reintegro' => request('p_id_tipo_reintegro'),
                        'p_cuit_prestador' => request('p_cuit_prestador'),
                        'p_n_prestador' => request('p_n_prestador'),
                        'p_referencia' => request('p_referencia'),
                        'p_letra' => request('p_letra'),
                        'p_sucursal' => request('p_sucursal'),
                        'p_numero' => request('p_numero'),
                        'p_importe' => request('p_importe'),
                        'p_json_detalles' => $json,
                        'p_id_persona' => request('p_id_persona'),
                        'p_cbu' => request('p_cbu'),
                        'p_id_usuario' => request('p_id_usuario'),
                        'p_tiene_adjunto' => request('p_tiene_adjunto')
                    ];
                    array_push($extras['sps'], ['sp_reintegro_insert' => $params_sp]);
                    array_push($extras['queries'], $this->get_query('validacion', 'sp_reintegro_insert', $params_sp));
                    $response = $this->ejecutar_sp_directo('validacion', 'sp_reintegro_insert', $params_sp);
                    array_push($extras['responses'], ['sp_reintegro_insert' => $response]);

                    if(is_array($response) && array_key_exists('error', $response)){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -4;
                        // Log::channel('')->error(''); // buscar canales en config/loggin.php
                    }else if(is_array($response) && $response[0]->nro_reintegro == null){
                        array_push($errors, $response[0]->msg);
                        $status = 'fail';
                        $message = $response[0]->msg;
                        $count = 0;
                        $data = null;
                        $code = -5;
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
                        $data = $response[0]->nro_reintegro;
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

}