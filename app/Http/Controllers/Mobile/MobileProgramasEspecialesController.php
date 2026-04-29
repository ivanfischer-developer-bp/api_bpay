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

    /**
     * Descarga un formulario de programa especial para su llenado
     */
    public function descargar_formulario_programa_especial(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'mobile/programas-especiales/descargar-formulario-programa-especial',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $id_usuario_sqlserver = $logged_user['id_usuario_sqlserver'];
        
        // Validar que venga el nombre del archivo
        $request->validate([
            'archivo' => 'required|string',
        ]);

        $archivo = $request->input('archivo');
        $ruta = storage_path('app/public/reportes/programas_especiales/' . $archivo);

        if (!file_exists($ruta)) {
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => ['Archivo no encontrado'],
                'message' => null,
                'line' => null,
                'code' => -1,
                'data' => [
                    'archivo' => $archivo,
                    'ruta' => $ruta,
                ],
                'params' => [
                    'archivo' => $archivo,
                ],
                'extras' => $extras,
                'logged_user' => $logged_user,
            ], 404);
        }

        return response()->download($ruta);
    }

}