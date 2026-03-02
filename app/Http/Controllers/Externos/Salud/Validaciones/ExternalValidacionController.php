<?php

namespace App\Http\Controllers\Externos\Salud\Validaciones;

use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use App\Models\User;

use App\Http\Controllers\ConexionSpController;

class ExternalValidacionController extends ConexionSpController
{
    
    /**
     * Anula una validación.
     * Endpoint externo
     * ws_anular_validacion
     * Consume: Silvio Quadri
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response $response
     */
    public function anular(Request $request)
    {
        // ver ejemplo de uso en AAEjemploUsoController
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_external_logged_user($user);
            
            $this->params = [
                'id_usuario' => $user->id, 
                'p_numero' => request('p_numero'),    
                'p_sucursal' => request('p_sucursal'),    
                'motivo' => request('motivo')
            ];
            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $error = 'sin especificar';
            
            if($user->hasPermissionTo('anular validaciones')){
                // ejecuta el sp
                try {
                    $response = $this->ejecutar_sp_directo('validacion_externa', 'ws_anular_validacion', null);
                    Log::channel('external')->info('ws_anular_validacion consultado por '.$request->user()->name);
                    // prepara la respuesta
                    // si hubo errores al ejecutar el sp
                    if(array_key_exists('error', $response)){
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición ';
                        $count = 0;
                        $data = null;
                        $error = $response['error']; 
                    }else{
                        // sino hubo errores al ajecutar sp y devuelve una autorización
                        if(array_key_exists('anulado', $response[0])){
                            // si la operación fue exitosa el estado es diferente de -1
                            if($response[0]->anulado != -1){
                                // retorna estado, sucursal(siempre es 994) y autorizacion
                                $status = 'ok';
                                $message = $response[0]->anulado == 1 ? 'Validación anulada con éxito.' : 'Transacción realizada con éxito';
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
                } catch (\Throwable $th) {
                    $status = 'fail';
                    $message = 'Error al realizar la petición.';
                    $count = 0;
                    $data = null;
                    $error = 'Line: '.$th->getLine().' Code: '.$th->getCode().' - '.$th->getMessage();
                }
                
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para ANULAR VALIDACIONES';
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
     * Consulta una validación.
     * Endpoint externo
     * ws_consultar_validacion
     * Consume: Silvio Quadri
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response $response
     */
    public function consultar(Request $request)
    {
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
            $response = null;
    
            // variables de parámetros
            $id_prestador = request('p_prestador') != null ? request('p_prestador') : null;
            $p_afiliado = request('p_afiliado') != null ? request('p_afiliado') : null;
            // verifica que tenga permisos para consultar validaciones
            if($user->hasPermissionTo('consultar validaciones')){
                if($p_afiliado == '' || $p_afiliado == null){
                    $status = 'fail';
                    $message = 'El parámetro p_afiliado es requerido.';
                    $count = 0;
                    $data = null;
                    $error = 'Falta de parámetros en la consulta';
                    $seguir = false;
                }else{
                    $seguir = true;
                }
                // si es prestador setea el parámetro id_prestador del usuario
                // para que ningún prestador pueda hacer consultas de otros prestadores
                if($user->hasRole('prestador')){
                    $id_prestador = $user->id_prestador != null ? $user->id_prestador : null;
                    if($id_prestador == null){
                        $status = 'fail';
                        $message = 'El usuario '.$user->roles[0]->name.' no tiene asignado id_prestador.';
                        $count = 0;
                        $data = null;
                        $error = 'Usuario no configurado';
                        $seguir = false;
                    }else{
                        $seguir = true;
                    }
                }
                // si es afiliado setea el parámetro p_afiliado y comprueba que venga
                // para que ninigún afiliado pueda hacer consultas de otros afiliados
                if($user->hasRole('afiliado')){
                    $id_prestador = null; // no debe ir seteado cuando es afiliado
                    if($p_afiliado == '' || $p_afiliado == null){
                        $status = 'fail';
                        $message = 'Falta de parámetros en la consulta';
                        $count = 0;
                        $data = null;
                        $error = 'Como es usuario con rol '.$user->roles[0]->name.' el parámetro p_afiliado es requerido.';
                        $seguir = false;
                    }else{
                        $seguir = true;
                    }
                }
                // si es administrador o super administrador debe proporcionar el id_prestador como un parámetro
                // ya que no está asociado a su usuario
                // if($user->hasRole('administrador') || $user->hasRole('super administrador')){
                //     $id_prestador = request('p_prestador'); // es necesario para consultar validaciones de un prestador
                //     if($id_prestador == null){
                //         $status = 'fail';
                //         $message = 'Falta de parámetros en la consulta';
                //         $count = 0;
                //         $data = null;
                //         $error = 'Como es usuario con rol '.$user->roles[0]->name.' el parámetro p_prestador es requerido.';
                //         $seguir = false;
                //     }else{
                //         $seguir = true;
                //     }
                // }
                // if($user->hasRole('gateway')){
                //     $id_prestador = request('p_prestador'); // es necesario para consultar validaciones de un prestador
                //     if($id_prestador == null){
                //         $status = 'fail';
                //         $message = 'Falta de parámetros en la consulta';
                //         $count = 0;
                //         $data = null;
                //         $error = 'Como es usuario con rol '.$user->roles[0]->name.' el parámetro p_prestador es requerido.';
                //         $seguir = false;
                //     }else{
                //         $seguir = true;
                //     }
                // }
                // si no hubo problemas de parámetros en las validaciones anteriores sigue adelante con la consulta
                if($seguir){
                    try {
                        $p_fd = request('p_fd');
                        $p_fh = request('p_fh');
                        $this->params = [
                            'p_afiliado' => $p_afiliado,
                            'p_fd' => isset($p_fd) ? Carbon::parse($p_fd)->format('Ymd') : null,
                            'p_fh' => isset($p_fd) ? Carbon::parse($p_fh)->format('Ymd') : null,
                            'p_id_prestador' => $id_prestador,
                            'p_id_estado' => is_string(request('p_id_estado')) ? intval(request('p_id_estado')) : request('p_id_estado'), // si es string lo caonvierte a entero, sino lo pasa como viene
                            // 'p_nombre' => null, // request('p_nombre') != null ? request('p_nombre') : '', // si es null lo convierte a cadena vacía
                            // 'p_numero' => null, // is_string(request('p_numero')) ? intval(request('p_numero')) : request('p_numero'),// si es string lo convierte a entero, sino lo pasa como viene
                        ];
                        
                        // ejecuta el sp
                        $response = $this->ejecutar_sp_directo('validacion_externa', 'ws_consultar_validacion', null);
                        Log::channel('external')->info('ws_consultar_validacion consultado por '.$request->user()->name.' (id '.$request->user()->id.') con parametros: '.json_encode($this->params));
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito';
                        $count = sizeof($response);
                        $data = $response;
                        $error = 'No se produjeron errores';
                        
                    } catch (\Throwable $th) {
                        $status = 'fail';
                        $message = 'Error no especificado';
                        $count  = 0;
                        $data = null;
                        $error = 'Line: '.$th->getLine().' Code: '.$th->getCode().' - '.$th->getMessage();
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CONSULTAR VALIDACIONES';
                $count  = 0;
                $data = null;
                $error = 'Intento de acceso no autorizado';
            }
            // retorna el response
            return response()->json([
                'status' => $status,
                'message' => $message,
                'count' => $count,
                'data' => $data,
                'error' => $error,
                'params' => $this->params,
                'logged_user' => $logged_user,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'fail',
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'count' => 0,
                'error' => 'Backend failed',
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'message' => $th->getMessage()
            ]);
        }
    }

     /**
     * Consulta la cobertura de un afiliado, como una validación pero sin realizar una autorización.
     * Endpoint externo
     * Consume: Silvio Quadri
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response $response
     */
    public function consultar_cobertura(Request $request)
    {
        // return 'Disculpe las molestias, esta ruta aún no está en funcionamiento.';
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
            $response = null;
    
            // variables de parámetros
            $id_prestador = null;
            $p_afiliado = null;
            $id_prestador = null;
            // verifica que tenga permisos para consultar validaciones
            if($user->hasPermissionTo('consultar validaciones')){
                // si es prestador setea el parámetro id_prestador del usuario
                // para que ningún prestador pueda hacer consultas de otros prestadores
                if($user->hasRole('prestador')){
                    $id_prestador = $user->id_prestador != null ? $user->id_prestador : null;
                    $p_afiliado = request('p_afiliado') != null ? request('p_afiliado') : null;
                    if($id_prestador == ''){
                        $status = 'fail';
                        $message = 'El usuario con rol '.$user->roles[0]->name.' no tiene asignado id_prestador.';
                        $count = 0;
                        $data = null;
                        $error = 'Usuario no configurado';
                        $seguir = false;
                    }else{
                        $seguir = true;
                    }
                }
                // si es afiliado setea el parámetro p_afiliado y comprueba que venga
                // para que ninigún afiliado pueda hacer consultas de otros afiliados
                if($user->hasRole('afiliado')){
                    $p_afiliado = request('p_afiliado') != null ? request('p_afiliado') : null;
                    $id_prestador = null; // no debe ir seteado cuando es afiliado
                    if($p_afiliado == ''){
                        $status = 'fail';
                        $message = 'Falta de parámetros en la consulta';
                        $count = 0;
                        $data = null;
                        $error = 'Como es usuario con rol '.$user->roles[0]->name.' el parámetro p_afiliado es requerido.';
                        $seguir = false;
                    }else{
                        $seguir = true;
                    }
                }
                // si es administrador o super administrador debe proporcionar el id_prestador como un parámetro
                // ya que no está asociado a su usuario
                if($user->hasRole('administrador') || $user->hasRole('super administrador')){
                    $id_prestador = request('p_prestador'); // es necesario para consultar validaciones de un prestador
                    if($id_prestador == ''){
                        $status = 'fail';
                        $message = 'Falta de parámetros en la consulta';
                        $count = 0;
                        $data = null;
                        $error = 'Como es usuario con rol '.$user->roles[0]->name.' el parámetro p_prestador es requerido.';
                        $seguir = false;
                    }else{
                        $seguir = true;
                    }
                }
                if($user->hasRole('gateway')){
                    $id_prestador = request('p_prestador'); // es necesario para consultar validaciones de un prestador
                    if($id_prestador == ''){
                        $status = 'fail';
                        $message = 'Falta de parámetros en la consulta';
                        $count = 0;
                        $data = null;
                        $error = 'Como es usuario con rol '.$user->roles[0]->name.' el parámetro p_prestador es requerido.';
                        $seguir = false;
                    }else{
                        $seguir = true;
                    }
                }
                // si no hubo problemas de parámetros en las validaciones anteriores sigue adelante con la consulta
                if($seguir){
                    try {
                        $this->params = [
                            'p_prestador' => $id_prestador,
                            'p_tipo_doc' => request('p_tipo_doc'), // varchar(10)
                            'p_nro_doc' => request('p_nro_doc'), // varchar(20)
                            'p_fecha_realizacion' => request('p_fecha_realizacion'), // datetime
                            'p_diagnostico' => request('p_diagnostico'), // varchar(40)=null
                            'p_json_prestaciones' => json_encode(request('p_json_prestaciones')), // nvarchar(max) json
                            // 'p_id_usuario' => $request->user()->id, // int=1
                            // 'p_autoriza_en_guardia' => request('p_autoriza_en_guardia'), // tinyint=0
                            // 'p_observaciones' => request('p_observaciones'), // varchar(1000)
                            'p_consulta_cobertura' => 1, // cuando es 0 ejecuta la emision de la validación ambulatoria, si es 1 solo consulta cobertura
                            'p_n_afiliado' => request('n_afiliado')
                        ];
                        
                        // ejecuta el sp
                        $response = $this->ejecutar_sp_directo('validacion_externa', 'ws_ambulatorio', null);
                        Log::channel('external')->info('ws_ambulatorio consultado por '.$request->user()->name.' (id '.$request->user()->id.') con parametro p_consulta_cobertura en 1');
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito';
                        $count = sizeof($response);
                        $data = $response;
                        $error = 'No se produjeron errores';
                        
                    } catch (\Throwable $th) {
                        $status = 'fail';
                        $message = 'Error no especificado';
                        $count  = 0;
                        $data = null;
                        $error = 'Line: '.$th->getLine().' Code: '.$th->getCode().' - '.$th->getMessage();
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CONSULTAR VALIDACIONES';
                $count  = 0;
                $data = null;
                $error = 'Intento de acceso no autorizado';
            }
            // retorna el response
            return response()->json([
                'status' => $status,
                'message' => $message,
                'count' => $count,
                'data' => $data,
                'error' => $error,
                'params' => $this->params,
                'logged_user' => $logged_user,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'fail',
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'count' => 0,
                'error' => 'Backend failed',
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'message' => $th->getMessage()
            ]);
        }
    }
}

