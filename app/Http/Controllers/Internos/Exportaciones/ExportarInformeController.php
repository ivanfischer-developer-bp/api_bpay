<?php

namespace App\Http\Controllers\Internos\Exportaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;
use App\Exports\InformeExport;

use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use File;
use Storage;

class ExportarInformeController extends ConexionSpController
{
    /**
     * Exporta un informe en formato excel y lo almacena en el path
     * storage/app/public/reportes/informe_sistema
     */
    public function exportar_informe(Request $request)
    {
        try {
            // return request('informe');
            $this->url = 'int/general/informes/exportar-informe';
            $this->controlador = 'InformesController.php';
            $this->funcion = 'exportar_informe';
            $this->permiso_requerido = 'generar informes';
            $this->user_id = $request->user()->id;
            $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
            $this->metodo_http = 'get';  //get, post
            $this->param_id_usuario = 'id_usuario';  // id_n_usuario -> user->
            $this->tipo_id_usuario = 'param';
            $this->param_usuario = $request->user()->id_usuario_sqlserver != null ? $request->user()->usuario : env('USUARIO_DEFAULT');
            // $this->param_usuario = 'dmourino@cajaabogados.org.ar';

            $errors = [];
            $informe = request('informe');
            $filtros = request('filtros');
            $this->params = [
                'informe' => $informe,
                'filtros' => $filtros
            ];
            $this->sp = $informe['stored_procedure'];

            $title = $informe['nombre'];
            $agregar_cabecera = $informe['cabecera'];
            $nombre = $informe['nombre'];
            $nombre_archivo = $informe['nombre_archivo'];
            $n_archivo_extension = $informe['n_archivo_extension'];
            $filename = 'Informe.xls';
            if($nombre_archivo == '' || $nombre_archivo == null){
                if($n_archivo_extension == '' || $n_archivo_extension == null ){
                    $filename = $nombre.'-'.Carbon::now()->toDateTimeString().'.xls';
                }else{
                    $filename = $nombre.'-'.Carbon::now()->toDateTimeString().'.'.$n_archivp_extension;
                }
            }else{
                if($n_archivo_extension == '' || $n_archivo_extension == null ){
                    $filename = $nombre_archivo.'.xls';
                }else{
                    $filename = $nombre_archivo.'.'.$n_archivo_extension;
                }
            }

            $sp_params = explode(',', $informe['parametros']);
            //  por cada parametro
            foreach ( $sp_params as $sp_param ) {
                if ( !empty($filtros[$sp_param]) ) {
                    $this->params_sp[$sp_param] = $filtros[$sp_param];
                }
            }

            $data = $this->ejecutar_sp();
            $cabecera = [];
            if($informe['cabecera'] == 1){
                foreach ($data[0] as $key => $value){
                    array_push($cabecera, $key);
                }
            }

            //  ejecuta el informe y almacena el archivo generado
            if ( !empty($data) ){
                try {
                    // almacenar
                    $ret = Excel::store(new InformeExport($data, $cabecera, $title), $filename, 'informes_sistema');
                    if($ret == 1) {
                        $this->response = [
                            'status' => 'ok',
                            'count' => $ret,
                            'errors' => [],
                            'message' => 'Archivo generado y almacenado',
                            'line' => null,
                            'code' => 1,
                            'data' => storage_path('informes_sistema').'/'.$filename,
                            'params' => $this->params,
                            'extras' => $this->extras,
                            'logged_user' => $this->logged_user,
                        ];
                    }else{
                        array_push($errors, $ret);
                        $this->response = [
                            'status' => 'fail',
                            'count' => 0,
                            'errors' => $errors,
                            'message' => 'No se pudo generar el archivo',
                            'line' => null,
                            'code' => 1,
                            'data' => null,
                            'params' => $this->params,
                            'extras' => $this->extras,
                            'logged_user' => $this->logged_user,
                        ];
                    }
                    return response()->json($this->response); 
                } catch (\Throwable $th) {
                    array_push($this->errors, 'Line: '.$th->getLine().' Error: '.$th->getMessage());
                    $this->response = [
                        'status' => 'fail',
                        'count' => 0,
                        'errors' => $errors,
                        'message' => $th->getMessage(),
                        'line' => $th->getLine(),
                        'code' => -1,
                        'data' => null,
                        'params' => $this->params,
                        'extras' => $this->extras,
                        'logged_user' => $this->logged_user,
                    ];
                    return response()->json($this->response); 
                }
            } else {
                $this->response = [
                    'status' => 'empty',
                    'count' => 0,
                    'errors' => [],
                    'message' => 'No se encotraron registros',
                    'line' => null,
                    'code' => -1,
                    'data' => null,
                    'params' => $this->params,
                    'extras' => $this->extras,
                    'logged_user' => $this->logged_user,
                ];
                return response()->json($this->response); 
            }
        } catch (\Throwable $th) {
            array_push($this->errors, 'Line: '.$th->getLine().' Error: '.$th->getMessage());
            $this->response = [
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $this->params,
                'extras' => $this->extras,
                'logged_user' => $this->logged_user,
            ];
            return response()->json($this->response); 
        }
    }
}