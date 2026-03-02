<?php

namespace App\Http\Controllers\Internos\Exportaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
// use Illuminate\Support\Facades\File as FacadeFile;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;
use Spatie\Permission\Models\Role;

use File;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;

class ExportarRolesController extends ConexionSpController
{

    /**
     * Exporta a pdf los datos de uno o varios roles
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function exportar_roles(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/role/exportar-roles',
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
            $ids_roles = request('ids_roles');
            $params = [
                'ids_roles' => $ids_roles,
                'accion' => $accion
            ];
            
            $permiso_requerido = 'exportar datos';
            if($user->hasPermissionTo($permiso_requerido)){
                $roles = [];
                if($ids_roles != ''){
                    $ids_roles_array = explode(',', $ids_roles);
                    $roles = Role::with('permissions')
                                ->whereIn('id', $ids_roles_array)
                                ->get();
                }else{
                    $status = 'fail';
                    array_push($errors, 'No se han proporcionado IDs de roles válidos');
                    $message = 'No se han proporcionado IDs de roles válidos';
                    $code = -3;
                    return response()->json([
                        'status' => $status,
                        'count' => 0,
                        'errors' => $errors,
                        'message' => $message,
                        'line' => null,
                        'code' => $code,
                        'data' => null,
                        'params' => $params,
                        'logged_user' => $logged_user,
                        'extras' => $extras
                    ]);
                }
                // return $roles;
                if ($roles) {
                    $data = [
                        'logo' => env('LOGO_PATH'),
                    ];
                    $pdf = new Fpdi('portrait', 'mm', 'A4');
        
                    $pdf->SetMargins(15, 15);
        
                    $numero_hoja = 0;
                    $cant_hojas = 1;
                    $cant_hojas = ceil(sizeof($roles));

                    $n=0;
                    // creamos el pdf
                    foreach ($roles as $rol) {
                        $numero_hoja++;
                        $pdf = $this->agregar_hoja_pdf($pdf, $data, $roles, $numero_hoja, $cant_hojas);

                        $pdf->Cell(180, 5, '', 0, 1);
                        $pdf->setFont('Arial', 'B', 14);
                        $pdf->SetTextColor(64, 128, 255);
                        $pdf->Cell(90, 6, utf8_decode('ID Rol: '.$rol->id), 0, 0, 'L');
                        $pdf->Cell(90, 6, utf8_decode('Rol: '.mb_convert_case($rol->name, MB_CASE_TITLE, 'UTF-8')), 0, 1, 'L');

                        $creado = $rol->created_at ? Carbon::parse($rol->created_at)->format('d/m/Y') : '';
                        $actualizado = $rol->updated_at ? Carbon::parse($rol->updated_at)->format('d/m/Y') : '';
                        $eliminado = $rol->deleted_at ? Carbon::parse($rol->deleted_at)->format('d/m/Y') : 'NO';
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
                        $pdf->Cell(180, 6, utf8_decode('Permisos del Rol '), 0, 1, 'L');

                        $pdf->setFont('Arial', '', 12);
                        $pdf->SetTextColor(64);
                        $permisos = $rol->permissions->toArray();
                        usort($permisos, function($a, $b) {
                            return strcmp($a['name'], $b['name']);
                        });
                        $columnas = 2;
                        $ancho_columna = 90;
                        $limite_pagina = 270; // límite inferior en mm para A4
                        
                        for ($i = 0; $i < count($permisos); $i++) {
                            // Verificar si necesitamos una nueva página
                            if ($pdf->GetY() > $limite_pagina) {
                                $numero_hoja++;
                                $pdf = $this->agregar_hoja_pdf($pdf, $data, $roles, $numero_hoja, $cant_hojas);
                                $pdf->Cell(180, 5, '', 0, 1);
                                $pdf->setFont('Arial', 'B', 14);
                                $pdf->SetTextColor(64, 128, 255);
                                $pdf->Cell(90, 6, utf8_decode('ID Rol: '.$rol->id), 0, 0, 'L');
                                $pdf->Cell(90, 6, utf8_decode('Rol: '.mb_convert_case($rol->name, MB_CASE_TITLE, 'UTF-8')), 0, 1, 'L');
                                $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
                                $pdf->setFont('Arial', '', 12);
                                $pdf->SetTextColor(64);
                            }
                            
                            $permission = (object)$permisos[$i];
                            $pdf->Cell($ancho_columna, 6, utf8_decode($permission->id.'. - '.$permission->name), 0, 0, 'L');
                            
                            // Si es la última columna o el último permiso, saltar línea
                            if (($i + 1) % $columnas == 0 || $i == count($permisos) - 1) {
                                $pdf->Ln();
                            }
                        }
                    }
                    //  -------------------
                    if ($accion == 'enviar') {
                        if(!File::exists(env('STORAGE_PATH').'reportes/sistema/roles/')){
                            File::makeDirectory(env('STORAGE_PATH').'reportes/sistema/roles/');
                        }
                        $file_path = env('STORAGE_PATH').'reportes/sistema/roles/';
                        if(!File::exists($file_path)){
                            File::makeDirectory($file_path);
                        }
                        $filename = 'roles-' . $logged_user['id'] . '-' . env('AMBIENTE') .'-'. Carbon::now()->format('Ymd') . '.pdf';
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
                        
                        if(!File::exists(env('STORAGE_PATH').'reportes/sistema/roles/')){
                            File::makeDirectory(env('STORAGE_PATH').'reportes/sistema/roles/');
                        }
                        $file_path = env('STORAGE_PATH').'reportes/sistema/roles/';
                        if(!File::exists($file_path)){
                            File::makeDirectory($file_path);
                        }
                        $filename = 'roles-' . $logged_user['id'] . '-' . env('AMBIENTE') .'-'. Carbon::now()->format('Ymd') . '.pdf';
                        $file = $file_path.$filename;
                        
                        $pdf->Output($file, "F");
                        return FacadeResponse::make(Storage::disk('roles')->get($filename), 200, [
                            'Content-Type' => 'application/pdf',
                            'Content-Disposition' => 'inline; ' . $filename,
                        ]);
                    }
                    //  -------------------
                }
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
        $pdf->Cell(60, 16, utf8_decode('Roles'), 0, 0, 'C');

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

