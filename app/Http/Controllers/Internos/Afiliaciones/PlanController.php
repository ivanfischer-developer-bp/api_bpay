<?php

namespace App\Http\Controllers\Internos\Afiliaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;

class PlanController extends ConexionSpController
{

    /**
     * Busca los cambios de planes de un afiliado
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function buscar_planes_afiliado(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/afiliaciones/afiliado/buscar-planes-afiliado';
        $this->permiso_requerido = 'gestionar afiliados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_grupo_plan_Select';
        $this->param_id_usuario = '';
        $this->tipo_id_usuario = ''; // id, usuario, email, param
        $this->param_usuario = '';
        $this->params = [
            'activo' => request('activo'), // cuando activo viene en 1 muestar el actual, sino los históricos
            'id_grupo' => request('id_grupo')
        ];
        $this->params_sp = [
            'historico' =>	$this->params['activo'],
            'id_grupo' =>	$this->params['id_grupo']
        ];
   
        return $this->ejecutar_sp_simple();
    }

    /**
     * Guarda cambia el plan a un afiliado
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function cambiar_plan_afiliado(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';
        $this->url = 'int/afiliaciones/afiliado/cambiar-plan-afiliado';
        $this->permiso_requerido = 'gestionar afiliados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_grupo_plan_Insert';
        $this->tipo_id_usuario = 'id'; 
        $this->param_id_usuario = 'id_usuario';
        $this->param_usuario = '';
        $this->params = [
            'id_grupo' => request('id_grupo'),
            'id_plan' => request('id_plan'),
            'fecha' => request('fecha')
        ];
        $this->params_sp = [
            'id_grupo'  =>	$this->params['id_grupo'],
            'id_plan'   =>	$this->params['id_plan'],
            'fecha'     => 	Carbon::parse($this->params['fecha'])->format('Ymd'),
        ];
        return $this->ejecutar_sp_simple();
    }
}
