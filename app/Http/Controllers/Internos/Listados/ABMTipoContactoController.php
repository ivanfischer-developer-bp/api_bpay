<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;

use DB;


class ABMTipoContactoController extends ConexionSpController
{
    /**
     * Retorna un listado de tipos de contactos.
     */
    public function listar_tipos_contactos(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/tipo-contacto/listar-tipos-contactos';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_tipo_contacto_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega un nuevo tipo de contacto
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_tipo_contacto(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/listados/tipo-contacto/agregar-tipo-contacto';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_tipo_contacto_Insert';
        $this->verificado = [
            'n_tipo_contacto' => request('n_tipo_contacto')
        ];
        if(empty(request('n_tipo_contacto'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }

        $this->params = [
            'n_tipo_contacto' => request('n_tipo_contacto'),
            'defecto' => request('defecto')
        ];

        $this->params_sp = [
            'n_tipo_contacto' => request('n_tipo_contacto'),
            'defecto' => request('defecto') != null && request('defecto') == true ? 1 : 0
        ];

        return $this->ejecutar_sp_simple();
    }

     /**
     * Actualiza los datos de un tipo de contacto
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function actualizar_tipo_contacto(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/listados/tipo-contacto/actualizar-tipo-contacto';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_tipo_contacto_Update';
        $this->verificado = [
            'id_tipo_contacto' => request('id_tipo_contacto'),
            'n_tipo_contacto' => request('n_tipo_contacto'),
        ];
        if(empty(request('n_tipo_contacto')) || empty(request('id_tipo_contacto'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'id_tipo_contacto' => request('id_tipo_contacto'),
            'n_tipo_contacto' => request('n_tipo_contacto'),
            'defecto' => request('defecto')
        ];

        $this->params_sp = [
            'id_tipo_contacto' => request('id_tipo_contacto'),
            'n_tipo_contacto' => request('n_tipo_contacto'),
            'defecto' => request('defecto') != null && request('defecto') == true ? 1 : 0
        ];
        return $this->ejecutar_sp_simple();
    }

}