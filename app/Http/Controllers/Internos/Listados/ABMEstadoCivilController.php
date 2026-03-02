<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;


class ABMEstadoCivilController extends ConexionSpController
{

    /**
     * Lista estados civiles
     */
    public function listar_estados_civiles(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/estado-civil/listar-estados-civiles';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_estado_civil_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega un nuevo estado civil
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_estado_civil(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/listados/estado_civil/agregar-estado_civil';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_estado_civil_Insert';
        $this->verificado = [
            'n_estado_civil' => request('n_estado_civil')
        ];
        if(empty(request('n_estado_civil'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }

        $this->params = [
            'n_estado_civil' => request('n_estado_civil'),
            'defecto' => request('defecto')
        ];

        $this->params_sp = [
            'n_estado_civil' => request('n_estado_civil'),
            'defecto' => request('defecto') != null && request('defecto') == true ? 1 : 0
        ];

        return $this->ejecutar_sp_simple();
    }

     /**
     * Actualiza los datos de un estado civil
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function actualizar_estado_civil(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/listados/estado-civil/actualizar-estado-civil';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_estado_civil_Update';
        $this->verificado = [
            'id_estado_civil' => request('id_estado_civil'),
            'n_estado_civil' => request('n_estado_civil'),
        ];
        if(empty(request('n_estado_civil')) || empty(request('id_estado_civil'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'id_estado_civil' => request('id_estado_civil'),
            'n_estado_civil' => request('n_estado_civil'),
            'defecto' => request('defecto')
        ];

        $this->params_sp = [
            'id_estado_civil' => request('id_estado_civil'),
            'n_estado_civil' => request('n_estado_civil'),
            'defecto' => request('defecto') != null && request('defecto') == true ? 1 : 0
        ];
        return $this->ejecutar_sp_simple();
    }
}