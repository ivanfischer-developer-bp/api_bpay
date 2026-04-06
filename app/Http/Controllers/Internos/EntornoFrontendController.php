<?php

namespace App\Http\Controllers\Internos;

use Illuminate\Http\Request;
use Illuminate\Http\Response;


use App\Http\Controllers\ConexionSpController;
use App\Models\EntornoFrontend;

use App\Models\User;

class EntornoFrontendController extends ConexionSpController
{

    public function cargar_entorno(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/configuraciones/entorno-frontend/cargar-entorno',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'responses' => [],
        ];
        $status = 'fail'; // 'ok', 'fail', 'empty', unauthorized', 'warning'  
        $message = '';
        $count = 0;
        $code = 0;
        $data = null;
        $errors = [];
        $params = [];
    
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

        $data = EntornoFrontend::where('entorno', config('site.entorno_frontend'))->first();
       

        if($data){
            $status = 'ok';
            $message = 'Entorno cargado correctamente.';
            $count = 1;
            $code = 1;
        }else{
            $status = 'empty';
            $code = -1;
            $message = 'No se encontró el entorno.';
            $errors = ['No se encontró el entorno.'];
        }

        return response()->json([
            'status' => $status,
            'count' => $count,
            'errors' => $errors,
            'message' => $message,
            'line' => null,
            'code' => $code,
            'data' => $data,
            'params' => $params,
            'extras' => $extras,
            'logged_user' => $logged_user,
        ]); 
    }
}