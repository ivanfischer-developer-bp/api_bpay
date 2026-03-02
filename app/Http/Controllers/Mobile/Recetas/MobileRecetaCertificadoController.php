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
// use setasign\Fpdi\Tcpdf\Fpdi;
// use setasign\Fpdi\Tfpdf\Fpdi; 

class MobileRecetaCertificadoController extends ConexionSpController
{
    // ###############################################################################################################
    // ################################# RECETA DE CERTIFICADO ##########################################################
    // ###############################################################################################################

    /**
     * Genera una nueva receta de prácticas medicas en https://apirecipe.hml.qbitos.com/apirecipe/prescribirPractica
     */
    public function generar_receta_certificado(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'mobile/recetas/generar-receta-certificado',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => [],
        ];
        
        $status = 'fail';
        $count = -1;
        $errors = [''];
        $message = null;
        $line = null;
        $code = 0; 
        $data = null;
        $params_log = [];

        try {

            $id_prescripcion = null;
            $id_hc = request('id_hc');
            $id_hoja_hc = request('id_hoja_hc');
            $id_medico = request('id_medico');
            $cie10 = request('cie10');
            $miprescripcion = request('certificado');

            $token_siaf = request('token_siaf');
            $id_convenio_siaf = request('id_convenio_siaf');
            $informar_siaf = request('informar_siaf') == 'true' ? true : false;
            $ambiente_recipe = request('ambiente_recipe') !== null ? request('ambiente_recipe') : env('AMBIENTE_RECIPE');

            $paciente = request('paciente');

            $n_convenio = request('n_convenio') !== null ? request('n_convenio') : null;
            $id_plan = request('id_plan') !== null ? request('id_plan') : null;
             
            $this->params = [
                'id_hc' => $id_hc,
                'id_hoja_hc' => $id_hoja_hc,
                'id_medico' => $id_medico,
                'cie10' => $cie10,
                'prescripcion' => $miprescripcion,
                'token_siaf' => $token_siaf,
                'id_convenio_siaf' => $id_convenio_siaf,
                'informar_siaf' => false, // $informar_siaf,
                'paciente' => $paciente
            ];

            $response_sp_prescripciones = [];
            $recetas_ok = false;
            $recetas_guardadas_ok = false;
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;
            $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
            
            
            $respose = generar_receta_certificado($miprescripcion, $ambiente_recipe);
            $json = json_decode($respose['response']);
            $extras['info_curl'] = $respose['info_curl'];
            array_push($extras['responses'], ['api_recetas' => $json]);

            $data = [
                'response_api' => $json,
                'ids_recetas' => [],
                'response_sp' => []
            ];
            
            // return $data;

            if($json == null){
                $recetas_ok = false;
                $errors = ['Error, la API de recetas devolvió NULL'];
                $message = 'No fue posible generar la prescripción debido a un problema externo.';
                $status = 'fail';
                $count = 0;
                $code = -9;
            }else{
                if(property_exists($json, 'otrasRecetas')){
                    $recetas_ok = true;
                    $data = [
                        'response_api' => $json,
                        'ids_prescripciones' => [],
                        'response_sp' => []
                    ];
                    $message = 'Prescripción generada con éxito. ';
                    $status = 'ok';
                    $count = 1;
                    $code = 1;
                    
                }else{
                    $recetas_ok = false;
                    // es el primer error si la receta falla
                    $errors = [$json->error.' - '.$json->mensaje, 'Error al generar prescripción'];
                    $message = 'No fue posible generar la prescripción. '.$json->mensaje;
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
            $data['response_siaf'] = [
                'codAutorizacion' => 0,
                'error' => 0,
                'estado' => "AUTORIZADO",
                'estado_id' => "A",
                'id' => 0,
                'mensaje' => null,
                'numero' => 0
            ];
            
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
            Log::channel('recetas')->info('# CODE: '.$code.' # STATUS: '.$status.' # PRESCRIPCION ERRORS: '.json_encode($errors).' # LINE: '.$th->getLine().' # MESSAGE: '.$th->getMessage());
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

    /**
     * Retorna un listado de certificados generados por un medico
     */
    public function listar_certificados_emitidos(Request $request)
    {
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'mobile/recetas/listar-certificados-emitidos',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'desarrollo' => 'falta desarrollar'
        ];
        return $extras;
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
            if($user->hasPermissionTo('listar prescripciones')){
                array_push($extras['queries'], $this->get_query('validacion', 'sp_HC_Prescripcion_select', $this->params_sp));
                array_push($extras['sps'], ['sp_HC_Prescripcion_select' => $this->params_sp]);
                $response = $this->ejecutar_sp_directo('validacion', 'sp_HC_Prescripcion_select', $this->params_sp);
                array_push($extras['responses'], ['sp_HC_Prescripcion_select' => $response]);

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
                    'data' => $data,
                    'params' => $this->params,
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
                    'data' => $data,
                    'params' => $this->params,
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
}