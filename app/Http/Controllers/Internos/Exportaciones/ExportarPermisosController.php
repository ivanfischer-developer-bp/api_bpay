<?php

namespace App\Http\Controllers\Internos\Exportaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
// use Illuminate\Support\Facades\File as FacadeFile;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use File;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;

class ExportarPermisosController extends ConexionSpController
{

    /**
     * Exporta a pdf los datos de uno o varios roles
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function exportar_permisos(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/permission/exportar-permisos',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'path' => '',
            'queries' => [],
            'responses' => [],
            'sps' => []
        ];
        
        try {
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $errors = [];
            $accion = request('accion') == '' ? 'mostrar' : request('accion');
            $ids_permisos = request('ids_permisos');
            $params = [
                'ids_permisos' => $ids_permisos,
                'accion' => $accion,
            ];
            
            $permiso_requerido = 'exportar datos';
            if($user->hasPermissionTo($permiso_requerido)){
                $permisos = [];
                if($ids_permisos != ''){
                        $ids_permisos_array = explode(',', $ids_permisos);
                        if(sizeof($ids_permisos_array) == 1){
                            $id_permiso = $ids_permisos_array[0];
                            $permiso = Permission::find($id_permiso);
                            $roles = $permiso->getRoleNames();
                            $users = User::permission($permiso->name)->get();
                            $datos = [
                                'permisos' => [],
                                'permiso' => $permiso,
                                'roles' => $roles,
                                'usuarios' => $users
                            ];
                        }else{
                            $permisos = Permission::whereIn('id', $ids_permisos_array)
                                    ->get();
                            $datos = [
                                'permisos' => $permisos,
                                'permiso' => null,
                                'roles' => [],
                                'usuarios' => []
                            ];
                        }
                }

                // return $datos;

                $numero_hoja = 0;
                $cant_hojas = ceil(sizeof($datos['permisos'])) > 0 ? ceil(sizeof($datos['permisos'])) : 1;

                
                $data = [
                    'logo' => env('LOGO_PATH'),
                ];
                
                if ($datos['permiso'] != null) {
                    $numero_hoja++;
                    $pdf = new Fpdi('portrait', 'mm', 'A4');
                    $pdf->SetMargins(15, 15);
                    $pdf = $this->agregar_hoja_pdf($pdf, $data, $datos, $numero_hoja, $cant_hojas);

                    $pdf->Cell(180, 5, '', 0, 1);
                    $pdf->setFont('Arial', 'B', 14);
                    $pdf->SetTextColor(64, 128, 255);
                    $pdf->Cell(90, 6, utf8_decode('ID Permiso: '.$permiso->id), 0, 0, 'L');
                    $pdf->Cell(90, 6, utf8_decode('Permiso: '.mb_convert_case($permiso->name, MB_CASE_TITLE, 'UTF-8')), 0, 1, 'L');
                    $creado = $permiso->created_at ? Carbon::parse($permiso->created_at)->format('d/m/Y') : '';
                    $actualizado = $permiso->updated_at ? Carbon::parse($permiso->updated_at)->format('d/m/Y') : '';
                    $eliminado = $permiso->deleted_at ? Carbon::parse($permiso->deleted_at)->format('d/m/Y') : 'NO';
                    $pdf->Cell(180, 5, '', 0, 1);
                    $pdf->setFont('Arial', 'B', 11);
                    $pdf->SetTextColor(64);
                    $pdf->Cell(60, 6, utf8_decode('Creado: '.$creado), 0, 0, 'L');
                    $pdf->Cell(60, 6, utf8_decode('Actualizado: '.$actualizado), 0, 0, 'L');
                    $pdf->Cell(60, 6, utf8_decode('Eliminado: '.$eliminado), 0, 1, 'L');

                    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());

                    $pdf->Cell(180, 5, '', 0, 1);
                    $pdf->setFont('Arial', 'B', 14);
                    $pdf->SetTextColor(64, 128, 255);
                    $pdf->Cell(180, 6, utf8_decode('Asignado a Roles'), 0, 1, 'L');

                    $pdf->setFont('Arial', '', 12);
                    $pdf->SetTextColor(64);
                    
                    $roles = $datos['roles']->toArray();
                    sort($roles);
                    $columnas = 2;
                    $ancho_columna = 90;
                    $limite_pagina = 270; // límite inferior en mm para A4
                    
                    for ($i = 0; $i < count($roles); $i++) {
                        // Verificar si necesitamos una nueva página
                        if ($pdf->GetY() > $limite_pagina) {
                            $numero_hoja++;
                            $pdf = $this->agregar_hoja_pdf($pdf, $data, $datos, $numero_hoja, $cant_hojas);
                            
                            $pdf->Cell(180, 5, '', 0, 1);
                            $pdf->setFont('Arial', 'B', 14);
                            $pdf->SetTextColor(64, 128, 255);
                            $pdf->Cell(90, 6, utf8_decode('ID Permiso: '.$permiso->id), 0, 0, 'L');
                            $pdf->Cell(90, 6, utf8_decode('Permiso: '.mb_convert_case($permiso->name, MB_CASE_TITLE, 'UTF-8')), 0, 1, 'L');
                            $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
                            $pdf->Cell(180, 5, '', 0, 1);

                            $pdf->setFont('Arial', '', 12);
                            $pdf->SetTextColor(64);
                        }
                        
                        $role = $roles[$i];
                        $pdf->Cell($ancho_columna, 6, utf8_decode($role), 0, 0, 'L');
                        
                        // Si es la última columna o el último permiso, saltar línea
                        if (($i + 1) % $columnas == 0 || $i == count($roles) - 1) {
                            $pdf->Ln();
                        }
                    }

                    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());

                    $pdf->Cell(180, 5, '', 0, 1);
                    $pdf->setFont('Arial', 'B', 14);
                    $pdf->SetTextColor(64, 128, 255);
                    $pdf->Cell(180, 6, utf8_decode('Usuarios con este permiso'), 0, 1, 'L');

                    $pdf->setFont('Arial', 'B', 11);
                    $pdf->SetTextColor(64);
                    $pdf->Cell(8, 6, utf8_decode('ID'), 0, 0, 'L');
                    $pdf->Cell(66, 6, utf8_decode('Usuario'), 0, 0, 'L');
                    $pdf->Cell(76, 6, utf8_decode('Nombre'), 0, 0, 'L');
                    $pdf->Cell(40, 6, utf8_decode('Rol'), 0, 1, 'L');
                    
                    $usuarios = $datos['usuarios']->sortBy('name');
                    $limite_pagina = 270; // límite inferior en mm para A4
                    
                    foreach ($usuarios as $usuario) {
                        // Verificar si necesitamos una nueva página
                        if ($pdf->GetY() > $limite_pagina) {
                            $numero_hoja++;
                            $pdf = $this->agregar_hoja_pdf($pdf, $data, $datos, $numero_hoja, $cant_hojas);
                            
                            $pdf->Cell(180, 5, '', 0, 1);
                            $pdf->setFont('Arial', 'B', 14);
                            $pdf->SetTextColor(64, 128, 255);
                            $pdf->Cell(90, 6, utf8_decode('ID Permiso: '.$permiso->id), 0, 0, 'L');
                            $pdf->Cell(90, 6, utf8_decode('Permiso: '.mb_convert_case($permiso->name, MB_CASE_TITLE, 'UTF-8')), 0, 1, 'L');
                            $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
                            $pdf->Cell(180, 5, '', 0, 1);
                            $pdf->setFont('Arial', 'B', 14);
                            $pdf->SetTextColor(64, 128, 255);
                            $pdf->Cell(180, 6, utf8_decode('Usuarios con este permiso'), 0, 1, 'L');

                            $pdf->setFont('Arial', 'B', 11);
                            $pdf->SetTextColor(64);
                            $pdf->Cell(8, 6, utf8_decode('ID'), 0, 0, 'L');
                            $pdf->Cell(66, 6, utf8_decode('Usuario'), 0, 0, 'L');
                            $pdf->Cell(76, 6, utf8_decode('Nombre'), 0, 0, 'L');
                            $pdf->Cell(40, 6, utf8_decode('Rol'), 0, 1, 'L');
                        }
                        
                        $rol = $usuario->roles->first() ? $usuario->roles->first()->name : 'Sin rol';
                        
                        $pdf->setFont('Arial', '', 10);
                        $pdf->SetTextColor(64);
                        $pdf->Cell(8, 6, utf8_decode($usuario->id), 0, 0, 'L');
                        $pdf->Cell(66, 6, utf8_decode($usuario->usuario), 0, 0, 'L');
                        $pdf->Cell(76, 6, utf8_decode($usuario->name), 0, 0, 'L');;
                        $pdf->Cell(40, 6, utf8_decode($rol), 0, 1, 'L');
                    }

                }

                if($datos['permisos'] != null && sizeof($datos['permisos']) > 0){
                    $numero_hoja++;
                    $pdf = new Fpdi('portrait', 'mm', 'A4');
                    $pdf->SetMargins(15, 15);
                    $pdf = $this->agregar_hoja_pdf($pdf, $data, $datos, $numero_hoja, $cant_hojas);
                    $permisos = $datos['permisos']->sortBy('name');
                    $pdf->Cell(180, 5, '', 0, 1);
                    $pdf->setFont('Arial', 'B', 11);
                    $pdf->SetTextColor(64);
                    $pdf->Cell(15, 6, utf8_decode('Id'), 0, 0, 'L');
                    $pdf->Cell(90, 6, utf8_decode('Nombre'), 0, 0, 'L');
                    $pdf->Cell(30, 6, utf8_decode('Creado'), 0, 0, 'L');
                    $pdf->Cell(30, 6, utf8_decode('Actualizado'), 0, 0, 'L');
                    $pdf->Cell(30, 6, utf8_decode('Eliminado'), 0, 1, 'L');
                    foreach ($permisos as $permiso) {
                        $creado = $permiso->created_at ? Carbon::parse($permiso->created_at)->format('d/m/Y') : '';
                        $actualizado = $permiso->updated_at ? Carbon::parse($permiso->updated_at)->format('d/m/Y') : '';
                        $eliminado = $permiso->deleted_at ? Carbon::parse($permiso->deleted_at)->format('d/m/Y') : 'NO';
                        $pdf->setFont('Arial', '', 11);
                        $pdf->Cell(15, 6, utf8_decode($permiso->id), 0, 0, 'L');
                        $pdf->Cell(90, 6, utf8_decode($permiso->name), 0, 0, 'L');
                        $pdf->Cell(30, 6, utf8_decode($creado), 0, 0, 'L');
                        $pdf->Cell(30, 6, utf8_decode($actualizado), 0, 0, 'L');
                        if($eliminado == 'NO'){
                            $pdf->SetTextColor(64);
                        }else{
                            $pdf->SetTextColor(255, 0, 0);
                        }
                        $pdf->Cell(30, 6, utf8_decode($eliminado), 0, 1, 'L');
                    }
                }
                    //  -------------------
                if ($accion == 'enviar') {
                    if(!File::exists(env('STORAGE_PATH').'reportes/sistema/permisos/')){
                        File::makeDirectory(env('STORAGE_PATH').'reportes/sistema/permisos/');
                    }
                    $file_path = env('STORAGE_PATH').'reportes/sistema/permisos/';
                    if(!File::exists($file_path)){
                        File::makeDirectory($file_path);
                    }
                    if($datos['permiso'] != null){
                        $nombre_permiso = str_replace(' ', '_', mb_convert_case($datos['permiso']->name, MB_CASE_TITLE, 'UTF-8')) . '-' ;
                    }else{
                        $nombre_permiso = '';
                    }
                    $filename = 'permisos-' . $logged_user['id'] . '-' . $nombre_permiso. env('AMBIENTE') .'-'. Carbon::now()->format('Ymd') . '.pdf';
                    $file = $file_path.$filename;
                    $pdf->Output($file, "F");
                    $extras['path'] = $file;
                    return response()->json([
                        'status' => 'ok',
                        'count' => 1,
                        'errors' => $errors,
                        'message' => 'Archivo generado satisfactoriamente',
                        'line' => null,
                        'code' => 1,
                        'data' =>$filename,
                        'params' => $params,
                        'extras' => $extras,
                        'logged_user' => $logged_user,
                    ]);
                }else{
                    
                    if(!File::exists(env('STORAGE_PATH').'reportes/sistema/permisos/')){
                        File::makeDirectory(env('STORAGE_PATH').'reportes/sistema/permisos/');
                    }
                    $file_path = env('STORAGE_PATH').'reportes/sistema/permisos/';
                    if(!File::exists($file_path)){
                        File::makeDirectory($file_path);
                    }
                    $filename = 'permisos-' . $logged_user['id'] . '-' . env('AMBIENTE') .'-'. Carbon::now()->format('Ymd') . '.pdf';
                    $file = $file_path.$filename;
                    
                    $pdf->Output($file, "F");
                    return FacadeResponse::make(Storage::disk('permisos')->get($filename), 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; ' . $filename,
                    ]);
                }
                //  -------------------
                
            }else{
                array_push($errors, 'El usuario no está autorizado en esta ruta');
                return response()->json([
                    'status' => 'unauthorized',
                    'count' => 0,
                    'errors' => $errors,
                    'message' => 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.$permiso_requerido,
                    'line' => null,
                    'code' => -2,
                    'data' => null,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]);
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Añade una nueva hoja con las cabeceras al pdf
     */
    private function agregar_hoja_pdf($pdf, $data, $roles, $numero_hoja, $cant_hojas)
    {
        $pdf->AddPage('portrait');
        //  establece valor de tipografia
        $pdf->SetFont('Arial', '', 10);
        $pdf->Image(storage_path('app/public/images/logo.png'), 15, 16, 0, 25, 'PNG');
        $pdf->Cell(60, 14, '', 0, 0); // recuadro del logo

        $pdf->SetFont('Arial', 'B', 24);
        $pdf->SetTextColor(64, 128, 255);
        $pdf->Cell(60, 16, utf8_decode('Permisos'), 0, 0, 'C');

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(64);
        // $pdf->Cell(60, 8, utf8_decode('Hoja '.$numero_hoja.' de '.$cant_hojas), 0, 2, 'R');
        $pdf->Cell(60, 8, utf8_decode('Hoja '.$numero_hoja), 0, 2, 'R');
        $pdf->Cell(60, 8, utf8_decode('Fecha Impresión: ') . Carbon::now()->format('d/m/Y'), 0, 0, 'R');
        $pdf->Cell(60, 4, '', 0, 2, 'C');

        $pdf->SetY(40);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());

        return $pdf;

    }

    /**
     * Añade el pie de página al pdf
     */
    private function agregar_pie_pdf($pdf, $data)
    {
        return $pdf;
    }

}

