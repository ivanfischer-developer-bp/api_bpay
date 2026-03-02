<?php

namespace App\Http\Controllers\Internos\Listados;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;


class ABMPlanController extends ConexionSpController
{

    /**
     * Obtiene un listado de los planes que coinciden con los parámetros dados
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function buscar_plan(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/plan/buscar-plan';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_plan_Select';
        $this->tipo_id_usuario = 'id';
        $this->param_id_usuario = 'id_usuario';
        if(request('id_plan') != null){
            $this->params['id_plan'] = request('id_plan');
            $this->params_sp['p_id_plan'] = request('id_plan');
        }
        if(request('id_convenio') != null){
            $this->params['id_convenio'] = request('id_convenio');
            $this->params_sp['p_id_convenio'] = request('id_convenio');
        }
        if(request('id_empresa') != null){
            $this->params['id_empresa'] = request('id_empresa');
            $this->params_sp['p_id_empresa'] = request('id_empresa');
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Obtiene un listado de los conceptos pertenecientes a un plan
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function buscar_conceptos_plan(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get'; 
        $this->url = '/int/listados/plan/buscar-conceptos-plan';
        $this->permiso_requerido = 'consultar listados';
        $this->db = 'afiliacion'; 
        $this->sp = 'sp_concepto_plan_Select';
        if(request('id_plan') != null){
            $this->params['id_plan'] = request('id_plan');
            $this->params_sp['id_plan'] = request('id_plan');
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega un nuevo plan 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function agregar_plan(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/listados/plan/agregar-plan',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail';
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $params_sp = [];

        $conceptos_ok = true;
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        try {
            $permiso_requerido = 'gestionar listados';
            if($user->hasPermissionTo($permiso_requerido)){
                $params = [
                    'n_plan' => request('n_plan'),
                    'id_convenio' => request('id_convenio'),
                    'vxgp' => request('vxgp') != null ? request('vxgp') : 0,
                    'nivel' => request('nivel'),
                    'cobertura_medicamento' => request('cobertura_medicamento'),
                    'conceptos' => request('conceptos')
                ];
                $params_sp = [
                    'p_n_plan' => request('n_plan'),
                    'p_id_convenio' => request('id_convenio'),
                    'p_vxgp' => request('vxgp') != null ? request('vxgp') : 0,
                    'p_nivel' => request('nivel'),
                    'p_cobertura_medicamento' => request('cobertura_medicamento'),
                    'p_id_usuario' => $id_usuario,
                    'p_fecha' => Carbon::now()->format('Ymd H:i:s'),
                ];

                array_push($extras['sps'], ['sp_plan_Insert' => $params_sp]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_plan_Insert', $params_sp));
                $response = $this->ejecutar_sp_directo('afiliacion','sp_plan_Insert', $params_sp);
                array_push($extras['responses'], ['sp_plan_Insert' => $response]);

                if(is_array($response) && array_key_exists('error', $response)){
                    array_push($errors, $response['error']);
                    $status = 'fail';
                    $message = 'Se produjo un error al guardar el plan';
                    $count = 0;
                    $data = null;
                    $code = -2;
                }else{
                    $status = 'ok';
                    $message = 'Plan guardado con éxito.';
                    $count = 1;
                    $code = 1;
                    $data = [
                        'plan' => $response,
                        'conceptos' => []
                    ];
                    foreach(request('conceptos') as $id_concepto){
                        $params_sp = [
                            'id_plan' => $response[0]->id,
                            'id_concepto' => $id_concepto,
                            'id_usuario' => $id_usuario,
                            'fecha' => Carbon::now()->format('Ymd H:i:s'),
                        ];
                        array_push($extras['sps'], ['sp_concepto_plan_Insert' => $params_sp]);
                        array_push($extras['queries'], $this->get_query('afiliacion', 'sp_concepto_plan_Insert', $params_sp));
                        $response_concepto = $this->ejecutar_sp_directo('afiliacion','sp_concepto_plan_Insert', $params_sp);
                        array_push($extras['responses'], ['sp_concepto_plan_Insert' => $response_concepto]);
                        
                        if(!empty($response_concepto[0]->id) && $response_concepto[0]->id > 0){
                            array_push($data['conceptos'], [$id_concepto => 'guardado']);
                        }else{
                            $conceptos_ok = false;
                            array_push($data['conceptos'], [$id_concepto => 'error']);
                        }
                    }
                    if($conceptos_ok){
                        $status = 'ok';
                        $message = $message.' Conceptos guardados con éxito.';
                        $count = 1;
                        $code = 2;
                    }else{
                        $status = 'ok';
                        $message = $message.' Error guardando conceptos.';
                        array_push($errors, 'Error guardando conceptos.');
                        $count = 1;
                        $code = -3;
                    }
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
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($permiso_requerido);
                $count  = -1;
                $data = null;
                array_push($errors, 'Error de permisos. '.$message);
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => -1,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -2,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Aactualiza un plan 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function actualizar_plan(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/listados/plan/actualizar-plan',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail';
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $params_sp = [];

        $conceptos_ok = true;
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        try {
            $permiso_requerido = 'gestionar listados';
            if($user->hasPermissionTo($permiso_requerido)){
                $params = [
                    'id_plan' => request('id_plan'),
                    'n_plan' => request('n_plan'),
                    'id_convenio' => request('id_convenio'),
                    'vxgp' => request('vxgp') != null ? request('vxgp') : 0,
                    'nivel' => request('nivel'),
                    'cobertura_medicamento' => request('cobertura_medicamento'),
                    'conceptos' => request('conceptos')
                ];
                $params_sp = [
                    'p_id_plan' => request('id_plan'),
                    'p_n_plan' => request('n_plan'),
                    'p_id_convenio' => request('id_convenio'),
                    'p_vxgp' => request('vxgp') != null ? request('vxgp') : 0,
                    'p_nivel' => request('nivel'),
                    'p_cobertura_medicamento' => request('cobertura_medicamento')
                ];

                array_push($extras['sps'], ['sp_plan_Update' => $params_sp]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_plan_Update', $params_sp));
                $response = $this->ejecutar_sp_directo('afiliacion','sp_plan_Update', $params_sp);
                array_push($extras['responses'], ['sp_plan_Update' => $response]);

                if(!empty($response[0]->filas) && $response[0]->filas > 0){
                    $status = 'ok';
                    $message = 'Plan guardado con éxito.';
                    $count = 1;
                    $code = 1;
                    $data = [
                        'plan' => $response,
                        'conceptos' => []
                    ];
                    //  obtiene los conceptos actuales
                    array_push($extras['sps'], ['sp_concepto_plan_Select' => ['id_plan' => strval(request('id_plan'))]]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_concepto_plan_Select', ['id_plan' => strval(request('id_plan'))]));
                    $conceptos_actuales = $this->ejecutar_sp_directo('afiliacion','sp_concepto_plan_Select', ['id_plan' => strval(request('id_plan'))]);
                    array_push($extras['responses'], ['sp_concepto_plan_Select' => $conceptos_actuales]);
                // return $conceptos_actuales;
                    if(!empty($conceptos_actuales)){
                        $ids_conceptos_actuales = [];
                        foreach($conceptos_actuales as $ca){
                            array_push($ids_conceptos_actuales, $ca->id_concepto);
                        }
                        if ( !empty(request('conceptos')) && count(request('conceptos')) > 0 ){
                            foreach(request('conceptos') as $id_concepto){
                                if ( !in_array($id_concepto, $ids_conceptos_actuales) ){
                                    $params_sp = [
                                        'id_plan' => request('id_plan'),
                                        'id_concepto' => $id_concepto,
                                        'id_usuario' => $id_usuario,
                                        'fecha' => Carbon::now()->format('Ymd H:i:s'),
                                    ];
                                    array_push($extras['sps'], ['sp_concepto_plan_Insert' => $params_sp]);
                                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_concepto_plan_Insert', $params_sp));
                                    $response_concepto = $this->ejecutar_sp_directo('afiliacion','sp_concepto_plan_Insert', $params_sp);
                                    array_push($extras['responses'], ['sp_concepto_plan_Insert' => $response_concepto]);
                        
                                    if(!empty($response_concepto[0]->id) && $response_concepto[0]->id > 0){
                                        array_push($data['conceptos'], [$id_concepto => 'guardado']);
                                    }else{
                                        $conceptos_ok = false;
                                        array_push($data['conceptos'], [$id_concepto => 'error']);
                                    }
                                }
                            }
                        }
                    }else{
                        if ( !empty(request('conceptos')) && count(request('conceptos')) > 0 ){
                            foreach(request('conceptos') as $id_concepto){
                                $params_sp = [
                                    'id_plan' => request('id_plan'),
                                    'id_concepto' => $id_concepto,
                                    'id_usuario' => $id_usuario,
                                    'fecha' => Carbon::now()->format('Ymd H:i:s'),
                                ];
                                array_push($extras['sps'], ['sp_concepto_plan_Insert' => $params_sp]);
                                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_concepto_plan_Insert', $params_sp));
                                $response_concepto = $this->ejecutar_sp_directo('afiliacion','sp_concepto_plan_Insert', $params_sp);
                                array_push($extras['responses'], ['sp_concepto_plan_Insert' => $response_concepto]);
                    
                                if(!empty($response_concepto[0]->id) && $response_concepto[0]->id > 0){
                                    array_push($data['conceptos'], [$id_concepto => 'guardado']);
                                }else{
                                    $conceptos_ok = false;
                                    array_push($data['conceptos'], [$id_concepto => 'error']);
                                }
                            }
                        }
                    }
                    if($conceptos_ok){
                        $status = 'ok';
                        $message = $message.' Conceptos guardados con éxito.';
                        $count = 1;
                        $code = 2;
                    }else{
                        $status = 'ok';
                        $message = $message.' Error guardando conceptos.';
                        array_push($errors, 'Error guardando conceptos.');
                        $count = 1;
                        $code = -3;
                    }
                }else{
                    array_push($errors, $response['error']);
                    $status = 'fail';
                    $message = 'Se produjo un error al guardar el plan';
                    $count = 0;
                    $data = null;
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
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($permiso_requerido);
                $count  = -1;
                $data = null;
                array_push($errors, 'Error de permisos. '.$message);
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => -1,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -2,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }
}
