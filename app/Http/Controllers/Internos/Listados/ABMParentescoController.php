<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;


class ABMParentescoController extends ConexionSpController
{
    /**
     * Obtiene un listado de los parentescos
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function listar_parentescos(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/parentescos/listar-parentescos';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_parentesco_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Añade un nuevo parentesco
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_parentesco(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post'; 
        $this->url = '/int/listados/parentescos/agregar-parentesco';
        $this->permiso_requerido = 'gestionar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_parentesco_Insert';
        $this->verificado = [
            $this->sp => [
                'n_parentesco' => request('n_parentesco')
            ]
        ];
        if(empty(request('n_parentesco'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'n_parentesco' => request('n_parentesco'),
            'primario' => request('primario') !== NULL ? request('primario') : NULL,
            'tope' => request('tope') !== NULL ? request('tope') : NULL,
        ];
        $this->params_sp = [
            'p_n_parentesco' => $this->params['n_parentesco'],
            'p_primario' => $this->params['primario'] == 1 ? 1 : 0,
            'p_tope' => $this->params['tope'] !== NULL ? $this->params['tope'] : NULL
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Actualiza los datos de un parentesco
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function actualizar_parentesco(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post'; 
        $this->url = '/int/listados/parentescos/actualizar-parentesco';
        $this->permiso_requerido = 'gestionar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_parentesco_Update';
        $this->verificado = [
            $this->sp => [
                'n_parentesco' => request('n_parentesco')
            ]
        ];
        if(empty(request('n_parentesco'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'id_parentesco' => request('id_parentesco'),
            'n_parentesco' => request('n_parentesco'),
            'primario' => request('primario') !== NULL ? request('primario') : NULL,
            'tope' => request('tope') !== NULL ? request('tope') : NULL,
        ];
        $this->params_sp = [
            'p_id_parentesco' => $this->params['id_parentesco'],
            'p_n_parentesco' => $this->params['n_parentesco'],
            'p_primario' => $this->params['primario'] == 1 ? 1 : 0,
            'p_tope' => $this->params['tope'] !== NULL ? $this->params['tope'] : NULL
        ];
        return $this->ejecutar_sp_simple();
    }
}