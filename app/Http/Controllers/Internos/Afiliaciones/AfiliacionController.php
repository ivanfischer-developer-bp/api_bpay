<?php

namespace App\Http\Controllers\Internos\Afiliaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
// use Illuminate\Support\Facades\File as FacadeFile;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;
use Pusher\ApiErrorException;
use Pusher\Pusher;
use Pusher\PusherException;

use File;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;

class AfiliacionController extends ConexionSpController
{

    /**
     * Buscar el registro histórico de cambios
     */
    public function buscar_registro_historico(Request $request)
    {
        $this->url = 'int/afiliaciones/afiliacion/buscar-registro-historico';
        $this->controlador = 'AfiliacionController.php';
        $this->funcion = 'buscar_registro_historico';
        $this->permiso_requerido = 'gestionar afiliaciones';
        $this->user_id = $request->user()->id;
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->metodo_http = 'get';  //get, post
        $this->param_id_usuario = '';
        $this->tipo_id_usuario = ''; // id, usuario, email, param
        $this->param_usuario = '';
        $this->params = [
            'id_persona' => request('id_persona')
        ];
        $this->params_sp = [
            'p_id_persona' =>	$this->params['id_persona']
        ];
        $this->sp = 'sp_persona_historico_Select';
        return $this->ejecutar_sp_simple();
    }

