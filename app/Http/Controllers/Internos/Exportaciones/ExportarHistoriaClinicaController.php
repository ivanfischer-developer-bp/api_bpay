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

class ExportarHistoriaClinicaController extends ConexionSpController
{
    public function exportar_historia_clinica(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/afiliaciones/historia-clinica/exportar',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'responses' => [],
            'sps' => [],
            'queries' => []
        ];
        try {
            $status = 'fail';
            $count = -1;
            $errors = [];
            $message = null;
            $line = null;
            $code = null;
            $data = null;
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
           
            $nro_afiliado = request('nro_afiliado');
            $id_hoja = request('id_hoja');
            $accion = request('accion') != null ? request('accion') : 'mostrar';
            $extras['accion'] = $accion;
            // return $nro_afiliado;
            array_push($extras['sps'], ['sp_afiliado_Select' => ['nro_afiliado' => $nro_afiliado]]);
            array_push($extras['queries'], $this->get_query('afiliacion', 'sp_afiliado_Select', ['nro_afiliado' => $nro_afiliado]));
            $afiliado = $this->ejecutar_sp_directo('afiliacion', 'sp_afiliado_Select', ['nro_afiliado' => $nro_afiliado]);
            array_push($extras['responses'], ['sp_afiliado_Select' => $afiliado]);
            $afiliado = $afiliado[0];
            // return $afiliado;

            if($user->hasPermissionTo('exportar datos')){
                // if(isset($id_hoja) && $id_hoja != null){
                //     $extras['sps'] = array('AWEB_TraerHojaHistoriaClinica');
                //     $this->params = [
                //         'doc_tipo' => $afiliado->abrev_tipo_doc, 
                //         'doc_numero' => $afiliado->nro_doc,
                //         'id_hoja' => $id_hoja
                //     ];
                //     $historia_clinica = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerHojaHistoriaClinica', null);
                //     $extras['response'] = $historia_clinica;
                // }else{
                    $this->params = [
                        'tipo_documento' => $afiliado->abrev_tipo_doc, 
                        'numero_documento' => $afiliado->nro_doc
                    ];
                    array_push($extras['sps'], ['AWEB_TraerHojasHistoriaClinica' => $this->params]);
                    array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerHojasHistoriaClinica', null));
                    $historia_clinica = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerHojasHistoriaClinica', null);
                    array_push($extras['responses'], ['AWEB_TraerHojasHistoriaClinica' => $historia_clinica]);
                // }
                // return $historia_clinica;
                if(empty($historia_clinica)){
                    $status = 'empty';
                    $count = 0;
                    $errors = [];
                    $message = 'No se encontraron registros';
                }else{
                    $status = 'ok';
                    $count = sizeof($historia_clinica);
                    $errors = [];
                    $message = 'Registros encontrados';
                    $data = $historia_clinica;

                    $numeroBarra = $afiliado->abrev_tipo_doc.$afiliado->nro_doc;
                    $datos = [
                        'logo' => env('IMAGE_PATH').env('LOGO'),
                        'codigo_barra' => '*' . $numeroBarra . '*',
                        'n_afiliado' => $afiliado->n_afiliado,
                        'obra_social' => $afiliado->n_convenio,
                        'plan' => $afiliado->n_plan,
                        'nombre_afiliado' => $afiliado->n_persona,
                        'documento' => $afiliado->abrev_tipo_doc.' '.$afiliado->nro_doc,
                        'contacto' => $afiliado->n_contacto,
                        'domicilio' => $afiliado->n_domicilio,
                        'localidad' => $afiliado->n_localidad,
                    ];
                    // return $datos;

                    //generamos y exportamos el pdf
                    $pdf = new Fpdi('portrait', 'mm', 'A4');
                
                    //  font setup
                    $font = 'Arial';
                    //  -------------------
                    $pdf->SetMargins(15, 15);
                    
                    $numero_hoja = 0;
                    $cant_hojas = $count; // ceil(sizeof($historia_clinica)/7);

                    if($id_hoja != null && $id_hoja != ''){
                        $numero_hoja = 1;
                        $cant_hojas = 1;
                        foreach($historia_clinica as $hc){
                            if($id_hoja == $hc->id_hoja){
                                // return $hc;
                                $pdf = $this->agregar_hoja_pdf($pdf, $datos, $hc, $numero_hoja, $cant_hojas);
                                $pdf = $this->agregar_datos_pdf($pdf, $hc);
                                $pdf = $this->agregar_pie_pdf($pdf, $datos);
                            }
                        }
                    }else{
                        foreach($historia_clinica as $hc){
                            $numero_hoja++;
                            $pdf = $this->agregar_hoja_pdf($pdf, $datos, $hc, $numero_hoja, $cant_hojas);
                            $pdf = $this->agregar_datos_pdf($pdf, $hc);
                            $pdf = $this->agregar_pie_pdf($pdf, $datos);
                        }
                    }

                    if ($accion == 'enviar') {
                        // if(!File::exists(env('STORAGE_PATH').'reportes/historias_clinicas/')){
                        //     File::makeDirectory(env('STORAGE_PATH').'reportes/historias_clinicas/');
                        // }
                        // $file_path = env('STORAGE_PATH').'reportes/historias_clinicas/';
                        // if(!File::exists($file_path)){
                        //     File::makeDirectory($file_path);
                        // }
                        // $filename = 'historia-clinica-'.$afiliado->n_afiliado.'.pdf';
                        // $file = $file_path.$filename;
                        $filename = 'historia-clinica-'.$afiliado->n_afiliado.'.pdf';
                        $file = storage_path('app/public/reportes/historias_clinicas/historia-clinica-'.$afiliado->n_afiliado.'.pdf');
                        $extras['path'] = $file;
                        $pdf->Output($file, "F");
                        return response()->json([
                            'status' => 'ok',
                            'count' => 1,
                            'data' =>$filename,
                            'errors' => $errors,
                            'message' => 'Archivo generado satisfactoriamente',
                            'line' => null,
                            'code' => 1,
                            'params' => $this->params,
                            'logged_user' => $logged_user,
                            'extras' => $extras
                        ]);
                    }else{
                        // if(!File::exists(env('STORAGE_PATH').'reportes/historias_clinicas/')){
                        //     File::makeDirectory(env('STORAGE_PATH').'reportes/historias_clinicas/');
                        // }
                        // $file_path = env('STORAGE_PATH').'reportes/historias_clinicas/';
                        // if(!File::exists($file_path)){
                        //     File::makeDirectory($file_path);
                        // }
                        // $filename = 'historia-clinica-'.$afiliado->n_afiliado.'.pdf';
                        // $file = $file_path.$filename;
                        $file = storage_path('app/public/reportes/historias_clinicas/historia-clinica-'.$afiliado->n_afiliado.'.pdf');
                        $filename = 'historia-clinica-'.$afiliado->n_afiliado.'.pdf';
                        $pdf->Output($file, "F");
                        return FacadeResponse::make(Storage::disk('historias_clinicas')->get($filename), 200, [
                            'Content-Type' => 'application/pdf',
                            'Content-Disposition' => 'inline; ' . $filename,
                        ]);
                    }
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para EXPORTAR DATOS';
                $count  = 0;
                $data = null;
            }
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => null,
                'code' => 1,
                'data' => $data,
                'params' => $this->params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);

        } catch (\Throwable $th) {
            $extras['data'] = $data;
            array_push($errors, 'Error de backend');
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $this->params,
                'logged_user' => $logged_user,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Añade una nueva hoja con las cabeceras al pdf
     */
    private function agregar_hoja_pdf($pdf, $data, $cobertura, $numero_hoja, $cant_hojas)
    {
        $pdf->AddPage('portrait');
        //  establece valor de tipografia
        $pdf->SetFont('Arial', '', 10);
                
        //  código de barras
        get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
        get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);
        
        //  cabecera
        $pdf->Image(storage_path('app/public/images/logo.png'), 15, 16, 30, 7,'PNG');
        $pdf->Cell(60, 14, '', 0, 0); // recuadro del logo
        $pdf->Cell(60, 14, '', 0, 0, 'C'); // recuadro del codigo de barras
        
        $pdf->Cell(60, 5, utf8_decode('Hoja '.$numero_hoja.' de '.$cant_hojas), 0, 2, 'R');
        $pdf->Cell(60, 5, utf8_decode('Fecha Impresión: ') . Carbon::now()->format('d/m/Y'), 0, 1, 'R');
        $pdf->Cell(60, 5, '', 0, 2, 'C');

        $pdf->Cell(180, 7, '', 0, 1); // espacio
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(180, 7, utf8_decode('Historia Clínica'), 0, 2, 'C');

        //  establece valor de tipografia
        $pdf->SetFont('Arial', 'B', 8);

        //  establece valor de tipografia
        $pdf->SetFont('Arial', '', 10);

        //  primera línea
        $pdf->Cell(60, 5, utf8_decode('Nro. Afiliado: ') . $data['n_afiliado'], 0, 0);
        $pdf->Cell(60, 5, utf8_decode('Obra Social: ') . utf8_decode($data['obra_social']), 0, 0, 'C');
        $pdf->Cell(60, 5, utf8_decode('Plan: ') . utf8_decode($data['plan']), 0, 1, 'R');

        //  segunda línea
        $pdf->Cell(110, 5, utf8_decode('Nombre: ') . utf8_decode($data['nombre_afiliado']), 0, 0);
        $pdf->Cell(70, 5, utf8_decode('Documento: ') . $data['documento'], 0, 1, 'R');

        //  tercera línea
        $pdf->Cell(180, 5, utf8_decode('Contacto: ') . $data['contacto'], 0, 1);
        
        // cuarta línea
        $pdf->Cell(180, 5, utf8_decode('Domicilio: ') . utf8_decode($data['domicilio']), 0, 1, 'L');

        // quinta línea
        $pdf->Cell(180, 5, utf8_decode('Localidad: ') . utf8_decode($data['localidad']), 0, 1, 'L');

        return $pdf;

    }

