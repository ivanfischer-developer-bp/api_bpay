<?php

namespace App\Http\Controllers\Internos;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
// use Illuminate\Support\Facades\File as FacadeFile;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use App\Services\Afiliados\PadronExternoOsceara;

use File;
use Storage;
use Carbon\Carbon;

class ConsultasExternasController extends ConexionSpController
{
    
    /**
     * Consulta en SISA del ministario de salud si un médico está dado de alta como profesional y retorna los datos registrados
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function consultar_sisa(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/auth/consultar-sisa',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => [],
            'url' => ''
        ];
        $status = '';
        $code = 0;
        $line = null;
        $message = '';
        $errors = [];
        $count = 0;
        $logged_user = null;
        $data = null;
        $params = [];

        $nro_doc = request('nro_doc');
        $params = [
            'nro_doc' => $nro_doc
        ];

        try {
            $url = env('SISA_URL_PROFESIONALES').'?usuario='.env('SISA_USER').'&clave='.env('SISA_PASSWORD').'&nrodoc='.$nro_doc;
            $extras['url'] = $url;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Laravel/8.0)');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,*/*;q=0.8',
                'Accept-Language: es-ES,es;q=0.8',
                'Cache-Control: no-cache'
            ));
            
            $resp = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);
            $extras['curl_debug'] = [
                'http_code' => $http_code,
                'curl_error' => $curl_error,
                'curl_errno' => $curl_errno,
                'response_length' => strlen($resp),
                'response_type' => gettype($resp)
            ];
            curl_close($ch);
            
            // Verificar errores de cURL
            if ($curl_errno !== 0) {
                array_push($errors, "Error cURL #{$curl_errno}: {$curl_error}");
                $message = 'Error de conectividad con SISA';
                $code = -4;
                $count = 0;
                $status = 'fail';
                $data = null;
            } else if ($http_code !== 200) {
                array_push($errors, "HTTP Error: {$http_code}");
                $message = "Error HTTP {$http_code} al consultar SISA";
                $code = -5;
                $count = 0;
                $status = 'fail';
                $data = $resp;
            } else if($resp != null && $resp !== false){
                $xml = simplexml_load_string($resp);
                $json = json_encode($xml);
                $response = json_decode($json ,TRUE);
                $data = $response;
                if($response['resultado'] == 'OK'){
                    $message = 'Profesional Registrado';
                    $code = 1;
                    $count = 1;
                    $status = 'ok';
                }else{
                    $message = 'Profesional NO encontrado';
                    $code = -2;
                    $count = 0;
                    $status = 'fail';
                }
            }else{
                array_push($errors, 'cURL retornó NULL o FALSE');
                $message = 'SISA retornó respuesta vacía o NULL';
                $code = -3;
                $count = 0;
                $status = 'fail';
                $data = [
                    'curl_response' => $resp,
                    'curl_error' => $curl_error,
                    'http_code' => $http_code
                ];
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
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th.getLine().'Error: '.$th->getMessage());
            $message = 'Fallo en backend';
            $code = -1;
            $count = 0;
            $status = 'fail';
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => $th.getLine(),
                'code' => $code,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]); 
        } finally {
            if(is_resource($ch)){
                curl_close( $ch );
            }
        }
    }

    /**
     * Obtiene un listado de medicamentos desde https://cnpm.msal.gov.ar/api/vademecum
     * 
     * @return \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function consultar_vademecum(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/consultas-externas/consultar-vademecum',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'responses' => [],
            'sps' => [],
            'queries' => []
        ];

        try{
            date_default_timezone_set('America/Argentina/Cordoba');
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            // variables de respuesta
            $status = 'no status';
            $message = '';
            $count = 0;
            $data = null;
            $errors = [];
            $line = null;
            $code = -1;
            
            try {
                $ch = curl_init();
                $searchdata = request('searchdata');
                $params = [
                    'searchdata' => $searchdata,
                ];
                $url = env('VADEMECUM_URL');
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'accept: application/json',
                    'content-type: application/json',
                ));
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_USERAGENT, 'api_bpay/1.0');
                $caPath = '/etc/ssl/certs/ca-certificates.crt';
                if(is_readable($caPath)){
                    curl_setopt($ch, CURLOPT_CAINFO, $caPath);
                }
                $sslVerify = env('ALFABETA_SSL_VERIFY', true);
                if(!$sslVerify){
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                }
                $payload = json_encode($params);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                $response = curl_exec($ch);
                
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                array_push($extras['responses'], [
                    'obtener_precio_medicamento' => [
                        'url' => $url,
                        'respuesta' => json_decode($response)
                        ]
                    ]
                );
                if($http_code != 200){
                    $message = 'Error HTTP '.$http_code.' en la consulta al endpoint.';
                    $status = 'fail';
                    $data = $response;
                    $count = 0;
                    $code = -4;
                }else{
                    $json = json_decode($response, true);
                    if(isset($json) && is_array($json)){
                        $data = $json;
                        $count = count($data);
                        $message = 'Listado de medicamentos obtenido.';
                        $status = 'ok';
                        $code = 1;
                    }else{
                        $data = [];
                        $count = 0;
                        $message = 'No se encontraron medicamentos para el criterio de búsqueda.';
                        $status = 'empty';
                        $code = -3;
                    }
                }

            } catch (\Throwable $th) {
                array_push($errors, 'error en el proceso');
                $message = $th->getMessage();
                $line = $th->getLine();
                $code = -2;
            } finally {
                if(is_resource($ch)){
                    curl_close( $ch );
                }
            }
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => $line,
                'code' => $code,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]); 
        } catch (\Throwable $th) {
            array_push($errors, 'Error capturado en backend');
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => null,
                'extras' => $extras,
                'logged_user' => null,
            ]);
        }
    }

    /**
     * Consulta el padrón antiguo de osceara
     * 
     * @return \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function consultar_padron_externo(Request $request)
    {   
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/consultas-externas/consultar-padron-externo',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];
        $status = 'fail';
        $message = '';
        $count = -1;
        $code = -1;
        $line = null;
        $data = [
            'estado_afiliado' => null,
            'actualizado' => false,
            'info_padron' => null
        ];
        $errors = [];
        $params = [];
    
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        try{
            if($user->hasPermissionTo('buscar afiliado')){
                $params = [
                    'nro_doc' => request('nro_doc'),
                    'id_persona' => request('id_persona'),
                    'actualizar_afiliado' => request('actualizar_afiliado') == 'SI' ? true : false
                ];
                if(env('AMBIENTE_PADRON_EXTERNO') == 'osceara'){
                    $actualizar = false;
                    $padron = new PadronExternoOsceara();
                    $resp = $padron->consultar($params['nro_doc']);
                    array_push($extras['responses'], [
                        'padron_externo_osceara' => $resp
                    ]);
                    $info = $resp['info'] ?? 'No se obtuvo información del padrón externo';
                    $estado = $resp['estado'] ?? null;
                    $data['info_padron'] = $info;
                    $data['estado_afiliado'] = $estado;
                    if($resp['estado'] != null){
                        $estado = $resp['estado'];
                        $data['estado_afiliado'] = $estado;

                        if(env('ACTUALIZAR_PADRON_AL_BUSCAR') == 'SI'){
                            $actualizar = true;
                        }else{
                            if($params['actualizar_afiliado']){
                                $actualizar = true;
                            }
                        }

                        if($actualizar && $estado != null){
                            $sp = 'sp_afiliado_estado_update';
                            $db = 'afiliacion';
                            $params_sp = [
                                'id_persona' => $params['id_persona'],
                                'id_usuario' => $id_usuario,
                                'n_estado' => $estado
                            ];
                            array_push($extras['sps'], [$sp => $params_sp]);
                            array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
                            $response = $this->ejecutar_sp_directo($db, $sp, $params_sp);
                            array_push($extras['responses'], [$sp => $response]);
                            if(isset($response['error']) && $response['error'] != null){
                                array_push($errors, 'Error al actualizar el estado del afiliado en la base de datos local: '.$response['error']);
                                $message = 'Estado NO actualizado. ';
                            }else{
                                if($response[0]->id == 1){
                                    $message = 'Estado actualizado correctamente. ';
                                    $data['actualizado'] = true;
                                }
                            }
                        }
                        $status = 'ok';
                        $message = $message.'Consulta realizada en padrón externo de Osceara. ';
                        $count = 1;
                        $code = 1;
                    }else{
                        $status = 'empty';
                        $message = 'Consulta realizada en padrón externo de Osceara pero no se obtuvo un estado válido. '.$info;
                        $count = 0;
                        $code = -3;
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para BUSCAR AFILIADO';
                $count  = 0;
                $data = null;
                $error = 'Error de permisos';
                $code = -2;
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
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]); 
        } catch (\Throwable $th) {
            array_push($errors, 'Error de Backend');
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => null,
            ]);
        }

    }








    // viejas consultas a las apis de Silvio Quadri

    /**
     * Consulta el boletín protectivo en la api de silvio quadri
     */
    public function boletin_protectivo(Request $request)
    {
        try{ 
            $extras = [
                'api_software_version' => config('site.software_version'),
                'ambiente' => config('site.ambiente'),
                'url' => '/int/consultas-externas/boletin-protectivo',
                'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
                'function' => __FUNCTION__,
                'sps' => [],
                'responses' => [],
                'queries' => []
            ];
            $status = 'fail';
            $message = '';
            $count = -1;
            $code = -1;
            $line = null;
            $data = null;
            $errors = [];
            $this->params = [];
        
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            
            if($user->hasPermissionTo('buscar afiliado')){

                $n_afiliado = request('nro_afiliado');
                $tipo_documento = request('tipo_doc');
                $documento = request('nro_doc');
                
                $this->params = [
                    'nro_afiliado' => $n_afiliado,
                    'tipo_doc' => $tipo_documento,
                    'nro_doc' => $documento
                ];
                
                $contra =  intval(substr(strval(request('nro_afiliado')), 0, -2));
                $params = [
                    'n_afiliado' => $n_afiliado,
                    'contra' => $contra
                ];
                $json1 = null;
                $error1 = null;
                if(env('CONSULTA_BOLETIN') == 'api'){
                    $url_boletin = env('API_BOLETIN_GRUPO');
                    try {
                        $ch = curl_init($url_boletin);
                        $fields = array(
                            'prepaga' => 1,
                            'contra' => $contra
                        );
                        
                        $fields_string = http_build_query($fields);
                        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
                        curl_setopt( $ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1' );
                        curl_setopt($ch, CURLOPT_POST, TRUE);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-AUTH-USER: integrisys", "X-AUTH-TOKEN: 2n6baXtpKFGy6TVB"]);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
                        $response = curl_exec( $ch );
                        array_push($extras['responses'], [
                            'consulta_boletin' => [
                                'url' => $url_boletin,
                                'respuesta' => json_decode($response)
                                ]
                            ]
                        );
                        // return $response;
                        if(!$response){
                            array_push($errors, 'Fallo cURL: '.curl_error( $ch ), curl_errno( $ch ));
                            throw new Exception('Fallo cURL: '.curl_error( $ch ), curl_errno( $ch ));
                        }
                        $httpReturnCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
                        if($httpReturnCode !== 200){
                            $json = null;
                            $response = html_entity_decode($response);
                            // error que arroja el web service consultado
                            $error = 'Ha ocurrido un problema en los Servicios en Línea. El error ha sido reportado.'; // Si el problema persiste, envíe un e-mail a webmaster@cajaabogados.org.ar'; 
                            array_push($errors, $error);
                            $data = null;
                            $code = -5;
                        }else{
                            $json = json_decode($response);
                            // return $json;
                            $error = null;
                        }
                        // consultamos si está dado de baja en AFILMED
                        $json1 = null;
                        $error1 = null;
                        $url_casa_afiliado = env('API_CASA_AFILIADO');
                        try {
                            $ch = curl_init($url_casa_afiliado);
                            $fields = array(
                                'docu_tipo' => $tipo_documento,
                                'docu_nro' => $documento
                            );
                            
                            $fields_string = http_build_query($fields);
                            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
                            curl_setopt( $ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1' );
                            curl_setopt($ch, CURLOPT_POST, TRUE);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-AUTH-USER: integrisys", "X-AUTH-TOKEN: 2n6baXtpKFGy6TVB"]);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
                            $response = curl_exec( $ch );
                            array_push($extras['responses'], [
                                'consulta_baja_afilmed' => [
                                    'url' => $url_casa_afiliado,
                                    'respuesta' => json_decode($response)
                                    ]
                                ]
                            );
                            // return $response;
                            if(!$response){
                                $error1 = 'Fallo cURL: '.curl_error( $ch ).'-'. curl_errno( $ch );
                                array_push($errors, $error1);
                            }
                            $httpReturnCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
                            if($httpReturnCode !== 200){
                                $json1 = null;
                                $response = html_entity_decode($response);
                                // error que arroja el web service consultado
                                $error1 = 'Error consultando estado del afiliado en AFILMED'; 
                                array_push($errors, $error1);
                            }else{
                                $json1 = json_decode($response);
                                if(property_exists($json1, 'status')){
                                    if($json1->status == 'ERROR'){
                                        $error1 = $json->msg;
                                        $error = $json->msg;
                                        array_push($errors, $error);
                                        throw new Exception($json1->msg);
                                        $status = 'fail';
                                        $code -7;
                                        $count = 0;
                                    }
                                }else{
                                    $error1 = null;
                                }
                            }
                        } catch (\Exception $e) {
                            $error1 = 'Line: '.$e->getLine().' Message: '.$e->getMessage();
                            $status = 'fail';
                            $code = -6;
                            $count = 1;
                            $message = 'Consulta de Boletín por API fallida';
                            array_push($errors, $error1);
                            $data = [
                                'boletin' => $json,
                                'baja_afilmed' => $json1 != null && property_exists($json1, 'data') ? $json1->data->baja_fecha : null,
                                'error_baja_afilmed' => $error1,
                                'response' => json_decode($response),
                                'consulta' => env('CONSULTA_BOLETIN'),
                                'contra' => $contra,
                                'httpReturnCode' => $httpReturnCode
                            ];
                        } finally {
                            if(is_resource($ch)){
                                curl_close( $ch );
                            }
                        }
                        if($error == null){
                            $code = 1;
                            $count = 1;
                            $status = 'ok';
                            $message = 'Boletín Protectivo obtenido.';
                            $data = [
                                'baja_afilmed' => $json1 != null && property_exists($json1, 'data') ? $json1->data->baja_fecha : null,
                                'boletin' => $json,
                                'consulta' => env('CONSULTA_BOLETIN'),
                                'contra' => $contra,
                                'error_baja_afilmed' => $error1,
                                'httpReturnCode' => $httpReturnCode,
                                'response' => json_decode($response)
                            ];
                        }else{
                            $code = -4;
                            $status = 'fail';
                            $message = 'No hay boletín que mostrar.';
                            array_push($errors, $error);
                            $data = [
                                'baja_afilmed' => $json1 != null && property_exists($json1, 'data') ? $json1->data->baja_fecha : null,
                                'boletin' => $json,
                                'consulta' => env('CONSULTA_BOLETIN'),
                                'contra' => $contra,
                                'error_baja_afilmed' => $error1,
                                'httpReturnCode' => $httpReturnCode,
                                'response' => json_decode($response)
                            ];
                        }
                    } catch (\Exception $e) {
                        $status = 'fail';
                        $code = -6;
                        $count = 1;
                        $message = 'Consulta de Boletín por procedimiento almacenado';
                        array_push($errors, 'Line: '.$e->getLine().' Message: '.$e->getMessage());
                        $data = [
                            'baja_afilmed' => $json1 != null && property_exists($json1, 'data') ? $json1->data->baja_fecha : null,
                            'boletin' => $json,
                            'consulta' => env('CONSULTA_BOLETIN'),
                            'contra' => $contra,
                            'error_baja_afilmed' => $error1,
                            'httpReturnCode' => $httpReturnCode,
                            'response' => json_decode($response)
                        ];
                        // return response()->json([
                        //     'code' =>-2,
                        //     'data' => null,
                        //     'baja_afilmed' => $json1 != null && property_exists($json1, 'data') ? $json1->data->baja_fecha : null,
                        //     'error_baja_afilmed' => $error1,
                        //     'error' => 'Line: '.$e->getLine().' - CODE: '.$e->getCode().' Message: '.$e->getMessage()
                        // ]);
                    } finally {
                        if(is_resource($ch)){
                            curl_close( $ch );
                        }
                    }
                }else{
                    $boletin = $this->ejecutar_sp_directo('afiliacion', 'sp_boletin_protectivo_select', $params);
                    // dd('boletin', $boletin);
                    if(!empty($boletin)){
                        $boletin[0]->periodo = $boletin[0]->periodo !== null ? Carbon::parse($boletin[0]->periodo)->format('d/m/Y') : null;
                        $boletin[0]->regularizacion_fecha = $boletin[0]->regularizacion_fecha !== null ? Carbon::parse($boletin[0]->regularizacion_fecha)->format('d/m/Y') : null;
                        $boletin[0]->vigencia_desde = $boletin[0]->vigencia_desde !== null ? Carbon::parse($boletin[0]->vigencia_desde)->format('d/m/Y') : null;
                        $boletin[0]->vigencia_hasta = $boletin[0]->vigencia_hasta !== null ? Carbon::parse($boletin[0]->vigencia_hasta)->format('d/m/Y') : null;

                        $status = 'ok';
                        $code = 2;
                        $count = 1;
                        $message = 'Consulta de Boletín por procedimiento almacenado';
                        $data = [
                            'boletin' => $boletin[0],
                            'baja_afilmed' => 'No consultada',
                            'error_baja_afilmed' => null,
                            'response' => json_decode($boletin),
                            'consulta' => env('CONSULTA_BOLETIN'),
                            'contra' => null,
                            'httpReturnCode' => 200
                        ];
                    }else{
                        $code = -3;
                        $count = 0;
                        $status = 'empty';
                        $message =  'No hay boletín que mostrar.';
                        $data = [
                            'boletin' => null,
                            'baja_afilmed' => 'No consultada',
                            'error_baja_afilmed' => null,
                            'response' => json_decode($boletin),
                            'consulta' => env('CONSULTA_BOLETIN'),
                            'contra' => null,
                            'httpReturnCode' => 200
                        ];
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para BUSCAR AFILIADO';
                $count  = 0;
                $data = null;
                $error = 'Error de permisos';
                $code = -2;
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
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]); 
        } catch (\Throwable $th) {
            array_push($errors, 'Error de Backend');
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $this->params,
                'extras' => $extras,
                'logged_user' => null,
            ]);
        }
    }

    /**
     * Consume la api de cobranzas de afilmed
     */
    public function cuenta_corriente_afilmed(Request $request)
    {
        try{ 
            $extras = [
                'api_software_version' => config('site.software_version'),
                'ambiente' => config('site.ambiente'),
                'url' => '/int/consultas-externas/cuenta_corriente_afilmed',
                'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
                'function' => __FUNCTION__,
                'sps' => [],
                'responses' => [],
                'queries' => []
            ];
            $status = 'fail';
            $message = '';
            $count = -1;
            $code = -1;
            $line = null;
            $data = null;
            $errors = [];
            $this->params = [];
        
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            
            if($user->hasPermissionTo('buscar afiliado')){
                $n_afiliado = request('nro_afiliado');
                $this->params = [
                    'nro_afiliado' => $n_afiliado
                ];
                $contra =  intval(substr(strval(request('nro_afiliado')), 0, -2));
                $url_casa_cuenta = env('API_CASA_CUENTA');
            
                $ch = curl_init($url_casa_cuenta);
                $fields = array(
                    'prepaga' => 1,
                    'contra' => $contra
                );
                $fields_string = http_build_query($fields);
                
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
                curl_setopt( $ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1' );
                curl_setopt( $ch, CURLOPT_POST, TRUE );
                curl_setopt( $ch, CURLOPT_HTTPHEADER, [
                    "X-AUTH-USER: integrisys",
					"X-AUTH-TOKEN: ".env('API_CASA_TOKEN'),
					"Content-Type: application/x-www-form-urlencoded"
                ] );
                curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string );
                $response = curl_exec( $ch );
                $httpReturnCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
                array_push($extras['responses'], [
                    'consulta_cuenta_corriente_afilmed' => [
                        'url' => $url_casa_cuenta,
                        'respuesta' => json_decode($response)
                        ]
                    ]
                );
                // return $response;
                $json = json_decode($response);
                if($json == null){
                    $data = null;
                    $status = 'fail';
                    $code = -3;
                    $count = null;
                    $message = 'Error de API externa';
                    // error que arroja el web service consultado
                    $error = 'Error consultando cuenta corriente del afiliado en AFILMED'; 
                    array_push($errors, $error);
                }else{
                    if(!$response){
                        $error = 'Fallo cURL: '.curl_error( $ch ).'-'. curl_errno( $ch );
                        array_push($errors, $error);
                        $status = 'fail';
                        $code = -4;
                        $count = null;
                        $message = 'Se produjo un error';
                    }else{
                        if(property_exists($json, 'status') && $json->status == 'OK'){
                            $data = $json->data;
                            $status = 'ok';
                            $code = 1;
                            $count = null;
                            $message = 'Listado obtenido';
                        }else{
                            $data = null;
                            $status = 'fail';
                            $code = -5;
                            $count = null;
                            $message = 'Sin Datos';
                            // error que arroja el web service consultado
                            $error = 'Error consultando cuenta corriente del afiliado en AFILMED'; 
                            array_push($errors, $error);
                        }
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para BUSCAR AFILIADO';
                $count  = 0;
                $data = null;
                $error = 'Error de permisos';
                $code = -2;
            }
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
            array_push($errors, 'Error de Backend');
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $this->params,
                'extras' => $extras,
                'logged_user' => null,
            ]);
        }
    }

    /**
     * Consulta la API de Cobranzas de Afilmed
     */
    public function cobranzas_afilmed(Request $request)
    {
        try{ 
            $extras = [
                'api_software_version' => config('site.software_version'),
                'ambiente' => config('site.ambiente'),
                'url' => '/int/consultas-externas/cobranzas_afilmed',
                'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
                'function' => __FUNCTION__,
                'sps' => [],
                'responses' => [],
                'queries' => []
            ];
            $status = 'fail';
            $message = '';
            $count = -1;
            $code = -1;
            $line = null;
            $data = null;
            $errors = [];
            $this->params = [];
        
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            
            if($user->hasPermissionTo('buscar afiliado')){

                $url = env('WEBSERVICE_CAJA_ABOGADOS').'?tkn=A39188ASXZ1W';
                $nro_doc = request('nro_doc');
                $fecha = request('fecha');

                if($nro_doc !== null){
                    $url = $url.'&nrodoc='.$nro_doc;
                    $this->params = [
                        'nro_doc' => $nro_doc
                    ];
                }

                if($fecha !== null){
                    $url = $url.'&fecha='.$fecha;
                    $this->params = [
                        'fecha' => $fecha
                    ];
                }

                $ch = curl_init($url);
                
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
                curl_setopt( $ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1' );
                $response = curl_exec( $ch );
                $httpReturnCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
                array_push($extras['responses'], [
                    'consulta_cobranzas_afilmed' => [
                        'url' => $url,
                        'respuesta' => json_decode($response)
                        ]
                    ]
                );

                if(!$response){
                    $error = 'Fallo cURL: '.curl_error( $ch ).'-'. curl_errno( $ch );
                    array_push($errors, $error);
                    $status = 'fail';
                    $code = -3;
                    $count = null;
                    $message = 'Se produjo un error en la API externa consultada';
                }else{
                    $json = json_decode($response);
                    if($json == null){
                        $data = null;
                        $status = 'fail';
                        $code = -4;
                        $count = null;
                        $message = 'Error consultando cobranzas del afiliado en AFILMED';
                        // error que arroja el web service consultado
                        $error = 'Error de API externa. La consulta retornó NULL'; 
                        array_push($errors, $error);
                    }else{
                        if(property_exists($json, 'status') && $json->status == 'OK'){
                            $data = $json->data;
                            $status = 'ok';
                            $code = 1;
                            $count = null;
                            $message = 'Listado obtenido';
                        }else{
                            $data = null;
                            $status = 'fail';
                            $code = -5;
                            $count = null;
                            $message = 'Error en la operación';
                            // error que arroja el web service consultado
                            $error = 'Error consultando cobranzas del afiliado en AFILMED'; 
                            array_push($errors, $error);
                        }
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para BUSCAR AFILIADO';
                $count  = 0;
                $data = null;
                $error = 'Error de permisos';
                $code = -2;
            }
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
            array_push($errors, 'Error de Backend');
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $this->params,
                'extras' => $extras,
                'logged_user' => null,
            ]);
        }
    }

    /**
     * Consulta un afiliado en la api de silvio quadri
     */
    public function afiliado_afilmed(Request $request)
    {
        try{ 
            $extras = [
                'api_software_version' => config('site.software_version'),
                'ambiente' => config('site.ambiente'),
                'url' => '/int/consultas-externas/afiliado-afilmed',
                'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
                'function' => __FUNCTION__,
                'sps' => [],
                'responses' => [],
                'queries' => []
            ];
            $status = 'fail';
            $message = '';
            $count = -1;
            $code = -1;
            $line = null;
            $data = null;
            $errors = [];
            $this->params = [];
        
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            
            if($user->hasPermissionTo('buscar afiliado')){

                $tipo_documento = request('tipo_doc');
                $documento = request('nro_doc');
                
                $this->params = [
                    'tipo_doc' => $tipo_documento,
                    'nro_doc' => $documento
                ];
                
                if(env('CONSULTA_BOLETIN') == 'api'){
                    $url_afiliado = env('API_CASA_AFILIADO');
                    try {
                        $ch = curl_init($url_afiliado);
                        $fields = array(
                            'docu_tipo' => $tipo_documento,
                            'docu_nro' => $documento
                        );
                        
                        $fields_string = http_build_query($fields);
                        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
                        curl_setopt( $ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1' );
                        curl_setopt($ch, CURLOPT_POST, TRUE);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-AUTH-USER: integrisys", "X-AUTH-TOKEN: 2n6baXtpKFGy6TVB"]);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
                        $response = curl_exec( $ch );
                        array_push($extras['responses'], [
                            'consulta_afiliado' => [
                                'url' => $url_afiliado,
                                'respuesta' => json_decode($response)
                                ]
                            ]
                        );
                        // return $response;
                        if(!$response){
                            array_push($errors, 'Fallo cURL: '.curl_error( $ch ), curl_errno( $ch ));
                            throw new Exception('Fallo cURL: '.curl_error( $ch ), curl_errno( $ch ));
                        }
                        $httpReturnCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
                        if($httpReturnCode !== 200){
                            $json = null;
                            $response = html_entity_decode($response);
                            // error que arroja el web service consultado
                            $error = 'Ha ocurrido un problema en los Servicios en Línea. El error ha sido reportado.'; // Si el problema persiste, envíe un e-mail a webmaster@cajaabogados.org.ar'; 
                            array_push($errors, $error);
                            $data = null;
                            $code = -5;
                            $message = 'No hay afiliado que mostrar';
                        }else{
                            $json = json_decode($response);
                            // return $json;
                            $error = null;
                        }
                        if($error == null){
                            $count = 1;
                            $status = 'ok';
                            $message = 'Afiliado encontrado';
                            $code = 1;
                            $data = [
                                'afiliado' => $json != null && property_exists($json, 'data') ? $json->data : null,
                                'consulta' => env('CONSULTA_BOLETIN'),
                                'httpReturnCode' => $httpReturnCode,
                                'response' => json_decode($response)
                            ];
                        }else{
                            $code = -4;
                            $status = 'fail';
                            $message = 'No hay afiliado que mostrar.';
                            array_push($errors, $error);
                            $data = [
                                'afiliado' => $json != null && property_exists($json, 'data') ? $json->data : null,
                                'consulta' => env('CONSULTA_BOLETIN'),
                                'httpReturnCode' => $httpReturnCode,
                                'response' => json_decode($response)
                            ];
                        }
                    } catch (\Exception $e) {
                        $status = 'fail';
                        $code = -6;
                        $count = 1;
                        $message = 'Error consultando afiliado en API externa';
                        array_push($errors, 'Line: '.$e->getLine().' Message: '.$e->getMessage());
                        $data = [
                            'afiliado' => null,
                            'consulta' => env('CONSULTA_BOLETIN'),
                            'httpReturnCode' => $httpReturnCode,
                            'response' => json_decode($response)
                        ];
                    } finally {
                        if(is_resource($ch)){
                            curl_close( $ch );
                        }
                    }
                }else{
                    $code = -3;
                    $count = 0;
                    $status = 'empty';
                    $message =  'No hay api que consultar.';
                    $data = null;
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para BUSCAR AFILIADO';
                $count  = 0;
                $data = null;
                $error = 'Error de permisos';
                $code = -2;
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
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]); 
        } catch (\Throwable $th) {
            array_push($errors, 'Error de Backend');
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $this->params,
                'extras' => $extras,
                'logged_user' => null,
            ]);
        }
    }

}