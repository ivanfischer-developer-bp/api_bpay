<?php

namespace App\Http\Controllers\Internos\General;

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

class InformesController extends ConexionSpController
{

    public function listar_informes(Request $request)
    {
        $this->url = 'int/general/informes/listar-informes';
        $this->controlador = 'InformesController.php';
        $this->funcion = 'listar_informes';
        $this->permiso_requerido = 'generar informes';
        $this->user_id = $request->user()->id;
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->metodo_http = 'get';  //get, post
        $this->param_id_usuario = 'id_usuario';  // id_n_usuario -> user->
        $this->tipo_id_usuario = 'param';
        // $this->param_usuario = $request->user()->id_usuario_sqlserver != null ? $request->user()->usuario : env('USUARIO_DEFAULT');
        $this->param_usuario = $request->user()->usuario;
        $this->params = [
            'id_empresa' => request('id_empresa') != null ? request('id_empresa') : 1,
        ];
        $this->params_sp = [
            'id_empresa' =>	$this->params['id_empresa'],
        ];
        $this->sp = 'AWEB_TraerInformes';
        // return $this->params_sp;
        return $this->ejecutar_sp_simple();
    }

    public function buscar_informe(Request $request)
    {
        $this->url = 'int/general/informes/buscar-informe';
        $this->controlador = 'InformesController.php';
        $this->funcion = 'buscar_informe';
        $this->permiso_requerido = 'generar informes';
        $this->user_id = $request->user()->id;
        $this->db = 'validacion'; // afiliacion, validacion, admin, alfabeta
        $this->metodo_http = 'post';  //get, post
        $informe = request('informe');
        $filtros = request('filtros');
        $this->params = [
            'informe' => $informe,
            'filtros' => $filtros
        ];
        if($informe['parametros'] != '' && $informe['parametros'] != null){
            $sp_params = explode(',', $informe['parametros']);
        }else{
            $sp_params = null;
        }
        $this->param_id_usuario = 'id_usuario';  // id_n_usuario -> user->
        $this->tipo_id_usuario = 'usuario';
        // $this->param_usuario = $request->user()->id_usuario_sqlserver != null ? $request->user()->usuario : env('USUARIO_DEFAULT');
        
        if($sp_params != null){
            // return 'if';
            //  por cada parametro
            foreach ( $sp_params as $sp_param ) {
                if ( !empty($filtros[$sp_param]) ) {
                    $this->params_sp[$sp_param] = $filtros[$sp_param];
                }
            }
        }else{
            // return 'else';
            $this->params_sp = null;
        }
        $this->sp = $informe['stored_procedure'];

        // return $this->sp;
        $ret = $this->ejecutar_sp_simple();
        // return $ret;
        $r = (array) json_decode(json_encode($ret));
        // return $r;
        $d = $r['original']->data;
        // return $d;
        if(is_array($d) && sizeof($d) > 0){
            // formateamos un poco los valores
            foreach ( $d as $item ){
                $item = (array) $item;
                // return $item;
                try {
                    foreach($item as $key => $value){
                        if($key != 'Codigo_Nomenclador'){
                            // si es un float la formateamos a ###,##
                            if(is_numeric($value) && is_float(0 + $value)){
                                $value = str_replace('.', ';', $value);
                                $value = str_replace(',', '.', $value);
                                $value = str_replace(';', ',', $value);
                                // $value = floatval($value);
                                $item[$key] = '$'.$value;
                            }
                            // si es un ',00' la formateamos a '0,00'
                            if(substr($value, 0, 1) == ',' && substr($value, 1, 1) == '0'){
                                $item[$key] = '$0,00';
                            }
                        }
                        // si es una fecha la formateamos
                        $v1 = explode(' ', $value, 2);
                        $v2 = explode('-', $v1[0], 3);
                        $v3 = explode(':', $value, 3);
                        // si tiene guiones y dos puntos es un datetime
                        if(sizeof($v2) >= 3 && sizeof($v3) >= 3){ 
                            // return $v2;
                            if(is_numeric($v2[0]) && is_numeric($v2[1]) && is_numeric($v2[2])){
                                $item[$key] = Carbon::parse($v1[0])->format('d/m/Y');
                                // return $item[$key];
                            }
                        }
                    }
                    // return $item;
                    $temp_data[] = $item;
                } catch (\Exception $e) {
                    return $e;
                }
            }
            //	sets array
            // return $temp_data;
            $r['original']->data = $temp_data;
            return response()->json($r['original']);
        }else{
            return response()->json($r['original']);
        }
    }

    /**
     * Retorna un listado de usuarios
     */
    public function listar_usuarios_informes(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  
        $this->url = 'int/general/informes/usuarios-informes';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_usuario_select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Retorna un listado de usuarios
     */
    public function listar_informes_usuario(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  
        $this->url = 'int/general/informes/informes-usuario';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_informe_usuario_select';
        if(request('n_usuario') != null){
            $this->params['n_usuario'] = request('n_usuario');
            $this->params_sp['p_n_usuario'] = request('n_usuario');
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Habilita un informe para un usuario
     */
    public function habilitar_informe(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  
        $this->url = 'int/general/informes/habilitar-informe';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_informe_usuario_insert';
        $this->params =[
            'n_usuario' => request('n_usuario'),
            'id_informe' => request('id_informe')
        ];
        $this->params_sp = [
            'p_n_usuario' => $this->params['n_usuario'],
            'p_id_informe' => $this->params['id_informe']
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Deshabilita un informe para un usuario
     */
    public function deshabilitar_informe(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  
        $this->url = 'int/general/informes/deshabilitar-informe';
        $this->permiso_requerido = 'realizar configuraciones';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_informe_usuario_delete';
        $this->params = [
            'n_usuario' => request('n_usuario'),
            'id_informe' => request('id_informe')
        ];
        $this->params_sp = [
            'p_n_usuario' => $this->params['n_usuario'],
            'p_id_informe' => $this->params['id_informe']
        ];
        return $this->ejecutar_sp_simple();
    }


}