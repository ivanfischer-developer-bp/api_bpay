<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;
use Illuminate\Support\Str;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use Auth;
use File;
use Storage;
use Carbon\Carbon;

use DB;

class MobileFileController extends ConexionSpController
{
     /**
     * Sube archivos a la carpeta especificada
     */
    public function subir_archivo(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/archivos/subir-archivo',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];

        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $ruta = '';

            $archivo = $request->archivo;
            $nombre_archivo = $request->nombre_archivo;
            $ext = explode('.', $nombre_archivo);
            $extension = $ext[1];
            
            $n_archivo = md5(date('YmdHis') . Auth::user()->id . uniqid()) . '.' . $extension;
            $ruta = env('UPLOADS_PATH_EXTERNO').$n_archivo;

            $params = [
                'archivo' => $archivo,
                'nombre_archivo' => $nombre_archivo,
            ];
            $message = 'Se produjeron errores en la subida del archivo';
            $errors = [];
            
            if($nombre_archivo == null){
                array_push($errors, 'No exite nombre de archivo. ');
                $status = 'fail';
                $code = -1;
                $count = 0;
            }
            if($archivo == null){
                array_push($errors, 'No exite el archivo');
                $status = 'fail';
                $code = -1;
                $count = 0;
            }
            if($nombre_archivo != null && $archivo != null){
                $errors = [];
                $message = 'archivo subido a '.env('UPLOADS_PATH_EXTERNO').$n_archivo.' en '.config('site.ambiente');
                $status = 'ok';
                $code = 1;
                $count = 1;

                copy($archivo, $ruta);
            }

            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => $code,
                'data' => $n_archivo,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user
            ]);
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user
            ]);
        }
    }

    /**
     * Elimina archivos de la carpeta uploads de la app
     */
    public function eliminar_archivo(Request $request)
    {
    
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/mobile/archivos/eliminar-archivo',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];

        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $ruta = '';

            $nombre_archivo = $request->nombre_archivo;
            $ruta = env('UPLOADS_PATH_EXTERNO').$nombre_archivo;
            
            $params = [
                'nombre_archivo' => $nombre_archivo,
            ];
            $message = 'No se pudo eliminar el archivo';
            $errors = [];
            
            if($nombre_archivo == null){
                array_push($errors, 'No exite nombre de archivo. ');
                $status = 'fail';
                $code = -1;
                $count = 0;
            }

            if($nombre_archivo != null){
                $deleted_ok = Storage::disk('uploads_externo')->delete($nombre_archivo);
            }
            if($deleted_ok){
                $errors = [];
                $message = 'archivo '.env('UPLOADS_PATH_EXTERNO').$nombre_archivo.' eliminado en '.config('site.ambiente');
                $status = 'ok';
                $code = 1;
                $count = 1;
            }else{
                array_push($errors, 'Error de eliminación de arhcivo');
                $message = 'El archivo '.$nombre_archivo.' no pudo ser eliminado en '.config('site.ambiente');
                $status = 'ok';
                $code = -1;
                $count = 0;
                $data = null;
            }

            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => $code,
                'data' => $nombre_archivo,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        }
    }
}