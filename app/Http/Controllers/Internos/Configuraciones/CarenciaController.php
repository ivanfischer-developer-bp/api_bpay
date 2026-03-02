<?php

namespace App\Http\Controllers\Internos\Configuraciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;


class CarenciaController extends ConexionSpController
{

    /**
     * Obtiene un listado de carencias
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function buscar_carencias(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/configuraciones/carencias/buscar-carencias';
        $this->permiso_requerido = '';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_carencia_select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega una nueva carencia
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_carencia(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/configuraciones/carencias/agregar-carencia';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_carencia_insert';
        $this->verificado = [
            $this->sp => [
                'n_carencia' => request('n_carencia')
            ]
        ];
        if(empty(request('n_carencia'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'n_carencia' => request('n_carencia'),
            'cantidad' => request('cantidad'),
            'defecto' => request('defecto') !== NULL ? request('defecto') : 0,
        ];
        $this->params_sp = [
            'p_n_carencia' => $this->params['n_carencia'],
            'p_cantidad' => $this->params['cantidad'],
            'p_defecto' => $this->params['defecto']
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Actualiza una carencia existente
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function actualizar_carencia(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/configuraciones/carencias/actualizar-carencia';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_carencia_update';
        $this->verificado = [
            $this->sp => [
                'n_carencia' => request('n_carencia')
            ]
        ];
        if(empty(request('n_carencia'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'id_carencia' => request('id_carencia'),
            'n_carencia' => request('n_carencia'),
            'cantidad' => request('cantidad'),
            'defecto' => request('defecto') !== NULL ? request('defecto') : 0,
        ];
        $this->params_sp = [
            'p_id_carencia' => $this->params['id_carencia'],
            'p_n_carencia' => $this->params['n_carencia'],
            'p_cantidad' => $this->params['cantidad'],
            'p_defecto' => $this->params['defecto']
        ];
        return $this->ejecutar_sp_simple();
    }
}