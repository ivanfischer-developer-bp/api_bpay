<?php

namespace App\Http\Controllers\Internos\General;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;

class BajaController extends ConexionSpController
{
    /**
     * Dar de baja o eliminar registros en al base de datos sqlserver
     */
    public function realizar_baja(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post'; 
        $this->url = '/int/general/realizar-baja';
        $baja = request('baja');
        $permiso = 'eliminar '.strtolower($baja['objeto']);
        $this->permiso_requerido = $permiso;
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_baja_Insert';  // para grupo familiar sp_grupo_estado_Update p_id_estado p_id_grupo p_id_usuario p_fecha p_id_tipo_baja p_observaciones
        $this->tipo_id_usuario = 'id'; 
        $this->param_id_usuario = 'id_usuario'; 
        if($baja['objeto'] != null){
            $this->params['objeto'] = $baja['objeto'];
        }
        if($baja['id_tipo_baja'] != null){
            $this->params['id_tipo_baja'] = $baja['id_tipo_baja'];
            $this->params_sp['id_tipo_baja'] = $baja['id_tipo_baja'];
        }
        if(isset($baja['fecha_baja'])){
            $this->params['fecha_baja'] = $baja['fecha_baja'];
            $this->params_sp['fec_baja'] = Carbon::parse($baja['fecha_baja'])->format('Ymd H:i:s');
        }
        if(isset($baja['id_tipo_baja'])){
            $this->params['id_tipo_baja'] = $baja['id_tipo_baja'];
            $this->params_sp['id_tipo_baja'] = $baja['id_tipo_baja'];
        }
        if(isset($baja['id_vigencia'])){
            $this->params['id_vigencia'] = $baja['id_vigencia'];
            $this->params_sp['id_vigencia'] = $baja['id_vigencia'];
        }
        // se quita porque observaciones no es un parametro del sp en este momento
        // if(isset($baja['observaciones'])){
        //     $this->params['observaciones'] = $baja['observaciones'];
        //     $this->params_sp['observaciones'] = $baja['observaciones'];
        // }else{
        //     $this->params['observaciones'] = '';
        //     $this->params_sp['observaciones'] = '';
        // }

        //  verifica parametros obligatorios
        $this->verificado = [
            'fecha_baja' => isset($baja['fecha_baja']) ? $baja['fecha_baja'] : null,
            'id_vigencia' => isset($baja['id_vigencia']) ? $baja['id_vigencia'] : null,
            'id_tipo_baja' => isset($baja['id_tipo_baja']) ? $baja['id_tipo_baja'] : null
        ];
        if ( !isset($baja['fecha_baja']) 
            || $baja['fecha_baja'] == '' 
            || !isset($baja['id_vigencia']) 
            || $baja['id_vigencia'] <= 1  
            || !isset($baja['id_tipo_baja']) 
            ){
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => ['Parámetros incorrectos o incompletos'],
                'message' => 'Verifique los parámetros',
                'line' => null,
                'code' => -1,
                'data' => null,
                'params' => $this->params,
                'extras' => [
                    'api_software_version' => config('site.software_version'),
                    'ambiente' => config('site.ambiente'),
                    'url' => $this->url,
                    'controller' => $this->controlador,
                    'function' => $this->funcion,
                    'queries' => [],
                    'sps' => [],
                    'responses' => [],
                    'verificado' => $this->verificado
                ],
                'logged_user' => $logged_user,
            ]); 
        }

        return $this->ejecutar_sp_simple();
    }
}