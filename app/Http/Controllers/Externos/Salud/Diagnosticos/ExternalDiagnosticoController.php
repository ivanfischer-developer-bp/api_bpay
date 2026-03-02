<?php

namespace App\Http\Controllers\Externos\Salud\Diagnosticos;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\ConexionSpController;
use Illuminate\Support\Facades\Log;

use App\Models\User;

class ExternalDiagnosticoController extends ConexionSpController
{
    /**
     * Obtiene los datos de un diagnóstico
     * Consume: Silvio Quadri
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response $response
     */
    public function consultar(Request $request)
    {
        // ver ejemplo de uso en AAEjemploUsoController
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_external_logged_user($user);
            
            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $error = 'sin especificar';
            $line = null;
            $code = null;

            if($user->hasPermissionTo('listar diagnosticos')){
                
                $this->params = [
                    'p_cod_cie_item' => request('p_cod_cie_item') 
                ];
                // ejecuta el sp
                try {
                    $response = $this->ejecutar_sp_directo('validacion_externa', 'sp_cie_v10_item_Select', null);
                    Log::channel('external')->info('sp_cie_v10_item_Select consultado por '.$request->user()->name.' (id '.$request->user()->id.')');
                    // return $response;
                    // prepara la respuesta
                    // si hubo errores al ejecutar el sp
                    if(is_array($response) && array_key_exists('error', $response)){
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $error = $response['error']; 
                        $code = -3;
                    }else if($count = sizeof($response) == 0){
                        $status = 'empty';
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                        $count = sizeof($response);
                        $data = $response;
                        $error = 'No se produjeron errores';
                        $code = -2;
                    }else{
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = sizeof($response);
                        $data = $response;
                        $error = 'No se produjeron errores';
                        $code = 1;
                    }
                } catch (\Throwable $th) {
                    $status = 'fail';
                    $message = $th->getMessage();
                    $count = 0;
                    $data = null;
                    $error = 'Se produjo un error al realizar la petición.';
                    $code = $th->getCode();
                    $line = $th->getLine();
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para LISTAR DIAGNOSTICOS';
                $count  = 0;
                $data = null;
                $error = null;
                $code = -1;
            }
            // retorna el response
            return response()->json([
                'status' => $status,
                'count' => $count,
                'error' => $error,
                'message' => $message,
                'line' => $line,
                'code' => $code,
                'data' => $data,
                'params' => $this->params,
                'logged_user' => $logged_user,
                'api_software_version' => config('site.external_software_version')
            ]); 
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'error' => 'Backend failed',
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'api_software_version' => config('site.external_software_version')
            ]);
        }
        

    }
}
