<?php

namespace App\Services\Pdf;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response as FacadeResponse;

use setasign\Fpdi\Fpdi;
use Carbon\Carbon;

class FormularioCronicosOscearaGeneratorPdf
{
    /**
     * Genera PDF del formulario de solicitud de medicamentos Resolución 310 (OSCEARA)
     * 
     * @param array $params Parámetros del formulario
     * @param array $extras Información adicional
     * @param object $user Usuario autenticado
     * @param object $controller Instancia del controlador
     * @return Fpdi
     */
    public function generar(Fpdi $pdf, array $params): Fpdi
    {
        $pdf->AddPage('portrait');
        $pdf->SetMargins(25, 5);
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf = $this->agregar_cabecera($pdf, $x, $y);
        
        $font = 'Arial';

        $pdf->SetTextColor(61, 112, 215);  
        $pdf->SetFont($font, '', 17);
        $pdf->SetXY($x, $y + 47);
        $pdf->Cell(180, 8, utf8_decode('MODALIDAD PARA AFILIADOS CON PATOLOGÍAS CRÓNICAS.'), 0, 2, 'L');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x, $y + 67);
        $pdf->SetFont($font, 'B', 12);
        $pdf->Cell(180, 8, utf8_decode('Estimado Doctor: '), 0, 2, 'L');
        
        $texto3 = 'Por la Presente, y con motivo de dar cumplimiento a la resolución 310/2004 (que establece un mayor porcentaje de cobertura para medicamentos de enfermedades crónicas) de la Superintendencia de Servicios de Salud; solicitamos tenga a bien, completar el formulario (se adjunta modelo) para registrar dichos casos en nuestro sistema de Autorizaciones y con ellos, establecer la cobertura según corresponda.';
        $pdf->SetXY($x, $y + 77);
        $pdf->SetFont($font, '', 10.5);
        $pdf->MultiCell(180, 5, utf8_decode($texto3), 0, 'J');

        $pdf->SetXY($x, $y + 102);
        $pdf->SetFont($font, 'B', 12);
        $pdf->Cell(180, 8, utf8_decode('Este formulario se confeccionará una vez al año.'), 0, 2, 'L');

        $texto4 = 'También se implementará un nuevo recetario para la prescripción de medicamentos con cobertura de hasta el 70%, el cual deberá completarse por triplicado (se llena el original y se calcan los siguientes) y salvo que Ud. considere lo contrario, servirá para la dispensa de medicamentos, por tres meses.';
        $pdf->SetXY($x, $y + 117);
        $pdf->SetFont($font, '', 10.5);
        $pdf->MultiCell(180, 5, utf8_decode($texto4), 0, 'J');

        $texto5 = 'Cada una de las hojas deberá tener firma original y sello. Comprendemos que todo esto significa un esfuerzo, pero sabemos que es para mayor beneficio de nuestros afiliados. Desde ya muchas gracias.';
        $pdf->SetXY($x, $y + 137);
        $pdf->SetFont($font, '', 10.5);
        $pdf->MultiCell(180, 5, utf8_decode($texto5), 0, 'J');
        $pdf->SetXY($x, $y + 147);
        $pdf->SetFont($font, 'B', 12);
        $pdf->Cell(180, 8, utf8_decode('Saludamos a Ud. cordialmente.'), 0, 2, 'L');


        $pdf = $this->agregar_pie($pdf, $x, $y);

        $pdf->AddPage('portrait');
        $pdf = $this->agregar_cabecera($pdf, $x, $y); // Agregar cabecera a la segunda página

        $pdf->SetFont($font, '', 10.5);

        $pdf->SetXY($x, $y + 40);
        $fecha_emision = ($params['paciente'] != null && !empty($params['paciente']['fecha_emision'])) ? Carbon::parse($params['paciente']['fecha_emision'])->format('d/m/Y') : '......./......./..............';
        $pdf->Cell(180, 8, utf8_decode('Fecha: '.$fecha_emision), 0, 2, 'L');

