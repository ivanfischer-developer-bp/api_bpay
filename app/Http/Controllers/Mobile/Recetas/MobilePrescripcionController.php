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

class MobilePrescripcionController extends ConexionSpController
{
    // ###############################################################################################################
    // ################################# RECETAS DE PRESCRIPCIONES DE PRACTICAS ######################################
    // ###############################################################################################################

    /**
     * Obtiene un listado de tipos y categorias de prácticas
     */
    public function get_tipo_y_categoria_practicas(Request $request)
    {
    
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/recetas/tipo-y-categoria-practicas',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];
        try {
            $params = null;
            $status = 'fail';
            $count = -1;
            $errors = [];
            $message = null;
            $line = null;
            $code = null;
            $data = [];

            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;
            $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
            
            $data = [
                'data' => [
                    [
                        'tipo' => 'Análisis Clínicos',
                        'categoria' => [
                            'Bacteriología',
                            'Bioquímica',
                            'Genética',
                            'Urología'
                        ]
                    ],
                    [
                        'tipo' => 'Ginecológico',
                        'categoria' => ['Ginecología']
                    ],
                    [
                        'tipo' => 'Medicina Nuclear',
                        'categoria' => [
                            'Cardiología',
                            'Centellograma'
                        ]
                    ],
                    [
                        'tipo' => 'Patología',
                        'categoria' => ['Patología']
                    ],
                    [
                        'tipo' => 'Práctica',
                        'categoria' => [
                            'Aire',
                            'Alergia',
                            'Bioquímica',
                            'Cardiología',
                            'Endoscopía',
                            'Fonoaudiología',
                            'Ginecología',
                            'Neumo',
                            'Neuro',
                            'Oftalmología',
                            'ORL',
                            'Rehabilitación',
                            'Urología'
                        ]
                    ],
                    [
                        'tipo' => 'Radiología',
                        'categoria' => [
                            'Cardiología',
                            'Densitometría',
                            'Ecografía',
                            'Ginecología',
                            'Radiología',
                            'Resonancia',
                            'Tomografía'
                        ]
                    ]
                ],
                'tipos' => [
                    'Análisis Clínicos',
                    'Ginecológico',
                    'Medicina Nuclear',
                    'Patología',
                    'Práctica',
                    'Radiología'
                ],
                'categorias' => [
                    'Bacteriología',
                    'Bioquímica',
                    'Genética',
                    'Urología',
                    'Ginecología',
                    'Cardiología',
                    'Centellograma',
                    'Patología',
                    'Aire',
                    'Alergia',
                    'Endoscopía',
                    'Fonoaudiología',
                    'Neumo',
                    'Neuro',
                    'Oftalmología',
                    'ORL',
                    'Rehabilitación',
                    'Tomografía'
                ]
            ];
            return response()->json([
                'status' => 'ok',
                'count' => sizeof($data),
                'errors' => $errors,
                'message' => 'Registros obtenidos',
                'line' => $line,
                'code' => 1,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        } catch (\Exception $e) {
            $status = 'fail';
            array_push($errors, 'Line: '.$th->getLine().' Error: '.$th->getMessage());
            $code = -1;  // metodo generar_receta
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
     * Obtiene un listado de medicamentos de https://apirecipe.hml.qbitos.com/apirecipe/GetPracticas/
     */
    public function get_practicas(Request $request)
    {
        
        date_default_timezone_set('America/Argentina/Cordoba');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'mobile/recetas/practicas',
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
        
        try {

            $params = [
                'conceptId' => request('conceptId') !== null ? request('conceptId') : null,
                'term' => request('term') !== null ? request('term') : null,
            ];
            $params_sp = [
                'conceptId' => request('conceptId') !== null ? request('conceptId') : null,
                'term' => request('term') !== null ? request('term') : null,
            ];
           
            $sp = 'sp_prestaciones_select';
            $db = 'validacion';
            array_push($extras['sps'], [$sp => $params_sp]);
            array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
            $response = $this->ejecutar_sp_directo($db, $sp, $params_sp);
            array_push($extras['responses'], [$sp => $response]);
            if(is_array($response) && array_key_exists('error', $response)){
                array_push($errors, $response['error']);
                $status = 'fail';
                $message = 'Se produjo un error al realizar la petición';
                $count = 0;
                $data = null;
                $code = -3;
                // Log::channel('')->error(''); // buscar canales en config/loggin.php
            }else if(empty($response)){
                $status = 'empty';
                $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                $count = 0;
                $data = $response;
                $code = -4;
                // Log::channel('')->info(''); // buscar canales en config/loggin.php
            }else{
                $status = 'ok';
                $message = 'Transacción realizada con éxito.';
                $count = sizeof($response);
                $data = $response;
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
     * Genera una nueva receta de prácticas medicas en https://apirecipe.hml.qbitos.com/apirecipe/prescribirPractica
     */
    public function generar_prescripcion_practicas(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/recetas/generar-prescripcion-practicas',
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

            $id_prescripcion = null;

            $id_hc = request('id_hc');
            $id_hoja_hc = request('id_hoja_hc');
            $id_medico = request('id_medico');
            $cie10 = request('cie10');
            $miprescripcion = request('prescripcion');
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

            // return $this->params;

            $response_sp_prescripciones = [];
            $recetas_ok = false;
            $recetas_guardadas_ok = false;
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;
            $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
            
            $respose = generar_receta_prescripcion($miprescripcion, $ambiente_recipe);
            $json = json_decode($respose['response']);
            $extras['info_curl'] = $respose['info_curl'];
            array_push($extras['responses'], ['api_recetas' => $json]);

            $data = [
                'response_api' => $json,
                'ids_recetas' => [],
                'response_sp' => []
            ];
            
            
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
                    
                    $c = 0;
                    foreach($json->otrasRecetas as $receta){
                        $params = [
                            'id_hoja' => $id_hoja_hc,
                            'id_historia' => $id_hc,
                            'fecha' => $receta->fecha,
                            'fechavencimiento' => $json->response[0]->fechavencimiento,
                            'id' =>  $receta->id,
                            'numeroPrescripcion' => $receta->numeroPrescripcion,
                            'nroCUIR' => '',  // esto no va en otrasRecetas de otrasPrescripciones
                            's3Link' => $receta->s3Link,
                            'verificador' => $receta->verificador,
                            'diagnostico' => $json->response[0]->diagnostico,
                            'observaciones' => $json->response[0]->observaciones, 
                            'usuario' =>  $logged_user['name'],
                            'id_medico' => $id_medico,
                            'medico_apellido' => $json->response[0]->medico->apellido,
                            'medico_nombre' => $json->response[0]->medico->nombre,
                            'medico_numeroDocumento' => $json->response[0]->medico->nroDoc,
                            'medico_numeroMatricula' =>  isset($json->response[0]->medico->matricula->numero) ? $json->response[0]->medico->matricula->numero : null,
                            'medico_provinciaMatricula' => isset($json->response[0]->medico->matricula->provincia) ? $json->response[0]->medico->matricula->provincia : null,
                            'medico_tipoMatricula' =>  isset($json->response[0]->medico->matricula->tipo) ? $json->response[0]->medico->matricula->tipo : null,
                            'medico_especialidad' => $json->response[0]->medico->especialidad,
                            'medico_fechaNacimiento' => $json->response[0]->medico->fechaNacimiento,
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
    
                            'prescripcion_1_item_nro' => isset($json->response[0]->prescripcion[0]) ?  1 : null,
                            'prescripcion_1_id' => isset($json->response[0]->prescripcion[0]) && isset($json->response[0]->prescripcion[0]->id) ? $json->response[0]->prescripcion[0]->id : null,
                            'prescripcion_1_cantidad' =>  isset($json->response[0]->prescripcion[0]) ? $json->response[0]->prescripcion[0]->cantidad : null,
                            'prescripcion_1_diagnostico' => isset($json->response[0]->prescripcion[0]) ? $json->response[0]->prescripcion[0]->diagnostico : null,
                            'prescripcion_1_codigoDiagnostico' => isset($json->response[0]->prescripcion[0]) ? $json->response[0]->prescripcion[0]->codigoDiagnostico : null,
                            'prescripcion_1_codigo' => isset($json->response[0]->prescripcion[0]) ? $json->response[0]->prescripcion[0]->codPractica : null,
                            'prescripcion_1_nombrePrescripcion' => isset($json->response[0]->prescripcion[0]) ? $json->response[0]->prescripcion[0]->prescripcion : null,
                            'prescripcion_1_observaciones' => isset($json->response[0]->prescripcion[0]) ? $json->response[0]->prescripcion[0]->observaciones : null,
                            'prescripcion_1_categoria' => null,

                            'prescripcion_2_item_nro' => isset($json->response[0]->prescripcion[1]) ?  2 : null,
                            'prescripcion_2_id' => isset($json->response[0]->prescripcion[1]) && isset($json->response[0]->prescripcion[1]->id) ? $json->response[0]->prescripcion[1]->id : null,
                            'prescripcion_2_cantidad' =>  isset($json->response[0]->prescripcion[1]) ? $json->response[0]->prescripcion[1]->cantidad : null,
                            'prescripcion_2_diagnostico' => isset($json->response[0]->prescripcion[1]) ? $json->response[0]->prescripcion[1]->diagnostico : null,
                            'prescripcion_2_codigoDiagnostico' => isset($json->response[0]->prescripcion[1]) ? $json->response[0]->prescripcion[1]->codigoDiagnostico : null,
                            'prescripcion_2_codigo' => isset($json->response[0]->prescripcion[1]) ? $json->response[0]->prescripcion[1]->codPractica : null,
                            'prescripcion_2_nombrePrescripcion' => isset($json->response[0]->prescripcion[1]) ? $json->response[0]->prescripcion[1]->prescripcion : null,
                            'prescripcion_2_observaciones' => isset($json->response[0]->prescripcion[1]) ? $json->response[0]->prescripcion[1]->observaciones : null,
                            'prescripcion_2_categoria' => null,

                            'prescripcion_3_item_nro' => isset($json->response[0]->prescripcion[2]) ?  3 : null,
                            'prescripcion_3_id' => isset($json->response[0]->prescripcion[2]) && isset($json->response[0]->prescripcion[2]->id) ? $json->response[0]->prescripcion[2]->id : null,
                            'prescripcion_3_cantidad' =>  isset($json->response[0]->prescripcion[2]) ? $json->response[0]->prescripcion[2]->cantidad : null,
                            'prescripcion_3_diagnostico' => isset($json->response[0]->prescripcion[2]) ? $json->response[0]->prescripcion[2]->diagnostico : null,
                            'prescripcion_3_codigoDiagnostico' => isset($json->response[0]->prescripcion[2]) ? $json->response[0]->prescripcion[2]->codigoDiagnostico : null,
                            'prescripcion_3_codigo' => isset($json->response[0]->prescripcion[2]) ? $json->response[0]->prescripcion[2]->codPractica : null,
                            'prescripcion_3_nombrePrescripcion' => isset($json->response[0]->prescripcion[2]) ? $json->response[0]->prescripcion[2]->prescripcion : null,
                            'prescripcion_3_observaciones' => isset($json->response[0]->prescripcion[2]) ? $json->response[0]->prescripcion[2]->observaciones : null,
                            'prescripcion_3_categoria' => null,

                            'prescripcion_4_item_nro' => isset($json->response[0]->prescripcion[3]) ?  4 : null,
                            'prescripcion_4_id' => isset($json->response[0]->prescripcion[3]) && isset($json->response[0]->prescripcion[3]->id) ? $json->response[0]->prescripcion[3]->id : null,
                            'prescripcion_4_cantidad' =>  isset($json->response[0]->prescripcion[3]) ? $json->response[0]->prescripcion[3]->cantidad : null,
                            'prescripcion_4_diagnostico' => isset($json->response[0]->prescripcion[3]) ? $json->response[0]->prescripcion[3]->diagnostico : null,
                            'prescripcion_4_codigoDiagnostico' => isset($json->response[0]->prescripcion[3]) ? $json->response[0]->prescripcion[3]->codigoDiagnostico : null,
                            'prescripcion_4_codigo' => isset($json->response[0]->prescripcion[3]) ? $json->response[0]->prescripcion[3]->codPractica : null,
                            'prescripcion_4_nombrePrescripcion' => isset($json->response[0]->prescripcion[3]) ? $json->response[0]->prescripcion[3]->prescripcion : null,
                            'prescripcion_4_observaciones' => isset($json->response[0]->prescripcion[3]) ? $json->response[0]->prescripcion[3]->observaciones : null,
                            'prescripcion_4_categoria' => null,

                            'prescripcion_5_item_nro' => isset($json->response[0]->prescripcion[4]) ?  5 : null,
                            'prescripcion_5_id' => isset($json->response[0]->prescripcion[4]) && isset($json->response[0]->prescripcion[4]->id) ? $json->response[0]->prescripcion[4]->id : null,
                            'prescripcion_5_cantidad' =>  isset($json->response[0]->prescripcion[4]) ? $json->response[0]->prescripcion[4]->cantidad : null,
                            'prescripcion_5_diagnostico' => isset($json->response[0]->prescripcion[4]) ? $json->response[0]->prescripcion[4]->diagnostico : null,
                            'prescripcion_5_codigoDiagnostico' => isset($json->response[0]->prescripcion[4]) ? $json->response[0]->prescripcion[4]->codigoDiagnostico : null,
                            'prescripcion_5_codigo' => isset($json->response[0]->prescripcion[4]) ? $json->response[0]->prescripcion[4]->codPractica : null,
                            'prescripcion_5_nombrePrescripcion' => isset($json->response[0]->prescripcion[4]) ? $json->response[0]->prescripcion[4]->prescripcion : null,
                            'prescripcion_5_observaciones' => isset($json->response[0]->prescripcion[4]) ? $json->response[0]->prescripcion[4]->observaciones : null,
                            'prescripcion_5_categoria' => null,

                            'prescripcion_6_item_nro' => isset($json->response[0]->prescripcion[5]) ?  6 : null,
                            'prescripcion_6_id' => isset($json->response[0]->prescripcion[5]) && isset($json->response[0]->prescripcion[5]->id) ? $json->response[0]->prescripcion[5]->id : null,
                            'prescripcion_6_cantidad' =>  isset($json->response[0]->prescripcion[5]) ? $json->response[0]->prescripcion[5]->cantidad : null,
                            'prescripcion_6_diagnostico' => isset($json->response[0]->prescripcion[5]) ? $json->response[0]->prescripcion[5]->diagnostico : null,
                            'prescripcion_6_codigoDiagnostico' => isset($json->response[0]->prescripcion[5]) ? $json->response[0]->prescripcion[5]->codigoDiagnostico : null,
                            'prescripcion_6_codigo' => isset($json->response[0]->prescripcion[5]) ? $json->response[0]->prescripcion[5]->codPractica : null,
                            'prescripcion_6_nombrePrescripcion' => isset($json->response[0]->prescripcion[5]) ? $json->response[0]->prescripcion[5]->prescripcion : null,
                            'prescripcion_6_observaciones' => isset($json->response[0]->prescripcion[5]) ? $json->response[0]->prescripcion[5]->observaciones : null,
                            'prescripcion_6_categoria' => null,

                            'prescripcion_7_item_nro' => isset($json->response[0]->prescripcion[6]) ?  7 : null,
                            'prescripcion_7_id' => isset($json->response[0]->prescripcion[6]) && isset($json->response[0]->prescripcion[6]->id) ? $json->response[0]->prescripcion[6]->id : null,
                            'prescripcion_7_cantidad' =>  isset($json->response[0]->prescripcion[6]) ? $json->response[0]->prescripcion[6]->cantidad : null,
                            'prescripcion_7_diagnostico' => isset($json->response[0]->prescripcion[6]) ? $json->response[0]->prescripcion[6]->diagnostico : null,
                            'prescripcion_7_codigoDiagnostico' => isset($json->response[0]->prescripcion[6]) ? $json->response[0]->prescripcion[6]->codigoDiagnostico : null,
                            'prescripcion_7_codigo' => isset($json->response[0]->prescripcion[6]) ? $json->response[0]->prescripcion[6]->codPractica : null,
                            'prescripcion_7_nombrePrescripcion' => isset($json->response[0]->prescripcion[6]) ? $json->response[0]->prescripcion[6]->prescripcion : null,
                            'prescripcion_7_observaciones' => isset($json->response[0]->prescripcion[6]) ? $json->response[0]->prescripcion[6]->observaciones : null,
                            'prescripcion_7_categoria' => null,

                            'prescripcion_8_item_nro' => isset($json->response[0]->prescripcion[7]) ?  8 : null,
                            'prescripcion_8_id' => isset($json->response[0]->prescripcion[7]) && isset($json->response[0]->prescripcion[7]->id) ? $json->response[0]->prescripcion[7]->id : null,
                            'prescripcion_8_cantidad' =>  isset($json->response[0]->prescripcion[7]) ? $json->response[0]->prescripcion[7]->cantidad : null,
                            'prescripcion_8_diagnostico' => isset($json->response[0]->prescripcion[7]) ? $json->response[0]->prescripcion[7]->diagnostico : null,
                            'prescripcion_8_codigoDiagnostico' => isset($json->response[0]->prescripcion[7]) ? $json->response[0]->prescripcion[7]->codigoDiagnostico : null,
                            'prescripcion_8_codigo' => isset($json->response[0]->prescripcion[7]) ? $json->response[0]->prescripcion[7]->codPractica : null,
                            'prescripcion_8_nombrePrescripcion' => isset($json->response[0]->prescripcion[7]) ? $json->response[0]->prescripcion[7]->prescripcion : null,
                            'prescripcion_8_observaciones' => isset($json->response[0]->prescripcion[7]) ? $json->response[0]->prescripcion[7]->observaciones : null,
                            'prescripcion_8_categoria' => null,

                            'prescripcion_9_item_nro' => isset($json->response[0]->prescripcion[8]) ?  9 : null,
                            'prescripcion_9_id' => isset($json->response[0]->prescripcion[8]) && isset($json->response[0]->prescripcion[8]->id) ? $json->response[0]->prescripcion[8]->id : null,
                            'prescripcion_9_cantidad' =>  isset($json->response[0]->prescripcion[8]) ? $json->response[0]->prescripcion[8]->cantidad : null,
                            'prescripcion_9_diagnostico' => isset($json->response[0]->prescripcion[8]) ? $json->response[0]->prescripcion[8]->diagnostico : null,
                            'prescripcion_9_codigoDiagnostico' => isset($json->response[0]->prescripcion[8]) ? $json->response[0]->prescripcion[8]->codigoDiagnostico : null,
                            'prescripcion_9_codigo' => isset($json->response[0]->prescripcion[8]) ? $json->response[0]->prescripcion[8]->codPractica : null,
                            'prescripcion_9_nombrePrescripcion' => isset($json->response[0]->prescripcion[8]) ? $json->response[0]->prescripcion[8]->prescripcion : null,
                            'prescripcion_9_observaciones' => isset($json->response[0]->prescripcion[8]) ? $json->response[0]->prescripcion[8]->observaciones : null,
                            'prescripcion_9_categoria' => null,

                            'prescripcion_10_item_nro' => isset($json->response[0]->prescripcion[9]) ?  10 : null,
                            'prescripcion_10_id' => isset($json->response[0]->prescripcion[9]) && isset($json->response[0]->prescripcion[9]->id) ? $json->response[0]->prescripcion[9]->id : null,
                            'prescripcion_10_cantidad' =>  isset($json->response[0]->prescripcion[9]) ? $json->response[0]->prescripcion[9]->cantidad : null,
                            'prescripcion_10_diagnostico' => isset($json->response[0]->prescripcion[9]) ? $json->response[0]->prescripcion[9]->diagnostico : null,
                            'prescripcion_10_codigoDiagnostico' => isset($json->response[0]->prescripcion[9]) ? $json->response[0]->prescripcion[9]->codigoDiagnostico : null,
                            'prescripcion_10_codigo' => isset($json->response[0]->prescripcion[9]) ? $json->response[0]->prescripcion[9]->codPractica : null,
                            'prescripcion_10_nombrePrescripcion' => isset($json->response[0]->prescripcion[9]) ? $json->response[0]->prescripcion[9]->prescripcion : null,
                            'prescripcion_10_observaciones' => isset($json->response[0]->prescripcion[9]) ? $json->response[0]->prescripcion[9]->observaciones : null,
                            'prescripcion_10_categoria' => null,

                        ];
                        
                        $sp = 'sp_HC_Prescripcion_insert';
                        $db = 'validacion';
                        array_push($params_log, $params);
                        array_push($extras['queries'], $this->get_query($db, $sp, $params));
                        array_push($extras['sps'], [$sp => $params]);
                        $ret_prescripciones = $this->ejecutar_sp_directo($db, $sp, $params);
                        array_push($extras['responses'], [$sp => $ret_prescripciones]);

                        if(isset($ret_prescripciones['error'])){
                            $status = 'warning';
                            $code = -7;
                            $message = 'La receta se generó y está disponible en la farmacia, pero NO fue posible guardar la receta en el sistema. ';
                            // es el primer error si la receta se realiza
                            $errors = $ret_prescripciones['error'];
                        }else{
                            $recetas_guardadas_ok = true; 
                            array_push($extras['responses'], ['recetas_ok' => $recetas_ok]);
                            array_push($extras['responses'], ['recetas_guardadas_ok' => $recetas_guardadas_ok]);
                            array_push($response_sp_prescripciones, $ret_prescripciones);
    
                            array_push($data['ids_prescripciones'], $ret_prescripciones[0]->id_prescripcion);
                            array_push($data['response_sp'], $ret_prescripciones);
                            $status = 'ok';
                            $code = 1;
                            $message = 'Recetas generadas y guardadas con éxito. ';
                            $count = sizeof($json->otrasRecetas);
                            
                            $data['response_sp'] = $ret_prescripciones;
                            $ids_prescripciones = [];
                            $id_prescripcion = json_decode(json_encode($response_sp_prescripciones[0]))[0]->id_prescripcion;
                            for($i = 0; $i < sizeof($response_sp_prescripciones); $i++){
                                if($response_sp_prescripciones[$i][0]->agregado == 1){
                                    array_push($ids_prescripciones, $response_sp_prescripciones[$i][0]->id_prescripcion);
                                }
                            }
                            
                        }
                        
                        $c++;
                    }
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
            
            if (is_array($errors) && count($errors) > 0) {
                // $errors tiene elementos
            } else {
                // $errors está vacío o no es array
                $errors = [' '];
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
            if (is_array($errors) && count($errors) > 0) {
                // $errors tiene elementos
            } else {
                // $errors está vacío o no es array
                $errors = [];
            }
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
     * Retorna un listado de prescripciones generadas por un medico
     */
    public function listar_prescripciones_emitidas(Request $request)
    {
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/recetas/listar-prescripciones-emitidas',
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