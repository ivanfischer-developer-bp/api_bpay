<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;


class ABMActividadController extends ConexionSpController
{

    /**
     * Lista actividades
     */
    public function listar_actividades(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/actividades/listar-actividades';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_actividad_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega una nueva actividad
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_actividad(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/listados/actividad/agregar-actividad';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_actividad_Insert';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro id_usuario, id_n_usuario
        $this->verificado = [
            'n_actividad' => request('n_actividad')
        ];
        if(empty(request('n_actividad'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }

        $this->params = [
            'n_actividad' => request('n_actividad'),
            'defecto' => request('defecto')
        ];

        $this->params_sp = [
            'n_actividad' => request('n_actividad'),
            'defecto' => request('defecto') != null && request('defecto') == true ? 1 : 0,
            'fecha' => Carbon::now()->format('Ymd H:i:s')
        ];

        return $this->ejecutar_sp_simple();
    }

     /**
     * Actualiza los datos de una actividad
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function actualizar_actividad(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/listados/actividad/actualizar-actividad';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_actividad_Update';
        $this->verificado = [
            'id_actividad' => request('id_actividad'),
            'n_actividad' => request('n_actividad'),
        ];
        if(empty(request('n_actividad')) || empty(request('id_actividad'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'id_actividad' => request('id_actividad'),
            'n_actividad' => request('n_actividad'),
            'defecto' => request('defecto')
        ];

        $this->params_sp = [
            'id_actividad' => request('id_actividad'),
            'n_actividad' => request('n_actividad'),
            'defecto' => request('defecto') != null && request('defecto') == true ? 1 : 0
        ];
        return $this->ejecutar_sp_simple();
    }
}