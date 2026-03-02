<?php

namespace App\Http\Controllers\Internos;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;



class EncuestasController extends ConexionSpController
{

    /**
     * Guarda la apreciación de un afiliado sobre la atención recibida 
     * o la negación de haber recibido atención
     */
    public function guardar_encuesta_atencion(Request $request)
    {
        try{ 
            $extras = [
                'api_software_version' => config('site.software_version'),
                'ambiente' => config('site.ambiente'),
                'url' => '/int/encuestas/guardar-encuesta-atencion',
                'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
                'function' => __FUNCTION__,
                'sps' => [],
                'responses' => [],
                'queries' => []
            ];
            $status = 'ok';
            $message = 'Respuesta Guardada';
            $count = 1;
            $code = 1;
            $line = null;
            $data = null;
            $errors = [];
            $this->params = [
                'id_encuesta' => request('id_encuesta'),
                'atendido' => request('atendido'),
                'calificacion_atencion' => request('calificacion_atencion'),
                'motivo_calificacion' => request('motivo_calificacion'),
                'pago_adicional' => request('pago_adicional'),
                'calificacion_tiempo' => request('calificacion_tiempo'),
                'calificacion_explicacion' => request('calificacion_explicacion'),
            ];

            $params_sp = [
                'p_id_envio_encuesta' => $this->params['id_encuesta'],
                'p_atendido' => $this->params['atendido'],
                'p_calificacion_atencion' => $this->params['calificacion_atencion'],
                'p_motivo_calificacion' => $this->params['motivo_calificacion'],
                'p_pago_adicional' => $this->params['pago_adicional'],
                'p_calificacion_tiempo' => $this->params['calificacion_tiempo'],
                'p_calificacion_explicacion' => $this->params['calificacion_explicacion']
            ];
            
            array_push($extras['sps'], ['sp_envio_encuesta_update' => $params_sp]);
            array_push($extras['queries'], $this->get_query('validacion', 'sp_envio_encuesta_update', $params_sp));
            $response = $this->ejecutar_sp_directo('validacion', 'sp_envio_encuesta_update', $params_sp);
            array_push($extras['responses'], ['sp_envio_encuesta_update' => $response]);
            
            if(is_array($response) && (array_key_exists('error', $response) || $response[0]->id == null)){
                $status = 'fail';
                $message = 'Encuesta NO Guardada';
                $count = 0;
                $code = -2;
                $line = null;
                $data = null;
                $errors = ['Se produjo un error al guardar la encuesta'];
            }else{
                $status = 'ok';
                $message = 'Encuesta Guardada';
                $count = 1;
                $code = 1;
                $line = null;
                $data = $response;
                $errors = [];
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
                'logged_user' => null,
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
