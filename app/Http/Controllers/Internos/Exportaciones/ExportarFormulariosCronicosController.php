<?php

namespace App\Http\Controllers\Internos\Exportaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\File as FacadeFile;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use File;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;

use App\Services\Pdf\FormularioCronicosGeneralGeneratorPdf;
use App\Services\Pdf\FormularioCronicosOscearaGeneratorPdf;

class ExportarFormulariosCronicosController extends ConexionSpController
{
    /**
     * Crea un pdf vacío de solicitud de medicamentos Resolución 310 para pacientes crónicos para ser impreso y llenado por el médico
     * @param Request $request
     * @return Response $response
     */
    public function exportar_formulario_cronicos(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/consultorio/formularios/exportar-formulario-cronicos',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => [],
            'verificado' => [],
            'path' => ''
        ];
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $errors = [];
            $data = null;
            $message = null;
            $count = 0;
            $code = 0;

            $accion = request('accion') !== null ? request('accion') : 'mostrar';
            $params = [
                'accion' => $accion,
                'guardar' => request('guardar') !== null ? request('guardar') : true,
                'paciente' => request('paciente_cronico') !== null ? request('paciente_cronico') : null,
                'fecha_emision' => request('fecha_emision') !== null ? request('fecha_emision') : Carbon::now()->format('Y-m-d'),
                'diagnosticos' => request('diagnosticos') !== null ? request('diagnosticos') : [],
                'institucion' => request('institucion') !== null ? request('institucion') : null,
                'medico_prescriptor' => request('medico_prescriptor') !== null ? request('medico_prescriptor') : null,
                'medicamentos' => request('medicamentos') !== null ? request('medicamentos') : [],
                'resumen_historia_clinica' => request('resumen_historia_clinica') !== null ? request('resumen_historia_clinica') : null,
                'cuadro_justificativo' => request('cuadro_justificativo') !== null ? request('cuadro_justificativo') : null,
            ];

            // return response()->json(['params' => $params, 'extras' => $extras], 200);

