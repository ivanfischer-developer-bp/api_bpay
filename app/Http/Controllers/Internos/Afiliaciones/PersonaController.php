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

class PersonaController extends ConexionSpController
{

    /**
     * Busca una persona
     */
    public function buscar_persona(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/afiliaciones/persona/buscar',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];

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
        $usuario_sqlserver_default = 1;
        try {

            $buscar_en = request('buscar_en');
            $tipo_busqueda = request('tipo_busqueda');
            $id_persona = request('id_persona');
            $id_tipo_documento = request('id_tipo_documento');
            $numero_documento = request('numero_documento');
            $id_rol = request('id_rol');
            $codigo = request('codigo');
            $nombre = request('nombre');
            $estado = request('estado') == '' ? 1 : request('estado');
            $p_afiliado = request('p_afiliado') != null ? request('p_afiliado') : null;

            //	si hay codigo cambia el tipo de busqueda
            if ( $codigo != '' ) {
                $tipo_busqueda = 'por-codigo';
            }
            $the_params = [
                'buscar_en' => $buscar_en,
                'tipo_busqueda' => $tipo_busqueda,
                'id_persona' => $id_persona,
                'id_tipo_documento' => $id_tipo_documento,
                'numero_documento' => $numero_documento,
                'id_rol' => $id_rol,
                'codigo' => $codigo,
                'estado' => $estado,
                'p_afiliado' => $p_afiliado
            ];
            $filtros = [];

            //	establece los filtros
            if ( $id_persona != '' && is_numeric($id_persona) ){
                $filtros['p_id_persona'] = $id_persona;
            }
            if ( $id_tipo_documento != '' && is_numeric($id_tipo_documento) ){
                $filtros['p_id_tipo_doc'] = $id_tipo_documento;
            }
            if ( $numero_documento != '' && $numero_documento != '' ){
                $filtros['p_nro_doc'] = $numero_documento;
            }
            // agregado para personas.js
            if($nombre != ''){
                $filtros['p_n_persona'] = $nombre;
            }

            //	crea el objeto dependiendo del tipo de búsqueda
            if ( $buscar_en == '' || $buscar_en == 'personas' )
            {
                
                //	establece el filtro de estado
                //$filtros['estado'] = $estado;
                
                //	devuelve las personas
                array_push($extras['sps'], ['sp_persona_Select' => $filtros]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_persona_Select', $filtros));
                $personas = $this->ejecutar_sp_directo('afiliacion', 'sp_persona_Select', $filtros);
                array_push($extras['responses'], ['sp_persona_Select' => $personas]);
                if(isset($personas['error'])){
                    array_push($errors, $personas['error']);
                    $status = 'fail';
                    $count = 0;
                    $message = 'Error en la consulta a la base de datos.';
                }
                if ( $personas ){
                    // dd('buscando personas back',$personas);
                    //	si pasó persona
                    if ( !empty($id_persona) && $id_persona > 0 ) {
                        //	establece el valor de persona
                        $persona = (array)$personas[0];
                        
                        //	obtiene las relaciones necesarias
                        // if ( $actividad = $persona_obj->ejecutar_sp('sp_persona_actividad_Select', ['p_id_persona' => $id_persona, 'p_is_vigente' => 1], TRUE) ){
                        // 	$persona['id_actividad'] = $actividad['id_actividad'];
                        // }
                        // if ( $estado_civil = $persona_obj->ejecutar_sp('sp_persona_estado_civil_Select', ['p_id_persona' => $id_persona, 'p_is_vigente' => 1], TRUE) ){
                        // 	$persona['id_estado_civil'] = $estado_civil['id_estado_civil'];
                        // }

                        //	si es una empresa
                        if ( $persona['juridica'] == 1 ) {
                            array_push($extras['sps'], ['sp_empresa_select' => ['p_id_persona' => $persona['id_persona']]]);
                            array_push($extras['queries'], $this->get_query('afiliacion', 'sp_empresa_Select', ['p_id_persona' => $persona['id_persona']]));
                            $empresa = $this->ejecutar_sp_directo('afiliacion', 'sp_empresa_Select', ['p_id_persona' => $persona['id_persona']]);
                            array_push($extras['responses'], ['sp_empresa_select' => $empresa]);
                            if(isset($empresa['error'])){
                                array_push($errors, $empresa['error']);
                                $status = 'fail';
                                $count = 0;
                                $message = 'Error en la consulta a la base de datos.';
                            }
                            if ( $empresa ){
                                $persona['id_empresa'] = $empresa[0]->id_empresa;
                            }
                        }
                        $data = $persona;
                        $status = 'ok';
                        $count = 1;
                        $message = 'Un Registro encontrado';
                    }else{
                        //	devuelve el resultado
                        
                        foreach($personas as $persona){
                            if(is_array($persona)){
                                $persona['fec_nac'] = Carbon::parse($persona['fec_nac'])->format('d/m/Y');
                            }else{
                                $persona->fec_nac = Carbon::parse($persona->fec_nac)->format('d/m/Y');
                            }
                            // se agrega para traer el estado civil y la actividad de las personas ingresadas en expedientes;
                            // $actividad = $persona_obj->ejecutar_sp('sp_persona_actividad_Select', [
                            // 	'p_id_persona' => $persona->id_persona, 
                            // 	'p_is_vigente' => 1
                            // ], TRUE) ;
                            // $persona->id_actividad = $actividad != null ? $actividad['id_actividad'] : null;
                            // $persona->n_actividad = $actividad != null ? $actividad['n_actividad'] : null;
                            
                            // $estado_civil = $persona_obj->ejecutar_sp('sp_persona_estado_civil_Select', [
                            // 	'p_id_persona' => $persona->id_persona, 
                            // 	'p_is_vigente' => 1
                            // ], TRUE); 
                            // $persona->id_estado_civil = $estado_civil != null ? $estado_civil['id_estado_civil'] : null;
                            // $persona->n_estado_civil = $estado_civil != null ? $estado_civil['n_estado_civil'] : null;
                            // dd('$estado_civil', $estado_civil);
                        }
                        $data = $persona;
                        $status = 'ok';
                        $count = sizeof($personas);
                        $message = 'Registros encontrados';
                    }
                }else{
                    $status = 'empty';
                    $count = 0;
                    $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda.';
                }
            } elseif ( $buscar_en == 'involucrados' ){
                //	establece el filtro de rol
                $filtros['p_id_rol'] = $id_rol;

                //	dependiendo el valor, establece el filtro
                if ( $codigo != '' ){
                    $filtros['p_codigo'] = $codigo;
                }
                if ( $nombre != '' ){
                    $filtros['p_nombre'] = $nombre;
                }

                //  para mostrar (o no) los afiliados de baja, según permiso
                $filtros['p_is_baja'] = 0;
                if ( $user->hasPermissionTo('buscar en bajas') ){
                    $filtros['p_is_baja'] = 1;
                }
                array_push($extras['sps'], ['sp_rol_validacion' => $filtros]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_rol_validacion', $filtros));
                $involucrados = $this->ejecutar_sp_directo('afiliacion', 'sp_rol_validacion', $filtros);
                array_push($extras['responses'], ['sp_rol_validacion' => $involucrados]);
                if(isset($involucrados['error'])){
                    array_push($errors, $involucrados['error']);
                    $status = 'fail';
                    $count = 0;
                    $message = 'Error en la consulta a la base de datos.';
                }
                $status = 'ok';
                $count = sizeof($involucrados);
                $message = 'Registros encontrados';
                $data = $involucrados;
            }elseif ( $buscar_en == 'todos' ){
                //	dependiendo el valor, establece el filtro
                if ( !empty($codigo) ){
                    $filtros['p_codigo'] = $codigo;
                }
                if ( !empty($nombre) ){
                    $filtros['p_nombre'] = $nombre;
                }
                if ( !empty($estado) ){
                    $filtros['p_vigente'] = $estado;
                }
                array_push($extras['sps'], ['sp_persona_rol_Select' => $filtros]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_persona_rol_Select', $filtros));
                $todos = $this->ejecutar_sp_directo('afiliacion', 'sp_persona_rol_Select', $filtros);
                array_push($extras['responses'], ['sp_persona_rol_Select' => $todos]);
                if(isset($todos['error'])){
                    array_push($errors, $todos['error']);
                    $data = null;
                    $status = 'fail';
                    $count = 0;
                    $message = 'Error en la consulta a la base de datos.';
                }
                $status = 'ok';
                $count = sizeof($todos);
                $message = 'Registros encontrados';
                $data = $todos;
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
                'params' => $the_params,
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
                'params' => $the_params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Agregar una persona física o jurídica
     */
    public function agregar_persona(Request $request)
    {
    	$extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/afiliaciones/persona/agregar-persona',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'verificado' => [],
            'queries' => []
        ];

        $status = 'fail';
        $count = -1;
        $errors = [];
        $message = null;
        $line = null;
        $code = null;
        $data = null;
        try {
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;

            //	obtiene las variables
            $persona = $request->input('persona');
            $persona = (array) json_decode(json_encode($persona));
            $this->params = ['persona' => $persona];

            $son_empresas = ['aseguradora'];
            $persona_ok = FALSE;
            $empresa_ok = TRUE;
            $actividad_ok = TRUE;
            $estado_civil_ok = TRUE;
            $origen_ok = TRUE;

            //	datos obligatorios
            $seguir = false;
            // si no está seteado el nombre de la persona
            if(!isset($persona['n_persona']) || $persona['n_persona'] == ''){
                $seguir = false;
                $code = -3;
                $extras['verificado'] = [
                    'n_persona' => empty($persona['n_persona']) ? null : $persona['n_persona'],
                ];
            }else{
                // si no esta seteado persona->juridica 
                if(!isset($persona['juridica'])){
                    $seguir = false;
                    $code = -4;
                    $extras['verificado'] = [
                        'n_persona' => $persona['n_persona'],
                        'juridica' => !isset($persona['juridica']) ? null : $persona['juridica']
                    ];
                }else{
                    // si es persona juridica y no está seteado cuit
                    if($persona['juridica'] == 1 && ( !isset($persona['cuit']) || $persona['cuit'] == '' )){
                        $seguir = false;
                        $code = -5;
                        $extras['verificado'] = [
                            'n_persona' => $persona['n_persona'],
                            'jurídica' => empty($persona['juridica']) ? null : $persona['juridica'],
                            'cuit' => empty($persona['cuit']) ? null : $persona['cuit'],
                        ];
                    }else{
                        // si es persona física
                        if($persona['juridica'] == 0){
                            // si no está seteado tipo persona
                            if(!isset($persona['tipo'])){
                                $seguir = false;
                                $code = -6;
                                $extras['verificado'] = [
                                    'n_persona' => $persona['n_persona'],
                                    'jurídica' => $persona['juridica'],
                                    'tipo' => isset($persona['tipo']) ? $persona['tipo'] : 'no seteado',
                                ];
                            }else{
                                // si es afiliado
                                if($persona['tipo'] == 'afiliado'){
                                    $extras['verificado'] = [
                                        'n_persona' => $persona['n_persona'],
                                        'juridica' => $persona['juridica'],
                                        'tipo' => $persona['tipo'],
                                        'fec_ingreso' => empty($persona['fec_ingreso']) ? null : $persona['fec_ingreso'],
                                        'fec_vigencia' => empty($persona['fec_vigencia']) ? null : $persona['fec_vigencia'],
                                        'id_nacionalidad' =>  empty($persona['id_nacionalidad']) ? null : $persona['id_nacionalidad'],
                                        'fec_nac' => empty($persona['fec_nac']) ? null : $persona['fec_nac'],
                                        'id_tipo_doc' => empty($persona['id_tipo_doc']) ? null : $persona['id_tipo_doc'],
                                        'nro_doc' => empty($persona['nro_doc']) ? null : $persona['nro_doc'],
                                        'sexo' => empty($persona['sexo']) ? null : $persona['sexo'],
                                        'id_tipo_facturacion' => empty($persona['id_tipo_facturacion']) ? null : $persona['id_tipo_facturacion'],
                                        'id_afip' => empty($persona['id_afip']) ? null : $persona['id_afip'],
                                        'id_plan' => empty($persona['id_plan']) ? null : $persona['id_plan'],
                                        'id_parentesco' => empty($persona['id_parentesco']) ? null : $persona['id_parentesco'],
                                        'n_afiliado' => empty($persona['n_afiliado']) ? null : $persona['n_afiliado'],
                                    ];
                                    // si no están seteados los campos obligatorios
                                    if ( ( !isset($persona['fec_ingreso']) || $persona['fec_ingreso'] == '' )
                                        || ( !isset($persona['fec_vigencia']) || $persona['fec_vigencia'] == '' )
                                        || ( !isset($persona['id_nacionalidad']) || $persona['id_nacionalidad'] == '' )
                                        || ( !isset($persona['fec_nac']) || $persona['fec_nac'] == '' )
                                        || ( !isset($persona['id_tipo_doc']) || $persona['id_tipo_doc'] == '' )
                                        || ( !isset($persona['nro_doc']) || $persona['nro_doc'] == '' )
                                        || ( !isset($persona['sexo']) || $persona['sexo'] == '' )
                                        || ( !isset($persona['id_tipo_facturacion']) || $persona['id_tipo_facturacion'] == '')
                                        || ( !isset($persona['id_afip']) || $persona['id_afip'] == '')
                                        || ( !isset($persona['id_plan']) || $persona['id_plan'] == '')
                                        || ( !isset($persona['id_parentesco']) || $persona['id_parentesco'] == '')
                                        || ( !isset($persona['n_afiliado']) || $persona['n_afiliado'] == '')
                                    ){
                                        $seguir = false;
                                        $code = -7;
                                    }else{
                                        $seguir = true;
                                        $code = 2;
                                    }
                                }else{ // promotor u otro tipo
                                    // si no es afiliado
                                    $seguir = true;
                                    $code = 3;
                                    $extras['verificado'] = [
                                        'n_persona' => $persona['n_persona'],
                                        'jurídica' => $persona['juridica'],
                                        'tipo' => $persona['tipo']
                                    ];
                                }
                            }
                        }
                    }
                    
                }
            }

            if(!$seguir){
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => ['Parámetros incorrectos o incompletos.'],
                    'message' => 'Verifique los parámetros',
                    'line' => null,
                    'code' => $code,
                    'data' => null,
                    'params' => $this->params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]);  
            }

            // $extras['verificado'] = [
            //     'persona_n_persona' => $persona['n_persona'],
            //     'persona_juridica' => $persona['juridica'],
            //     'persona_fec_ingreso' => $persona['fec_ingreso'],
            //     'persona_fec_vigencia' => $persona['fec_vigencia'],
            //     'persona_id_nacionalidad' =>  $persona['id_nacionalidad'],
            //     'persona_fec_nac' => $persona['fec_nac'],
            //     'persona_id_tipo_doc' => $persona['id_tipo_doc'],
            //     'persona_nro_doc' => $persona['nro_doc'],
            //     'persona_sexo' => $persona['sexo'],
            //     'persona_id_tipo_facturacion' => $persona['id_tipo_facturacion'],
            //     'persona_id_afip' => $persona['id_afip'],
            //     'persona_id_plan' => $persona['id_plan'],
            //     'persona_id_parentesco' => $persona['id_parentesco'],
            //     'persona_n_afiliado' => $persona['n_afiliado'],
            //     'persona_cuit' => $persona['cuit'],
            // ];
            // //	datos obligatorios
            // if (( !isset($persona['n_persona']) || $persona['n_persona'] == '' )
            //     || ( $persona['juridica'] == 0
            //         && (( !isset($persona['fec_ingreso']) || $persona['fec_ingreso'] == '' )
            //             || ( !isset($persona['fec_vigencia']) || $persona['fec_vigencia'] == '' )
            //             || ( !isset($persona['id_nacionalidad']) || $persona['id_nacionalidad'] == '' )
            //             || ( !isset($persona['fec_nac']) || $persona['fec_nac'] == '' )
            //             || ( !isset($persona['id_tipo_doc']) || $persona['id_tipo_doc'] == '' )
            //             || ( !isset($persona['nro_doc']) || $persona['nro_doc'] == '' )
            //             || ( !isset($persona['sexo']) || $persona['sexo'] == '' )
            //             || ( !isset($persona['id_tipo_facturacion']) || $persona['id_tipo_facturacion'] == '')
            //             || ( !isset($persona['id_afip']) || $persona['id_afip'] == '')
            //             || ( !isset($persona['id_plan']) || $persona['id_plan'] == '')
            //             || ( !isset($persona['id_parentesco']) || $persona['id_parentesco'] == '')
            //             || ( !isset($persona['n_afiliado']) || $persona['n_afiliado'] == '')
            //         )
            //     ) || !isset($persona['juridica']) 
            //     || ( $persona['juridica'] == 1 && (!isset($persona['cuit']) || $persona['cuit'] == '' ))
            //     ){   
            //     return response()->json([
            //         'status' => $status,
            //         'count' => $count,
            //         'errors' => ['Parámetros incorrectos o incompletos.'],
            //         'message' => 'Verifique los parámetros',
            //         'line' => null,
            //         'code' => null,
            //         'data' => null,
            //         'params' => $this->params,
            //         'logged_user' => $logged_user,
            //         'extras' => $extras
            //     ]); 
            // }

            get_begin_transaction('afiliacion');

            //	si es una persona juridica
            if ( $persona['juridica'] == 1 )
            {
                //	establece por defecto los valores para el documento, desde el CUIT
                $tipo_documento = get_traer_tipo_documento('CUI');
                $persona['id_tipo_doc'] = $tipo_documento['id_tipo_doc'];
                $persona['nro_doc'] = $persona['cuit'];
            }
            
            
            //	crea la variable
            $params_sp = [
                'p_amf_fec'             => empty($persona['amf_fec']) ? NULL : Carbon::parse($persona['amf_fec'])->format('Ymd H:i:s'),
                'p_amf_importe_seguro'  => empty($persona['amf_importe_seguro']) ? NULL : $persona['amf_importe_seguro'],
                'p_celular'             => empty($persona['celular']) ? NULL : $persona['celular'],
                'p_cuit' 			    => empty($persona['cuit']) ? NULL : $persona['cuit'],
                'p_cuit_titular'        => empty($persona['cuit_titular']) ? NULL : $persona['cuit_titular'],
                'p_discapacidad'        => empty($persona['discapacidad']) ? 0 : $persona['discapacidad'],
                'p_email' 			    => empty($persona['email']) ? NULL : $persona['email'],
                'p_emite_factura'       => empty($persona['emite_factura']) ? 0 : $persona['emite_factura'],
                'p_fec_hasta' 	        => ( empty($persona['fec_hasta']) ) ? NULL : Carbon::parse($persona['fec_hasta'])->format('Ymd H:i:s'),
                'p_fec_ingreso' 	    => ( empty($persona['fec_ingreso']) ) ? NULL : Carbon::parse($persona['fec_ingreso'])->format('Ymd H:i:s'),
                'p_fec_nac' 		    => empty($persona['fec_nac']) ? NULL : Carbon::parse($persona['fec_nac'])->format('Ymd H:i:s'),
                'p_fecha' 			    => ( empty($persona['fec_vigencia']) ) ? Carbon::now()->format('Ymd H:i:s') : Carbon::parse($persona['fec_vigencia'])->format('Ymd H:i:s'),
                'p_fecha_estado'	    => ( empty($persona['fecha_estado']) ) ? Carbon::now()->format('Ymd H:i:s') : Carbon::parse($persona['fecha_estado'])->format('Ymd H:i:s'),
                'p_id_actividad'        => empty($persona['id_actividad']) ? null : $persona['id_actividad'],
                'p_id_afip'             => $persona['id_afip'],
                'p_id_estado'           => empty($persona['id_estado']) ? null : $persona['id_estado'],
                'p_id_estado_civil'     => empty($persona['id_estado_civil']) ? null : $persona['id_estado_civil'],
                'p_id_grupo'            => $persona['titular'] == 1 ? null : $persona['id_grupo'],
                'p_id_nacionalidad'     => empty($persona['id_nacionalidad']) ? 1 : $persona['id_nacionalidad'],
                'p_id_origen'           => empty($persona['id_origen']) ? null : $persona['id_origen'],
                'p_id_origen_matricula' => empty($persona['id_origen_matricula']) ? NULL : $persona['id_origen_matricula'],
                'p_id_parentesco'       => $persona['id_parentesco'],
                'p_id_plan'             => $persona['id_plan'],
                'p_id_promotor'         => $persona['id_promotor'],
                'p_id_rango' 		    => empty($persona['id_rango']) ? 0 : $persona['id_rango'],
                'p_id_tipo_doc' 	    => empty($persona['id_tipo_doc']) ? NULL : $persona['id_tipo_doc'],
                'p_id_tipo_facturacion' => $persona['id_tipo_facturacion'],
                'p_id_usuario' 		    => $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default,
                'p_juridica' 		    => $persona['juridica'],
                'p_matricula'           => empty($persona['matricula']) ? 0 : $persona['matricula'],
                'p_motivo_estado'       => empty($persona['motivo_estado']) ? NULL : $persona['motivo_estado'],
                'p_n_afiliado'          => $persona['n_afiliado'],
                'p_n_matricula'         => empty($persona['n_matricula']) ? NULL : $persona['n_matricula'],
                'p_n_persona' 		    => $persona['n_persona'],
                'p_nro_doc' 		    => empty($persona['nro_doc']) ? NULL : $persona['nro_doc'],
                'p_sexo' 			    => ( $persona['juridica'] == 0 && isset($persona['sexo']) ) ? $persona['sexo'] : 'M',
                'p_situacion'		    => empty($persona['situacion']) ? 'N' : $persona['situacion'],
                'p_titular'             => $persona['titular'],
                'p_cod_pos'             => empty($persona['cod_pos']) ? NULL : $persona['cod_pos'],
                'p_telefono'            => empty($persona['telefono']) ? NULL : $persona['telefono'],
                'p_n_domicilio'         => empty($persona['domicilio']) ? NULL : $persona['domicilio'],
                'p_n_provincia'         => empty($persona['n_provincia']) ? NULL : $persona['n_provincia'],
                // 'p_id_tipo_baja' 	    => empty($persona['id_tipo_baja']) ? NULL : $persona['id_tipo_baja'],
                // 'p_fec_hasta'           => empty($persona->fec_hasta) ? NULL : Carbon::parse($persona->fec_hasta)->format('Ymd H:i:s'),
            ];
        // return $params_sp;
            $sp = 'sp_persona_Insert';
            $db = 'afiliacion';
            // inserta la persona
            array_push($extras['sps'], [$sp => $params_sp]);
            array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
            $ret = $this->ejecutar_sp_directo($db, $sp, $params_sp);
            array_push($extras['responses'], [$sp => $ret]);

            if ( is_array($ret) && isset($ret[0]) && $ret[0]->id > 0 ){
                $id_persona = $ret[0]->id;
                $persona_ok = TRUE;
            }else{
                if(is_array($ret) && isset($ret[0]) && $ret[0]->msg != ''){
                    $message = $ret[0]->msg;
                }else{
                    $message = 'Error al Agregar Persona';
                }
                array_push($errors, 'El sp retorno un error al insertar la persona');
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

            }
            
            //	si es persona juridica crea la empresa
            if ( $persona['juridica'] == 1 ) { //&& in_array($persona['tipo'], $son_empresas) )
                //	cambia el flag
                $empresa_ok = FALSE;
                //	crea la variable
                $params_sp = [
                    'p_id_persona' => $id_persona,
                    'p_id_usuario' => $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default,
                    'p_fecha' => Carbon::now()->format('Ymd H:i:s'),
                ];
                $sp = 'sp_empresa_Insert';
                $db = 'afiliacion';
                //	inserta la empresa
                array_push($extras['sps'], [$sp => $params_sp]);
                array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
                $ret = $this->ejecutar_sp_directo($db, $sp, $params_sp);
                array_push($extras['responses'], [$sp => $ret]);

                if ( is_array($ret) && isset($ret['id']) && $ret['id'] > 0 ){
                    $id_empresa = $ret['id'];
                    $empresa_ok = TRUE;
                }else{
                    array_push($errors, $ret['error']);
                    $status = 'fail';
                    $count = 0;
                    $message = 'Error en la consulta a la base de datos.';

                }
            }

            if($persona['juridica'] == 0){
                array_push($extras['responses'], [
                    'persona_ok' => $persona_ok,
                    'empresa_ok' => $empresa_ok,
                ]);
                if ( $persona_ok && $empresa_ok ){
                    get_commit('afiliacion');
                    $data = $id_persona;
                    $code = 1;
                    if ( !empty($id_empresa) ){
                        $extras['id_empresa'] = $id_empresa;
                    }
                    $message = $message.' La persona se insertó exitosamente';
                    $status = 'ok';
                    $count = 1;
                }else{//  si hay errores
                    get_rollback('afiliacion');
                    array_push($errors, 'No se pudo insertar la persona. Err: -1');
                    $status = 'fail';
                    $count = -1;
                }
            }else{
                array_push($extras['responses'], [
                    'persona_ok' => $persona_ok,
                    'empresa_ok' => $empresa_ok
                ]);
                if ( $persona_ok && $empresa_ok){
                    get_commit('afiliacion');
                    $data = $id_persona;
                    if ( !empty($id_empresa) ){
                        $extras['id_empresa'] = $id_empresa;
                    }
                    $message = $message.' La persona se insertó exitosamente';
                    $status = 'ok';
                    $count = 1;
                }else{//  si hay errores
                    get_rollback('afiliacion');
                    array_push($errors, 'No se pudo insertar la persona. Err: -2');
                    $status = 'fail';
                    $count = -1;
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
            array_push($errors, 'Line: '.$th->getLine().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => $this->params,
                'logged_user' => null,
                'extras' => $extras,
            ]);
        }
    }

    /**
     * Actualiza los datos de una persona
     */
    public function actualizar_persona(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/afiliaciones/persona/actualizar-persona',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'verificado' => [],
            'queries' => []
        ];

        $status = 'fail';
        $count = -1;
        $errors = [];
        $message = null;
        $line = null;
        $code = null;
        $data = null;
        try {
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;
            $id_usuario_sqlserver = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

            $persona = json_decode(json_encode(request('persona')));
            $this->params['persona'] = $persona;
            //	variables de control
            $id_empresa = FALSE;
            $persona_ok = FALSE;
            $empresa_ok = TRUE;
            $actividad_baja_ok = TRUE;
            $actividad_ok = TRUE;
            $estado_civil_baja_ok = TRUE;
            $estado_civil_ok = TRUE;

            //	datos obligatorios
            $seguir = false;
            // si no está seteado el nombre de la persona
            if(!isset($persona->n_persona) || $persona->n_persona == ''){
                $seguir = false;
                $code = -3;
                $extras['verificado'] = [
                    'n_persona' => empty($persona->n_persona) ? null : $persona->n_persona,
                ];
            }else{
                // si no esta seteado persona->juridica 
                if(!isset($persona->juridica)){
                    $seguir = false;
                    $code = -4;
                    $extras['verificado'] = [
                        'n_persona' => $persona->n_persona,
                        'juridica' => !isset($persona->juridica) ? null : $persona->juridica
                    ];
                }else{
                    // si es persona juridica y no está seteado cuit
                    if($persona->juridica == 1 && ( !isset($persona->cuit) || $persona->cuit == '' )){
                        $seguir = false;
                        $code = -5;
                        $extras['verificado'] = [
                            'n_persona' => $persona->n_persona,
                            'jurídica' => empty($persona->juridica) ? null : $persona->juridica,
                            'cuit' => empty($persona->cuit) ? null : $persona->cuit,
                        ];
                    }else{
                        // si es persona física
                        if($persona->juridica == 0){
                            // si no está seteado tipo persona
                            if(!isset($persona->tipo)){
                                $seguir = false;
                                $code = -6;
                                $extras['verificado'] = [
                                    'n_persona' => $persona->n_persona,
                                    'jurídica' => $persona->juridica,
                                    'tipo' => isset($persona->tipo) ? $persona->tipo : 'no seteado',
                                ];
                            }else{
                                // si es afiliado
                                if($persona->tipo == 'afiliado'){
                                    $extras['verificado'] = [
                                        'n_persona' => $persona->n_persona,
                                        'jurídica' => $persona->juridica,
                                        'tipo' => $persona->tipo,
                                        'fec_ingreso' => empty($persona->fec_ingreso) ? null : $persona->fec_ingreso,
                                        'fec_vigencia' => empty($persona->fec_vigencia) ? null : $persona->fec_vigencia,
                                        'id_nacionalidad' =>  empty($persona->id_nacionalidad) ? null : $persona->id_nacionalidad,
                                        'fec_nac' => empty($persona->fec_nac) ? null : $persona->fec_nac,
                                        'id_tipo_doc' => empty($persona->id_tipo_doc) ? null : $persona->id_tipo_doc,
                                        'nro_doc' => empty($persona->nro_doc) ? null : $persona->nro_doc,
                                        'sexo' => empty($persona->sexo) ? null : $persona->sexo,
                                        'id_tipo_facturacion' => empty($persona->id_tipo_facturacion) ? null : $persona->id_tipo_facturacion,
                                        'id_afip' => empty($persona->id_afip) ? null : $persona->id_afip,
                                        'id_plan' => empty($persona->id_plan) ? null : $persona->id_plan,
                                        'id_parentesco' => empty($persona->id_parentesco) ? null : $persona->id_parentesco,
                                        'n_afiliado' => empty($persona->n_afiliado) ? null : $persona->n_afiliado,
                                    ];
                                    // si no están seteados los campos obligatorios
                                    if ( ( !isset($persona->fec_ingreso) || $persona->fec_ingreso == '' )
                                        || ( !isset($persona->fec_vigencia) || $persona->fec_vigencia == '' )
                                        || ( !isset($persona->id_nacionalidad) || $persona->id_nacionalidad == '' )
                                        || ( !isset($persona->fec_nac) || $persona->fec_nac == '' )
                                        || ( !isset($persona->id_tipo_doc) || $persona->id_tipo_doc == '' )
                                        || ( !isset($persona->nro_doc) || $persona->nro_doc == '' )
                                        || ( !isset($persona->sexo) || $persona->sexo == '' )
                                        || ( !isset($persona->id_tipo_facturacion) || $persona->id_tipo_facturacion == '')
                                        || ( !isset($persona->id_afip) || $persona->id_afip == '')
                                        || ( !isset($persona->id_plan) || $persona->id_plan == '')
                                        || ( !isset($persona->id_parentesco) || $persona->id_parentesco == '')
                                        || ( !isset($persona->n_afiliado) || $persona->n_afiliado == '')
                                    ){
                                        $seguir = false;
                                        $code = -7;
                                    }else{
                                        $seguir = true;
                                        $code = 2;
                                    }
                                }else{  // promotor u otro tipo
                                    // si no es afiliado
                                    $seguir = true;
                                    $code = 3;
                                    $extras['verificado'] = [
                                        'n_persona' => $persona->n_persona,
                                        'jurídica' => $persona->juridica,
                                        'tipo' => $persona->tipo
                                    ];
                                }
                            }
                        }
                    }
                    
                }
            }

            if(!$seguir){
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => ['Parámetros incorrectos o incompletos.'],
                    'message' => 'Verifique los parámetros',
                    'line' => null,
                    'code' => $code,
                    'data' => null,
                    'params' => $this->params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]);  
            }

            // $extras['verificado'] = [
            //     'persona_n_persona' => empty($persona->n_persona) ? null : $persona->n_persona,
            //     'persona_tipo' => isset($persona->tipo) ? $persona->tipo : 'no seteado',
            //     'persona_fec_ingreso' => empty($persona->fec_ingreso) ? null : $persona->fec_ingreso,
            //     'persona_fec_vigencia' => empty($persona->fec_vigencia) ? null : $persona->fec_vigencia,
            //     'persona_id_nacionalidad' =>  empty($persona->id_nacionalidad) ? null : $persona->id_nacionalidad,
            //     'persona_fec_nac' => empty($persona->fec_nac) ? null : $persona->fec_nac,
            //     'persona_juridica' => $persona->juridica,
            //     'persona_cuit' => empty($persona->cuit) ? null : $persona->cuit,
            //     'persona_id_tipo_doc' => $persona->id_tipo_doc,
            //     'persona_nro_doc' => empty($persona->nro_doc) ? null : $persona->nro_doc,
            //     'persona_sexo' => empty($persona->sexo) ? null : $persona->sexo,
            //     'persona_id_tipo_facturacion' => empty($persona->id_tipo_facturacion) ? null : $persona->id_tipo_facturacion,
            //     'persona_id_afip' => empty($persona->id_afip) ? null : $persona->id_afip,
            //     'persona_id_plan' => empty($persona->id_plan) ? null : $persona->id_plan,
            //     'persona_id_parentesco' => empty($persona->id_parentesco) ? null : $persona->id_parentesco,
            //     'persona_n_afiliado' => empty($persona->n_afiliado) ? null : $persona->n_afiliado,
            //     'persona_titular' => empty($persona->titular) ? null : $persona->titular
            // ];
            // //	datos obligatorios
            // if (( !isset($persona->n_persona) || $persona->n_persona == '' )
            //         || (isset($persona->tipo) && $persona->tipo == 'afiliado'
            //             && (( !isset($persona->fec_ingreso) || $persona->fec_ingreso == '' )
            //                 || ( !isset($persona->fec_vigencia) || $persona->fec_vigencia == '' )
            //                 || ( !isset($persona->id_nacionalidad) || $persona->id_nacionalidad == '' )
            //                 || ( !isset($persona->fec_nac) || $persona->fec_nac == '' )
            //             )
            //         )
            //         || !isset($persona->juridica)
            //         || ( $persona->juridica == 1
            //             && ( !isset($persona->cuit) || $persona->cuit == '' )
            //         )
            //         || ( $persona->juridica == 0
            //             && ( ( !isset($persona->id_tipo_doc) || $persona->id_tipo_doc == '' )
            //                 || ( !isset($persona->nro_doc) || $persona->nro_doc == '' )
            //                 || ( !isset($persona->sexo) || $persona->sexo == '' )
            //             )
            //         )
            //         || ( $persona->titular == 1 && (!isset($persona->id_tipo_facturacion) || $persona->id_tipo_facturacion == ''))
            //         || ( !isset($persona->id_afip) || $persona->id_afip == '')
            //         || ( !isset($persona->id_plan) || $persona->id_plan == '')
            //         || ( !isset($persona->id_parentesco) || $persona->id_parentesco == '')
            //         || ( !isset($persona->n_afiliado) || $persona->n_afiliado == '')
            //     ){
            //     return response()->json([
            //         'status' => $status,
            //         'count' => $count,
            //         'errors' => ['Parámetros incorrectos o incompletos.'],
            //         'message' => 'Verifique los parámetros',
            //         'line' => null,
            //         'code' => null,
            //         'data' => null,
            //         'params' => $this->params,
            //         'logged_user' => $logged_user,
            //         'extras' => $extras
            //     ]); 
            // }

            

            //  inicia transaccion
            get_begin_transaction('afiliacion');

            //	si es una persona juridica
            if ( $persona->juridica == 1 ) {
                //	establece por defecto los valores para el documento, desde el CUIT
                $tipo_documento = get_traer_tipo_documento('CUI');
                $persona->id_tipo_doc = $tipo_documento['id_tipo_doc'];
                $persona->nro_doc = $persona->cuit;
            }
            
            $file = $request->file('foto');
            $tempArr = array();

            $nombre_foto = null;		
            if (!is_null($file)) {
                $tempArr = explode('.', $file->getClientOriginalName());
                Storage::disk('avatars')->put($persona->nro_doc . '.' . $tempArr[1], file_get_contents($file));
                $nombre_foto = $persona->nro_doc . '.' . $tempArr[1];
            }else{
                $nombre_foto = $persona->nombre_foto ?? null;
            }

            //	crea la variable
            $params_sp = [
                'p_amf_fec'             => empty($persona->amf_fec) ? NULL : Carbon::parse($persona->amf_fec)->format('Ymd H:i:s'),
                'p_amf_importe_seguro'  => empty($persona->amf_importe_seguro) ? NULL : $persona->amf_importe_seguro,
                'p_celular'             => empty($persona->celular) ? NULL : $persona->celular,
                'p_cod_pos'             => empty($persona->cod_pos) ? NULL : $persona->cod_pos,
                'p_cuit' 				=> empty($persona->cuit) ? NULL : $persona->cuit,
                'p_cuit_titular' 		=> empty($persona->cuit_titular) ? NULL : $persona->cuit_titular,
                'p_discapacidad'        => empty($persona->discapacidad) ? 0 : $persona->discapacidad,
                'p_email'               => empty($persona->email) ? NULL : $persona->email,
                'p_emite_factura'       => empty($persona->emite_factura) ? 0 : $persona->emite_factura,
                'p_fec_hasta'           => empty($persona->fec_hasta) ? NULL : Carbon::parse($persona->fec_hasta)->format('Ymd H:i:s'),
                'p_fec_ingreso' 		=> empty($persona->fec_ingreso) ? Carbon::now()->format('Ymd H:i:s') : Carbon::parse($persona->fec_ingreso)->format('Ymd H:i:s'),
                'p_fec_nac' 			=> empty($persona->fec_nac) ? NULL : Carbon::parse($persona->fec_nac)->format('Ymd H:i:s'),
                'p_fecha_vigencia' 		=> empty($persona->fec_vigencia) ? NULL : Carbon::parse($persona->fec_vigencia)->format('Ymd H:i:s'),
                'p_fecha_estado' 		=> empty($persona->fecha_estado) ? NULL : Carbon::parse($persona->fecha_estado)->format('Ymd H:i:s'),
                'p_foto'                => $persona->foto,
                'p_id_actividad'        => empty($persona->id_actividad) ? NULL : $persona->id_actividad,
                'p_id_afip'             => isset($persona->id_afip) ? $persona->id_afip : NULL,
                'p_id_estado_civil'     => empty($persona->id_estado_civil) ? NULL : $persona->id_estado_civil,
                'p_id_estado'           => empty($persona->id_estado) ? NULL : $persona->id_estado,
                'p_id_grupo'            => isset($persona->id_grupo) ? $persona->id_grupo : NULL,
                'p_id_nacionalidad' 	=> empty($persona->id_nacionalidad) ? 1 : $persona->id_nacionalidad,
                'p_id_origen_matricula' => empty($persona->id_origen_matricula) ? NULL : $persona->id_origen_matricula,
                'p_id_origen'           => empty($persona->id_origen) ? NULL : $persona->id_origen,
                'p_id_parentesco'       => isset($persona->id_parentesco) ? $persona->id_parentesco : NULL,
                'p_id_persona'		    => $persona->id_persona,
                'p_id_plan'             => isset($persona->id_plan) ? $persona->id_plan : NULL,
                'p_id_promotor'         => isset($persona->id_promotor) ? $persona->id_promotor : NULL,
                'p_id_rango' 			=> empty($persona->id_rango) ? NULL : $persona->id_rango,
                'p_id_tipo_doc' 		=> empty($persona->id_tipo_doc) ? NULL : $persona->id_tipo_doc,
                'p_id_tipo_facturacion' => isset($persona->id_tipo_facturacion) ? $persona->id_tipo_facturacion : NULL,
                'p_id_usuario'          => $id_usuario_sqlserver,
                'p_juridica' 			=> $persona->juridica,
                'p_matricula'           => empty($persona->matricula) ? NULL : $persona->matricula,
                'p_motivo_estado'       => empty($persona->motivo_estado) ? NULL : $persona->motivo_estado,
                'p_n_afiliado'          => isset($persona->n_afiliado) ? $persona->n_afiliado : NULL,
                'p_n_domicilio'         => empty($persona->domicilio) ? NULL : $persona->domicilio,
                'p_n_matricula'         => empty($persona->n_matricula) ? NULL : $persona->n_matricula,
                'p_n_persona' 		    => $persona->n_persona,
                'p_n_provincia'         => empty($persona->n_provincia) ? NULL : $persona->n_provincia,
                'p_nro_doc' 			=> empty($persona->nro_doc) ? NULL : $persona->nro_doc,
                'p_sexo' 				=> empty($persona->sexo) ? '' : $persona->sexo,
                'p_situacion'			=> empty($persona->situacion) ? NULL : $persona->situacion,
                'p_telefono'            => empty($persona->telefono) ? NULL : $persona->telefono,
                'p_titular'             => isset($persona->titular) ? $persona->titular : NULL,
            ];
        // return $params_sp;
            $sp = 'sp_persona_Update';
            $db = 'afiliacion';
            array_push($extras['sps'], [$sp => $params_sp]);
            array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
            $ret = $this->ejecutar_sp_directo($db, $sp, $params_sp);
            array_push($extras['responses'], [$sp => $ret]);
            if(is_array($ret) && isset($ret[0]) && $ret[0]->resultado > 0){
                $id_persona = $persona->id_persona;
                $persona_ok = TRUE;
            }else{
                array_push($errors, $ret['error']);
                $status = 'fail';
                $count = 0;
                $message = 'Error en la consulta a la base de datos.';
            }

            //	si es persona juridica y la empresa no existe, la crea
            if ( $persona->juridica == 1 && !$id_empresa )
            {
                //	cambia el flag
                $empresa_ok = FALSE;

                //	crea la variable
                $params_sp_empresa = [
                    'p_id_persona' => $persona->id_persona,
                    'p_id_usuario' => $id_usuario_sqlserver,
                    'p_fecha' => Carbon::now()->format('Ymd H:i:s'),
                ];

                //	inserta la empresa
                $sp_empresa = 'sp_empresa_Insert';
                $db_empresa = 'afiliacion';
                array_push($extras['sps'], [$sp_empresa => $params_sp_empresa]);
                array_push($extras['queries'], $this->get_query($db_empresa, $sp_empresa, $params_sp_empresa));
                $ret = $this->ejecutar_sp_directo($db_empresa, $sp_empresa, $params_sp_empresa);
                array_push($extras['responses'], [$sp_empresa => $ret]);
                if(is_array($ret) && isset($ret[0]) && $ret[0]->resultado > 0){
                    $id_empresa = $ret['id'];
                    $empresa_ok = TRUE;
                }else{
                    array_push($errors, $resp['error']);
                    $status = 'fail';
                    $count = 0;
                    $message = 'Error en la consulta a la base de datos.';
                }
            }
 
            array_push($extras['responses'], [
                'persona' => $persona_ok,
                'empresa' => $empresa_ok,
            ]);
            //  si no hay errores
            if ( $persona_ok  && $empresa_ok ){
                get_commit('afiliacion');
                $data = $id_persona;
                if ( $id_empresa ){
                    $extras['id_empresa'] = $id_empresa;
                }
                $status = 'ok';
                $count = 1;
                $code = 1;
                $message = 'La persona se actualizó exitosamente.';
            } else {
                get_rollback('afiliacion');
                $status = 'fail';
                $count = 0;
                $code = -2;
                $message = 'No se pudo actualizar la persona';
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
                'logged_user' => $logged_user,
            ]); 
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Error: '.$th->getMessage());
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

    /**
     * Cambiar tipo y número de documento
     */
    public function cambiar_documento(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/afiliaciones/persona/cambiar-documento',
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
                    'id_persona' => request('id_persona'),
                    'id_tipo_doc' => request('id_tipo_doc'),
                    'nro_doc' => request('nro_doc'),
                    'doc_tipo' => request('doc_tipo'),
                    'doc_tipo_old' => request('doc_tipo_old'),
                    'id_tipo_doc_old' => request('id_tipo_doc_old'),
                    'nro_doc_old' => request('nro_doc_old'),
                ];
                $params_sp = [
                    'p_id_persona' => $params['id_persona'],
                    'p_id_tipo_doc' => $params['id_tipo_doc'],
                    'p_nro_doc' => $params['nro_doc'],
                    'p_doc_tipo' => $params['doc_tipo'],
                    'p_doc_tipo_old' => $params['doc_tipo_old'],
                    'p_id_tipo_doc_old' => $params['id_tipo_doc_old'],
                    'p_nro_doc_old' => $params['nro_doc_old'],
                    'p_id_usuario' => $id_usuario,
                ];
                $sp = 'sp_persona_nro_doc_Update';
                $db = 'afiliacion';
                array_push($extras['sps'], [$sp => $params_sp]);
                array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
                $resp = $this->ejecutar_sp_directo($db, $sp, $params_sp);
                array_push($extras['responses'], [$sp => $resp]);
                if(isset($resp['error'])){
                    array_push($errors, $resp['error']);
                    $status = 'fail';
                    $count = 0;
                    $message = 'Error en la consulta a la base de datos.';
                }else if(!empty($resp) && $resp[0]->resultado > 0){
                    $status = 'ok';
                    $data = $resp[0]->resultado;
                    $count = 1;
                    $code = 1;
                    $message = 'Documento modificado con éxito';
                }else{
                    $status = 'ok';
                    $data = $resp;
                    $count = 1;
                    $code = -2;
                    $message = 'No se pudo modificar el documento';
                    array_push($errors, 'Error al intentar cambiar documento');
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
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtotupper($permiso_requerido);
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