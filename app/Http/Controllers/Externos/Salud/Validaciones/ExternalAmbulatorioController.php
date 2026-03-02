<?php

namespace App\Http\Controllers\Externos\Salud\Validaciones;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

class ExternalAmbulatorioController extends ConexionSpController
{
    /**
     * Crea una nueva validación en ambulatorio.
     * Endpoint externo
     * ws_ambulatorio
     * Consume: Silvio Quadri
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
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
            $seguir = true;
            // return $request->user()->es_prestador();
            if($user->hasPermissionTo('crear validacion ambulatorio')){
                $this->params = [
                    'p_tipo_doc' => request('p_tipo_doc'), // varchar(10)
                    'p_nro_doc' => request('p_nro_doc'), // varchar(20)
                    'p_fecha_realizacion' => request('p_fecha_realizacion'), // datetime
                    'p_diagnostico' => request('p_diagnostico'), // varchar(40)=null
                    'p_json_prestaciones' => json_encode(request('p_json_prestaciones')), // nvarchar(max) json
                    'p_id_usuario' => $request->user()->id, // int=1
                    'p_autoriza_en_guardia' => request('p_autoriza_en_guardia'), // tinyint=0
                    'p_observaciones' => request('p_observaciones'), // varchar(1000)
                    'p_consulta_cobertura' => 0, // cuando es 0 ejecuta la emision de la validación ambulatoria, si es 1 solo consulta cobertura
                    'n_token' => request('n_token'),
                    'p_n_afiliado' => request('n_afiliado')
                ];
                // si es prestador sacamos el parámetro id_prestador del usuario
                if($request->user()->es_prestador()){
                    $this->params['p_prestador'] = $request->user()->id_prestador; // int
                }
                // si es administrador debe proporcionar el parámetrop_prestador
                if($user->hasRole('administrador') || $user->hasRole('super administrador')){
                    $this->params['p_prestador'] = request('p_prestador'); // int
                    if($this->params['p_prestador'] == ''){
                        $status = 'fail';
                        $message = 'Falta de parámetros en la consulta';
                        $count = 0;
                        $data = null;
                        $error = 'Como es usuario '.$user->roles[0]->name.' el parámetro p_prestador es requerido.';
                        $seguir = false;
                    }else{
                        $seguir = true;
                    }
                }
                // si es gateway debe proporcionar el parámetrop_prestador
                if($user->hasRole('gateway')){
                    $this->params['p_prestador'] = request('p_prestador'); // int
                    if($this->params['p_prestador'] == ''){
                        $status = 'fail';
                        $message = 'Falta de parámetros en la consulta';
                        $count = 0;
                        $data = null;
                        $error = 'Como es usuario '.$user->roles[0]->name.' el parámetro p_prestador es requerido.';
                        $seguir = false;
                    }else{
                        $seguir = true;
                    }
                }
                // si no se produjeron errores por falta de parámetro ejecuta el sp
                if($seguir){
                    // ejecuta el sp
                    // $response['error'] = 'desconectado temporalmente';
                    $response = $this->ejecutar_sp_directo('validacion_externa', 'ws_ambulatorio', null);
                    Log::channel('external')->info('ws_ambulatorio consultado por '.$request->user()->name.' (id '.$request->user()->id.') con prestador '.$this->params['p_prestador']);
                    // prepara la respuesta
                    // si hubo errores al ejecutar el sp
                    if(array_key_exists('error', $response)){
                        // return 'Se produjo un error: '.$response['error'];
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición ';
                        $count = 0;
                        $data = null;
                        $error = $response['error']; 
                    }else{
                        // sino hubo errores al ajecutar sp y devuelve una autorización
                        if(array_key_exists('autorizacion', $response[0])){
                            // si la operación fue exitosa el estado es diferente de -1
                            if($response[0]->estado != -1){
                                // retorna estado, sucursal(siempre es 994) y autorizacion
                                $status = 'ok';
                                $message = 'Transacción realizada con éxito';
                                $count = sizeof($response);
                                $data = $response;
                                $error = 'No se produjeron errores';
                            }else{
                                // si el estado es -1 hubo error en la transacción
                                $status = 'fail';
                                $message = 'Error en la transacción';
                                $count = 0;
                                $data = $response;
                                $error = 'Error de transacción, no se realizó la operación';
                            }
                        }else{
                            // si no devuelve una autorización
                            $status = 'fail';
                            $message = 'No se realizó correctamente la transacción';
                            $count = 0;
                            $data = $response;
                            $error = 'Errores en la conexión con la base de datos.'; 
                        }
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol ' . strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CREAR VALIDACION AMBULATORIO';
                $count  = 0;
                $data = null;
                $error = null;
            }
            // retorna la respuesta
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
     * Crea una nueva validación en ambulatorio.
     * Endpoint externo
     * ws_ambulatorio_faba
     * Consume: Silvio Quadri
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function ambulatorio_faba(Request $request)
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
            $seguir = true;
            // return $request->user()->es_prestador();
            if($user->hasPermissionTo('crear validacion ambulatorio')){
                $this->params = [
                    'p_tipo_doc' => request('p_tipo_doc'), // varchar(10)
                    'p_nro_doc' => request('p_nro_doc'), // varchar(20)
                    'p_fecha_realizacion' => request('p_fecha_realizacion'), // datetime
                    'p_diagnostico' => request('p_diagnostico'), // varchar(40)=null
                    'p_json_prestaciones' => json_encode(request('p_json_prestaciones')), // nvarchar(max) json
                    'p_id_usuario' => $request->user()->id, // int=1
                    'p_autoriza_en_guardia' => request('p_autoriza_en_guardia'), // tinyint=0
                    'p_observaciones' => request('p_observaciones'), // varchar(1000)
                    'p_consulta_cobertura' => 0, // cuando es 0 ejecuta la emision de la validación ambulatoria, si es 1 solo consulta cobertura
                    'n_token' => request('n_token'),
                    'p_n_afiliado' => request('n_afiliado')
                ];
                // si es prestador sacamos el parámetro id_prestador del usuario
                if($request->user()->es_prestador()){
                    $this->params['p_prestador'] = $request->user()->id_prestador; // int
                }
                // si es administrador debe proporcionar el parámetrop_prestador
                if($user->hasRole('administrador') || $user->hasRole('super administrador')){
                    $this->params['p_prestador'] = request('p_prestador'); // int
                    if($this->params['p_prestador'] == ''){
                        $status = 'fail';
                        $message = 'Falta de parámetros en la consulta';
                        $count = 0;
                        $data = null;
                        $error = 'Como es usuario '.$user->roles[0]->name.' el parámetro p_prestador es requerido.';
                        $seguir = false;
                    }else{
                        $seguir = true;
                    }
                }
                // si es gateway debe proporcionar el parámetrop_prestador
                if($user->hasRole('gateway')){
                    $this->params['p_prestador'] = request('p_prestador'); // int
                    if($this->params['p_prestador'] == ''){
                        $status = 'fail';
                        $message = 'Falta de parámetros en la consulta';
                        $count = 0;
                        $data = null;
                        $error = 'Como es usuario '.$user->roles[0]->name.' el parámetro p_prestador es requerido.';
                        $seguir = false;
                    }else{
                        $seguir = true;
                    }
                }
                // si no se produjeron errores por falta de parámetro ejecuta el sp
                if($seguir){
                    // ejecuta el sp
                    // $response['error'] = 'desconectado temporalmente';
                    $response = $this->ejecutar_sp_directo('validacion_externa', 'ws_ambulatorio_faba', null);
                    Log::channel('external')->info('ws_ambulatorio_faba consultado por '.$request->user()->name.' (id '.$request->user()->id.') con prestador '.$this->params['p_prestador']);
                    // prepara la respuesta
                    // si hubo errores al ejecutar el sp
                    if(array_key_exists('error', $response)){
                        // return 'Se produjo un error: '.$response['error'];
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición ';
                        $count = 0;
                        $data = null;
                        $error = $response['error']; 
                    }else{
                        // sino hubo errores al ajecutar sp y devuelve una autorización
                        if(array_key_exists('autorizacion', $response[0])){
                            // si la operación fue exitosa el estado es diferente de -1
                            if($response[0]->estado != -1){
                                // retorna estado, sucursal(siempre es 994) y autorizacion
                                $status = 'ok';
                                $message = 'Transacción realizada con éxito';
                                $count = sizeof($response);
                                $data = $response;
                                $error = 'No se produjeron errores';
                            }else{
                                // si el estado es -1 hubo error en la transacción
                                $status = 'fail';
                                $message = 'Error en la transacción';
                                $count = 0;
                                $data = $response;
                                $error = 'Error de transacción, no se realizó la operación';
                            }
                        }else{
                            // si no devuelve una autorización
                            $status = 'fail';
                            $message = 'No se realizó correctamente la transacción';
                            $count = 0;
                            $data = $response;
                            $error = 'Errores en la conexión con la base de datos.'; 
                        }
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol ' . strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CREAR VALIDACION AMBULATORIO';
                $count  = 0;
                $data = null;
                $error = null;
            }
            // retorna la respuesta
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