            if($user->hasPermissionTo('exportar datos')){
                $pdf = new Fpdi('portrait', 'mm', 'A4');

                if(env('AMBIENTE_FORMULARIO_CRONICOS', 'general') == 'osceara'){
                    $fcgpdf = new FormularioCronicosOscearaGeneratorPdf();
                    $pdf = $fcgpdf->generar($pdf, $params);
                }else{
                    $fcgpdf = new FormularioCronicosGeneralGeneratorPdf();
                    $pdf = $fcgpdf->generar($pdf, $params);
                }

                if($params['paciente'] != null 
                    && $params['paciente']['nro_doc'] != null 
                    && $params['paciente']['tipo_doc'] != null
                    && $params['guardar'])
                {
                    // guardar en historia clinica el pdf
                    $upload_path_externo = env('UPLOADS_PATH_EXTERNO');
                    if(!File::exists($upload_path_externo)){
                        File::makeDirectory($upload_path_externo, 0777, true);
                    }
                    $archivo_historia = md5(date('YmdHis') . $user->id . uniqid()) . '.pdf';
                    $ruta_archivo_historia = $upload_path_externo.$archivo_historia;
                    $pdf->Output($ruta_archivo_historia, "F");

                    $params = [
                        'tipo_documento' => $params['paciente']['tipo_doc'],
                        'numero_documento' => $params['paciente']['nro_doc'],
                        'usuario' => $logged_user['usuario'],
                        'id_medico' => $params['medico_prescriptor']['id_medico'],
                        'hoja' => 'Formulario de Habilitación de Medicamentos para Enfermedades Crónicas - Resolución 310/04',
                        'archivo' => $archivo_historia,
                        'Talla' => null,
                        'Peso' => null,
                        'BMI' => null,
                        'TA' => null,
                        'FC' => null,
                        'RM' => null,
                        'Antecedentes_Gin' => null,
                        'Anticonceptivo' => null,
                        'Mamas' => null,
                        'Genitales_Externos' => null,
                        'Menarca' => null,
                        'FUM' => null,
                        'Ultimo_Control' => null,
                        'Piel' => null,
                        'Usa_Lentes' => null,
                        'Odoscopia_Normal' => null,
                        'Dentadura' => null,
                        'Pulmones' => null,
                        'Corazon' => null,
                        'Abdomen' => null,
                        'OD_Agudeza_Visual' => null,
                        'OI_Agudeza_Visual' => null,
                        'OD_Agudeza_Audio' => null,
                        'OI_Agudeza_Audio' => null,
                        'Datos_Adicionales' => null,
                        'Hallazgos_Anormales' => null,
                    ];
                    // $extras['params_sp'] = $params;

                    //  devuelve las hojas de la historia clinica
                    array_push($extras['queries'], $this->get_query('validacion', 'AWEB_AgregarHojaHistoriaClinica', $params));
                    $response = $this->ejecutar_sp_directo('validacion', 'AWEB_AgregarHojaHistoriaClinica', $params);
                    array_push($extras['responses'], ['AWEB_AgregarHojaHistoriaClinica' => $response]);
                    // return $extras;
                }

                //  -------------------
                if ($accion == 'enviar') {
                    if(!File::exists(env('STORAGE_PATH').'reportes/formularios/')){
                        File::makeDirectory(env('STORAGE_PATH').'reportes/formularios/');
                    }
                    $file_path = env('STORAGE_PATH').'reportes/formularios/';
                    if(!File::exists($file_path)){
                        File::makeDirectory($file_path);
                    }
                    $filename = 'formulario-310.pdf';
                    $file = $file_path.$filename;
                    $pdf->Output($file, "F");
                    $extras['path'] = $file;
                    return response()->json([
                        'status' => 'ok',
                        'count' => 1,
                        'data' =>$filename,
                        'errors' => $errors,
                        'message' => 'Archivo generado satisfactoriamente',
                        'line' => null,
                        'code' => 1,
                        'params' => $params,
                        'logged_user' => $logged_user,
                        'extras' => $extras
                    ]);
                }else{
                    if(!File::exists(env('STORAGE_PATH').'reportes/formularios/')){
                        File::makeDirectory(env('STORAGE_PATH').'reportes/formularios/');
                    }
                    $file_path = env('STORAGE_PATH').'reportes/formularios/';
                    if(!File::exists($file_path)){
                        File::makeDirectory($file_path);
                    }
                    $filename = 'formulario-310.pdf';
                    $file = $file_path.$filename;
                    $pdf->Output($file, "F");
                    return FacadeResponse::make(Storage::disk('formularios')->get($filename), 200, [
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
                    'code' => -1,
                    'data' => null,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]);
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $params,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }

    /**
     * Crea un pdf vacío de solicitud de medicamentos Resolución 310 para pacientes crónicos para ser impreso y llenado por el médico
     * @param Request $request
     * @return Response $response
     */
    public function exportar_recetario_tratamientos_cronicos(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/programas-especciales/formularios/exportar-recetario-tratamientos-cronicos',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => [],
            'verificado' => [],
            'path' => ''
        ];
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $errors = [];
            $data = null;
            $message = null;
            $count = 0;
            $code = 0;

            $accion = request('accion') !== null ? request('accion') : 'mostrar';
            $params = [
                'accion' => $accion
            ];

            if($user->hasPermissionTo('exportar datos')){
                $pdf = new Fpdi('portrait', 'mm', 'A4');
                //  font setup
                $font = 'Times';  // Courier(mooiespaciada) Times Helvetica Simbol ZapfDingbats arial
                //  -------------------
                $pdf->AddPage('portrait');
                $pdf->SetMargins(10, 15);
                $pdf->Cell(180, 0, '', 0, 1); // espacio en blanco para ubicar el texto
                // logo 
                $pdf->Image(env('IMAGE_PATH').'/'.env('LOGO'), 10, 5, 20, 0, 'PNG');

                $pdf->SetTextColor(0, 0, 0); //(50, 50, 158); // letra en color azul

                $pdf->SetFont($font, 'B', 16);
                $pdf->Cell(180, 7, utf8_decode('Recetario de medicamentos para tratamientos crónicos'), 0, 2, 'C');

                $pdf->Cell(180, 7, '', 0, 1); // espacio en blanco entre textos

                $pdf->SetFont($font, 'B', 10);

                // $pdf->SetFillColor(128, 128, 255);  // fondo azul
                // $pdf->SetDrawColor(255, 255, 255);
                // $x = $pdf->GetX();
                // $y = $pdf->GetY();
                // $pdf->Rect($x + 50, $y - 2, 135, 35, 'DF');
                // $pdf->SetXY($x +60, $y + 10);

                $pdf->Cell(20, 7, utf8_decode('Afiliado N°:'), 1, 0); // número de afiliado del paciente
                $pdf->Cell(6, 7, '', 1, 0); // número de afiliado del paciente
                $pdf->Cell(6, 7, '', 1, 0); // número de afiliado del paciente
                $pdf->Cell(6, 7, '', 1, 0); // número de afiliado del paciente
                $pdf->Cell(6, 7, '', 1, 0); // número de afiliado del paciente
                $pdf->Cell(6, 7, '', 1, 0); // número de afiliado del paciente
                $pdf->Cell(6, 7, '', 1, 0); // número de afiliado del paciente
                $pdf->Cell(6, 7, '', 1, 0); // número de afiliado del paciente
                $pdf->Cell(6, 7, '', 1, 0); // número de afiliado del paciente
                $pdf->Cell(6, 7, '', 1, 0); // número de afiliado del paciente
                $pdf->Cell(6, 7, '', 1, 0); // número de afiliado del paciente
                $pdf->Cell(6, 7, '', 1, 0); // número de afiliado del paciente
                $pdf->Cell(6, 7, '', 1, 0); // número de afiliado del paciente
                $pdf->Cell(6, 7, '', 1, 0); // número de afiliado del paciente
                $pdf->Cell(82, 7, utf8_decode('Sucursal:'), 1, 1); // número de afiliado del paciente
                $pdf->Cell(180, 7, utf8_decode('Apellido y Nombre:'), 1, 1); // apellido y nombre del paciente

                $pdf->Cell(180, 2, '', 0, 1); // espacio en blanco entre textos
                $pdf->SetFont($font, 'B', 14);
                $pdf->Cell(180, 7, utf8_decode('Datos del médico Prescriptor'), 0, 2, 'L'); // título datos del médico prescriptor
                $pdf->Cell(180, 2, '', 0, 1); // espacio en blanco entre textos

                $pdf->SetFont($font, 'B', 10);

                $pdf->Cell(180, 7, utf8_decode('Apellido y Nombre:'), 1, 1); // apellido y nombre del medico prescriptor
                $pdf->Cell(90, 7, utf8_decode('Matrícula:'), 1, 0); // matrícula del medico prescriptor
                $pdf->Cell(90, 7, utf8_decode('Número de Prestador:'), 1, 1); // número de prestador del medico prescriptor

                $pdf->Cell(180, 2, '', 0, 1); // espacio en blanco entre textos
                $pdf->SetFont($font, 'B', 14);
                $pdf->Cell(180, 7, utf8_decode('Medicamentos Prescriptos'), 0, 2, 'L'); // título medicamentos prescriptos
                $pdf->Cell(180, 2, '', 0, 1); // espacio en blanco entre textos

                $pdf->SetFont($font, 'B', 10);

                $pdf->Cell(90, 7, utf8_decode('Nombre del medicamento (Genérico y Comercial)'), 1, 0); // nombre del medicamento prescripto
                $pdf->Cell(45, 7, utf8_decode('Presentación'), 1, 0); // presentación del medicamento prescripto
                $pdf->Cell(45, 7, utf8_decode('N° de envases p/mes'), 1, 1); // numero de envases por mes del medicamento prescripto
                $pdf->Cell(5, 7, utf8_decode('1'), 1, 0); //1
                $pdf->Cell(85, 7, utf8_decode(''), 1, 0); //1
                $pdf->Cell(45, 7, utf8_decode(''), 1, 0); //1
                $pdf->Cell(45, 7, utf8_decode(''), 1, 1); //1
                $pdf->Cell(5, 7, utf8_decode('2'), 1, 0); //2
                $pdf->Cell(85, 7, utf8_decode(''), 1, 0); //2
                $pdf->Cell(45, 7, utf8_decode(''), 1, 0); //2
                $pdf->Cell(45, 7, utf8_decode(''), 1, 1); //2
                $pdf->Cell(5, 7, utf8_decode('3'), 1, 0); //3
                $pdf->Cell(85, 7, utf8_decode(''), 1, 0); //3
                $pdf->Cell(45, 7, utf8_decode(''), 1, 0); //3
                $pdf->Cell(45, 7, utf8_decode(''), 1, 1); //3

                $pdf->Cell(180, 2, '', 0, 1); // espacio en blanco entre textos
                $pdf->SetFont($font, 'B', 14);
                $pdf->Cell(180, 7, utf8_decode('Diagnóstico / Observaciones'), 0, 2, 'L'); // título diagnóstico / observaciones
                $pdf->Cell(180, 2, '', 0, 1); // espacio en blanco entre textos

                $pdf->SetFont($font, 'B', 10);
                $pdf->Cell(180, 7, utf8_decode(''), 1, 2, 'L');
                $pdf->Cell(180, 7, utf8_decode('Meses durante los cuales el médico indica esta receta:'), 1, 2, 'L');

                $x = $pdf->GetX();
                $y = $pdf->GetY();

                $pdf->Rect($x + 90, $y - 6, 5, 5);
                $pdf->setXY($pdf->GetX() + 85, $pdf->GetY() - 7);
                $pdf->Cell(10, 7, utf8_decode('2'), 0, 0); 

                $pdf->Rect($x + 101, $y - 6, 5, 5);
                $pdf->setXY($pdf->GetX() + 1, $pdf->GetY());
                $pdf->Cell(10, 7, utf8_decode('3'), 0, 0); 

                $pdf->Rect($x + 112, $y - 6, 5, 5);
                $pdf->setXY($pdf->GetX() + 1, $pdf->GetY());
                $pdf->Cell(10, 7, utf8_decode('4'), 0, 0);

                $pdf->Rect($x + 123, $y - 6, 5, 5);
                $pdf->setXY($pdf->GetX() + 1, $pdf->GetY());
                $pdf->Cell(10, 7, utf8_decode('5'), 0, 0); 

                $pdf->Rect($x + 134, $y - 6, 5, 5);
                $pdf->setXY($pdf->GetX() + 1, $pdf->GetY());
                $pdf->Cell(10, 7, utf8_decode('6'), 0, 0); 

                $pdf->setXY($pdf->GetX() + 1, $pdf->GetY());
                $pdf->Cell(10, 7, utf8_decode('Meses'), 0, 0); 

                $pdf->Cell(180, 2, '', 0, 1); // espacio en blanco entre textos

                $pdf->setXY($pdf->GetX(), $pdf->GetY());
                $pdf->Rect($x, $y, 120, 30);
                $pdf->setXY($pdf->GetX(), $pdf->GetY() + 29);
                $pdf->Cell(120, 7, utf8_decode('Firma y sello del médico prscriptor'), 0, 0, 'C'); // firma y sello del médico prescriptor
                $pdf->Rect($x + 120, $y, 60, 30);
                $pdf->setXY($pdf->GetX(), $pdf->GetY());
                $pdf->Cell(60, 7, utf8_decode('Fecha: ........../........../......................'), 0, 1, 'C'); // fecha

                $pdf->SetFont($font, 'I', 10);
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->SetXY($x, $y + 2);
                $texto1 = 'Nota: La totalidad de la ficha deberá ser completada a mano con la misma tinta y sin cambio de tipografía. Toda enmienda deberá ser salvada por el prescriptor (con firma y sello)';
                $pdf->MultiCell(180, 3, utf8_decode($texto1), 0, 'L'); 

                $pdf->SetFont($font, 'B', 14);
                $pdf->Cell(180, 7, 'Sr. Profesional:', 0, 1); // titulo 

                $pdf->SetFont($font, '', 8);
                $texto2 = 'Este recetario está destinado a pacientes bajo tratamiento crónico y contribuye a agilizar su tarea durante las consultas. Completado el mismo, el paciente podrá disponer de la opción de la repetición el tiempo de validez que usted indique.';
                $pdf->MultiCell(180, 3, utf8_decode($texto2), 0, 'L'); 

                $pdf->SetFont($font, 'B', 8);
                $texto3 = 'Es importante que no omita completar ninguno de los datos solicitados';
                $pdf->MultiCell(180, 3, utf8_decode($texto3), 0, 'L'); 

                $pdf->SetFont($font, '', 8);
                $texto4 = 'Los medicamentos no deben incluir drogas de uso ocasional (ej. descongestivos, antimigrañosos, etc) ni aquellos bajo vigilancia intensiva o que requieren de análisis de laboratorio para evaluar continuidad o cambios de dosis (clonazepina, por ej.), ni las incluídas em los listados de estupecafientes o psicotrópicos (listas III y IV). En lo que refiere a cantidad de medicamentos recetados, éste recetario debe ser completado de acuerdo a la Norma de prescripción de medicamentos detallada en la guía de Modalidad Operativa vigente.';
                $pdf->MultiCell(180, 3, utf8_decode($texto4), 0, 'L'); 

                $pdf->SetFont($font, 'B', 14);
                $pdf->Cell(180, 7, 'Sr. Afiliado:', 0, 1); // titulo 

                $pdf->SetFont($font, '', 8);
                $texto5 = 'Se autorizará, de acuerdo a los datos que constan en este recetario, una receta por cada mes de tratamiento para la totalidad del período indicado y podrá ser retiradas en la sucursal donde se entregó dicho recetario (excepto AMBA), o enviadas por correo a Ud. (entre 7 y 10 días hábiles dependiendo de la localidad).';
                $pdf->MultiCell(180, 3, utf8_decode($texto5), 0, 'L'); 
                $pdf->Cell(180, 7, utf8_decode('Para hacer uso de esta última opción, deberá agregar los datos que se detallan acontinuación:'), 0, 1);  

                $pdf->SetFont($font, 'B', 10);
                $pdf->Cell(180, 7, utf8_decode('Retira en sucursal: '), 1, 2, 'L');

                $x = $pdf->GetX();
                $y = $pdf->GetY();

                $pdf->Rect($x + 40, $y - 6, 5, 5);
                $pdf->setXY($pdf->GetX() + 35, $pdf->GetY() - 7);
                $pdf->Cell(10, 7, utf8_decode('Si'), 0, 0); 
                $pdf->Rect($x + 55, $y - 6, 5, 5);
                $pdf->setXY($pdf->GetX() + 4, $pdf->GetY());
                $pdf->Cell(10, 7, utf8_decode('No'), 0, 1); 

                $pdf->SetFont($font, 'B', 14);
                $pdf->Cell(180, 10, 'Datos para la entrega en domicilio de recetas', 0, 1); // titulo 

                $pdf->SetFont($font, 'B', 10);
                $pdf->Cell(120, 7, utf8_decode('Dirección: '), 1, 0, 'L'); // dirección para envío de recetas
                $pdf->Cell(60, 7, utf8_decode('Código postal: '), 1, 1, 'L'); // codigo postal para envío de recetas
                $pdf->Cell(120, 7, utf8_decode('Localidad: '), 1, 0, 'L'); // localidad para envío de recetas
                $pdf->Cell(60, 7, utf8_decode('Provincia: '), 1, 1, 'L'); // provincia para envío de recetas
                $pdf->Cell(180, 7, utf8_decode('Teléfono (incluir prefijo): '), 1, 1, 'L'); // teléfono para envío de recetas

                $pdf->Cell(180, 7, utf8_decode(''), 0, 1, 'L'); // espacio entre textos
                // pie de página
                $pdf->SetFont($font, 'I', 8);
                $texto2 = 'El titular de los datos personales tiene la facultad de ejercer el derecho a acceso a los mismos en forma gratuita a intervalos no inferiores a seis meses, salvo que se acredite un interés legítimo de efecto conforme a lo establecido en el artículo 14, inciso 3 de la Ley N° 25.326. la DIRECCIÓN NACIONAL DE PROTECCIÓN DE DATOS PERSONALES, Órgano de Control de la Ley N° 25.326, tiene la atribución de atender las denuncias y reclamos que se interpongan con relación al incumplimiento de Las normas sobre protección de datos personales.';
                $pdf->MultiCell(180, 3, utf8_decode($texto2), 0, 'L'); 

                //  -------------------
                if ($accion == 'enviar') {
                    if(!File::exists(env('STORAGE_PATH').'reportes/formularios/')){
                        File::makeDirectory(env('STORAGE_PATH').'reportes/formularios/');
                    }
                    $file_path = env('STORAGE_PATH').'reportes/formularios/';
                    if(!File::exists($file_path)){
                        File::makeDirectory($file_path);
                    }
                    $filename = 'recetario-cronicos.pdf';
                    $file = $file_path.$filename;
                    $pdf->Output($file, "F");
                    $extas['path'] = $file;
                    return response()->json([
                        'status' => 'ok',
                        'count' => 1,
                        'data' =>$filename,
                        'errors' => $errors,
                        'message' => 'Archivo generado satisfactoriamente',
                        'line' => null,
                        'code' => 1,
                        'params' => $params,
                        'logged_user' => $logged_user,
                        'extras' => $extras
                    ]);
                }else{
                    if(!File::exists(env('STORAGE_PATH').'reportes/formularios/')){
                        File::makeDirectory(env('STORAGE_PATH').'reportes/formularios/');
                    }
                    $file_path = env('STORAGE_PATH').'reportes/formularios/';
                    if(!File::exists($file_path)){
                        File::makeDirectory($file_path);
                    }
                    $filename = 'recetario-cronicos.pdf';
                    $file = $file_path.$filename;
                    $pdf->Output($file, "F");
                    return FacadeResponse::make(Storage::disk('formularios')->get($filename), 200, [
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
                    'code' => -1,
                    'data' => null,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]);
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' - Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $params,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }
}