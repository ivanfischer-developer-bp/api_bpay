<?php

namespace App\Http\Controllers\Internos\Validaciones;

use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;

use App\Http\Controllers\ConexionSpController;

class CajaValidacionesController extends ConexionSpController
{
    /**
     * consultar los movimientos de caja
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function consultar_movimientos_caja(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/validaciones/caja/consultar-movimientos-caja';
        $this->permiso_requerido = 'consultar movimientos caja';
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'AWEB_TraerMovimientosCaja';
        $this->tipo_id_usuario = 'usuario'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario
        // $this->param_usuario = 'servega@gmail.com'; // el parámetro cuando no es el id o el nombre
        // return $this->logged_user;
        $this->params = [
            'id_sucursal' => $request->input('id_sucursal')
        ];
        $this->params_sp = [
            'id_sucursal' => $request->input('id_sucursal')
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Inserta un movimiento en caja
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function insertar_movimiento_caja(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/validaciones/caja/consultar-movimientos-caja';
        $this->permiso_requerido = 'agregar movimiento caja';
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'AWEB_InsertarMovimientoCaja';
        $this->tipo_id_usuario = 'usuario'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario
        $valor = floatval(request('valor'));
        $this->params = [
            'concepto' => request('descripcion'),
            'debe' => ( request('tipo') == 'ingreso' ) ? $valor : 0,
            'haber' => ( request('tipo') == 'egreso' ) ? $valor : 0,
            'id_sucursal' => $request->input('id_sucursal'),
        ];
        $this->params_sp = [
            'id_sucursal' => $this->params['id_sucursal'],
            'concepto' => $this->params['concepto'],
            'debe' => $this->params['debe'],
            'haber' => $this->params['haber']
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Relaciona una factura a un movimiento de caja
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function relacionar_factura_movimiento(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/validaciones/caja/ralacionar-factura-movimiento';
        $this->permiso_requerido = 'agregar movimiento caja';
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'AWEB_InsertarMovimientoCajaFactura';
        $this->params = [
            'id_movimiento_caja' => request('id_movimiento_caja'),
            'numero_factura' => $request->input('numero_factura'),
        ];
        $this->params_sp = [
            'id_movimiento_caja' => $this->params['id_movimiento_caja'],
            'numero_factura' => $this->params['numero_factura']
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * realizar cierre de caja
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function cerrar_caja(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/validaciones/caja/cerrar-caja';
        $this->permiso_requerido = 'realizar cierres caja';
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'AWEB_CerrarCaja';
        $this->tipo_id_usuario = 'usuario'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario
        $this->params = [
            'id_sucursal' => $request->input('id_sucursal')
        ];
        $this->params_sp = [
            'id_sucursal' => $request->input('id_sucursal')
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * consulta cierres de caja
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function consultar_cierres_caja(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/validaciones/caja/consultar-cierres-caja';
        $this->permiso_requerido = 'consultar movimientos caja';
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'AWEB_TraerCierresCajaHistorico';
        $fecha_desde = request('fecha_desde');
        $fecha_hasta = request('fecha_hasta');
        $id_sucursal = request('id_sucursal');
        $this->params = [
            'fecha_desde' => $fecha_desde,
            'fecha_hasta' => $fecha_hasta,
            'id_sucursal' => $id_sucursal
        ];
        $this->params_sp = [
            'fecha_desde' => $fecha_desde != '' ? $fecha_desde : NULL,
            'fecha_hasta' => $fecha_hasta != '' ? $fecha_hasta : NULL,
            'id_sucursal' => $id_sucursal != '' ? $id_sucursal : NULL
        ];
        return $this->ejecutar_sp_simple();
    }
    
    /**
     * busca el detalle del cierre de caja
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function buscar_detalle_cierre_caja(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/validaciones/caja/buscar-detalle-cierre-caja';
        $this->permiso_requerido = 'consultar movimientos caja';
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'AWEB_TraerMovimientosCaja';
        $id_movimiento_caja_historico = request('id_movimiento_caja_historico');
        $this->params = [
            'id_movimiento_caja_historico' => $id_movimiento_caja_historico,
        ];
        $this->params_sp = [
            'id_sucursal' => NULL,
            'id_movimiento_caja_historico' => $id_movimiento_caja_historico
        ];
        return $this->ejecutar_sp_simple();
    }
}