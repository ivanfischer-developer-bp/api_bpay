<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;
use DB;

use App\Models\User;
use App\Http\Controllers\ConexionSpController;

class AlfabetaController extends ConexionSpController
{

    /**
     * Actualiza la base de datos de medicamento alfabeta
     * Consulta los precios de medicamentos desde https://cnpm.msal.gov.ar/api/vademecum
     *
     * @return \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function actualizar_alfabeta(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/alfabeta/actualizar-alfabeta',
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
            $status = 'fail';
            $message = 'Aflabeta No pudo ser actualizada';
            $count = 0;
            $data = null;
            $errors = [];
            $line = null;
            $code = -1;

            if($user->hasPermissionTo('procesar alfabeta')){
                try {
                    Log::channel('alfabeta')->info('User-----> '.$logged_user['name'].' --------------');
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
                    if($response === false){
                        Log::channel('alfabeta')->info('cURL errno: '.curl_errno($ch).' error: '.curl_error($ch));
                    }
                    Log::channel('alfabeta')->info('cURL HTTP code: '.curl_getinfo($ch, CURLINFO_HTTP_CODE));
                    Log::channel('alfabeta')->info('cURL content-type: '.curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
                    array_push($extras['responses'], ['curl' => (array) json_decode($response)]);
                    Log::channel('alfabeta')->info('Fecha: '.Carbon::now()->format('d-m-Y').' Respuesta '.$url.': '.substr($response, 0, 1000).'..."');
                    if($response != null){
                        curl_close($ch);
                        $r = (array) json_decode($response);
                        if(is_array($r)){
                            $count = sizeof($r);
                        }
                        Log::channel('alfabeta')->info('Cantidad de registros obtenidos: '.$count);
                        $fecha_proceso = Carbon::now()->format('Ymd');
                        $sp = 'sp_actualizar_alfabeta';
                        $db = 'alfabeta';
                        
                        // Envolver el array en un objeto ya que el SP espera un objeto JSON, no un array
                        $json_data = json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $json_size = strlen($json_data);
                        // El prefijo N es sintaxis SQL Server para Unicode, pero no debe ir dentro del valor cuando usas consultas preparadas con ?.
                        // Cuando usas ? en una consulta preparada con PDO, el driver automáticamente se encarga de:
                        // Escapar el valor
                        // Tratarlo como Unicode (N) en SQL Server
                        // PDO/SQL Server automáticamente transforma tu parámetro a formato Unicode internamente. No necesitas agregar nada más.
                        // Sí, un JSON puede empezar con [ perfectamente: un array es JSON válido.
                        // Solo sería obligatorio empezar con {} si el SP está programado para esperar un objeto con una clave específica (por ejemplo medicamentos).
                        // Si el SP está usando OPENJSON(@p_json) directamente, acepta array o objeto.
                        // Si en cambio hace algo como OPENJSON(@p_json, '$.medicamentos'), entonces debe venir como:
                        // {"medicamentos":[...]}

                        
                        Log::channel('alfabeta')->info('Tamaño del JSON optimizado: '.$json_size.' bytes');
                        $actualizados = 0;
                        try {
                            array_push($extras['sps'], [$sp => ['json_size' => $json_size.' bytes', 'p_fecha_proceso' => $fecha_proceso, 'p_json' => $json_data]]);
                            array_push($extras['queries'], 'JSON de '.$json_size.' bytes. EXEC '.$sp.' @p_fecha_proceso = '.$fecha_proceso.', @p_json = '.$json_data);
                            $resultado = DB::connection($db)->statement(
                                "EXEC ".$sp." @p_fecha_proceso = ?, @p_json = ?",
                                [$fecha_proceso, $json_data]
                            );
                            // $ejecutado = [(object) ['resultado' => $ejecutado ? 1 : 0]];
                            array_push($extras['responses'], [$sp => $resultado]);
                            // array_push($extras['responses'], [$sp => $ejecutado]);
                            Log::channel('alfabeta')->info('Ejecutado correctamente');
                        } catch (\Throwable $th_sql) {
                            Log::channel('alfabeta')->info('Error: '.$th_sql->getMessage());
                            $resultado = ['error' => $th_sql->getMessage()];
                            array_push($extras['responses'], [$sp => $resultado]);
                        }
                        Log::channel('alfabeta')->info('Fecha: '.Carbon::now()->format('d-m-Y').' Resulado proceso: '.substr(json_encode($resultado), 0, 300));
                        if(is_array($resultado) && array_key_exists('error', $resultado)){
                            $res = substr(json_encode($resultado), 0, 300).'..."';
                            array_push($errors, 'Error al ejecutar '.$sp);
                            array_push($errors, $resultado['error']);
                            $message = $res;
                            $code = -2;
                            Log::channel('alfabeta')->info('Fecha: '.Carbon::now()->format('d-m-Y').' Error al procesar '.$sp.' desde controlador: '.$res);
                        }else{
                            if($resultado){
                                $actualizados = $resultado;
                                $data = $count;
                                $code = 1;
                                $status = 'ok';
                                $message = 'Procedimiento ejecutado con éxito. Se obtuvieron '.$count.' registros. Se actualizaron '. $data . ' registros';
                                Log::channel('alfabeta')->info('Fecha: '.Carbon::now()->format('d-m-Y').' Procedimiento ejecutado con éxito. Alfabeta actualizada');
                            }else{
                                $status = 'ok';
                                $data = 0;
                                $message = 'Procedimiento ejecutado. Nada para actualizar';
                                Log::channel('alfabeta')->info('Fecha: '.Carbon::now()->format('d-m-Y').' Procedimiento ejecutado. Nada para actualizar');
                                $code = 2;
                            }
                            // if(!empty($resultado) && is_array($resultado) && !empty($resultado[0]) && $resultado[0]->resultado > 0){
                            //     $actualizados = $resultado;
                            //     $data = $resultado[0]->resultado;
                            //     $code = 1;
                            //     $status = 'ok';
                            //     $message = 'Procedimiento ejecutado con éxito. Se obtuvieron '.$count.' registros. Se actualizaron '. $actualizados . ' registros';
                            //     Log::channel('alfabeta')->info('Fecha: '.Carbon::now()->format('d-m-Y').' Procedimiento ejecutado con éxito. Alfabeta actualizada');
                            // }else{
                            //     $status = 'ok';
                            //     $data = 0;
                            //     $message = 'Procedimiento ejecutado. Nada para actualizar';
                            //     Log::channel('alfabeta')->info('Fecha: '.Carbon::now()->format('d-m-Y').' Procedimiento ejecutado. Nada para actualizar');
                            //     $code = 2;
                            // }
                        }
                    }else{
                        if($response == false || $response == 0){
                            $message = 'El endpoint falló o no devolvió datos. Nada para actualizar';
                            $status = 'ok';
                            $data = $response;
                            $count = 0;
                            $code = 3;
                        }else{
                            $message = 'No hay registros para actualizar';
                            $status = 'empty';
                            $data = $response;
                            $count = 0;
                            $code = -1;
                        }
                        Log::channel('alfabeta')->info('Fecha: '.Carbon::now()->format('d-m-Y').' Respuesta: No hay registros para actualizar');
                    }
                } catch (\Throwable $th) {
                    array_push($errors, 'error en el proceso');
                    $message = $th->getMessage();
                    $line = $th->getLine();
                    $code = $th->getCode();
                    Log::channel('alfabeta')->info('Fecha: '.Carbon::now()->format('d-m-Y').' Error en el proceso: '.$th->getMessage());
                } finally {
                    if(is_resource($ch)){
                        curl_close( $ch );
                    }
                    Log::channel('alfabeta')->info('..................');
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
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CONSULTAR ALFABETA';
                $count  = 0;
                $data = null;
                array_push($errors, 'Error de permisos');
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => null,
                    'data' => $data,
                    'params' => $this->params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Error capturado en backend');
            Log::channel('alfabeta')->info('Fecha: '.Carbon::now()->format('d-m-Y').' Error capturado en backend: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => null,
                'extras' => $extras,
                'logged_user' => null,
            ]);
        }
    }

    /**
     * Consulta los precios históricos de medicamentos en alfabeta
     * 
     * @return \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function consultar_alfabeta(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/alfabeta/consultar-alfabeta',
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
            $status = 'fail';
            $message = 'Aflabeta No pudo ser actualizada';
            $count = 0;
            $data = null;
            $errors = [];
            $line = null;
            $code = -1;

            $nombre = request('nombre');
            $troquel = request('troquel');
            $codigo_barra = request('codigo_barra');
            $numero_registro = request('numero_registro');

            $this->params = [
                'p_nombre' => $nombre,
                'p_troquel' => $troquel,
                'p_cod_barra' => $codigo_barra,
                'p_nro_registro' => $numero_registro
            ];
            $params = [
                'nombre' => $nombre,
                'troquel' => $troquel,
                'codigo_barra' => $codigo_barra,
                'numero_registro' => $numero_registro
            ];

            if($user->hasPermissionTo('consultar alfabeta')){

                array_push($extras['sps'], ['sp_alfabeta_select' => $this->params]);
                array_push($extras['queries'], $this->get_query('validacion', 'sp_alfabeta_select', null));
                $resp = $this->ejecutar_sp_directo('validacion', 'sp_alfabeta_select', null);
                array_push($extras['responses'], ['sp_alfabeta_select', $resp]);

                if(!empty($resp) && sizeof($resp) > 0){
                    $data = $resp;
                    $status = 'ok';
                    $message = 'Registros encontrados';
                    $count = sizeof($data);
                }else{
                    $data = $resp;
                    $status = 'empty';
                    $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                    $count = 0; 
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
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]); 
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CONSULTAR ALFABETA';
                $count  = 0;
                $data = null;
                array_push($errors, 'Error de permisos');
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => null,
                    'data' => $null,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Error capturado en backend');
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => $data,
                'params' => $params,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Obtiene un listado de laboratorios
     * 
     * @return \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function listar_laboratorios(Request $request)
    {
        // sp_laboratorio_select
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/alfabeta/listar-laboratorios';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'alfabeta'; 
        $this->sp = 'sp_laboratorio_select';
        return $this->ejecutar_sp_simple();
    }

}

// {
//     "codigo":76,
//     "troquel":3086942,
//     "estado":"A",
//     "nombre":"ACI-TIP",
//     "presentacion":"susp.x 200 ml",
//     "importado":false,
//     "heladera":false,
//     "iva":false,
//     "laboratorio":{
//         "id":903,
//         "nombre":"Siegfried"
//     },
//     "tipoVenta":{
//         "id":2,
//         "nombre":"Venta Bajo Receta"
//     },
//     "control":{
//         "id":0,
//         "nombre":"NO CONTROLADO"
//     },
//     "tamanio":{
//         "id":1,
//         "nombre":"MENOR"
//     },
//     "forma":{
//         "id":10,
//         "nombre":"Jarabe\/Suspensi\u00f3n oral\/Polvo para uso oral"
//     },
//     "via":{
//         "id":2,
//         "nombre":"oral"
//     },
//     "monodroga":{
//         "id":5986,
//         "nombre":"magaldrato+simeticona"
//     },
//     "accion":{
//         "id":607,
//         "nombre":"Anti\u00e1cido Antiflatulento"
//     },
//     "unidadPotencia":{
//         "id":5,
//         "nombre":"mg\/5 ml"
//     },
//     "unidades":1,
//     "cantidad":null,
//     "gravado":false,
//     "aptoCeliacos":false,
//     "porcentajePami":null,
//     "precioVentaPami":null,
//     "marcaSifar":false,
//     "montoIoma":null,
//     "marcaNormaIoma":false,
//     "prospectoId":null,
//     "fechaUltimaActualizacion":"2024-05-13T18:00:15-03:00",
//     "ultimoPrecio":"10393.31",
//     "ultimoPrecioVigencia":"2024-05-13T18:00:04-03:00",
//     "barras":[
//         "7795345002033"
//     ]
// }

