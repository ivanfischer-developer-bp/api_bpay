<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;


class ABMGravamenController extends ConexionSpController
{
    /**
     * Obtiene un listado de gravámenes
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function listar_gravamenes(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/gravamen/listar-gravamenes';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_gravamen_Select';
        return $this->ejecutar_sp_simple();
    }

    
    /**
     * Agrega un nuevo gravamen
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_gravamen(Request $request)
    {
        // no implementado aún 
        
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/gravamen/agregar-gravamen';
        return $this->endpoint_no_implementado();
    }

    /**
     * Actualiza un gravamen existente
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function actualizar_gravamen(Request $request)
    {
        // no implementado aún 

        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/gravamen/actualizar-gravamen';
        return $this->endpoint_no_implementado();   
    }
}