<?php

namespace App\Http\Controllers\Internos\Afiliaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\File as FacadeFile;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use File;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;

class AfiliadoController extends ConexionSpController
{
    
    /**
     * Busca un afiliado a través del sp_afiliado_Select
     */
    public function buscar_afiliado(Request $request)
    {
        try {
            // variables de respuesta
            $extras = [
                'api_software_version' => config('site.software_version'),
                'ambiente' => config('site.ambiente'),
                'url' => '/int/afiliaciones/afiliado/buscar-afiliado',
                'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
                'function' => __FUNCTION__,
                'sps' => [],
                'responses' => [],
                'queries' => []
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
                    $id_estado = request('id_estado');
                    $id_plan = request('id_plan');
                    $id_convenio = request('id_convenio');
                    $id_empresa = request('id_empresa');
                    $id_grupo = request('id_grupo');
                    $hst = request('historico') != null ? request('historico') : 0;
                    $buscar_internacion_abierta = request('buscar_internacion_abierta') != null ? filter_var(request('buscar_internacion_abierta'), FILTER_VALIDATE_BOOLEAN) : false;
                    
                    $params_sp = [];

                    $this->params['buscar_internacion_abierta'] = $buscar_internacion_abierta;
                    $this->params['tipo_busqueda'] = $tipo_busqueda;

                    if ($nro_afiliado != '' && $nro_afiliado != 'null'){
                        $this->params['nro_afiliado'] = $nro_afiliado;
                        $params_sp['nro_afiliado'] = $nro_afiliado;
                    }
                    if ($codigo != '' && $codigo != 'null'){
                        $this->params['nro_afiliado'] = $codigo;
                        $params_sp['nro_afiliado'] = $codigo;
                    }
                    if ($id_afiliado != '' && $id_afiliado != 'null'){
                        $this->params['p_id_afiliado'] = intval($id_afiliado);
                        $params_sp['p_id_afiliado'] = intval($id_afiliado);
                    }
                    if ($id_persona != '' && $id_persona != 'null'){
                        $this->params['id_persona'] = intval($id_persona);
                        $params_sp['id_persona'] = intval($id_persona);
                    }
                    if ($nombre != '' && $nombre != 'null'){
                        $this->params['nombre'] = $nombre;
                        $params_sp['nombre'] = $nombre;
                    }
                    if ($id_tipo_documento != '' && $id_tipo_documento != 'null'){
                        $this->params['id_tipo_doc'] = intval($id_tipo_documento);
                        $params_sp['id_tipo_doc'] = intval($id_tipo_documento);
                    }
                    if ($numero_documento != '' && $numero_documento != 'null'){
                        $this->params['nro_doc'] = $numero_documento;
                        $params_sp['nro_doc'] = $numero_documento;
                    }
                    if ($estado != '' && $estado != 'null'){
                        $this->params['estado'] = $estado;
                        $params_sp['estado'] = $estado;
                    }
                    if ($id_plan != '' && $id_plan != 'null'){
                        $this->params['id_plan'] = intval($id_plan);
                        $params_sp['id_plan'] = intval($id_plan);
                    }
                    if ($id_convenio != '' && $id_convenio != 'null'){
                        $this->params['id_convenio'] = intval($id_convenio);
                        $params_sp['id_convenio'] = intval($id_convenio);
                    }
                    if ($id_empresa != '' && $id_empresa != 'null'){
                        $this->params['id_empresa'] = intval($id_empresa);
                        $params_sp['id_empresa'] = intval($id_empresa);
                    }
                    if ($id_grupo != '' && $id_grupo != 'null'){
                        $this->params['p_id_grupo'] = intval($id_grupo);
                        $params_sp['p_id_grupo'] = intval($id_grupo);
                    }
                    if ($id_estado != '' && $id_estado != 'null' && $id_estado != null){
                        $this->params['id_estado'] = intval($id_estado);
                        $params_sp['p_id_estado'] = intval($id_estado);
                    }else{
                        $this->params['id_estado'] = null;
                        $params_sp['p_id_estado'] = null;
                    }
                    
                    // $this->params['p_hst'] = $hst;
                    // $params_sp['p_hst'] = $hst;

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
                                    // $data['abcdsefs'] = $persona->id_rango;
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
                        if ($tipo_busqueda == 'por-codigo' || $tipo_busqueda == 'por-id' || $tipo_busqueda == 'por-id-persona' || $tipo_busqueda == 'por-documento') {
                            if (isset($response[0]->foto) && Storage::disk('avatars_externo')->exists($response[0]->foto)) {
                                $ext = explode('.', $response[0]->foto);
                                $response[0]->n_foto = $response[0]->foto;
                                $response[0]->foto = 'data:image/' . $ext[1] . ';base64,' . base64_encode(file_get_contents(env('AVATARS_PATH').DIRECTORY_SEPARATOR.($response[0]->foto)));
                                $data['n_foto'] = $response[0]->n_foto;
                                $data['foto'] = $response[0]->foto;
                            }
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

    /**
     * Agrega un afiliado
     */
    public function agregar_afiliado(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/afiliaciones/afiliado/agregar-afiliado',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'verificado' => [],
            'queries' => []
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
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

        try {

            //  variables de control
            $grupo_ok = TRUE;
            $afiliado_ok = FALSE;
            $titular_ok = TRUE;
            $beneficiario_ok = TRUE;
            $grupo_plan_ok = TRUE;

            //	obtiene las variables
            $id_persona = request('id_persona');
            $afiliado = request('afiliado');
            $carga_simplificada = request('simple') == 1 ? TRUE : FALSE;

            $this->params = [
                'id_persona' => $id_persona,
                'afiliado' => $afiliado,
                'simple' => $carga_simplificada
            ];

            $extras['verificado'] = [
                'afiliado' => $afiliado,
                'carga_simplificada' => $carga_simplificada,
                'id_parentesco' => $afiliado['id_parentesco'],
                'id_persona' => $id_persona,
                'n_afiliado' => $afiliado['n_afiliado'],
            ];
            //	datos obligatorios
            if (empty($id_persona) 
                || empty($afiliado) 
                || empty($afiliado['n_afiliado']) 
                || (!$carga_simplificada && (empty($afiliado['id_parentesco']))) || ($carga_simplificada))
                {
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => ['Parámetros incorrectos o incompletos.'],
                    'message' => 'Verifique los parámetros',
                    'line' => null,
                    'code' => null,
                    'data' => null,
                    'params' => $this->params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }

            // return response()->json([
            //     'status' => 'ok',
            //     'count' => $count,
            //     'errors' => $errors,
            //     'message' => $message,
            //     'line' => null,
            //     'code' => null,
            //     'data' => $afiliado,
            //     'params' => $this->params,
            //     'extras' => $extras,
            //     'logged_user' => $logged_user,
            // ]);

            get_begin_transaction('afiliacion');

            // cuando es un alta de beneficiario debe ir en 1   
            // cuando es un alta de titular debe ir en 0
            if (!empty($afiliado['id_grupo']) && $afiliado['esAltaFamiliar'] == 1) {
                array_push($extras['responses'], ['id_grupo' => $afiliado['id_grupo']]);
                $ret['id_grupo'] = $afiliado['id_grupo'];
            } else {
                $grupo_ok = FALSE;
                $params_grupo = [
                    'id_promotor' => empty($afiliado['id_promotor']) ? NULL : $afiliado['id_promotor'],
                    'id_usuario' => $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default,
                    'fecha' => Carbon::now()->format('Ymd H:i:s')
                ];
                array_push($extras['sps'], ['sp_grupo_Insert' => $params_grupo]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_grupo_Insert', $params_grupo));
                $ret_grupo = $this->ejecutar_sp_directo('afiliacion', 'sp_grupo_Insert', $params_grupo);
                array_push($extras['responses'], ['sp_grupo_Insert' => $ret_grupo]);
                if (!empty($ret_grupo) && !empty($ret_grupo[0]) && !empty($ret_grupo[0]->id)) {
                    $grupo_ok = TRUE;
                    $ret['id_grupo'] = $ret_grupo[0]->id;
                }
            }
            $params_afiliado = [
                'id_afiliado' => NULL,
                'id_persona' => $id_persona,
                'n_afiliado' => $afiliado['n_afiliado'],
                'id_usuario' => $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default,
                'fecha' => Carbon::now()->format('Ymd H:i:s')
            ];
            array_push($extras['sps'], ['sp_afiliado_Insert' => $params_afiliado]);
            array_push($extras['queries'], $this->get_query('afiliacion', 'sp_afiliado_Insert', $params_afiliado));
            $ret_afiliado = $this->ejecutar_sp_directo('afiliacion', 'sp_afiliado_Insert', $params_afiliado);
            array_push($extras['responses'], ['sp_afiliado_Insert' => $ret_afiliado]);
            // if(!empty($ret_afiliado) && is_array($ret_afiliado)){
            //     $ret_afiliado = $ret_afiliado[0];
            // }
            // get_rollback('afiliacion');
            // return $ret_afiliado;
            if (isset($ret_afiliado[0]->id) && $ret_afiliado[0]->id > 0) {
                $afiliado_ok = TRUE;
                $ret['id_afiliado'] = $ret_afiliado[0]->id;
                $data = $ret_afiliado[0]->id;
                $status = 'ok';
                $count = 1;
                // cuando esAltaFamiliar es 0 el afiliado es titular
                // sino es un benefifiario
                if ($afiliado['esAltaFamiliar'] == 0) {
                    $titular_ok = FALSE;
                    $params_titular = [
                        'id_persona' => $id_persona,
                        'id_grupo' => $ret['id_grupo'],
                        'id_parentesco' => $carga_simplificada ? $parentesco['id_parentesco'] : $afiliado['id_parentesco'],
                        'id_usuario' => $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default,
                        'fecha' => Carbon::now()->format('Ymd H:i:s')
                    ];
                    array_push($extras['sps'], ['sp_titular_Insert' => $params_titular]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_titular_Insert', $params_titular));
                    $ret_titular = $this->ejecutar_sp_directo('afiliacion', 'sp_titular_Insert', $params_titular);
                    array_push($extras['responses'], ['sp_titular_Insert' => $ret_titular]);
                    if (!empty($ret_titular) && !empty($ret_titular[0]) && !empty($ret_titular[0]->id)) {
                        $titular_ok = TRUE;
                        $ret['id_titular'] = $ret_titular[0]->id;
                    }
                } else {
                    $beneficiario_ok = FALSE;
                    $params_beneficiario = [
                        'id_persona' => $id_persona,
                        'id_grupo' => $ret['id_grupo'],
                        'id_parentesco' => $afiliado['id_parentesco'],
                        'id_usuario' => $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default,
                        'fecha' => Carbon::now()->format('Ymd H:i:s')
                    ];
                    array_push($extras['sps'], ['sp_beneficiario_Insert' => $params_beneficiario]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_beneficiario_Insert', $params_beneficiario));
                    $ret_beneficiario = $this->ejecutar_sp_directo('afiliacion', 'sp_beneficiario_Insert', $params_beneficiario);
                    array_push($extras['responses'], ['sp_beneficiario_Insert' => $ret_beneficiario]);
                    if (!empty($ret_beneficiario) && !empty($ret_beneficiario[0]) && !empty($ret_beneficiario[0]->id)) {
                        $beneficiario_ok = TRUE;
                        $ret['id_beneficiario'] = $ret_beneficiario[0]->id;
                    }
                }
            }

            if ($grupo_ok && !empty($afiliado['id_plan'])) {
                $params_grupo_plan = [
                    'id_grupo' => $ret['id_grupo'],
                    'id_plan' => $afiliado['id_plan'],
                    'id_vigencia' => NULL,
                    'id_usuario' => $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default,
                    'fecha' => Carbon::now()->format('Ymd H:i:s')
                ];
                array_push($extras['sps'], ['sp_grupo_plan_Insert' => $params_grupo_plan]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_grupo_plan_Insert', $params_grupo_plan));
                $ret_plan = $this->ejecutar_sp_directo('afiliacion', 'sp_grupo_plan_Insert', $params_grupo_plan);
                array_push($extras['responses'], ['sp_grupo_plan_Insert' => $ret_plan]);
                if (!empty($ret_plan) && !empty($ret_plan[0]) && !empty($ret_plan[0]->id)) {
                    $grupo_plan_ok = TRUE;
                    $ret['id_grupo_plan'] = $ret_plan[0]->id;
                }
            }
            array_push($extras['responses'], 
                ['respuestas' => [
                        'grupo_ok' => $grupo_ok,
                        'afiliado_ok' => $afiliado_ok,
                        'titular_ok' => $titular_ok,
                        'beneficiario_ok' => $beneficiario_ok,
                        'grupo_plan_ok' => $grupo_plan_ok
                    ]
                ]
            );
            if ($grupo_ok && $afiliado_ok && $titular_ok && $beneficiario_ok && $grupo_plan_ok) {
                get_commit('afiliacion');

                $ret['code'] = 1;
                $ret['message'] = 'El afiliado se insertó exitosamente';
                $message = 'El Afiliado se insertó exitosamente';
                $count = 1;
                array_push($extras['responses'], ['response_general' => $ret]);
            } else {
                get_rollback('afiliacion');
                array_push($errors, 'No se pudo insertar el afiliado. Err: -2');
                $status = 'fail';
                $message = 'No se pudo insertar el Afiliado.';
                $count = 0;
                $ret['code'] = -2;
                if ($ret_afiliado[0]->id <= 0){
                    $ret['message'] = 'El número ya existe.';
                }else{
                    $ret['message'] = 'No se pudo insertar el afiliado';
                }
                array_push($extras['responses'], ['response_2' => $ret]);
            }

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
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
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
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Actualiza los datos de un afiliado
     */
    public function actualizar_afiliado(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/afiliaciones/afiliado/actualizar-afiliado',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'verificado' => [],
            'queries' => []
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
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            //  variables de control
            $afiliado_ok = TRUE;
            $promotor_ok = TRUE;
            $plan_ok = TRUE;
            $titular_ok = TRUE;
            $beneficiario_ok = TRUE;
            $baja_afiliado_ok = TRUE;
            $baja_plan_ok = TRUE;
            $baja_titular_ok = TRUE;
            $baja_beneficiario_ok = TRUE;

            //  variables para las bajas
            $fecha_baja = Carbon::now();

            //	obtiene las variables
            $id_persona = request('id_persona');
            $afiliado = request('afiliado');
            $carga_simplificada = request('simple') == 1 ? TRUE : FALSE;
            $this->params = [
                'id_persona' => $id_persona,
                'afiliado' => $afiliado,
                'simple' => $carga_simplificada
            ];

            $extras['verificado'] = [
                'afiliado' => $afiliado,
                'carga_simplificada' => $carga_simplificada,
                'id_parentesco' => $afiliado['id_parentesco'],
                'id_persona' => $id_persona,
                'id_plan' => $afiliado['id_plan'],
                'id_promotor' => $afiliado['id_promotor'],
                'n_afiliado' => $afiliado['n_afiliado'],
            ];
            //	datos obligatorios
            if(empty($id_persona)
                || empty($afiliado)
                || empty($afiliado['n_afiliado'])
                || (!$carga_simplificada
                    && (empty($afiliado['id_parentesco']) || empty($afiliado['id_promotor']))
                )
                || ($carga_simplificada && empty($afiliado['id_plan']))
                ){
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => ['Parámetros incorrectos o incompletos.'],
                    'message' => 'Verifique los parámetros',
                    'line' => null,
                    'code' => -1,
                    'data' => null,
                    'params' => $this->params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }

            //  obtiene el parentesco
            if($carga_simplificada){
                $param_parentesco = ['p_n_parentesco' => 'titular'];
                array_push($extras['sps'], ['sp_parentesco_Select' => $param_parentesco]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_parentesco_Select', $params_parentesco));
                $parentesco = $this->ejecutar_sp_directo('afiliacion', 'sp_parentesco_Select', $param_parentesco);
                array_push($extras['responses'], ['sp_parentesco_Select' => $parentesco]);
            }else{
                $param_parentesco = ['p_id_parentesco' => $afiliado['id_parentesco']];
                array_push($extras['sps'], ['sp_parentesco_Select' => $param_parentesco]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_parentesco_Select', $param_parentesco));
                $parentesco = $this->ejecutar_sp_directo('afiliacion', 'sp_parentesco_Select', $param_parentesco);
                array_push($extras['responses'], ['sp_parentesco_Select' => $parentesco]);
            }
            //	datos obligatorios
            if (empty($parentesco[0]) || empty($parentesco[0]->n_parentesco)){
                $status = 'fail';
                $count = 0;
                $errors = ['Parámetros incorrectos o incompletos.', 'No existe el parentesco'];
                $message = 'No se pudo actualizar el afiliado.';
                $extras['verificado'] = [
                    'parentesco[0]' => $parentesco[0],
                    'parentesco[0]->n_parentesco' => $parentesco[0]->n_parentesco
                ];
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => 'Verifique los parámetros',
                    'line' => null,
                    'code' => -2,
                    'data' => null,
                    'params' => $this->params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }
            //  obtiene los datos actuales
            //  si es titular
            if (strtolower($parentesco[0]->n_parentesco) == 'titular') {
                //  cambia flag
                $es_titular = TRUE;
            } else {
                //  cambia flag
                $es_titular = FALSE;
            }

            //  inicia transaccion
            get_begin_transaction('afiliacion');

            //  si es titular, el formulario no es el simplicado y el promotor actual no es igual al nuevo
            if ($es_titular && !$carga_simplificada) {
                //	actualiza el grupo
                $params_grupo = [
                    'p_id_grupo' => $afiliado['id_grupo'],
                    'p_id_promotor' => empty($afiliado['id_promotor']) ? NULL : $afiliado['id_promotor'],
                ];
                array_push($extras['sps'], ['sp_grupo_update' => $params_grupo]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_grupo_update', $params_grupo));
                $ret_actualiza_grupo = $this->ejecutar_sp_directo('afiliacion', 'sp_grupo_update', $params_grupo);
                array_push($extras['responses'], ['sp_grupo_update' => $ret_actualiza_grupo]);

                //  si hay error al actualizar el grupo
                if (empty($ret_actualiza_grupo) || empty($ret_actualiza_grupo[0]) || empty($ret_actualiza_grupo[0]->filas)){
                    $promotor_ok = FALSE;
                }
            }

            //  si pudo guardar
            array_push($extras['responses'], [
                'afiliado' => $afiliado_ok,
                'promotor' => $promotor_ok,
                'plan' => $plan_ok,
                'titular' => $titular_ok,
                'beneficiario' => $beneficiario_ok,
                'baja_afiliado' => $baja_afiliado_ok,
                'baja_plan' => $baja_plan_ok,
                'baja_titular' => $baja_titular_ok,
                'baja_beneficiario' => $baja_beneficiario_ok
            ]);
            $data = [
                'afiliado' => $afiliado_ok ? 'Actualizado' : null,
                'promotor' => $promotor_ok ? 'Actualizado' : null,
                'plan' => $plan_ok ? 'Actualizado' : null,
                'titular' => $titular_ok ? 'Actualizado' : null,
                'beneficiario' => $beneficiario_ok ? 'Actualizado' : null,
                'id_afiliado' => $afiliado['id_afiliado']
            ];
            if ($afiliado_ok 
                && $promotor_ok 
                && $plan_ok 
                && $titular_ok
                && $beneficiario_ok 
                && $baja_afiliado_ok 
                && $baja_plan_ok 
                && $baja_titular_ok 
                && $baja_beneficiario_ok
                ) {
                get_commit('afiliacion');
                $status = 'ok';
                $count = 1;
                $message = 'Afiliado actualizado exitosamente.';
            } else {
                get_rollback('afiliacion');
                $status = 'fail';
                $count = 0;
                array_push($errors, 'error actualizando afiliado');
                //  establece code y mensaje de retorno
                $ret['code'] = -79014;
                if ($ret_afiliado['id'] <= 0){
                    $message = 'El número ya existe';
                } else {
                    $message = 'No se pudo actualizar el afiliado';
                }
            }
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
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
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
                'logged_user' => $logged_user,
            ]);
        }

    }

    /**
     * Cambia la clave de usuario de un afiliado mobile
     */
    public function cambiar_password_afiliado_mobile(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/afiliaciones/afiliado/cambiar-password-afiliado-mobile',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        try{
            $usuario = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($usuario);

            $affected = false;
            $actualizar = true;
            $sqlserver_actualizado = false;
            // variables de respuesta
            $status = 'fail';
            $message = 'Error. No se encontraron registros.';
            $count = 0;
            $code = null;
            $data = [
                'usuario' => null,
                'api_bpay_actualizado' => $affected,
                'sqlserver_actualizado' => $sqlserver_actualizado
            ];
            $errors = [];
            


            // return response()->json(['params' => $params]);
            $password = request('password') !== null ? request('password') : 'abcd1234';
            $id_usuario_sqlserver = request('id_usuario_sqlserver');
            $sincronizar = request('sincronizar') !== null ? boolval(request('sincronizar')) : true;

            $params = [
                'id_usuario_sqlserver' => request('id_usuario_sqlserver'),
                'password' => request('password'),
                'sincronizar' => request('sincronizar')
            ];

            if($usuario->hasPermissionTo('blanquear clave mobile')){ 
                if(!isset($id_usuario_sqlserver)){
                    $status = 'fail';
                    $message = 'Error. El parámetro id_usuario_sqlserver es obligatorio.';
                    $code = -5;
                    $errors =  ['Parámetros insuficientes o incorrectos'];
                }else if(!isset($password)){
                    $status = 'fail';
                    $message = 'Error. El campo password no puede estar vacío.';
                    $code = -6;
                    $errors =  ['Parámetros insuficientes o incorrectos'];
                }else{
                    try {
                        $users = User::with('roles', 'permissions')->where('id_usuario_sqlserver', $id_usuario_sqlserver)->get();
                        $user_encontrado = null;
                        if(!empty($users) && count($users) > 0){
                            // Buscar el primer usuario con rol 'afiliado mobile'
                            foreach($users as $u){
                                $extras['has_role'] = $u->hasRole('afiliado mobile');
                                if($u->hasRole('afiliado mobile')){
                                    $user_encontrado = $u;
                                    break;
                                }
                            }
                        }
                        $extras['usuario_encontrado'] = $user_encontrado;
                        $extras['users'] = $users;
                        
                        if($user_encontrado != null){
                            if($sincronizar && $id_usuario_sqlserver != null ){
                                //  define los parametros a actualizar en sqlserver
                                $parametros = [
                                    'p_id_usuario' => $id_usuario_sqlserver,
                                    'p_apynom' => $user_encontrado->name,
                                    'p_n_usuario' => $user_encontrado->usuario,  
                                    'p_email' => $user_encontrado->email,
                                    'p_activo' => 1,
                                    'p_id_area' => NULL,
                                    'p_supervisor' => NULL,
                                    'p_desactivar' => NULL,
                                    'p_activar' => NULL,
                                    'p_bloqueado' => 0,
                                    'p_finaliza_clave' => NULL,
                                    'p_clave_nueva' => $password,
                                    'p_id_usuario_envia' => $logged_user['id'],
                                    'p_atributos' => NULL,
                                ];
                                //  actualiza los datos del usuario
                                array_push($extras['sps'], ['sp_usuario_Update' => $parametros]);
                                array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_Update', $parametros));
                                $ret = $this->ejecutar_sp_directo('admin', 'sp_usuario_Update', $parametros);
                                array_push($extras['responses'], ['sp_usuario_Update' => $ret]);
                                // $ret = $this->ejecutar_sp_directo('admin', 'sp_usuario_Update', $parametros);
                                if ( !empty($ret) && !empty($ret[0]) && $ret[0]->filas > 0 ){
                                    // si se actualizó correctamente cambia la bandera
                                    $sqlserver_actualizado = true;
                                }else{ 
                                    // sino modifica la bandera para que no se realice el cambio en la base de datos
                                    $actualizar = false;
                                }
                                // si se puede actualizar, ya sea porque se actualizó sqlserver o porque el usuario no está en sqlserver
                                if($actualizar && $user_encontrado != null){
                                    $user_encontrado->password = Hash::make($password);
                                    $affected = $user_encontrado->save();
                                    array_push($extras['responses'], ['api_bpay' => $affected]);
                                }
                            }else if($user_encontrado != null){
                                $user_encontrado->password = Hash::make($password);
                                $affected = $user_encontrado->save();
                                array_push($extras['responses'], ['api_bpay' => $affected]);
                            }

                            if($affected){
                                $status = 'ok';
                                $message = 'Contraseña reseteada';
                                $count = 1;
                                $code = 1;
                                $data = [
                                    'usuarios' => $users,
                                    'usuario' => $user_encontrado,
                                    'api_bpay_actualizado' => $affected,
                                    'sqlserver_actualizado' => $sqlserver_actualizado
                                ];
                            }else{
                                $status = 'fail';
                                $message = 'No se pudo resetear la contraseña.';
                                $count = 0;
                                $code = -4;
                                $data = [
                                    'usuarios' => $users,
                                    'usuario' => null,
                                    'api_bpay_actualizado' => $affected,
                                    'sqlserver_actualizado' => $sqlserver_actualizado
                                ];
                            }

                        }else{
                            $status = 'fail';
                            $errors = ['No se encontró un usuario con rol "afiliado mobile" para el id buscado.'];
                            $message = 'No se encontró un usuario con rol "afiliado mobile" para el id buscado.';
                            $count = 0;
                            $code = -7;
                            $data = [
                                'usuarios' => $users,
                                'usuario' => $user_encontrado,
                                'api_bpay_actualizado' => $affected,
                                'sqlserver_actualizado' => $sqlserver_actualizado
                            ];
                        }
                        
                    } catch (\Throwable $th) {
                        $status = 'fail';
                        $message = 'No se pudo resetear la contraseña';
                        array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
                        $code = -3;
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($usuario->roles[0]->name).' no tiene permiso. Se requiere permiso para RESETEAR PASSWORD';
                $code = -2;
                array_push($errors, 'Intento de acceso no autorizado');
            }
            // return $error;
            $extras['api_bpay_actualizado'] = $affected;
            $extras['sqlserver_actualizado'] = $sqlserver_actualizado;
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => $code,
                'params' => $params,
                'data' => $data,
                'extras' => $extras,
                'logged_user' => $logged_user
            ]);
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'params' => $params,
                'data' => $data,
                'extras' => $extras,
                'logged_user' => $logged_user
            ]);
        } 
    }

    /**
     * Cambia el número de afiliado
     */
    public function cambiar_numero(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/afiliaciones/afiliado/cambiar-numero-afiliado',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'verificado' => [],
            'queries' => []
        ];
        $status = 'fail';
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $params = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            $permiso_requerido = 'gestionar afiliados';
            if($user->hasPermissionTo($permiso_requerido)){
                $params = [
                    'n_afiliado' => request('n_afiliado'),
                    'n_afiliado_nuevo' => request('n_afiliado_nuevo'),
                    'id_persona' => request('id_persona'),
                    'id_usuario' => $id_usuario,   // no viene del front
                    'fec_carga' => Carbon::now()->format('Ymd'),  // no viene del front
                    'fec_baja' => request('fec_baja'),
                    'id_tipo_baja' => request('id_tipo_baja'),  // baja
                    'n_motivo' => request('n_motivo'),   // baja
                    'n_archivo' => request('n_archivo'),  // baja
                    'id_grupo' => request('id_grupo'),
                    'id_parentesco' => request('id_parentesco'),
                ];
                array_push($extras['sps'], ['sp_afiliado_Update' => $params]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_afiliado_Update', $params));
                $resp = $this->ejecutar_sp_directo('afiliacion','sp_afiliado_Update', $params);
                array_push($extras['responses'], ['sp_afiliado_Update' => $resp]);
                if(isset($resp['error'])){
                    array_push($errors, $resp['error']);
                    $data = null;
                    $status = 'fail';
                    $count = 0;
                    $message = 'Error en la consulta a la base de datos.';
                }else if(!empty($resp) && is_array($resp) && $resp[0]->resultado == 1){
                    $status = 'ok';
                    $data = $resp[0]->resultado;
                    $count = 1;
                    $code = 1;
                    $message = 'Registro guardado correctamente';
                }else{
                    $status = 'fail';
                    $data = null;
                    $count = 0;
                    $code = -2;
                    $message = 'No se pudo guardar el registro';
                    array_push($errors, 'Error en la ejecución de sp');
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
                array_push($errors, 'Error de permisos');
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
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Busca el hitorial de cambios del afiliado
     */
    public function historial_afiliado(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/afiliaciones/afiliado/historial-afiliado',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'verificado' => [],
            'queries' => []
        ];
        $status = 'fail';
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $params = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            $permiso_requerido = 'gestionar afiliados';
            if($user->hasPermissionTo($permiso_requerido)){
                $params = [
                    'id_persona' => request('id_persona')
                ];
                array_push($extras['sps'], ['sp_afiliado_historico_Select' => $params]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_afiliado_historico_Select', $params));
                $resp = $this->ejecutar_sp_directo('afiliacion','sp_afiliado_historico_Select', $params);
                array_push($extras['responses'], ['sp_afiliado_historico_Select' => $resp]);
                if(isset($resp['error'])){
                    array_push($errors, $resp['error']);
                    $data = null;
                    $status = 'fail';
                    $count = 0;
                    $message = 'Error en la consulta a la base de datos.';
                }else if(!empty($resp)){
                    $status = 'ok';
                    $data = $resp;
                    $count = sizeof($data);
                    $code = 1;
                    $message = '';
                }else{
                    $status = 'empty';
                    $data = $resp;
                    $count = 0;
                    $code = -2;
                    $message = 'No se encontraron registros';
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
                array_push($errors, 'Error de permisos');
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
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

}


