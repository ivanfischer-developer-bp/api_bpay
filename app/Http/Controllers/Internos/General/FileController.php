<?php

namespace App\Http\Controllers\Internos\General;

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

class FileController extends ConexionSpController
{
    /**
     * Devuelve el archivo solicitado almacenadoen el upload externo
     */
    public function view(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/archivos/ver',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            //  arma el nombre del archivo
            $accion = request('accion');
            $file = request('file');
            $extension = request('extension');
            $filename = $file . '.' . $extension;
            $errors = [];

            $params = [
                'file' => $file,
                '$extension' => $extension,
                'accion' => $accion
            ];
    
            //  extensiones permitidas
            $extensiones_permitidas = ['pdf', 'csv', 'xls', 'txt', 'jpg', 'jpeg', 'jfif', 'webp', 'png', 'doc', 'docx', 'xls', 'xlsx', 'caj', 'd00',
                                        'PDF', 'CSV', 'XLS', 'TXT', 'JPG', 'JPEG', 'JFIF', 'WEBP', 'PNG', 'DOC', 'DOCX', 'XLS', 'XLSX', 'CAJ', 'D00'];

            //  verifica que el archivo exista
            // if (in_array($extension, $extensiones_permitidas)) {
            if (Storage::disk('uploads_externo')->exists($filename) && in_array($extension, $extensiones_permitidas)) {
                
                //  dependiendo la extension establece el mimetype
                switch ($extension) {
                    case 'pdf':
                        $content_type = 'application/pdf';
                        break;
                    case 'txt':
                        $content_type = 'text/plain';
                        break;
                    case 'csv':
                        $content_type = 'text/plain';
                        break;
                    case 'jpg':
                        $content_type = 'image/jpeg';
                        break;
                    case 'jpeg':
                        $content_type = 'image/jpeg';
                        break;
                    case 'jfif':
                        $content_type = 'image/jfif';
                        break;
                    case 'webp':
                        $content_type = 'image/webp';
                        break;
                    case 'png':
                        $content_type = 'image/png';
                        break;
                    case 'doc':
                        $content_type = 'application/vnd.ms-word';
                        break;
                    case 'docx':
                        // $content_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                        $content_type = 'application/vnd.ms-word';
                        break;
                    case 'xls':
                        $content_type = 'application/vnd.ms-excel';
                        break;
                    case 'xlsx':
                        $content_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                        break;
                    case 'PDF':
                        $content_type = 'application/pdf';
                        break;
                    case 'TXT':
                        $content_type = 'text/plain';
                        break;
                    case 'CSV':
                        $content_type = 'text/plain';
                        break;
                    case 'JPG':
                        $content_type = 'image/jpeg';
                        break;
                    case 'JPEG':
                        $content_type = 'image/jpeg';
                        break;
                    case 'PNG':
                        $content_type = 'image/png';
                        break;
                    case 'DOC':
                        $content_type = 'application/vnd.ms-word';
                        break;
                    case 'DOCX':
                        // $content_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                        $content_type = 'application/vnd.ms-word';
                        break;
                    case 'XLS':
                        $content_type = 'application/vnd.ms-excel';
                        break;
                    case 'XLSX':
                        $content_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                        break;
                    default:
                        $content_type = 'application/vnd.ms-excel';
                        break;
                }
  
                //  descarga el documento
                if ($accion == 'descargar') {
                    $new_filename = !empty($request->segments()[4]) ? $request->segments()[4] : md5($filename . Carbon::now()->format('YmdHis'));
                    return response()->download(Storage::disk('uploads_externo').'/'.$filename, $new_filename . '.' . $extension, [
                        'Content-Type' => $content_type,
                    ]);
                }else {
                    return FacadeResponse::make(Storage::disk('uploads_externo')->get($filename), 200, [
                        'Content-Type' => $content_type,
                        'Content-Disposition' => 'inline; ' . $filename,
                    ]);
                }
                
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Sube archivos a la carpeta especificada
     */
    public function upload(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/archivos/subir',
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
            
            if($extension == 'jfif'){
                $extension = 'jpeg';
            }
            $n_archivo = md5(date('YmdHis') . Auth::user()->id . uniqid()) . '.' . $extension;
            $ruta = env('UPLOADS_PATH_EXTERNO').$n_archivo;

            $params = [
                'archivo' => $archivo,
                'nombre_archivo' => $nombre_archivo,
                'extension' => $extension,
                'n_archivo' => $n_archivo,
                'ruta' => $ruta,
                'all' => $request->all()
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
                $message = 'Archivo '.$n_archivo.' subido en '.config('site.ambiente');
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

    /**
     * Elimina archivos de la carpeta uploads de la app
     */
    public function destroy(Request $request)
    {
    
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/archivos/eliminar',
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
                if (Storage::disk('uploads_externo')->exists($nombre_archivo)) {
                    $deleted_ok = Storage::disk('uploads_externo')->delete($nombre_archivo);
                } else {
                    $deleted_ok = false;
                    array_push($errors, 'El archivo '.$nombre_archivo.' no existe en '.config('site.ambiente'));
                    $message = 'El archivo '.$nombre_archivo.' no existe en '.config('site.ambiente');
                }
            }
            if($deleted_ok){
                $message = 'Archivo '.$nombre_archivo.' eliminado en '.config('site.ambiente');
                $status = 'ok';
                $code = 1;
                $count = 1;
            }else{
                $status = 'fail';
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

    public function buscar_avatar(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/archivos/avatar/buscar',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            
            $file = request('file');
            $extension = request('extension');
            $filename = $file . '.' . $extension;

            $params = [
                'file' => $file,
                'extension' => $extension
            ];

            $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG'];

            switch ($extension) {
                case 'jpg':
                    $content_type = 'image/jpeg';
                    break;
                case 'jpeg':
                    $content_type = 'image/jpeg';
                    break;
                case 'png':
                    $content_type = 'image/png';
                    break;
                case 'JPG':
                    $content_type = 'image/jpeg';
                    break;
                case 'JPEG':
                    $content_type = 'image/jpeg';
                    break;
                case 'PNG':
                    $content_type = 'image/png';
                    break;
            }
            if (Storage::disk('avatars_externo')->exists($filename)){
                return FacadeResponse::make(Storage::disk('avatars_externo')->get($filename), 200, [
                    'Content-Type' => $content_type,
                    'Content-Disposition' => 'inline; ' . $filename,
                ]);
            }else{
                return FacadeResponse::make(Storage::disk('avatares')->get('avatar-no-image.png'), 200, [
                    'Content-Type' => $content_type,
                    'Content-Disposition' => 'inline; ' . $filename,
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
                'data' => null,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Sube archivos de avatars a la carpeta especificada
     */
    public function subir_avatar(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/archivos/avatar/subir',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];

        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $ruta = '';

            $archivo = $request->archivo;
            $nombre_archivo = $request->nombre_archivo;
            // $ext = explode('.', $nombre_archivo);
            // $extension = $ext[1];
            
            // $n_archivo = md5(date('YmdHis') . Auth::user()->id . uniqid()) . '.' . $extension;
            $ruta = env('AVATARS_PATH_EXTERNO').$nombre_archivo;

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
                $message = 'archivo subido a '.env('AVATARS_PATH_EXTERNO').$nombre_archivo.' en '.config('site.ambiente');
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
                'data' => $nombre_archivo,
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
                'code' => $th->getCode(),
                'data' => null,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Elimina archivos de la carpeta uploads de la app
     */
    public function quitar_avatar(Request $request)
    {
    
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/archivos/avatar/quitar',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];

        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $ruta = '';

            $nombre_archivo = $request->nombre_archivo;
            $ruta = env('AVATARS_PATH_EXTERNO').$nombre_archivo;

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
                $deleted_ok = Storage::disk('avatars_externo')->delete($nombre_archivo);
            }
            if($deleted_ok){
                $errors = [];
                $message = 'archivo '.$nombre_archivo.' eliminado en '.config('site.ambiente');
                $status = 'ok';
                $code = 1;
                $count = 1;
            }else{
                array_push($errors, 'Error de eliminación de archivo');
                $message = 'El archivo '.$nombre_archivo.' no pudo ser eliminado en '.config('site.ambiente');
                $status = 'fail';
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
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Sube un archivo de firma de medico a la carpeta storage/app/public/uploads/firma_medicos
     * @param archivo file archivo a subir.
     * @param nombre_archivo string nombre del archivo con su extensión. 
     * Extensiones permitidas: png, jpg, jpeg.
     * El nombre del archivo debe ser el dni de médico mas la extensión. 
     */
    public function subir_firma_medico(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/archivos/firma-medicos/subir-firma-medico',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];

        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $ruta = '';

            $archivo = $request->archivo;
            $nombre_archivo = $request->nombre_archivo;
            // $ext = explode('.', $nombre_archivo);
            // $extension = $ext[1];
            
            // $n_archivo = md5(date('YmdHis') . Auth::user()->id . uniqid()) . '.' . $extension;
            $ruta = env('FIRMA_MEDICOS').$nombre_archivo;

            $data = null;
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
                $data = null;
            }
            if($archivo == null){
                array_push($errors, 'No exite el archivo. ');
                $status = 'fail';
                $code = -1;
                $count = 0;
                $data = null;
            }
            if($nombre_archivo != null && $archivo != null){
                $errors = [' '];
                $message = 'Archivo subido a '.env('FIRMA_MEDICOS').$nombre_archivo.' en '.config('site.ambiente');
                $status = 'ok';
                $code = 1;
                $count = 1;
                $data = $nombre_archivo;

                copy($archivo, $ruta);
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
                'code' => $th->getCode(),
                'data' => null,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Elimina un archivo de firma de médico
     * @param nombre_archivo string nombre del archivo a eliminar
     * El nombre del archivo debe ser el dni del médico con la extension.
     * Extensiones permitidas: png, jpg, jpeg
     */
    public function quitar_firma_medico(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/archivos/firma-medicos/quitar-firma-medico',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];

        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $ruta = '';

            $nombre_archivo = $request->nombre_archivo;
            $ruta = env('FIRMA_MEDICOS').$nombre_archivo;

            $data = null;
            $params = [
                'nombre_archivo' => $nombre_archivo,
            ];
            $message = 'No se pudo eliminar el archivo';
            $errors = [' '];
            
            if($nombre_archivo == null){
                $errors = ['No exite nombre de archivo. '];
                $status = 'fail';
                $code = -1;
                $count = 0;
            }else{
                if($nombre_archivo != null){
                    $deleted_ok = Storage::disk('firma_medicos')->delete($nombre_archivo);
                    array_push($extras['responses'], ['archivo_eliminado' => $deleted_ok]);
                }
                if($deleted_ok){
                    $message = 'Archivo '.$nombre_archivo.' eliminado en '.config('site.ambiente');
                    $status = 'ok';
                    $code = 1;
                    $count = 1;
                    $data = $nombre_archivo;
                }else{
                    $errors = ['Error de eliminación de archivo'];
                    $message = 'No se encontró el archivo '.$nombre_archivo.' y no pudo ser eliminado en '.config('site.ambiente');
                    $status = 'fail';
                    $code = -1;
                    $count = 0;
                    $data = null;
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
                'logged_user' => $logged_user
            ]);
        } catch (\Throwable $th) {
            $errors = ['Line: '.$th->getLine().' Error: '.$th->getMessage()];
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
     * Busca un archivo de firma de médico
     * @param file string nombre del archivo. Debe ser el dni del médico
     * @param extension string debe ser una del as extensiones permitidas: png, jpg, jpeg
     */
    public function buscar_firma_medico(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/archivos/firma-medicos/buscar-firma-medico',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            
            $file = request('file');
            $extension = request('extension');
            $filename = $file . '.' . $extension;

            $params = [
                'file' => $file,
                'extension' => $extension
            ];

            $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG'];

            switch ($extension) {
                case 'jpg':
                    $content_type = 'image/jpeg';
                    break;
                case 'jpeg':
                    $content_type = 'image/jpeg';
                    break;
                case 'png':
                    $content_type = 'image/png';
                    break;
                case 'JPG':
                    $content_type = 'image/jpeg';
                    break;
                case 'JPEG':
                    $content_type = 'image/jpeg';
                    break;
                case 'PNG':
                    $content_type = 'image/png';
                    break;
            }
            
            if (Storage::disk('firma_medicos')->exists($filename)){
                return FacadeResponse::make(Storage::disk('firma_medicos')->get($filename), 200, [
                    'Content-Type' => $content_type,
                    'Content-Disposition' => 'inline; ' . $filename,
                ]);
            }else{
                return FacadeResponse::make(Storage::disk('firma_medicos')->get('subir-firma-medico.png'), 200, [
                    'Content-Type' => $content_type,
                    'Content-Disposition' => 'inline; ' . $filename,
                ]);
            }
        } catch (\Throwable $th) {
            $errors = ['Line: '.$th->getLine().' - Error: '.$th->getMessage()];
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
     * Sube un manual de usuario en pdf a la carpeta storage/app/public/uploads/manuales
     * @param archivo file archivo a subir.
     * @param nombre_archivo string nombre del archivo con su extensión. 
     * El nombre debe respetar la nomenclatura: manual_[modulo].pdf y el archivo debe ser un pdf.
     */
    public function subir_manual(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/archivos/manuales/subir-manual',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];

        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $ruta = '';

            $archivo = $request->archivo;
            $nombre_archivo = $request->nombre_archivo;
            $ruta = env('MANUALES').$nombre_archivo;

            $data = null;
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
                $data = null;
            }
            if($archivo == null){
                array_push($errors, 'No exite el archivo. ');
                $status = 'fail';
                $code = -1;
                $count = 0;
                $data = null;
            }
            if($nombre_archivo != null && $archivo != null){
                $errors = [' '];
                $message = 'Archivo subido a '.env('MANUALES').$nombre_archivo.' en '.config('site.ambiente');
                $status = 'ok';
                $code = 1;
                $count = 1;
                $data = $nombre_archivo;

                copy($archivo, $ruta);
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
                'code' => $th->getCode(),
                'data' => null,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Busca un manual de usuario en pdf de un módulo
     * @param manual string nombre del modulo: 
     * administracion, informacion, comunicaciones, afiliaciones, validaciones, consultas, consultorio, etc
     */
    public function buscar_manual(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/archivos/manuales/buscar-manual',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            
            $manual = strtolower(request('manual'));
            $modulo = strtolower(request('modulo'));
            $filename = $manual;
            if($modulo != null){
                $filename = 'manual_'.$modulo.'.pdf';
            }


            $errors = [];
            $params = [
                'manual' => $manual,
                'modulo' => $modulo
            ];

            if (Storage::disk('manuales')->exists($filename)){
                return FacadeResponse::make(Storage::disk('manuales')->get($filename), 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; ' . $filename,
                ]);
            }else{
                return response()->json([
                    'status' => 'fail',
                    'count' => 0,
                    'errors' => $errors,
                    'message' => 'No se encontró el manual '.$filename,
                    'line' => null,
                    'code' => -1,
                    'data' => null,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user
                ]);
            }
        } catch (\Throwable $th) {
            $errors = ['Line: '.$th->getLine().' - Error: '.$th->getMessage()];
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
     * Elimina un archivo pdf de manual de usuario de un módulo
     * @param nombre_archivo string nombre del archivo a eliminar
     * El nombre debe respetar la nomenclatura: manual_[modulo].pdf y el archivo debe ser un pdf.
     */
    public function quitar_manual(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/archivos/firma-medicos/quitar-firma-medico',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];

        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $ruta = '';

            $nombre_archivo = $request->nombre_archivo;
            $ruta = env('MANUALES').$nombre_archivo;

            $data = null;
            $params = [
                'nombre_archivo' => $nombre_archivo,
            ];
            $message = 'No se pudo eliminar el archivo';
            $errors = [' '];
            
            if($nombre_archivo == null){
                $errors = ['No exite nombre de archivo. '];
                $status = 'fail';
                $code = -1;
                $count = 0;
            }else{
                if($nombre_archivo != null){
                    $deleted_ok = Storage::disk('manuales')->delete($nombre_archivo);
                    array_push($extras['responses'], ['archivo_eliminado' => $deleted_ok]);
                }
                if($deleted_ok){
                    $message = 'Archivo '.$nombre_archivo.' eliminado en '.config('site.ambiente');
                    $status = 'ok';
                    $code = 1;
                    $count = 1;
                    $data = $nombre_archivo;
                }else{
                    $errors = ['Error de eliminación de archivo'];
                    $message = 'No se encontró el archivo '.$nombre_archivo.' y no pudo ser eliminado en '.config('site.ambiente');
                    $status = 'fail';
                    $code = -2;
                    $count = 0;
                    $data = null;
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
                'logged_user' => $logged_user
            ]);
        } catch (\Throwable $th) {
            $errors = ['Line: '.$th->getLine().' Error: '.$th->getMessage()];
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
     * retorna un listado de los nombres de los manuales subidos
     */
    public function listar_manuales(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/archivos/manuales/subir-manual',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];

        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $params = [];
            $count = 0;
            $errors = [];
            $message = 'No se encontraron archivos';
            $status = 'empty';
            $code = 0;
            $data = null;

            $dirPath = env('MANUALES');
            $files = scandir($dirPath);
            $files = array_filter($files, function ($file) use ($dirPath) {
                return $file !== '.'
                    && $file !== '..'
                    && strpos($file, '.') !== 0
                    && !is_dir($dirPath . '/' . $file);
            });
            $files = array_values($files);

            if(count($files) > 0){
                $status = 'ok';
                $code = 1;
                $message = 'Archivos encontrados';
                $count = count($files);
                $data = $files;
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
                'code' => $th->getCode(),
                'data' => null,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        }
    }

    public function ver_validacion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/archivos/ver-validacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
        ];
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $errors = [];
            $filename = request('archivo');
            $accion = request('accion'); // ver o descargar
            $params = [
                'filename' => $filename,
                'accion' => $accion
            ];
            
            // Verificar que el archivo existe en el disco validaciones
            if (!Storage::disk('validaciones')->exists($filename)) {
                array_push($errors, 'El archivo no existe en el directorio');
                return response()->json([
                    'status' => 'fail',
                    'code' => -3,
                    'errors' => $errors,
                    'message' => 'El archivo "' . $filename . '" no existe en el directorio de validaciones',
                    'count' => 0,
                    'line' => null,
                    'data' => null,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user
                ]);
            }

            // Obtener la ruta completa del archivo
            $filepath = storage_path('app/public/reportes/validaciones/' . $filename);

            // Verificar que el archivo existe físicamente
            if (!file_exists($filepath)) {
                array_push($errors, 'El archivo no existe físicamente en el servidor');
                return response()->json([
                    'status' => 'fail',
                    'code' => -4,
                    'errors' => $errors,
                    'message' => 'El archivo "' . $filename . '" no existe en la ruta: ' . $filepath,
                    'count' => 0,
                    'line' => null,
                    'data' => null,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user
                ]);
            }

            // Verificar permisos de lectura
            if (!is_readable($filepath)) {
                array_push($errors, 'El archivo no tiene permisos de lectura');
                return response()->json([
                    'status' => 'fail',
                    'code' => -5,
                    'errors' => $errors,
                    'message' => 'El archivo "' . $filename . '" no es legible. Permisos: ' . substr(sprintf('%o', fileperms($filepath)), -4),
                    'count' => 0,
                    'line' => null,
                    'data' => null,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user
                ]);
            }

            if ($accion == 'descargar') {
                try {
                    // Usar headers personalizados para forzar la descarga
                    $headers = [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                        'Content-Length' => filesize($filepath),
                    ];
                    
                    return response()->file($filepath, $headers);
                } catch (\Exception $e) {
                    array_push($errors, 'Error en descarga: ' . $e->getMessage());
                    return response()->json([
                        'status' => 'fail',
                        'code' => -6,
                        'errors' => $errors,
                        'message' => 'Error al intentar descargar: ' . $e->getMessage(),
                        'count' => 0,
                        'line' => $e->getLine(),
                        'data' => null,
                        'params' => $params,
                        'extras' => array_merge($extras, ['filepath' => $filepath, 'filesize' => filesize($filepath)]),
                        'logged_user' => $logged_user
                    ]);
                }
            }else {
                return FacadeResponse::make(file_get_contents($filepath), 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $filename . '"',
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
                'code' => $th->getCode(),
                'data' => null,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        }
    }
}






    // sp_objeto_adjunto_Select
    // sp_objeto_adjunto_Insert
    
    // public function upload(Request $request)
    // {
    //     if($request->hasFile('urlpdf')){
    //         $file=$request->file('urlpdf');
    //         $nombre = 'pdf_'.time().'.'.$file->guessExtension();
    //         $ruta = env('STORAGE_PATH').'uploads/'.$nombre;
    //         if($file->guessExtension()=='pdf'){
    //             copy($file, $ruta);
    //             return 'archivo subido a '.$ruta;
    //         }
    //     }
    // }

    // public function store(Request $request)
    // {
    //     if ($request->ajax()) {
    //         //  gets the uploading file type
    //         $uploading_type = $request->input('type');
    //         //  if has file
    //         // dd('$uploading_type', $uploading_type);
    //         // dd('is_array($request->hasFile(file))', is_array($request->file('file')));
    //         if ($request->hasFile('file')) {
    //             if(is_array($request->file('file'))){
    //                 foreach ($request->file('file') as $file) {
    //                     if (!in_array($file->getMimeType(), oh_tipos_de_archivos_habilitados($uploading_type, 'array', TRUE))) {
    //                         return array('code' => -3, 'message' => 'Extensión no permitida');
    //                     }
    //                 }
    //                 $files_name = [];
    //                 foreach ($request->file('file') as $file) {
    //                     //  gets file extension
    //                     $extension = File::extension($file->getClientOriginalName());
    //                     //  creates new name based on current date and logged user
    //                     $filename = md5(date('YmdHis') . Auth::user()->id . uniqid()) . '.' . $extension;
    //                     //  move uploaded file
    //                     if ($file->move(oh_path_almacenamiento(), $filename)){
    //                         array_push($files_name, $filename);
    //                     }
    //                 }
    //                 return array('code' => 1, 'message' => '', 'filesname' => $files_name);
    //             } else {
    //                 //  gets file extension
    //                 $extension = File::extension($request->file('file')->getClientOriginalName());
    //                 // dd('$request->file(file)->getMimeType()', $request->file('file')->getMimeType());
    //                 //  if file is allowed
    //                 if (in_array($request->file('file')->getMimeType(), oh_tipos_de_archivos_habilitados($uploading_type, 'array', TRUE))) {
    //                     //  creates new name based on current date and logged user
    //                     $filename = md5(date('YmdHis') . Auth::user()->id) . '.' . $extension;
    //                     //  move uploaded file
    //                     if ($request->file('file')->move(oh_path_almacenamiento(), $filename)){
    //                         return array('code' => 1, 'message' => '', 'filename' => $filename);
    //                     }else{
    //                         return array('code' => -4, 'message' => 'Error al mover archivo');
    //                     }
    //                 } else { //  if file is not allowed
    //                     return array('code' => -3, 'message' => 'Extensión no permitida');
    //                 }
    //             }
    //         } else {  //  if doesn't have file
    //             return array('code' => -2, 'message' => 'No hay archivo para subir');
    //         }
    //     } else {  //  if isn't an ajax request
    //         return abort(404);
    //     }
    //     //  return default error
    //     return array('code' => -1, 'message' => 'Error General');
    // }

    /**
     * Elimina un archivo subido de la carpeta storage
     * revisar
     */
    // public function destroy(Request $request)
    // {
    //     // revisar
    //     try {
    //         $user = User::with('roles', 'permissions')->find($request->user()->id);
    //         $logged_user = $this->get_logged_user($user);
    //         // variables de respuesta
    //         $status = 'null';
    //         $message = 'sin comentarios';
    //         $count = -1;
    //         $data = null;

    //         $error = 'sin especificar';
    //         //  si no recibe nombre del archivo
            
    //         //  obtiene datos
    //         $filename = $request->input('filename');
    //         $n_baja = $request->input('n_baja');
    //         $id_objeto_adjunto = $request->input('id_objeto_adjunto');
    //         $message = '';
            
    //         $params = [
    //             'filename' => $filename,
    //             'n_baja' => $n_baja,
    //             'id_objeto_adjunto' => $id_objeto_adjunto
    //         ];
            
    //         if (empty($filename)){
    //             return ['code' => -1, 'message' => 'No se especificó archivo'];
    //         }
    //         //  variables de control
    //         $deleted_ok = TRUE;
    //         $baja_objeto_adjunto_ok = TRUE;

    //         //  variables de retorno
    //         $message = empty($id_objeto_adjunto) ? 'No se pudo eliminar el archivo' : 'No se pudo finalizar el adjunto';
    //         $message_ok = empty($id_objeto_adjunto) ? 'Archivo eliminado exitosamente' : 'Adjunto finalizado exitosamente';

    //         //  si tiene objeto adjunto
    //         if (!empty($id_objeto_adjunto)) {
    //             //  cambia flag
    //             $baja_objeto_adjunto_ok = FALSE;

    //             //  crea el objeto
    //             $objeto_adjunto_obj = new ObjetoAdjunto;

    //             //  obtiene los datos del adjunto
    //             $objeto_adjunto = $this.ejecutar_sp_directo('sp_objeto_adjunto_Select', $id_objeto_adjunto);

    //             //  si encuentra el objeto_adjunto
    //             if (!empty($objeto_adjunto) && !empty($objeto_adjunto['id_vigencia'])) {
    //                 //  finaliza el objeto_adjunto
    //                 $n_baja = empty($n_baja) ? 'Eliminación del Archivo Adjunto' : $n_baja;
    //                 $baja_objeto_adjunto_ok = oh_finalizar(['id_vigencia' => $objeto_adjunto['id_vigencia'], 'n_baja' => $n_baja]);
    //             }
    //         }

    //         //  si se finalizo el objeto_adjunto (si no habia que hacerlo por default es TRUE)
    //         //  y si el archivo existe
    //         if ($baja_objeto_adjunto_ok && Storage::disk('uploads')->exists($filename)) {
    //             //  elimina el archivo
    //             $deleted_ok = Storage::disk('uploads')->delete($filename);
    //         }

    //         return [
    //             'code' => ($baja_objeto_adjunto_ok ? 1 : -1),
    //             'deleted' => ($deleted_ok ? 1 : 0),
    //             'baja_objeto_adjunto' => ($baja_objeto_adjunto_ok ? 1 : 0),
    //             'message' => (($deleted_ok && $baja_objeto_adjunto_ok) ? $message_ok : $message)
    //         ];
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'status' => 'fail',
    //             'data' => null,
    //             'params' => null,
    //             'logged_user' => $logged_user,
    //             'count' => 0,
    //             'error' => 'Backend failed',
    //             'line' => $th->getLine(),
    //             'code' => $th->getCode(),
    //             'message' => $th->getMessage()
    //         ]);
    //     }
    // }

    // public function getImage($filename)
    // {
    //     if (!Storage::disk('uploads')->exists($filename)) {
    //         abort('404');
    //     }

    //     $ext = explode('.', $filename);
    

    //     return 'data:image/' . $ext[1] . ';base64,' . base64_encode(file_get_contents(storage_path('uploads' . DIRECTORY_SEPARATOR . ($filename))));
    // }