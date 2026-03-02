<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;


class ABMOrigenAfiliadoController extends ConexionSpController
{

    /**
     * Lista origenes
     */
    public function listar_origen_afiliado(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = 'int/listados/origen-afiliado/listar-origen-afiliado';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_origen_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Crea un nuevo origen de afiliado
     */
    public function agregar_origen_afiliado(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/listados/origen-afiliado/agregar-origen-afiliado';
        $this->permiso_requerido = 'gestionar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_origen_insert';
        $this->params = [
            'n_origen' => request('n_origen'),
            'porcentaje_cuota' => request('porcentaje_cuota'),
            'porcentaje_servicio' => request('porcentaje_servicio'),
            'porcentaje_patologia' => request('porcentaje_patologia'),
            'porcentaje_copago' => request('porcentaje_copago'),
            'agrega_fam' => request('agrega_fam'),
            'primario' => request('primario'),
        ];
        $this->params_sp = [
            'p_n_origen' =>$this->params['n_origen'],
            'p_porcentaje_cuota' =>$this->params['porcentaje_cuota'],
            'p_porcentaje_servicio' =>$this->params['porcentaje_servicio'],
            'p_porcentaje_patologia' =>$this->params['porcentaje_patologia'],
            'p_porcentaje_copago' =>$this->params['porcentaje_copago'],
            'p_agrega_fam' =>$this->params['agrega_fam'],
            'p_primario' =>$this->params['primario'],
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Actualiza un origen de afiliado
     */
    public function actualizar_origen_afiliado(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/listados/origen-afiliado/actualizar-origen-afiliado';
        $this->permiso_requerido = 'gestionar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_origen_update';
        $this->params = [
            'id_origen' => request('id_origen'),
            'n_origen' => request('n_origen'),
            'porcentaje_cuota' => request('porcentaje_cuota'),
            'porcentaje_servicio' => request('porcentaje_servicio'),
            'porcentaje_patologia' => request('porcentaje_patologia'),
            'porcentaje_copago' => request('porcentaje_copago'),
            'agrega_fam' => request('agrega_fam'),
            'primario' => request('primario'),
        ];
        $this->params_sp = [
            'p_id_origen' =>$this->params['id_origen'],
            'p_n_origen' =>$this->params['n_origen'],
            'p_porcentaje_cuota' =>$this->params['porcentaje_cuota'],
            'p_porcentaje_servicio' =>$this->params['porcentaje_servicio'],
            'p_porcentaje_patologia' =>$this->params['porcentaje_patologia'],
            'p_porcentaje_copago' =>$this->params['porcentaje_copago'],
            'p_agrega_fam' =>$this->params['agrega_fam'],
            'p_primario' =>$this->params['primario'],
        ];
        return $this->ejecutar_sp_simple();
    }
}