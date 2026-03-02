<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use Carbon\Carbon;


use App\Http\Controllers\ConexionSpController;

use App\Models\User;
use App\Models\ConnectedUser;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class MobilePreautorizacionesController extends ConexionSpController 
{

    /**
     * Obtiene un listado de tipos de preautorizaciones
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function buscar_tipo_preautorizacion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/preautorizaciones/buscar-tipo-preautorizacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $errors = [];
        $data = null;
        $status = null;
        $code = 0;
        $count = 0;
        $line = null;
        $params = [];
        $message = null;
        $logged_user = null;

        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $id_usuario_sqlserver = $logged_user['id_usuario_sqlserver'];

        try {

            if($logged_user['roles']['name'] != 'afiliado mobile' && $logged_user['roles']['name'] != 'afiliado'){
                return response()->json([
                    'status' => 'unauthorized',
                    'count' => 0,
                    'errors' => $errors,
                    'message' => 'El usuario no es un afiliado',
                    'line' => $line,
                    'code' => -2,
                    'data' => $data,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]);
            }else{
                if($id_usuario_sqlserver != 0){
                    $params = [
                        'id_tipo_preautorizacion' => $request->input('id_tipo_preautorizacion', null),
                    ];
                    $params_sp = [
                        'id_tipo_preautorizacion' => $request->input('id_tipo_preautorizacion', null),
                    ];

                    $sp = 'sp_tipo_preautorizacion_select';
                    $db = 'validacion';
                    array_push($extras['sps'], [$sp => $params_sp]);
                    array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
                    $response = $this->ejecutar_sp_directo($db, $sp, $params_sp);
                    array_push($extras['responses'], [$sp => $response]);

                    if(is_array($response) && array_key_exists('error', $response)){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -3;
                        // Log::channel('')->error(''); // buscar canales en config/loggin.php
                    }else if(empty($response)){
                        $status = 'empty';
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                        $count = 0;
                        $data = $response;
                        $code = -4;
                        // Log::channel('')->info(''); // buscar canales en config/loggin.php
                    }else{
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = sizeof($response);
                        $data = $response;
                        $code = 1;
                    }
                }else{
                    $status = 'fail';
                    $code = -3;
                    $count = 0;
                    $message = 'El usuario logueado no tiene id_usuario_sqlserver';
                    array_push($errors, 'El usuario logueado no tiene id_usuario_sqlserver');
                }

                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => $line,
                    'code' => $code,
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
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Crear una nueva preautorizacion
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function crear_preautorizacion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/preautorizaciones/crear-preautorizacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $errors = [];
        $data = null;
        $status = null;
        $code = 0;
        $count = 0;
        $line = null;
        $params = [];
        $message = null;
        $logged_user = null;

        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $id_usuario_sqlserver = $logged_user['id_usuario_sqlserver'];

        try {

            if($logged_user['roles']['name'] != 'afiliado mobile' && $logged_user['roles']['name'] != 'afiliado'){
                return response()->json([
                    'status' => 'unauthorized',
                    'count' => 0,
                    'errors' => $errors,
                    'message' => 'El usuario no es un afiliado',
                    'line' => $line,
                    'code' => -2,
                    'data' => $data,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]);
            }else{
                if($id_usuario_sqlserver != 0){
                    $params = [
                        'fecha_solicitud' => request('fecha_solicitud') != null ? Carbon::parse(request('fecha_solicitud'))->format('Ymd') : null,
                        'id_tipo_preautorizacion' => request('id_tipo_preautorizacion'),
                        'id_usuario' => $id_usuario_sqlserver,
                        'id_persona' => request('id_persona'),
                        'observaciones' => request('observaciones'),
                        'lugar_atencion' => request('lugar_atencion'),
                        'imagen' => request('imagen'),
                    ];
                    $params_sp = [
                        'fecha_solicitud' => request('fecha_solicitud') != null ? Carbon::parse(request('fecha_solicitud'))->format('Ymd') : null,
                        'id_tipo_preautorizacion' => request('id_tipo_preautorizacion'),
                        'id_usuario' => $id_usuario_sqlserver,
                        'id_persona' => request('id_persona'),
                        'observaciones' => request('observaciones'),
                        'lugar_atencion' => request('lugar_atencion'),
                        'archivo' => null
                    ];
                    if(request('imagen') != null && request('imagen') != ''){
                        // $params_sp['imagen'] = request('imagen');
                        $params_sp['imagen'] = '';
                    }

                    $base64 = $request->input('imagen');
                    $imagen = base64_decode($base64);
                    $extension = $this->detectarExtensionImagen($imagen);
                    if($extension != 'bin'){
                        $nombreArchivo = 'imagen_' . time() . '.' . $extension;
                        Storage::disk('uploads_externo')->put($nombreArchivo, $imagen);
                        array_push($extras['responses'], ['archivo' => $nombreArchivo]);
                        $params_sp['archivo'] = $nombreArchivo;
                    }else{
                        array_push($extras['responses'], ['archivo' => 'No es una imagen válida']);
                    }

                    $sp = 'sp_pre_autorizaciones_insert';
                    $db = 'validacion';
                    array_push($extras['sps'], [$sp => $params_sp]);
                    array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
                    $response = $this->ejecutar_sp_directo($db, $sp, $params_sp);
                    array_push($extras['responses'], [$sp => $response]);

                    if(is_array($response) && array_key_exists('error', $response)){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -3;
                        // Log::channel('')->error(''); // buscar canales en config/loggin.php
                    }else if(empty($response)){
                        $status = 'empty';
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                        $count = 0;
                        $data = $response;
                        $code = -4;
                        // Log::channel('')->info(''); // buscar canales en config/loggin.php
                    }else{
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = sizeof($response);
                        $data = $response;
                        $code = 1;
                    }
                }else{
                    $status = 'fail';
                    $code = -3;
                    $count = 0;
                    $message = 'El usuario logueado no tiene id_usuario_sqlserver';
                    array_push($errors, 'El logueado no tiene id_usuario_sqlserver');
                }

                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => $line,
                    'code' => $code,
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
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Obtiene un listado de preautorizaciones
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function buscar_preautorizacion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/preautorizaciones/buscar-preautorizacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $errors = [];
        $data = null;
        $status = null;
        $code = 0;
        $count = 0;
        $line = null;
        $params = [];
        $message = null;
        $logged_user = null;

        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $id_usuario_sqlserver = $logged_user['id_usuario_sqlserver'];

        try {

            if($logged_user['roles']['name'] != 'afiliado mobile' && $logged_user['roles']['name'] != 'afiliado'){
                return response()->json([
                    'status' => 'unauthorized',
                    'count' => 0,
                    'errors' => $errors,
                    'message' => 'El usuario no es un afiliado',
                    'line' => $line,
                    'code' => -2,
                    'data' => $data,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]);
            }else{
                if($id_usuario_sqlserver != 0){
                    $params = [
                        'id_pre_autorizacion' => $request->input('id_pre_autorizacion', null),
                        'id_persona' => $request->input('id_persona', null),
                        'fec_desde' => $request->input('fec_desde', null),
                        'fec_hasta' => $request->input('fec_hasta', null),
                    ];

                    $params_sp = [
                        'id_pre_autorizacion' => $request->input('id_pre_autorizacion') != null ? intval($request->input('id_pre_autorizacion')) : null,
                        'id_persona' => $request->input('id_persona') != null ? intval($request->input('id_persona')) : null,
                        'fec_desde' => $request->input('fec_desde', null) != null ? Carbon::parse($request->input('fec_desde'))->format('Ymd') : null,
                        'fec_hasta' => $request->input('fec_hasta', null) != null ? Carbon::parse($request->input('fec_hasta'))->format('Ymd') : null,
                    ];

                    $sp = 'sp_pre_autorizaciones_select';
                    $db = 'validacion';
                    array_push($extras['sps'], [$sp => $params_sp]);
                    array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
                    $response = $this->ejecutar_sp_directo($db, $sp, $params_sp);
                    array_push($extras['responses'], [$sp => $response]);

                    if(is_array($response) && array_key_exists('error', $response)){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -3;
                        // Log::channel('')->error(''); // buscar canales en config/loggin.php
                    }else if(empty($response)){
                        $status = 'empty';
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                        $count = 0;
                        $data = $response;
                        $code = -4;
                        // Log::channel('')->info(''); // buscar canales en config/loggin.php
                    }else{
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = sizeof($response);
                        $data = $response;
                        $code = 1;
                    }
                }else{
                    $status = 'fail';
                    $code = -3;
                    $count = 0;
                    $message = 'El usuario logueado no tiene id_usuario_sqlserver';
                    array_push($errors, 'El usuario logueado no tiene id_usuario_sqlserver');
                }

                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => $line,
                    'code' => $code,
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
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }

    /**
     * Función privada para detectar la extensión de una imagen a partir de su contenido binario
     * @param string $imagenBinaria Contenido binario de la imagen
     * @return string Extensión de la imagen ('jpg', 'png', 'gif', o 'bin' si es desconocida)
     */
    private function detectarExtensionImagen($imagenBinaria) 
    {
        // tipo de imagen
        // data:image/png;base64,iVBORw0KGgo...
        // data:image/jpeg;base64,/9j/4AAQSkZ...
        // data:image/gif;base64,R0lGODdh...
        if (preg_match('/^data:image\/(\w+);base64,/', $imagenBinaria, $matches)) {
            $tipo = $matches[1]; // 'png', 'jpeg', 'gif', etc.
            $extension = $tipo == 'jpeg' ? 'jpg' : $tipo;
            return $extension;
        } 

        // JPEG
        if (substr($imagenBinaria, 0, 2) === "\xFF\xD8") {
            return 'jpg';
        }
        // PNG
        if (substr($imagenBinaria, 0, 8) === "\x89PNG\x0D\x0A\x1A\x0A") {
            return 'png';
        }
        // GIF
        if (substr($imagenBinaria, 0, 6) === "GIF87a" || substr($imagenBinaria, 0, 6) === "GIF89a") {
            return 'gif';
        }

        return 'bin'; // Desconocido
    }

}