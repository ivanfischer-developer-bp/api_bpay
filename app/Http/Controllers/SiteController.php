<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Conexion;

use DB;

class SiteController extends Controller
{
    /**
     * Devuelve la versión actual de software
     * Comprueba la disponibilidad del sitio
     */
    public function index(Request $request)
    {
        $extras = [
            'api_externa_software_version' => config('site.external_software_version'),
            'api_interna_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/site',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
        ];
        try {
            $error = 'No hubo errores.';
            $status = 'ok';
            $message = 'Conexiones con base de datos establecidas sin problemas.';
            $mysql = false;
            $api_bpay_db = false;
            $errors = [];

            try {
                // $users = DB::connection('mysql')->select('select name from users');
                // $users = DB::connection(env('DB_CONNECTION'))->select('select name from users');
                // if(sizeof($users) > 0){
                $users = DB::connection(env('DB_CONNECTION'))->table('users')->first();
                if($users !== null){
                    $mysql = 'ok';
                    $api_bpay_db = 'ok';
                }
            } catch (\Throwable $th) {
                $mysql = 'fail';
                $api_bpay_db = 'fail';
                $error = $th->getMessage();
                array_push($errors, 'autenticación -> '.$error);
            }
            try {
                //code...
                $conexion_admin = new Conexion('admin');
                $res_admin = $conexion_admin->ejecutar_sp('sp_dummy');
                $admin = !empty($res_admin) && strtolower($res_admin[0]->estado) == 'ok' ? 'ok' : 'fail';
            } catch (\Throwable $th) {
                $admin = 'fail';
                $error = $th->getMessage();
                array_push($errors, 'administracion -> '.$error);
            }
            try {
                $conexion_afiliacion = new Conexion('afiliacion');
                $res_afiliacion = $conexion_afiliacion->ejecutar_sp('sp_dummy');
                $afiliacion = !empty($res_afiliacion) && strtolower($res_afiliacion[0]->estado) == 'ok' ? 'ok' : 'fail';
            } catch (\Throwable $th) {
                $afiliacion = 'fail';
                $error = $th->getMessage();
                array_push($errors, 'afiliaciones -> '.$error);
            }
            try {
                $conexion_validacion = new Conexion('validacion');
                $res_validacion = $conexion_validacion->ejecutar_sp('sp_dummy');
                $validacion = !empty($res_validacion) && strtolower($res_validacion[0]->estado) == 'ok' ? 'ok' : 'fail';
            } catch (\Throwable $th) {
                $validacion = 'fail';
                $error = $th->getMessage();
                array_push($errors, 'validaciones -> '.$error);;
            }

            // if($admin=='fail' || $afiliacion=='fail' || $validacion=='fail' || $mysql=='fail'){
            if($admin=='fail' || $afiliacion=='fail' || $validacion=='fail' || $api_bpay_db=='fail'){
                $status = 'fail';
                $message = 'No se pudo establecer conexión con alguna de las bases de datos.';
            }
            
            return response()->json([
                'status' => $status,
                'count' => 4,
                'errors' => $errors == [] ? [$error] : $errors,
                'message' => $message,
                'line' => null,
                'code' => 1,
                'software_version' => config('site.software_version'),
                'databases_connections' => [
                    'database_api' => DB::connection('api_bpay')->getDriverName(),// env('DB_CONNECTION'),
                    'autenticacion' => $api_bpay_db,
                    'administracion' => $admin,
                    'afiliaciones' => $afiliacion,
                    'validaciones' => $validacion,
                ],
                'extras' => $extras
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'error' => 'Backend failed',
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }
}
