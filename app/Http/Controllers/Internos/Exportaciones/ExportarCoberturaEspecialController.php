<?php

namespace App\Http\Controllers\Internos\Exportaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use File;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;

class ExportarCoberturaEspecialController extends ConexionSpController
{
    /**
     * Exporta en un pdf las coberturas especiales de un afiliado
     * @param nro_afiliado en el request, numero de afiliado, obligatorio
     * @param tipo_descarga en el request, string opcional con alguno de los siguientes valores: 
     * 'mostrar' (por omisión) o 'enviar'
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response $response
     */
    public function exportar_coberturas_especiales_afiliado(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/coberturas-especiales/afiliado-exportar',
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
            $nro_afiliado = request('nro_afiliado');
            $params = [
                'nro_afiliado' => $nro_afiliado,
                'accion' => $accion
            ];
            // return $params;
            if($user->hasPermissionTo('exportar datos')){
                array_push($extras['sps'], [
                    'sp_afiliado_select'=> [
                        'nro_afiliado' => $nro_afiliado,
                        'p_hst' => 0
                    ]
                ]);
                // return $extras;
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_afiliado_select', ['nro_afiliado' => $nro_afiliado, 'p_hst' => 0]));
                $afiliado = $this->ejecutar_sp_directo('afiliacion', 'sp_afiliado_select', ['nro_afiliado' => $nro_afiliado, 'p_hst' => 0]);
                array_push($extras['responses'], ['sp_afiliado_select'=> $afiliado]);
                //  obtiene los datos
                $afiliado = $afiliado[0];
                // return $afiliado;
                array_push($extras['sps'], [
                    'sp_persona_cobertura_select'=> [
                        'p_id_persona' => $afiliado->id_persona
                    ]
                ]);
                // return $extras;
                try {
                    if(isset($afiliado->error)){
                        array_push($errors, $afiliado->error);
                    }
                } catch (\Exception $e) {
                    return $e;
                }
                // return $extras;
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_persona_cobertura_select', ['p_id_persona' => $afiliado->id_persona]));
                $cobertura = $this->ejecutar_sp_directo('afiliacion', 'sp_persona_cobertura_select', ['p_id_persona' => $afiliado->id_persona]);
                array_push($extras['responses'], ['sp_persona_cobertura_select'=> $cobertura]);
                if(isset($cobertura['error'])){
                    array_push($errors, $cobertura['error']);
                }
                // return ['afiliado' => $afiliado, 'st' => $cobertura];
                //  si obtiene los datos
                if ($cobertura) {
                    $data = [
                        'logo' => env('LOGO_PATH'),
                        'codigo_barra' => '*' . $afiliado->n_afiliado . '*',
                        'n_afiliado' => $afiliado->n_afiliado,
                        'obra_social' => $afiliado->n_convenio,
                        'plan' => $afiliado->n_plan,
                        'nombre_afiliado' => $afiliado->n_persona,
                        'documento' => $afiliado->abrev_tipo_doc.' '.$afiliado->nro_doc,
                        'contacto' => $afiliado->n_contacto,
                        'domicilio' => $afiliado->n_domicilio,
                        'localidad' => $afiliado->n_localidad,
                    ];
        
                    // $pdf = new \fpdf\FPDF('portrait', 'mm', 'A4');
                    $pdf = new Fpdi('portrait', 'mm', 'A4');
        
                    $pdf->SetMargins(15, 15);
        
                    $numero_hoja = 1;
                    $cant_hojas = 1;
                    $cant_hojas = ceil(sizeof($cobertura)/7);
                    $pdf = $this->agregar_hoja_pdf_coberturas_especiales_afiliado($pdf, $data, $cobertura, $numero_hoja, $cant_hojas);
                    //  TABLA --------------------------------------------------------------
                    //  establece valor de tipografia
                    $pdf->SetFont('Arial', '', 8);
                    // dd($cobertura);
                    $pdf->Cell(180, 5, '', 0, 1); // dejamos un espacio antes de la primera para separar el encabezado
                    //  por cada cobertura especial
                    $n=0;
                    foreach ($cobertura as $cobertura1) {
                        $n++;
                        if($n % 5 == 0){
                            $numero_hoja++;
                            $pdf = $this->agregar_hoja_pdf_coberturas_especiales_afiliado($pdf, $data, $cobertura, $numero_hoja, $cant_hojas);
                        }
                        $pdf->setFont('Arial', 'B', 10);
                        $pdf->Cell(180, 3, '', 0, 1); // dejamos un espacio arriba
                        $pdf->Cell(180, 7, utf8_decode('Cobertura Especial N° '.$n), 0, 1, 'L');
                        $pdf->SetFont('Arial', '', 8);
                        $pdf->Cell(180, 5, utf8_decode('Terapéutica: '.$cobertura1->n_patologia), 0, 1, 'L');
                        $diagnostico = $cobertura1->n_cie_item;
                        if(strlen(utf8_decode($diagnostico)) > 96 ){
                            $diagnostico = substr(utf8_decode(trim($diagnostico)), 0, 96);
                            $diagnostico = $diagnostico.'...';
                        }
                        $pdf->Cell(180, 5, utf8_decode('Diagnóstico: '.$diagnostico), 0, 1, 'L');
                        $pdf->Cell(45, 5, utf8_decode('Fecha Vigencia: '.Carbon::parse($cobertura1->fec_vigencia)->format('d/m/Y')), 0, 0, 'L');
                        $pdf->Cell(45, 5, utf8_decode('Fecha Vencimiento: '.Carbon::parse($cobertura1->fec_vto)->format('d/m/Y')), 0, 0, 'L');
                        $pdf->Cell(45, 5, utf8_decode('Fecha FUM: '.Carbon::parse($cobertura1->fecha_fum)->format('d/m/Y')), 0, 0, 'L');
                        $pdf->Cell(45, 5, utf8_decode('Fecha FPP: '.Carbon::parse($cobertura1->fecha_fpp)->format('d/m/Y')), 0, 1, 'L');
                        $pdf->Cell(45, 5, utf8_decode('Médico: '.$cobertura1->n_medico), 0, 0, 'L');
                        $pdf->Cell(45, 5, utf8_decode('Matricula: '.$cobertura1->n_matricula), 0, 0, 'L');
                        $pdf->Cell(45, 5, utf8_decode('Importe Recargo: $'.$cobertura1->importe_recargo), 0, 0, 'L');
                        $pdf->Cell(45, 5, utf8_decode('Porcentaje: '.$cobertura1->porcentaje.'%'), 0, 1, 'L');
                        $new_y = $pdf->getY();
                        $observaciones = $cobertura1->n_drogas;
                        if(strlen(utf8_decode($observaciones)) > 96){
                            if(strlen(utf8_decode($observaciones)) > 288){
                                $observaciones = substr(utf8_decode(trim($observaciones)), 0, 285);
                                $observaciones = $observaciones.'...';
                                // $pdf->SetY($new_y+15);
                            }
                            $pdf->MultiCell(180, 5, 'Observaciones: '.$observaciones, 0, 'L');
                            // $pdf->SetY($new_y+10);
                        }else{
                            $pdf->Cell(180, 5, utf8_decode('Observaciones: '.$observaciones), 0, 1, 'L');
                        }
                        // $pdf->Cell(45, 5, utf8_decode('Porcentaje: '.$cobertura1->porcentaje.'%'), 1, 1, 'L');
                        $pdf->SetY($pdf->getY());
                        $pdf->Cell(180, 1, '', 0, 1); // dejamos un espacio abajo
                        $pdf->Cell(180, 0.01, '', 1, 1, 'C');
                    }
                    
                    //  -------------------
                    if ($accion == 'enviar') {
                        if(!File::exists(env('STORAGE_PATH').'reportes/coberturas_especiales_afiliado/')){
                            File::makeDirectory(env('STORAGE_PATH').'reportes/coberturas_especiales_afiliado/');
                        }
                        $file_path = env('STORAGE_PATH').'reportes/coberturas_especiales_afiliado/';
                        if(!File::exists($file_path)){
                            File::makeDirectory($file_path);
                        }
                        $filename = 'coberturas-especiales-' . $afiliado->nro_doc . '.pdf';
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
                            'params' => ['nro_afiliado' => $nro_afiliado, 'accion' => $accion],
                            'extras' => $extras,
                            'logged_user' => $logged_user,
                        ]);
                    }else{
                        if(!File::exists(env('STORAGE_PATH').'reportes/coberturas_especiales_afiliado/')){
                            File::makeDirectory(env('STORAGE_PATH').'reportes/coberturas_especiales_afiliado/');
                        }
                        $file_path = env('STORAGE_PATH').'reportes/coberturas_especiales_afiliado/';
                        if(!File::exists($file_path)){
                            File::makeDirectory($file_path);
                        }
                        $filename = 'coberturas-especiales-' . $afiliado->nro_doc . '.pdf';
                        $file = $file_path.$filename;
                        $pdf->Output($file, "F");
                        return FacadeResponse::make(Storage::disk('coberturas_especiales_afiliado')->get($filename), 200, [
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
                    'message' => 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para EXPORTAR DATOS',
                    'line' => null,
                    'code' => -1,
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
    private function agregar_hoja_pdf_coberturas_especiales_afiliado($pdf, $data, $cobertura, $numero_hoja, $cant_hojas)
    {
        $pdf->AddPage('portrait');
        //  establece valor de tipografia
        $pdf->SetFont('Arial', '', 10);
                
        //  código de barras
        get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
        get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);
        
        //  cabecera
        // $pdf->Image(get_path_imagenes('logo.png'), 15, 16, 30, 7, 'PNG');
        // $pdf->Image(env('LOGO_PATH'), 15, 16, 30, 7, 'PNG');
        $pdf->Image(storage_path('app/public/images/logo.png'), 15, 16, 45, 0, 'PNG');
        $pdf->Cell(60, 14, '', 0, 0); // recuadro del logo
        $pdf->Cell(60, 14, '', 0, 0, 'C'); // recuadro del codigo de barras
        
        $pdf->Cell(60, 5, utf8_decode('Hoja '.$numero_hoja.' de '.$cant_hojas), 0, 2, 'R');
        $pdf->Cell(60, 5, utf8_decode('Fecha Impresión: ') . Carbon::now()->format('d/m/Y'), 0, 1, 'R');
        $pdf->Cell(60, 5, '', 0, 2, 'C');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(180, 7, utf8_decode('Coberturas Especiales del Afiliado'), 0, 2, 'C');

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
     * Añade el pie de página al pdf
     */
    private function agregar_pie_pdf_coberturas_especiales_afiliado($pdf, $data)
    {
        return $pdf;
    }
}