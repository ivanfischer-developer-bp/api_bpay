<?php

namespace App\Http\Controllers\Internos\Exportaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
// use Illuminate\Support\Facades\File as FacadeFile;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use File;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;

class ExportarUsuariosController extends ConexionSpController
{

    /**
     * Exporta a pdf los datos de uno o varios usuarios
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function exportar_usuarios(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/admin/user/exportar-usuarios',
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
            $ids_usuarios = request('ids_usuarios');
            $params = [
                'ids_usuarios' => $ids_usuarios,
                'accion' => $accion
            ];
            
            $permiso_requerido = 'exportar datos';
            if($user->hasPermissionTo($permiso_requerido)){
                // buscar usuario, roles, permisos y perfil
                $usuarios = [];
                if($ids_usuarios != ''){
                    $ids_usuarios_array = explode(',', $ids_usuarios);
                    $usuarios = User::with('roles.permissions', 'permissions', 'profile_doctor', 'profile_secretary')
                                ->whereIn('id', $ids_usuarios_array)
                                ->get();
                }else{
                    $status = 'fail';
                    array_push($errors, 'No se han proporcionado IDs de usuarios válidos');
                    $message = 'No se han proporcionado IDs de usuarios válidos';
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
                // return $usuarios;
                if ($usuarios) {
                    $data = [
                        'logo' => env('LOGO_PATH'),
                    ];
                    $pdf = new Fpdi('portrait', 'mm', 'A4');
        
                    $pdf->SetMargins(15, 15);
        
                    $numero_hoja = 0;
                    $cant_hojas = 1;
                    $cant_hojas = ceil(sizeof($usuarios));

                    $n=0;
                    // creamos el pdf
                    foreach ($usuarios as $usuario) {
                        $numero_hoja++;
                        $pdf = $this->agregar_hoja_pdf($pdf, $data, $usuarios, $numero_hoja, $cant_hojas);
                        // $n++;
                        // if($n % 5 == 0){
                        // }
                        $creado = $usuario->created_at ? Carbon::parse($usuario->created_at)->format('d/m/Y') : '';
                        $actualizado = $usuario->updated_at ? Carbon::parse($usuario->updated_at)->format('d/m/Y') : '';
                        $eliminado = $usuario->deleted_at ? Carbon::parse($usuario->deleted_at)->format('d/m/Y') : 'NO';
                        $verificado = $usuario->verified_at ? Carbon::parse($usuario->verified_at)->format('d/m/Y') : 'NO';

                        $pdf->Cell(180, 5, '', 0, 1);
                        $pdf->setFont('Arial', 'B', 14);
                        $pdf->SetTextColor(64, 128, 255);
                        $pdf->Cell(90, 6, utf8_decode('Datos del Usuario'), 0, 1, 'L');

                        $pdf->setFont('Arial', '', 12);
                        $pdf->SetTextColor(64);

                        $pdf->Cell(90, 6, utf8_decode('ID: '.$usuario->id), 0, 0, 'L');
                        $pdf->Cell(90, 6, utf8_decode('ID sqlserver: '.$usuario->id_usuario_sqlserver), 0, 1, 'L');

                        $pdf->Cell(90, 6, utf8_decode('Tipo Documento: '.$usuario->tipo_doc), 0, 0, 'L');
                        $pdf->Cell(90, 6, utf8_decode('Nro Documento: '.$usuario->nro_doc), 0, 1, 'L');

                        $pdf->Cell(90, 6, utf8_decode('Nombre: '.$usuario->nombre), 0, 0, 'L');
                        $pdf->Cell(90, 6, utf8_decode('Apellido: '.$usuario->apellido), 0, 1, 'L');

                        $pdf->Cell(90, 6, utf8_decode('Usuario: '.$usuario->usuario), 0, 0, 'L');
                        $pdf->Cell(90, 6, utf8_decode('Name: '.$usuario->name), 0, 1, 'L');

                        $pdf->Cell(90, 6, utf8_decode('Email: '.$usuario->email), 0, 1, 'L');
                        
                        $pdf->Cell(90, 6, utf8_decode('Creado: '.$creado), 0, 0, 'L');
                        $pdf->Cell(90, 6, utf8_decode('Actualizado: '.$actualizado), 0, 1, 'L');

                        $pdf->Cell(90, 6, utf8_decode('Verificado: '.$verificado), 0, 0, 'L');
                        $pdf->Cell(90, 6, utf8_decode('Eliminado: '.$eliminado), 0, 1, 'L');
                        
                        if($usuario->roles[0]->name == 'prestador'){
                            $pdf->Cell(90, 6, utf8_decode('Empresa: '.$usuario->empresa), 0, 1, 'L');

                            $pdf->Cell(40, 6, utf8_decode('ID Prestador: '.$usuario->id_prestador), 0, 0, 'L');
                            $pdf->Cell(120, 6, utf8_decode('Company name: '.$usuario->company_name), 0, 1, 'L');
                        }

                        if($usuario->roles[0]->name == 'medico'
                            || $usuario->roles[0]->name == 'medico supervisor'
                            || $usuario->roles[0]->name == 'medico administrador'
                            || $usuario->roles[0]->name == 'medico auditor'){
                                $perfil_completo = $usuario->perfil_completo == 1 ? 'Si' : 'No';
                            $pdf->Cell(90, 6, utf8_decode('ID Rol: '.$usuario->roles[0]->id), 0, 0, 'L');
                            $pdf->Cell(90, 6, utf8_decode('Rol: '.mb_convert_case($usuario->roles[0]->name, MB_CASE_TITLE, 'UTF-8')), 0, 1, 'L');
                            if($usuario->profile_doctor != null){
                                $pdf->Cell(180, 5, '', 0, 1);
                                $pdf->setFont('Arial', 'B', 14);
                                $pdf->SetTextColor(64, 128, 255);
                                $pdf->Cell(180, 6, utf8_decode('Perfil '.mb_convert_case($usuario->roles[0]->name, MB_CASE_TITLE, 'UTF-8')), 0, 1, 'L');
                                $pdf->setFont('Arial', '', 12);
                                $pdf->SetTextColor(64);

                                $pdf->Cell(90, 6, utf8_decode('Perfil Completo: '.$perfil_completo), 0, 0, 'L');
                                $pdf->Cell(45, 6, utf8_decode('ID Perfil Doctor: '.$usuario->profile_doctor->id), 0, 0, 'L');
                                $genero = strtoupper($usuario->profile_doctor->sexo) == 'M' 
                                    || strtoupper($usuario->profile_doctor->sexo) == 'MASCULINO'? 'Masculino' 
                                        : (strtoupper($usuario->profile_doctor->sexo) == 'F'
                                        || strtoupper($usuario->profile_doctor->sexo) == 'FEMENINO' ? 'Femenino' 
                                        : (strtoupper($usuario->profile_doctor->sexo) == 'X' ? 'No Binario' 
                                        : 'Sin Especificar'));
                                $pdf->Cell(45, 6, utf8_decode('Género: '.$genero), 0, 1, 'L');

                                $pdf->Cell(90, 6, utf8_decode('Tipo Documento: '.$usuario->tipo_doc), 0, 0, 'L');
                                $pdf->Cell(90, 6, utf8_decode('Número Documento: '.$usuario->nro_doc), 0, 1, 'L');

                                $pdf->Cell(90, 6, utf8_decode('Tratamiento: '.$usuario->profile_doctor->tratamiento), 0, 0, 'L');
                                $pdf->Cell(90, 6, utf8_decode('Nacimiento: '.Carbon::parse($usuario->profile_doctor->fechaNacimiento)->format('d/m/Y')), 0, 1, 'L');

                                $pdf->Cell(90, 6, utf8_decode('Especialidad: '.$usuario->profile_doctor->especialidad), 0, 0, 'L');
                                $pdf->Cell(90, 6, utf8_decode('Matrícula: '.$usuario->profile_doctor->matricula_tipo.' '.$usuario->profile_doctor->matricula_numero.' - '.$usuario->profile_doctor->matricula_provincia), 0, 1, 'L');
                            
                                $pdf->Cell(90, 6, utf8_decode('Teléfono: '.$usuario->profile_doctor->telefono), 0, 0, 'L');
                                $pdf->Cell(90, 6, utf8_decode('Email: '.$usuario->profile_doctor->email), 0, 1, 'L');

                                $pdf->Cell(90, 6, utf8_decode('CUIT: '.$usuario->profile_doctor->cuit), 0, 0, 'L');
                                $pdf->Cell(90, 6, utf8_decode('ID ReFePS: '.$usuario->profile_doctor->idRefeps), 0, 1, 'L');

                                $firma_registrada = $usuario->profile_doctor->firma_registrada == 1 ? 'Si' : 'No';
                                $pdf->Cell(60, 6, utf8_decode('Firma Registrada: '.$firma_registrada), 0, 0, 'L');
                                $pdf->Cell(60, 6, utf8_decode('ID Convenio: '.$usuario->profile_doctor->id_convenio), 0, 0, 'L');
                                $pdf->Cell(60, 6, utf8_decode('Ambiente: '.$usuario->profile_doctor->ambiente), 0, 1, 'L');
                            }else{
                                $pdf->Cell(90, 6, utf8_decode('Perfil Completo: '.$perfil_completo), 0, 1, 'L');
                            }
                            
                        }else if($usuario->roles[0]->name == 'secretaria medico'){
                            $pdf->Cell(90, 6, utf8_decode('ID Rol: '.$usuario->roles[0]->id), 0, 0, 'L');
                            $pdf->Cell(90, 6, utf8_decode('Rol: '.mb_convert_case($usuario->roles[0]->name, MB_CASE_TITLE, 'UTF-8')), 0, 1, 'L');
                            if($usuario->profile_secretary != null){
                                $pdf->Cell(180, 5, '', 0, 1);
                                $pdf->setFont('Arial', 'B', 14);
                                $pdf->SetTextColor(64, 128, 255);
                                $pdf->Cell(180, 6, utf8_decode('Perfil '.mb_convert_case($usuario->roles[0]->name, MB_CASE_TITLE, 'UTF-8')), 0, 1, 'L');
                                $pdf->setFont('Arial', '', 12);
                                $pdf->SetTextColor(64);

                                $pdf->Cell(90, 6, utf8_decode('ID Perfil Secretaria: '.$usuario->profile_secretary->id), 0, 0, 'L');
                                $genero = strtoupper($usuario->profile_secretary->sexo) == 'M' 
                                    || strtoupper($usuario->profile_secretary->sexo) == 'MASCULINO'? 'Masculino' 
                                        : (strtoupper($usuario->profile_secretary->sexo) == 'F'
                                        || strtoupper($usuario->profile_secretary->sexo) == 'FEMENINO' ? 'Femenino' 
                                        : (strtoupper($usuario->profile_secretary->sexo) == 'X' ? 'No Binario' 
                                        : 'Sin Especificar'));
                                $pdf->Cell(90, 6, utf8_decode('Género: '.$genero), 0, 1, 'L');
                            
                                $pdf->Cell(90, 6, utf8_decode('Apellido: '.$usuario->profile_secretary->apellido), 0, 0, 'L');
                                $pdf->Cell(90, 6, utf8_decode('Nombre: '.$usuario->profile_secretary->nombre), 0, 1, 'L');

                                $pdf->Cell(90, 6, utf8_decode('Tipo Documento: '.$usuario->profile_secretary->tipo_doc), 0, 0, 'L');
                                $pdf->Cell(90, 6, utf8_decode('Número Documento: '.$usuario->profile_secretary->nro_doc), 0, 1, 'L');

                                $pdf->Cell(180, 6, utf8_decode('Email: '.$usuario->profile_secretary->email), 0, 1, 'L');
                            }
                        }else{
                            $pdf->Cell(90, 6, utf8_decode('ID Rol: '.$usuario->roles[0]->id), 0, 0, 'L');
                            $pdf->Cell(90, 6, utf8_decode('Rol: '.mb_convert_case($usuario->roles[0]->name, MB_CASE_TITLE, 'UTF-8')), 0, 1, 'L');
                        }

                        $pdf->Cell(180, 5, '', 0, 1);
                        $pdf->setFont('Arial', 'B', 14);
                        $pdf->SetTextColor(64, 128, 255);
                        $pdf->Cell(180, 6, utf8_decode('Permisos del Rol "'.mb_convert_case($usuario->roles[0]->name, MB_CASE_TITLE, 'UTF-8').'"'), 0, 1, 'L');

                        $pdf->setFont('Arial', '', 12);
                        $pdf->SetTextColor(64);
                        $permisos = $usuario->roles[0]->permissions->toArray();
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
                                $pdf = $this->agregar_hoja_pdf($pdf, $data, $usuarios, $numero_hoja, $cant_hojas);
                                $pdf->Cell(180, 5, '', 0, 1);
                                $pdf->setFont('Arial', 'B', 14);
                                $pdf->SetTextColor(64, 128, 255);
                                $pdf->Cell(180, 6, utf8_decode('Usuario: '. $usuario->usuario), 0, 1, 'L');
                                $pdf->Cell(180, 6, utf8_decode('Permisos del Rol "'.mb_convert_case($usuario->roles[0]->name, MB_CASE_TITLE, 'UTF-8').'" (continuación)'), 0, 1, 'L');
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

                        if(!empty($usuario->permissions) && sizeof($usuario->permissions) > 0){
                            $pdf->Cell(180, 5, '', 0, 1);
                            $pdf->setFont('Arial', 'B', 14);
                            $pdf->SetTextColor(64, 128, 255);
                            $pdf->Cell(180, 6, utf8_decode('Permisos Directos Asignados al Usuario'), 0, 1, 'L');

                            $pdf->setFont('Arial', '', 12);
                            $pdf->SetTextColor(64);
                            $permisos = $usuario->permissions->toArray();
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
                                    $pdf = $this->agregar_hoja_pdf($pdf, $data, $usuarios, $numero_hoja, $cant_hojas);
                                    $pdf->Cell(180, 5, '', 0, 1);
                                    $pdf->setFont('Arial', 'B', 14);
                                    $pdf->SetTextColor(64, 128, 255);
                                    $pdf->Cell(180, 6, utf8_decode('Usuario: '. $usuario->usuario), 0, 1, 'L');
                                    $pdf->Cell(180, 6, utf8_decode('Permisos del Rol "'.mb_convert_case($usuario->roles[0]->name, MB_CASE_TITLE, 'UTF-8').'" (continuación)'), 0, 1, 'L');
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
                        }else{
                            $pdf->Cell(180, 5, '', 0, 1);
                            $pdf->setFont('Arial', 'B', 14);
                            $pdf->SetTextColor(64, 128, 255);
                            $pdf->Cell(180, 6, utf8_decode('Permisos Directos Asignados al Usuario: NINGUNO'), 0, 1, 'L');
                        }
                    }
                    //  -------------------
                    if ($accion == 'enviar') {
                        if(!File::exists(env('STORAGE_PATH').'reportes/sistema/usuarios/')){
                            File::makeDirectory(env('STORAGE_PATH').'reportes/sistema/usuarios/');
                        }
                        $file_path = env('STORAGE_PATH').'reportes/sistema/usuarios/';
                        if(!File::exists($file_path)){
                            File::makeDirectory($file_path);
                        }
                        $filename = 'usuarios-' . $logged_user['id'] . '-' . env('AMBIENTE') .'-'. Carbon::now()->format('Ymd') . '.pdf';
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
                        
                        if(!File::exists(env('STORAGE_PATH').'reportes/sistema/usuarios/')){
                            File::makeDirectory(env('STORAGE_PATH').'reportes/sistema/usuarios/');
                        }
                        $file_path = env('STORAGE_PATH').'reportes/sistema/usuarios/';
                        if(!File::exists($file_path)){
                            File::makeDirectory($file_path);
                        }
                        $filename = 'usuarios-' . $logged_user['id'] . '-' . env('AMBIENTE') .'-'. Carbon::now()->format('Ymd') . '.pdf';
                        $file = $file_path.$filename;
                        
                        $pdf->Output($file, "F");
                        return FacadeResponse::make(Storage::disk('usuarios')->get($filename), 200, [
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
    private function agregar_hoja_pdf($pdf, $data, $usuarios, $numero_hoja, $cant_hojas)
    {
        $pdf->AddPage('portrait');
        //  establece valor de tipografia
        $pdf->SetFont('Arial', '', 10);
        $pdf->Image(storage_path('app/public/images/logo.png'), 15, 16, 0, 25, 'PNG');
        $pdf->Cell(60, 14, '', 0, 0); // recuadro del logo

        $pdf->SetFont('Arial', 'B', 24);
        $pdf->SetTextColor(64, 128, 255);
        $pdf->Cell(60, 16, utf8_decode('Usuarios'), 0, 0, 'C');

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

