<?php

namespace App\Http\Controllers\Internos\Coberturas;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use File;
use Storage;
use Carbon\Carbon;

class CoberturaEspecialController extends ConexionSpController
{
    /**
     * Obtiene las coberturas especiales de un afiliado mediante sp_persona_cobertura_select
     * Endpoint interno 
     * sp_persona_cobertura_select
     * @param p_id_persona number en el request, id de la persona a consultar las coberturas especiales.
     */
    public function consultar_coberturas_especiales_afiliado(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/coberturas-especiales/afiliado-consultar',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'responses' => [],
            'sps' => [
                'sp_persona_cobertura_select'
            ],
            'queries' => []
        ];
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            
            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            $code = null;

            if($user->hasPermissionTo('consultar coberturas especiales')){
                $id_persona = request('id_persona');
                
                $this->params = [
                    'p_id_persona' => $id_persona
                ];

                // ejecuta el sp
                try {
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_persona_cobertura_select', null));
                    $response = $this->ejecutar_sp_directo('afiliacion', 'sp_persona_cobertura_select', null);
                    array_push($extras['responses'], ['sp_persona_cobertura_select' => $response]);
                    // return $response;
                    // prepara la respuesta
                    // si hubo errores al ejecutar el sp
                    if(is_array($response) && array_key_exists('error', $response)){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -3;
                    }else if($count = sizeof($response) == 0){
                        $status = 'empty';
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                        $count = sizeof($response);
                        $data = $response;
                        $code = 2;
                    }else{
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = sizeof($response);
                        $data = $response;
                        $code = 1;
                    }
                } catch (\Throwable $th) {
                    array_push($errors, 'Line: '.$th->getLine().' - '.$th->getMessage());
                    $status = 'fail';
                    $message = $th->getMessage();
                    $code = $th->getCode();
                    $line = $th->getLine();
                    $count = 0;
                    $data = null;
                }
            }else{
                $status = 'unauthorized';
                $code = -1;
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CONSULTAR COBERTURAS ESPECIALES';
                $count  = 0;
                $data = null;
                $error = null;
            }
            // retorna el response
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => $code,
                'data' => $data,
                'params' => $this->params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]); 
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' - '.$th->getMessage());
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

    /**
     * Guarda una cobertura especial de un afiliado mediante sp_persona_cobertura_insert
     * Endpoint interno
     * sp_persona_cobertura_insert
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_cobertura_especial_afiliado(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/coberturas-especiales/agregar-cobertura-especial-afiliado';
        $this->permiso_requerido = 'consultar coberturas especiales';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_persona_cobertura_insert';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'p_id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario

        $this->params = [
            'cobertura' => request('cobertura'),
        ];
        $this->params_sp = [
            'p_id_persona' => $this->params['cobertura']['id_persona'],
            'p_fecha' => Carbon::now()->format('Y-m-d'),
            'p_permanente' => $this->params['cobertura']['permanente'],
            'p_prevalente' => $this->params['cobertura']['prevalente'],
            'p_fec_vigencia' => $this->params['cobertura']['fec_vigencia'] != null ? Carbon::parse($this->params['cobertura']['fec_vigencia'])->format('Y-m-d') : null,
            'p_fec_vto' => $this->params['cobertura']['fec_vto'] != null ? Carbon::parse($this->params['cobertura']['fec_vto'])->format('Y-m-d') : null,
            'p_porcentaje' => $this->params['cobertura']['porcentaje'] != null ? $this->params['cobertura']['porcentaje'] : 0.0,
            'p_id_patologia' => $this->params['cobertura']['id_patologia'],
            'p_importe_recargo' => $this->params['cobertura']['importe_recargo'] != null ? $this->params['cobertura']['importe_recargo'] : 0.0,
            'p_n_medico' => $this->params['cobertura']['n_medico'],
            'p_n_matricula' => $this->params['cobertura']['n_matricula'],
            'p_fecha_fum' => $this->params['cobertura']['fecha_fum'] != null ? Carbon::parse($this->params['cobertura']['fecha_fum'])->format('Y-m-d') : null,
            'p_fecha_fpp' => $this->params['cobertura']['fecha_fpp'] != null ? Carbon::parse($this->params['cobertura']['fecha_fpp'])->format('Y-m-d') : null,
            'p_n_drogas' => $this->params['cobertura']['n_drogas'],
            'p_id_cie_item' => $this->params['cobertura']['id_cie_item'],
            'p_n_archivo' => $this->params['cobertura']['n_archivo'],
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Guarda una cobertura especial de un afiliado mediante sp_persona_cobertura_insert
     * Endpoint interno
     * sp_persona_cobertura_insert
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function actualizar_cobertura_especial_afiliado(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/coberturas-especiales/actualizar-cobertura-especial-afiliado';
        $this->permiso_requerido = 'consultar coberturas especiales';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_persona_cobertura_update';
        $this->params = [
            'cobertura' => request('cobertura'),
        ];
        $this->params_sp = [
            'p_id_persona_cobertura' => $this->params['cobertura']['id_persona_cobertura'],
            'p_fecha' => Carbon::now()->format('Y-m-d'),
            'p_permanente' => $this->params['cobertura']['permanente'],
            'p_prevalente' => $this->params['cobertura']['prevalente'],
            'p_fec_vigencia' => $this->params['cobertura']['fec_vigencia'] != null ? Carbon::parse($this->params['cobertura']['fec_vigencia'])->format('Y-m-d') : null,
            'p_fec_vto' => $this->params['cobertura']['fec_vto'] != null ? Carbon::parse($this->params['cobertura']['fec_vto'])->format('Y-m-d') : null,
            'p_porcentaje' => $this->params['cobertura']['porcentaje'] != null ? $this->params['cobertura']['porcentaje'] : 0.0,
            'p_id_patologia' => $this->params['cobertura']['id_patologia'],
            'p_importe_recargo' => $this->params['cobertura']['importe_recargo'] != null ? $this->params['cobertura']['importe_recargo'] : 0.0,
            'p_n_medico' => $this->params['cobertura']['n_medico'],
            'p_n_matricula' => $this->params['cobertura']['n_matricula'],
            'p_fecha_fum' => $this->params['cobertura']['fecha_fum'] != null ? Carbon::parse($this->params['cobertura']['fecha_fum'])->format('Y-m-d') : null,
            'p_fecha_fpp' => $this->params['cobertura']['fecha_fpp'] != null ? Carbon::parse($this->params['cobertura']['fecha_fpp'])->format('Y-m-d') : null,
            'p_n_drogas' => $this->params['cobertura']['n_drogas'],
            'p_id_cie_item' => $this->params['cobertura']['id_cie_item'],
            'p_n_archivo' => $this->params['cobertura']['n_archivo'],
        ];
        return $this->ejecutar_sp_simple();
    }
 
}

