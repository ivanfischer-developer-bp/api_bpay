<?php

namespace App\Http\Controllers\Externos\Salud\Recetas;

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

class ExternalRecetasController extends ConexionSpController
{

    /**
     * Obtiene un listado de diagnósticos de https://apirecipe.hml.qbitos.com/apirecipe/GetDiagnostico
     */
    public function buscar_diagnosticos(Request $request)
    {
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            $code = 1;

            $ch = curl_init();
            $texto = curl_escape($ch, request('texto'));
            // $url = "https://apirecipe.hml.qbitos.com/apirecipe/GetDiagnostico?text=".$texto;
            $url = env('QBI2RECIPE_URLBASE')."/GetDiagnostico?text=".$texto;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Bearer '.env('QBI2RECIPE_TOKEN')
            ) );
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $response = curl_exec($ch);
            curl_close($ch);
            // return json_decode($response, true)['error'];
            if(isset(json_decode($response)->error)){
                return response()->json([
                    'status' => 'fail',
                    'count' => 0,
                    'errors' => [json_decode($response)->error],
                    'message' => json_decode($response)->mensaje,
                    'line' => null,
                    'code' => -2,
                    'data' => null,
                    'params' => request('texto'),
                    'logged_user' => $logged_user,
                    'api_software_version' => config('site.external_software_version')
                ]);
            }
            return response()->json([
                'status' => 'ok',
                'count' => sizeof(json_decode($response)->diagnosticos),
                'errors' => [],
                'line' => null,
                'message' => null,
                'code' => $code,
                'data' => json_decode($response)->diagnosticos,
                'params' => request('texto'),
                'logged_user' => $logged_user,
                'api_software_version' => config('site.external_software_version')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => [$e->getMessage()],
                'line' => null,
                'message' => $e->getMessage(),
                'code' => -1,
                'data' => null,
                'params' => request('texto'),
                'logged_user' => $logged_user,
                'api_software_version' => config('site.external_software_version')
            ]);
        } finally {
            if(is_resource($ch)){
                curl_close( $ch );
            }
        }
    }

    /**
     * Obtiene un listado de medicamentos de https://apirecipe.hml.qbitos.com/apirecipe/GetMedicamento/
     */
    public function buscar_medicamentos(Request $request)
    {
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            $param = request('medicamento');
            $numero_pagina = request('numeroPagina');
            $params = [
                'medicamento' => request('medicamento'),
                'numeroPagina' => request('numeroPagina')
            ];
            // $url = 'https://apirecipe.hml.qbitos.com/apirecipe/GetMedicamento/'.$param.'?clienteAppId='.env('QBI2RECIPE_IDFINANCIADOR').'&numeroPagina='.$numero_pagina;
            $url = env('QBI2RECIPE_URLBASE').'/GetMedicamento/'.$param.'?clienteAppId='.env('QBI2RECIPE_CLIENTEAPPID').'&numeroPagina='.$numero_pagina;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Bearer '.env('QBI2RECIPE_TOKEN')
            ) );
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $response = curl_exec($ch);
            curl_close($ch);
            if(isset(json_decode($response)->error)){
                return response()->json([
                    'status' => 'fail',
                    'count' => 0,
                    'errors' => [json_decode($response)->error],
                    'message' => json_decode($response)->mensaje,
                    'line' => null,
                    'code' => -1,
                    'data' => null,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'api_software_version' => config('site.external_software_version')
                ]);
            }
            
            if(env('AMBIENTE') == 'sanos' || env('AMBIENTE') == 'local' || env('AMBIENTE') == 'staging'){
                // sp_vademecum @p_json_items{ nro_registro: int, troquel: varchar(7), nombre: varchar(44), presentacion: varchar(24) }
                // return json_decode($response);
                $status = '';
                $count = 0;
                $message = '';
                $data = null;
                $code = 0;
                $errors = [];

                $response = json_decode($response);
                // return $response;
                $json_items = [];
                foreach ($response->medicamentos as $medicamento) {
                    // return $medicamento;
                    $json_item = [
                        'nro_registro' => null, //: int, 
                        'troquel' => null, //: varchar(7), 
                        'nombre' => $medicamento->nombreProducto, //: varchar(44), 
                        'presentacion' => $medicamento->presentacion //: varchar(24)
                    ];
                    array_push($json_items, $json_item);
                }
                // return json_encode($json_items);
                $vademecum = $this->ejecutar_sp_directo('afiliacion_externa', 'sp_vademecum', ['p_json_items' => json_encode($json_items)]);
                // return response()->json([
                //     'busqueda' => $param,
                //     'count_medicamentos' => sizeof($response->medicamentos),
                //     'count_vademecum' => sizeof($vademecum),
                //     'medicamentos' => $response->medicamentos,
                //     'vademecum' => $vademecum
                // ]);
                if(sizeof($vademecum) > 0){
                    for ($i = 0; $i < sizeof($response->medicamentos); $i++) {
                        // return $medicamento;
                        for ($j = 0; $j < sizeof($vademecum); $j++) {
                            if($response->medicamentos[$i]->nombreProducto == $vademecum[$j]->nombre){
                                $response->medicamentos[$i]->tieneCobertura = $vademecum[$j]->cobertura == 'NO' ? false : true;
                                $response->medicamentos[$i]->requiereAprobacion = $vademecum[$j]->req_autorizacion == 'NO' ? false : true;
                            }
                        }
                    }
                    $status = 'ok';
                    $count = sizeof($response->medicamentos);
                    $message = 'Medicamentos con vademecum obtenidos';
                    $data = $response;
                    $code = 1;
                }else{
                    array_push($errors, 'Error consultando vademecum');
                    $status = 'fail';
                    $count = sizeof($response->medicamentos);
                    $message = 'Medicamentos obtenidos sin vademecum';
                    $data = $response;
                    $code = 1;
                }
            }else{
                $status = 'ok';
                $count = sizeof($response->medicamentos);
                $message = 'Medicamentos obtenidos';
                $data = $response;
                $code = 1;
            }
            
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'line' => null,
                'message' => $message,
                'code' => $code,
                'data' => $data,
                'params' => $params,
                'logged_user' => $logged_user,
                'api_software_version' => config('site.external_software_version')
            ]);
        } catch (\Exception $e) {
            array_push($errors, 'Error de Backend: '.$e->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'line' => null,
                'message' => $e->getMessage(),
                'code' => -1,
                'data' => null,
                'params' => $params,
                'logged_user' => $logged_user,
                'api_software_version' => config('site.external_software_version')
            ]);
        } finally {
            if(is_resource($ch)){
                curl_close( $ch );
            }
        }
    }

    /**
     * Genera una nueva receta en https://apirecipe.hml.qbitos.com/apirecipe/Receta
     */
    public function generar_receta(Request $request)
    {
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            // $id_hc = request('id_hc');
            // $id_hoja_hc = request('id_hoja_hc');
            $mireceta = request('receta');
            $id_medico = request('id_medico');

            $this->params = [
                // 'id_hc' => $id_hc,
                // 'id_hoja_hc' => $id_hoja_hc,
                // 'id_medico' => $id_medico,
                'receta' => $mireceta,
            ];
            // $mireceta['paciente']['cobertura']['nroFinanciador'] = env('QBI2RECIPE_NROFINANCIADOR');

            $status = 'fail';
            $count = -1;
            $errors = [];
            $message = null;
            $line = null;
            $code = null;
            $data = null;

            $response_sp_recetas = [];
            $recetas_ok = false;
            $recetas_guardadas_ok = false;
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;
            $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

            $ch = curl_init();
            // curl_setopt($ch, CURLOPT_URL, 'https://apirecipe.hml.qbitos.com/apirecipe/Receta');
            curl_setopt($ch, CURLOPT_URL, env('QBI2RECIPE_URLRECETA'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer '.env('QBI2RECIPE_TOKEN'),
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($mireceta));
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $response = curl_exec($ch);
            curl_close($ch);
            $json = json_decode($response);
            $data = $json;
            if(property_exists($json, 'recetas')){
                $recetas_ok = true;
                $c = 0;
                foreach($json->recetas as $receta){
                    $params = [
                        'id_hoja' => null,
                        'id_historia' => null,
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
                        'medico_apellido' => $json->response[$c]->medico->apellido,
                        'medico_nombre' => $json->response[$c]->medico->nombre,
                        'medico_numeroDocumento' => $json->response[$c]->medico->numeroDocumento,
                        'medico_numeroMatricula' => $json->response[$c]->medico->numeroMatricula,
                        'medico_provinciaMatricula' => $json->response[$c]->medico->provinciaMatricula,
                        'medico_tipoMatricula' => $json->response[$c]->medico->tipoMatricula,
                        'medico_especialidad' => $json->response[$c]->medico->especialidad,
                        'medico_fechaNacimiento' => $json->response[$c]->medico->fechaNacimiento,
                        'observaciones' => $json->response[$c]->observaciones, 

                        'medicamento_1_item_nro' => isset($json->response[$c]->medicamentos[0]) ?  1 : null,
                        'medicamento_1_cantidad' =>  isset($json->response[$c]->medicamentos[0]) ? $json->response[$c]->medicamentos[0]->cantidad : null,
                        'medicamento_1_diagnostico' =>  isset($json->response[$c]->medicamentos[0]) ? $json->response[$c]->medicamentos[0]->diagnostico : null,
                        'medicamento_1_nombreDroga' =>  isset($json->response[$c]->medicamentos[0]) ? $json->response[$c]->medicamentos[0]->nombreDroga : null,
                        'medicamento_1_nombreProducto' =>  isset($json->response[$c]->medicamentos[0]) ? $json->response[$c]->medicamentos[0]->nombreProducto : null,
                        'medicamento_1_observaciones' =>  isset($json->response[$c]->medicamentos[0]) ? $json->response[$c]->medicamentos[0]->observaciones : null,
                        'medicamento_1_posologia' =>  isset($json->response[$c]->medicamentos[0]) ? $json->response[$c]->medicamentos[0]->posologia : null,
                        'medicamento_1_presentacion' =>  isset($json->response[$c]->medicamentos[0]) ? $json->response[$c]->medicamentos[0]->presentacion : null,
                        'medicamento_1_tratamiento' =>  isset($json->response[$c]->medicamentos[0]) ?$json->response[$c]->medicamentos[0]->tratamiento : null,
                        'medicamento_1_regNo' => null, // isset($mireceta['medicamentos'][0]) ? $mireceta['medicamentos'][0]['regNo'] : null,
                        'medicamento_1_permite_sustitucion' => isset($mireceta['medicamentos'][0]) ? $mireceta['medicamentos'][0]['permiteSustitucion'] : null,
                        'medicamento_1_requiere_duplicado' => isset($mireceta['medicamentos'][0]) ? $mireceta['medicamentos'][0]['requiereDuplicado'] : null,
                        
                        'medicamento_2_item_nro' =>  isset($json->response[$c]->medicamentos[1]) ? 2 : null,
                        'medicamento_2_cantidad' =>  isset($json->response[$c]->medicamentos[1]) ? $json->response[$c]->medicamentos[1]->cantidad : null,
                        'medicamento_2_diagnostico' =>  isset($json->response[$c]->medicamentos[1]) ? $json->response[$c]->medicamentos[1]->diagnostico : null,
                        'medicamento_2_nombreDroga' =>  isset($json->response[$c]->medicamentos[1]) ? $json->response[$c]->medicamentos[1]->nombreDroga : null,
                        'medicamento_2_nombreProducto' =>  isset($json->response[$c]->medicamentos[1]) ? $json->response[$c]->medicamentos[1]->nombreProducto : null,
                        'medicamento_2_observaciones' =>  isset($json->response[$c]->medicamentos[1]) ? $json->response[$c]->medicamentos[1]->observaciones : null,
                        'medicamento_2_posologia' =>  isset($json->response[$c]->medicamentos[1]) ? $json->response[$c]->medicamentos[1]->posologia : null,
                        'medicamento_2_presentacion' =>  isset($json->response[$c]->medicamentos[1]) ? $json->response[$c]->medicamentos[1]->presentacion : null,
                        'medicamento_2_tratamiento' =>  isset($json->response[$c]->medicamentos[1]) ? $json->response[$c]->medicamentos[1]->tratamiento : null,
                        'medicamento_2_regNo' => null, // isset($mireceta['medicamentos'][1]) ? $mireceta['medicamentos'][1]['regNo'] : null,
                        'medicamento_2_permite_sustitucion' => isset($mireceta['medicamentos'][1]) ? $mireceta['medicamentos'][1]['permiteSustitucion'] : null,
                        'medicamento_2_requiere_duplicado' => isset($mireceta['medicamentos'][1]) ? $mireceta['medicamentos'][1]['requiereDuplicado'] : null,
                        
                        'medicamento_3_item_nro' =>  isset($json->response[$c]->medicamentos[2]) ? 3 : null,
                        'medicamento_3_cantidad' =>  isset($json->response[$c]->medicamentos[2]) ? $json->response[$c]->medicamentos[2]->cantidad : null,
                        'medicamento_3_diagnostico' =>  isset($json->response[$c]->medicamentos[2]) ? $json->response[$c]->medicamentos[2]->diagnostico : null,
                        'medicamento_3_nombreDroga' =>  isset($json->response[$c]->medicamentos[2]) ? $json->response[$c]->medicamentos[2]->nombreDroga : null,
                        'medicamento_3_nombreProducto' =>  isset($json->response[$c]->medicamentos[2]) ? $json->response[$c]->medicamentos[2]->nombreProducto : null,
                        'medicamento_3_observaciones' =>  isset($json->response[$c]->medicamentos[2]) ? $json->response[$c]->medicamentos[2]->observaciones : null,
                        'medicamento_3_posologia' =>  isset($json->response[$c]->medicamentos[2]) ? $json->response[$c]->medicamentos[2]->posologia : null,
                        'medicamento_3_presentacion' =>  isset($json->response[$c]->medicamentos[2]) ? $json->response[$c]->medicamentos[2]->presentacion : null,
                        'medicamento_3_tratamiento' =>  isset($json->response[$c]->medicamentos[2]) ? $json->response[$c]->medicamentos[2]->tratamiento : null,
                        'medicamento_3_regNo' => null, // isset($mireceta['medicamentos'][2]) ? $mireceta['medicamentos'][2]['regNo'] : null,
                        'medicamento_3_permite_sustitucion' => isset($mireceta['medicamentos'][2]) ? $mireceta['medicamentos'][2]['permiteSustitucion'] : null,
                        'medicamento_3_requiere_duplicado' => isset($mireceta['medicamentos'][2]) ? $mireceta['medicamentos'][2]['requiereDuplicado'] : null,

                        'medicamento_4_item_nro' =>  isset($json->response[$c]->medicamentos[3]) ? 4 : null,
                        'medicamento_4_cantidad' =>  isset($json->response[$c]->medicamentos[3]) ? $json->response[$c]->medicamentos[3]->cantidad : null,
                        'medicamento_4_diagnostico' =>  isset($json->response[$c]->medicamentos[3]) ? $json->response[$c]->medicamentos[3]->diagnostico : null,
                        'medicamento_4_nombreDroga' =>  isset($json->response[$c]->medicamentos[3]) ? $json->response[$c]->medicamentos[3]->nombreDroga : null,
                        'medicamento_4_nombreProducto' =>  isset($json->response[$c]->medicamentos[3]) ? $json->response[$c]->medicamentos[3]->nombreProducto : null,
                        'medicamento_4_observaciones' =>  isset($json->response[$c]->medicamentos[3]) ? $json->response[$c]->medicamentos[3]->observaciones : null,
                        'medicamento_4_posologia' =>  isset($json->response[$c]->medicamentos[3]) ? $json->response[$c]->medicamentos[3]->posologia : null,
                        'medicamento_4_presentacion' =>  isset($json->response[$c]->medicamentos[3]) ? $json->response[$c]->medicamentos[3]->presentacion : null,
                        'medicamento_4_tratamiento' =>   isset($json->response[$c]->medicamentos[3]) ? $json->response[$c]->medicamentos[3]->tratamiento : null,
                        'medicamento_4_regNo' => null, // isset($mireceta['medicamentos'][3]) ? $mireceta['medicamentos'][3]['regNo'] : null,
                        'medicamento_4_permite_sustitucion' => isset($mireceta['medicamentos'][3]) ? $mireceta['medicamentos'][3]['permiteSustitucion'] : null,
                        'medicamento_4_requiere_duplicado' => isset($mireceta['medicamentos'][3]) ? $mireceta['medicamentos'][3]['requiereDuplicado'] : null,

                        'medicamento_5_item_nro' =>  isset($json->response[$c]->medicamentos[4]) ? 5 : null,
                        'medicamento_5_cantidad' =>  isset($json->response[$c]->medicamentos[4]) ? $json->response[$c]->medicamentos[4]->cantidad : null,
                        'medicamento_5_diagnostico' =>  isset($json->response[$c]->medicamentos[4]) ? $json->response[$c]->medicamentos[4]->diagnostico : null,
                        'medicamento_5_nombreDroga' =>  isset($json->response[$c]->medicamentos[4]) ? $json->response[$c]->medicamentos[4]->nombreDroga : null,
                        'medicamento_5_nombreProducto' =>  isset($json->response[$c]->medicamentos[4]) ? $json->response[$c]->medicamentos[4]->nombreProducto : null,
                        'medicamento_5_observaciones' =>  isset($json->response[$c]->medicamentos[4]) ? $json->response[$c]->medicamentos[4]->observaciones : null,
                        'medicamento_5_posologia' =>  isset($json->response[$c]->medicamentos[4]) ? $json->response[$c]->medicamentos[4]->posologia : null,
                        'medicamento_5_presentacion' =>  isset($json->response[$c]->medicamentos[4]) ? $json->response[$c]->medicamentos[4]->presentacion : null,
                        'medicamento_5_tratamiento' =>   isset($json->response[$c]->medicamentos[4]) ? $json->response[$c]->medicamentos[4]->tratamiento : null,       
                        'medicamento_5_regNo' => null, // isset($mireceta['medicamentos'][4]) ? $mireceta['medicamentos'][4]['regNo'] : null,
                        'medicamento_5_permite_sustitucion' => isset($mireceta['medicamentos'][4]) ? $mireceta['medicamentos'][4]['permiteSustitucion'] : null,
                        'medicamento_5_requiere_duplicado' => isset($mireceta['medicamentos'][4]) ? $mireceta['medicamentos'][4]['requiereDuplicado'] : null,
                    ];
                    
                    $ret_receta = $this->ejecutar_sp_directo('validacion_externa', 'sp_HC_Receta_insert', $params);
                    Log::channel('external')->info('sp_HC_Receta_insert consultado por '.$request->user()->name.' (id '.$request->user()->id.')');
                    if(isset($ret_receta['error'])){
                        array_push($errors, $ret_receta['error']);
                    }else{
                        $recetas_guardadas_ok = true;
                    }
                    array_push($response_sp_recetas, $ret_receta);
                    $c++;
                }
            }else{
                array_unshift($errors, [$json->mensaje, ' Error al generar receta ']);
            }
            if($recetas_ok && $recetas_guardadas_ok){
                $status = 'ok';
                $data = [
                    'response_api' => $json,
                    // 'response_sp' => $response_sp_recetas
                ];
                $count = sizeof($json->recetas);
                $message = 'Recetas generadas y guardadas con éxito';
            }else{
                $status = 'fail';
                $count = 0;
                array_push($errors, 'error guardando receta');
                $message = 'No fue posible generar o guardar la receta';
            }

            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => 1,
                'data' => $data,
                'params' => $this->params,
                'logged_user' => $logged_user
            ]);
        } catch (\Exception $e) {
            array_push($errors, 'Error de Backend: '.$e->getMessage());
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $e->getMessage(),
                'line' => null,
                'code' => -1,
                'data' => $data,
                'params' => $this->params,
                'logged_user' => $logged_user
            ]);
        } finally {
            if(is_resource($ch)){
                curl_close( $ch );
            }
        }
    }

}