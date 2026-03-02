<?php

namespace App\Http\Controllers\Externos\Salud\Afiliaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
// use Illuminate\Support\Facades\File as FacadeFile;
use Illuminate\Support\Facades\Response as FacadeResponse;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use File;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;

class ExternalAfiliacionController extends ConexionSpController
{
    /**
     * Consulta el estado de un afiliado.
     * Externa
     * ws_elegibilidad
     * Consume: Silvio Quadri
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response $response
     */
    public function elegibilidad(Request $request)
    {
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_external_logged_user($user);
            
            $this->params = [
                'p_n_afiliado' => request('nro_afiliado'),
                'p_tipo_doc' => request('p_tipo_doc'),
                'p_nro_doc' => is_string(request('p_nro_doc')) ? intval(request('p_nro_doc')) : request('p_nro_doc'), // si es string lo convierte a entero, sino lo pasa como viene
            ];
            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            $code = 0;

            if($user->hasPermissionTo('consultar elegibilidad afiliaciones')){
                // ver ejemplo de uso en AAEjemploUsoController
                // ejecuta el sp
                try {
                    // $response['error'] = 'desconectado temporalmente';
                    $response = $this->ejecutar_sp_directo('validacion_externa', 'ws_elegibilidad', null);
                    Log::channel('external')->info('ws_elegibilidad consultado por '.$request->user()->name.' (id '.$request->user()->id.') con parametros: '.json_encode($this->params));
                    // return $response;
                    // prepara la respuesta
                    // si hubo errores al ejecutar el sp
                    if(is_array($response) && array_key_exists('error', $response)){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -3;
                    }else if($count = sizeof($response) == 0){
                        $status = 'empty';
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                        $count = sizeof($response);
                        $data = [];
                        $code = -4;
                    }else{
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = sizeof($response);
                        $data = $response[0];
                        $code = 1;
                    }
                } catch (\Throwable $th) {
                    array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' - '.$th->getMessage());
                    $status = 'fail';
                    $message = 'Se produjo un error al realizar la petición';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CONSULTAR ELEGIBILIDADMAFILIACIONES';
                $count  = 0;
                $data = null;
                $errors = null;
                $code = -1;
            }
            // retorna el response
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => $code,
                'data' => $data,
                'params' => $this->params,
                'logged_user' => $logged_user,
                'api_software_version' => config('site.external_software_version')
            ]); 
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' - '.$th->getMessage());
            $code = -2;
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $code,
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'api_software_version' => config('site.external_software_version')
            ]);
        }
    }

    /**
     * Busca un afiliado a través del sp_afiliado_Select, realizado para adminfarm
     */
    public function buscar_afiliado(Request $request)
    {
        // variables de respuesta inicializadas fuera de try para evitar "Undefined variable"
        $status = '';
        $message = '';
        $count = 0;
        $code = 0;
        $data = null;
        $errors = [];
        $this->params = [];
        $logged_user = null;
        
        try {
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_external_logged_user($user);
            
            if($user->hasPermissionTo('buscar afiliado')){
                // ver ejemplo de uso en AAEjemploUsoController
                // ejecuta el sp
                try {
                    $id_afiliado = request('id');
                    $tipo = request('tipo');
                    $id_persona = request('id_persona');
                    $codigo = request('codigo');
                    $nombre = request('nombre');
                    $id_tipo_documento = request('id_tipo_documento');
                    $numero_documento = request('numero_documento');
                    $tipo_busqueda = request('tipo_busqueda');
                    $id_plan = request('id_plan');
                    $id_convenio = request('id_convenio');
                    $id_empresa = request('id_empresa');
                    $id_grupo = request('id_grupo');
                    $hst = request('historico') != null ? request('historico') : 0;
                    
                    $params_sp = [];

                    $this->params['tipo_busqueda'] = $tipo_busqueda;

                    if ($codigo != ''){
                        $this->params['nro_afiliado'] = $codigo;
                        $params_sp['nro_afiliado'] = $codigo;
                    }
                    if ($id_afiliado != ''){
                        $this->params['p_id_afiliado'] = $id_afiliado;
                        $params_sp['p_id_afiliado'] = $id_afiliado;
                    }
                    if ($id_persona != ''){
                        $this->params['id_persona'] = $id_persona;
                        $params_sp['id_persona'] = $id_persona;
                    }
                    if ($nombre != ''){
                        $this->params['nombre'] = $nombre;
                        $params_sp['nombre'] = $nombre;
                    }
                    if ($id_tipo_documento != ''){
                        $this->params['id_tipo_doc'] = $id_tipo_documento;
                        $params_sp['id_tipo_doc'] = $id_tipo_documento;
                    }
                    if ($numero_documento != ''){
                        $this->params['nro_doc'] = $numero_documento;
                        $params_sp['nro_doc'] = $numero_documento;
                    }

                    if ($id_plan != ''){
                        $this->params['id_plan'] = $id_plan;
                        $params_sp['id_plan'] = $id_plan;
                    }
                    if ($id_convenio != ''){
                        $this->params['id_convenio'] = $id_convenio;
                        $params_sp['id_convenio'] = $id_convenio;
                    }
                    if ($id_empresa != ''){
                        $this->params['id_empresa'] = $id_empresa;
                        $params_sp['id_empresa'] = $id_empresa;
                    }
                    if ($id_grupo != ''){
                        $this->params['p_id_grupo'] = $id_grupo;
                        $params_sp['p_id_grupo'] = $id_grupo;
                    }
                    
                    $this->params['p_hst'] = $hst;
                    $params_sp['p_hst'] = $hst;

                    
                   
                    // $this->params['p_otros'] = 'ejemplo de error'; // puesto para provocar un error de sqlserver para verlo en el front
                    //	obtiene los afiliados
                    $response = $this->ejecutar_sp_directo('afiliacion_externa', 'sp_afiliado_Select', $params_sp);
                    if(isset($response['error'])){
                        array_push($errors, $response['error']);
                        $data = null;
                        $status = 'fail';
                        $count = 0;
                        $code = -3;
                        $message = 'Error en consulta a base de datos';
                        return response()->json([
                            'status' => $status,
                            'count' => $count,
                            'errors' => $errors,
                            'message' => $message,
                            'line' => null,
                            'code' => $code,
                            'data' => $data,
                            'params' => $this->params,
                            'logged_user' => $logged_user,
                            'api_software_version' => config('site.external_software_version')
                        ]);
                    }else{
                        $data = $response;
                        $status = 'ok';
                        $count = sizeof($response);
                        $message = 'Registros encontrados';
                    }

                    // return $response;
                    if(!empty($response)){
                        $status = 'ok';
                        $count = is_array($response) ? sizeof($response) : 1;
                        $this->params['tipo_busqueda'] = $tipo_busqueda;

                        if ($tipo_busqueda != 'por-codigo' 
                            || $tipo_busqueda != 'por-id' 
                            || $tipo_busqueda != 'por-id-persona'){
                            $data = $response;
                        }
                        //	si es por código o por-id o por-id-persona o por-documento
                        if ($tipo_busqueda == 'por-codigo' 
                            || $tipo_busqueda == 'por-id' 
                            || $tipo_busqueda == 'por-id-persona' 
                            || $tipo_busqueda == 'por-documento') {
                            
                            //	obtiene el primer registro
                            $data = (array)$response[0];

                            if($data['fec_hasta'] != null){
                                $hoy = Carbon::now();
                                $d = explode('/', $data['fec_hasta']);
                                $fec_hasta = Carbon::parse($d[2], ($d[1] -1), $d[0]);
                                if($fec_hasta->gt($hoy)){
                                    $data['is_fec_hasta_vencida'] = true;
                                }else{
                                    $data['is_fec_hasta_vencida'] = false;
                                }
                            }else{
                                $data['is_fec_hasta_vencida'] = false;
                            }
    
                            //  establece el ID de la persona
                            $id_persona = FALSE;
                            if (!empty($data['id_persona'])){
                                $id_persona = $data['id_persona'];
                            }
                            if (!empty($data['PerId'])){
                                $id_persona = $data['PerId'];
                            }
    
                            //  obtiene el nombre del empleador
                            if ($id_persona){
                                $params_empleador = [
                                    'p_id_persona' => $id_persona, 
                                    'p_activos' => 1
                                ];
                                $empleador = $this->ejecutar_sp_directo('afiliacion_externa', 'sp_empleado_Select', $params_empleador);
                                if($empleador){
                                    $data['empleador'] = $empleador;
                                }

                                $params_persona = [
                                    'p_id_persona' => $id_persona
                                ];
                                $persona = $this->ejecutar_sp_directo('afiliacion_externa', 'sp_persona_Select', $params_persona);
                                if($persona){
                                    if(is_array($persona)){
                                        $persona = $persona[0];
                                    }
                                    $data['persona'] = $persona;
                                    $data['fec_vigencia'] = Carbon::parse($persona->fec_vigencia)->format('d/m/Y');
                                    $data['fec_carga'] = Carbon::parse($persona->fec_carga)->format('d/m/Y');
                                    $data['id_rango'] = $persona->id_rango;
                                    // $data['abcdsefs'] = $persona->id_rango;
                                    $data['situacion'] = $persona->situacion;
                                }
                            }
                            
                            if($data['id_grupo'] != null){
                                $params_grupo = [
                                    'p_id_grupo' => $data['id_grupo'],
                                    'p_activos' => 1
                                ];
                                $grupo = $this->ejecutar_sp_directo('afiliacion_externa', 'sp_grupo_afiliado_Select', $params_grupo);
                                $data['grupo_familiar'] = $grupo;
                            }
                            //	establece los datos particulares
                            $data['codigo'] = $data['n_afiliado'];
                            $data['numero_afiliado'] = $data['n_afiliado'];
                            $data['nombre_afiliado'] = $data['n_persona'];
                            $data['documento'] = $data['abrev_tipo_doc'] . ' ' . $data['nro_doc'];
                            $data['estado'] = (empty($data['activo']) || round(trim($data['activo'])) == 0) ? 2 : 1;
                            // $data['fec_nac'] = !empty($data['fec_nac']) ? Carbon::parse($data['fec_nac'])->format('d/m/Y') : '';
                            $data['vto_certificado'] = !empty($data['vto_certificado']) ? Carbon::parse($data['vto_certificado'])->format('d/m/Y') : '';
    
                            $data['vto_certificado_js'] = '';
                            if (!empty($data['vto_certificado'])){
                                $data['vto_certificado_js'] = Carbon::parse($data['vto_certificado'])->format('Y d m');
                            }
                        }
                        // obtiene el avatar 
                        if ($tipo_busqueda == 'por-codigo' 
                            || $tipo_busqueda == 'por-id' 
                            || $tipo_busqueda == 'por-id-persona' 
                            || $tipo_busqueda == 'por-documento') {
                            if (isset($response[0]->foto) && Storage::disk('avatars_externo')->exists($response[0]->foto)) {
                                $ext = explode('.', $response[0]->foto);
                                $response[0]->n_foto = $response[0]->foto;
                                $response[0]->foto = 'data:image/' . $ext[1] . ';base64,' . base64_encode(file_get_contents(env('AVATARS_PATH').DIRECTORY_SEPARATOR.($response[0]->foto)));
                                $data['n_foto'] = $response[0]->n_foto;
                                $data['foto'] = $response[0]->foto;
                            }
                        }
                    }else{
                        $status = 'empty';
                        $data = null;
                        $count = 0;
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                    }
                } catch (\Throwable $th) {
                    array_push($errors, $th->getMessage());
                    $message = 'Se produjo un error al realizar la petición.';
                    $status = 'fail';
                    $count = 0;
                    $data = null;
                    $code = -2;
                    return response()->json([
                        'status' => $status,
                        'count' => $count,
                        'errors' => $errors,
                        'message' => $message,
                        'line' => null,
                        'code' => $code,
                        'data' => $data,
                        'params' => $this->params,
                        'logged_user' => $logged_user,
                        'api_software_version' => config('site.external_software_version')
                    ]);
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para BUSCAR AFILIADO';
                $count  = 0;
                $data = null;
                $error = 'Error de permisos';
            }
            // retorna el response
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => $code,
                'data' => $data,
                'params' => $this->params,
                'logged_user' => $logged_user,
                'api_software_version' => config('site.external_software_version')
            ]); 
        } catch (\Throwable $th) {
            array_push($errors, 'Error de Backend');
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => $code,
                'data' => $data,
                'params' => $this->params,
                'logged_user' => $logged_user,
                'api_software_version' => config('site.external_software_version')
            ]);
        }
    }

}

