<?php

namespace App\Http\Controllers\Externos;

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

class ExternalFileController extends ConexionSpController
{
    /**
     * Obtiene un archivo almacenado en el upload externo sin necesidad de autenticación
     * externo
     * Creado para obtener archivos desde portal en scripcase
     */
    public function view(Request $request)
    {
        //  arma el nombre del archivo
        $accion = request('accion');
        $file = request('file');
        $extension = request('extension');
        $filename = $file . '.' . $extension;

        $params = [
            'file' => $file,
            '$extension' => $extension,
            'accion' => $accion
        ];

        //  extensiones permitidas
        $extensiones_permitidas = ['pdf', 'csv', 'xls', 'txt', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'caj', 'd00',
                                    'PDF', 'CSV', 'XLS', 'TXT', 'JPG', 'JPEG', 'PNG', 'DOC', 'DOCX', 'XLS', 'XLSX', 'CAJ', 'D00'];

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
    }
}