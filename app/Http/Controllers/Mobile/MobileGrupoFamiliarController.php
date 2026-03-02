<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Storage;
use Carbon\Carbon;

class MobileGrupoFamiliarController extends ConexionSpController {

    /**
     * Retorna un listado con el grupo familiar de un afiliado
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function buscar_grupo_familiar(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = '/mobile/afiliaciones/grupo-familiar/buscar-grupo-familiar';
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

}
