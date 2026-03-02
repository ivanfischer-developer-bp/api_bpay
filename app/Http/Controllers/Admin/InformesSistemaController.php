<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;
use App\Models\ConnectedUser;

use DB;
use Carbon\Carbon;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;
use Pusher\ApiErrorException;
use Pusher\Pusher;
use Pusher\PusherException;

class InformesSistemaController extends ConexionSpController
{
    /**
     * Genera un informe de medicamentos recetados
     */
    public function generar_informe_medicamentos_recetados(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'admin/sistema/informes-sistema/medicamentos-recetados',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail'; // 'ok', 'fail', 'empty', unauthorized', 'warning'  
        $message = '';
        $count = 0;
        $code = 0;
        $data = null;
        $errors = [];
        $params = [];
        $params_sp = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $permiso_requerido = '';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $filtrar_por = strtolower(trim((string) request('filtrar_por')));
                $ordenar_por = strtolower(trim((string) request('ordenar_por')));
                $orden = strtolower(trim((string) request('orden')));
                $orden = in_array($orden, ['asc', 'desc']) ? $orden : 'asc';
                $params = [
                    'filtrar_por' => $filtrar_por,
                    'ordenar_por' => $ordenar_por,
                    'orden' => $orden,
                ];
                $ordenar_por = in_array($ordenar_por, ['cantidad', 'alfabeto', 'nombre']) ? $ordenar_por : 'alfabeto';

                if($filtrar_por === 'droga'){
                    $campo_normalizado = "LOWER(LTRIM(RTRIM(nombreDroga)))";
                    $query = DB::connection('validacion')
                        ->table('HC_Receta_medicamento')
                        ->select(DB::raw($campo_normalizado . ' AS nombre'), DB::raw('COUNT(*) AS cantidad'))
                        ->whereNotNull('nombreDroga')
                        ->whereRaw("LTRIM(RTRIM(nombreDroga)) <> ''")
                        ->groupBy(DB::raw($campo_normalizado));

                    if($ordenar_por === 'cantidad'){
                        $query->orderBy('cantidad', $orden)->orderBy(DB::raw($campo_normalizado), $orden);
                    }else{
                        $query->orderBy(DB::raw($campo_normalizado), $orden);
                    }
                }else if($filtrar_por === 'producto'){
                    $campo_normalizado = "LOWER(LTRIM(RTRIM(nombreProducto)))";
                    $query = DB::connection('validacion')
                        ->table('HC_Receta_medicamento')
                        ->select(DB::raw($campo_normalizado . ' AS nombre'), DB::raw('COUNT(*) AS cantidad'))
                        ->whereNotNull('nombreProducto')
                        ->whereRaw("LTRIM(RTRIM(nombreProducto)) <> ''")
                        ->groupBy(DB::raw($campo_normalizado));

                    if($ordenar_por === 'cantidad'){
                        $query->orderBy('cantidad', $orden)->orderBy(DB::raw($campo_normalizado), $orden);
                    }else{
                        $query->orderBy(DB::raw($campo_normalizado), $orden);
                    }
                }else if($filtrar_por === 'usuario'){
                    $campo_normalizado = "LOWER(LTRIM(RTRIM(usuario)))";
                    $query = DB::connection('validacion')
                        ->table('HC_Receta')
                        ->select(DB::raw($campo_normalizado . ' AS nombre'), DB::raw('COUNT(*) AS cantidad'))
                        ->whereNotNull('usuario')
                        ->whereRaw("LTRIM(RTRIM(usuario)) <> ''")
                        ->groupBy(DB::raw($campo_normalizado));

                    if($ordenar_por === 'cantidad'){
                        $query->orderBy('cantidad', $orden)->orderBy(DB::raw($campo_normalizado), $orden);
                    }else{
                        $query->orderBy(DB::raw($campo_normalizado), $orden);
                    }
                }else{
                    $subquerySql = "
                        SELECT LOWER(LTRIM(RTRIM(nombreDroga))) AS nombre
                        FROM HC_Receta_medicamento
                        WHERE nombreDroga IS NOT NULL
                          AND LTRIM(RTRIM(nombreDroga)) <> ''
                        UNION ALL
                        SELECT LOWER(LTRIM(RTRIM(nombreProducto))) AS nombre
                        FROM HC_Receta_medicamento
                        WHERE nombreProducto IS NOT NULL
                          AND LTRIM(RTRIM(nombreProducto)) <> ''
                    ";

                    $query = DB::connection('validacion')
                        ->table(DB::raw("($subquerySql) AS medicamentos"))
                        ->select('nombre', DB::raw('COUNT(*) AS cantidad'))
                        ->groupBy('nombre');

                    if($ordenar_por === 'cantidad'){
                        $query->orderBy('cantidad', $orden)->orderBy('nombre', $orden);
                    }else{
                        $query->orderBy('nombre', $orden);
                    }
                }

                $response = $query
                    ->get()
                    ->map(function ($item) {
                        return [
                            'nombre' => $item->nombre,
                            'cantidad' => (int) $item->cantidad
                        ];
                    })
                    ->toArray();

                if(empty($response)){
                    $status = 'empty';
                    $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                    $count = 0;
                    $data = $response;
                    $code = -4;
                }else{
                    $status = 'ok';
                    $message = 'Transacción realizada con éxito.';
                    $count = sizeof($response);
                    $data = $response;
                    $code = 1;
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
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }
}