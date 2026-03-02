<?php

namespace App\Http\Controllers\Internos\Afiliaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
// use Illuminate\Support\Facades\File as FacadeFile;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use File;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;

class HistoriaClinicaController extends ConexionSpController
{
    /**
     * Obtiene la historia clinica de un afiliado por su tipo y numero de documento
     * @param tipo_documento string en el request
     * @param numero_documento number en el request
     */
    public function buscar_historia_clinica(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  
        $this->url = '/int/afiliaciones/historia-clinica/buscar';
        $this->permiso_requerido = 'ver historia clinica';
        $this->db = 'validacion'; 
        $this->sp = 'AWEB_TraerHojasHistoriaClinica';
        if(request('tipo_documento') != null){
            $this->params['tipo_documento'] = request('tipo_documento');
            $this->params_sp['tipo_documento'] = request('tipo_documento');
        }
        if(request('numero_documento') != null){
            $this->params['numero_documento'] = request('numero_documento');
            $this->params_sp['numero_documento'] = request('numero_documento');
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Añade una nueva hoja a la historia clinica
     */
    public function agregar_historia_clinica(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/afiliaciones/historia-clinica/agregar',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [
                'AWEB_AgregarHojaHistoriaClinica'
            ],
            'responses' => [],
            'queries' => []
        ];
        try {
            $status = 'fail';
            $count = -1;
            $errors = [];
            $message = null;
            $line = null;
            $code = null;
            $data = null;
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $examen_fisico = request('examen_fisico');

            $this->params = [
                'id_medico' => request('id_medico'),
                'tipo_documento' => request('tipo_documento'), 
				'numero_documento' => request('numero_documento'),
                'observaciones' => request('observaciones'),
                'adjunto' => request('adjunto'),
                'examen_fisico' => $examen_fisico
            ];

            if($user->hasPermissionTo('gestionar historia clinica')){
                //  parametros obligatorios
                if ( empty($this->params['tipo_documento']) || empty($this->params['numero_documento']) || empty($this->params['observaciones']) ){
                    $error = 'Parámetros incorrectos o incompletos'; 
                    $message = 'Verifique los parámetros';
                    $code = -1;
                    $count = 0;
                }else{
                    //  establece los parametros
                    $params = [
                        'tipo_documento' => $this->params['tipo_documento'],
                        'numero_documento' => $this->params['numero_documento'],
                        'usuario' => $logged_user['usuario'],
                        'id_medico' => !empty($this->params['id_medico']) ? $this->params['id_medico'] : null,
                        'hoja' => $this->params['observaciones'],
                        'archivo' => !empty($this->params['adjunto']) ? $this->params['adjunto'] : NULL,
                        'Talla' => $examen_fisico['talla'] != null ? $examen_fisico['talla'] : null,
                        'Peso' => $examen_fisico['peso'] != null ? $examen_fisico['peso'] : null,
                        'BMI' => $examen_fisico['bmi'] != null ? $examen_fisico['bmi'] : null,
                        'TA' => $examen_fisico['ta'] != null ? $examen_fisico['ta'] : null,
                        'FC' => $examen_fisico['fc'] != null ? $examen_fisico['fc'] : null,
                        'RM' => $examen_fisico['rm'] != null ? $examen_fisico['rm'] : null,
                        'Antecedentes_Gin' => $examen_fisico['antecedentes_ginecologicos'] != null ? $examen_fisico['antecedentes_ginecologicos'] : null,
                        'Anticonceptivo' => $examen_fisico['anticonceptivo'] != null ? $examen_fisico['anticonceptivo'] : null,
                        'Mamas' => $examen_fisico['mamas'] != null ? $examen_fisico['mamas'] : null,
                        'Genitales_Externos' => $examen_fisico['genitales_externos'] != null ? $examen_fisico['genitales_externos'] : null,
                        'Menarca' => $examen_fisico['menarca'] != null ? $examen_fisico['menarca'] : null,
                        'FUM' => $examen_fisico['fum'] != null ? $examen_fisico['fum'] : null,
                        'Ultimo_Control' => $examen_fisico['ultimo_control'] != null ? $examen_fisico['ultimo_control'] : null,
                        'Piel' => $examen_fisico['piel'] != null ? $examen_fisico['piel'] : null,
                        'Usa_Lentes' => $examen_fisico['usa_lentes'] != null ? $examen_fisico['usa_lentes'] : null,
                        'Odoscopia_Normal' => $examen_fisico['odoscopia_normal'] != null ? $examen_fisico['odoscopia_normal'] : null,
                        'Dentadura' => $examen_fisico['dentadura'] != null ? $examen_fisico['dentadura'] : null,
                        'Pulmones' => $examen_fisico['pulmones'] != null ? $examen_fisico['pulmones'] : null,
                        'Corazon' => $examen_fisico['corazon'] != null ? $examen_fisico['corazon'] : null,
                        'Abdomen' => $examen_fisico['abdomen'] != null ? $examen_fisico['abdomen'] : null,
                        'OD_Agudeza_Visual' => $examen_fisico['od_agudeza_visual'] != null ? $examen_fisico['od_agudeza_visual'] : null,
                        'OI_Agudeza_Visual' => $examen_fisico['oi_agudeza_visual'] != null ? $examen_fisico['oi_agudeza_visual'] : null,
                        'OD_Agudeza_Audio' => $examen_fisico['od_agudeza_audio'] != null ? $examen_fisico['od_agudeza_audio'] : null,
                        'OI_Agudeza_Audio' => $examen_fisico['oi_agudeza_audio'] != null ? $examen_fisico['oi_agudeza_audio'] : null,
                        'Datos_Adicionales' => $examen_fisico['datos_adicionales'] != null ? $examen_fisico['datos_adicionales'] : null,
                        'Hallazgos_Anormales' => $examen_fisico['hallazgos_anormales'] != null ? $examen_fisico['hallazgos_anormales'] : null,
                    ];
                    $extras['params_sp'] = $params;
    
                    //  devuelve las hojas de la historia clinica
                    array_push($extras['queries'], $this->get_query('validacion', 'AWEB_AgregarHojaHistoriaClinica', $params));
                    $response = $this->ejecutar_sp_directo('validacion', 'AWEB_AgregarHojaHistoriaClinica', $params);
                    array_push($extras['responses'], ['AWEB_AgregarHojaHistoriaClinica' => $response]);
                    //  dependiendo el resultado, devuelve el mensaje
                    // return response()->json([
                    //     'response' => $response
                    // ]);
                    if(isset($response['error'])){
                        array_push($errors, $resp['error']);
                        $data = null;
                        $status = 'fail';
                        $count = 0;
                        $message = 'Error en la consulta a la base de datos.';
                    }else if ( !empty($response) && !empty($response[0]) && $response[0]->agregado > 0 ){
                        $status = 'ok';
                        $count = sizeof($response);
                        $message = 'La historia clínica fue insertada exitosamente';
                        $data = $response;
                        $code = 1;
                    }else{
                        array_push($errors, 'Error al insertar registro');
                        $status = 'fail';
                        $count = 0;
                        $code = -2;
                        $message = 'No se pudo insertar la historia clínica';
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para GESTIONAR HISTORIA CLINICA';
                $count  = 0;
                $data = null;
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
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);

        } catch (\Throwable $th) {
            array_push($errors, 'Error de backend');
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => $data,
                'params' => $this->params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        }
    }
}