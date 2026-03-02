<?php

namespace App\Http\Controllers\Internos\Afiliaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;

class PersonaContactoController extends ConexionSpController
{
    /**
     * lista los contactos de una persona
     */
    public function listar_contactos(Request $request)
    {
        $this->url = 'int/afiliaciones/persona/listar-contactos';
        $this->controlador = 'PersonaContactoController.php';
        $this->funcion = 'listar_contactos';
        $this->permiso_requerido = 'gestionar afiliados';
        $this->user_id = $request->user()->id;
        $this->db = 'afiliacion';
        $this->metodo_http = 'get';
        $this->params = [
            'tipo' => request('tipo'), 
            'id_persona' => request('id_persona') !== NULL ? request('id_persona') : NULL,
            'id_empresa' => request('id_empresa') !== NULL ? request('id_empresa') : NULL
        ];
        if($this->params['tipo'] == 'persona' || $this->params['tipo'] == ''){
            $this->params_sp = [
                'id_persona' => $this->params['id_persona'], 
                'historico' => 1
            ];
            $this->sp = 'sp_persona_contacto_Select';
        }
        if($this->params['tipo'] == 'empresa'){
            $this->params_sp = [
                'id_empresa' => $this->params['id_empresa'], 
                'historico' => 1
            ];
            $this->sp = 'sp_sucursal_contacto_Select';
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agregar un nuevo contacto a la persona
     * Interno
     */
    public function agregar_contacto(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';
        $this->url = 'int/afiliaciones/persona/agregar-contacto';
        $this->permiso_requerido = 'gestionar afiliados';
        $this->db = 'afiliacion';
        $this->sp = 'sp_alta_contacto';
        $this->param_id_usuario = 'p_id_usuario';
        $this->params = [
            'id_persona' => request('id_persona') !== NULL ? request('id_persona') : NULL,
            'id_sucursal' => request('id_sucursal') !== NULL ? request('id_sucursal') : NULL,
            'id_tipo_contacto' => request('id_tipo_contacto'),
            'n_contacto' => request('n_contacto')
        ];
        $this->params_sp = [
            'p_id_persona' 	        =>	$this->params['id_persona'],
            'p_id_sucursal'         =>  ( empty($this->params['id_sucursal']) ) ? NULL : $this->params['id_sucursal'],
            'p_tipo_objeto'         =>  ( empty($this->params['id_sucursal']) ) ? 'P'  : 'S',
            'p_id_tipo_contacto'    =>	$this->params['id_tipo_contacto'],
            'p_n_contacto'          =>	$this->params['n_contacto'],
            'p_fecha' 		        => 	Carbon::now()->format('Ymd H:i:s'),
        ];
        return $this->ejecutar_sp_simple();
    }
}
