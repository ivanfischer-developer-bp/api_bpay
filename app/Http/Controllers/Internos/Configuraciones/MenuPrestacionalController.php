<?php

namespace App\Http\Controllers\Internos\Configuraciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;


class MenuPrestacionalController extends ConexionSpController
{

    /**
     * Obtiene un listado de tipo de prestacion
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function buscar_tipo_prestacion(Request $request)
    {

        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/configuraciones/menu-prestacional/buscar-tipo-prestacion';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_menu_tipo_prestacion_select';
        if(request('id_menu') != null){
            $this->params['id_menu'] = request('id_menu');
            $this->params_sp['p_id_menu'] = request('id_menu');
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Obtiene un listado de tipo de prestacion
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function listar_menu_prestacional(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/configuraciones/menu-prestacional/listar-menu-prestacional';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_menu_select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Añade un nuevo menu prestacional
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_menu_prestacional(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/configuraciones/menu-prestacional/agregar-menu-prestacional';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_menu_insert';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'p_id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario
        $this->verificado = [
            $this->sp => [
                'n_menu' => request('n_menu')
            ]
        ];
        if(empty(request('n_menu')) || request('n_menu') == null){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'n_menu' => request('n_menu')
        ];
        $this->params_sp = [
            'p_n_menu' => $this->params['n_menu'],
            'p_fecha' => Carbon::now()->format('Ymd H:i:s'),
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Actualiza los datos un nuevo menu prestacional
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function actualizar_menu_prestacional(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/configuraciones/menu-prestacional/actualizar-menu-prestacional';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_menu_update';
        $this->verificado = [
            $this->sp => [
                'id_menu' => request('id_menu'),
                'n_menu' => request('n_menu')
            ]
        ];
        if(empty(request('n_menu')) || request('n_menu') == null || empty(request('id_menu')) || request('id_menu') == null){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'n_menu' => request('n_menu'),
            'id_menu' => request('id_menu')
        ];
        $this->params_sp = [
            'p_n_menu' => $this->params['n_menu'],
            'p_id_menu' => $this->params['id_menu']
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega un nuevo tipo de prestacion al menu prestacional
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_tipo_prestacion_menu(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/configuraciones/menu-prestacional/agregar-tipo-prestacion-menu';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_menu_tipo_prestacion_insert';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'p_id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario
        $this->verificado = [
            $this->sp => [
                'id_menu' => request('id_menu'),
                'id_tipo_prestacion' => request('id_tipo_prestacion')
            ]
        ];
        if(empty(request('id_menu')) || request('id_menu') == null
            || empty(request('id_tipo_prestacion')) || request('id_tipo_prestacion') == null
            ){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'id_menu' => request('id_menu'),
            'id_tipo_prestacion' => request('id_tipo_prestacion')
        ];
        $this->params_sp = [
            'p_id_menu' => $this->params['id_menu'],
            // 'p_id_tipo_prestacion' => $this->params['id_tipo_prestacion'],
            'p_fecha' => Carbon::now()->format('Ymd H:i:s'),
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Quita un tipo de prestacion al menu prestacional
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function quitar_tipo_prestacion_menu(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/configuraciones/menu-prestacional/quitar-tipo-prestacion-menu';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_baja_Insert';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario
        $this->params = [
            'id_vigencia' => request('id_vigencia'),
        ];
        $this->params_sp = [
            'id_tipo_baja' => 1,
            'fec_baja' => Carbon::now()->format('Ymd'),
            'id_vigencia' => $this->params['id_vigencia'],
            'n_baja' => 'Baja tipo expediente plan'
        ];
        return $this->ejecutar_sp_simple();
    }
}