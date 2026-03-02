<?php

namespace App\Http\Controllers\Internos\Afiliaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;


use Carbon\Carbon;

class CredencialController extends ConexionSpController
{
    /**
     * Busca credenciales
     */
    public function buscar_credenciales(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/afiliaciones/afiliacion/credenciales/buscar-credenciales';
        $this->permiso_requerido = 'gestionar afiliaciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_credencial_Select';
        $this->params = [
            'pendiente' => request('pendiente') !== NULL ? request('pendiente') : NULL,
        ];
        $this->params_sp = [
            'pendiente' => $this->params['pendiente'] !== NULL && $this->params['pendiente'] !== '' ? $this->params['pendiente'] : 0
        ];
        if(request('id_grupo') != null){
            $this->params['id_grupo'] = request('id_grupo');
            $this->params_sp['id_grupo'] = request('id_grupo');
        }
        if ( ( $request->input('filtrar_por_fechas') == 1 
                || $request->input('filtrar_por_fechas') == true ) 
                && !empty($request->input('fecha_desde')) 
                && !empty($request->input('fecha_hasta')) 
            ){
            $this->params['filtrar_por_fechas'] = request('filtrar_por_fechas');
            $this->params['fecha_desde'] = $request->input('fecha_desde');
            $this->params['fecha_hasta'] = $request->input('fecha_hasta');

            $this->params_sp['fdde'] = request('fecha_desde');
            $this->params_sp['fhta'] = request('fecha_hasta');
        }
        if(request('id_afiliado') != null){
            $this->params['id_afiliado'] = request('id_afiliado');
            $this->params_sp['id_afiliado'] = request('id_afiliado');
        }
        if(request('nro_afiliado') != null){
            $this->params['nro_afiliado'] = request('nro_afiliado');
            $this->params_sp['nro_afiliado'] = request('nro_afiliado');
        }
        if(request('id_tipo_doc') != null){
            $this->params['id_tipo_doc'] = request('id_tipo_doc');
            $this->params_sp['id_tipo_doc'] = request('id_tipo_doc');
        }
        if(request('nro_doc') != null){
            $this->params['nro_doc'] = request('nro_doc');
            $this->params_sp['nro_doc'] = request('nro_doc');
        }
        if(request('nombre') != null){
            $this->params['nombre'] = request('nombre');
            $this->params_sp['nombre'] = request('nombre');
        }
        return $this->ejecutar_sp_simple();
    }
}