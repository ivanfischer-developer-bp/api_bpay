<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;

class MobileProgramasEspecialesController extends ConexionSpController
{
    /**
     * Lista los programas especiales para su configuración
     */
    public function listar_programas_especiales(Request $request)
    {
    
        date_default_timezone_set('America/Argentina/Cordoba');
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'mobile/programas-especiales/listar-programas-especiales';
        $this->permiso_requerido = '';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_programa_especial_select';
        return $this->ejecutar_sp_simple();
    }

}