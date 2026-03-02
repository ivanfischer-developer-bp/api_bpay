<?php

namespace App\Http\Controllers\Externos\Salud\Coberturas;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use File;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;

class ExternalCoberturaEspecialController extends ConexionSpController
{
    /**
     * Obtiene los datos de las coberturas especiales de un afiliado mediante el ws_coberturaEspecial
     * Endpoint externo 
     * ws_coberturaEspecial
     * Consume: Silvio Quadri
     * @param p_tipo_doc en el request, string tipo de documento
     * @param nro_doc en el request, number número de documento
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

            if($user->hasPermissionTo('consultar coberturas especiales')){
                $tipo_doc = request('p_tipo_doc');
                $nro_doc = request('p_nro_doc');
                
                $this->params = [
                    'p_tipo_doc' => $tipo_doc,
                    'p_nro_doc' => $nro_doc
                ];

                if($tipo_doc == null && $nro_doc  == null){
                    $this->params['p_vigentes'] = 1;
                }
                // ejecuta el sp
                try {
                    $response = $this->ejecutar_sp_directo('validacion_externa', 'ws_coberturaEspecial', null);
                    Log::channel('external')->info('ws_coberturaEspecial consultado por '.$request->user()->name.' (id '.$request->user()->id.') con parametros: '.json_encode($this->params));
                    // return $response;
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
                    $message = 'Se produjo un error al realizar la petición.';
                    $count = 0;
                    $data = null;
                    $error = 'Line: '.$th->getLine().' Code: '.$th->getCode().' - '.$th->getMessage();
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CONSULTAR COBERTURAS ESPECIALES';
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

    /**
     * Consulta medicamentos
     * Endpoit externo 
     * ws_coberturaEspecialMedicamentos
     * Consume: Silvio quadri
     * @param p_tipo_doc en el request, tipo de documento del afiliado, ej:'DNI'
     * @param p_nro_doc en el request, número de documento del afiliado
     * @param p_vigentes en el request, 1 o 0, determina si se buscará solo entre los afiliados vigentes o en todos
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response $response
     */
    public function consultar_medicamentos(Request $request)
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

            if($user->hasPermissionTo('consultar coberturas especiales')){
                $tipo_doc = request('p_tipo_doc');
                $nro_doc = request('p_nro_doc');
                $vigentes = request('p_vigentes');
                
                $this->params = [
                    'p_tipo_doc' => $tipo_doc,
                    'p_nro_doc' => $nro_doc,
                    'p_vigentes' => $vigentes
                ];

                if($tipo_doc == null && $nro_doc  == null){
                    $this->params['p_vigentes'] = 1;
                }
                // ejecuta el sp
                try {
                    $response = $this->ejecutar_sp_directo('validacion_externa', 'ws_coberturaEspecialMedicamentos', null);
                    Log::channel('external')->info('ws_coberturaEspecialMedicamentos consultado por '.$request->user()->name.' (id '.$request->user()->id.')');
                    // return $response;
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
                    $message = 'Se produjo un error al realizar la petición.';
                    $count = 0;
                    $data = null;
                    $error = 'Line: '.$th->getLine().' Code: '.$th->getCode().' - '.$th->getMessage();
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CONSULTAR COBERTURAS ESPECIALES';
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

