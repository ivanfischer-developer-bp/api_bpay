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
        // variables
        $fecha_emision = $params['paciente'] != null ? Carbon::parse($params['paciente']['fecha_emision'])->format('d/m/Y') : '';
        $apellido_y_nombre = $params['paciente'] != null ? $params['paciente']['apellido_y_nombre'] : '';
        $nro_afiliado = $params['paciente'] != null ? $params['paciente']['nro_afiliado'] : '';
        $nro_doc = $params['paciente'] != null ? $params['paciente']['nro_doc'] : '';
        $fecha_nacimiento = $params['paciente'] != null ? Carbon::parse($params['paciente']['fec_nac'])->format('d/m/Y') : '';
        $edad = $params['paciente'] != null ? $params['paciente']['edad'] : '';
        $domicilio = ($params['paciente'] != null && !empty($params['paciente']['domicilio'])) ? 'Domicilio: '.substr($params['paciente']['domicilio'], 0, 30).'...' : 'Domicilio: __________________________';
        $localidad = ($params['paciente'] != null && !empty($params['paciente']['localidad'])) ? 'Localidad: '.substr($params['paciente']['localidad'], 0, 30).'...' : 'Localidad: __________________________';
        $provincia = ($params['paciente'] != null && !empty($params['paciente']['provincia'])) ? 'Provincia: '.$params['paciente']['provincia'] : 'Provincia: _____________________';
        $telefono = ($params['paciente'] != null && !empty($params['paciente']['telefono'])) ? 'Teléfono: '.$params['paciente']['telefono'] : 'Teléfono: (         ) - ___________________';
        $celular = ($params['paciente'] != null && !empty($params['paciente']['celular'])) ? 'Celular: '.$params['paciente']['celular'] : 'Celular: (         ) - _____________________';
        $email = ($params['paciente'] != null && !empty($params['paciente']['email'])) ? 'Email: '.$params['paciente']['email'] : 'Email: ________________________';
        $otro_contacto = ($params['paciente'] != null && !empty($params['paciente']['otro_contacto'])) ? 'Otro contacto: '.$params['paciente']['otro_contacto'] : 'Otro contacto: ______________________________________';
        $parentesco = ($params['paciente'] != null && !empty($params['paciente']['parentesco'])) ? 'Parentesco: '.$params['paciente']['parentesco'] : 'Parentesco: _________________________';
        $domicilio2 = ($params['paciente'] != null && !empty($params['paciente']['domicilio2'])) ? 'Domicilio: '.substr($params['paciente']['domicilio2'], 0, 45).'...' : 'Domicilio: _________________________________________';
        $telefono2 = ($params['paciente'] != null && !empty($params['paciente']['telefono2'])) ? 'Teléfono: '.$params['paciente']['telefono2'] : 'Teléfono: (         ) - ____________________';
        $celular2 = ($params['paciente'] != null && !empty($params['paciente']['celular2'])) ? 'Celular: '.$params['paciente']['celular2'] : 'Celular: (         ) - _______________';
        
        $sexo = null;
        if($params['paciente'] != null && $params['paciente']['sexo']){
            switch ($params['paciente']['sexo']) {
                case 'masculino':
                    $sexo = 'M';
                    break;
                case 'Masculino':
                    $sexo = 'M';
                    break;
                case 'MASCULINO':
                    $sexo = 'M';
                    break;
                case 'M':
                    $sexo = 'M';
                    break;
                case 'femenino':
                    $sexo = 'F';
                    break;
                case 'Femenino':
                    $sexo = 'F';
                    break;
                case 'FEMENINO':
                    $sexo = 'F';
                    break;
                case 'F':
                    $sexo = 'F';
                    break;
                case 'no binario':
                    $sexo = 'O';
                    break;
                case 'No Binario':
                    $sexo = 'O';
                    break;
                case 'NO BINARIO':
                    $sexo = 'O';
                    break;
                case 'OTRO':
                    $sexo = 'O';
                    break;
                case 'Otro':
                    $sexo = 'O';
                    break;
                case 'otro':
                    $sexo = 'O';
                    break;
                case 'O':
                    $sexo = 'O';
                    break;
                case 'X':
                    $sexo = 'O';
                    break;
                default:
                    $sexo = null;
            }
        }
        //  font setup
        $font = 'Arial';
        //  -------------------
        $pdf->AddPage('portrait');
        $pdf->SetMargins(5, 5);
        $pdf->Cell(200, 0, '', 0, 1); // espacio en blanco para ubicar el texto
        // logo 
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->Rect($x, $y - 5, 30, 10);
        $pdf->SetXY($x, $y);
        $pdf->Image(env('IMAGE_PATH').'/logo_osceara.png', 10, 5, 20, 0, 'PNG');
        $pdf->Rect($x + 30, $y -5, 150, 10);
        $pdf->Rect($x + 180, $y -5, 20, 10);

        $pdf->SetXY($x + 30, $y -5);
        $pdf->SetFont($font, '', 10);
        $pdf->Cell(150, 5, utf8_decode('EMPADRONAMIENTO RESOLUCIÓN 310/01 SSSalud Crónico'), 0, 0, 'C');
        $pdf->Cell(20, 10, utf8_decode('F.310.A'), 0, 1, 'C');
        $pdf->SetXY($x + 30, $y);
        $pdf->Cell(150, 5, utf8_decode('VÁLIDO POR 6 MESES'), 0, 1, 'C');
        $pdf->SetFont($font, '', 9);
        $pdf->SetXY($x + 65, $y);
        $pdf->Cell(170, 5, utf8_decode('Fec. Emisión: '.$fecha_emision), 0, 0, 'C');  // fecha de emisión

        $pdf->SetFont($font, '', 10);
        $pdf->Rect($x, $y + 7, 200, 35);
        $pdf->SetXY($x, $y +7);
        $pdf->Cell(65, 7, utf8_decode('1.- Afiliado: '.$apellido_y_nombre), 0, 0, 'L');  // afiliado
        $pdf->Cell(45, 7, utf8_decode('Documento: '.$nro_doc), 0, 0, 'L');              // documento
        $pdf->Cell(40, 7, utf8_decode('Fec. Nac.: '.$fecha_nacimiento), 0, 0, 'L');     // fecha de nacimiento

        $dibujar_checkbox = function ($x, $y, $checked = false) use ($pdf) {
            $pdf->Rect($x, $y, 4, 4);

            if ($checked) {
                $pdf->SetLineWidth(0.5);
                $pdf->Line($x + 0.4, $y + 1.7, $x + 1.2, $y + 2.5);
                $pdf->Line($x + 1.2, $y + 2.5, $x + 2.6, $y + 0.4);
                $pdf->SetLineWidth(0.2);
            }
        };

        $fila_sexo_x = $x + 148;
        $fila_sexo_y = $y + 7;
        $pdf->SetXY($fila_sexo_x, $fila_sexo_y);
        $pdf->SetFont($font, '', 9);

        $pdf->Cell(9, 7, utf8_decode('Sexo:'), 0, 0, 'L');  // sexo

        $pdf->Cell(8, 7, utf8_decode('Masc.'), 0, 0, 'L');
        $dibujar_checkbox($pdf->GetX() +2, $fila_sexo_y + 1, $sexo === 'M');
        $pdf->SetX($pdf->GetX() + 7);

        $pdf->Cell(8, 7, utf8_decode('Fem.'), 0, 0, 'L');
        $dibujar_checkbox($pdf->GetX() +1, $fila_sexo_y + 1, $sexo === 'F');
        $pdf->SetX($pdf->GetX() + 7);

        $pdf->Cell(8, 7, utf8_decode('Otro'), 0, 0, 'L');
        $dibujar_checkbox($pdf->GetX(), $fila_sexo_y + 1, $sexo === 'O');
        $pdf->Ln(5);

        $pdf->SetXY($x, $y + 13);
        $pdf->SetFont($font, '', 10);
        $pdf->Cell(70, 7, utf8_decode($domicilio), 0, 0, 'L'); // domicilio
        $pdf->Cell(70, 7, utf8_decode($localidad), 0, 0, 'L'); // localidad
        $pdf->Cell(60, 7, utf8_decode($provincia), 0, 1, 'L'); // provincia
        $pdf->Cell(70, 7, utf8_decode($telefono), 0, 0, 'L'); // telefono
        $pdf->Cell(70, 7, utf8_decode($celular), 0, 0, 'L'); // celular
        $pdf->Cell(60, 7, utf8_decode($email), 0, 1, 'L'); // email
        $pdf->Cell(100, 7, utf8_decode($otro_contacto), 0, 0, 'L'); // otro_contacto
        $pdf->Cell(100, 7, utf8_decode($domicilio2), 0, 1, 'L'); // domicilio2
        $pdf->Cell(70, 7, utf8_decode($parentesco), 0, 0, 'L'); // parentesco
        $pdf->Cell(70, 7, utf8_decode($telefono2), 0, 0, 'L'); // telefono2
        $pdf->Cell(60, 7, utf8_decode($celular2), 0, 1, 'L'); // celular2

        $pdf->Rect($x, $y + 42, 200, 21);
        $pdf->SetXY($x, $y + 42);
        $pdf->Cell(80, 7, utf8_decode('2.- DIAGNÓSTICOS POR ÓRGANOS/SISTEMAS'), 0, 0, 'L'); // diagnóstico
        $pdf->SetTextColor(100, 100, 100); 
        $pdf->Cell(120, 7, utf8_decode('(campo obligatorio)'), 0, 1, 'L'); // diagnóstico
        $pdf->SetTextColor(0, 0, 0); 










        return $pdf;
    }
}
