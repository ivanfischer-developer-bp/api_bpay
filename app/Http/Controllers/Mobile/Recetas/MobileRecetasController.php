<?php

namespace App\Http\Controllers\Mobile\Recetas;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;
use App\Models\ProfileDoctor;

use File;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MobileRecetasController extends ConexionSpController
{
    /**
     * Obtiene un listado de los financiadores de INNOVAMED
     */
    public function get_financiadores(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'mobile/recetas/financiadores',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = '';
        $code = 0;
        $line = null;
        $message = '';
        $errors = [];
        $count = 0;

        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        $url = env('QBI2RECIPE_URLBASE').'/GetFinanciadores?id='.env('QBI2RECIPE_CLIENTEAPPID');

        $params = [
            'url' => $url
        ];

        try {
            $response = get_financiadores();
            $resp = json_decode($response);
            array_push($extras['responses'], ['api' => $response]);
            // return $resp;
            if(isset($resp->error)){
                array_push($errors, $resp->error);
                $status = 'fail';
                $count = 0;
                $message = $resp->mensaje;
                $data = null;
                $code = -2;
            }else{
                $status = 'ok';
                $count = sizeof($resp->financiadores);
                $message = 'Financiadores obtenidos';
                $data = $resp->financiadores;
                $code = 1;
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
        } catch (\Exception $e) {
            array_push($errors, 'Line: '.$e.getLine().'Error: '.$e->getMessage());
            $message = 'Fallo en backend';
            $code = -1;
            $count = 0;
            $status = 'fail';
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => $e.getLine(),
                'code' => $code,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]); 
        } 
    }

    /**
     * Obtiene un listado de practicas de https://apirecipe.hml.qbitos.com/apirecipe/GetDiagnostico
     */
    public function get_diagnosticos(Request $request)
    {
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $texto = request('texto');

            $response = get_diagnosticos($texto);
            $resp = json_decode($response, true);
            if(isset($resp['error'])){
                response()->json([
                    'data' => $resp['data'],
                    'error' => $resp['error'],
                    'line' => $resp['line'],
                    'message' => $resp['message']
                ]);
            }else{
                return json_decode($response);
            }
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'error' => $e->getCode(),
                'line' => $e.getLine(),
                'message' => $e->getMessage()
            ]);
        }
    }

    public function get_medicos(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/recetas/medicos',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
        ];
        try {
            $this->params = [];

            $status = 'fail';
            $count = -1;
            $errors = [];
            $message = null;
            $line = null;
            $code = null;
            $data = [];

            $response_sp_recetas = [];
            $recetas_ok = false;
            $recetas_guardadas_ok = false;
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;
            $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

            if($user->hasRole('super administrador') || $user->can('listar medicos')){
                // $medicos = ProfileDoctor::with('users')->get();
                $users = User::with('profile_doctor')->get();
                foreach($users as $user){
                    if($user->profile_doctor != null){
                        $m = [
                            'id' => $user->profile_doctor->id,
                            'id_usuario_sqlserver' => $user->id_usuario_sqlserver,
                            'user_id' => $user->id,
                            'apellido' => $user->profile_doctor->apellido,
                            'nombre' => $user->profile_doctor->nombre,
                            'tratamiento' => $user->profile_doctor->tratamiento != null ? $user->profile_doctor->tratamiento : '',
                            'tipoDoc' => $user->profile_doctor->tipoDoc,
                            'nroDoc' => $user->profile_doctor->nroDoc,
                            'especialidad' => $user->profile_doctor->especialidad,
                            'sexo' => $user->profile_doctor->sexo,
                            'fechaNacimiento' => $user->profile_doctor->fechaNacimiento,
                            'email' => $user->profile_doctor->email,
                            'telefono' => $user->profile_doctor->telefono,
                            'pais' => $user->profile_doctor->pais,
                            'firmalink' => $user->profile_doctor->firmalink,
                            'matricula_tipo' => $user->profile_doctor->matricula_tipo,
                            'matricula_numero' => $user->profile_doctor->matricula_numero,
                            'matricula_provincia' => $user->profile_doctor->matricula_provincia,
                            'cuit' => $user->profile_doctor->cuit,
                            'id_convenio' => $user->profile_doctor->id_convenio,
                            'idTributario' => $user->profile_doctor->cuit, // idTributario es el cuit pero es un parametro requerido por innovamed
                            'idRefeps' => $user->profile_doctor->idRefeps,
                            'ambiente_recipe' => $user->profile_doctor->ambiente_recipe != null ? $user->profile_doctor->ambiente_recipe : env('AMBIENTE_RECIPE', 'local'),
                            'matricula' => [
                                'tipo' => $user->profile_doctor->matricula_tipo,
                                'numero' => $user->profile_doctor->matricula_numero,
                                'provincia' => $user->profile_doctor->matricula_provincia
                            ],
                            'horario' => $user->profile_doctor->horario,  // deprecado
                            'diasAtencion' => $user->profile_doctor->diasAtencion,  // deprecado
                            'datosContacto' => $user->profile_doctor->datosContacto,  // deprecado
                            'nombreConsultorio' => $user->profile_doctor->nombreConsultorio,
                            'direccionConsultorio' => $user->profile_doctor->direccionConsultorio,
                            'informacionAdicional' => $user->profile_doctor->informacionAdicional  // deprecado
                        ]; 
                        array_push($data, $m);
                    }
                }
            }
            if($user->hasRole('secretaria medico')){
                $secretary = ProfileSecretay::with('doctors')
                                            ->where('user_id', '=', $user->id)
                                            ->first();
                $medicos = $secretary->doctors;

                foreach($medicos as $medico){
                    $id_usuario_sqlserver = DB::table('users')->select('id_usuario_sqlserver')->where('users.id', '=', $medico->user_id);
                    $m = [
                        'id' => $medico->id,
                        'user_id' => $medico->user_id,
                        'id_usuario_sqlserver' => $id_usuario_sqlserver,
                        'apellido' => $medico->apellido,
                        'nombre' => $medico->nombre,
                        'tratamiento' => $medico->tratamiento != null ? $medico->tratamiento : '',
                        'tipoDoc' => $medico->tipoDoc,
                        'nroDoc' => $medico->nroDoc,
                        'especialidad' => $medico->especialidad,
                        'sexo' => $medico->sexo,
                        'fechaNacimiento' => $medico->fechaNacimiento,
                        'email' => $medico->email,
                        'telefono' => $medico->telefono,
                        'pais' => $medico->pais,
                        'firmalink' => $medico->firmalink,
                        'matricula_tipo' => $medico->matricula_tipo,
                        'matricula_numero' => $medico->matricula_numero,
                        'matricula_provincia' => $medico->matricula_provincia,
                        'cuit' => $medico->cuit,
                        'id_convenio' => $user->profile_doctor->id_convenio,
                        'idTributario' => $user->profile_doctor->cuit, // idTributario es el cuit pero es un parametro requerido por innovamed
                        'idRefeps' => $user->profile_doctor->idRefeps,
                        'matricula' => [
                            'tipo' => $medico->matricula_tipo,
                            'numero' => $medico->matricula_numero,
                            'provincia' => $medico->matricula_provincia
                        ],
                        'horario' => $medico->horario,  // deprecado
                        'diasAtencion' => $medico->diasAtencion,  // deprecado
                        'datosContacto' => $medico->datosContacto,  // deprecado
                        'nombreConsultorio' => $medico->nombreConsultorio,
                        'direccionConsultorio' => $medico->direccionConsultorio,
                        'informacionAdicional' => $medico->informacionAdicional  // deprecado
                    ]; 
                    array_push($data, $m);
                }
            }
            
            $status = 'ok';
            $errors = [];
            $count = sizeof($data);
            $message = 'Registros encontrados';
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => 1,
                'data' => $data,
                'params' => $this->params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        } catch (\Exception $e) {
            array_push($errors, 'Error de Backend');
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'data' => $data,
                'params' => $this->params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        }
    }

    // ###############################################################################################################
    // ############################### RECETAS DE MEDICAMENTOS #########################################################
    // ###############################################################################################################

    /**
     * Obtiene un listado de medicamentos de https://apirecipe.hml.qbitos.com/apirecipe/GetMedicamento/
     */
    public function get_medicamentos(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'mobile/recetas/medicamentos',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = '';
        $code = 0;
        $line = null;
        $message = '';
        $errors = [];
        $count = 0;

        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        $param = eliminar_acentos(request('medicamento'));
        $numero_pagina = request('numeroPagina');
        $consultar_vademecum = request('consultar_vademecum') == null || request('consultar_vademecum') == 'si' ? true : false;

        $params = [
            'medicamento' => eliminar_acentos(request('medicamento')),
            'numeroPagina' => request('numeroPagina'),
            'consultar_vademecum' => $consultar_vademecum
        ];

        if(request('consultar_vademecum') != null){
            $params['consulta_vademecum'] = $consultar_vademecum;
        }

        try {
            $response = get_medicamentos($param, $numero_pagina);
            $resp = json_decode($response);
            array_push($extras['responses'], ['api' => $response]);
            // return $resp;
            if(isset($resp->error)){
                array_push($errors, $resp->error);
                $status = 'fail';
                $count = 0;
                $message = $resp->mensaje;
                $data = null;
                $code = -2;
            }else{
                // consultar vademecum si el ambiente es sanos
                if($consultar_vademecum){
                    $json_items = [];
                    foreach ($resp->medicamentos as $medicamento) {
                        $json_item = [
                            'nro_registro' => null, //: int, 
                            'troquel' => null, //: varchar(7), 
                            'nombre' => $medicamento->nombreProducto, //: varchar(44), 
                            'presentacion' => $medicamento->presentacion //: varchar(24)
                        ];
                        array_push($json_items, $json_item);
                    }
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_vademecum', ['p_json_items' => json_encode($json_items)]));
                    array_push($extras['sps'], ['sp_vademecum' => ['p_json_items' => $json_items]]);
                    $vademecum = $this->ejecutar_sp_directo('afiliacion', 'sp_vademecum', ['p_json_items' => json_encode($json_items)]);
                    array_push($extras['responses'], ['sp_vademecum' => $vademecum]);
                    
                    if(sizeof($vademecum) > 0){
                        for ($i = 0; $i < sizeof($resp->medicamentos); $i++) {
                            for ($j = 0; $j < sizeof($vademecum); $j++) {
                                if(intval($resp->medicamentos[$i]->regNo) == intval($vademecum[$j]->nro_registro)){
                                    $resp->medicamentos[$i]->tieneCobertura = $vademecum[$j]->cobertura == 'NO' ? false : true;
                                    $resp->medicamentos[$i]->requiereAprobacion = $vademecum[$j]->req_autorizacion == 'NO' ? false : true;
                                }
                            }
                        }
                        $status = 'ok';
                        $count = sizeof($resp->medicamentos);
                        $message = 'Medicamentos con vademecum obtenidos';
                        $data = $resp;
                        $code = 3;
                    }else{
                        array_push($errors, 'Error consultando vademecum');
                        $status = 'fail';
                        $count = sizeof($resp->medicamentos);
                        $message = 'Medicamentos obtenidos sin vademecum';
                        $data = $resp;
                        $code = 2;
                    }
                }else{
                    $status = 'ok';
                    $count = sizeof($resp->medicamentos);
                    $message = 'Medicamentos obtenidos';
                    $data = $resp;
                    $code = 1;
                }
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
        } catch (\Exception $e) {
            array_push($errors, 'Line: '.$e.getLine().'Error: '.$e->getMessage());
            $message = 'Fallo en backend';
            $code = -1;
            $count = 0;
            $status = 'fail';
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => $e.getLine(),
                'code' => $code,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]); 
        } 
    }

    /**
     * Anula (Elimina) una receta generada con QBI2recipe
     * endpoint https://apirecipe.qbitos.com/apirecipe/Receta/{{id}}
     */
    public function anular_receta(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'mobile/recetas/anular-receta',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail';
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $params = [];
        $params_sp = [];
        $ejecutar_sp= true;
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            $permiso_requerido = 'anular receta';
            if($user->hasPermissionTo($permiso_requerido)){
                $ambiente_recipe = request('ambiente_recipe') !== null ? request('ambiente_recipe') : env('AMBIENTE_RECIPE');
                $id_receta = request('id_receta');
                $params = [
                    'id_receta' => $id_receta,
                    'ambiente_recipe' => $ambiente_recipe
                ];
                if ( empty($id_receta) ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    array_push($extras['verificado'], ['id_receta' => $id_receta]);
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;  // metodo anular_receta
                }else{
                    $response = anular_receta($id_receta, $ambiente_recipe);
                    $json = json_decode($response['response']);
                    $extras['info_curl'] = $response['info_curl'];
                    $extras['ambiente_recipe'] = $ambiente_recipe;
                    array_push($extras['responses'], ['api_recetas' => $response['response']]);
                    if(!empty($response['errors'])){
                        foreach ($response['errors'] as $error) {
                            array_push($errors, $error);
                        }   
                    }

                    // {
                    //     "error": "QBI2 OPERACION INVALIDA.",
                    //     "mensaje": "La receta ya habia sido previamente anulada"
                    // }

                    // {
                    //     "codigoRespuesta": 0,
                    //     "mensaje": "Receta anulada correctamente"
                    // }

                    if($json == null){
                        array_push($errors, 'Error de api de recetas. Retorno NULL');
                        $status = 'fail';
                        $count = 0;
                        $message = 'La api de recetas retornó NULL';
                        $code = -6;
                        $data = $json;
                    }else{
                        if(property_exists($json, 'error')){
                            array_push($errors, $json->error);
                            $status = 'fail';
                            $count = 0;
                            $message = 'Error en API de recetas. '.$json->error;
                            $code = -8;
                            $data = null;
                        }else{
                            if((property_exists($json, 'codigoRespuesta') && $json->codigoRespuesta == 0) || ($json->mensaje == 'Receta anulada correctamente' || $json->mensaje == 'La receta ya habia sido previamente anulada')){
                                
                                $response_sp = [];
                                // ejecutamos el sp para borrar la receta del listado
                                $params_sp = [
                                    'id' => request('id_receta')
                                ];
                                array_push($extras['sps'], ['sp_HC_Receta_delete' => $params_sp]);
                                array_push($extras['queries'], $this->get_query('validacion', 'sp_HC_Receta_delete', $params_sp));
                                $response_sp = $this->ejecutar_sp_directo('validacion','sp_HC_Receta_delete', $params_sp);
                                array_push($extras['responses'], ['sp_HC_Receta_delete' => $response_sp]);
                                if(is_array($response_sp) && array_key_exists('error', $response_sp)){
                                    array_push($errors, $response_sp['error']);
                                    $status = 'fail';
                                    $message = 'Se produjo un error al realizar la petición';
                                    $count = 0;
                                    $data = null;
                                    $code = -3;
                                }else if(empty($response_sp)){
                                    $status = 'empty';
                                    $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                                    $count = 0;
                                    $data = $response_sp;
                                    $code = -4;
                                }else{
                                    $status = 'ok';
                                    $message = 'Transacción realizada con éxito.';
                                    $count = 1;
                                    $data = [
                                        'api_recipe' => $json,
                                        'sp' => $response_sp
                                    ]; //$response;
                                    $code = 1;
                                }
                            }else{
                                array_push($errors, $json->mensaje);
                                $status = 'fail';
                                $count = 0;
                                $message = $json->mensaje;
                                $code = -7;
                                $data = null;
                            }
                        }
                    }
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
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($permiso_requerido);
                $count  = -2;
                $data = null;
                array_push($errors, 'Error de permisos. '.$message);
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => -2,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Busca una receta en el endpoint de QBI2recipe
     * No se usa en este momento, pero se deja como referencia
     */
    public function buscar_receta(Request $request)
    {
        $nro_receta = request('nro_receta');
        $dni = request('dni');
        $nro_afiliado = request('nro_afiliado');
        $ambiente_recipe = request('ambiente_recipe');
        return buscar_receta($nro_receta, $dni, $nro_afiliado, $ambiente_recipe);

        // $req['nroReceta'] = request('nro_receta');  //idReceta en el response de las recetas
        // if(request('dni') != null){
        //     $req['dni'] = request('dni');
        // }
        // if(request('nro_afiliado') != null){
        //     $req['nroAfiliado'] = request('nro_afiliado');
        // }
        // $ambiente_recipe = request('ambiente_recipe') !== null ? request('ambiente_recipe') : env('AMBIENTE_RECIPE');
        // try {
        //     // $url = 'https://apirecipe.hml.qbitos.com/apirecipe/Receta/Get';

        //     if($ambiente_recipe == 'staging' || $ambiente_recipe == 'local'){
        //         $req['clienteAppId'] = env('QBI2RECIPE_CLIENTEAPPID_TESTING');
        //         $url = env('QBI2RECIPE_URLRECETA_TESTING').'/Get';
        //         curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //             'Content-Type: application/json',
        //             'Authorization: Bearer '.env('QBI2RECIPE_TOKEN_TESTING')
        //         ) );
        //     }else{
        //         $req['clienteAppId'] = env('QBI2RECIPE_CLIENTEAPPID');
        //         $url = env('QBI2RECIPE_URLRECETA').'/Get';
        //         curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //             'Content-Type: application/json',
        //             'Authorization: Bearer '.env('QBI2RECIPE_TOKEN')
        //         ) );
        //     }

        //     // $url = env('QNI2RECIPE_URLRECETA').'/Get';
        //     $ch = curl_init();
        //     curl_setopt($ch, CURLOPT_URL, $url);
        //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        //     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($req));
        //     // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //     //     'Content-Type: application/json',
        //     //     'Authorization: Bearer '.env('QBI2RECIPE_TOKEN')
        //     // ) );
        //     curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        //     $response = curl_exec($ch);
        //     curl_close($ch);
        //     return json_decode($response);
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'data' => null,
        //         'error' => $e->getCode(),
        //         'line' => $e.getLine(),
        //         'message' => $e->getMessage()
        //     ]);
        // } finally {
        //     if(is_resource($ch)){
        //         curl_close( $ch );
        //     }
        // }
    }

    /**
     * Retorna un listado de recetas generadas por un medico
     */
    public function listar_recetas_emitidas(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/recetas/listar-recetas-emitidas',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
        ];
        try {
            // date_default_timezone_set('America/Argentina/Cordoba');
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;
            $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

            $id_medico = request('id_medico') != 0 ? request('id_medico') : null;
            $fecha_desde = request('fecha_desde') != null && request('fecha_desde') != '' ? request('fecha_desde') : null;
            $fecha_hasta = request('fecha_hasta') != null && request('fecha_hasta') != '' ? request('fecha_hasta') : null;

            if($fecha_desde != null){
                $desde = Carbon::parse($fecha_desde)->startOfDay()->format('Y-m-d H:i:s');
            }else{
                $desde = null;
            }
            
            if($fecha_hasta != null){
                $hasta = Carbon::parse($fecha_hasta)->endOfDay()->format('Y-m-d H:i:s');
            }else{
                $hasta = null;
            }

            $this->params = [
                'id_medico' => request('id_medico'),
                'fecha_desde' => request('fecha_desde'),
                'fecha_hasta' => request('fecha_hasta'),
                'doc_tipo' => request('doc_tipo'),
                'doc_numero' => request('doc_numero'),
                'desde' => $desde,
                'hasta' => $hasta
            ];
            
            $this->params_sp = [
                'id_medico' => request('id_medico'),
                'doc_tipo' => request('doc_tipo'),
                'doc_numero' => request('doc_numero'),
                'fecha_desde' => $desde,
                'fecha_hasta' => $hasta
            ];

            $status = 'fail';
            $count = -1;
            $errors = [];
            $message = null;
            $line = null;
            $code = null;
            $data = null;
            if($user->hasPermissionTo('listar recetas')){
                array_push($extras['queries'], $this->get_query('validacion', 'sp_HC_Receta_select', $this->params_sp));
                array_push($extras['sps'], ['sp_HC_Receta_select' => $this->params_sp]);
                $response = $this->ejecutar_sp_directo('validacion', 'sp_HC_Receta_select', $this->params_sp);
                array_push($extras['responses'], ['sp_HC_Receta_select' => $response]);
                
                if(isset($response['error'])){
                    array_push($errors, $response['error']);
                }else{
                    if(sizeof($response) > 0){
                        $data = $response;
                        $status = 'ok';
                        $count = sizeof($data);
                        $message = 'Registros encontrados';
                    }else{
                        $data = $response;
                        $status = 'empty';
                        $count = 0;
                        $message = 'No se encontraron registros';
                    }
                }
                
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => 1,
                    'params' => $this->params,
                    'data' => $data,
                    'extras' => $extras,
                    'logged_user' => $logged_user
                ]);
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para LISTAR RECETAS';
                $count  = 0;
                $data = null;
                $error = 'Error de permisos. '.$message;
                arry_push ($errors, $error);
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => null,
                    'params' => $this->params,
                    'data' => $data,
                    'extras' => $extras,
                    'logged_user' => $logged_user
                ]); 
            }
        } catch (\Exception $e) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'params' => $this->params,
                'data' => $data,
                'extras' => $extras,
                'logged_user' => $logged_user
            ]);
        }
    }

    /**
     * Genera una nueva receta de medicamentos en https://apirecipe.hml.qbitos.com/apirecipe/Receta
     */
    public function generar_receta_medicamentos(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/recetas/generar-receta',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => [],
        ];

        try {
            $status = 'fail';
            $count = -1;
            $errors = [];
            $message = null;
            $line = null;
            $code = 0;  // metodo generar_receta
            $data = null;
            $params_log = [];

            $id_receta = null;

            // $ch = curl_init();

            $id_hc = request('id_hc');
            $id_hoja_hc = request('id_hoja_hc');
            $id_medico = request('id_medico');
            $cie10 = request('cie10');
            $mireceta = request('receta');
            $nro_siniestro = sizeof($mireceta['informacionExtra']) > 0 && $mireceta['informacionExtra'][0]['key'] == 'SINIESTRO' ? $mireceta['informacionExtra'][0]['value'] : null;
            $token_siaf = request('token_siaf');
            $id_convenio_siaf = request('id_convenio_siaf');
            $informar_siaf = request('informar_siaf') == 'true' ? true : false;
            $ambiente_recipe = request('ambiente_recipe') !== null ? request('ambiente_recipe') : env('AMBIENTE_RECIPE');

            $paciente = request('paciente');

            $n_convenio = request('n_convenio') !== null ? request('n_convenio') : null;
            $id_plan = request('id_plan') !== null ? request('id_plan') : null;
        
            // if($ambiente_recipe == 'staging' || $ambiente_recipe == 'local'){
            //     $mireceta['subemisor']['nombre'] = env('SUBEMISOR_NOMBRE_TESTING');
            //     $mireceta['subemisor']['cuit'] = env('SUBEMISOR_CUIT_TESTING');
            //     $mireceta['subemisor']['direccion'] = env('SUBEMISOR_DIRECCION_TESTING');
            //     $mireceta['subemisor']['logoBase64'] = base64_encode(file_get_contents(env('SUBEMISOR_LOGO_TESTING')));
            //     $mireceta['clienteAppId'] = env('QBI2RECIPE_CLIENTEAPPID_TESTING');
            // }else{
            //     $mireceta['subemisor']['nombre'] = env('SUBEMISOR_NOMBRE');
            //     $mireceta['subemisor']['cuit'] = env('SUBEMISOR_CUIT');
            //     $mireceta['subemisor']['direccion'] = env('SUBEMISOR_DIRECCION');
            //     $mireceta['subemisor']['logoBase64'] = base64_encode(file_get_contents(env('SUBEMISOR_LOGO')));
            //     $mireceta['clienteAppId'] = env('QBI2RECIPE_CLIENTEAPPID');
            // }
            
            // if(file_exists(storage_path('app/public/uploads/firma_medicos').'/'.$mireceta['medico']['nroDoc'].'.png')){
            //     $mireceta['medico']['firmabase64'] = base64_encode(file_get_contents(storage_path('app/public/uploads/firma_medicos').'/'.$mireceta['medico']['nroDoc'].'.png'));
            // }


            $this->params = [
                'id_hc' => $id_hc,
                'id_hoja_hc' => $id_hoja_hc,
                'id_medico' => $id_medico,
                'cie10' => $cie10,
                'receta' => $mireceta,
                'token_siaf' => $token_siaf,
                'id_convenio_siaf' => $id_convenio_siaf,
                'informar_siaf' => $informar_siaf,
                'paciente' => $paciente,
                'ambiente_receta' => $ambiente_recipe,
            ];

            // return $this->params;

            $response_sp_recetas = [];
            $recetas_ok = false;
            $recetas_guardadas_ok = false;
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;
            $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

            // $ch = curl_init();
            // curl_setopt($ch, CURLOPT_URL, 'https://apirecipe.hml.qbitos.com/apirecipe/Receta');
            // if($ambiente_recipe == 'staging' || $ambiente_recipe == 'local'){
            //     curl_setopt($ch, CURLOPT_URL, env('QBI2RECIPE_URLRECETA_TESTING'));
            //     curl_setopt($ch, CURLOPT_HTTPHEADER, [
            //         'Content-Type: application/json',
            //         'Authorization: Bearer '.env('QBI2RECIPE_TOKEN_TESTING'),
            //     ]);
            // }else{
            //     curl_setopt($ch, CURLOPT_URL, env('QBI2RECIPE_URLRECETA'));
            //     curl_setopt($ch, CURLOPT_HTTPHEADER, [
            //         'Content-Type: application/json',
            //         'Authorization: Bearer '.env('QBI2RECIPE_TOKEN'),
            //     ]);
            // }
            // curl_setopt($ch, CURLOPT_URL, env('QBI2RECIPE_URLRECETA'));
            // curl_setopt($ch, CURLOPT_HTTPHEADER, [
            //     'Content-Type: application/json',
            //     'Authorization: Bearer '.env('QBI2RECIPE_TOKEN'),
            // ]);
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($mireceta));
            // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            // $response = curl_exec($ch);
            // $info_curl = [
            //     'url' => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
            //     'campos' => json_decode(json_encode($mireceta)),
            //     'respuesta' => json_decode($response)
            // ];
            // $extras['info_curl'] = $info_curl;
            // curl_close($ch);
            $respose = generar_receta_medicamentos($mireceta, $ambiente_recipe);
            $json = json_decode($respose['response']);
            $extras['info_curl'] = $respose['info_curl'];
            array_push($extras['responses'], ['api_recetas' => $json]);
            
            if($json == null){
                $recetas_ok = false;
                $errors = ['Error, la API de recetas devolvió NULL'];
                    // $message = 'No fue posible generar receta debido a un problema externo.';
                    $message = 'No fue posible generar receta, actualice el archivo de firma por uno cuyo tamaño sea menor a 1 mega.';
                    $status = 'fail';
                    $count = 0;
                    $code = -9;
            }else{
                if(property_exists($json, 'recetas')){
                    $recetas_ok = true;
                    $data = [
                        'response_api' => $json,
                        'ids_recetas' => [],
                        'response_sp' => []
                    ];
    
                    $c = 0;
                    foreach($json->recetas as $receta){
                        $params = [
                            'id_hoja' => $id_hoja_hc,
                            'id_historia' => $id_hc,
                            'fecha' => $receta->fecha,
                            'fechavencimiento' => $json->response[$c]->fechavencimiento,
                            'id' => $receta->id,
                            'idReceta' => $receta->idReceta,
                            's3Link' => $receta->s3Link,
                            'verificador' => $receta->verificador,
                            'autorizacion' => isset($json->authredon) && !empty($json->authredon) && isset($json->authredon[0]->autorizacion) ? $json->authredon[0]->autorizacion : null,
                            'usuario' =>  $logged_user['name'],
                            'diagnostico' => $json->response[$c]->diagnostico,
                            'id_medico' => $id_medico,
                            'nro_siniestro' => $nro_siniestro,
                            'medico_apellido' => $json->response[$c]->medico->apellido,
                            'medico_nombre' => $json->response[$c]->medico->nombre,
                            'medico_numeroDocumento' => $json->response[$c]->medico->numeroDocumento,
                            'medico_numeroMatricula' =>  $mireceta['medico']['matricula']['numero'], //$json->response[$c]->medico->matricula->numero, //numeroMatricula,
                            'medico_provinciaMatricula' => $mireceta['medico']['matricula']['provincia'], //$json->response[$c]->medico->matricula->provincia, //provinciaMatricula,
                            'medico_tipoMatricula' =>  $mireceta['medico']['matricula']['tipo'], //$json->response[$c]->medico->matricula->tipo, // tipoMatricula,
                            'medico_especialidad' => $json->response[$c]->medico->especialidad,
                            'medico_fechaNacimiento' => $json->response[$c]->medico->fechaNacimiento,
                            'observaciones' => $json->response[$c]->observaciones, 
                            'ambiente' => $ambiente_recipe,
    
                            'Doc_tipo' => isset($paciente) ? $paciente['Doc_tipo'] : null,
                            'Doc_Numero' => isset($paciente) ? $paciente['Doc_Numero'] : null,
                            'n_persona' => isset($paciente) ? $paciente['n_persona'] : null,
                            'sexo' => isset($paciente) ? $paciente['sexo'] : null,
                            'fec_nac' => isset($paciente) ? $paciente['fec_nac'] : null,
                            'n_afiliado' => isset($paciente) ? $paciente['n_afiliado'] : null,
                            'id_plan' => isset($paciente) ? $paciente['id_plan'] : null,
                            'n_plan' => isset($paciente) ? $paciente['n_plan'] : null,
                            'id_convenio' => isset($paciente) ? $paciente['id_convenio'] : null,
                            'n_convenio' => isset($paciente) ? $paciente['n_convenio'] : null,
    
                            'medicamento_1_item_nro' => isset($json->response[$c]->medicamentos[0]) ?  1 : null,
                            'medicamento_1_cantidad' =>  isset($json->response[$c]->medicamentos[0]) ? $json->response[$c]->medicamentos[0]->cantidad : null,
                            'medicamento_1_diagnostico' => isset($json->response[$c]->medicamentos[0]) ? $json->response[$c]->medicamentos[0]->diagnostico : null,
                            'medicamento_1_nombreDroga' => isset($json->response[$c]->medicamentos[0]) ? $json->response[$c]->medicamentos[0]->nombreDroga : null,
                            'medicamento_1_nombreProducto' => isset($json->response[$c]->medicamentos[0]) ? $json->response[$c]->medicamentos[0]->nombreProducto : null,
                            'medicamento_1_observaciones' => isset($json->response[$c]->medicamentos[0]) ? $json->response[$c]->medicamentos[0]->observaciones : null,
                            'medicamento_1_posologia' => isset($json->response[$c]->medicamentos[0]) ? $json->response[$c]->medicamentos[0]->posologia : null,
                            'medicamento_1_presentacion' => isset($json->response[$c]->medicamentos[0]) ? $json->response[$c]->medicamentos[0]->presentacion : null,
                            'medicamento_1_tratamiento' => isset($json->response[$c]->medicamentos[0]) ? $json->response[$c]->medicamentos[0]->tratamiento : null,
                            'medicamento_1_regNo' => isset($mireceta['medicamentos'][0]) && isset($mireceta['medicamentos'][0]['regNo']) ? $mireceta['medicamentos'][0]['regNo'] : null,
                            'medicamento_1_permite_sustitucion' => isset($mireceta['medicamentos'][0]) && isset($mireceta['medicamentos'][0]['permiteSustitucion']) ? $mireceta['medicamentos'][0]['permiteSustitucion'] : null,
                            'medicamento_1_requiere_duplicado' => isset($mireceta['medicamentos'][0]) && isset($mireceta['medicamentos'][0]['requiereDuplicado']) ? $mireceta['medicamentos'][0]['requiereDuplicado'] : null,
                            
                            'medicamento_2_item_nro' => isset($json->response[$c]->medicamentos[1]) ? 2 : null,
                            'medicamento_2_cantidad' => isset($json->response[$c]->medicamentos[1]) ? $json->response[$c]->medicamentos[1]->cantidad : null,
                            'medicamento_2_diagnostico' => isset($json->response[$c]->medicamentos[1]) ? $json->response[$c]->medicamentos[1]->diagnostico : null,
                            'medicamento_2_nombreDroga' => isset($json->response[$c]->medicamentos[1]) ? $json->response[$c]->medicamentos[1]->nombreDroga : null,
                            'medicamento_2_nombreProducto' => isset($json->response[$c]->medicamentos[1]) ? $json->response[$c]->medicamentos[1]->nombreProducto : null,
                            'medicamento_2_observaciones' => isset($json->response[$c]->medicamentos[1]) ? $json->response[$c]->medicamentos[1]->observaciones : null,
                            'medicamento_2_posologia' => isset($json->response[$c]->medicamentos[1]) ? $json->response[$c]->medicamentos[1]->posologia : null,
                            'medicamento_2_presentacion' => isset($json->response[$c]->medicamentos[1]) ? $json->response[$c]->medicamentos[1]->presentacion : null,
                            'medicamento_2_tratamiento' => isset($json->response[$c]->medicamentos[1]) ? $json->response[$c]->medicamentos[1]->tratamiento : null,
                            'medicamento_2_regNo' => isset($mireceta['medicamentos'][1]) && isset($mireceta['medicamentos'][1]['regNo']) ? $mireceta['medicamentos'][1]['regNo'] : null,
                            'medicamento_2_permite_sustitucion' => isset($mireceta['medicamentos'][1]) && isset($mireceta['medicamentos'][1]['permiteSustitucion']) ? $mireceta['medicamentos'][1]['permiteSustitucion'] : null,
                            'medicamento_2_requiere_duplicado' => isset($mireceta['medicamentos'][1]) && isset($mireceta['medicamentos'][1]['requiereDuplicado']) ? $mireceta['medicamentos'][1]['requiereDuplicado'] : null,
                            
                            'medicamento_3_item_nro' => isset($json->response[$c]->medicamentos[2]) ? 3 : null,
                            'medicamento_3_cantidad' => isset($json->response[$c]->medicamentos[2]) ? $json->response[$c]->medicamentos[2]->cantidad : null,
                            'medicamento_3_diagnostico' => isset($json->response[$c]->medicamentos[2]) ? $json->response[$c]->medicamentos[2]->diagnostico : null,
                            'medicamento_3_nombreDroga' => isset($json->response[$c]->medicamentos[2]) ? $json->response[$c]->medicamentos[2]->nombreDroga : null,
                            'medicamento_3_nombreProducto' => isset($json->response[$c]->medicamentos[2]) ? $json->response[$c]->medicamentos[2]->nombreProducto : null,
                            'medicamento_3_observaciones' => isset($json->response[$c]->medicamentos[2]) ? $json->response[$c]->medicamentos[2]->observaciones : null,
                            'medicamento_3_posologia' => isset($json->response[$c]->medicamentos[2]) ? $json->response[$c]->medicamentos[2]->posologia : null,
                            'medicamento_3_presentacion' => isset($json->response[$c]->medicamentos[2]) ? $json->response[$c]->medicamentos[2]->presentacion : null,
                            'medicamento_3_tratamiento' => isset($json->response[$c]->medicamentos[2]) ? $json->response[$c]->medicamentos[2]->tratamiento : null,
                            'medicamento_3_regNo' => isset($mireceta['medicamentos'][2]) && isset($mireceta['medicamentos'][2]['regNo']) ? $mireceta['medicamentos'][2]['regNo'] : null,
                            'medicamento_3_permite_sustitucion' => isset($mireceta['medicamentos'][2]) && isset($mireceta['medicamentos'][2]['permiteSustitucion']) ? $mireceta['medicamentos'][2]['permiteSustitucion'] : null,
                            'medicamento_3_requiere_duplicado' => isset($mireceta['medicamentos'][2]) && isset($mireceta['medicamentos'][2]['requiereDuplicado']) ? $mireceta['medicamentos'][2]['requiereDuplicado'] : null,
    
                            'medicamento_4_item_nro' => isset($json->response[$c]->medicamentos[3]) ? 4 : null,
                            'medicamento_4_cantidad' => isset($json->response[$c]->medicamentos[3]) ? $json->response[$c]->medicamentos[3]->cantidad : null,
                            'medicamento_4_diagnostico' => isset($json->response[$c]->medicamentos[3]) ? $json->response[$c]->medicamentos[3]->diagnostico : null,
                            'medicamento_4_nombreDroga' => isset($json->response[$c]->medicamentos[3]) ? $json->response[$c]->medicamentos[3]->nombreDroga : null,
                            'medicamento_4_nombreProducto' => isset($json->response[$c]->medicamentos[3]) ? $json->response[$c]->medicamentos[3]->nombreProducto : null,
                            'medicamento_4_observaciones' => isset($json->response[$c]->medicamentos[3]) ? $json->response[$c]->medicamentos[3]->observaciones : null,
                            'medicamento_4_posologia' => isset($json->response[$c]->medicamentos[3]) ? $json->response[$c]->medicamentos[3]->posologia : null,
                            'medicamento_4_presentacion' => isset($json->response[$c]->medicamentos[3]) ? $json->response[$c]->medicamentos[3]->presentacion : null,
                            'medicamento_4_tratamiento' => isset($json->response[$c]->medicamentos[3]) ? $json->response[$c]->medicamentos[3]->tratamiento : null,
                            'medicamento_4_regNo' => isset($mireceta['medicamentos'][3]) && isset($mireceta['medicamentos'][3]['regNo']) ? $mireceta['medicamentos'][3]['regNo'] : null,
                            'medicamento_4_permite_sustitucion' => isset($mireceta['medicamentos'][3]) && isset($mireceta['medicamentos'][3]['permiteSustitucion']) ? $mireceta['medicamentos'][3]['permiteSustitucion'] : null,
                            'medicamento_4_requiere_duplicado' => isset($mireceta['medicamentos'][3]) && isset($mireceta['medicamentos'][3]['requiereDuplicado']) ? $mireceta['medicamentos'][3]['requiereDuplicado'] : null,
    
                            'medicamento_5_item_nro' => isset($json->response[$c]->medicamentos[4]) ? 5 : null,
                            'medicamento_5_cantidad' => isset($json->response[$c]->medicamentos[4]) ? $json->response[$c]->medicamentos[4]->cantidad : null,
                            'medicamento_5_diagnostico' => isset($json->response[$c]->medicamentos[4]) ? $json->response[$c]->medicamentos[4]->diagnostico : null,
                            'medicamento_5_nombreDroga' => isset($json->response[$c]->medicamentos[4]) ? $json->response[$c]->medicamentos[4]->nombreDroga : null,
                            'medicamento_5_nombreProducto' => isset($json->response[$c]->medicamentos[4]) ? $json->response[$c]->medicamentos[4]->nombreProducto : null,
                            'medicamento_5_observaciones' => isset($json->response[$c]->medicamentos[4]) ? $json->response[$c]->medicamentos[4]->observaciones : null,
                            'medicamento_5_posologia' => isset($json->response[$c]->medicamentos[4]) ? $json->response[$c]->medicamentos[4]->posologia : null,
                            'medicamento_5_presentacion' => isset($json->response[$c]->medicamentos[4]) ? $json->response[$c]->medicamentos[4]->presentacion : null,
                            'medicamento_5_tratamiento' => isset($json->response[$c]->medicamentos[4]) ? $json->response[$c]->medicamentos[4]->tratamiento : null,       
                            'medicamento_5_regNo' => isset($mireceta['medicamentos'][4]) && isset($mireceta['medicamentos'][4]['regNo']) ? $mireceta['medicamentos'][4]['regNo'] : null,
                            'medicamento_5_permite_sustitucion' => isset($mireceta['medicamentos'][4]) && isset($mireceta['medicamentos'][4]['permiteSustitucion']) ? $mireceta['medicamentos'][4]['permiteSustitucion'] : null,
                            'medicamento_5_requiere_duplicado' => isset($mireceta['medicamentos'][4]) && isset($mireceta['medicamentos'][4]['requiereDuplicado']) ? $mireceta['medicamentos'][4]['requiereDuplicado'] : null,
                        ];
                        
                        array_push($params_log, $params);
                        array_push($extras['queries'], $this->get_query('validacion', 'sp_HC_Receta_insert', $params));
                        array_push($extras['sps'], ['sp_HC_Receta_insert' => $params]);
                        $ret_receta = $this->ejecutar_sp_directo('validacion', 'sp_HC_Receta_insert', $params);
                        array_push($extras['responses'], ['sp_HC_Receta_insert' => $ret_receta]);
    
                        if(isset($ret_receta['error'])){
                            $status = 'warning';
                            $code = -7;
                            $message = 'La receta se generó y está disponible en la farmacia, pero NO fue posible guardar la receta en el sistema. ';
                            // es el primer error si la receta se realiza
                            $errors = $ret_receta['error'];
                        }else{
                            $recetas_guardadas_ok = true; 
                            array_push($extras['responses'], ['recetas_ok' => $recetas_ok]);
                            array_push($extras['responses'], ['recetas_guardadas_ok' => $recetas_guardadas_ok]);
                            array_push($response_sp_recetas, $ret_receta);
    
                            array_push($data['ids_recetas'], $ret_receta[0]->id_receta);
                            array_push($data['response_sp'], $ret_receta);
                            $status = 'ok';
                            $code = 1;
                            $message = 'Recetas generadas y guardadas con éxito. ';
                            $count = sizeof($json->recetas);
                            
                            // $data['response_sp'] = $ret_receta;
                            // $ids_recetas = [];
                            // $id_receta = json_decode(json_encode($response_sp_recetas[0]))[0]->id_receta;
                            // for($i = 0; $i < sizeof($response_sp_recetas); $i++){
                            //     if($response_sp_recetas[$i][0]->agregado == 1){
                            //         array_push($ids_recetas, $response_sp_recetas[$i][0]->id_receta);
                            //     }
                            // }
                            
                        }
                        
                        
                        $c++;
                    }
                }else{
                    $recetas_ok = false;
                    // es el primer error si la receta falla
                    $errors = [$json->error.' - '.$json->mensaje, ' Error al generar receta '];
                    $message = 'No fue posible generar receta. '.$json->mensaje;
                    $status = 'fail';
                    $count = 0;
                    $code = -8;
                }
            }
            
            // si se realizó la receta y se debe informar a SIAF (en sanos)
            // consultamos la api de SIAF
            // informar siaf viene en true cuando algún medicamento requiere aprobación.
            $data['validar_siaf'] = env('VALIDAR_SIAF');
            $data['informar_siaf'] = $informar_siaf ? 'SI' : 'NO';
            $data['comprobar_siempre_siaf'] = env('COMPROBAR_SIEMPRE_SIAF');
            $data['response_siaf'] = [
                'codAutorizacion' => 0,
                'error' => 0,
                'estado' => "AUTORIZADO",
                'estado_id' => "A",
                'id' => 0,
                'mensaje' => null,
                'numero' => 0
            ];
            if($recetas_ok && env('VALIDAR_SIAF') == 'SI' && ($informar_siaf || env('COMPROBAR_SIEMPRE_SIAF') == 'SI')) {
                try {
                    $data['response_login_siaf'] = 'Token proporcionado por el front válido';
                    $data['response_siaf'] = [];
                    $info_curl_siaf = [
                        'url' => '', 
                        'campos' => [],
                        'respuesta' => [],
                        'campos_postdata' => [],
                        'respuesta_postdata' => []
                    ];
                    if($token_siaf == null || $token_siaf == ''){
                        $fields = array(
                            'username' => env('USER_SIAF'),
                            'password' => env('PASSWORD_SIAF')
                        );
                        $fields_string = http_build_query($fields);
                        $ch = curl_init();
                        curl_setopt_array($ch, array(
                            CURLOPT_URL => env('LOGIN_SIAF'),
                            CURLINFO_HEADER_OUT => true,
                            CURLOPT_POST => 1,
                            CURLOPT_HTTPHEADER => array(
                                "cache-control: no-cache",
                                'Content-Type: application/x-www-form-urlencoded'
                            ),
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_POSTFIELDS => $fields_string,
                            CURLOPT_SSL_VERIFYHOST => 0,
                        ));
                        $response_login_siaf = curl_exec($ch);
                        $info_curl_login_siaf = [
                            'url' => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
                            'campos' => json_decode(json_encode(['username' => env('USER_SIAF')]), false),
                            'respuesta' => json_decode($response_login_siaf)
                        ];
                        $extras['info_curl_login_siaf'] = $info_curl_login_siaf;
                        curl_close($ch);
                        $json_login_siaf = json_decode($response_login_siaf);
                        array_push($extras['responses'], ['login_siaf' => $json_login_siaf]);
                        if(gettype($json_login_siaf) == 'string') {
                            $token_siaf = $json_login_siaf;
                            $data['response_login_siaf'] = 'login SIAF correcto, token obtenido';
                        }else{
                            $message = 'Receta realizada pero NO AUTORIZADA. El paciente debe llevar la receta para autorizar en delegación. ';
                            $status = 'fail';
                            $code = -3;   // metodo generar_receta
                            $data['response_login_siaf'] = $json_login_siaf;
                            array_push($errors, 'ERROR SIAF. Error de Login. ');
                            Log::channel('siaf')->error('ERROR SIAF Login. Code -3 '.json_decode(json_encode($json_login_siaf), false)->mensaje.' id_receta: '.$data['ids_recetas'][0] ?? null);
                        }
                    }
                    if($token_siaf == null || $token_siaf == '') {
                        $message = 'Receta realizada pero NO AUTORIZADA. El paciente debe llevar la receta para autorizar en delegación. ';
                        $status = 'fail';
                        $code = -4;  // metodo generar_receta
                        $data['response_login_siaf'] = $token_siaf;
                        array_push($errors, 'ERROR SIAF. No se porporcionó token válido. ');
                        Log::channel('siaf')->error('ERROR SIAF Login. Code -4. No se proporcionó token válido.'.' id_receta: '.$data['ids_recetas'][0] ?? null);
                    }else{
                        $receta = json_decode(json_encode($mireceta), false);
                        $items = [];
                        foreach ($receta->medicamentos as $key => $value) {
                           $item = [
                                'item' => 1,
                                'nomenclador_codigo' => $value->regNo,
                                'cantidad' => $value->cantidad
                            ];
                            array_push($items, json_decode(json_encode($item), false));
                            $item = [];
                        }
                        $fields = array(
                            'convenio_id' => $id_convenio_siaf,
                            'beneficiario_numero' => $receta->paciente->cobertura->numero,
                            'fecha' => Carbon::now()->format('d/m/Y'), // '05/11/2024',
                            'diagnostico' => $receta->diagnostico,
                            'prescriptor_matricula' => $receta->medico->matricula->numero,
                            'prescriptor' => $receta->medico->apellido.', '.$receta->medico->nombre,
                            'observaciones' => '',
                            'referencia' => $json->recetas[0]->idReceta, // $id_receta,
                            'cie10' => $cie10,
                            'items' => $items
                        );
                        // $fields_string = http_build_query($fields);
                        $ch = curl_init();
                        curl_setopt_array($ch, array(
                            CURLOPT_URL => env('REGISTRO_PRESCRIPCIONES_SIAF'),
                            CURLINFO_HEADER_OUT => true,
                            CURLOPT_POST => 1,
                            CURLOPT_HTTPHEADER => array(
                                'Content-Type: application/json',
                                'Authorization: Bearer '.$token_siaf
                            ),
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_POSTFIELDS => json_encode($fields),
                            CURLOPT_SSL_VERIFYHOST => 0,
                        ));
                        $info_curl_siaf['url'] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                        $response_siaf = curl_exec($ch);
                        array_push($info_curl_siaf['campos'], json_decode(json_encode($fields), false));
                        array_push($info_curl_siaf['respuesta'], json_decode($response_siaf));
                        $extras['info_curl_siaf'] = $info_curl_siaf;
                        curl_close($ch);
                        $json_siaf = json_decode($response_siaf);
                        array_push($extras['responses'], ['siaf' => $json_siaf]);

                        $data['token_siaf'] = $token_siaf;
                        array_push($data['response_siaf'], json_decode(json_encode($json_siaf)));

                        if($json_siaf != null && (json_decode(json_encode($json_siaf), false)->error != 0)){
                            $message = $message.'Receta realizada pero NO AUTORIZADA. El paciente debe comunicarse con la delegación. ';
                            $status = 'warning';
                            $code = -5;  // metodo generar_receta
                            array_push($errors, 'ERROR SIAF. Error de Autorización de receta. '.json_decode(json_encode($json_siaf), false)->mensaje.' id_receta: '.$data['ids_recetas'][0] ?? null);
                            Log::channel('siaf')->error('ERROR SIAF SolicitudFarmacia. Code -5 '.json_decode(json_encode($json_siaf), false)->mensaje.' id_receta: '.$data['ids_recetas'][0] ?? null);
                        }else{
                            $message = $message.' Autorización de receta solicitada. El paciente debe comunicarse con la delegación. ';
                            $code = 2;  // metodo generar_receta
                            $status = 'ok';
                        }
                        $fecha = Carbon::now();
                        if($receta->recetasPostadatas->cantidad > 0){
                            for($i = 1; $i <= $receta->recetasPostadatas->cantidad; $i++){
                                $items = [];
                                $dias = $receta->recetasPostadatas->diasAPosdatar;
                                $fecha = Carbon::parse($fecha)->addDays($dias)->format('d/m/Y');
                                foreach ($receta->medicamentos as $key => $value) {
                                    $item = [
                                        'item' => 1,
                                        'nomenclador_codigo' => $value->regNo,
                                        'cantidad' => $value->cantidad
                                    ];
                                    array_push($items, json_decode(json_encode($item), false));
                                    $item = [];
                                }
                                $fields_postdata = array(
                                    'convenio_id' => $id_convenio_siaf,
                                    'beneficiario_numero' => $receta->paciente->cobertura->numero,
                                    'fecha' => $fecha,
                                    'diagnostico' => $receta->diagnostico,
                                    'prescriptor_matricula' => $receta->medico->matricula->numero,
                                    'prescriptor' => $receta->medico->apellido.', '.$receta->medico->nombre,
                                    'observaciones' => '',
                                    'referencia' => $json->recetas[$i]->idReceta, // $ids_recetas[$i],
                                    'cie10' => $cie10,
                                    'items' => $items
                                );
                                // $fields_postdata_string = http_build_query($fields);
                                $ch = curl_init();
                                curl_setopt_array($ch, array(
                                    CURLOPT_URL => env('REGISTRO_PRESCRIPCIONES_SIAF'),
                                    CURLINFO_HEADER_OUT => true,
                                    CURLOPT_POST => 1,
                                    CURLOPT_HTTPHEADER => array(
                                        'Content-Type: application/json',
                                        'Authorization: Bearer '.$token_siaf
                                    ),
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_FOLLOWLOCATION => true,
                                    CURLOPT_POSTFIELDS => json_encode($fields_postdata),
                                    CURLOPT_SSL_VERIFYHOST => 0,
                                ));
                                $response_siaf = curl_exec($ch);
                                // return $info_curl_siaf['campos_postdata'];
                                array_push($info_curl_siaf['campos_postdata'], json_decode(json_encode($fields_postdata), false));
                                array_push($info_curl_siaf['respuesta_postdata'], json_decode($response_siaf));
                                $extras['info_curl_siaf'] = $info_curl_siaf;
                                curl_close($ch);
                                $json_siaf = json_decode($response_siaf);
                                array_push($extras['responses'], ['siaf' => $json_siaf]);
                                array_push($data['response_siaf'], json_decode(json_encode($json_siaf)));
    
                                if(json_decode(json_encode($json_siaf), false)->error != 0){
                                    if (substr($message, -strlen('Receta realizada pero NO AUTORIZADA. El paciente debe comunicarse con la delegación. ')) !== 'Receta realizada pero NO AUTORIZADA. El paciente debe comunicarse con la delegación. ') {
                                        // La última frase es diferente
                                        $message = $message.'Receta realizada pero NO AUTORIZADA. El paciente debe comunicarse con la delegación. ';
                                    }
                                    $status = 'warning';
                                    $code = -6; // metodo generar_receta
                                    array_push($errors, 'ERROR SIAF. Error de Autorización de receta. '.json_decode(json_encode($json_siaf), false)->mensaje.' id_receta: '.$data['ids_recetas'][$i]);
                                    Log::channel('siaf')->error('ERROR SIAF SolicitudFarmacia. Code -6 '.json_decode(json_encode($json_siaf), false)->mensaje.' id_receta: '.$data['ids_recetas'][$i]);
                                }else{
                                    $message = $message.' Autorización de receta solicitada. El paciente debe comunicarse con la delegación. ';
                                    $code = 5;  // metodo generar_receta
                                    $status = 'ok';
                                }
                            }
                        }
                    }
    
                } catch (\Throwable $th) {
                    $message = 'Receta realizada pero NO AUTORIZADA. El paciente debe llevar la receta para autorizar en delegación. ';
                    $status = 'warning';
                    array_push($errors, 'ERROR SIAF. Line: '.$th->getLine().' Error: '.$th->getMessage());
                    $code = -2;  // metodo generar_receta
                    Log::channel('siaf')->error('ERROR SIAF. Code -2 '.$th->getLine().' Error: '.$th->getMessage().' id_receta: '.$data['ids_recetas'][0] ?? null);
                    return response()->json([
                        'status' => $status,
                        'count' => $count,
                        'errors' => $errors,
                        'message' => $message,
                        'line' => null,
                        'code' => $code,
                        'data' => $data,
                        'params' => $this->params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ]);
                } finally {
                    if(is_resource($ch)){
                        curl_close( $ch );
                    }
                }
            }
            if(sizeof($errors) == 0){
                // si no hay errores enviamos un array con un string vacio para evitar errores en el front
                $errors =  [' '];
            }
            Log::channel('recetas')->info('# CODE: '.$code.' # STATUS: '.$status.' # ERRORS: '.json_encode($errors).' # MESSAGE: '.$message.' # PARAMS: '.json_encode($params_log).' # RECETAS: '.json_encode($json));
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => $code,
                'data' => $data,
                'params' => $this->params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        } catch (\Throwable $th) {
            $status = 'fail';
            array_push($errors, 'Line: '.$th->getLine().' Error: '.$th->getMessage());
            $code = -1;  // metodo generar_receta
            Log::channel('recetas')->info('# CODE: '.$code.' # STATUS: '.$status.' # ERRORS: '.json_encode($errors).' # LINE: '.$th->getLine().' # MESSAGE: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => null,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $code,
                'data' => null,
                'params' => null,
                'extras' => $extras,
                'logged_user' => null,
            ]);
        } 
    }
   
}


