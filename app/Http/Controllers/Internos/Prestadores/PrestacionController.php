<?php

namespace App\Http\Controllers\Internos\Prestadores;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use setasign\Fpdi\Fpdi;
use Carbon\Carbon;
use Storage;


class PrestacionController extends ConexionSpController
{

    /**
     * Busca prestaciones
     */
    public function buscar_prestaciones(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';
        $this->url = '/int/prestacion/buscar-prestaciones';
        $this->permiso_requerido = 'buscar prestaciones';
        $this->db = 'validacion'; 
        $this->sp = 'AWEB_TraerPrestadoresPractica';
        $this->params = [
            'practica' => request('practica'),
            'tipo_busqueda' => request('tipo_busqueda'),
            'id_plan_prestacional' => request('id_plan_prestacional'),
            'localidad' => request('localidad'),
            'razon_social' => request('razon_social')
        ];
        $this->params_sp = [
            'p_practica' => request('practica'),
            'tipo_busqueda' => request('tipo_busqueda'),
            'id_plan_prestacional' => request('id_plan_prestacional'),
            'p_localidad' => null !== request('localidad') ? request('localidad') : '',
            'p_razon_social' => null !== request('razon_social') ? request('razon_social') : ''
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Busca prestaciones para una validacion
     */
    public function buscar_practicas_prestadores(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  
        $this->url = '/int/prestacion/buscar-practicas-prestadores';
        $this->permiso_requerido = 'buscar prestaciones';
        $this->db = 'validacion'; 
        $verificar_consulta = null !== request('verificar_consulta') ? request('verificar_consulta') : 0;
        $this->params = [
            'id_convenio' => request('id_convenio'),
            'sexo' => request('sexo'),
            'valor_buscado' => request('valor_buscado'),
            'id_tipo_internacion' => request('id_tipo_internacion'),
            'verificar_consulta' => request('verificar_consulta'),
            'tipo_busqueda' => request('tipo_busqueda'),
            'id_plan_prestacional' => request('id_plan_prestacional'),
            'id_persona' => request('id_persona') != null ? intval(request('id_persona')) : null,
            'medicamento' => request('medicamento') !== null ? intval(request('medicamento')) : 0
        ];
        $this->params_sp = [
            'id_convenio' => $this->params['id_convenio'], 
            'tipo_busqueda' => $this->params['tipo_busqueda']
        ];
        if($verificar_consulta == 1){
            $this->sp = 'AWEB_VerificarCargaConsulta';
            return $this->ejecutar_sp_simple();
        }else{
            $this->params_sp['sexo' ] = $this->params['sexo'];
            $this->params_sp['plan_prest'] = $this->params['id_plan_prestacional'];
            $this->params_sp['id_persona'] = $this->params['id_persona'];
            if ($this->params['valor_buscado'] != ''){
                $this->params_sp['codigo_nomenclador'] = $this->params['valor_buscado'];
            }
            if ( $this->params['id_plan_prestacional'] != '' ){
                $this->params_sp['id_tipo_internacion'] = 0;
            }
            $this->sp = 'AWEB_TraerPracticas';
            return $this->ejecutar_sp_simple();
        }
    }

    public function verificar_carga_consulta(Request $request)
    {

        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  
        $this->url = 'int/prestacion/verificar-carga-consulta';
        $this->permiso_requerido = 'ver prestaciones';
        $this->db = 'validacion'; 
        $this->sp = 'AWEB_VerificarCargaConsulta';
        if(request('id_convenio') != null){
            $this->params['id_convenio'] = request('id_convenio');
            $this->params_sp['id_convenio'] = request('id_convenio');
        }
        if(request('tipo_busqueda') != null){
            $this->params['tipo_busqueda'] = request('tipo_busqueda');
            $this->params_sp['tipo_busqueda'] = request('tipo_busqueda');
        }
        return $this->ejecutar_sp_simple();
    }
}