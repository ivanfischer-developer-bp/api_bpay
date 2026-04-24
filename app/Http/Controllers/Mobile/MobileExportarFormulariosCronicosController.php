<?php

namespace App\Http\Controllers\Mobile;

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

class MobileExportarFormulariosCronicosController extends ConexionSpController
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
            'url' => '/mobile/formularios/exportar-formulario-cronicos',
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
                'paciente' => request('paciente_cronico') !== null ? request('paciente_cronico') : null
            ];

            // return response()->json(['params' => $params, 'extras' => $extras], 200);

            if($user->hasPermissionTo('exportar datos')){
                $pdf = new Fpdi('portrait', 'mm', 'A4');
                //  font setup
                $font = 'Arial';
                //  -------------------
                $pdf->AddPage('portrait');
                $pdf->SetMargins(10, 15);
                $pdf->Cell(180, 0, '', 0, 1); // espacio en blanco para ubicar el texto
                // logo 
                $pdf->Image(env('IMAGE_PATH').'/'.env('LOGO'), 10, 5, 20, 0, 'PNG');
                $pdf->SetFont($font, 'B', 14);
                $pdf->Cell(180, 7, utf8_decode('Formulario de solicitud de medicamentos de Resolución 310'), 0, 2, 'C');

                $pdf->Cell(180, 7, '', 0, 1); // espacio en blanco entre textos

                $apellido_y_nombre = $params['paciente'] != null ? $params['paciente']['apellido_y_nombre'] : '';
                $nro_afiliado = $params['paciente'] != null ? $params['paciente']['nro_afiliado'] : '';
                $edad = $params['paciente'] != null ? $params['paciente']['edad'] : '';
                $telefono = $params['paciente'] != null ? $params['paciente']['telefono'] : '';
                $domicilio = $params['paciente'] != null ? $params['paciente']['domicilio'] : '';
                $email = $params['paciente'] != null ? $params['paciente']['email'] : '';
                $pdf->SetFont($font, '', 10);
                $pdf->Cell(180, 7, utf8_decode('Apellido y Nombre: '.$apellido_y_nombre), 1, 2); // apellido y nombre del paciente
                $pdf->Cell(75, 7, utf8_decode('Nro Afiliado: '.$nro_afiliado), 1, 0); // número de afiliado del paciente
                $pdf->Cell(30, 7, utf8_decode('Edad: '.$edad), 1, 0); // número de afiliado del paciente
                $pdf->Cell(75, 7, utf8_decode('Teléfono: '.$telefono), 1, 1); // telefono del paciente
                $pdf->Cell(90, 7, utf8_decode('Domicilio: '.$domicilio), 1, 0); // domicilio del paciente
                $pdf->Cell(90, 7, utf8_decode('Email: '.$email), 1, 1); // email del paciente
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->Rect($x, $y, 180, 30);
                $pdf->SetXY($x, $y + 1);
                $textoResumenHC = $params['paciente'] != null && $params['paciente']['resumen_historia_clinica'] != '' ? $params['paciente']['resumen_historia_clinica'] : 'Breve resumen de historia clínica ';
                $pdf->MultiCell(180, 5, utf8_decode($textoResumenHC), 0, 'L'); // resumen de hitoria clinica del paciente
                $pdf->SetXY($x, $y + 30);
                $pdf->SetFont($font, '', 9);
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->Rect($x, $y, 180, 30);
                $pdf->SetXY($x, $y + 1);
                $cuadroJustificativo = $params['paciente'] != null && $params['paciente']['cuadro_justificativo'] != '' ? $params['paciente']['cuadro_justificativo'] : 'Descripción del cuadro que justifica el uso de la/s droga/s solicitada/s (incluyendo limitaciones al uso de otra/s droga/s) ';
                $pdf->MultiCell(180, 5, utf8_decode($cuadroJustificativo), 0, 'L'); // justificacion de la medicación solicitada
                $pdf->SetXY($x, $y + 30);

                $pdf->Cell(180, 2, '', 0, 2); // espacio en blanco entre textos

                $pdf->SetFont($font, '', 8);
                $pdf->Cell(51, 7, utf8_decode('Principio activo '), 1, 0); // principio activo del medicamento solicitado
                $pdf->Cell(52, 7, utf8_decode('Marca Comercial '), 1, 0); // marca comercial del medicamento solicitado
                $pdf->Cell(51, 7, utf8_decode('Unidad Posológica '), 1, 0); // unidad posológica del medicamento solicitado
                // $pdf->Cell(36, 10, utf8_decode('Comprimidos por día '), 1, 0); // comprimidos por día del medicamento solicitado
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->Rect($x, $y, 10, 7);
                $pdf->SetXY($x, $y + 1);
                $pdf->MultiCell(10, 3, utf8_decode('Dosis diaria'), 0, 'L'); // dosis diaria del medicamento solicitado
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->Rect($x + 164, $y -7, 15, 7);
                $pdf->SetXY($x + 164, $y - 6);
                $pdf->MultiCell(15, 3, utf8_decode('Número. unidades '), 0, 'L'); // contenido del envase del medicamento solicitado
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                if($params['paciente'] != null && isset($params['paciente']['medicamentos']) && is_array($params['paciente']['medicamentos']) && count($params['paciente']['medicamentos']) > 0) {
                    $medicamentos = $params['paciente']['medicamentos'];
                    // return response()->json(['params' => $params, 'medicamentos' => $medicamentos, 'extras' => $extras], 200);
                    foreach($medicamentos as $medicamento){
                        $pdf->Cell(51, 5, utf8_decode($medicamento['principio_activo']), 1, 0); // principio activo del medicamento solicitado
                        $pdf->Cell(52, 5, utf8_decode($medicamento['marca_comercial']), 1, 0); // marca comercial del medicamento solicitado
                        $pdf->Cell(51, 5, utf8_decode($medicamento['unidad_posologica']), 1, 0); // unidad posológica del medicamento solicitado
                        $pdf->Cell(10, 5, utf8_decode($medicamento['comprimidos_x_dia']), 1, 0, 'R'); // dosis diaria del medicamento solicitado
                        $pdf->Cell(15, 5, utf8_decode($medicamento['contenido_envase']), 1, 1, 'R'); // contenido del envase del medicamento solicitado
                    }
                }else{
                    $pdf->Cell(51, 5, '', 1, 0); // fila 1
                    $pdf->Cell(52, 5, '', 1, 0); // fila 1
                    $pdf->Cell(51, 5, '', 1, 0); // fila 1
                    $pdf->Cell(10, 5, '', 1, 0); // fila 1
                    $pdf->Cell(15, 5, '', 1, 1); // fila 1
                    $pdf->Cell(51, 5, '', 1, 0); // fila 2
                    $pdf->Cell(52, 5, '', 1, 0); // fila 2
                    $pdf->Cell(51, 5, '', 1, 0); // fila 2
                    $pdf->Cell(10, 5, '', 1, 0); // fila 2
                    $pdf->Cell(15, 5, '', 1, 1); // fila 2
                    $pdf->Cell(51, 5, '', 1, 0); // fila 3
                    $pdf->Cell(52, 5, '', 1, 0); // fila 3
                    $pdf->Cell(51, 5, '', 1, 0); // fila 3
                    $pdf->Cell(10, 5, '', 1, 0); // fila 3
                    $pdf->Cell(15, 5, '', 1, 1); // fila 3
                    $pdf->Cell(51, 5, '', 1, 0); // fila 4
                    $pdf->Cell(52, 5, '', 1, 0); // fila 4
                    $pdf->Cell(51, 5, '', 1, 0); // fila 4
                    $pdf->Cell(10, 5, '', 1, 0); // fila 4
                    $pdf->Cell(15, 5, '', 1, 1); // fila 4
                    $pdf->Cell(51, 5, '', 1, 0); // fila 5
                    $pdf->Cell(52, 5, '', 1, 0); // fila 5
                    $pdf->Cell(51, 5, '', 1, 0); // fila 5
                    $pdf->Cell(10, 5, '', 1, 0); // fila 5
                    $pdf->Cell(15, 5, '', 1, 1); // fila 5
                    $pdf->Cell(51, 5, '', 1, 0); // fila 6
                    $pdf->Cell(52, 5, '', 1, 0); // fila 6
                    $pdf->Cell(51, 5, '', 1, 0); // fila 6
                    $pdf->Cell(10, 5, '', 1, 0); // fila 6
                    $pdf->Cell(15, 5, '', 1, 1); // fila 6
                    $pdf->Cell(51, 5, '', 1, 0); // fila 7
                    $pdf->Cell(52, 5, '', 1, 0); // fila 7
                    $pdf->Cell(51, 5, '', 1, 0); // fila 7
                    $pdf->Cell(10, 5, '', 1, 0); // fila 7
                    $pdf->Cell(15, 5, '', 1, 1); // fila 7
                }

                $pdf->Cell(180, 2, '', 0, 2); // espacio entre textos
                // fuente en 12
                $pdf->SetFont($font, 'B', 12);
                $pdf->Cell(180, 7, utf8_decode('Diagnósticos: '), 0, 2, 'L'); // titulo diagnosticos
                // fuente en 8
                $pdf->SetFont($font, '', 10);
                $pdf->Cell(180, 2, '', 0, 2); // espacio entre textos

                // Obtener diagnósticos del paciente como array de strings
                $diagnosticos_paciente = [];
                if($params['paciente'] != null) {
                    if(isset($params['paciente']['diagnosticos']) && is_array($params['paciente']['diagnosticos'])) {
                        $diagnosticos_paciente = $params['paciente']['diagnosticos'];
                    }
                }
                
                // Debug: guardar los diagnósticos en extras
                $extras['diagnosticos_debug'] = [
                    'array_completo' => $diagnosticos_paciente,
                    'es_array' => is_array($diagnosticos_paciente),
                    'cantidad' => count($diagnosticos_paciente),
                    'contenido_json' => json_encode($diagnosticos_paciente)
                ];

                // Función helper para dibujar checkbox marcado con check dibujado
                $dibujar_checkbox = function($pdf, $x, $y, $checked = false) {
                    // Guardar la posición actual para no disrumpir el flujo del documento
                    $current_x = $pdf->GetX();
                    $current_y = $pdf->GetY();
                    
                    // Dibujar el rectángulo del checkbox (4x4mm)
                    $pdf->SetDrawColor(0, 0, 0);
                    $pdf->SetLineWidth(0.3);
                    $pdf->Rect($x, $y, 4, 4);
                    
                    if($checked) {
                        // Establecer un ancho de línea grueso para que el check se vea claro
                        $pdf->SetLineWidth(0.8);
                        
                        // Dibujar un check (tick mark) proporcional al tamaño 4x4
                        // Primera línea: diagonal inferior (parte corta del check)
                        $pdf->Line($x + 0.7, $y + 2, $x + 1.7, $y + 3.1);
                        
                        // Segunda línea: diagonal superior (parte larga del check)  
                        $pdf->Line($x + 1.7, $y + 3.1, $x + 3.7, $y + 0.5);
                        
                        // Restaurar el ancho de línea a normal
                        $pdf->SetLineWidth(0.3);
                    }
                    
                    // Restaurar la posición original del PDF (sin cambios en la alineación)
                    $pdf->SetXY($current_x, $current_y);
                };

                // Función para buscar si un diagnóstico está en el array
                $tiene_diagnostico = function($diagnostico_texto, $diagnosticos_array) {
                    return in_array($diagnostico_texto, $diagnosticos_array);
                };

                $pdf->Cell(50, 3, utf8_decode('Hipertención arterial (I10)'), 0, 0); // diagnostico 1
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $checked = $tiene_diagnostico('Hipertención arterial (I10)', $diagnosticos_paciente);
                $dibujar_checkbox($pdf, $x, $y, $checked);
                $pdf->setX($pdf->GetX() + 10);
                $pdf->Cell(50, 3, utf8_decode('Anticoagulación (D68.3)'), 0, 0); // diagnostico 2
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $checked = $tiene_diagnostico('Anticoagulación (D68.3)', $diagnosticos_paciente);
                $dibujar_checkbox($pdf, $x, $y, $checked);
                $pdf->setX($pdf->GetX() + 10);
                $pdf->SetFont($font, '', 9);
                $pdf->Cell(55, 3, utf8_decode('Medicación anticonvulsivante (G40)'), 0, 0); // diagnostico 3
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $checked = $tiene_diagnostico('Medicación anticonvulsivante (G40)', $diagnosticos_paciente);
                $dibujar_checkbox($pdf, $x, $y, $checked);
                $pdf->setX($pdf->GetX() + 10);
                $pdf->Cell(0, 5, '', 0, 1); // retorno de carro
                $pdf->Cell(180, 1, '', 0, 1); // espacio
                // fuente en 10
                $pdf->SetFont($font, '', 10);

                $pdf->Cell(50, 3, utf8_decode('Gota (M10)'), 0, 0); // diagnostico 4
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $checked = $tiene_diagnostico('Gota (M10)', $diagnosticos_paciente);
                $dibujar_checkbox($pdf, $x, $y, $checked);
                $pdf->setX($pdf->GetX() + 10);
                $pdf->Cell(50, 3, utf8_decode('Dislipemia (E78)'), 0, 0); // diagnostico 5
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $checked = $tiene_diagnostico('Dislipemia (E78)', $diagnosticos_paciente);
                $dibujar_checkbox($pdf, $x, $y, $checked);
                $pdf->setX($pdf->GetX() + 10);
                $pdf->Cell(55, 3, utf8_decode('Artritis reumatoidea (M05)'), 0, 0); // diagnostico 6
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $checked = $tiene_diagnostico('Artritis reumatoidea (M05)', $diagnosticos_paciente);
                $dibujar_checkbox($pdf, $x, $y, $checked);
                $pdf->setX($pdf->GetX() + 10);
                $pdf->Cell(0, 5, '', 0, 1); // retorno de carro
                $pdf->Cell(180, 1, '', 0, 1); // espacio
                // fuente en 10
                $pdf->SetFont($font, '', 10);

                $pdf->Cell(50, 3, utf8_decode('Enfermedad coronaria (I20)'), 0, 0); // diagnostico 7
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $checked = $tiene_diagnostico('Enfermedad coronaria (I20)', $diagnosticos_paciente);
                $dibujar_checkbox($pdf, $x, $y, $checked);
                $pdf->setX($pdf->GetX() + 10);
                $pdf->Cell(50, 3, utf8_decode('Hipo/Hipertiroidismo (E03)'), 0, 0); // diagnostico 8
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $checked = $tiene_diagnostico('Hipo/Hipertiroidismo (E03)', $diagnosticos_paciente);
                $dibujar_checkbox($pdf, $x, $y, $checked);
                $pdf->setX($pdf->GetX() + 10);
                $pdf->Cell(55, 3, utf8_decode('Asma o EPOC (J45)'), 0, 0); // diagnostico 9
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $checked = $tiene_diagnostico('Asma o EPOC (J45)', $diagnosticos_paciente);
                $dibujar_checkbox($pdf, $x, $y, $checked);
                $pdf->setX($pdf->GetX() + 10);
                $pdf->Cell(0, 5, '', 0, 1); // retorno de carro
                $pdf->Cell(180, 1, '', 0, 1); // espacio
                // fuente en 10
                $pdf->SetFont($font, '', 10);

                $pdf->Cell(50, 3, utf8_decode('Insuficiencia Cardíaca (I50)'), 0, 0); // diagnostico 10
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $checked = $tiene_diagnostico('Insuficiencia Cardíaca (I50)', $diagnosticos_paciente);
                $dibujar_checkbox($pdf, $x, $y, $checked);
                $pdf->setX($pdf->GetX() + 10);
                $pdf->Cell(50, 3, utf8_decode('Colitis ulcerosa (K50)'), 0, 0); // diagnostico 11
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $checked = $tiene_diagnostico('Colitis ulcerosa (K50)', $diagnosticos_paciente);
                $dibujar_checkbox($pdf, $x, $y, $checked);
                $pdf->setX($pdf->GetX() + 10);
                $pdf->Cell(55, 3, utf8_decode('Glaucoma (H40)'), 0, 0); // diagnostico 12
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $checked = $tiene_diagnostico('Glaucoma (H40)', $diagnosticos_paciente);
                $dibujar_checkbox($pdf, $x, $y, $checked);
                $pdf->setX($pdf->GetX() + 10);
                $pdf->Cell(0, 5, '', 0, 1); // retorno de carro
                $pdf->Cell(180, 1, '', 0, 1); // espacio
                // fuente en 10
                $pdf->SetFont($font, '', 10);

                $pdf->Cell(50, 3, utf8_decode('Arritmia crónica (I49)'), 0, 0); // diagnostico 13
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $checked = $tiene_diagnostico('Arritmia crónica (I49)', $diagnosticos_paciente);
                $dibujar_checkbox($pdf, $x, $y, $checked);
                $pdf->setX($pdf->GetX() + 10);
                $pdf->SetFont($font, '', 9);
                $pdf->Cell(50, 3, utf8_decode('Enfermedad extrapiramidal (G20)'), 0, 0); // diagnostico 14
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $checked = $tiene_diagnostico('Enfermedad extrapiramidal (G20)', $diagnosticos_paciente);
                $dibujar_checkbox($pdf, $x, $y, $checked);
                $pdf->setX($pdf->GetX() + 10);
                $pdf->SetFont($font, '', 10);
                $pdf->Cell(55, 3, utf8_decode('Modificadores de conducta (F29)'), 0, 0); // diagnostico 15
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $checked = $tiene_diagnostico('Modificadores de conducta (F29)', $diagnosticos_paciente);
                $dibujar_checkbox($pdf, $x, $y, $checked);
                $pdf->setX($pdf->GetX() + 10);
                $pdf->Cell(0, 5, '', 0, 1); // retorno de carro
                
                $pdf->Cell(180, 2, '', 0, 2); // espacio entre textos
                // fuente en 12
                $pdf->SetFont($font, 'B', 12);
                $pdf->Cell(180, 7, utf8_decode('Datos del médico prescriptor: '), 0, 2, 'L'); // titulo diagnosticos
                // fuente en 8
                $pdf->SetFont($font, '', 10);
                $pdf->Cell(180, 2, '', 0, 2); // espacio entre textos

                $apellido_y_nombre_medico = $params['paciente'] != null && $params['paciente']['medico_prescriptor'] ? $params['paciente']['medico_prescriptor']['nombre_y_apellido'] : ''; // nombre y apellido del médico prescriptor
                $especialidad_medico = $params['paciente'] != null && $params['paciente']['medico_prescriptor'] ? $params['paciente']['medico_prescriptor']['especialidad'] : ''; // especialidad del médico prescriptor
                $matricula_medico = $params['paciente'] != null && $params['paciente']['medico_prescriptor'] ? $params['paciente']['medico_prescriptor']['matricula'] : ''; // matrícula del médico prescriptor
                $telefono_medico = $params['paciente'] != null && $params['paciente']['medico_prescriptor'] ? $params['paciente']['medico_prescriptor']['telefono'] : ''; // teléfono del médico prescriptor
                $institucion = $params['paciente'] != null ? $params['paciente']['institucion'] : ''; // institución del médico prescriptor
                $fecha = $params['paciente'] != null ? $params['paciente']['fecha_emision'] : '     /     /      '; // fecha de emisión del formulario
                $pdf->Cell(180, 7, utf8_decode('Nombre y Apellido: '.$apellido_y_nombre_medico), 1, 2); // nombre y apellido del médico prescriptor
                $pdf->Cell(130, 7, utf8_decode('Especialidad: '.$especialidad_medico), 1, 0); // especialidad del médico prescriptor
                $pdf->Cell(50, 7, utf8_decode('Matrícula: '.$matricula_medico), 1, 1); // matrícula del médico prescriptor
                $pdf->Cell(60, 7, utf8_decode('Teléfono: '.$telefono_medico), 1, 0); // teléfono del médico prescriptor
                $pdf->Cell(120, 7, utf8_decode('Institución: '.$institucion), 1, 1); // institución del médico prescriptor

                $pdf->SetX($pdf->GetX() + 10);
                $pdf->SetY(232);
                $pdf->Cell(140, 10, utf8_decode('Firma y sello del médico: '), 0, 0); // firma y sello del médico prescriptor
                $pdf->Cell(40, 10, utf8_decode('Fecha: ' . $fecha), 0, 2); // fecha
                if($params['paciente'] != null && isset($params['paciente']['medico_prescriptor']['nro_doc']) && $params['paciente']['medico_prescriptor']['nro_doc'] != '') {
                    if(file_exists(storage_path('app/public/uploads/firma_medicos').'/'.$params['paciente']['medico_prescriptor']['nro_doc'].'.png')){
                        $pdf->Image(storage_path('app/public/uploads/firma_medicos').'/'.$params['paciente']['medico_prescriptor']['nro_doc'].'.png', 50, $pdf->GetY() - 15, 30, 0, 'PNG');
                        // sello
                        $pdf->SetX(50);
                        $pdf->SetY(240);
                        $pdf->SetFont($font, 'B', 6);
                        $pdf->Cell(40, 2, utf8_decode(''), 0, 0, 'L');
                        $pdf->Cell(20, 2, utf8_decode($params['paciente']['medico_prescriptor']['nombre_y_apellido']), 0, 2, 'C');
                        $pdf->Cell(20, 2, utf8_decode($params['paciente']['medico_prescriptor']['matricula']), 0, 2, 'C');
                        $pdf->Cell(20, 2, utf8_decode($params['paciente']['medico_prescriptor']['especialidad']), 0, 2, 'C');
                    }
                }
                $pdf->SetFont($font, 'B', 8);
                $pdf->SetX($pdf->GetX() + 10);
                $pdf->SetY(250);
                $pdf->Cell(180, 3, utf8_decode('Nota: El formulario se tramitará dentro de los 10 días hábiles de recibida la solicitud.'), 0, 2, 'L');
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $texto = 'Una vez aprobado por Farmacia, el alta de los medicamentos en las farmacias de AMBA se efectivizará dentro de las 72 horas hábiles. Le recordamos que cada 2 años deberá actualizar la habilitación. Cabe destacar que para la dispensación de los medicamentos, en la red de farmacias habilitadas, debe presentar la receta correspondiente.';
                $pdf->MultiCell(180, 3, utf8_decode($texto), 0, 'L'); 
                $pdf->SetXY($x, $y + 10);

                $pdf->Cell(180, 1, '', 0, 1); // espacio en blanco para ubicar el texto
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->SetFont($font, 'I', 8);
                $texto2 = 'El titular de los datos personales tiene la facultad de ejercer el derecho a acceso a los mismos en forma gratuita a intervalos no inferiores a seis meses, salvo que se acredite un interés legítimo de efecto conforme a lo establecido en el artículo 14, inciso 3 de la Ley N° 25.326. la DIRECCIÓN NACIONAL DE PROTECCIÓN DE DATOS PERSONALES, Órgano de Control de la Ley N° 25.326, tiene la atribución de atender las denuncias y reclamos que se interpongan con relación al incumplimiento de Las normas sobre protección de datos personales.';
                $pdf->MultiCell(180, 3, utf8_decode($texto2), 0, 'L'); 

                //  -------------------
                $pdf->AddPage('portrait');
                $pdf->SetMargins(10, 15);
                $pdf->Cell(180, 0, '', 0, 1); // espacio en blanco para ubicar el texto
                // logo 
                $pdf->Image(env('IMAGE_PATH').'/'.env('LOGO'), 10, 7, 45, 0, 'PNG');
                $x = $pdf->GetX();
                $y = $pdf->GetY();

                // recuadro de color
                // $pdf->SetFillColor(0, 0, 128);  // fondo azul para el título del segundo folio
                $pdf->SetDrawColor(255, 255, 255);
                // $pdf->Rect($x + 50, $y - 2, 135, 35, 'DF');
                $pdf->SetXY($x +60, $y + 10);
                
                // tamaño y tipo y color del texto
                $pdf->SetTextColor(0, 0, 0);  // texto en blanco para el título del segundo folio
                $pdf->SetFont($font, 'BI', 20);

                $pdf->MultiCell(130, 7, utf8_decode('Habilitación de medicamentos para enfermedades crónicas (Resolución 310/04)'), 0, 'L'); // título del segundo folio
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetDrawColor(0, 0, 0);
                $pdf->SetXY($x, $y + 40);
                
                // tamaño y tipo y color del texto
                $pdf->SetTextColor(0, 0, 0); // letra en color negro
                $pdf->SetFont($font, '', 16);

                $pdf->Cell(180, 7, '', 0, 1); // espacio en blanco entre textos
                $pdf->Cell(180, 7, 'Procedimiento', 0, 1); // espacio en blanco entre textos
                $pdf->Cell(180, 7, '', 0, 1); // espacio en blanco entre textos

                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->SetXY($x, $y);
                $pdf->MultiCell(180, 7, utf8_decode('1.- Entregar a tu médico el formulario adjunto para que lo complete de puño y letra, lo firme y lo selle.'), 0, 'L'); 
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->SetXY($x, $y + 10);
                $pdf->MultiCell(180, 7, utf8_decode('2.- Escanear el documento y enviar por email a la delegación más cercana a tu domicilio o presentarlo personalmente.'), 0, 'L'); 
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->SetXY($x, $y + 10);
                $pdf->MultiCell(180, 7, utf8_decode('3.- Recibirás un email de notificación sobre el estado del pedido.'), 0, 'L'); 

                $pdf->Cell(180, 20, '', 0, 1); // espacio en blanco entre textos

                $pdf->SetFont($font, 'BI', 20);

                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->SetXY($x, $y + 10);
                $pdf->Cell(180, 10, utf8_decode('Nota: '), 0, 1, 'L'); 

                $pdf->SetFont($font, 'I', 14);
                
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->SetXY($x, $y);
                $texto3 = 'Te recordamos que la autorización de los medicamentos correspondientes tiene una validez de dos años, pasado este lapso deberás presentar nuevamente el formulario para realizar una nueva habilitación.';
                $pdf->MultiCell(180, 10, utf8_decode($texto3), 0, 'L'); 

                $pdf->Cell(180, 10, '', 0, 1); // espacio en blanco entre textos

                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->SetXY($x, $y);
                $texto4 = 'Para cualquier información adicional, podrás consultar la página web de la Superintendencia de Servicios de Salud: www.sssalud.gob.ar';
                $pdf->MultiCell(180, 10, utf8_decode($texto4), 0, 'L'); 

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
                    $archivo = $file_path.$filename;
                    $pdf->Output($archivo, "F");
                    $extas['path'] = $archivo;
                    return response()->json([
                        'status' => 'ok',
                        'count' => 1,
                        'data' =>$archivo,
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
            'url' => '/mobile/formularios/exportar-recetario-tratamientos-cronicos',
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

                $pdf->SetTextColor(0, 0, 0); // (50, 50, 158); // letra en color azul

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