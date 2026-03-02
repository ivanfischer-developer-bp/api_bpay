<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;


class ABMTipoFacturaController extends ConexionSpController
{
    /**
     * Retorna un listado de los tipos de factura
     */
    public function listar_tipos_factura(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/tipos-factura/listar-tipos-factura';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_tipo_factura_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega un nuevo tipo de factura
     */
    public function agregar_tipo_factura(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post'; 
        $this->url = '/int/listados/tipos-factura/agregar-tipo-baja';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_tipo_factura_Insert';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'p_id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario
        $this->verificado = [
            $this->sp => [
                'cod_tipo_factura' => request('cod_tipo_factura'),
                'n_tipo_factura' => request('n_tipo_factura')
            ]
        ];
        if(empty(request('n_tipo_factura')) || empty(request('cod_tipo_factura'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'n_tipo_factura' => request('n_tipo_factura'),
            'defecto' => request('defecto'),
            'cod_tipo_factura' => request('cod_tipo_factura'),
            'ss_cod' => request('ss_cod')
        ];
        $this->params_sp = [
            'p_n_tipo_factura' => $this->params['n_tipo_factura'],
            'p_cod_tipo_factura' => $this->params['cod_tipo_factura'],
            'p_ss_cod' => $this->params['ss_cod'],
            'p_defecto' => ( empty($this->params['defecto']) ? 0 : 1 ),
            'p_fecha' => Carbon::now()->format('Ymd H:i:s')
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Actualiza un tipo de factura
     */
    public function actualizar_tipo_factura(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post'; 
        $this->url = '/int/listados/tipos-factura/actualizar-tipo-baja';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_tipo_factura_Update';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'p_id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario
        $this->verificado = [
            $this->sp => [
                'id_tipo_factura' => request('id_tipo_factura'),
                'n_tipo_factura' => request('n_tipo_factura')
            ]
        ];
        
        if(empty(request('n_tipo_factura')) || empty(request('id_tipo_factura'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'id_tipo_factura' => request('id_tipo_factura'),
            'n_tipo_factura' => request('n_tipo_factura'),
            'defecto' => request('defecto'),
            'cod_tipo_factura' => request('cod_tipo_factura'),
            'ss_cod' => request('ss_cod')
        ];
        $this->params_sp = [
            'p_id_tipo_factura' => $this->params['id_tipo_factura'],
            'p_n_tipo_factura' => $this->params['n_tipo_factura'],
            'p_n_tipo_factura' => $this->params['n_tipo_factura'],
            'p_cod_tipo_factura' => $this->params['cod_tipo_factura'],
            'p_ss_cod' => $this->params['ss_cod'],
            'p_defecto' => ( empty($this->params['defecto']) ? 0 : 1 ),
            'p_fecha' => Carbon::now()->format('Ymd H:i:s')
        ];
        return $this->ejecutar_sp_simple();
    }
}