        $pdf->SetXY($x, $y + 47);
        $pdf->SetFillColor(158, 199, 235); 
        $pdf->SetDrawColor(158, 199, 235);
        $pdf->Rect($x, $y + 47, 180, 8, 'DF');
        $pdf->SetFont($font, '', 11);
        $pdf->Cell(180, 8, utf8_decode('Datos del afiliado'), 0, 2, 'L');

        $pdf->SetXY($x, $y + 57);
        $pdf->SetFont($font, '', 10.5);
        $nombre = ($params['paciente'] != null && !empty($params['paciente']['apellido_y_nombre'])) ? $params['paciente']['apellido_y_nombre'] : '..............................................................................................................................................';
        $pdf->Cell(180, 5, utf8_decode('Apellido y Nombre: '.$nombre), 0, 2, 'L');
        $numero_afiliado = ($params['paciente'] != null && !empty($params['paciente']['numero_afiliado'])) ? $params['paciente']['numero_afiliado'] : '............................';
        $pdf->Cell(50, 5, utf8_decode('Afiliado N°: '.$numero_afiliado), 0, 0, 'L');
        $plan = ($params['paciente'] != null && !empty($params['paciente']['n_plan'])) ? $params['paciente']['n_plan'] : '.........................................................';
        $pdf->Cell(70, 5, utf8_decode('Plan: '.$plan), 0, 0, 'L');
        $fec_ingreso = ($params['paciente'] != null && !empty($params['paciente']['fec_ingreso'])) ? Carbon::parse($params['paciente']['fec_ingreso'])->format('d/m/Y') : '...........................';
        $pdf->Cell(60, 5, utf8_decode('Fecha de Ingreso: '.$fec_ingreso), 0, 1, 'L');
        $pdf->SetX($x);
        $domicilio = ($params['paciente'] != null && !empty($params['paciente']['domicilio'])) ? $params['paciente']['domicilio'] : '.............................................................................................................................................................';
        $pdf->Cell(180, 5, utf8_decode('Domicilio: '.$domicilio), 0, 1, 'L');
        $pdf->SetX($x);
        $localidad = ($params['paciente'] != null && !empty($params['paciente']['localidad'])) ? $params['paciente']['localidad'] : '...............................';
        $pdf->Cell(50, 5, utf8_decode('Localidad: '.$localidad), 0, 0, 'L');
        $cp = ($params['paciente'] != null && !empty($params['paciente']['cod_postal'])) ? $params['paciente']['cod_postal'] : '..................';
        $pdf->Cell(30, 5, utf8_decode('C.P.: '.$cp), 0, 0, 'L');
        $email = ($params['paciente'] != null && !empty($params['paciente']['email'])) ? $params['paciente']['email'] : '...............................................................';
        $pdf->Cell(100, 5, utf8_decode('Email (part-laboral): '.$email), 0, 1, 'L');
        $pdf->SetX($x);
        $telefono = ($params['paciente'] != null && !empty($params['paciente']['telefono'])) ? $params['paciente']['telefono'] : '..............................................................';
        $pdf->Cell(90, 5, utf8_decode('Tel. Particular: '.$telefono), 0, 0, 'L');
        $telefono2 = ($params['paciente'] != null && !empty($params['paciente']['telefono2'])) ? $params['paciente']['telefono2'] : '.................................................................';
        $pdf->Cell(90, 5, utf8_decode('Tel. Laboral: '.$telefono2), 0, 1, 'L');

        $pdf->SetX($x);
        $pdf->SetFont($font, '', 10);
        $texto6 = 'Breve resumen de Historia Clínica (especificando las limitaciones de uso de otras drogas y tiempo de evolución)';
        $pdf->Cell(180, 8, utf8_decode($texto6), 0, 1, 'L');

