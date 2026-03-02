<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;


class ABMRangoEdadController extends ConexionSpController
{
    /**
     * Retorna un listado de los rangos de edad
     */
    public function listar_rangos_edad(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = 'int/listados/rangos-edad/listar-rangos-edad';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_rango_edad_select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega un nuevo rango de edad
     */
    public function agregar_rango_edad(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post'; 
        $this->url = 'int/listados/rangos-edad/agregar-rango-edad';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_rango_edad_insert';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'p_id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario
        $this->verificado = [
            $this->sp => [
                'n_rango' => request('n_rango')
            ]
        ];
        if(empty(request('n_rango'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'n_rango' => request('n_rango'),
            'edad_hasta' => request('edad_hasta'),
            'edad_desde' => request('edad_desde'),
        ];
        $this->params_sp = [
            'p_n_rango' => $this->params['n_rango'],
            'p_edad_dde' => $this->params['edad_desde'],
            'p_edad_hta' => $this->params['edad_hasta'],
            'p_fecha' => Carbon::now()->format('Ymd H:i:s')
        ];
        return $this->ejecutar_sp_simple();
    }
}