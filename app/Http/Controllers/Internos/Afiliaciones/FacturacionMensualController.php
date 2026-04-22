<?php

namespace App\Http\Controllers\Internos\Afiliaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;

class FacturacionMensualController extends ConexionSpController
{
    /**
     * Retorna un listado de los períodos pendientes de facturación
     */
    public function listar_periodos_pendientes(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/afiliaciones/facturacion-mensual/listar-periodos-pendientes';
        $this->permiso_requerido = 'ver facturacion';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_periodo_pendiente_select';
        return $this->ejecutar_sp_simple();
    }
}