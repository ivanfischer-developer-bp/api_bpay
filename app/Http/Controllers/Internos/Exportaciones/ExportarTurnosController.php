<?php

namespace App\Http\Controllers\Internos\Exportaciones;

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

class ExportarTurnosController extends Controller
{
    /**
     * Exporta a pdf el listado de turnos atendidos recibido como un json
     */
    public function exportar_listado_turnos(Request $request)
    {
        
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/consultorio/turnos/exportar-listado-turnos',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => []
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
            
            $accion = request('accion');
            $estado = request('estado');
            $fecha_desde = request('fecha_desde');
            $fecha_hasta = request('fecha_hasta');
            $nombre_medico = request('nombre_medico') != null ? request('nombre_medico') : 'Todos';
            $cantidad = request('cantidad');
            $listado_turnos =  request('turnos');
            // $listado_turnos = (array) json_decode(json_encode(json_decode(request('turnos'))));
            $params = [
                'accion' => $accion,
                'estado' => $estado,
                'fecha_desde' => $fecha_desde,
                'fecha_hasta' => $fecha_desde,
                'nombre_medico' => $nombre_medico,
                'cantidad' => $cantidad,
                'listado_turnos' => $listado_turnos,
            ];
            // solo para prueba de mucho turnos
            // foreach ($listado_turnos as $key => $value) {
            //     array_push($listado_turnos, $value);
            //     array_push($listado_turnos, $value);
            // }
            // return $listado_turnos;
            // return $params;
            $desde = Carbon::parse($fecha_desde);
            $hasta = Carbon::parse($fecha_hasta);
            $d = Carbon::parse($fecha_desde)->format('d-m-Y');
            $h = Carbon::parse($fecha_hasta)->format('d-m-Y');

            if($user->hasPermissionTo('exportar datos')){
                // usort($listado_turnos, object_sorter('nombre_medico', 'DESC', true)); // array, orden, sencible a mayúsculas
                usort($listado_turnos, array_sorter('nombre_medico', 'DESC', true)); // array, orden, sencible a mayúsculas
                // if($listado_turnos != null && sizeof($listado_turnos) > 0){
                if($listado_turnos != null && $cantidad > 0){
                    // $total_lineas = sizeof($listado_turnos);
                    $total_lineas = $cantidad; // sizeof($listado_turnos);
                    $lineas_por_pagina = 45;
                    $total_paginas = 0;
                    $pagina_actual = 0;
                    $total_paginas = intval(round(($total_lineas/$lineas_por_pagina), 0, PHP_ROUND_HALF_UP));
                    $total_paginas = $total_paginas > 1 ? $total_paginas : 1;
    
                    //	crea el objeto y establece variables
                    // $pdf = new \fpdf\FPDF('portrait', 'mm', 'A4');
                    $pdf = new Fpdi('portrait', 'mm', 'A4');
                    //  font setup
                    $font = 'Arial';
                    //  -------------------
                    $pdf->SetMargins(15, 15);
                    $pdf->AddPage('portrait');
                    $pagina_actual++;
                    $pdf->SetFont($font, 'B', 14);
                    $title = utf8_decode('Listado de turnos '.$estado.'s');
                    $pdf->Cell(180, 10, $title, 0, 1, 'L');
                    $pdf->SetFont($font, 'B', 10);
                    if($nombre_medico == 'Todos'){
                        $pdf->Cell(90, 5, utf8_decode('Medico: '.$listado_turnos[0]['nombre_medico']), 0, 0, 'L');
                        // $pdf->Cell(90, 5, utf8_decode('Medico: '.$listado_turnos[0]->nombre_medico), 0, 0, 'L');
                    }else{
                        $pdf->Cell(90, 5, utf8_decode('Medico: '.$nombre_medico), 0, 0, 'L');
                    }
                    $pdf->Cell(90, 5, 'Fecha: '.$d.' al '.$h, 0, 1, 'R');
                    $pdf->Cell(180, 5, "", 0, 2, 'C'); // espacio en blanco
                    
                    // cabecera tabla
                    $pdf->Cell(180, 0.01, '', 1, 1, 'C'); // línea
                    $pdf->Cell(15, 5, utf8_decode("Fecha"), 0, 0, 'L');
                    $pdf->Cell(50, 5, utf8_decode("Paciente"), 0, 0, 'L');
                    $pdf->Cell(35, 5, utf8_decode("Origen"), 0, 0, 'L');
                    $pdf->Cell(35, 5, utf8_decode("Convenio"), 0, 0, 'L');
                    $pdf->Cell(45, 5, utf8_decode("Plan"), 0, 1, 'L');
                    // $pdf->Cell(30, 5, "Observaciones", 1, 0, 'L');
                    $pdf->SetFont($font, '', 8);
                    $i = 0;
                    $c = 0;
                    foreach($listado_turnos as $turno){
                        $c++;
                        if($c > $lineas_por_pagina){
                            $pdf->AddPage('portrait');
                            $pagina_actual++;
                            $c = 0;
                            $pdf->SetFont($font, 'B', 14);
                            $title = utf8_decode('Listado de turnos '.$estado.'s');
                            $pdf->Cell(180, 10, $title, 0, 1, 'L');
                            $pdf->SetFont($font, 'B', 10);
                            if($nombre_medico == 'Todos'){
                                // $pdf->Cell(90, 5, utf8_decode('Medico: '.$listado_turnos[0]->nombre_medico), 0, 0, 'L');
                                $pdf->Cell(90, 5, utf8_decode('Medico: '.$listado_turnos[0]['nombre_medico']), 0, 0, 'L');
                            }else{
                                $pdf->Cell(90, 5, utf8_decode('Medico: '.$nombre_medico), 0, 0, 'L');
                            }
                            $pdf->Cell(90, 5, 'Fecha: '.$d.' al '.$h, 0, 1, 'R');
                            $pdf->Cell(180, 5, "", 0, 2, 'C'); // espacio en blanco
                            
                            // cabecera tabla
                            $pdf->Cell(180, 0.01, '', 1, 1, 'C'); // línea
                            $pdf->Cell(15, 5, utf8_decode("Fecha"), 0, 0, 'L');
                            $pdf->Cell(50, 5, utf8_decode("Paciente"), 0, 0, 'L');
                            $pdf->Cell(35, 5, utf8_decode("Origen"), 0, 0, 'L');
                            $pdf->Cell(35, 5, utf8_decode("Convenio"), 0, 0, 'L');
                            $pdf->Cell(45, 5, utf8_decode("Plan"), 0, 1, 'L');
                            // $pdf->Cell(30, 5, "Observaciones", 1, 0, 'L');
                            $pdf->SetFont($font, '', 8);
                        }
                        if($nombre_medico == 'Todos'){
                            if($i > 0){
                                // if($turno->nombre_medico !== $listado_turnos[$i -1]->nombre_medico){
                                if($turno['nombre_medico'] !== $listado_turnos[$i -1]['nombre_medico']){
                                    $pdf->AddPage('portrait');
                                    $pdf->SetFont($font, 'B', 14);
                                    $title = utf8_decode('Listado de turnos '.$estado.'s');
                                    $pdf->Cell(180, 10, $title, 0, 1, 'L');
                                    $pdf->SetFont($font, 'B', 10);
                                    $pdf->Cell(90, 5, utf8_decode('Medico: '.$turno['nombre_medico']), 0, 0, 'L');
                                    // $pdf->Cell(90, 5, utf8_decode('Medico: '.$turno->nombre_medico), 0, 0, 'L');
                                    $pdf->Cell(90, 5, 'Fecha: '.$d.' al '.$h, 0, 1, 'R');
                                    $pdf->Cell(180, 5, "", 0, 2, 'C'); // espacio en blanco
                                    
                                    // cabecera tabla
                                    $pdf->Cell(180, 0.01, '', 1, 1, 'C'); // línea
                                    $pdf->Cell(15, 5, utf8_decode("Fecha"), 0, 0, 'L');
                                    $pdf->Cell(50, 5, utf8_decode("Paciente"), 0, 0, 'L');
                                    $pdf->Cell(35, 5, utf8_decode("Origen"), 0, 0, 'L');
                                    $pdf->Cell(35, 5, utf8_decode("Convenio"), 0, 0, 'L');
                                    $pdf->Cell(45, 5, utf8_decode("Plan"), 0, 1, 'L');

                                    $pdf->SetFont($font, '', 8);
                                    $pdf->Cell(180, 0.01, '', 1, 1, 'C'); // línea
                                    $fecha = Carbon::parse($turno['date'])->format('d/m/y');
                                    // $fecha = Carbon::parse($turno->date)->format('d/m/y');
                                    $pdf->Cell(15, 5, utf8_decode($fecha), 0, 0, 'L');
                                    $pdf->Cell(50, 5, utf8_decode(capitalize($turno['nombre_afiliado'])), 0, 0, 'L');
                                    $pdf->Cell(35, 5, utf8_decode(capitalize($turno['nombre_origen'])), 0, 0, 'L');
                                    $pdf->Cell(35, 5, utf8_decode(capitalize($turno['nombre_convenio'])), 0, 0, 'L');
                                    $pdf->Cell(45, 5, utf8_decode(capitalize($turno['nombre_plan'])), 0, 1, 'L');
                                    // $pdf->Cell(50, 5, utf8_decode(capitalize($turno->nombre_afiliado)), 0, 0, 'L');
                                    // $pdf->Cell(35, 5, utf8_decode(capitalize($turno->nombre_origen)), 0, 0, 'L');
                                    // $pdf->Cell(35, 5, utf8_decode(capitalize($turno->nombre_convenio)), 0, 0, 'L');
                                    // $pdf->Cell(45, 5, utf8_decode(capitalize($turno->nombre_plan)), 0, 1, 'L');
                                }else{
                                    $pdf->SetFont($font, '', 8);
                                    $pdf->Cell(180, 0.01, '', 1, 1, 'C'); // línea
                                    $fecha = Carbon::parse($turno['date'])->format('d/m/y');
                                    // $fecha = Carbon::parse($turno->date)->format('d/m/y');
                                    $pdf->Cell(15, 5, utf8_decode($fecha), 0, 0, 'L');
                                    $pdf->Cell(50, 5, utf8_decode(capitalize($turno['nombre_afiliado'])), 0, 0, 'L');
                                    $pdf->Cell(35, 5, utf8_decode(capitalize($turno['nombre_origen'])), 0, 0, 'L');
                                    $pdf->Cell(35, 5, utf8_decode(capitalize($turno['nombre_convenio'])), 0, 0, 'L');
                                    $pdf->Cell(45, 5, utf8_decode(capitalize($turno['nombre_plan'])), 0, 1, 'L');
                                    // $pdf->Cell(50, 5, utf8_decode(capitalize($turno->nombre_afiliado)), 0, 0, 'L');
                                    // $pdf->Cell(35, 5, utf8_decode(capitalize($turno->nombre_origen)), 0, 0, 'L');
                                    // $pdf->Cell(35, 5, utf8_decode(capitalize($turno->nombre_convenio)), 0, 0, 'L');
                                    // $pdf->Cell(45, 5, utf8_decode(capitalize($turno->nombre_plan)), 0, 1, 'L');
                                }
                            }else{
                                $pdf->SetFont($font, '', 8);
                                $pdf->Cell(180, 0.01, '', 1, 1, 'C'); // línea
                                $fecha = Carbon::parse($turno['date'])->format('d/m/y');
                                // $fecha = Carbon::parse($turno->date)->format('d/m/y');
                                $pdf->Cell(15, 5, utf8_decode($fecha), 0, 0, 'L');
                                $pdf->Cell(50, 5, utf8_decode(capitalize($turno['nombre_afiliado'])), 0, 0, 'L');
                                $pdf->Cell(35, 5, utf8_decode(capitalize($turno['nombre_origen'])), 0, 0, 'L');
                                $pdf->Cell(35, 5, utf8_decode(capitalize($turno['nombre_convenio'])), 0, 0, 'L');
                                $pdf->Cell(45, 5, utf8_decode(capitalize($turno['nombre_plan'])), 0, 1, 'L');
                                // $pdf->Cell(50, 5, utf8_decode(capitalize($turno->nombre_afiliado)), 0, 0, 'L');
                                // $pdf->Cell(35, 5, utf8_decode(capitalize($turno->nombre_origen)), 0, 0, 'L');
                                // $pdf->Cell(35, 5, utf8_decode(capitalize($turno->nombre_convenio)), 0, 0, 'L');
                                // $pdf->Cell(45, 5, utf8_decode(capitalize($turno->nombre_plan)), 0, 1, 'L');
                            }
                        }else{
                            $pdf->SetFont($font, '', 8);
                            $pdf->Cell(180, 0.01, '', 1, 1, 'C'); // línea
                            $fecha = Carbon::parse($turno['date'])->format('d/m/y');
                            // $fecha = Carbon::parse($turno->date)->format('d/m/y');
                            $pdf->Cell(15, 5, utf8_decode($fecha), 0, 0, 'L');
                            $pdf->Cell(50, 5, utf8_decode(capitalize($turno['nombre_afiliado'])), 0, 0, 'L');
                            $pdf->Cell(35, 5, utf8_decode(capitalize($turno['nombre_origen'])), 0, 0, 'L');
                            $pdf->Cell(35, 5, utf8_decode(capitalize($turno['nombre_convenio'])), 0, 0, 'L');
                            $pdf->Cell(45, 5, utf8_decode(capitalize($turno['nombre_plan'])), 0, 1, 'L');
                            // $pdf->Cell(50, 5, utf8_decode(capitalize($turno->nombre_afiliado)), 0, 0, 'L');
                            // $pdf->Cell(35, 5, utf8_decode(capitalize($turno->nombre_origen)), 0, 0, 'L');
                            // $pdf->Cell(35, 5, utf8_decode(capitalize($turno->nombre_convenio)), 0, 0, 'L');
                            // $pdf->Cell(45, 5, utf8_decode(capitalize($turno->nombre_plan)), 0, 1, 'L');
                        }
                        $i++;
                    }
                    $pdf->Cell(180, 0.01, '', 1, 1, 'C'); // línea
    
                    // pie de página
                    $pdf->setX(15);
                    $pdf->setY(270);
                    // $pdf->Cell(180, 5, "Hoja ".$pagina_actual." de ".$total_paginas, 0, 2, 'R');
                }else{
                    array_push($errors, 'turnos vacío');
                    return response()->json([
                        'status' => 'fail',
                        'count' => 0,
                        'errors' => $errors,
                        'message' => 'Turnos no puede estar vacío',
                        'line' => null,
                        'code' => -1,
                        'data' => $data,
                        'params' => $params,
                        'logged_user' => $logged_user != null ? $logged_user : null,
                        'extras' => $extras
                    ]);
                }

                if ($accion == 'enviar') {
                    $file_path = env('STORAGE_PATH').'reportes/listados_turnos_medicos/';
                    $filename = utf8_decode($nombre_medico).'-'.$d.'-'.$h.'-'.$estado.'.pdf';
                    $file = $file_path.$filename;
                    $extras['path'] = $file;
                    $pdf->Output($file, "F");
                    $data = $filename;
                    $status = 'ok';
                    $count = 1;
                    $errors = [];
                    $message = 'Archivo generado satisfactoriamente';
                    return response()->json([
                        'status' => $status,
                        'count' => $count,
                        'errors' => $errors,
                        'message' => null,
                        'line' => null,
                        'code' => 1,
                        'data' => $data,
                        'params' => $params,
                        'logged_user' => $logged_user != null ? $logged_user : null,
                        'extras' => $extras
                    ]);
                }else{
                    $file_path = env('STORAGE_PATH').'reportes/listados_turnos_medicos/';
                    $filename = utf8_decode($nombre_medico).'-'.$d.'-'.$h.'-'.$estado.'.pdf';
                    $file = $file_path.$filename;
                    $pdf->Output($file, "F");
                    return FacadeResponse::make(Storage::disk('listados_turnos_medicos')->get($filename), 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; ' . $filename,
                    ]);
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para EXPORTAR DATOS';
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
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]); 
            }
        } catch (\Throwable $th) {
            $extras['path'] = env('STORAGE_PATH').'/reportes/listado_turnos/';
            array_push($errors, 'Error de backend');
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => $data,
                'params' => $params,
                'logged_user' => $logged_user != null ? $logged_user : null,
                'extras' => $extras
            ]);
        }
    }
}