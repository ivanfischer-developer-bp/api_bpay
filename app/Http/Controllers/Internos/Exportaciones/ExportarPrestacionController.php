<?php

namespace App\Http\Controllers\Internos\Exportaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use setasign\Fpdi\Fpdi;
use Carbon\Carbon;
use Storage;


class ExportarPrestacionController extends ConexionSpController
{
    public function exportar_prestaciones(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        
        $this->extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/prestacion/exportar-prestaciones',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'path' => '',
            'queries' => [],
            // 'responses' => [], // se quita porque da server error
            'sps' => []
        ];
        
        try {
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            $this->params = [];
            // variables de respuesta
            $status = 'null';
            $count = -1;
            $errors = [];
            $message = 'sin comentarios';
            $line = null;
            $code = null;
            $data = null;

            $codigo_interno = request('codigo_interno');
            $imprimir_rechazadas = request('imprimir_rechazadas');
            $accion = request('accion');
            $contar_impresion = request('contar_impresion');
            $this->params = [
                'accion' => $accion,
                'practica' => request('practica'),
                'tipo_busqueda' => request('tipo_busqueda'),
                'id_plan_prestacional' => request('id_plan_prestacional'),
                'localidad' => request('localidad'),
                'razon_social' => request('razon_social')
            ];

            $this->params_sp = [
                'p_practica' => request('practica'),
                'tipo_busqueda' => request('tipo_busqueda'),
                'id_plan_prestacional' => request('id_plan_prestacional'),
                'p_localidad' => null !== request('localidad') ? request('localidad') : '',
                'p_razon_social' => null !== request('razon_social') ? request('razon_social') : ''
            ];

            if($user->hasPermissionTo('exportar datos')){
                array_push($this->extras['queries'], $this->get_query('validacion', 'AWEB_TraerPrestadoresPractica', $this->params_sp));
                array_push($this->extras['sps'], ['AWEB_TraerPrestadoresPractica' => $this->params_sp]);
                $prestaciones = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerPrestadoresPractica', $this->params_sp);
                // array_push($this->extras['responses'], ['AWEB_TraerPrestadoresPractica' => $prestaciones]); da server error, por eso se quita
                
                if(!empty($prestaciones)){
                    $data = [
                        'logo' => env('LOGO')
                    ];
                    $pdf = new Fpdi('portrait', 'mm', 'A4');
                    $pdf->SetMargins(15, 15);
    
                    $numero_hoja = 1;
                    $cant_hojas = 1;
                    $cant_hojas = ceil(sizeof($prestaciones)/5);
                    $pdf = $this->agregar_hoja_pdf($pdf, $data, $prestaciones, $numero_hoja, $cant_hojas);
                    //  TABLA --------------------------------------------------------------
                    //  establece valor de tipografia
                    $pdf->SetFont('Arial', '', 8);
                    // $pdf->Cell(180, 5, '', 0, 1); // dejamos un espacio antes de la primera para separar el encabezado
                    //  por cada prestacion
                    $n=0;
                    
                    foreach ($prestaciones as $prestacion) {
                        $n++;
                        if($n % 5 == 0){
                            $numero_hoja++;
                            $pdf = $this->agregar_hoja_pdf($pdf, $data, $prestaciones, $numero_hoja, $cant_hojas);
                        }
                        
                        $pdf->Cell(180, 3, '', 0, 1); // dejamos un espacio abajo
                        $pdf->setFont('Arial', 'B', 8);
                        $pdf->Cell(20, 5, utf8_decode('Razon Social: '), 0, 0, 'L');
                        $pdf->SetFont('Arial', '', 8);
                        $pdf->Cell(100, 5, utf8_decode(trim($prestacion->Razon_Social)), 0, 1, 'L');
                        $pdf->setFont('Arial', 'B', 8);
                        $pdf->Cell(10, 5, utf8_decode('Plan: '), 0, 0, 'L');
                        $pdf->SetFont('Arial', '', 8);
                        $pdf->Cell(50, 5, utf8_decode(trim($prestacion->n_plan)), 0, 1, 'L');
                        $pdf->setFont('Arial', 'B', 8);
                        $pdf->Cell(20, 5, utf8_decode('Agrupación: '), 0, 0, 'L');
                        $pdf->SetFont('Arial', '', 8);
                        $pdf->Cell(160, 5, utf8_decode(trim($prestacion->agrupacion)), 0, 1, 'L');
                        $pdf->setFont('Arial', 'B', 8);
                        $pdf->Cell(20, 5, utf8_decode('Provincia: '), 0, 0, 'L');
                        $pdf->SetFont('Arial', '', 8);
                        $pdf->Cell(70, 5, utf8_decode(trim($prestacion->Provincia)), 0, 0, 'L');
                        $pdf->setFont('Arial', 'B', 8);
                        $pdf->Cell(20, 5, utf8_decode('Localidad: '), 0, 0, 'L');
                        $pdf->SetFont('Arial', '', 8);
                        $pdf->Cell(70, 5, utf8_decode(trim($prestacion->Localidad)), 0, 1, 'L');
                        $pdf->setFont('Arial', 'B', 8);
                        $pdf->Cell(20, 5, utf8_decode('Domicilio: '), 0, 0, 'L');
                        $pdf->SetFont('Arial', '', 8);
                        $pdf->Cell(70, 5, utf8_decode(trim($prestacion->Domicilio)), 0, 0, 'L');
                        $pdf->setFont('Arial', 'B', 8);
                        $pdf->Cell(20, 5, utf8_decode('Teléfonos: '), 0, 0, 'L');
                        $pdf->SetFont('Arial', '', 8);
                        if(strlen(utf8_decode($prestacion->Telefonos)) > 48){
                            $prestacion->Telefonos = substr(utf8_decode(trim($prestacion->Telefonos)), 0, 48).'...';
                        }
                        $pdf->Cell(70, 5, utf8_decode(trim($prestacion->Telefonos)), 0, 1, 'L');
                        $pdf->setFont('Arial', 'B', 8);
                        $pdf->Cell(20, 5, utf8_decode('Inclusiones: '), 0, 0, 'L');
                        $pdf->SetFont('Arial', '', 8);
                        $pdf->Cell(160, 5, utf8_decode(trim($prestacion->Inclusion_Observaciones)), 0, 1, 'L');
                        $pdf->setFont('Arial', 'B', 8);
                        $pdf->Cell(20, 5, utf8_decode('Exclusiones: '), 0, 0, 'L');
                        $pdf->SetFont('Arial', '', 8);
                        $pdf->Cell(160, 5, utf8_decode(trim($prestacion->Exclusion_Observaciones)), 0, 1, 'L');
                        $pdf->SetY($pdf->getY());
                        $pdf->Cell(180, 3, '', 0, 1); // dejamos un espacio abajo
                        $pdf->Cell(180, 0.01, '', 1, 1, 'C');
                    }
                    
                    $pdf = $this->agregar_pie_pdf($pdf, $data);
                    $pdf->SetMargins(15, 15);
                    // try {
                    //     return $prestaciones;
                    // } catch (\Throwable $th) {
                    //     return $th->getMessage();
                    // }
                    if ($accion == 'enviar') {
                        
                        $file_path = env('STORAGE_PATH').'reportes/prestaciones/';
                        $filename = 'lista-prestaciones.pdf';
                        $file = $file_path.$filename;
                        $this->extras['path'] = $file;
                        $pdf->Output($file, "F");
                        $data = $filename;
                        $status = 'ok';
                        $count = 1;
                        $message = 'Archivo generado satisfactoriamente';
                        
                        return response()->json([
                            'status' => $status,
                            'count' => $count,
                            'errors' => $errors,
                            'message' => $message,
                            'line' => null,
                            'code' => 1,
                            'data' => $data,
                            'params' => $this->params,
                            'extras' => $this->extras,
                            'logged_user' => $logged_user != null ? $logged_user : null
                        ]);
                    }else{
                        $file_path = env('STORAGE_PATH').'reportes/prestaciones/';
                        $filename = 'lista-prestaciones.pdf';
                        $file = $file_path.$filename;
                        $pdf->Output($file, "F");
                        return FacadeResponse::make(Storage::disk('prestaciones')->get($filename), 200, [
                            'Content-Type' => 'application/pdf',
                            'Content-Disposition' => 'inline; ' . $filename,
                        ]);
                    }
                }else{
                    $status = 'empty';
                    $message = 'No se encontraron registros que coincidan con los parámetros otorgados.';
                    $count = 0;
                    $code = -3;
                    $line = null;
                    $data = null;
                    $errors = [];
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para EXPORTAR DATOS';
                $count  = 0;
                $code = -2;
                $line = null;
                $data = null;
                array_push($errors, 'No autorizado');
            }
            // retorna el response
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $message,
                'line' => $line,
                'code' => $code,
                'data' => $data,
                'params' => $this->params,
                'extras' => $this->extras,
                'logged_user' => $logged_user
            ]); 
        } catch (\Throwable $th) {
            array_push($errors, 'Fallo de backend');
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => null,
                'extras' => $this->extras,
                'logged_user' => null
            ]);
        }
    }

    /**
     * Añade una nueva hoja con las cabeceras al pdf
     */
    private function agregar_hoja_pdf($pdf, $data, $prestaciones, $numero_hoja, $cant_hojas)
    {
        $pdf->AddPage('portrait');
        
        //  cabecera
        $pdf->Image(env('LOGO_PATH'), 15, 16, 50, 0, 'PNG');
        $pdf->Cell(60, 14, '', 0, 0); // recuadro del logo
        $pdf->Cell(60, 14, '', 0, 0, 'C'); // recuadro del codigo de barras
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(60, 5, utf8_decode('Hoja '.$numero_hoja.' de '.$cant_hojas), 0, 2, 'R');
        $pdf->Cell(60, 5, utf8_decode('Fecha Impresión: ') . Carbon::now()->format('d/m/Y'), 0, 1, 'R');
        $pdf->Cell(60, 5, '', 0, 2, 'C');
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(180, 10, utf8_decode('Listado de Prestadores'), 0, 2, 'C');
        $pdf->Cell(180, 3, '', 0, 1); // dejamos un espacio arriba
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->MultiCell(180, 8, utf8_decode('Prestación: ('.trim($prestaciones[0]->Practica).') '.trim($prestaciones[0]->Descripcion)), 0, 'L');
        $pdf->Cell(180, 0.01, '', 1, 1, 'C');

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