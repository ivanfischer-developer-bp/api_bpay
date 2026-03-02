<?php

namespace App\Http\Controllers\Internos\Configuraciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;


class EstadoGrupoController extends ConexionSpController
{
    /**
     * Obtiene un listado de estados de grupos
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function buscar_estados_grupos(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/configuraciones/estados-grupos/buscar-estados-grupos';
        $this->permiso_requerido = '';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_estado_select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega un nuevo estado de grupo
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_estado_grupo(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/configuraciones/estados-grupos/agregar-estado-grupo';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_estado_insert';
        $this->verificado = [
            $this->sp => [
                'n_estado' => request('n_estado')
            ]
        ];
        if(empty(request('n_estado'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'n_estado'  => request('n_estado'),
            'activo'    => !empty(request('activo')) && request('activo') == 1 ? 1 : 0,
            'baja'      => !empty(request('baja')) && request('baja') == 1 ? 1 : 0,
            'consume'   => !empty(request('consume')) && request('consume') == 1 ? 1 : 0,
            'defecto'   => !empty(request('defecto')) && request('defecto') == 1 ? 1 : 0,
            'factura'   => !empty(request('factura')) && request('factura') == 1 ? 1 : 0,
            'moroso'    => !empty(request('moroso')) && request('moroso') == 1 ? 1 : 0,
            'suspendido'=> !empty(request('suspendido')) && request('suspendido') == 1 ? 1 : 0,
            'sin_basico'=> !empty(request('sin_basico')) && request('sin_basico') == 1 ? 1 : 0
        ];
        $this->params_sp = [
            'n_estado'  => $this->params['n_estado'],
            'activo'    => ( !empty($this->params['activo']) && $this->params['activo'] == 1 ? 1 : 0 ),
            'baja'      => ( !empty($this->params['baja']) && $this->params['baja'] == 1 ? 1 : 0 ),
            'consume'   => ( !empty($this->params['consume']) && $this->params['consume'] == 1 ? 1 : 0 ),
            'defecto'   => ( !empty($this->params['defecto']) && $this->params['defecto'] == 1 ? 1 : 0 ),
            'factura'   => ( !empty($this->params['factura']) && $this->params['factura'] == 1 ? 1 : 0 ),
            'moroso'    => ( !empty($this->params['moroso']) && $this->params['moroso'] == 1 ? 1 : 0 ),
            'suspendido'=> ( !empty($this->params['suspendido']) && $this->params['suspendido'] == 1 ? 1 : 0 ),
            'sin_basico'=> ( !empty($this->params['sin_basico']) && $this->params['sin_basico'] == 1 ? 1 : 0 )
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Actualiza un estado de grupo existente
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function actualizar_estado_grupo(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/configuraciones/estados-grupos/actualizar-estado-grupo';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_estado_update';
        $this->verificado = [
            $this->sp => [
                'id_estado' => request('id_estado'),
                'n_estado' => request('n_estado'),
            ]
        ];
        if(empty(request('id_estado')) || empty(request('n_estado'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'id_estado' => request('id_estado'),
            'n_estado'  => request('n_estado'),
            'activo'    => !empty(request('activo')) && request('activo') == 1 ? 1 : 0,
            'baja'      => !empty(request('baja')) && request('baja') == 1 ? 1 : 0,
            'consume'   => !empty(request('consume')) && request('consume') == 1 ? 1 : 0,
            'defecto'   => !empty(request('defecto')) && request('defecto') == 1 ? 1 : 0,
            'factura'   => !empty(request('factura')) && request('factura') == 1 ? 1 : 0,
            'moroso'    => !empty(request('moroso')) && request('moroso') == 1 ? 1 : 0,
            'suspendido'=> !empty(request('suspendido')) && request('suspendido') == 1 ? 1 : 0,
            'sin_basico'=> !empty(request('sin_basico')) && request('sin_basico') == 1 ? 1 : 0
        ];
        $this->params_sp = [
            'id_estado' => $this->params['id_estado'],
            'n_estado'  => $this->params['n_estado'],
            'activo'    => ( !empty($this->params['activo']) && $this->params['activo'] == 1 ? 1 : 0 ),
            'baja'      => ( !empty($this->params['baja']) && $this->params['baja'] == 1 ? 1 : 0 ),
            'consume'   => ( !empty($this->params['consume']) && $this->params['consume'] == 1 ? 1 : 0 ),
            'defecto'   => ( !empty($this->params['defecto']) && $this->params['defecto'] == 1 ? 1 : 0 ),
            'factura'   => ( !empty($this->params['factura']) && $this->params['factura'] == 1 ? 1 : 0 ),
            'moroso'    => ( !empty($this->params['moroso']) && $this->params['moroso'] == 1 ? 1 : 0 ),
            'suspendido'=> ( !empty($this->params['suspendido']) && $this->params['suspendido'] == 1 ? 1 : 0 ),
            'sin_basico'=> ( !empty($this->params['sin_basico']) && $this->params['sin_basico'] == 1 ? 1 : 0 )
        ];
        return $this->ejecutar_sp_simple();
    }
}