<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;


class ABMPromotorController extends ConexionSpController
{
    /**
     * Obtiene un listado de toos los promotores
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function listar_promotores(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/promotores';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_promotor_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Obtiene un listado de los promotores que coinciden con los parámetros dados
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function buscar_promotor(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/promotores/buscar-promotor';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_promotor_Select';
        if(request('id_persona') != null){
            $this->params['id_persona'] = request('id_persona');
            $this->params_sp['p_id_persona'] = request('id_persona');
        }
        if(request('id_empresa') != null){
            $this->params['id_empresa'] = request('id_empresa');
            $this->params_sp['p_id_empresa'] = request('id_empresa');
        }
        if(request('cuit') != null){
            $this->params['cuit'] = request('cuit');
            $this->params_sp['p_cuit'] = request('cuit');
        }
        if(request('nombre') != null){
            $this->params['nombre'] = request('nombre');
            $this->params_sp['p_n_persona'] = request('nombre');
        }
        if(request('id_promotor') != null){
            $this->params['id_promotor'] = request('id_promotor');
            $this->params_sp['p_id_promotor'] = request('id_promotor');
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega un nuevo promotor 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_promotor(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = '/int/listados/promotores/agregar-promotor';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_promotor_Insert';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario
        $this->verificado = [
            $this->sp => [
                'id_persona' => request('id_persona'), 
                'cod_objeto' => request('cod_objeto')
            ]
        ];
        if(empty(request('id_persona')) || empty(request('cod_objeto'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'id_persona' => request('id_persona'),
            'cod_objeto' => request('cod_objeto'),
            'id_zona' => request('id_zona')
        ];
        $this->params_sp = [
            'id_persona' => $this->params['id_persona'],
            'cod_promotor' => $this->params['cod_objeto'],
            'id_zona' => $this->params['id_zona'],
            'fecha' => Carbon::now()->format('Ymd H:i:s'),
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Actualiza un promotor 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function actualizar_promotor(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = '/int/listados/promotores/actualizar-promotor';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_promotor_Update';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro: id_usuario, id_n_usuario, p_id_usuario
        $this->params = [
            'id_promotor' => request('id_promotor'),
            'cod_objeto' => request('cod_objeto'),
            'id_zona' => request('id_zona')
        ];
        $this->params_sp = [
            'id_promotor' => $this->params['id_promotor'],
            'cod_promotor' => $this->params['cod_objeto'],
            'id_zona' => $this->params['id_zona'],
        ];
        return $this->ejecutar_sp_simple();
    }

}