    /**
     * Añade los datos de la historia clínica al pdf
     */
    private function agregar_datos_pdf($pdf, $hc)
    {
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(180, 4, '', 0, 1); // dejamos un espacio antes de la primera para separar el encabezado
        // Línea doble
        $pdf->Cell(180, 0, '', 1, 1); 
        $pdf->Cell(180, 1, '', 0, 1); 
        $pdf->Cell(180, 0, '', 1, 1); 
        $pdf->Cell(180, 4, '', 0, 1); // dejamos un espacio después de la segunda para separar el encabezado

        //fecha: 
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(40, 7, 'Fecha de Control: ', 0, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(125, 7, Carbon::parse($hc->fecha)->format('d/m/Y'), 0, 0);
        $pdf->SetFont('Arial', '', 6);
        $pdf->Cell(15, 7, $hc->id_hoja, 0, 1, 'R');
        // epicrisis:
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(20, 7, 'Epicrisis: ', 0, 1);
        $pdf->SetFont('Arial', '', 12);
        $pdf->MultiCell(180, 5, utf8_decode($hc->texto), 0, 'J');
        // linea
        $pdf->Cell(180, 3, '', 0, 1);
        $pdf->Cell(180, 0, '', 1, 1);
        $pdf->Cell(180, 3, '', 0, 1);
        // talla, peso, TA, MBI, FC
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(15, 7, 'Talla: ', 0, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(20, 7, utf8_decode($hc->Talla), 0, 0);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(15, 7, 'Peso: ', 0, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(20, 7, utf8_decode($hc->Peso), 0, 0);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(15, 7, 'I.M.C.: ', 0, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(20, 7, utf8_decode($hc->BMI), 0, 0);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(12, 7, 'T.A.: ', 0, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(20, 7, utf8_decode($hc->TA), 0, 0);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(12, 7, 'F.C.: ', 0, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(20, 7, utf8_decode($hc->FC), 0, 1);
        // controles realizados
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(180, 7, utf8_decode('Controles Realizados: '), 0, 1);
        // piel
        $pdf->Cell(12, 7, utf8_decode('Piel: '), 0, 0);
        $pdf->SetFont('Arial', '', 12);
        if($hc->Piel == 1){
            $pdf->Cell(10, 7, utf8_decode('SI'), 0, 0);
        }else if($hc->Piel == 0){
            $pdf->Cell(10, 7, utf8_decode('NO'), 0, 0);
        }else{
            $pdf->Cell(10, 7, '', 0, 0);
        }
        // dentadura
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(25, 7, utf8_decode('Dentadura: '), 0, 0);
        $pdf->SetFont('Arial', '', 12);
        if($hc->Dentadura == 1){
            $pdf->Cell(10, 7, utf8_decode('SI'), 0, 0);
        }else if($hc->Dentadura == 0){
            $pdf->Cell(10, 7, utf8_decode('NO'), 0, 0);
        }else{
            $pdf->Cell(10, 7, '', 0, 0);
        }
        // pulmones
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(25, 7, utf8_decode('Pulmones: '), 0, 0);
        $pdf->SetFont('Arial', '', 12);
        if($hc->Pulmones == 1){
            $pdf->Cell(10, 7, utf8_decode('SI'), 0, 0);
        }else if($hc->Pulmones == 0){
            $pdf->Cell(10, 7, utf8_decode('NO'), 0, 0);
        }else{
            $pdf->Cell(10, 7, '', 0, 0);
        }
        // Corazón
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(20, 7, utf8_decode('Corazón: '), 0, 0);
        $pdf->SetFont('Arial', '', 12);
        if($hc->Corazon == 1){
            $pdf->Cell(10, 7, utf8_decode('SI'), 0, 0);
        }else if($hc->Corazon == 0){
            $pdf->Cell(10, 7, utf8_decode('NO'), 0, 0);
        }else{
            $pdf->Cell(10, 7, '', 0, 0);
        }
        // Abdomen
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(25, 7, utf8_decode('Abdomen: '), 0, 0);
        $pdf->SetFont('Arial', '', 12);
        if($hc->Abdomen == 1){
            $pdf->Cell(10, 7, utf8_decode('SI'), 0, 1);
        }else if($hc->Abdomen == 0){
            $pdf->Cell(10, 7, utf8_decode('NO'), 0, 1);
        }else{
            $pdf->Cell(10, 7, '', 0, 0);
        }
        // Odoscopia_normal
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(40, 7, utf8_decode('Odoscopía Normal: '), 0, 0);
        $pdf->SetFont('Arial', '', 12);
        if($hc->Odoscopia_Normal == 1){
            $pdf->Cell(10, 7, utf8_decode('SI'), 0, 1);
        }
        // if($hc->Odoscopia_Normal == 0){
        //     $pdf->Cell(10, 7, utf8_decode('NO'), 0, 1);
        // }
        if($hc->Odoscopia_Normal == null){
            $pdf->Cell(10, 7, '', 0, 1);
        }

        if($hc->Antecedentes_Gin != null){
            // linea
            $pdf->Cell(180, 3, '', 0, 1);
            $pdf->Cell(180, 0, '', 1, 1);
            $pdf->Cell(180, 3, '', 0, 1);
            // RM
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(12, 7, 'R.M.: ', 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(20, 7, utf8_decode($hc->RM), 0, 0);
            // Antecedentes_gin
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(62, 7, utf8_decode('Antecedentes Ginecológicos: '), 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(20, 7, utf8_decode($hc->Antecedentes_Gin), 0, 0);
            // Anticonceptivo
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(35, 7, utf8_decode('Anticonceptivo: '), 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(30, 7, utf8_decode($hc->Anticonceptivo), 0, 1);
            // Menarca
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(20, 7, utf8_decode('Menarca: '), 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(30, 7, Carbon::parse($hc->Menarca)->format('d/m/Y'), 0, 0);
            // FUM
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(17, 7, utf8_decode('F.U.M.: '), 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(30, 7, Carbon::parse($hc->FUM)->format('d/m/Y'), 0, 1);
        }
        // agudeza visual
        if($hc->OD_Agudeza_Visual != null){
            // linea
            $pdf->Cell(180, 3, '', 0, 1);
            $pdf->Cell(180, 0, '', 1, 1);
            $pdf->Cell(180, 3, '', 0, 1);
            // agudeza visual
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(180, 7, utf8_decode('Agudeza Visual: '), 0, 1);
            // Usa lentes
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(30, 7, utf8_decode('Usa Lentes: '), 0, 0);
            $pdf->SetFont('Arial', '', 12);
            if($hc->Usa_Lentes == 1){
                $pdf->Cell(30, 7, 'SI', 0, 0);
            }else if($hc->Usa_Lentes == 0){
                $pdf->Cell(30, 7, 'NO', 0, 0);
            }else{
                $pdf->Cell(30, 7, '', 0, 0);
            }
            // ojo derecho
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(30, 7, utf8_decode('Ojo Derecho: '), 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(30, 7, $hc->OD_Agudeza_Visual, 0, 0);
            // ojo izquierdo
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(30, 7, utf8_decode('Ojo Izquierdo: '), 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(30, 7, $hc->OI_Agudeza_Visual, 0, 1);
            // ultimo control
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(35, 7, utf8_decode('Último Control: '), 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(145, 7, $hc->Ultimo_Control, 0, 1);
        }
        // agudeza auditiva
        if($hc->OD_Agudeza_Audio != null){
            // linea
            $pdf->Cell(180, 3, '', 0, 1);
            $pdf->Cell(180, 0, '', 1, 1);
            $pdf->Cell(180, 3, '', 0, 1);
            // agudeza visual
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(180, 7, utf8_decode('Agudeza Auditiva: '), 0, 1);
            // oído derecho
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(32, 7, utf8_decode('Oído Derecho: '), 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(60, 7, $hc->OD_Agudeza_Audio, 0, 0);
            // oído izquierdo
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(35, 7, utf8_decode('Oído Izquierdo: '), 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(60, 7, $hc->OI_Agudeza_Audio, 0, 1);
        }
        // linea
        $pdf->Cell(180, 3, '', 0, 1);
        $pdf->Cell(180, 0, '', 1, 1);
        $pdf->Cell(180, 3, '', 0, 1);
        // datos adicionales
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 7, utf8_decode('Datos Adicionales: '), 0, 1);
        $pdf->SetFont('Arial', '', 12);
        $pdf->MultiCell(180, 5, utf8_decode($hc->Datos_Adicionales), 0, 'J');
        // hallazgos anormales
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 7, utf8_decode('Hallazgos Anormales: '), 0, 1);
        $pdf->SetFont('Arial', '', 12);
        $pdf->MultiCell(180, 5, utf8_decode($hc->Hallazgos_Anormales), 0, 'J');

        // Línea doble
        $pdf->Cell(180, 0, '', 1, 1); 
        $pdf->Cell(180, 1, '', 0, 1); 
        $pdf->Cell(180, 0, '', 1, 1); 
        $pdf->Cell(180, 4, '', 0, 1); // dejamos un espacio después de la segunda para separar el encabezado

        // // ###############################################################################################################################
        // // recetas
        // // se debe hacer un foreach de las recetas porque podría haber más de una
        // $pdf->SetFont('Arial', 'B', 14);
        // $pdf->Cell(180, 7, utf8_decode('RECETAS'), 0, 2, 'C');
        // $pdf->Cell(180, 2, '', 0, 1); // dejamos un espacio después de la segunda para separar el título
        // $pdf->SetFont('Arial', '', 8);
        // $pdf->Cell(180, 5, '-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------', 0, 1, 'C'); 
        // $pdf->SetFont('Arial', 'B', 12);
        // $pdf->Cell(17, 7, utf8_decode('Código: '), 0, 0, 'L');
        // $pdf->SetFont('Arial', '', 12);
        // $pdf->Cell(13, 7, utf8_decode('M255'), 0, 0, 'L');  // reemplazar por datos
        // $pdf->SetFont('Arial', 'B', 12);
        // $pdf->Cell(28, 7, utf8_decode('Diagnóstico: '), 0, 0, 'L');
        // $pdf->SetFont('Arial', '', 12);
        // $pdf->Cell(122, 7, utf8_decode('DOLOR EN ARTICULACION'), 0, 1, 'L');  // reemplazar por datos
        // $pdf->SetFont('Arial', 'B', 12);
        // $pdf->Cell(180, 10, utf8_decode('Medicamentos recetados'), 0, 2, 'L');
        // $pdf->SetFont('Arial', '', 12);
        // $pdf->MultiCell(180, 4, utf8_decode('ALGICLER (diclofenac potásico+paracetamol)      comp.recx 10      cantidad: 1'), 0, 'J');  // reemplazar por datos
        // $pdf->SetFont('Arial', 'B', 12);
        // $pdf->Cell(32, 7, utf8_decode('Observaciones:'), 0, 0, 'L');
        // $pdf->SetFont('Arial', '', 12);
        // $pdf->Cell(148, 7, utf8_decode('tomar una vez al día'), 0, 1, 'L');    // reemplazar por datos 
        // $pdf->Cell(180, 2, '', 0, 1); // dejamos un espacio después de la segunda para separar el título
        // // lo siguiente se reemplaza por un foreach de medicamentos
        // $pdf->SetFont('Arial', '', 12);
        // $pdf->MultiCell(180, 4, utf8_decode('ACTRON (ibuprofeno)      caps.gelar.blanda x 20     cantidad: 1'), 0, 'J');  // reemplazar por datos
        // $pdf->SetFont('Arial', 'B', 12);
        // $pdf->Cell(32, 7, utf8_decode('Observaciones:'), 0, 0, 'L');
        // $pdf->SetFont('Arial', '', 12);
        // $pdf->Cell(148, 7, utf8_decode('tomar cada 6 hs.'), 0, 1, 'L');    // reemplazar por datos 
        // $pdf->Cell(180, 2, '', 0, 1); // dejamos un espacio después de la segunda para separar el título

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