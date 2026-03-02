<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;


class ABMObraSocialController extends ConexionSpController
{

    /**
     * Busca una obra social por alguno de los parámetros
     * Obtiene un listado de las obras sociales que coinciden con los 
     * parámetros dados
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function buscar_obra_social(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/listados/obra-social/buscar-obra-social';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_osocial_Select';
        if(request('cuit') != null){
            $this->params['cuit'] = request('cuit');
            $this->params_sp['p_cuit'] = request('cuit');
        }
        if(request('id_empresa') != null){
            $this->params['id_empresa'] = request('id_empresa');
            $this->params_sp['p_id_empresa'] = request('id_empresa');
        }
        if(request('id_osocial') != null){
            $this->params['id_osocial'] = request('id_osocial');
            $this->params_sp['p_id_osocial'] = request('id_osocial');
        }
        if(request('id_persona') != null){
            $this->params['id_persona'] = request('id_persona');
            $this->params_sp['p_id_persona'] = request('id_persona');
        }
        if(request('n_persona') != null){
            $this->params['n_persona'] = request('n_persona');
            $this->params_sp['p_n_persona'] = request('n_persona');
        }
        return $this->ejecutar_sp_simple();
    }
}