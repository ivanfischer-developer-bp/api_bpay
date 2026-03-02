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

class ExportarPatologiaController extends ConexionSpController
{
    /**
     * Exporta el listado de terapeuticas especiales de una persona
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function exportar_persona_patologia(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/afiliaciones/afiliacion/generar-certificado-afiliacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => 'generar_certificado_afiliacio',
            'sps' => [],
            'responses' => [],
            'queries' => [],
            'verificado' => [],
            'path' => ''
        ];
        try {
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
            $afiliado = request('afiliado');
            $patologias = request('patologias');

            $params = [
                'accion' => $accion,
                'afiliado' => $afiliado,
                'patologias' => $patologias
            ];
            
            if($user->hasPermissionTo('exportar datos')){
                $data = [
                    'logo' => env('LOGO_PATH'),
                    'codigo_barra' => '*' . $afiliado['n_afiliado'] . '*',
                    'n_afiliado' => $afiliado['n_afiliado'],
                    'obra_social' => $afiliado['n_convenio'],
                    'plan' => $afiliado['n_plan'],
                    'nombre_afiliado' => $afiliado['n_persona'],
                    'documento' => $afiliado['abrev_tipo_doc'].' '.$afiliado['nro_doc'],
                    'contacto' => $afiliado['n_contacto'],
                    'domicilio' => $afiliado['n_domicilio'],
                    'localidad' => $afiliado['n_localidad'],
                ];
                $pdf = new Fpdi('portrait', 'mm', 'A4');
                $pdf->SetMargins(15, 15);
                $numero_hoja = 1;
                $cant_hojas = 1;
                $pdf = $this->agregar_hoja_pdf($pdf, $data, $numero_hoja, $cant_hojas);
                //  TABLA --------------------------------------------------------------
                //  establece valor de tipografia
                $pdf->SetFont('Arial', '', 8);
                $dos_lineas = false;
                // return $patologias;
                //  por cada situaciión terapéutica
                foreach ($patologias as $patologia) {
                    
                    // situaciópn terapéutica
                    $pdf->Cell(180, 1, '', 0, 1); // dejamos un espacio arriba
                    $new_y = $pdf->getY();
                    if(strlen(utf8_decode(trim($patologia['n_patologia']))) > 24){
                        if(strlen(utf8_decode(trim($patologia['n_patologia']))) > 45){
                            $pat = substr(utf8_decode(trim($patologia['n_patologia'])), 0, 42);
                            $pat = $pat.'...';
                        }else{
                            $pat = substr(utf8_decode(trim($patologia['n_patologia'])), 0, 110);
                        }
                        $pdf->MultiCell(50, 5, $pat, 0, 'L');
                        $pdf->SetY($new_y);
                        $pdf->SetX($pdf->GetX() + 50);
                    }else{
                        $pdf->Cell(50, 5, utf8_decode($patologia['n_patologia']), 0, 0, 'L');
                    }
                    $pdf->SetFont('Arial', '', 8);
                    
                    // diagnóstico
                    if(strlen(utf8_decode(trim($patologia['n_cie_item']))) > 24){
                        // $new_y = strlen(utf8_decode(trim($st1->n_patologia))) > 24 ? $pdf->getY() : $pdf->getY() - 5;
                        if(strlen(utf8_decode(trim($patologia['n_cie_item']))) > 45){
                            $diagnostico = substr(utf8_decode(trim($patologia['n_cie_item'])), 0, 42);
                            $diagnostico = $diagnostico.'...';
                        }else{
                            $diagnostico = substr(utf8_decode(trim($patologia['n_cie_item'])), 0, 110);
                        }
                        $pdf->SetY($new_y);
                        $pdf->SetX($pdf->GetX() + 50);
                        $pdf->MultiCell(50, 5, $diagnostico, 0, 'L');
                    }else{
                        $pdf->Cell(50, 5, utf8_decode($patologia['n_cie_item']), 0, 0, 'L');
                    }
                    $new_y = strlen(utf8_decode(trim($patologia['n_patologia']))) > 24 && strlen(utf8_decode(trim($patologia['n_cie_item']))) > 24 ? $pdf->getY()-5 : $pdf->getY();
                    $pdf->SetY($new_y);
                    $pdf->SetX($pdf->GetX() + 100);
                    $pdf->SetFont('Arial', '', 8);
                    $pdf->Cell(20, 5, utf8_decode(Carbon::parse($patologia['fec_vto'])->format('d/m/Y')), 0, 0, 'C'); // fecha vencimiento
                    $pdf->Cell(12, 5, $patologia['permanente'] == 1 ? 'SI': 'NO', 0, 0, 'C'); // permanente
                    $pdf->Cell(12, 5, $patologia['prevalente'] == 1 ? 'SI': 'NO', 0, 0, 'C'); // prevalente
                    $pdf->Cell(16, 5, $patologia['porcentaje'].' '.utf8_decode('%'), 0, 0, 'R'); // porcentaje
                    $pdf->Cell(20, 5, $patologia['importe_recargo'] != null ? '$ '.utf8_decode($patologia['importe_recargo']) : '$ 0,00', 0, 1, 'R'); // importe recargo
                    $nueva_linea = strlen(utf8_decode(trim($patologia['n_patologia']))) > 24 || strlen(utf8_decode(trim($patologia['n_cie_item']))) > 24 ? $pdf->getY()+5 : $pdf->getY();
                    $pdf->SetY($nueva_linea);
                    $pdf->Cell(180, 1, '', 0, 1); // dejamos un espacio abajo
                    $pdf->Cell(180, 0.01, '', 1, 1, 'C');
                }
                if ($accion == 'enviar') {
                    $file_path = env('STORAGE_PATH').'reportes/situacion_terapeutica/';
                    $filename = 'situacion-terapeutica-'.$data['n_afiliado'].'.pdf';
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
                        'message' => $message,
                        'line' => null,
                        'code' => 1,
                        'data' => $data,
                        'params' => $params,
                        'logged_user' => $logged_user != null ? $logged_user : null,
                        'extras' => $extras
                    ]);
                }else{
                    $file_path = env('STORAGE_PATH').'reportes/situacion_terapeutica/';
                    $filename = 'situacion-terapeutica-'.$data['n_afiliado'].'.pdf';
                    $file = $file_path.$filename;
                    $pdf->Output($file, "F");
                    return FacadeResponse::make(Storage::disk('situacion_terapeutica')->get($filename), 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; ' . $filename,
                    ]);
                }

            }else{
                array_push($errors, 'No autorizado');
                return response()->json([
                    'status' => 'unauthorized',
                    'count' => 0,
                    'errors' => $errors,
                    'message' => 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para EXPORTAR DATOS',
                    'line' => null,
                    'code' => -2,
                    'data' => null,
                    'params' => $this->params,
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
    private function agregar_hoja_pdf($pdf, $data, $numero_hoja, $cant_hojas)
    {
        $pdf->AddPage('portrait');
        //  establece valor de tipografia
        $pdf->SetFont('Arial', '', 10);
                
        //  código de barras
        get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
        get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);
        
        //  cabecera
        $pdf->Image(env('LOGO_PATH'), 15, 16, 40, 0, 'PNG');
        $pdf->Cell(60, 14, '', 0, 0); // recuadro del logo
        $pdf->Cell(60, 14, '', 0, 0, 'C'); // recuadro del codigo de barras
        
        $pdf->Cell(60, 5, utf8_decode('Hoja '.$numero_hoja.' de '.$cant_hojas), 0, 2, 'R');
        $pdf->Cell(60, 5, utf8_decode('Fecha Impresión: ') . Carbon::now()->format('d/m/Y'), 0, 1, 'R');
        $pdf->Cell(60, 5, '', 0, 2, 'C');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(180, 7, utf8_decode('Situación Terapéutica del Afiliado'), 0, 2, 'C');

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

        // =============================================================
        // CABECERA DE LA TABLA
        // =============================================================
        $pdf->SetY($pdf->getY()+7);
        $pdf->Cell(180, 2, '', 0, 1);
        $pdf->Cell(180, 0.01, '', 1, 1, 'C');
        $pdf->Cell(50, 10, utf8_decode('Situación Terapéutica'), 0, 0, 'L');
        $pdf->Cell(50, 10, utf8_decode('Diagnóstico'), 0, 0, 'L');
        $pdf->Cell(20, 10, utf8_decode('Fec. Vto'), 0, 0, 'C');
        $pdf->Cell(12, 10, utf8_decode('Perm.'), 0, 0, 'C');
        $pdf->Cell(12, 10, utf8_decode('Prev.'), 0, 0, 'C');
        $pdf->Cell(16, 10, utf8_decode('%'), 0, 0, 'R');
        $pdf->Cell(20, 10, utf8_decode('imp. rec.'), 0, 1, 'R');
        $pdf->Cell(180, 0.01, '', 1, 1, 'C');
        $pdf->Cell(180, 2, '', 0, 1);

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