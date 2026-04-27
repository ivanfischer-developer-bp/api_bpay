<?php

namespace App\Http\Controllers\Internos\Afiliaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;

class PeriodoController extends ConexionSpController
{
    /**
     * Lista los perdíodos vigentes
     */
    public function listar_periodos(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/afiliaciones/periodos/listar-periodos';
        $this->permiso_requerido = 'ver facturacion';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_periodo_select';
        if(request('id_periodo') != null){
            $this->params['id_periodo'] = request('id_periodo');
            $this->params_sp['id_periodo'] = request('id_periodo');
        }
        if(request('estado') != null){
            $this->params['estado'] = request('estado');
            $this->params_sp['estado'] = request('estado');
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Genera un informe del período
     */
    public function generar_informe_periodo(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/afiliaciones/periodos/generar-informe-periodo';
        $this->permiso_requerido = 'ver facturacion';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = '';
        $this->tipo_id_usuario = 'usuario'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro: id_usuario, id_n_usuario, p_id_usuario
        $this->params = [
            'id_periodo' => request('id_periodo'),
            'estado' => request('estado')
        ];
        $this->params_sp = [
            'id_periodo' => $this->params['id_periodo'],
        ];
        
        switch($this->params['estado']){
            case 'Pendiente':
                $this->sp = 'sp_informe_deuda_pendiente';
                break;
            case 'Confirmada':
                $this->sp = 'sp_informe_deuda_confirmada';
                break;
            case 'Facturada':
                $this->sp = 'sp_informe_deuda_facturada';
                break;
            case 'Finalizada':
                $this->sp = 'sp_informe_deuda_finalizada';
                break;
        }
        return $this->ejecutar_sp_simple();
    }
}