    /**
     * Busca la documentacion de un afiliado por su id_grupo
     */
    public function buscar_documentacion(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/afiliaciones/afiliacion/documentacion/buscar-documentacion';
        $this->permiso_requerido = 'gestionar afiliaciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_grupo_documentacion_Select';
        $this->params = [
            'id_grupo' => request('id_grupo'),
            'activos' => request('activos')
        ];
        $this->params_sp = [
            'p_id_grupo' => $this->params['id_grupo'],
            'p_activos' => $this->params['activos']
        ];
        if($this->params['activos'] == '' || $this->params['activos'] == null){
            $this->params_sp['p_activos'] = 1;
        }
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agregar una nueva documentacion pedida a un afiliado
     */
    public function agregar_documentacion(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/afiliaciones/afiliacion/documentacion/agregar-documentacion';
        $this->permiso_requerido = 'gestionar afiliaciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_persona_documentacion_Insert';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro id_usuario, id_n_usuario
        $this->verificado = [
            'id_documentacion' => request('id_documentacion'), 
            'fec_pedido' => request('fec_pedido'),
            'fec_vto' => request('fec_vto')
        ];
        if(empty(request('id_documentacion')) || empty(request('fec_pedido') || empty(request('fec_vto')))){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'id_persona' => request('id_persona'),
            'id_documentacion' => request('id_documentacion'),
            'fec_vto' => request('fec_vto'),
            'fec_pedido' => request('fec_pedido'),
        ];
        $this->params_sp = [
            'id_persona' => $this->params['id_persona'],
            'id_documentacion' => $this->params['id_documentacion'],
            'fec_vto' => Carbon::parse($this->params['fec_vto'])->format('Ymd H:i:s'),
            'fec_pedido' => Carbon::parse($this->params['fec_pedido'])->format('Ymd H:i:s'),
            'fecha' => Carbon::now()->format('Ymd H:i:s')
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Marca una documentación como recibida
     */
    public function marcar_documentacion_recibida(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/afiliaciones/afiliacion/documentacion/marcar-documentacion-recibida';
        $this->permiso_requerido = 'gestionar afiliaciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_persona_documentacion_Update';
        $this->tipo_id_usuario = 'id'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro id_usuario, id_n_usuario
        $this->verificado = [
            'id_documentacion' => request('id_documentacion'), 
            'fec_pedido' => request('fec_pedido'),
            'fec_vto' => request('fec_vto'),
            'fec_emision' => request('fec_emision'),
            'n_archivo' => request('n_archivo'),
        ];
        if(empty(request('id_documentacion')) 
            || empty(request('fec_pedido') 
            || empty(request('fec_vto')))
            || empty(request('fec_emision'))
            || empty(request('n_archivo'))
            ){
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }
        $this->params = [
            'id_persona_documentacion' => request('id_persona_documentacion'),
            'id_persona' => request('id_persona'),
            'id_documentacion' => request('id_documentacion'),
            'fec_vto' => request('fec_vto'),
            'fec_pedido' => request('fec_pedido'),
            'fec_emision' => request('fec_emision'),
            'n_archivo' => request('n_archivo')
        ];
        $this->params_sp = [
            'id_persona_documentacion' => $this->params['id_persona_documentacion'],
            'id_persona' => $this->params['id_persona'],
            'id_documentacion' => $this->params['id_documentacion'],
            'fec_vto' => $this->params['fec_vto'],
            'fec_pedido' => $this->params['fec_pedido'],
            'fec_emision' => $this->params['fec_emision'],
            'fec_presenta' => Carbon::now()->format('Ymd H:i:s'),
            'n_archivo' => $this->params['n_archivo']
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Da de baja una documentación
     */
    public function baja_documentacion(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'post';  //get, post
        $this->url = 'int/afiliaciones/afiliacion/documentacion/baja-documentacion';
        $this->permiso_requerido = 'gestionar afiliaciones';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_persona_documentacion_baja';

        $this->verificado = [
            'id_persona_documentacion' => request('id_persona_documentacion')
        ];

        if (empty(request('id_persona_documentacion'))) {
            $this->message = 'Verifique los parámetros';
            $this->status = 'fail';
            $this->count = 0;
            array_push($this->errors, 'Parámetros incorrectos o incompletos');
            $this->code = -5;
            return $this->get_response();
        }

        $this->params = [
            'id_persona_documentacion' => request('id_persona_documentacion')
        ];

        $this->params_sp = [
            'id_persona_documentacion' => $this->params['id_persona_documentacion']
        ];

        return $this->ejecutar_sp_simple();
    }

    /**
     * Retorna un listado de los recién nacidos de un grupo familiar
     */
    public function buscar_recien_nacidos(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/afiliaciones/afiliacion/buscar-recien-nacidos';
        $this->permiso_requerido = 'consultar afiliados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_grupo_rn';
        $this->params = [
            'id_grupo' => request('id_grupo')
        ];
        $this->params_sp = [
            'id_grupo' => $this->params['id_grupo']
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Agrega un recien nacido a un grupo familiar
     */
    public function agregar_recien_nacido(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/afiliaciones/afiliacion/agregar-recien_nacido',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail'; // 'ok', 'fail', 'empty', unauthorized', 'warning'  
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $params_sp = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            
            $permiso_requerido = 'gestionar afiliados';
            if($user->hasPermissionTo($permiso_requerido)){
                $nueva_persona = null;
                $nuevo_afiliado = null;
                $nuevo_beneficiario = null;
                $persona_origen = null;
                $notificacion = null;
                $usuarios_notificar = null;

                $params = [
                    'nueva_persona' => request('nueva_persona'),
                    'persona_origen' => request('persona_origen'),
                    'nuevo_afiliado' => request('nuevo_afiliado'),
                    'beneficiario' => request('beneficiario'),
                    'id_grupo' => request('id_grupo')
                ];
                // return $params;
                $params_sp = [
                    'p_nro_doc' => $params['nueva_persona']['p_nro_doc']
                ];
                // return $params_sp;
                array_push($extras['sps'], ['sp_persona_Select' => $params_sp]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_persona_Select', $params_sp));
                $persona = $this->ejecutar_sp_directo('afiliacion', 'sp_persona_Select', $params_sp);
                array_push($extras['responses'], ['sp_persona_Select' => $persona]);
                // return $persona;
                if(!empty($persona)){
                    $errors = ['Persona existente'];
                    return response()->json([
                        'status' => 'fail',
                        'count' => 1,
                        'errors' => $errors,
                        'message' => 'Ya existe una persona con el número de documento '.$params_sp['p_nro_doc'].' registrado en el sistema.',
                        'line' => null,
                        'code' => -5,
                        'data' => [
                            'persona' => $persona
                        ],
                        'params' => $params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ]);
                    
                }
                $params_persona = $params['nueva_persona'];
                $params_persona['p_id_usuario'] = $id_usuario;
                array_push($extras['sps'], ['sp_persona_Insert' => $params_persona]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_persona_Insert', $params_persona));
                $nueva_persona = $this->ejecutar_sp_directo('afiliacion', 'sp_persona_Insert', $params_persona);
                array_push($extras['responses'], ['sp_persona_Insert' => $nueva_persona]);

                if((is_array($nueva_persona) && array_key_exists('error', $nueva_persona)) || empty($nueva_persona)){
                    array_push($errors, $nueva_persona['error']);
                    $status = 'fail';
                    $message = 'Se produjo un error al insertar la nueva persona';
                    $count = 0;
                    $data = null;
                    $code = -3;
                    // Log::channel('')->error(''); // buscar canales en config/loggin.php
                }else{

                    $params_persona_origen = $params['persona_origen'];
                    $params_persona_origen['p_id_persona'] = $nueva_persona[0]->id;
                    $params_persona_origen['p_id_usuario'] = ''.$id_usuario;
                    array_push($extras['sps'], ['sp_persona_origen_insert' => $params_persona_origen]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_persona_origen_insert', $params_persona_origen));
                    $persona_origen = $this->ejecutar_sp_directo('afiliacion', 'sp_persona_origen_insert', $params_persona_origen);
                    array_push($extras['responses'], ['sp_persona_origen_insert' => $persona_origen]);
                    
                    $params_afiliado = $params['nuevo_afiliado'];
                    $params_afiliado['id_usuario'] = ''.$id_usuario;
                    $params_afiliado['id_persona'] = $nueva_persona[0]->id;
                    array_push($extras['sps'], ['sp_afiliado_Insert' => $params_afiliado]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_afiliado_Insert', $params_afiliado));
                    $nuevo_afiliado = $this->ejecutar_sp_directo('afiliacion', 'sp_afiliado_Insert', $params_afiliado);
                    array_push($extras['responses'], ['sp_afiliado_Insert' => $nuevo_afiliado]);

                    $params_beneficiario = $params['beneficiario'];
                    $params_beneficiario['id_usuario'] = ''.$id_usuario;
                    $params_beneficiario['id_persona'] = $nueva_persona[0]->id;
                    array_push($extras['sps'], ['sp_beneficiario_Insert' => $params_beneficiario]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_beneficiario_Insert', $params_beneficiario));
                    $nuevo_beneficiario = $this->ejecutar_sp_directo('afiliacion', 'sp_beneficiario_Insert', $params_beneficiario);
                    array_push($extras['responses'], ['sp_beneficiario_Insert' => $nuevo_beneficiario]);

                    if(!empty($persona_origen) && !empty($nuevo_afiliado) && !empty($nuevo_beneficiario)){
                        // notificacion con pusher
                        $nom = $params_persona['p_n_persona'];
                        $doc = $params_persona['p_nro_doc'];
                        $af = $params_afiliado['n_afiliado'];

                        array_push($extras['sps'], ['sp_tipo_notificacion_select' => ['p_id' => 3]]);
                        array_push($extras['queries'], $this->get_query('admin', 'sp_tipo_notificacion_select', ['p_id' => 3]));
                        $rol = $this->ejecutar_sp_directo('admin', 'sp_tipo_notificacion_select', ['p_id' => 3]);
                        array_push($extras['responses'], ['sp_tipo_notificacion_select' => $rol]);

                        array_push($extras['sps'], ['sp_usuario_rol_select' => ['p_id_rol' => $rol[0]->id_rol]]);
                        array_push($extras['queries'], $this->get_query('admin', 'sp_usuario_rol_select', ['p_id_rol' => $rol[0]->id_rol]));
                        $usuarios_notificar = $this->ejecutar_sp_directo('admin','sp_usuario_rol_select', ['p_id_rol' => $rol[0]->id_rol]);
                        array_push($extras['responses'], ['sp_usuario_rol_select' => $usuarios_notificar]);

                        $id_usuarios = [];
                        if (count($usuarios_notificar) > 0) {
                            foreach ($usuarios_notificar as $usuario) {
                                if ($id_usuario !== $usuario->id_usuario) {
                                    $mensaje = "Se ingresó un recién nacido con número de afiliado ".$af.", nombre ".$nom.", documento ".$doc.".";
                                    $utiles = [
                                        'path_url' => '/expediente',
                                        'codigo' => 'id_expediente'
                                    ];
                                    array_push($id_usuarios, $id_usuario);

                                    $tipo_notificacion = 3;
                                    $notificacion = $this->ejecutar_sp_directo('admin', 'sp_notificacion_insert', [
                                        'p_id_contrato' => $usuario->id_contrato,
                                        'p_id_usuario' => $usuario->id_usuario,
                                        'p_texto_notificacion' => $mensaje,
                                        'p_asunto' => "Pre Afiliación: Recién Nacido",
                                        'p_notificado' => 0,
                                        'p_utiles' => json_encode($utiles),
                                        'p_id_tipo_notificacion' => $tipo_notificacion
                                    ]);
                                    // $this->pusherService->triggerNotification(
                                    //     "afiliaciones", // channel
                                    //     "RecienNacido", //event
                                    //     [
                                    //         'id_usuario' => $usuario->id_usuario, //Session::get('usuario.id_usuario'), 
                                    //         'code' => 3,
                                    //         'ambito' => env('ENVIRONMENT'),
                                    //         'nombre' => $nom,
                                    //         'documento' => $doc,
                                    //         'afiliado' => $af
                                    //     ] // message
                                    // );
                                }
                            }
                            // notificar con pusher
                            try {
                                $clientePusher = new Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), array('cluster' => env('PUSHER_APP_CLUSTER', 'us2')));
                            } catch (PusherException $e) {
                                Log::warning("Error al instanciar pusher: " . $e->getMessage());
                                return $e;
                            }

                            $channel = 'afiliaciones';
                            $event = 'RecienNacido';
                            $msg = [
                                'id_usuarios' => $id_usuarios, // $usuario->id_usuario, //Session::get('usuario.id_usuario'), 
                                'code' => 3,
                                'ambito' => env('ENVIRONMENT'),
                                'nombre' => $nom,
                                'documento' => $doc,
                                'afiliado' => $af
                            ];

                            try {
                                $clientePusher->trigger($channel, $event, $msg);
                            } catch (PusherException $e) {
                                Log::warning("Error al desencadenar evento pusher: " . $e->getMessage());
                                return $e;
                            } catch (GuzzleException $e) {
                                Log::warning("Error al desencadenar evento guzzle: " . $e->getMessage());
                                return $e;
                            } catch (\Exception $e) {
                                return $e;
                            } 
                        }

                        $status = 'ok';
                        $message = 'Afiliado agregado exitosamente con el número '.$params['nuevo_afiliado']['n_afiliado'];
                        $count = 1;
                        $data = [
                            'nueva_persona' => $nueva_persona,
                            'nuevo_afiliado' => $nuevo_afiliado,
                            'nuevo_beneficiario' => $nuevo_beneficiario,
                            'origen' => $persona_origen,
                            'notificacion' => $msg,
                            'usuarios_notificados' => $usuarios_notificar
                        ];
                        $code = 1;
                    }else{
                        $errors = ['Fallo en la insersión'];
                        $status = 'fail';
                        $message = 'No se pudo agregar al recién nacido.';
                        $count = 0;
                        $data = [
                            'nueva_persona' => $nueva_persona,
                            'nuevo_afiliado' => $nuevo_afiliado,
                            'nuevo_beneficiario' => $nuevo_beneficiario,
                            'persona_origen' => $persona_origen,
                            'notificacion' => $notificacion ,
                            'usuarios_notificados' => $usuarios_notificar
                        ];
                        $code = -6;
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
                    'code' => -2,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }
        }catch (\Exception $e) {
            return $e;
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
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

