<?php

namespace App\Http\Controllers\Internos\Prestadores;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Carbon\Carbon;


class PrestadoresController extends ConexionSpController
{

    /**
     * Busca prestadores
     * El parámetro busqueda puede ser 'efector' o 'prescriptor' y determina si va abuscar 
     * efectores o prescriptores
     */
    public function buscar_prestadores(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/prestadores/buscar-prestadores',
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
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        try {
            
            if($user->hasPermissionTo('consultar listados')){
                $busqueda = request('busqueda');
                $id_plan_prestacional = request('id_plan_prestacional');
                $id_prestador = request('id_prestador');
                $id_prestadores = request('id_prestadores');
                $id_zona = request('id_zona');
                $matricula = request('matricula');
                $nombre = request('nombre');
                $ambulatorio = request('tipo') == 'ambulatoria' ? 1 : 0;
                $guardia = request('tipo') == 'guardia' ? 1 : 0;
                $internacion = request('tipo') == 'internacion' ? 1 : 0;
                $medicamento = request('tipo') == 'medicamento' ? 1 : 0;
                $this->params = [
                    'busqueda' => $busqueda,
                    'id_plan_prestacional' => $id_plan_prestacional,
                    'id_prestador' => $id_prestador,
                    'id_prestadores' => $id_prestadores,
                    'id_zona' => $id_zona,
                    'matricula' => $matricula,
                    'nombre' => $nombre,
                    'ambulatorio' => $ambulatorio,
                    'guardia' => $guardia,
                    'internacion' => $internacion,
                    'medicamento' => $medicamento,
                ];

                $id_tipo_prestador = 0;
                if ( $busqueda == 'efector' ){
                    $id_tipo_prestador = 3;
                    $id_prestadores = 1;
                }
                //  si la busqueda es general
                if ( $id_prestador == NULL ){
                    $params = [
                        'Bloquear' => 1,
                        'razon_social' => $nombre != null ? $nombre : '',
                        'id_TipoPrestador' => $id_tipo_prestador,
                        'id_prestadores' => $id_prestadores != null ? $id_prestadores : '',
                        'matricula' => $matricula != null ? $matricula : '',
                    ];
                        
                    if ( $busqueda != 'prescriptores' ){
                        $params['Id_Zona'] = $id_zona;          
                        $params['id_plan_prestacional'] = $id_plan_prestacional;          
                    }

                    if($busqueda == 'efectores' ){
                        $params['id_plan_prestacional'] = $id_plan_prestacional;
                        $params['ambulatorio'] = $ambulatorio;
                        $params['internacion'] = $internacion;
                        $params['guardia'] = $guardia;
                        $params['medicamento'] = $medicamento;
                    }

                    if($medicamento == 1){
                        $params['ambulatorio'] = 1;
                    }
                    
                    //  devuelve los resultados de la consulta
                    // $res = $prestador_obj->traer($params);
                    array_push($extras['sps'], ['AWEB_TraerPrescriptoresEfectores' => $params]);
                    array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerPrescriptoresEfectores', $params));
                    $resp = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerPrescriptoresEfectores', $params);
                    array_push($extras['responses'], ['AWEB_TraerPrescriptoresEfectores' => $resp]);
                    $data = $resp;
                    $status = 'ok';
                    $count = sizeof($resp);
                    $message = 'Registros obtenidos exitosamente';
                }else{ 
                    //  si la busqueda es por un prestador en particular
                    //  devuelve el restultado del prestador encontrado
                    array_push($extras['sps'], ['AWEB_TraerPrestador' => ['id_prestador' => $id_prestador]]);
                    array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerPrestador', ['id_prestador' => $id_prestador]));
                    $prestador = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerPrestador', ['id_prestador' => $id_prestador]);
                    array_push($extras['responses'], ['AWEB_TraerPrestador' => $prestador]);
                    if(isset($prestador['error'])){
                        array_push($errors, $prestador['error']);
                        $status = 'fail';
                        $count = 0;
                        $message = 'Error en la consulta a la base de datos.';
                    }else if ($prestador){
                        //  obtiene los datos y los establece en la variable de retorno
                        $count = 1;
                        $prestador = (array)$prestador[0];
                        $prestador['id_persona'] = $prestador['id_prestador'];
                        $prestador['id_tipo_doc'] = get_traer_id_tipo_documento('CUIT');
                        array_push($extras['responses'], ['AWEB_TraerPrestador' => $prestador]);
                        $data = $prestador;
                        $status = 'ok';
                        $message = 'Registros obtenidos exitosamente';
                    }
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

            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CONSULTAR LISTADOS';
                $count  = 0;
                $data = null;
                array_push($errors, 'Error de permisos');
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
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => $data,
                'params' => $this->params,
                'extras' => $extras,
                'logged_user' => $logged_user != null ? $logged_user : null,
            ]);
        }
    }