        $pdf->SetX($x);
        $textoResumenHC = $params['paciente'] != null && !empty($params['paciente']['resumen_historia_clinica']) ? $params['paciente']['resumen_historia_clinica'] : '..............................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................';
        $pdf->MultiCell(180, 4, utf8_decode($textoResumenHC), 0, 'L'); // resumen de hitoria clinica del paciente

        $pdf->SetX($x);
        $pdf->SetFont($font, 'B', 10);
        $pdf->Cell(42, 8, utf8_decode('TRATAMIENTO ACTUAL'), 0, 0, 'L');
        $pdf->SetFont($font, '', 10);
        $pdf->Cell(138, 8, utf8_decode('(completar todos los datos)'), 0, 0, 'L');


        $pdf = $this->agregar_pie($pdf, $x, $y);

        return $pdf;
    }

    /**
     * Agrega la cabecera al PDF
     * @param Fpdi $pdf Instancia del PDF     
     * @return Fpdi PDF con la cabecera agregada
     */
    private function agregar_cabecera(Fpdi $pdf, float $x, float $y): Fpdi
    {
        //  font setup
        $font = 'Arial';
        
        $pdf->Cell(180, 1, '', 0, 2, 'L'); // espacio en blanco para iniciar textos
        $pdf->SetFont($font, '', 9);
        $texto1 = env('URL_FORMULARIO_CRONICOS') . " · " . env('EMAIL_FORMULARIO_CRONICOS');
        $pdf->Cell(150, 3, utf8_decode($texto1), 0, 2, 'L'); 
        $pdf->SetFont($font, '', 8);
        $texto2 = env('DIRECCION_FORMULARIO_CRONICOS') . " · " . env('TELEFONO_FORMULARIO_CRONICOS');
        $pdf->Cell(150, 3, utf8_decode($texto2), 0, 2, 'L'); 
        
        // logo 
        $pdf->SetXY($x + 150, $y);
        $pdf->Image(env('IMAGE_PATH').'/logo_osceara.png', 160, 5, 30, 0, 'PNG');
        $pdf->SetLineWidth(0.5);
        $pdf->Line(12, 18, 155, 18);
        $pdf->SetLineWidth(0.2);

        $pdf->SetXY($x, $y + 12);
        $pdf->SetTextColor(61, 112, 215);  
        $pdf->SetFont($font, '', 20);
        $pdf->Cell(150, 9, utf8_decode('SOLICITUD DE MEDICAMENTOS POR'), 0, 2, 'L');
        $pdf->Cell(150, 9, utf8_decode('PATOLOGÍAS CRÓNICAS PREVALENTES.'), 0, 2, 'L');
        $pdf->SetFont($font, 'B', 20);
        $pdf->Cell(150, 9, utf8_decode('RESOLUCIÓN SSS 310/2004'), 0, 2, 'L');
        $pdf->SetXY($x, $y + 42);
        $pdf->SetTextColor(0, 0, 0);  
        $pdf->SetFont($font, '', 18);

        return $pdf;
    }

    private function agregar_pie(Fpdi $pdf, float $x, float $y): Fpdi
    {
        $pageWidth = method_exists($pdf, 'GetPageWidth') ? $pdf->GetPageWidth() : 210;
        $pageHeight = method_exists($pdf, 'GetPageHeight') ? $pdf->GetPageHeight() : 297;
        $footerText = utf8_decode('AUDITORÍA MÉDICA');
        $leftMargin = 20;
        $rightMargin = $pageWidth - 20;
        $lineY = $pageHeight - 10;

        $pdf->SetTextColor(61, 112, 215);  
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 11);
        $textWidth = $pdf->GetStringWidth($footerText) + 8;
        $textX = ($pageWidth - $textWidth) / 2;

        $pdf->SetLineWidth(0.5);
        $pdf->Line($leftMargin, $lineY, $textX - 3, $lineY);
        $pdf->Line($textX + $textWidth + 3, $lineY, $rightMargin, $lineY);

        $pdf->Text($textX + 4, $lineY + 1.2, $footerText);
        $pdf->SetLineWidth(0.2);

        return $pdf;
    }
}