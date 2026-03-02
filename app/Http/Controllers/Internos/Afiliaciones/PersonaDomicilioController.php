<?php

namespace App\Http\Controllers\Internos\Afiliaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;

class PersonaDomicilioController extends ConexionSpController
{
    /**
     * Lista los domicilios de una persona o empresa
     */
    public function listar_domicilios(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = '/int/afiliaciones/persona/listar-domicilios';
        $this->permiso_requerido = 'gestionar afiliados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_persona_domicilio_Select';
        $tipo = request('tipo') != null ? request('tipo') : 'persona';
        if ( $tipo == 'persona' || $tipo == '' ){
            if(request('id_persona') != null){
                $this->params['id_persona'] = request('id_persona');
                $this->params_sp['id_persona'] = request('id_persona');
                $this->params_sp['historico'] = 1;
            }
            return $this->ejecutar_sp_simple();
        }
        // no se ha probado cuando tipo es empresa
        if ( $tipo == 'empresa' ){
            //	obtiene el id de la empresa de la persona
            $params_empresa['p_id_persona'] = request('id_persona');
            $this->sp = 'sp_empresa_Select';
            array_push($this->extras['sps'], ['sp_empresa_Select' => $this->params_sp]);
            array_push($this->extras['queries'], $this->get_query('afiliacion', 'sp_empresa_Select', $params_empresa));
            $empresa = $this->ejecutar_sp_directo('afiliacion', 'sp_empresa_Select', $params_empresa);
            array_push($extras['responses'], ['sp_empresa_Select' => $empresa]);
            $id_empresa = $empresa[0]->id_empresa;
            if($id_empresa != null){ 
                if(request('id_empresa') != null){
                    $this->params['id_empresa'] = request('id_empresa');
                    $this->params_sp['id_empresa'] = request('id_empresa');
                    $this->params_sp['historico'] = 1;
                }
                return $this->ejecutar_sp_simple();
            }else{
                array_push($errors, 'No se pudo obtener el id de la empresa');
                $status = 'fail';
                $message = 'No se pudo obtener el id de la empresa';
                $code = -1;
                $count = 0;
                $data = null;
                $user = User::with('roles', 'permissions')->find($this->user_id);
                $logged_user = $this->get_logged_user($user);
                return response()->json([
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
                ]); 
            }
        }
    }

    /**
     * Guarda un domicilio de una persona
     */
    public function agregar_domicilio(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = '/int/afiliaciones/persona/agregar-domicilio';
        $this->permiso_requerido = 'gestionar afiliados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_alta_domicilio';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'p_id_usuario'; // nombre del parámetro id_usuario, id_n_usuario
        $this->params = [
            'id_persona' 	      => request('id_persona'),
            'id_sucursal'         => ( empty(request('id_sucursal')) ) ? NULL : request('id_sucursal'),
            'id_tipo_domicilio'   => request('id_tipo_domicilio'),
            'id_zona'             => request('id_zona'),
            'n_tipo_domicilio'    => (empty(request('n_tipo_domicilio')) ) ? NULL : request('n_tipo_domicilio'),
            'id_localidad'        => request('id_localidad'),
            'calle'               => request('calle'),
            'puerta'              => request('puerta'),
            'piso'                => ( empty(request('piso') ) ) ? NULL : request('piso'),
            'departamento'        => ( empty(request('departamento')) ) ? NULL : request('departamento'),
            'latitud'             => ( empty(request('latitud')) ) ? NULL : request('latitud'),
            'longitud'            => ( empty(request('longitud')) ) ? NULL : request('longitud'),
        ];
        $this->params_sp = [
            'p_id_persona' 	        => request('id_persona'),
            'p_id_sucursal'         => ( empty(request('id_sucursal')) ) ? NULL : request('id_sucursal'),
            'p_tipo_objeto'         => ( empty(request('id_sucursal')) ) ? 'P'  : 'S',
            'p_id_tipo_domicilio'   => request('id_tipo_domicilio'),
            'p_id_zona'             => request('id_zona'),
            'p_n_domicilio'         => (empty(request('n_tipo_domicilio')) ) ? NULL : request('n_tipo_domicilio'),
            'p_id_localidad'        => request('id_localidad'),
            'p_calle'               => request('calle'),
            'p_puerta'              => request('puerta'),
            'p_piso'                => ( empty(request('piso') ) ) ? NULL : request('piso'),
            'p_departamento'        => ( empty(request('departamento')) ) ? NULL : request('departamento'),
            'p_latitud'             => ( empty(request('latitud')) ) ? NULL : request('latitud'),
            'p_longitud'            => ( empty(request('longitud')) ) ? NULL : request('longitud'),
            'p_link_gmaps'          => NULL,
            'p_fecha' 		        => Carbon::now()->format('Ymd H:i:s'),
        ];
        return $this->ejecutar_sp_simple();
    }
}