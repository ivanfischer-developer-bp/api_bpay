<?php

namespace App\Http\Controllers\Externos;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;


class ExternalListadosController extends ConexionSpController
{
    /**
     * Consulta un loistado de tipos de documentos.
     * Endpoint Externo
     * sp_tipo_doc_Select
     * Consume: Silvio Quadri
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response $response
     */
    public function consultar_tipos_documentos(Request $request)
    {
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_external_logged_user($user);

            $this->params = [];
            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $error = 'sin especificar';

            if($user->hasPermissionTo('consultar listados')){
                // ver ejemplo de uso en AAEjemploUsoController
                // ejecuta el sp
                try {
                    $response = $this->ejecutar_sp_directo('validacion_externa', 'sp_tipo_doc_Select', null);
                    Log::channel('external')->info('sp_tipo_doc_Select consultado por '.$request->user()->name);
                    // prepara la respuesta
                    // si hubo errores al ejecutar el sp
                    if(is_array($response) && array_key_exists('error', $response)){
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $error = $response['error']; 
                    }else if($count = sizeof($response) == 0){
                        $status = 'empty';
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                        $count = sizeof($response);
                        $data = $response;
                        $error = 'No se produjeron errores';
                    }else{
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = sizeof($response);
                        $data = $response;
                        $error = 'No se produjeron errores';
                    }
                } catch (\Throwable $th) {
                    $status = 'fail';
                    $message = 'Se produjo el siguiente error al realizar la petición.';
                    $count = 0;
                    $data = null;
                    $error = 'Line: '.$th->getLine().' Code: '.$th->getCode().' - '.$th->getMessage();
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para LISTAR USUARIOS';
                $count  = 0;
                $data = null;
                $error = null;
            }
            // retorna el response
            return response()->json([
                'status' => $status,
                'count' => $count,
                'error' => $error,
                'message' => $message,
                'line' => null,
                'code' => null,
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
