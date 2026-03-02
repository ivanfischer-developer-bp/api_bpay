<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;


class ABMTipoBajaController extends ConexionSpController
{

    /**
     * Retorna un listado de los tipos de baja
     */
    public function buscar_tipos_baja(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/tipos-baja/buscar-tipos-baja';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_tipo_baja_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega un nuevo tipo de baja
     */
    public function agregar_tipo_baja(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post'; 
        $this->url = '/int/listados/tipos-baja/agregar-tipo-baja';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_tipo_baja_Insert';
        $this->verificado = [
            $this->sp => [
                'n_tipo_baja' => request('n_tipo_baja')
            ]
        ];
        if(empty(request('n_tipo_baja'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'n_tipo_baja' => request('n_tipo_baja'),
            'defecto' => request('defecto'),
            'baja_persona' => request('baja_persona')
        ];
        $this->params_sp = [
            'p_n_tipo_baja' => $this->params['n_tipo_baja'],
            'p_defecto' => ( !empty($this->params['defecto']) && $this->params['defecto'] == 1 ? 1 : 0 ),
            'p_baja_persona' => ( !empty($this->params['baja_persona']) && $this->params['baja_persona'] == 1 ? 1 : 0 )
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Actualiza un tipo de baja
     */
    public function actualizar_tipo_baja(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post'; 
        $this->url = '/int/listados/tipos-baja/actualizar-tipo-baja';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_tipo_baja_Update';
        $this->verificado = [
            $this->sp => [
                'id_tipo_baja' => request('id_tipo_baja'),
                'n_tipo_baja' => request('n_tipo_baja')
            ]
        ];
        
        if(empty(request('n_tipo_baja')) || empty(request('id_tipo_baja'))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incompletos o incorrectos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'id_tipo_baja' => request('id_tipo_baja'),
            'n_tipo_baja' => request('n_tipo_baja'),
            'defecto' => request('defecto'),
            'baja_persona' => request('baja_persona')
        ];
        $this->params_sp = [
            'p_id_tipo_baja' => $this->params['id_tipo_baja'],
            'p_n_tipo_baja' => $this->params['n_tipo_baja'],
            'p_defecto' => ( !empty($this->params['defecto']) && $this->params['defecto'] == 1 ? 1 : 0 ),
            'p_baja_persona' => ( !empty($this->params['baja_persona']) && $this->params['baja_persona'] == 1 ? 1 : 0 )
        ];
        return $this->ejecutar_sp_simple();
    }
}