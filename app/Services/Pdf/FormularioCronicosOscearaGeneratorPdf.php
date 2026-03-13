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
        // variables generales
        $lorem_ipsum_400 = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa.';
        $fecha_emision = '......./......./..............';
        $nombre = '..............................................................................................................................................';
        $numero_afiliado = '............................';
        $plan = '.........................................................';
        $fec_ingreso = '...........................';
        $domicilio = '.............................................................................................................................................................';
        $localidad = '...............................';
        $cp = '..................';
        $email = '...............................................................';
        $telefono = '..............................................................';
        $telefono2 = '.................................................................';
        $textoResumenHC = '..............................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................';
        $nombreMedico = '..............................................................................................................................................';
        $domicilioMedico = '...................................................................................................';
        $localidadMedico = '........................................';
        $telefonoMedico = '..................................................';
        $horarioConsulta = '.........................................................................';

        if($params['paciente'] != null){
            $fecha_emision = !empty($params['fecha_emision']) ? $params['fecha_emision'] : '......./......./..............';
            $nombre = !empty($params['paciente']['apellido_y_nombre']) ? $params['paciente']['apellido_y_nombre'] : '..............................................................................................................................................';
            $numero_afiliado = !empty($params['paciente']['nro_afiliado']) ? $params['paciente']['nro_afiliado'] : '............................';
            $plan = !empty($params['paciente']['n_plan']) ? $params['paciente']['n_plan'] : '.........................................................';
            $fec_ingreso = !empty($params['paciente']['fec_ingreso']) ? $params['paciente']['fec_ingreso'] : '...........................';
            $domicilio = !empty($params['paciente']['domicilio']) ? $params['paciente']['domicilio'] : '.............................................................................................................................................................';
            $localidad = !empty($params['paciente']['localidad']) ? $params['paciente']['localidad'] : '...............................';
            $cp = !empty($params['paciente']['cod_postal']) ? $params['paciente']['cod_postal'] : '..................';
            $email = !empty($params['paciente']['email']) ? $params['paciente']['email'] : '...............................................................';
            $telefono = !empty($params['paciente']['telefono']) ? $params['paciente']['telefono'] : '..............................................................';
            $telefono2 = !empty($params['paciente']['telefono2']) ? $params['paciente']['telefono2'] : '.................................................................';
            $textoResumenHC = !empty($params['resumen_historia_clinica']) ? $params['resumen_historia_clinica'] : '..............................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................';
            $medicoPrescriptor = $params['medico_prescriptor'] ?? null;
            if (is_array($medicoPrescriptor) && !empty($medicoPrescriptor)) {
                $nombreMedico = !empty($medicoPrescriptor['nombre_y_apellido']) ? $medicoPrescriptor['nombre_y_apellido'] : '..............................................................................................................................................';
                $domicilioMedico = !empty($medicoPrescriptor['domicilio']) ? $medicoPrescriptor['domicilio'] : '...................................................................................................';
                $localidadMedico = !empty($medicoPrescriptor['localidad']) ? $medicoPrescriptor['localidad'] : '........................................';
                $telefonoMedico = !empty($medicoPrescriptor['telefono']) ? $medicoPrescriptor['telefono'] : '..................................................';
                $horarioConsulta = !empty($medicoPrescriptor['horario_consulta']) ? $medicoPrescriptor['horario_consulta'] : '.........................................................................';
            }
            
        }
        $texto3 = 'Por la Presente, y con motivo de dar cumplimiento a la resolución 310/2004 (que establece un mayor porcentaje de cobertura para medicamentos de enfermedades crónicas) de la Superintendencia de Servicios de Salud; solicitamos tenga a bien, completar el formulario (se adjunta modelo) para registrar dichos casos en nuestro sistema de Autorizaciones y con ellos, establecer la cobertura según corresponda.';
        $texto4 = 'También se implementará un nuevo recetario para la prescripción de medicamentos con cobertura de hasta el 70%, el cual deberá completarse por triplicado (se llena el original y se calcan los siguientes) y salvo que Ud. considere lo contrario, servirá para la dispensa de medicamentos, por tres meses.';
        $texto5 = 'Cada una de las hojas deberá tener firma original y sello. Comprendemos que todo esto significa un esfuerzo, pero sabemos que es para mayor beneficio de nuestros afiliados. Desde ya muchas gracias.';
        $texto6 = 'Breve resumen de Historia Clínica (especificando las limitaciones de uso de otras drogas y tiempo de evolución)';
    
        $leftMargin = 15;
        $topMargin = 5;

        $pdf->AddPage('portrait');
        $pdf->SetMargins($leftMargin, $topMargin);
        $pdf->SetXY($leftMargin, $topMargin);
        $x = $leftMargin;
        $y = $topMargin;
        $font = 'Arial';
        $pdf = $this->agregar_cabecera($pdf, $x, $y);

        $pdf->SetTextColor(61, 112, 215);
        $pdf->SetFont($font, '', 17);
        $pdf->SetXY($x, $y + 47);
        $pdf->Cell(180, 8, utf8_decode('MODALIDAD PARA AFILIADOS CON PATOLOGÍAS CRÓNICAS.'), 0, 2, 'L');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x, $y + 67);
        $pdf->SetFont($font, 'B', 12);
        $pdf->Cell(180, 8, utf8_decode('Estimado Doctor: '), 0, 2, 'L');

        $pdf->SetXY($x, $y + 77);
        $pdf->SetFont($font, '', 10.5);
        $pdf->MultiCell(180, 5, utf8_decode($texto3), 0, 'J');

        $pdf->SetXY($x, $y + 102);
        $pdf->SetFont($font, 'B', 12);
        $pdf->Cell(180, 8, utf8_decode('Este formulario se confeccionará una vez al año.'), 0, 2, 'L');

        $pdf->SetXY($x, $y + 117);
        $pdf->SetFont($font, '', 10.5);
        $pdf->MultiCell(180, 5, utf8_decode($texto4), 0, 'J');

        $pdf->SetXY($x, $y + 137);
        $pdf->SetFont($font, '', 10.5);
        $pdf->MultiCell(180, 5, utf8_decode($texto5), 0, 'J');
        $pdf->SetXY($x, $y + 147);
        $pdf->SetFont($font, 'B', 12);
        $pdf->Cell(180, 8, utf8_decode('Saludamos a Ud. cordialmente.'), 0, 2, 'L');

        $pdf = $this->agregar_pie($pdf, $x, $y);

        $pdf->AddPage('portrait');
        $pdf = $this->agregar_cabecera($pdf, $x, $y);

        $pdf->SetFont($font, '', 10.5);
        $pdf->SetXY($x, $y + 40);
        $pdf->Cell(180, 8, utf8_decode('Fecha: ' . $fecha_emision), 0, 2, 'L');

        $pdf->SetXY($x, $y + 47);
        $pdf->SetFillColor(158, 199, 235);
        $pdf->SetDrawColor(158, 199, 235);
        $pdf->Rect($x, $y + 47, 180, 8, 'DF');
        $pdf->SetFont($font, '', 11);
        $pdf->Cell(180, 8, utf8_decode('Datos del afiliado'), 0, 2, 'L');

        $pdf->SetXY($x, $y + 57);
        $pdf->SetFont($font, '', 10.5);
        $pdf->Cell(180, 5, utf8_decode('Apellido y Nombre: ' . $nombre), 0, 2, 'L');
        $pdf->Cell(50, 5, utf8_decode('Afiliado N°: ' . $numero_afiliado), 0, 0, 'L');
        $pdf->Cell(70, 5, utf8_decode('Plan: ' . $plan), 0, 0, 'L');
        $pdf->Cell(60, 5, utf8_decode('Fecha de Ingreso: ' . $fec_ingreso), 0, 1, 'L');
        $pdf->SetX($x);
        $pdf->Cell(180, 5, utf8_decode('Domicilio: ' . $domicilio), 0, 1, 'L');
        $pdf->SetX($x);
        $pdf->Cell(50, 5, utf8_decode('Localidad: ' . $localidad), 0, 0, 'L');
        $pdf->Cell(30, 5, utf8_decode('C.P.: ' . $cp), 0, 0, 'L');
        $pdf->Cell(100, 5, utf8_decode('Email (part-laboral): ' . $email), 0, 1, 'L');
        $pdf->SetX($x);
        $pdf->Cell(90, 5, utf8_decode('Tel. Particular: ' . $telefono), 0, 0, 'L');
        $pdf->Cell(90, 5, utf8_decode('Tel. Laboral: ' . $telefono2), 0, 1, 'L');

        $pdf->SetX($x);
        $pdf->SetFont($font, 'B', 9);
        $pdf->Cell(180, 8, utf8_decode($texto6), 0, 1, 'L');

        $pdf->SetFont($font, '', 10);
        $pdf->SetX($x);
        $pdf->MultiCell(180, 4, utf8_decode($textoResumenHC), 0, 'L');

        $pdf->SetX($x);
        $pdf->SetY($y + 115);
        $pdf->SetFont($font, 'B', 10);
        $pdf->Cell(42, 8, utf8_decode('TRATAMIENTO ACTUAL'), 0, 0, 'L');
        $pdf->SetFont($font, '', 10);
        $pdf->Cell(138, 8, utf8_decode('(completar todos los datos)'), 0, 2, 'L');

        $pdf->SetFont($font, '', 8);
        $pdf->SetX($x);

        $anchosColumnas = [50, 35, 40, 15, 20, 20];
        $cabeceras = [
            'Droga (Genérico)',
            'Marca Comercial',
            'Unidad Posológica',
            'Comp./ dia',
            'Tiempo Prescripto en meses',
            'Dictamen Audit.'
        ];

        $contarLineasTexto = function (string $texto, float $anchoDisponible) use ($pdf): int {
            $texto = str_replace("\r", '', $texto);
            if ($texto === '') {
                return 1;
            }

            $lineas = 0;
            foreach (explode("\n", $texto) as $linea) {
                if ($linea === '') {
                    $lineas++;
                    continue;
                }

                $actual = '';
                $palabras = preg_split('/\s+/', trim($linea)) ?: [];
                foreach ($palabras as $palabra) {
                    if ($palabra === '') {
                        continue;
                    }

                    $candidato = $actual === '' ? $palabra : $actual . ' ' . $palabra;
                    if ($pdf->GetStringWidth($candidato) <= $anchoDisponible) {
                        $actual = $candidato;
                        continue;
                    }

                    if ($actual !== '') {
                        $lineas++;
                    }

                    if ($pdf->GetStringWidth($palabra) <= $anchoDisponible) {
                        $actual = $palabra;
                        continue;
                    }

                    $segmento = '';
                    $longitud = strlen($palabra);
                    for ($i = 0; $i < $longitud; $i++) {
                        $caracter = $palabra[$i];
                        if ($pdf->GetStringWidth($segmento . $caracter) > $anchoDisponible) {
                            $lineas++;
                            $segmento = $caracter;
                        } else {
                            $segmento .= $caracter;
                        }
                    }
                    $actual = $segmento;
                }

                if ($actual !== '') {
                    $lineas++;
                }
            }

            return max(1, $lineas);
        };

        $dibujarFilaTabla = function (array $columnas, bool $esCabecera = false, float $altoExtra = 0) use ($pdf, $x, $font, $anchosColumnas, $contarLineasTexto): void {
            $altoLinea = 3.5;
            $pdf->SetFont($font, '', $esCabecera ? 9 : 9);

            $inicioY = $pdf->GetY();
            $altoFila = $altoLinea + $altoExtra;
            $altosTexto = [];

            foreach ($columnas as $indice => $columna) {
                $textoCelda = utf8_decode((string) $columna);
                $lineas = $contarLineasTexto($textoCelda, $anchosColumnas[$indice] - 2);
                $altosTexto[$indice] = $lineas * $altoLinea;
                $altoFila = max($altoFila, $altosTexto[$indice]);
            }

            $actualX = $x;
            $pdf->SetDrawColor(90, 90, 90);
            foreach ($anchosColumnas as $anchoColumna) {
                $pdf->Rect($actualX, $inicioY, $anchoColumna, $altoFila);
                $actualX += $anchoColumna;
            }

            $actualX = $x;
            foreach ($columnas as $indice => $columna) {
                $textoCelda = utf8_decode((string) $columna);
                $alineacion = $esCabecera ? 'C' : 'L';
                $offsetY = max(0, ($altoFila - $altosTexto[$indice]) / 2);
                $paddingX = $esCabecera ? 0 : 1;
                $anchoTexto = $esCabecera ? $anchosColumnas[$indice] : $anchosColumnas[$indice] - 2;

                $pdf->SetXY($actualX + $paddingX, $inicioY + $offsetY);
                $pdf->MultiCell($anchoTexto, $altoLinea, $textoCelda, 0, $alineacion);
                $actualX += $anchosColumnas[$indice];
            }

            $pdf->SetXY($x, $inicioY + $altoFila);
        };

        $dibujarFilaTabla($cabeceras, true);

        $medicamentos = [];
        if (isset($params['medicamentos']) && is_array($params['medicamentos'])) {
            $medicamentos = $params['medicamentos'];
        }

        if (count($medicamentos) > 0) {
            foreach (array_slice($medicamentos, 0, 7) as $medicamento) {
                $drogaGenerico = $medicamento['principio_activo'] ?? $medicamento['droga_generico'] ?? $medicamento['droga'] ?? '';
                $marcaComercial = $medicamento['marca_comercial'] ?? '';
                $unidadPosologica = $medicamento['unidad_posologica'] ?? '';
                $comprimidosDia = $medicamento['comprimidos_x_dia'] ?? $medicamento['comp_dia'] ?? '';
                $tiempoPrescriptoMeses = $medicamento['tiempo_prescripto_meses'] ?? $medicamento['tiempo_prescripto'] ?? $medicamento['meses'] ?? '';
                $dictamenAudit = $medicamento['dictamen_auditoria'] ?? $medicamento['dictamen_audit'] ?? $medicamento['dictamen'] ?? '';

                $dibujarFilaTabla([
                    $drogaGenerico,
                    $marcaComercial,
                    $unidadPosologica,
                    $comprimidosDia,
                    $tiempoPrescriptoMeses,
                    $dictamenAudit,
                ], false, 2);
            }
        } else {
            for ($i = 0; $i < 7; $i++) {
                $dibujarFilaTabla(['', '', '', '', '', ''], false, 2);
            }
        }

        $pdf->SetFillColor(158, 199, 235);
        $pdf->SetDrawColor(158, 199, 235);
        $pdf->Rect($x, $pdf->GetY() + 2, 180, 8, 'DF');
        $pdf->SetXY($x, $pdf->GetY() + 2);
        $pdf->SetFont($font, '', 11);
        $pdf->Cell(180, 8, utf8_decode('Diagnóstico'), 0, 2, 'L');
        $pdf->SetXY($x, $pdf->GetY() + 2);

        $diagnosticos = [
            'Hipertensión arterial',
            'Gota',
            'Enfermedad coronaria',
            'Insuficiencia cardíaca',
            'Arritmia crónica',
            'Coagulopatías',
            'Anticoncepción',
            'EPOC',
            'Dislipemias',
            'Hipotiroidismo',
            'Hipertiroidismo',
            'Colitis ulcerosa/Crohn',
            'Epilepsia',
            'Artritis reumatoidea, otras A.R.',
            'Asma bronquial',
            'Glaucoma',
            'Enf. Extrapiramidal',
            'Enf. Psiquiatría, mod. conducta',
            'Sindrome Metabólico',
            'Otros'
        ];

        $diagnosticosPaciente = [];
        if (isset($params['diagnosticos']) && is_array($params['diagnosticos'])) {
            $diagnosticosPaciente = $params['diagnosticos'];
        } 

        $dibujarCheckbox = function (Fpdi $pdf, float $xCheck, float $yCheck, bool $checked = false): void {
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetLineWidth(0.3);
            $pdf->Rect($xCheck, $yCheck, 4, 4);

            if ($checked) {
                $pdf->SetLineWidth(0.8);
                $pdf->Line($xCheck + 0.7, $yCheck + 2, $xCheck + 1.7, $yCheck + 3.1);
                $pdf->Line($xCheck + 1.7, $yCheck + 3.1, $xCheck + 3.7, $yCheck + 0.5);
                $pdf->SetLineWidth(0.3);
            }
        };

        $extraerTextoDiagnostico = function ($diagnostico) use (&$extraerTextoDiagnostico): ?string {
            if (is_string($diagnostico)) {
                $texto = trim($diagnostico);
                return $texto !== '' ? $texto : null;
            }

            if (is_array($diagnostico)) {
                foreach (['diagnostico', 'descripcion', 'nombre', 'label', 'text', 'value'] as $clave) {
                    if (!array_key_exists($clave, $diagnostico)) {
                        continue;
                    }

                    $texto = $extraerTextoDiagnostico($diagnostico[$clave]);
                    if ($texto !== null) {
                        return $texto;
                    }
                }
            }

            if (is_object($diagnostico)) {
                return $extraerTextoDiagnostico((array) $diagnostico);
            }

            return null;
        };

        $diagnosticosPacienteTexto = [];
        foreach ($diagnosticosPaciente as $diagnosticoPaciente) {
            $textoDiagnostico = $extraerTextoDiagnostico($diagnosticoPaciente);
            if ($textoDiagnostico !== null) {
                $diagnosticosPacienteTexto[] = $textoDiagnostico;
            }
        }
        $diagnosticosPacienteTexto = array_values(array_unique($diagnosticosPacienteTexto));

        $normalizarTexto = function (string $texto): string {
            $texto = trim($texto);
            $texto = preg_replace('/\([^)]*\)/u', ' ', $texto) ?? $texto;
            $texto = strtr($texto, [
                'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
                'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n',
            ]);
            $texto = function_exists('mb_strtolower') ? mb_strtolower($texto, 'UTF-8') : strtolower($texto);
            $texto = preg_replace('/[^a-z0-9]+/u', ' ', $texto) ?? $texto;
            $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

            return trim($texto);
        };

        $tieneDiagnostico = function (string $diagnosticoTexto, array $diagnosticosArray) use ($normalizarTexto): bool {
            $diagnosticoBuscado = $normalizarTexto($diagnosticoTexto);
            foreach ($diagnosticosArray as $diagnosticoPaciente) {
                if (!is_string($diagnosticoPaciente)) {
                    continue;
                }

                $diagnosticoPacienteNormalizado = $normalizarTexto($diagnosticoPaciente);
                if ($diagnosticoPacienteNormalizado === $diagnosticoBuscado) {
                    return true;
                }

                if ($diagnosticoBuscado !== '' && (str_contains($diagnosticoPacienteNormalizado, $diagnosticoBuscado) || str_contains($diagnosticoBuscado, $diagnosticoPacienteNormalizado))) {
                    return true;
                }
            }

            return false;
        };

        $pdf->SetFont($font, '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetDrawColor(0, 0, 0);

        $otrosSeleccionado = $tieneDiagnostico('Otros', $diagnosticosPacienteTexto);
        $diagnosticosSinMatch = [];
        foreach ($diagnosticosPacienteTexto as $diagnosticoPaciente) {
            $textoPaciente = trim($diagnosticoPaciente);
            if ($normalizarTexto($textoPaciente) === 'otros') {
                continue;
            }

            if (!$tieneDiagnostico($textoPaciente, $diagnosticos)) {
                $diagnosticosSinMatch[] = $textoPaciente;
            }
        }
        $diagnosticosSinMatch = array_values(array_unique($diagnosticosSinMatch));
        $textoOtrosDetalle = implode(', ', $diagnosticosSinMatch);

        $construirLineaPuntos = function (string $prefijo, float $anchoDisponible) use ($pdf): string {
            $texto = $prefijo;
            while ($pdf->GetStringWidth($texto . '.') <= $anchoDisponible) {
                $texto .= '.';
            }

            return $texto;
        };

        $columnas = 3;
        $anchoColumna = 60;
        $altoLineaDiag = 3.5;
        $anchoTextoDiag = 54;

        foreach (array_chunk($diagnosticos, $columnas) as $filaDiagnosticos) {
            $yFila = $pdf->GetY();
            $altoFilaDiag = 4;
            $altosTexto = [];
            $textosFila = [];
            $anchosTextoFila = [];

            foreach ($filaDiagnosticos as $indice => $diagnosticoTexto) {
                $anchoDisponible = $anchoTextoDiag;
                $textoDiag = $diagnosticoTexto;

                if ($diagnosticoTexto === 'Otros') {
                    $columnasRestantes = max(1, $columnas - $indice);
                    $anchoDisponible = ($anchoColumna * $columnasRestantes) - 6;

                    if ($otrosSeleccionado) {
                        $textoDiag = 'Otros: ' . $textoOtrosDetalle;
                    } else {
                        $textoDiag = $construirLineaPuntos('Otros: ', $anchoDisponible);
                    }
                }

                $textoDiagDecodificado = utf8_decode($textoDiag);
                $lineasDiag = $contarLineasTexto($textoDiagDecodificado, $anchoDisponible);
                $altosTexto[$indice] = $lineasDiag * $altoLineaDiag;
                $textosFila[$indice] = $textoDiagDecodificado;
                $anchosTextoFila[$indice] = $anchoDisponible;
                $altoFilaDiag = max($altoFilaDiag, $altosTexto[$indice]);
            }

            foreach ($filaDiagnosticos as $indice => $diagnosticoTexto) {
                $xColumna = $x + ($indice * $anchoColumna);
                $checked = !empty($diagnosticosPacienteTexto) && $tieneDiagnostico($diagnosticoTexto, $diagnosticosPacienteTexto);
                $yCheckbox = $yFila + (($altoFilaDiag - 4) / 2);
                $dibujarCheckbox($pdf, $xColumna, $yCheckbox, $checked);

                $altoTextoDiag = $altosTexto[$indice] ?? $altoLineaDiag;
                $yTexto = $yFila + (($altoFilaDiag - $altoTextoDiag) / 2);
                $pdf->SetXY($xColumna + 6, $yTexto);
                $pdf->MultiCell($anchosTextoFila[$indice], $altoLineaDiag, $textosFila[$indice], 0, 'L');
            }

            $pdf->SetXY($x, $yFila + $altoFilaDiag + 1);
        }

        $pdf->SetFillColor(158, 199, 235);
        $pdf->SetDrawColor(158, 199, 235);
        $pdf->Rect($x, $pdf->GetY() + 2, 180, 8, 'DF');
        $pdf->SetXY($x, $pdf->GetY() + 2);
        $pdf->SetFont($font, '', 11);
        $pdf->Cell(180, 8, utf8_decode('Datos del Médico Tratante'), 0, 2, 'L');
        $pdf->SetXY($x, $pdf->GetY() + 2);

        $pdf->SetFont($font, '', 10.5);
        $pdf->Cell(180, 5, utf8_decode('Apellido y Nombre: ' . $nombreMedico), 0, 2, 'L');
        $pdf->SetX($x);
        $pdf->Cell(120, 5, utf8_decode('Domicilio: ' . $domicilioMedico), 0, 0, 'L');
        $pdf->Cell(80, 5, utf8_decode('Localidad: ' . $localidadMedico), 0, 1, 'L');
        $pdf->SetX($x);
        $pdf->Cell(70, 5, utf8_decode('Teléfono: ' . $telefonoMedico), 0, 0, 'L');
        $pdf->Cell(130, 5, utf8_decode('Horario de consulta: ' . $horarioConsulta), 0, 1, 'L');

        // firmas
        $pdf->SetFont($font, 'B', 9);
        $pdf->SetXY($x, 265);
        $pdf->Cell(60, 5, utf8_decode('.....................................'), 0, 0, 'C');
        $pdf->Cell(60, 5, utf8_decode('.....................................'), 0, 0, 'C');  
        $pdf->Cell(60, 5, utf8_decode('.....................................'), 0, 1, 'C');  
        $pdf->SetX($x);
        $pdf->Cell(60, 5, utf8_decode('FIRMA Y SELLO'), 0, 0, 'C');
        $pdf->Cell(60, 5, utf8_decode('FECHA'), 0, 0, 'C');  
        $pdf->Cell(60, 5, utf8_decode('VIGENCIA HASTA'), 0, 1, 'C');  



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
        // variables
        $texto1 = env('URL_FORMULARIO_CRONICOS') . " · " . env('EMAIL_FORMULARIO_CRONICOS');
        $texto2 = env('DIRECCION_FORMULARIO_CRONICOS') . " · " . env('TELEFONO_FORMULARIO_CRONICOS');
        //  font setup
        $font = 'Arial';

        $pdf->SetTextColor(0, 0, 0); 
        $pdf->SetXY($x, $y);

        $pdf->Cell(180, 1, '', 0, 2, 'L'); // espacio en blanco para iniciar textos
        $pdf->SetFont($font, '', 9);
        $pdf->Cell(150, 3, utf8_decode($texto1), 0, 2, 'L'); 
        $pdf->SetFont($font, '', 8);
        $pdf->Cell(150, 3, utf8_decode($texto2), 0, 2, 'L'); 
        
        // logo 
        $pdf->SetXY($x + 150, $y);
        $pdf->Image(env('IMAGE_PATH').'/logo_osceara.png', 160, 5, 30, 0, 'PNG');
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, 15, 155, 15);
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

        // Center footer against the main 180mm content block used by this form.
        $contentLeft = $x;
        $contentRight = $x + 180;
        if ($contentRight > $pageWidth) {
            $contentLeft = 15;
            $contentRight = $pageWidth - 15;
        }

        $lineY = $pageHeight - 10;

        $pdf->SetTextColor(61, 112, 215);  
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 11);

        $textWidth = $pdf->GetStringWidth($footerText);
        $textX = $contentLeft + (($contentRight - $contentLeft - $textWidth) / 2);
        $gap = 3;

        $pdf->SetLineWidth(0.5);
        $pdf->Line($contentLeft, $lineY, $textX - $gap, $lineY);
        $pdf->Line($textX + $textWidth + $gap, $lineY, $contentRight, $lineY);

        $pdf->Text($textX, $lineY + 1.2, $footerText);
        $pdf->SetLineWidth(0.2);

        return $pdf;
    }
}