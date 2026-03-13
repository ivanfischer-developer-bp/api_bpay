<?php

namespace App\Services\Pdf;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response as FacadeResponse;
use setasign\Fpdi\Fpdi;

class FormularioCronicosGeneralGeneratorPdf
{
    /**
     * Genera PDF del formulario de solicitud de medicamentos Resolución 310 (General)
     * 
     * @param array $params Parámetros del formulario
     * @param array $extras Información adicional
     * @param object $user Usuario autenticado
     * @param object $controller Instancia del controlador
     * @return Fpdi
     */
    public function generar(Fpdi $pdf, array $params): Fpdi
    {
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
        $textoResumenHC = $params['resumen_historia_clinica'] != '' ? $params['resumen_historia_clinica'] : '';
        $pdf->Cell(180, 5, utf8_decode('Breve resumen de historia clínica '), 0, 1, 'L'); // resumen de hitoria clinica del paciente
        $pdf->MultiCell(180, 5, utf8_decode($textoResumenHC), 0, 'L'); // resumen de hitoria clinica del paciente
        $pdf->SetXY($x, $y + 30);
        $pdf->SetFont($font, '', 9);
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->Rect($x, $y, 180, 30);
        $pdf->SetXY($x, $y + 1);
        $cuadroJustificativo = $params['cuadro_justificativo'] != '' ? $params['cuadro_justificativo'] : '';
        $pdf->Cell(180, 5, utf8_decode('Descripción del cuadro que justifica el uso de la/s droga/s solicitada/s (incluyendo limitaciones al uso de otra/s droga/s) '), 0, 1, 'L'); // justificacion de la medicación solicitada
        $pdf->MultiCell(180, 5, utf8_decode($cuadroJustificativo), 0, 'L'); // justificacion de la medicación solicitada
        $pdf->SetXY($x, $y + 30);

        $pdf->Cell(180, 2, '', 0, 2); // espacio en blanco entre textos

        $pdf->SetFont($font, '', 8);
        $pdf->Cell(51, 7, utf8_decode('Principio activo '), 1, 0); // principio activo del medicamento solicitado
        $pdf->Cell(53, 7, utf8_decode('Marca Comercial '), 1, 0); // marca comercial del medicamento solicitado
        $pdf->Cell(51, 7, utf8_decode('Unidad Posológica '), 1, 0); // unidad posológica del medicamento solicitado
        // $pdf->Cell(36, 10, utf8_decode('Comprimidos por día '), 1, 0); // comprimidos por día del medicamento solicitado
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->Rect($x, $y, 10, 7);
        $pdf->SetXY($x, $y + 1);
        $pdf->MultiCell(10, 3, utf8_decode('Dosis diaria'), 0, 'L'); // dosis diaria del medicamento solicitado
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->Rect($x + 165, $y -7, 15, 7);
        $pdf->SetXY($x + 165, $y - 6);
        $pdf->MultiCell(15, 3, utf8_decode('Número. unidades '), 0, 'L'); // contenido del envase del medicamento solicitado
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        if(isset($params['medicamentos']) && is_array($params['medicamentos']) && count($params['medicamentos']) > 0) {
            $medicamentos = $params['medicamentos'];
            // return response()->json(['params' => $params, 'medicamentos' => $medicamentos, 'extras' => $extras], 200);
            foreach($medicamentos as $medicamento){
                $pdf->Cell(51, 5, utf8_decode($medicamento['principio_activo']), 1, 0); // principio activo del medicamento solicitado
                $pdf->Cell(53, 5, utf8_decode($medicamento['marca_comercial']), 1, 0); // marca comercial del medicamento solicitado
                $pdf->Cell(51, 5, utf8_decode($medicamento['unidad_posologica']), 1, 0); // unidad posológica del medicamento solicitado
                $pdf->Cell(10, 5, utf8_decode($medicamento['comprimidos_x_dia']), 1, 0, 'R'); // dosis diaria del medicamento solicitado
                $pdf->Cell(15, 5, utf8_decode($medicamento['contenido_envase']), 1, 1, 'R'); // contenido del envase del medicamento solicitado
            }
        }else{
            $pdf->Cell(51, 5, '', 1, 0); // fila 1
            $pdf->Cell(53, 5, '', 1, 0); // fila 1
            $pdf->Cell(51, 5, '', 1, 0); // fila 1
            $pdf->Cell(10, 5, '', 1, 0); // fila 1
            $pdf->Cell(15, 5, '', 1, 1); // fila 1
            $pdf->Cell(51, 5, '', 1, 0); // fila 2
            $pdf->Cell(53, 5, '', 1, 0); // fila 2
            $pdf->Cell(51, 5, '', 1, 0); // fila 2
            $pdf->Cell(10, 5, '', 1, 0); // fila 2
            $pdf->Cell(15, 5, '', 1, 1); // fila 2
            $pdf->Cell(51, 5, '', 1, 0); // fila 3
            $pdf->Cell(53, 5, '', 1, 0); // fila 3
            $pdf->Cell(51, 5, '', 1, 0); // fila 3
            $pdf->Cell(10, 5, '', 1, 0); // fila 3
            $pdf->Cell(15, 5, '', 1, 1); // fila 3
            $pdf->Cell(51, 5, '', 1, 0); // fila 4
            $pdf->Cell(53, 5, '', 1, 0); // fila 4
            $pdf->Cell(51, 5, '', 1, 0); // fila 4
            $pdf->Cell(10, 5, '', 1, 0); // fila 4
            $pdf->Cell(15, 5, '', 1, 1); // fila 4
            $pdf->Cell(51, 5, '', 1, 0); // fila 5
            $pdf->Cell(53, 5, '', 1, 0); // fila 5
            $pdf->Cell(51, 5, '', 1, 0); // fila 5
            $pdf->Cell(10, 5, '', 1, 0); // fila 5
            $pdf->Cell(15, 5, '', 1, 1); // fila 5
            $pdf->Cell(51, 5, '', 1, 0); // fila 6
            $pdf->Cell(53, 5, '', 1, 0); // fila 6
            $pdf->Cell(51, 5, '', 1, 0); // fila 6
            $pdf->Cell(10, 5, '', 1, 0); // fila 6
            $pdf->Cell(15, 5, '', 1, 1); // fila 6
            $pdf->Cell(51, 5, '', 1, 0); // fila 7
            $pdf->Cell(53, 5, '', 1, 0); // fila 7
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
        if(isset($params['diagnosticos']) && is_array($params['diagnosticos'])) {
            $diagnosticos_paciente = $params['diagnosticos'];
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

        $pdf->Cell(50, 3, utf8_decode('Hipertensión arterial (I10)'), 0, 0); // diagnostico 1
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $checked = $tiene_diagnostico('Hipertensión arterial (I10)', $diagnosticos_paciente);
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

        $apellido_y_nombre_medico = $params['medico_prescriptor']['nombre_y_apellido'] ?? ''; // nombre y apellido del médico prescriptor
        $especialidad_medico = $params['medico_prescriptor']['especialidad'] ?? ''; // especialidad del médico prescriptor
        $matricula_medico = $params['medico_prescriptor']['matricula'] ?? ''; // matrícula del médico prescriptor
        $telefono_medico = $params['medico_prescriptor']['telefono'] ?? ''; // teléfono del médico prescriptor
        $institucion = $params['institucion'] ?? ''; // institución del médico prescriptor
        $fecha = $params['fecha_emision'] ?? '     /     /      '; // fecha de emisión del formulario
        $pdf->Cell(180, 7, utf8_decode('Nombre y Apellido: '.$apellido_y_nombre_medico), 1, 2); // nombre y apellido del médico prescriptor
        $pdf->Cell(130, 7, utf8_decode('Especialidad: '.$especialidad_medico), 1, 0); // especialidad del médico prescriptor
        $pdf->Cell(50, 7, utf8_decode('Matrícula: '.$matricula_medico), 1, 1); // matrícula del médico prescriptor
        $pdf->Cell(60, 7, utf8_decode('Teléfono: '.$telefono_medico), 1, 0); // teléfono del médico prescriptor
        $pdf->Cell(120, 7, utf8_decode('Institución: '.$institucion), 1, 1); // institución del médico prescriptor

        $pdf->SetX($pdf->GetX() + 10);
        $pdf->SetY(232);
        $pdf->Cell(140, 10, utf8_decode('Firma y sello del médico: '), 0, 0); // firma y sello del médico prescriptor
        $pdf->Cell(40, 10, utf8_decode('Fecha: ' . $fecha), 0, 2); // fecha
        if(isset($params['medico_prescriptor']['nro_doc']) && $params['medico_prescriptor']['nro_doc'] != '') {
            if(file_exists(storage_path('app/public/uploads/firma_medicos').'/'.$params['medico_prescriptor']['nro_doc'].'.png')){
                $pdf->Image(storage_path('app/public/uploads/firma_medicos').'/'.$params['medico_prescriptor']['nro_doc'].'.png', 50, $pdf->GetY() - 15, 30, 0, 'PNG');
                // sello
                $pdf->SetX(50);
                $pdf->SetY(240);
                $pdf->SetFont($font, 'B', 6);
                $pdf->Cell(40, 2, utf8_decode(''), 0, 0, 'L');
                $pdf->Cell(20, 2, utf8_decode($params['medico_prescriptor']['nombre_y_apellido']), 0, 2, 'C');
                $pdf->Cell(20, 2, utf8_decode($params['medico_prescriptor']['matricula']), 0, 2, 'C');
                $pdf->Cell(20, 2, utf8_decode($params['medico_prescriptor']['especialidad']), 0, 2, 'C');
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

        return $pdf;
    }
}
