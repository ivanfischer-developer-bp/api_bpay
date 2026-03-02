<?php

namespace App\Http\Controllers\Internos\Diagnosticos;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\ConexionSpController;

use App\Models\User;

class DiagnosticoController extends ConexionSpController
{
    /**
     * Obtiene los datos de un diagnóstico
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response $response
     */
    public function buscar_diagnosticos(Request $request)
    {
        // ver ejemplo de uso en AAEjemploUsoController
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/diagnosticos/buscar-diagnosticos',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            
            // variables de respuesta
            $status = 'null';
            $message = 'sin comentarios';
            $count = -1;
            $data = null;
            $errors = [];
            $line = null;
            $code = null;

            if($user->hasPermissionTo('listar diagnosticos')){
                $buscar_en = request('buscar_en');
                $tipo_busqueda = request('tipo_busqueda');
                $termino = request('termino');
                $id_tipo_expediente = request('id_tipo_expediente');
                $codigo = request('codigo');
                $modulo = request('modulo') != null ? request('modulo'): 'validacion';

                $params = [
                    'buscar_en' => $buscar_en,
                    'codigo' => $codigo,
                    'id_tipo_expediente' => $id_tipo_expediente,
                    'termino' => $termino,
                    'tipo_busqueda' => $tipo_busqueda,
                    'modulo' => $modulo
                ];
                

                // ejecuta el sp
                try {
                    if ( is_numeric($id_tipo_expediente) && $id_tipo_expediente > 0 ){
                        $filtros = [
                            'p_id_tipo_expediente' => $id_tipo_expediente,
                        ];
                        array_push($extras['sps'], ['sp_cie_v10_item_Select' => $filtros]);
                        array_push($extras['queries'], $this->get_query($modulo, 'sp_cie_v10_item_Select', $filtros));
                        $response = $this->ejecutar_sp_directo($modulo, 'sp_cie_v10_item_Select', $filtros);
                        array_push($extras['responses'], ['sp_cie_v10_item_Select' => $response]);
                        if(isset($response['error'])){
                            array_push($errors, $response['error']);
                            $status = 'fail';
                            $count = 0;
                            $message = 'Error en la consulta a la base de datos.';
                        }
                    } else {
                        $parametro_descripcion = 'p_string';
                        $sp = 'sp_cie_v10_item_Select';
                        if ( $buscar_en == 'capitulo' ){
                            $sp = 'sp_cie_v10_capitulo_Select';
                            $parametro_descripcion = 'p_n_capitulo';
                        } elseif ( $buscar_en == 'sub-capitulo' ) {
                            $sp = 'sp_cie_v10_sub_capitulo_Select';
                            $parametro_descripcion = 'p_n_sub_capitulo';
                        } elseif ( $buscar_en == 'diagnostico') {
                            $sp = 'sp_cie_v10_item_Select';
                            $parametro_descripcion = 'p_string';
                        }
                        if ( $tipo_busqueda == 'por-codigo' ) {
                            $filtros['p_cod_cie_item'] = $codigo;
                            array_push($extras['sps'], [$sp => $filtros]);
                            array_push($extras['queries'], $this->get_query($modulo, $sp, $filtros));
                            $response = $this->ejecutar_sp_directo($modulo, $sp, $filtros);
                            array_push($extras['responses'], [$sp => $response]);
                            if(isset($response['error'])){
                                array_push($errors, $response['error']);
                                $status = 'fail';
                                $count = 0;
                                $message = 'Error en la consulta a la base de datos.';
                            }else{
                                $data = $response[0];  // solo devolvemos el primer resultado porque es por código, debe ser exacto
                            }
                        } else {
                            //	prepara los wildcards
                            $prefijo = $tipo_busqueda == 'que-contenga' ? '%' : '';
                            $sufijo = $tipo_busqueda == 'que-inicie' ? '%' : '';
            
                            //	agrega el filtro por descripción
                            $filtros[$parametro_descripcion] = $prefijo . $termino . $sufijo;
                            array_push($extras['sps'], [$sp => $filtros]);
                            array_push($extras['queries'], $this->get_query($modulo, $sp, $filtros));
                            $response = $this->ejecutar_sp_directo($modulo, $sp, $filtros);
                            array_push($extras['responses'], [$sp => $response]);
                            if(isset($response['error'])){
                                array_push($errors, $response['error']);
                                $status = 'fail';
                                $count = 0;
                                $message = 'Error en la consulta a la base de datos.';
                            }else{
                                $data = $response;
                            }
                        }
                    }
                    // prepara la respuesta
                    // si hubo errores al ejecutar el sp
                    if(is_array($response) && array_key_exists('error', $response)){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $code = -3;
                    }else if($count = sizeof($response) == 0){
                        $status = 'empty';
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                        $count = sizeof($response);
                        $code = -2;
                    }else{
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = sizeof($response);
                        $code = 1;
                    }
                } catch (\Throwable $th) {
                    array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
                    $status = 'fail';
                    $message = $th->getMessage();
                    $count = 0;
                    $code = $th->getCode();
                    $line = $th->getLine();
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para LISTAR DIAGNOSTICOS';
                $count  = 0;
                $errors = [];
                $code = -1;
            }
            // retorna el response
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
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }
}
