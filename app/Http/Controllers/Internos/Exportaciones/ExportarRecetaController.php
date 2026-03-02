<?php

namespace App\Http\Controllers\Internos\Exportaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;
use App\Models\ProfileDoctor;

use File;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
// use setasign\Fpdi\Tcpdf\Fpdi;
// use setasign\Fpdi\Tfpdf\Fpdi; 

class ExportarRecetaController extends ConexionSpController
{
    /**
     * Genera un pdf interno de una receta
     */
    public function generar_pdf_receta(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/recetas/generar-pdf-receta',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];
        $status = 'fail';
        $count = -1;
        $errors = [];
        $message = null;
        $line = null;
        $code = null;
        $data = null;
        try {
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            $accion = request('accion') == '' ? 'mostrar' : request('accion');
            $nro_receta = request('nro_receta');
            
            if($user->hasPermissionTo('exportar datos')){
                
                return $this->crear_pdf_receta($nro_receta, $accion, 'int/recetas/generar-pdf-receta', $logged_user);  
               
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
                    'params' => $this->params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]); 
            }
        } catch (\Throwable $th) {
            $extras['path'] = env('STORAGE_PATH').'/reportes/informes_afiliados/';
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => $data,
                'params' => $this->params,
                'logged_user' => $logged_user != null ? $logged_user : null,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Crea el pdf de la receta
     */
    private function crear_pdf_receta($nro_receta, $accion, $url, $logged_user)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => $url,
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];
        $status = 'fail';
        $count = -1;
        $errors = [];
        $message = null;
        $line = null;
        $code = null;
        $data = null;
        try {

            $this->params = [
                'nro_receta' => $nro_receta,
                'accion' => $accion,
            ];
            array_push($extras['queries'], $this->get_query('validacion', 'sp_HC_Receta_id_select', ['id_receta' => $nro_receta]));
            array_push($extras['sps'], ['sp_HC_Receta_id_select' => ['id_receta' => $nro_receta]]);
            $registros_receta = $this->ejecutar_sp_directo('validacion', 'sp_HC_Receta_id_select', ['id_receta' => $nro_receta]);
            array_push($extras['responses'], ['sp_HC_Receta_id_select' => $registros_receta]);
            // return $registros_receta;
            $id_medico = $registros_receta[0]->id_medico;
            $doctor = ProfileDoctor::find($id_medico);
            
            $pdf = new Fpdi('portrait', 'mm', 'A4');
            $pdf->SetMargins(15, 15);

            // ###########################################  MOSTRAR ORIGINAL ############################################################
            $pdf->AddPage('portrait');
            $pdf->Image(env('IMAGE_PATH').'/logo_receta.png', 15, 16, 40, 0, 'PNG');
            $hoja = 'original';
            if($hoja != ''){
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(119, 7, utf8_decode(strtoupper($hoja).' - '), 0, 0, 'R');
            }
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(61, 7, 'Nro. de receta: '.$nro_receta, 0, 1, 'R');
            $pdf->Cell(180, 7, 'Fecha: '.Carbon::parse($registros_receta[0]->fecha)->format('d/m/Y'), 0, 1, 'R');

            $pdf->Cell(180, 10, '', 0, 1, 'C'); // dejamos un espacio de 10 mm por el espacio del logo

            // ###################################  MEDICO #####################################################################3
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(180, 7, utf8_decode('Dr/a '.$doctor->nombre.' '.$doctor->apellido), 0, 1, 'L');
            $pdf->SetFont('Arial', '', 12);
            $tipo_matricula = $doctor->matricula_tipo == 'MN' ? 'MN.' : 'MP.';
            $pdf->Cell(180, 7, utf8_decode($doctor->especialidad.' '.$tipo_matricula.' '.$doctor->matricula_numero), 0, 1, 'L');
            
            $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
            $pdf->Cell(180, 0, '', 1, 1, 'C'); // creamos una linea
            $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 5 mm

            // #######################   CODIGOS DE BARRA de nro receta y nro afiliado   ###########################################
            // $filepath="", $text="0", $size="20", $orientation="horizontal", $code_type="code128", $print=false, $SizeFactor=1
            // codigo barra numero receta
            $y = $pdf->GetY();
            $file_path_codigo_receta = env('STORAGE_PATH').'reportes/codigos/numero_receta.png';
            barcode($file_path_codigo_receta, $nro_receta, 40, 'horizontal', 'code128', true, 1);
            $pdf->Image($file_path_codigo_receta, 15, $y, 85, 30, 'PNG');
            // codigo barra numero afiliado
            $file_path_codigo_afiliado = env('STORAGE_PATH').'reportes/codigos/numero_afiliado.png';
            // $filepath="", $text="0", $size="20", $orientation="horizontal", $code_type="code128", $print=false, $SizeFactor=1
            barcode($file_path_codigo_afiliado, $registros_receta[0]->n_afiliado, 40, 'horizontal', 'code128', true, 1);
            $pdf->Image($file_path_codigo_afiliado, 115, $y, 75, 30, 'PNG');

            $pdf->setY($pdf->getY()+25);

            // ##################################  PACIENTE  #####################################################
            // convenio, numero afiliado y plan
            $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(60, 7, utf8_decode($registros_receta[0]->n_convenio), 0, 0, 'L'); 
            $pdf->Cell(60, 7, utf8_decode('Nro. Afiliado: '.$registros_receta[0]->n_afiliado), 0, 0, 'L'); 
            $pdf->Cell(60, 7, utf8_decode('PLAN: '.$registros_receta[0]->n_plan), 0, 1, 'L'); 

            // paciente y dni paciente
            $pdf->Cell(120, 7, utf8_decode('Paciente: '.$registros_receta[0]->n_persona), 0, 0, 'L'); 
            $pdf->Cell(60, 7, utf8_decode($registros_receta[0]->Doc_Tipo.': '.$registros_receta[0]->Doc_Numero), 0, 1, 'L'); 

            // fecha nacimiento y sexo
            $pdf->Cell(120, 7, utf8_decode('Fecha Nacimiento: '.Carbon::parse($registros_receta[0]->fec_nac)->format('d/m/Y')), 0, 0, 'L'); 
            $sexo = $registros_receta[0]->sexo == 'F' ? 'Femenino' : ($registros_receta[0]->sexo == 'M' ? 'Masculino' : 'No binario');
            $pdf->Cell(60, 7, utf8_decode('Sexo: '.$sexo ), 0, 1, 'L'); 

            $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
            $pdf->Cell(180, 0, '', 1, 1, 'C'); // creamos una linea
            $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 5 mm

            // ###################################  Rp  ##############################################################
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(60, 7, utf8_decode('Rp: '), 0, 1, 'L'); 
            $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 10 mm

            // #############################################  MEDICAMENTOS  ####################################################################
            forEach($registros_receta as $receta){
                $pdf->SetFont('Arial', 'B', 12);
                if($receta->permite_sustitucion == 'N'){
                    $texto_medicamento = $receta->nombreProducto.' ('.$receta->nombreDroga.') ';
                }else{
                    $texto_medicamento = $receta->nombreDroga;
                }
                if($receta->tratamiento == 1){
                    $texto_medicamento = $texto_medicamento.' - TRATAMIENTO PROLONGADO';
                }
                $pdf->Cell(180, 7, utf8_decode($texto_medicamento), 0, 1, 'L');
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(180, 7, utf8_decode($receta->presentacion.' - '.'Cantidad: '.$receta->cantidad.' '.cantidad_a_letras($receta->cantidad, true)), 0, 1, 'L');
                
                // $pdf->Cell(180, 7, utf8_decode('Cantidad: '.$receta->cantidad.' '.cantidad_a_letras($receta->cantidad, true)), 0, 1, 'L');

                if($receta->observaciones != ''){
                    $pdf->Cell(180, 7, utf8_decode('Observaciones: '.$receta->observaciones), 0, 1, 'L');
                }

                $pdf->setY($pdf->getY()+5);
            }

            // ########################## QR VERIFICADOR #####################################################
            // da el error non-conforming drawing primitive definition `2' @ error/draw.c/RenderMVGContent/4414
            // $qr_ref = $registros_receta[0]->verificador;
            // $qr_ref = 'https://qa.verumrp.com.ar/8DDD85A2A6744EB1E84786742EBE4118B4B108E96EA47F878F5A21BF6E95A155';
            // // QrCode::format('png')->size(100)->generate($qr_ref, env('STORAGE_PATH').'reportes/codigos/qrcode_receta.png');
            // $qr = QrCode::size(100)->format('png')->generate($qr_ref, env('STORAGE_PATH').'reportes/codigos/qrcode_receta.png');
            $pdf->Image(env('STORAGE_PATH').'reportes/codigos/qr_empresa.png', 150, 150, 30, 30, 'PNG');
            $pdf->SetFont('Arial', '', 9);
            $pdf->setY(180);
            $pdf->Cell(120, 7, '', 0, 0, 'C');
            $pdf->Cell(60, 7, utf8_decode('VERIFICAR EMISOR'), 0, 1, 'C'); 
            // $pdf->Cell(60, 7, utf8_decode('VERIFICAR EMISOR'), 0, 1, 'C', true, $registros_receta[0]->verificador); // para mostrar link pero no funciona

            // #########################################  DIAGNOSTICO  ########################################################################
            $pdf->SetFont('Arial', '', 12);
            $pdf->setX(15);
            $pdf->setY(190);
            $pdf->Cell(180, 7, utf8_decode('Diagnóstico: '.$registros_receta[0]->diagnostico), 0, 1, 'L');
            
            // #################################### FIRMA MEDICO ######################################################
            $file_path_firma_medico = env('STORAGE_PATH').'uploads/firma_medicos/'.$doctor->nroDoc.'.png';
            if (file_exists($file_path_firma_medico)){
                $pdf->Image($file_path_firma_medico, 115, 200, 50, 25, 'PNG');
            }
            $pdf->setY(220);
            $pdf->setX(100);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(80, 5, utf8_decode($doctor->nombre.' '.$doctor->apellido), 0, 2, 'C');
            $tipo_matricula = $doctor->matricula_tipo == 'MN' ? 'MN.' : 'MP.';
            $pdf->Cell(80, 5, utf8_decode(strtoupper($doctor->especialidad.' '.$tipo_matricula).' '.$doctor->matricula_numero), 0, 2, 'C');
            $pdf->Cell(80, 0, '', 1, 2, 'C'); // creamos una linea
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(80, 5, utf8_decode('FIRMA Y SELLO'), 0, 2, 'C');
            $pdf->setX(15);
            $pdf->Cell(180, 5, utf8_decode('Este documento ha sido firmado electrónica o digitalmente según corresponda por Dr/a '.$doctor->nombre.' '.$doctor->apellido), 0, 1, 'R');

            $pdf->Cell(180, 2, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
            $pdf->Cell(180, 0, '', 1, 1, 'C'); // creamos una linea
            $pdf->Cell(180, 2, '', 0, 1, 'C'); // dejamos un espacio de 5 mm

            // ##################################################### PIE DE PAGINA ###################################################
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(60, 5, utf8_decode('Consultorio: '.$doctor->nombreConsultorio), 0, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode('Teléfono: '.$doctor->telefono), 0, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode('Dirección: '.$doctor->direccionConsultorio), 0, 1, 'L');
            if($doctor->horario != null){
                $de = ' de '.$doctor->horario;
            }else{
                $de = '';
            }
            $pdf->Cell(180, 5, utf8_decode('Atención: '.$doctor->diasAtencion.$de), 0, 1, 'L');

            $pdf->Cell(180, 2, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
            $pdf->Cell(180, 0, '', 1, 1, 'C'); // creamos una linea
            $pdf->Cell(180, 2, '', 0, 1, 'C'); // dejamos un espacio de 5 mm

            $pdf->SetFont('Arial', '', 6);
            $texto_receta = 'LA RECETA DEBE VALIDARSE POR EL SISTEMA ONLINE EN LA FARMACIA';
            $pdf->Cell(180, 5, utf8_decode($texto_receta), 0, 1, 'R');
            $pdf->SetFont('Arial', '', 7);
            $texto_final = 'Esta receta fue creada por un emisor inscripto y validado en el Registro de Recetarios Electrónicos del Ministerio de Salud de la Nación - RL '.env('NRO_RL');
            $pdf->Cell(180, 5, utf8_decode($texto_final), 0, 1, 'R');
            $pdf->cell(180, 0, '', 0, 1, 'R');

            $pdf->setY($pdf->getY());

            $mostrar_posologia = false;
            forEach($registros_receta as $receta){
                if($receta->posologia != ''){
                    $mostrar_posologia = true;
                }
            }

            if($mostrar_posologia){
                // ###########################################  MOSTRAR POSOLOGIA ############################################################
                $pdf->AddPage('portrait');
                $pdf->Image(env('IMAGE_PATH').'/logo_receta.png', 15, 16, 40, 0, 'PNG');
                $hoja = '';
                if($hoja != ''){
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Cell(119, 7, utf8_decode(strtoupper($hoja).' - '), 0, 0, 'R');
                }else{
                    $pdf->Cell(119, 7, '', 0, 0, 'R');
                }
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(61, 7, 'Nro. de receta: '.$nro_receta, 0, 1, 'R');
                $pdf->Cell(180, 7, 'Fecha: '.Carbon::parse($registros_receta[0]->fecha)->format('d/m/Y'), 0, 1, 'R');
    
                $pdf->Cell(180, 10, '', 0, 1, 'C'); // dejamos un espacio de 10 mm por el espacio del logo
    
                // ###################################  MEDICO #####################################################################3
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(180, 7, utf8_decode('Dr/a '.$doctor->nombre.' '.$doctor->apellido), 0, 1, 'L');
                $pdf->SetFont('Arial', '', 12);
                $tipo_matricula = $doctor->matricula_tipo == 'MN' ? 'MN.' : 'MP.';
                $pdf->Cell(180, 7, utf8_decode($doctor->especialidad.' '.$tipo_matricula.' '.$doctor->matricula_numero), 0, 1, 'L');
                
                $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
                $pdf->Cell(180, 0, '', 1, 1, 'C'); // creamos una linea
                $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
    
                // #######################   CODIGOS DE BARRA de nro receta y nro afiliado   ###########################################
                // $filepath="", $text="0", $size="20", $orientation="horizontal", $code_type="code128", $print=false, $SizeFactor=1
                // codigo barra numero receta
                $y = $pdf->GetY();
                $file_path_codigo_receta = env('STORAGE_PATH').'reportes/codigos/numero_receta.png';
                barcode($file_path_codigo_receta, $nro_receta, 40, 'horizontal', 'code128', true, 1);
                $pdf->Image($file_path_codigo_receta, 15, $y, 85, 30, 'PNG');
                // codigo barra numero afiliado
                $file_path_codigo_afiliado = env('STORAGE_PATH').'reportes/codigos/numero_afiliado.png';
                // $filepath="", $text="0", $size="20", $orientation="horizontal", $code_type="code128", $print=false, $SizeFactor=1
                barcode($file_path_codigo_afiliado, $registros_receta[0]->n_afiliado, 40, 'horizontal', 'code128', true, 1);
                $pdf->Image($file_path_codigo_afiliado, 115, $y, 75, 30, 'PNG');
    
                $pdf->setY($pdf->getY()+25);
    
                // ##################################  PACIENTE  #####################################################
                // convenio, numero afiliado y plan
                $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(60, 7, utf8_decode($registros_receta[0]->n_convenio), 0, 0, 'L'); 
                $pdf->Cell(60, 7, utf8_decode('Nro. Afiliado: '.$registros_receta[0]->n_afiliado), 0, 0, 'L'); 
                $pdf->Cell(60, 7, utf8_decode($registros_receta[0]->n_plan), 0, 1, 'L'); 
    
                // paciente y dni paciente
                $pdf->Cell(120, 7, utf8_decode('Paciente: '.$registros_receta[0]->n_persona), 0, 0, 'L'); 
                $pdf->Cell(60, 7, utf8_decode($registros_receta[0]->Doc_Tipo.': '.$registros_receta[0]->Doc_Numero), 0, 1, 'L'); 
    
                // fecha nacimiento y sexo
                $pdf->Cell(120, 7, utf8_decode('Fecha Nacimiento: '.Carbon::parse($registros_receta[0]->fec_nac)->format('d/m/Y')), 0, 0, 'L'); 
                $sexo = $registros_receta[0]->sexo == 'F' ? 'Femenino' : ($registros_receta[0]->sexo == 'M' ? 'Masculino' : 'No binario');
                $pdf->Cell(60, 7, utf8_decode('Sexo: '.$sexo ), 0, 1, 'L'); 
    
                $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
                $pdf->Cell(180, 0, '', 1, 1, 'C'); // creamos una linea
                $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
    
                // ###################################  POSOLOGIA  ##############################################################
                $pdf->SetFont('Arial', 'B', 16);
                $pdf->Cell(60, 7, utf8_decode('POSOLOGÍA: '), 0, 1, 'L'); 
                $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 10 mm
    
                // #############################################  MEDICAMENTOS  ####################################################################
                forEach($registros_receta as $receta){
                    if($receta->posologia != ''){
                        $pdf->SetFont('Arial', 'B', 12);
                        if($receta->permite_sustitucion == 'N'){
                            $texto_medicamento = $receta->nombreProducto.' ('.$receta->nombreDroga.') ';
                        }else{
                            $texto_medicamento = $receta->nombreDroga;
                        }
                        $pdf->Cell(180, 7, utf8_decode($texto_medicamento), 0, 1, 'L');
                        $pdf->SetFont('Arial', '', 12);
                        $pdf->Cell(180, 7, utf8_decode($receta->presentacion), 0, 1, 'L');
                        $pdf->Cell(180, 7, utf8_decode('TRATAMIENTO: '.$receta->posologia), 0, 1, 'L');
        
                        $pdf->setY($pdf->getY()+5);
                    }
                }
    
                // #########################################  DIAGNOSTICO  ########################################################################
                // $pdf->Cell(180, 10, '', 0, 1, 'C'); // dejamos un espacio de 10 mm
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(180, 7, utf8_decode('Diagnóstico: '.$registros_receta[0]->diagnostico), 0, 1, 'L');
    
                // #################################### FIRMA MEDICO ######################################################
                $file_path_firma_medico = env('STORAGE_PATH').'uploads/firma_medicos/'.$doctor->nroDoc.'.png';
                if (file_exists($file_path_firma_medico)){
                    $pdf->Image($file_path_firma_medico, 115, 200, 50, 25, 'PNG');
                }
                $pdf->setY(220);
                $pdf->setX(100);
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell(80, 5, utf8_decode($doctor->nombre.' '.$doctor->apellido), 0, 2, 'C');
                $tipo_matricula = $doctor->matricula_tipo == 'MN' ? 'MN.' : 'MP.';
                $pdf->Cell(80, 5, utf8_decode(strtoupper($doctor->especialidad.' '.$tipo_matricula).' '.$doctor->matricula_numero), 0, 2, 'C');
                $pdf->Cell(80, 0, '', 1, 2, 'C'); // creamos una linea
                $pdf->SetFont('Arial', '', 8);
                $pdf->Cell(80, 5, utf8_decode('FIRMA Y SELLO'), 0, 1, 'C');
                $pdf->setX(15);
                $pdf->Cell(180, 5, utf8_decode('Este documento ha sido firmado electrónica o digitalmente según corresponda por Dr/a '.$doctor->nombre.' '.$doctor->apellido), 0, 1, 'R');
    
                $pdf->Cell(180, 2, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
                $pdf->Cell(180, 0, '', 1, 1, 'C'); // creamos una linea
                $pdf->Cell(180, 2, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
    
                // ##################################################### PIE DE PAGINA ###################################################
                $pdf->SetFont('Arial', '', 10);
                $pdf->Cell(60, 5, utf8_decode('Consultorio: '.$doctor->nombreConsultorio), 0, 0, 'L');
                $pdf->Cell(60, 5, utf8_decode('Teléfono: '.$doctor->telefono), 0, 0, 'L');
                $pdf->Cell(60, 5, utf8_decode('Dirección: '.$doctor->direccionConsultorio), 0, 1, 'L');
                if($doctor->horario != null){
                    $de = ' de '.$doctor->horario;
                }else{
                    $de = '';
                }
                $pdf->Cell(180, 5, utf8_decode('Atención: '.$doctor->diasAtencion.$de), 0, 1, 'L');
    
                $pdf->Cell(180, 2, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
                $pdf->Cell(180, 0, '', 1, 1, 'C'); // creamos una linea
                $pdf->Cell(180, 2, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
    
                $pdf->SetFont('Arial', '', 6);
                $texto_receta = 'LA RECETA DEBE VALIDARSE POR EL SISTEMA ONLINE EN LA FARMACIA';
                $pdf->Cell(180, 5, utf8_decode($texto_receta), 0, 1, 'R');
                $pdf->SetFont('Arial', '', 7);
                $texto_final = 'Esta receta fue creada por un emisor inscripto y validado en el Registro de Recetarios Electrónicos del Ministerio de Salud de la Nación - RL '.env('NRO_RL');
                $pdf->Cell(180, 5, utf8_decode($texto_final), 0, 1, 'R');
                $pdf->cell(180, 0, '', 0, 1, 'R');
                $pdf->setY($pdf->getY());
            }

            $mostrar_duplicado = false;
            forEach($registros_receta as $receta){
                if($receta->requiere_duplicado != ''){
                    $mostrar_duplicado = true;
                }
            }
            if($mostrar_duplicado){
                // ###########################################  MOSTRAR DUPLICADO ##############################################################################
                $pdf->AddPage('portrait');
                $pdf->Image(env('IMAGE_PATH').'/logo_receta.png', 15, 16, 40, 0, 'PNG');
                $hoja = 'duplicado';
                if($hoja != ''){
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Cell(119, 7, utf8_decode(strtoupper($hoja).' - '), 0, 0, 'R');
                }
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(61, 7, 'Nro. de receta: '.$nro_receta, 0, 1, 'R');
                $pdf->Cell(180, 7, 'Fecha: '.Carbon::parse($registros_receta[0]->fecha)->format('d/m/Y'), 0, 1, 'R');
    
                $pdf->Cell(180, 10, '', 0, 1, 'C'); // dejamos un espacio de 10 mm por el espacio del logo
    
                // ###################################  MEDICO #####################################################################3
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(180, 7, utf8_decode('Dr/a '.$doctor->nombre.' '.$doctor->apellido), 0, 1, 'L');
                $pdf->SetFont('Arial', '', 12);
                $tipo_matricula = $doctor->matricula_tipo == 'MN' ? 'MN.' : 'MP.';
                $pdf->Cell(180, 7, utf8_decode($doctor->especialidad.' '.$tipo_matricula.' '.$doctor->matricula_numero), 0, 1, 'L');
                
                $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
                $pdf->Cell(180, 0, '', 1, 1, 'C'); // creamos una linea
                $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
    
                // #######################   CODIGOS DE BARRA de nro receta y nro afiliado   ###########################################
                // $filepath="", $text="0", $size="20", $orientation="horizontal", $code_type="code128", $print=false, $SizeFactor=1
                // codigo barra numero receta
                $y = $pdf->GetY();
                $file_path_codigo_receta = env('STORAGE_PATH').'reportes/codigos/numero_receta.png';
                barcode($file_path_codigo_receta, $nro_receta, 40, 'horizontal', 'code128', true, 1);
                $pdf->Image($file_path_codigo_receta, 15, $y, 85, 30, 'PNG');
                // codigo barra numero afiliado
                $file_path_codigo_afiliado = env('STORAGE_PATH').'reportes/codigos/numero_afiliado.png';
                // $filepath="", $text="0", $size="20", $orientation="horizontal", $code_type="code128", $print=false, $SizeFactor=1
                barcode($file_path_codigo_afiliado, $registros_receta[0]->n_afiliado, 40, 'horizontal', 'code128', true, 1);
                $pdf->Image($file_path_codigo_afiliado, 115, $y, 75, 30, 'PNG');
    
                $pdf->setY($pdf->getY()+25);
    
                // ##################################  PACIENTE  #####################################################
                // convenio, numero afiliado y plan
                $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(60, 7, utf8_decode($registros_receta[0]->n_convenio), 0, 0, 'L'); 
                $pdf->Cell(60, 7, utf8_decode('Nro. Afiliado: '.$registros_receta[0]->n_afiliado), 0, 0, 'L'); 
                $pdf->Cell(60, 7, utf8_decode($registros_receta[0]->n_plan), 0, 1, 'L'); 
    
                // paciente y dni paciente
                $pdf->Cell(120, 7, utf8_decode('Paciente: '.$registros_receta[0]->n_persona), 0, 0, 'L'); 
                $pdf->Cell(60, 7, utf8_decode($registros_receta[0]->Doc_Tipo.': '.$registros_receta[0]->Doc_Numero), 0, 1, 'L'); 
    
                // fecha nacimiento y sexo
                $pdf->Cell(120, 7, utf8_decode('Fecha Nacimiento: '.Carbon::parse($registros_receta[0]->fec_nac)->format('d/m/Y')), 0, 0, 'L'); 
                $sexo = $registros_receta[0]->sexo == 'F' ? 'Femenino' : ($registros_receta[0]->sexo == 'M' ? 'Masculino' : 'No binario');
                $pdf->Cell(60, 7, utf8_decode('Sexo: '.$sexo ), 0, 1, 'L'); 
    
                $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
                $pdf->Cell(180, 0, '', 1, 1, 'C'); // creamos una linea
                $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
    
                // ###################################  Rp  ##############################################################
                $pdf->SetFont('Arial', 'B', 16);
                $pdf->Cell(60, 7, utf8_decode('Rp: '), 0, 1, 'L'); 
                $pdf->Cell(180, 5, '', 0, 1, 'C'); // dejamos un espacio de 10 mm
    
                // #############################################  MEDICAMENTOS  ####################################################################
                forEach($registros_receta as $receta){
                    $pdf->SetFont('Arial', 'B', 12);
                    if($receta->permite_sustitucion == 'N'){
                        $texto_medicamento = $receta->nombreProducto.' ('.$receta->nombreDroga.') ';
                    }else{
                        $texto_medicamento = $receta->nombreDroga;
                    }
                    if($receta->tratamiento == 1){
                        $texto_medicamento = $texto_medicamento.' - TRATAMIENTO PROLONGADO';
                    }
                    $pdf->Cell(180, 7, utf8_decode($texto_medicamento), 0, 1, 'L');
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Cell(180, 7, utf8_decode($receta->presentacion.' - '.'Cantidad: '.$receta->cantidad.' '.cantidad_a_letras($receta->cantidad, true)), 0, 1, 'L');
                    
                    // $pdf->Cell(180, 7, utf8_decode('Cantidad: '.$receta->cantidad.' '.cantidad_a_letras($receta->cantidad, true)), 0, 1, 'L');
    
                    if($receta->observaciones != ''){
                        $pdf->Cell(180, 7, utf8_decode('Observaciones: '.$receta->observaciones), 0, 1, 'L');
                    }
    
                    $pdf->setY($pdf->getY()+5);
                }

                // ########################## QR VERIFICADOR #####################################################
                // da el error non-conforming drawing primitive definition `2' @ error/draw.c/RenderMVGContent/4414
                $pdf->Image(env('STORAGE_PATH').'reportes/codigos/qr_empresa.png', 150, 150, 30, 30, 'PNG');
                $pdf->SetFont('Arial', '', 9);
                $pdf->setY(180);
                $pdf->Cell(120, 7, '', 0, 0, 'C');
                $pdf->Cell(60, 7, utf8_decode('VERIFICAR EMISOR'), 0, 1, 'C'); 
                // $pdf->Cell(60, 7, utf8_decode('VERIFICAR EMISOR'), 0, 1, 'C', true, $registros_receta[0]->verificador); // para mostrar link pero no funciona
    
                // #########################################  DIAGNOSTICO  ########################################################################
                // $pdf->Cell(180, 10, '', 0, 1, 'C'); // dejamos un espacio de 10 mm
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(180, 7, utf8_decode('Diagnóstico: '.$registros_receta[0]->diagnostico), 0, 1, 'L');
    
                // #################################### FIRMA MEDICO ######################################################
                $file_path_firma_medico = env('STORAGE_PATH').'uploads/firma_medicos/'.$doctor->nroDoc.'.png';
                if (file_exists($file_path_firma_medico)){
                    $pdf->Image($file_path_firma_medico, 115, 200, 50, 25, 'PNG');
                }
                $pdf->setY(220);
                $pdf->setX(100);
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell(80, 5, utf8_decode($doctor->nombre.' '.$doctor->apellido), 0, 2, 'C');
                $tipo_matricula = $doctor->matricula_tipo == 'MN' ? 'MN.' : 'MP.';
                $pdf->Cell(80, 5, utf8_decode(strtoupper($doctor->especialidad.' '.$tipo_matricula).' '.$doctor->matricula_numero), 0, 2, 'C');
                $pdf->Cell(80, 0, '', 1, 2, 'C'); // creamos una linea
                $pdf->SetFont('Arial', '', 8);
                $pdf->Cell(80, 5, utf8_decode('FIRMA Y SELLO'), 0, 1, 'C');
                $pdf->setX(15);
                $pdf->Cell(180, 5, utf8_decode('Este documento ha sido firmado electrónica o digitalmente según corresponda por Dr/a '.$doctor->nombre.' '.$doctor->apellido), 0, 1, 'R');
    
                $pdf->Cell(180, 2, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
                $pdf->Cell(180, 0, '', 1, 1, 'C'); // creamos una linea
                $pdf->Cell(180, 2, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
    
                // ##################################################### PIE DE PAGINA ###################################################
                $pdf->SetFont('Arial', '', 10);
                $pdf->Cell(60, 5, utf8_decode('Consultorio: '.$doctor->nombreConsultorio), 0, 0, 'L');
                $pdf->Cell(60, 5, utf8_decode('Teléfono: '.$doctor->telefono), 0, 0, 'L');
                $pdf->Cell(60, 5, utf8_decode('Dirección: '.$doctor->direccionConsultorio), 0, 1, 'L');
                if($doctor->horario != null){
                    $de = ' de '.$doctor->horario;
                }else{
                    $de = '';
                }
                $pdf->Cell(180, 5, utf8_decode('Atención: '.$doctor->diasAtencion.$de), 0, 1, 'L');
    
                $pdf->Cell(180, 2, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
                $pdf->Cell(180, 0, '', 1, 1, 'C'); // creamos una linea
                $pdf->Cell(180, 2, '', 0, 1, 'C'); // dejamos un espacio de 5 mm
    
                $pdf->SetFont('Arial', '', 6);
                $texto_receta = 'LA RECETA DEBE VALIDARSE POR EL SISTEMA ONLINE EN LA FARMACIA';
                $pdf->Cell(180, 5, utf8_decode($texto_receta), 0, 1, 'R');
                $pdf->SetFont('Arial', '', 7);
                $texto_final = 'Esta receta fue creada por un emisor inscripto y validado en el Registro de Recetarios Electrónicos del Ministerio de Salud de la Nación - RL '.env('NRO_RL');
                $pdf->Cell(180, 5, utf8_decode($texto_final), 0, 1, 'R');
            }


            if ($accion == 'enviar') {
                $file_path = env('STORAGE_PATH').'/reportes/recetas_afiliados/';
                $filename = 'receta-'.$nro_receta.'.pdf';
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
                    'params' => $this->params,
                    'extras' => $extras,
                    'logged_user' => $logged_user != null ? $logged_user : null
                ]);
            }else{
                $file_path = env('STORAGE_PATH').'/reportes/recetas_afiliados/';
                $filename = 'receta-'.$nro_receta.'.pdf';
                $file = $file_path.$filename;
                $pdf->Output($file, "F");
                return FacadeResponse::make(Storage::disk('recetas_afiliados')->get($filename), 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; ' . $filename,
                ]);
            }
            
        } catch (\Throwable $th) {
            $extras['path'] = env('STORAGE_PATH').'/reportes/recetas_afiliados/';
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => $data,
                'params' => $this->params,
                'extras' => $extras,
                'logged_user' => $logged_user != null ? $logged_user : null
            ]);
        }
    }
}