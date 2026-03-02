<?php

namespace App\Http\Controllers\Internos;

use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;

use App\Http\Controllers\ConexionSpController;

class UsuariosSqlserverController extends ConexionSpController
{
    /**
     * Busca un usuario auditor por su nombre
     */
    public function buscar_auditor(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/usuarios-sqlserver/buscar-auditor';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'admin'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_usuario_auditor_select';
        if(request('id_contrato') != null){
            $this->params['id_contrato'] = request('id_contrato');
            $this->params_sp['p_id_contrato'] = request('id_contrato');
        }else{
            $this->params_sp['p_id_contrato'] = 3;
        }
        if(request('nombre') != null){
            $this->params['nombre'] = request('nombre');
            $this->params_sp['p_apynom'] = request('nombre');
        }
        return $this->ejecutar_sp_simple();
    }
}