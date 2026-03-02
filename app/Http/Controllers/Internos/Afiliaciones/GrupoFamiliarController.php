<?php

namespace App\Http\Controllers\Internos\Afiliaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
// use Illuminate\Support\Facades\File as FacadeFile;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Storage;
use Carbon\Carbon;

class GrupoFamiliarController extends ConexionSpController
{
    /**
     * Retorna un listado con el grupo familiar de un afiliado
     */
    public function buscar_grupo_familiar(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = '/int/afiliaciones/grupo-familiar/buscar-grupo-familiar';
        $this->permiso_requerido = 'buscar afiliado';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_grupo_afiliado_Select';
        $this->params = [
            'id_grupo' => request('id_grupo')
        ];
        $this->params_sp = [
            'p_id_grupo' => $this->params['id_grupo']
        ];
        if(request('activos') != null){
            $this->params['activos'] = request('activos');
            $this->params_sp['p_activos'] = request('activos');
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Cambiar a un afiliado beneficiario de grupo
     */
    public function cambiar_grupo_familiar(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = '/int/afiliaciones/grupo-familiar/cambiar-grupo-familiar';
        $this->permiso_requerido = 'gestionar afiliados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_grupo_beneficiario_Update';
        $this->params = [
            'id_persona' => request('id_persona'),
            'id_grupo_nuevo' => request('id_grupo_nuevo'),
            'id_parentesco_nuevo' => request('id_parentesco_nuevo')
        ];
        $this->params_sp = [
            'p_id_persona' => $this->params['id_persona'],
            'p_id_grupo_nuevo' => $this->params['id_grupo_nuevo'],
            'p_id_parentesco_nuevo' => $this->params['id_parentesco_nuevo']
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega un afiliado existente como titular
     */
    public function nuevo_titular(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = '/int/afiliaciones/grupo-familiar/nuevo-titular';
        $this->permiso_requerido = 'gestionar afiliados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_genera_grupo';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario
        $this->params = [
            'id_persona' => request('id_persona'),
            'n_afiliado' => request('n_afiliado'),
            'n_afiliado_nuevo' => request('n_afiliado_nuevo'),
            'id_plan' => request('id_plan'),
            'id_convenio' => request('id_convenio'),
            'fecha' => request('fecha'),
            'id_promotor' => request('id_promotor')
        ];
        $this->params_sp = [
            'id_persona' => intval($this->params['id_persona']),
            'n_afiliado' => $this->params['n_afiliado'],
            'n_afiliado_nuevo' => $this->params['n_afiliado_nuevo'],
            'id_plan' => intval($this->params['id_plan']),
            'id_convenio' => intval($this->params['id_convenio']),
            'fecha' => $this->params['fecha'],
            'id_promotor' => intval($this->params['id_promotor'])
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Dar de baja un grupo familiar cambiando su estado
     */
    public function baja_grupo_familiar(Request $request){
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/afiliaciones/afiliado/baja-grupo-familiar';
        $this->permiso_requerido = 'gestionar afiliados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_grupo_estado_Update';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'p_id_usuario'; // nombre del parámetro id_usuario, id_n_usuario, p_id_usuario
        $this->params = [
            'id_estado' => request('id_estado'),
            'id_grupo' => request('id_grupo'),
            'id_tipo_baja' => request('id_tipo_baja'),
            'observaciones' => request('observaciones')
        ];
        $this->params_sp = [
            'p_id_estado' => $this->params['id_estado'],
            'p_id_grupo' => $this->params['id_grupo'],
            'p_id_tipo_baja' => $this->params['id_tipo_baja'],
            'p_observaciones' => $this->params['observaciones'],
            'p_fecha' => Carbon::now()->format('Ymd H:i:s')
        ];
        return $this->ejecutar_sp_simple();
    }
}