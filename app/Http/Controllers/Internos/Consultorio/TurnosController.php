<?php

namespace App\Http\Controllers\Internos\Consultorio;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ProfileDoctor;
use App\Models\ProfileSecretary;
use App\Models\Turno;

use File;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;

class TurnosController extends Controller
{

    /** 
     * Busca turnos de un médico entre las fechas dadas
    */
    public function buscar_turnos(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/consultorio/turnos/buscar',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
        ];
        try {
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;
            $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

            $status = 'fail';
            $count = -1;
            $errors = [];
            $message = null;
            $line = null;
            $code = null;
            $data = [];
            $params = [];

            $id_medico = request('id_medico') != 0 ? request('id_medico') : null;
            $fecha_desde = request('fecha_desde') != null && request('fecha_desde') != '' ? request('fecha_desde') : null;
            $fecha_hasta = request('fecha_hasta') != null && request('fecha_hasta') != '' ? request('fecha_hasta') : null;

            
            if($fecha_desde != null){
                $desde = Carbon::parse($fecha_desde)->startOfDay();
            }else{
                $desde = false;
            }
            if($fecha_hasta != null){
                $hasta = Carbon::parse($fecha_hasta)->endOfDay();
            }else{
                $hasta = Carbon::now()->endOfDay();            
            }

            $params = [
                'id_medico' => request('id_medico'),
                'fecha_desde' => request('fecha_desde'),
                'fecha_hasta' => request('fecha_hasta'),
                'desde' => $desde,
                'hasta' => $hasta
            ];

            $query = DB::table('turnos')
                ->join('profile_doctors', 'turnos.id_medico', '=', 'profile_doctors.id')
                ->select('turnos.centro',
                    'turnos.consultorio',
                    'turnos.date',
                    'turnos.end',
                    'turnos.estado',
                    'turnos.id', 
                    'turnos.id_afiliado',
                    'turnos.id_convenio',
                    'turnos.id_medico',
                    'turnos.id_origen',
                    'turnos.id_plan',
                    'turnos.id_secretaria',
                    'turnos.nombre_afiliado',
                    'turnos.nombre_convenio',
                    'turnos.nombre_medico',
                    'turnos.nombre_origen',
                    'turnos.nombre_plan',
                    'turnos.numero_afiliado',
                    'turnos.observaciones',
                    'turnos.slot_duration',
                    'turnos.slot_duration_desc',
                    'turnos.start',
                    'turnos.title',
                    'profile_doctors.user_id',
                    'profile_doctors.apellido',
                    'profile_doctors.nombre',
                    'profile_doctors.tratamiento',
                    'profile_doctors.tipoDoc',
                    'profile_doctors.nroDoc',
                    'profile_doctors.especialidad',
                    'profile_doctors.sexo',
                    'profile_doctors.fechaNacimiento',
                    'profile_doctors.email',
                    'profile_doctors.telefono',
                    'profile_doctors.pais',
                    'profile_doctors.firmalink',
                    'profile_doctors.matricula_tipo',
                    'profile_doctors.matricula_numero',
                    'profile_doctors.matricula_provincia',
                    'profile_doctors.cuit',
                    'profile_doctors.horario',
                    'profile_doctors.diasAtencion',
                    'profile_doctors.datosContacto',
                    'profile_doctors.nombreConsultorio',
                    'profile_doctors.direccionConsultorio',
                    'profile_doctors.informacionAdicional');
            $turnos = $query->when($id_medico, function($q, $id_medico){
                    return $q->where('turnos.id_medico', '=', $id_medico);
                })
                ->when($desde, function($q, $desde){
                    return $q->where('turnos.start', '>', $desde);
                })
                ->when($hasta, function($q, $hasta){
                    return $q->where('turnos.start', '<', $hasta);
                })
                ->orderBy('profile_doctors.id', 'asc')
                ->orderBy('turnos.start', 'asc')
                ->get();

            if(!empty($turnos)){
                $data = $turnos;
                $count = sizeof($data);
                $status = 'ok';
            }else{
                $count = 0;
                $data = $turnos;
                $status = 'empty';
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
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);

        } catch (\Throwable $th) {
            array_push($errors, 'Error de Backend');
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => $data,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Crea o actualiza un turno
     */
    public function sincronizar_turno(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/consultorio/turnos/sincronizar',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
        ];
        try {
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;
            $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

            $status = 'fail';
            $count = -1;
            $errors = [];
            $message = null;
            $line = null;
            $code = null;
            $data = [];
            $params = [];

            $turno = request('turno');
            $params = [
                'turno' => $turno
            ];
            
            $t = Turno::updateOrCreate([
                'id' => $turno['id']
                ], [
                'id_afiliado' => $turno['id_afiliado'],
                'id_medico' => $turno['id_medico'],
                'id_secretaria' => $turno['id_secretaria'],
                'title' => $turno['title'],
                'date' => $turno['date'],
                'start' => $turno['start'],
                'end' => $turno['end'],
                'centro' => $turno['centro'],
                'consultorio' => $turno['consultorio'],
                'nombre_afiliado' => $turno['nombre_afiliado'],
                'numero_afiliado' => $turno['numero_afiliado'],
                'nombre_medico' => $turno['nombre_medico'],
                'slot_duration' => $turno['slot_duration'],
                'slot_duration_desc' => $turno['slot_duration_desc'],
                'estado' => $turno['estado'],
                'observaciones' => $turno['observaciones'],
                'id_convenio' => $turno['id_convenio'],
                'nombre_convenio' => $turno['nombre_convenio'],
                'id_origen' => $turno['id_origen'],
                'nombre_origen' => $turno['nombre_origen'],
                'id_plan' => $turno['id_plan'],
                'nombre_plan' => $turno['nombre_plan'],
            ]);
            array_push($extras['responses'], ['turno' => $t]);
            $extras['turno'] = $turno;

            if(!empty($t)){
                $fecha = Carbon::parse($turno['date']);
                $primero_de_mes = Carbon::createFromDate($fecha->year, $fecha->month, 1, 0, 0, 0, 'America/Argentina/Buenos_Aires');
                $primero_de_mes_siguiente = Carbon::createFromDate($fecha->year, $fecha->add('month', 1)->month, 1, 0, 0, 0, 'America/Argentina/Buenos_Aires');
                $params = [
                    'id_medico' => $turno['id_medico'],
                    'fecha_desde' => $primero_de_mes,
                    'fecha_hasta' => $primero_de_mes_siguiente
                ];
                $turnos = Turno::where('id_medico', '=', $params['id_medico'])
                                ->whereBetween('start', [$params['fecha_desde'], $params['fecha_hasta']])
                                ->orderBy('start')
                                ->get();
                array_push($extras['responses'], ['turnos' => $turnos]);
                $data = $turnos;
                $count = sizeof($turnos);
                $message = 'Turno sincronizado con éxito';
                $status = 'ok';
            }else{
                $count = 0;
                $data = $t;
                $status = 'fail';
                $message = 'No se pudo sincronizar el turno';
                array_push($errors, ['turno' => $t]);
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
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);

        } catch (\Throwable $th) {
            array_push($errors, 'Error de Backend');
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => $data,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Elimina un turno
     */
    public function eliminar_turno(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/consultorio/turnos/eliminar_turno',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
        ];
        try {
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;
            $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

            $status = 'fail';
            $count = -1;
            $errors = [];
            $message = null;
            $line = null;
            $code = null;
            $data = [];
            $params = [];

            $id_turno = request('id_turno');
            $fecha = request('fecha');
            $params = [
                'id_turno' => $id_turno,
                'fecha' => $fecha
            ];
            
            $turno = Turno::find($id_turno);
            if($turno){
                $response = $turno->delete();
                array_push($extras['responses'], ['eliminado' => $response]);
                if($response){
                    $fecha = Carbon::parse($fecha);
                    $primero_de_mes = Carbon::createFromDate($fecha->year, $fecha->month, 1, 0, 0, 0, 'America/Argentina/Buenos_Aires');
                    $primero_de_mes_siguiente = Carbon::createFromDate($fecha->year, $fecha->add('month', 1)->month, 1, 0, 0, 0, 'America/Argentina/Buenos_Aires');
                    $params = [
                        'id_medico' => $turno['id_medico'],
                        'fecha_desde' => $primero_de_mes,
                        'fecha_hasta' => $primero_de_mes_siguiente
                    ];
                    $turnos = Turno::where('id_medico', '=', $params['id_medico'])
                                    ->whereBetween('start', [$params['fecha_desde'], $params['fecha_hasta']])
                                    ->orderBy('start')
                                    ->get();
                    array_push($extras['responses'], ['turnos' => $turnos]);
                    $data = $turnos;
                    $count = sizeof($turnos);
                    $message = 'Turno eliminado con éxito';
                    $status = 'ok';
                }else{
                    $count = 0;
                    $data = $response;
                    $status = 'fail';
                    $message = 'No se pudo eliminar el turno';
                    array_push($errors, $response);
                }
            }else{
                array_push($errors, 'No se ecuentra el turno con id '.$id_turno);
                array_push($extras['responses'], ['turno' => $turno]);
                $message = 'No se ecuentra el turno con id '.$id_turno;
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
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);

        } catch (\Throwable $th) {
            array_push($errors, 'Error de Backend');
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => $data,
                'params' => $params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        }
    }
}