    /**
     * Busca información sobre prestadores
     * Si tipo es 1 busca información de prescriptores, si es 0 busca información de efectores
     */
    public function buscar_informacion_prestador(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/prestadores/buscar-informacion-prestador',
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
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        try {
            if($user->hasPermissionTo('consultar listados')){
                $id = request('id');
                $matricula = request('matricula');
                $tipo = request('tipo');
                $this->params = [
                    'p_id_prestador' => $id,
                    'p_matricula' => $matricula,
                    'p_tipo' => $tipo
                ];
                array_push($extras['sps'], ['sp_informacion_prestador' => $this->params]);
                array_push($extras['queries'], $this->get_query('validacion', 'sp_informacion_prestador', null));
                $resp = $this->ejecutar_sp_directo('validacion', 'sp_informacion_prestador', null);
                array_push($extras['responses'], ['sp_informacion_prestador' => $resp]);
                if(isset($resp['error'])){
                    array_push($errors, $resp['error']);
                    $status = 'fail';
                    $count = 0;
                    $message = 'Error en la consulta a la base de datos.';
                }else if(is_array($resp) && !empty($resp)){
                    $data = $resp[0];
                    $status = 'ok';
                    $count = 1;
                }else{
                    $data = $resp;
                    $status = 'ok';
                    $count = 1;
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

            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CONSULTAR LISTADOS';
                $count  = 0;
                $data = null;
                array_push($errors, 'Error de permisos');
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
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => $data,
                'params' => $this->params,
                'extras' => $extras,
                'logged_user' => $logged_user != null ? $logged_user : null,
            ]);
        }
    }

    /**
     * Busca centros de atención
     */
    public function buscar_centros_atencion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/prestadores/buscar-centros-atencion',
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
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        try {
            
            if($user->hasPermissionTo('consultar listados')){
                $ambulatorio = request('ambulatoria');
                $guardia = request('guardia');
                $id_plan_prestacional = request('id_plan_prestacional');
                $id_prestador = request('id_prestador');
                $id_prestadores_entidad = request('id_prestadores_entidad');
                $id_zona = request('id_zona');
                $internacion = request('internacion');
                $this->params = [
                    'ambulatorio' => $ambulatorio,
                    'guardia' => $guardia,
                    'id_plan_prestacional' => $id_plan_prestacional,
                    'id_prestador' => $id_prestador,
                    'id_prestador_entidad' => $id_prestadores_entidad,
                    'id_zona' => $id_zona,
                    'internacion' => $internacion
                ];

                //  devuelve los resultados de la consulta
                array_push($extras['sps'], ['AWEB_TraerCentrosAtencion' => $this->params]);
                array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerCentrosAtencion', null));
                $resp = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerCentrosAtencion', null);
                array_push($extras['responses'], ['AWEB_TraerCentrosAtencion' => $resp]);
                if(isset($resp['error'])){
                    array_push($errors, $resp['error']);
                    $status = 'fail';
                    $count = 0;
                    $message = 'Error en la consulta a la base de datos.';
                }else if (!empty($resp)){
                    $count = 1;
                    $data = $resp;
                    $status = 'ok';
                    $message = 'Registros obtenidos exitosamente';
                }else{
                    $count = 0;
                    $data = $resp;
                    $status = 'empty';
                    $message = 'No se encotraron registros.';
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

            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para CONSULTAR LISTADOS';
                $count  = 0;
                $data = null;
                array_push($errors, 'Error de permisos');
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
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => $data,
                'params' => $this->params,
                'extras' => $extras,
                'logged_user' => $logged_user != null ? $logged_user : null,
            ]);
        }
    }
    
}
