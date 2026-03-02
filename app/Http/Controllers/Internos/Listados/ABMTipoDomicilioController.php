<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;

use DB;


class ABMTipoDomicilioController extends ConexionSpController
{
    /**
     * Lista tipos de domicilios
     */
    public function listar_tipos_domicilios(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  
        $this->url = '/int/listados/tipo-domicilio/listar-tipos-domicilios';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_tipo_domicilio_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega un nuevo tipo de domicilio
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_tipo_domicilio(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/listados/tipo-domicilio/agregar-tipo-domicilio';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_tipo_domicilio_Insert';
        $this->verificado = [
            'n_tipo_domicilio' => request('n_tipo_domicilio')
        ];
        if(empty(request('n_tipo_domicilio'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }

        $this->params = [
            'n_tipo_domicilio' => request('n_tipo_domicilio'),
            'defecto' => request('defecto')
        ];

        $this->params_sp = [
            'n_tipo_domicilio' => request('n_tipo_domicilio'),
            'factura' => 0,
            'defecto' => request('defecto') != null && request('defecto') == true ? 1 : 0
        ];

        return $this->ejecutar_sp_simple();
    }

     /**
     * Actualiza los datos de un tipo de domiciliio
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function actualizar_tipo_domicilio(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/listados/tipo-domicilio/actualizar-tipo-domicilio';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_tipo_domicilio_Update';
        $this->verificado = [
            'id_tipo_domicilio' => request('id_tipo_domicilio'),
            'n_tipo_domicilio' => request('n_tipo_domicilio'),
        ];
        if(empty(request('n_tipo_domicilio')) || empty(request('id_tipo_domicilio'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'id_tipo_domicilio' => request('id_tipo_domicilio'),
            'n_tipo_domicilio' => request('n_tipo_domicilio'),
            'defecto' => request('defecto')
        ];

        $this->params_sp = [
            'id_tipo_domicilio' => request('id_tipo_domicilio'),
            'n_tipo_domicilio' => request('n_tipo_domicilio'),
            'factura' => 0,
            'defecto' => request('defecto') != null && request('defecto') == true ? 1 : 0
        ];
        return $this->ejecutar_sp_simple();
    }
}