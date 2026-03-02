<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response as FacadeResponse;

use File;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;

use App\Http\Controllers\ConexionSpController;

use App\Models\User;

class MobileAfiliadoController extends ConexionSpController {
    
    /**
     * Busca un afiliado a través del sp_afiliado_Select
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function buscar_afiliado(Request $request)
    {
        try {
            // variables de respuesta
            $extras = [
                'api_software_version' => config('site.software_version'),
                'ambiente' => config('site.ambiente'),
                'url' => '/mobile/afiliaciones/afiliado/buscar-afiliado',
                'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
                'function' => __FUNCTION__,
                'sps' => [],
                'responses' => [],
                'queries' => [],
                'verificado' => []
            ];
            $status = 'fail';
            $message = '';
            $count = -1;
            $data = null;
            $errors = [];
            $this->params = [];
        
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            
            if($user->hasPermissionTo('buscar afiliado')){
                // ver ejemplo de uso en AAEjemploUsoController
                // ejecuta el sp
                try {
                    $id_afiliado = request('id');
                    $tipo = request('tipo');
                    $id_persona = request('id_persona');
                    $codigo = request('codigo');
                    $nombre = request('nombre');
                    $id_tipo_documento = request('id_tipo_documento');
                    $numero_documento = request('numero_documento');
                    $tipo_busqueda = request('tipo_busqueda');
                    $nro_afiliado = request('numero_afiliado');
                    $estado = request('estado');
                    $id_plan = request('id_plan');
                    $id_convenio = request('id_convenio');
                    $id_empresa = request('id_empresa');
                    $id_grupo = request('id_grupo');
                    $hst = request('historico') != null ? request('historico') : 0;
                    $buscar_internacion_abierta = request('buscar_internacion_abierta') != null ? filter_var(request('buscar_internacion_abierta'), FILTER_VALIDATE_BOOLEAN) : false;
                    
                    $params_sp = [];

                    $this->params['buscar_internacion_abierta'] = $buscar_internacion_abierta;

                    if ($nro_afiliado != ''){
                        $this->params['nro_afiliado'] = $nro_afiliado;
                        $params_sp['nro_afiliado'] = $nro_afiliado;
                    }
                    if ($codigo != ''){
                        $this->params['nro_afiliado'] = $codigo;
                        $params_sp['nro_afiliado'] = $codigo;
                    }
                    if ($id_afiliado != ''){
                        $this->params['p_id_afiliado'] = $id_afiliado;
                        $params_sp['p_id_afiliado'] = $id_afiliado;
                    }
                    if ($id_persona != ''){
                        $this->params['id_persona'] = $id_persona;
                        $params_sp['id_persona'] = $id_persona;
                    }
                    if ($nombre != ''){
                        $this->params['nombre'] = $nombre;
                        $params_sp['nombre'] = $nombre;
                    }
                    if ($id_tipo_documento != ''){
                        $this->params['id_tipo_doc'] = $id_tipo_documento;
                        $params_sp['id_tipo_doc'] = $id_tipo_documento;
                    }
                    if ($numero_documento != ''){
                        $this->params['nro_doc'] = $numero_documento;
                        $params_sp['nro_doc'] = $numero_documento;
                    }
                    if ($estado != ''){
                        $this->params['estado'] = $estado;
                        $params_sp['estado'] = $estado;
                    }
                    if ($id_plan != ''){
                        $this->params['id_plan'] = $id_plan;
                        $params_sp['id_plan'] = $id_plan;
                    }
                    if ($id_convenio != ''){
                        $this->params['id_convenio'] = $id_convenio;
                        $params_sp['id_convenio'] = $id_convenio;
                    }
                    if ($id_empresa != ''){
                        $this->params['id_empresa'] = $id_empresa;
                        $params_sp['id_empresa'] = $id_empresa;
                    }
                    if ($id_grupo != ''){
                        $this->params['p_id_grupo'] = $id_grupo;
                        $params_sp['p_id_grupo'] = $id_grupo;
                    }
                    
                    $this->params['p_hst'] = $hst;
                    $params_sp['p_hst'] = $hst;

                    // if(request('buscar_internacion_abierta') != null){
                    //     $this->params['buscar_internacion_abierta'] = $buscar_internacion_abierta;
                    // }

                    // if(request('consultar_boletin_protectivo') != null){
                    //     $this->params['consultar_boletin_protectivo'] = $consultar_boletin_protectivo;
                    // }

                    if (empty($tipo)) {
                        // $this->params['p_otros'] = 'ejemplo de error'; // puesto para provocar un error de sqlserver para verlo en el front
                        //	obtiene los afiliados
                        array_push($extras['sps'], ['sp_afiliado_Select' => $params_sp]);
                        array_push($extras['queries'], $this->get_query('afiliacion', 'sp_afiliado_Select', $params_sp));
                        $response = $this->ejecutar_sp_directo('afiliacion', 'sp_afiliado_Select', $params_sp);
                        array_push($extras['responses'], ['sp_afiliado_Select' => $response]);
                        if(isset($response['error'])){
                            array_push($errors, $response['error']);
                            $data = null;
                            $status = 'fail';
                            $count = 0;
                            $message = 'Error en consulta a base de datos';
                            return response()->json([
                                'status' => $status,
                                'count' => $count,
                                'errors' => $errors,
                                'message' => 'Se produjo un error al realizar la petición.',
                                'data' => $data,
                                'params' => $this->params,
                                'logged_user' => $logged_user,
                                'line' => null,
                                'code' => -5,
                                'extras' => $extras
                            ]);
                        }else{
                            $data = $response;
                            $status = 'ok';
                            $count = sizeof($response);
                            $message = 'Registros encontrados';
                        }
                    } else if ($tipo == 'small') {
                        //  busqueda por rol
                        $params = ['p_id_rol' => 1, 'p_is_baja' => 1];
                        if ($nombre != ''){
                            $params['p_nombre'] = $nombre;
                        }
                        if ($codigo != ''){
                            $params['p_codigo'] = $codigo;
                        }
                        array_push($extras['sps'], ['sp_rol_validacion' => $params]);
                        array_push($extras['queries'], $this->get_query('afiliacion', 'sp_rol_validacion', $params));
                        $response = $this->ejecutar_sp_directo('afiliacion', 'sp_rol_validacion', $params);
                        array_push($extras['responses'], ['sp_rol_validacion' => $response]);
                    }

                    // return $response;

                    if(!empty($response)){
                        $status = 'ok';
                        $count = is_array($response) ? sizeof($response) : 1;
                        $this->params['tipo_busqueda'] = $tipo_busqueda;

                        if ($tipo_busqueda != 'por-codigo' || $tipo_busqueda != 'por-id' || $tipo_busqueda != 'por-id-persona'){
                            $data = $response;
                        }
                        //	si es por código o por-id o por-id-persona o por-documento
                        if ($tipo_busqueda == 'por-codigo' || $tipo_busqueda == 'por-id' || $tipo_busqueda == 'por-id-persona' || $tipo_busqueda == 'por-documento') {
                            
                            //	obtiene el primer registro
                            $data = (array)$response[0];

                            if($data['fec_hasta'] != null){
                                $hoy = Carbon::now();
                                $d = explode('/', $data['fec_hasta']);
                                $fec_hasta = Carbon::parse($d[2], ($d[1] -1), $d[0]);
                                if($fec_hasta->gt($hoy)){
                                    $data['is_fec_hasta_vencida'] = true;
                                }else{
                                    $data['is_fec_hasta_vencida'] = false;
                                }
                            }else{
                                $data['is_fec_hasta_vencida'] = false;
                            }
                            array_push($extras, ['is_fec_hasta_vencida' => $data['is_fec_hasta_vencida']]);
    
                            //  establece el ID de la persona
                            $id_persona = FALSE;
                            if (!empty($data['id_persona'])){
                                $id_persona = $data['id_persona'];
                            }
                            if (!empty($data['PerId'])){
                                $id_persona = $data['PerId'];
                            }
    
                            //  obtiene el nombre del empleador
                            if ($id_persona){
                                $params_empleador = [
                                    'p_id_persona' => $id_persona, 
                                    'p_activos' => 1
                                ];
                                array_push($extras['sps'], ['sp_empleado_Select' => $params_empleador]);
                                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_empleado_Select', $params_empleador));
                                $empleador = $this->ejecutar_sp_directo('afiliacion', 'sp_empleado_Select', $params_empleador);
                                array_push($extras['responses'], ['sp_empleado_Select' => $empleador]);
                                if($empleador){
                                    $data['empleador'] = $empleador;
                                }

                                $params_persona = [
                                    'p_id_persona' => $id_persona
                                ];
                                array_push($extras['sps'], ['sp_persona_Select' => $params_persona]);
                                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_persona_Select', $params_persona));
                                $persona = $this->ejecutar_sp_directo('afiliacion', 'sp_persona_Select', $params_persona);
                                array_push($extras['responses'], ['sp_persona_Select' => $persona]);
                                if($persona){
                                    if(is_array($persona)){
                                        $persona = $persona[0];
                                    }
                                    $data['persona'] = $persona;
                                    $data['fec_vigencia'] = Carbon::parse($persona->fec_vigencia)->format('d/m/Y');
                                    $data['fec_carga'] = Carbon::parse($persona->fec_carga)->format('d/m/Y');
                                    $data['id_rango'] = $persona->id_rango;
                                    $data['situacion'] = $persona->situacion;
                                }
                            }
                            
                            if($data['id_grupo'] != null){
                                $params_grupo = [
                                    'p_id_grupo' => $data['id_grupo'],
                                    'p_activos' => 1
                                ];
                                array_push($extras['sps'], ['sp_grupo_afiliado_Select' => $params_grupo]);
                                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_grupo_afiliado_Select', $params_grupo));
                                $grupo = $this->ejecutar_sp_directo('afiliacion', 'sp_grupo_afiliado_Select', $params_grupo);
                                array_push($extras['responses'], ['sp_grupo_afiliado_Select' => $grupo]);
                                $data['grupo_familiar'] = $grupo;
                            }
                            //	establece los datos particulares
                            $data['codigo'] = $data['n_afiliado'];
                            $data['numero_afiliado'] = $data['n_afiliado'];
                            $data['nombre_afiliado'] = $data['n_persona'];
                            $data['documento'] = $data['abrev_tipo_doc'] . ' ' . $data['nro_doc'];
                            $data['estado'] = (empty($data['activo']) || round(trim($data['activo'])) == 0) ? 2 : 1;
                            // $data['fec_nac'] = !empty($data['fec_nac']) ? Carbon::parse($data['fec_nac'])->format('d/m/Y') : '';
                            $data['vto_certificado'] = !empty($data['vto_certificado']) ? Carbon::parse($data['vto_certificado'])->format('d/m/Y') : '';
    
                            $data['vto_certificado_js'] = '';
                            if (!empty($data['vto_certificado'])){
                                $data['vto_certificado_js'] = Carbon::parse($data['vto_certificado'])->format('Y d m');
                            }
                        }
                        // obtiene el avatar 
                        if (isset($response[0]->foto) && Storage::disk('avatars_externo')->exists($response[0]->foto)) {
                            $ext = explode('.', $response[0]->foto);
                            $response[0]->n_foto = $response[0]->foto;
                            $response[0]->foto = 'data:image/' . $ext[1] . ';base64,' . base64_encode(file_get_contents(env('AVATARS_PATH').DIRECTORY_SEPARATOR.($response[0]->foto)));
                            $data->n_foto = $response[0]->n_foto;
                            $data->foto = $response[0]->foto;
                        }

                        // busca si el afiliado tiene internacion activa
                        if($tipo_busqueda == 'por-codigo' && $buscar_internacion_abierta){
                            
                            $params['afiliado'] = $response[0]->n_afiliado;
                            $params['id_empresa'] = $response[0]->id_empresa;
                            array_push($extras['sps'], ['AWEB_TraerAfiliado' => $params]);
                            array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerAfiliado', $params));
                            $i = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerAfiliado', $params);
                            array_push($extras['responses'], ['AWEB_TraerAfiliado' => $i]);
                            if($i[0]->codigo_internacion != null){
                                $fecha_ingreso_internacion = get_carbon_ymd($i[0]->fecha_ingreso_internacion);
                                $fecha_probable_egreso_internacion = get_carbon_ymd($i[0]->fecha_probable_egreso);
                                if ($i[0]->prioridad_internacion == 'urgente' || ($i[0]->prioridad_internacion == 'programada' && $fecha_ingreso_internacion->lte(Carbon::now()))){
                                    $abierta = 1;
                                }
                                if($i[0]->tiene_insumos == 1){
                                    $abierta = 1;
                                } 
                                // $data["codigo_internacion"] = $i[0]->codigo_internacion;
                                $data['internacion_abierta'] = $i[0];
                                // [
                                //     'codigo_internacion' => $i[0]->codigo_internacion,
                                //     'fecha_ingreso' => $fecha_ingreso_internacion,
                                //     'fecha_probable_egreso' => $fecha_probable_egreso_internacion,
                                //     'prioridad' => $i[0]->prioridad_internacion,
                                //     'abierta' => $abierta,
                                //     'tipo_internacion' => $i[0]->tipo_internacion,
                                //     'id_tipo_internacion' => $i[0]->id_tipo_internacion,
                                //     'tiene_insumos' => $i[0]->tiene_insumos,
                                //     'afiliado' => $i[0]
                                // ];
                            }else{
                                $data['internacion_abierta'] = null;
                            }
                        }

                        
                    }else{
                        $status = 'empty';
                        $data = null;
                        $count = 0;
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                    }
                } catch (\Throwable $th) {
                    array_push($errors, $th->getMessage());
                    return response()->json([
                        'status' => $status,
                        'count' => $count,
                        'errors' => $errors,
                        'line' => $th->getLine(),
                        'code' => $th->getCode(),
                        'message' => 'Se produjo un error al realizar la petición.',
                        'data' => $data,
                        'params' => $this->params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ]);
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para BUSCAR AFILIADO';
                $count  = 0;
                $data = null;
                $error = 'Error de permisos';
            }
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
        } catch (\Throwable $th) {
            array_push($errors, 'Error de Backend');
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => $this->params,
                'extras' => $extras,
                'logged_user' => null,
            ]);
        }
    }
}
