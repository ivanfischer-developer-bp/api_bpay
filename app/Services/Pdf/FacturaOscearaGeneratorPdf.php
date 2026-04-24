<?php

namespace App\Services\Pdf;

use setasign\Fpdi\Fpdi;

class FacturaOscearaGeneratorPdf
{
    /**
     * Genera el PDF de la factura para el ambiente OSCEARA
     * @param Fpdi $pdf
     * @param array $params
     * @return Fpdi
     */
    public function generar(Fpdi $pdf, array $params): Fpdi
    {
        $data = $params;
        // return $data;
        // Estructura base para factura OSCEARA (sin plantilla)
        $font = 'Arial';
        $pdf->SetMargins(13, 15);
        $pdf->AddPage('portrait');
        $pdf->SetFont($font, '', 10);

        // colores
        $verde = [26, 116, 105];
        $verde_claro = [83, 161, 160];
        $negro = [0, 0, 0];
        $blanco = [255, 255, 255];
        $amarillo = [254, 204, 16];
        $amarillo_claro = [246, 219, 96];
        $gris = [128, 128, 128];

        // cuadrado central
        $pdf->SetDrawColor(...$verde); // verde
        $pdf->SetLineWidth(0.3);
        $pdf->Line(93, 15, 93, 33); // izquierda
        $pdf->Line(113, 15, 113, 33); // derecha
        $pdf->Line(93, 15, 113, 15); // superior
        $pdf->Line(93, 33, 113, 33); // inferior
        $pdf->SetDrawColor(...$negro); // restablece a negro para el resto
        // datos
        // logo
        $pdf->Image(storage_path('app/public/images/logo_osceara_plus.png'), 12, 10, 40, 0, 'PNG');
        // tipo
        $pdf->SetFont($font, '', 8);
        $pdf->SetY(15);
        $pdf->SetX(93);
        $pdf->Cell(20, 5, utf8_decode('FACTURA'), 0, 0, 'C');
        $pdf->SetFont($font, 'B', 16);
        $pdf->SetY(20);
        $pdf->SetX(93);
        $ultimoCaracter = substr($data['tipo_documento'], -1);
        $pdf->MultiCell(20, 10, utf8_decode($ultimoCaracter), 0, 'C');
        
        // direccion y tipo contribuyente
        $pdf->SetFont($font, '', 6);
        $pdf->SetTextColor(...$verde); // verde
        $pdf->SetY(30);
        $pdf->SetX(13);
        $pdf->Cell(80, 3, utf8_decode($data['legal_direccion']), 0, 2, 'L');
        $pdf->Cell(80, 3, utf8_decode($data['telefono']), 0, 2, 'L');
        $pdf->Cell(80, 3, utf8_decode('IVA Responsable Inscripto'), 0, 2, 'L');
        $pdf->SetTextColor(0, 0, 0); // restablece a negro
        $pdf->SetFont($font, '', 6);
        $pdf->SetTextColor(...$verde); // verde
        $pdf->SetY(15);
        $pdf->SetX(143);
        $pdf->Cell(25, 3, utf8_decode('FC'), 0, 2, 'L');
        $pdf->Cell(25, 3, utf8_decode('Fecha'), 0, 2, 'L');
        $pdf->Cell(25, 3, utf8_decode('VencimientoFactura'), 0, 2, 'L');
        $pdf->SetFont($font, '', 6);
        $pdf->SetTextColor(...$negro); // negro
        $pdf->SetY(15);
        $pdf->SetX(168);
        $numero_factura = $data['fe_cbte_nro'] != null ? $data['fe_cbte_nro'] : $data['numero_factura'];
        $pdf->Cell(20, 3, utf8_decode($numero_factura), 0, 2, 'R');
        $fecha_factura = $data['fe_cbte_fecha'] != null ? $data['fe_cbte_fecha'] : $data['fecha_factura'];
        $pdf->Cell(20, 3, utf8_decode($fecha_factura), 0, 2, 'R');
        $pdf->Cell(20, 3, utf8_decode(''), 0, 2, 'L'); // VencimientoFactura no se muestra

        $pdf->SetFont($font, '', 6);
        $pdf->SetTextColor(...$verde); // verde
        $pdf->SetY(30);
        $pdf->SetX(143);
        $pdf->Cell(20, 3, utf8_decode('CUIT: '.(isset($data['cuit_empresa']) ? $data['cuit_empresa'] : '')), 0, 2, 'L');
        $pdf->Cell(20, 3, utf8_decode('INGRESOS BRUTOS: '.(isset($data['ingresos_brutos']) ? $data['ingresos_brutos'] : '')), 0, 2, 'L');
        $pdf->Cell(20, 3, utf8_decode('INICIO ACTIVIDAD: '.(isset($data['inicio_actividad']) ? $data['inicio_actividad'] : '')), 0, 2, 'L');

        // linea divisoria
        $pdf->SetDrawColor(...$verde); // verde
        $pdf->Line(12, 42, 195, 42); // horizontal
        $pdf->SetDrawColor(...$negro); // restablece a negro
        $pdf->SetTextColor(...$negro); // negro

        // nombre y domicilio del cliente
        $pdf->SetY(46);
        $pdf->SetX(13);
        $pdf->SetFont($font, 'B', 10);
        $pdf->Cell(180, 4, utf8_decode($data['nombre']), 0, 2, 'L');
        $pdf->SetFont($font, 'B', 7);
        $pdf->Cell(180, 4, utf8_decode($data['domicilio']), 0, 2, 'L');
        $pdf->SetFont($font, '', 6);

        // datos afiliado
        $pdf->SetY(54);
        $pdf->SetX(13);
        $pdf->SetTextColor(...$verde);
        $pdf->Cell(30, 3, utf8_decode('N° de Afiliado: '), 0, 2, 'L');
        $pdf->Cell(30, 3, utf8_decode('Plan: '), 0, 2, 'L');
        $pdf->Cell(30, 3, utf8_decode('CUIT/CUIL: '), 0, 2, 'L');
        $pdf->Cell(30, 3, utf8_decode('Condición ante el IVA: '), 0, 2, 'L');
        $pdf->Cell(30, 3, utf8_decode('Condición de venta: '), 0, 2, 'L');
        $pdf->Cell(30, 3, utf8_decode('Composición Familiar: '), 0, 2, 'L');

        $pdf->SetY(54);
        $pdf->SetX(43);
        $pdf->SetTextColor(...$negro);
        $pdf->Cell(60, 3, utf8_decode($data['nro_afiliado']), 0, 2, 'L');
        $pdf->Cell(60, 3, utf8_decode(isset($data['n_plan']) ? $data['n_plan'] : ''), 0, 2, 'L');
        $pdf->Cell(60, 3, utf8_decode(isset($data['cuit_cuil']) ? $data['cuit_cuil'] : ''), 0, 2, 'L');
        $pdf->Cell(60, 3, utf8_decode(isset($data['condicion_iva']) ? $data['condicion_iva'] : ''), 0, 2, 'L');
        $pdf->Cell(60, 3, utf8_decode(isset($data['condicion_venta']) ? $data['condicion_venta'] : 'Contado'), 0, 2, 'L');
        $pdf->Cell(60, 3, utf8_decode(isset($data['composicion_familiar']) ? $data['composicion_familiar'] : ''), 0, 2, 'L');

        // Dibuja el recuadro redondeado en la posición y tamaño solicitados
        $pdf->SetY(54);
        $pdf->SetDrawColor(...$verde);
        $pdf->Rect(120, 54, 77, 4, 'D');
        $pdf->Rect(120, 58, 77, 4, 'D');
        $pdf->SetY(54);
        $pdf->SetX(122);
        $pdf->SetDrawColor(...$negro);
        $pdf->SetTextColor(...$verde);
        $pdf->Cell(50, 4, utf8_decode('Saldo actualizado del mes anterior impago'), 0, 2, 'L');
        $pdf->Cell(15, 4, utf8_decode('Saldo total al '), 0, 2, 'L');
        $pdf->SetY(54);
        $pdf->SetX(170);
        $pdf->SetTextColor(...$negro);
        $fecha_saldo = isset( $data['fe_cbte_fecha']) ?  $data['fe_cbte_fecha'] : Carbon::now()->format('d/M/Y');
        $pdf->Cell(25, 4, utf8_decode(isset($data['deuda']) ? $data['deuda'] : '$ 0,00'), 0, 2, 'R');
        $pdf->SetY(58);
        $pdf->SetX(140);
        $pdf->SetTextColor(...$negro);
        $pdf->Cell(15, 4, utf8_decode($fecha_saldo), 0, 2, 'L');
        $pdf->SetY(58);
        $pdf->SetX(170);
        $pdf->Cell(25, 4, utf8_decode(isset( $data['saldo']) ?  $data['saldo'] : '$ 0,00'), 0, 2, 'R');
        
        //
        // Relleno verde_claro, borde blanco
        $pdf->SetDrawColor(...$blanco); // borde blanco
        $pdf->SetFillColor(...$verde_claro); // relleno verde_claro
        $pdf->Rect(13, 75, 150, 6, 'DF');
        $pdf->Rect(151, 75, 48, 6, 'DF');
        $pdf->SetY(76);
        $pdf->SetX(15);
        $pdf->SetTextColor(...$blanco);
        $pdf->Cell(135, 4, utf8_decode('DETALLE DE LO FACTURADO EN ESTE PERíODO '), 0, 0, 'L');
        $pdf->Cell(48, 4, utf8_decode('MONTO '), 0, 1, 'C');
        
        $pdf->SetDrawColor(...$negro);
        $pdf->SetTextColor(...$negro);
        $pdf->SetFont($font, '', 6);

        // conceptos
        $pdf->SetY(82);
        $pdf->SetX(15);
        $conceptos = explode('<br>', $data['conceptos']);
        foreach($conceptos as $concepto){
            $pdf->SetX(15);
            if(strlen(utf8_decode(trim($concepto)))>90){
                $concepto = substr(utf8_decode(trim($concepto)), 0, 88);
                $concepto = $concepto.'...';
            }else{
                $concepto = utf8_decode(trim($concepto));
            }
            $pdf->MultiCell(135, 4, $concepto, 0, 'L');
        }

        // importes
        $pdf->SetY(82);
        $pdf->SetX(150);
        $pdf->SetFont($font, 'B', 8);
        $importes = explode('<br>', $data['importes']);
        foreach($importes as $importe){
            if($importe != ''){
                $pdf->SetX(151);
                // Borde y fondo amarillo_claro
                $pdf->SetDrawColor(...$amarillo_claro);
                $pdf->SetFillColor(...$amarillo_claro);
                $pdf->Cell(48, 4, '$ '.utf8_decode($importe), 1, 0, 'R', true);
                $pdf->Ln(4);
            }
        }
              
        // relleno de amarillo
        $pdf->SetY($pdf->getY());
        $pdf->SetX(151);
        $pdf->Rect($pdf->GetX(), $pdf->GetY(), 48, (165 - $pdf->getY()), 'DF');
        $pdf->SetDrawColor(...$amarillo_claro); // Restablece color borde
        $pdf->SetFillColor(...$amarillo_claro);


        $pdf->SetDrawColor(...$negro); // Restablece color borde
        $pdf->SetFillColor(...$blanco); // Restablece color fondo
        $pdf->SetFont($font, '', 6);
        $pdf->SetY(154);
        $pdf->SetX(13);
        $pdf->Cell(20, 3, utf8_decode('El monto de esta factura será debitado de su cuenta bancaria.'), 0, 2, 'L');
        $pdf->Cell(20, 3, utf8_decode('Régimen de Transparencia Fiscal al Consumidor (Ley 27.743):'), 0, 2, 'L');
        $pdf->Cell(20, 3, utf8_decode('IVA contenido: $ (0,00)'), 0, 2, 'L');

        

        // qr code
        $pdf->Image(storage_path('app/public/images/qr_empresa.png'), 133, $pdf->getY() -15, 15, 0, 'PNG');

        // recuadros de impuestos
        $pdf->SetDrawColor(...$gris);
        $pdf->SetY(165);
        $pdf->SetX(13);
        $pdf->Cell(34, 6, utf8_decode('NETO GRAVADO'), 1, 0, 'C');
        $pdf->Cell(30, 6, utf8_decode('EXCENTO'), 1, 0, 'C');
        $pdf->Cell(36, 6, utf8_decode('OTROS IMPUESTOS'), 1, 0, 'C');
        $pdf->Cell(38, 6, utf8_decode('IVA'), 1, 0, 'C');
        $pdf->SetFont($font, 'B', 8);
        $pdf->Cell(48, 6, utf8_decode('TOTAL'), 1, 1, 'R');
        $pdf->SetFont($font, '', 6);
        $pdf->Cell(34, 6, utf8_decode(isset( $data['neto_gravado']) ?  $data['neto_gravado'] : '-'), 1, 0, 'C');
        $pdf->Cell(30, 6, utf8_decode(isset( $data['excento']) ?  $data['excento'] : '-'), 1, 0, 'C');
        $pdf->Cell(36, 6, utf8_decode(isset( $data['otros_impuestos']) ?  $data['otros_impuestos'] : '-'), 1, 0, 'C');
        $pdf->Cell(38, 6, utf8_decode(isset( $data['iva']) ?  $data['iva'] : '-'), 1, 0, 'C');
        $pdf->SetFont($font, 'B', 8);
        $pdf->Cell(48, 6, utf8_decode(isset( $data['imp_total']) ?  $data['imp_total'] : '-'), 1, 1, 'R');
        $pdf->SetFont($font, '', 6);

        // relleno de amarillo
        $pdf->SetY($pdf->getY());
        $pdf->SetX(151);
        $pdf->SetDrawColor(...$amarillo_claro); // Restablece color borde
        $pdf->SetFillColor(...$amarillo_claro);
        $pdf->Rect($pdf->GetX(), $pdf->GetY() + 1, 48, (197 - $pdf->getY()), 'DF');

        // Relleno verde_claro, borde blanco
        $pdf->SetDrawColor(...$blanco); // borde blanco
        $pdf->SetFillColor(...$verde_claro); // relleno verde_claro
        $pdf->Rect(13, 197, 150, 6, 'DF');
        $pdf->Rect(151, 197, 48, 6, 'DF');
        $pdf->SetY(198);
        $pdf->SetX(15);
        $pdf->SetTextColor(...$blanco);
        $pdf->SetFont($font, 'B', 8);
        $pdf->Cell(135, 4, utf8_decode('TOTAL A PAGAR '), 0, 0, 'L');
        $pdf->Cell(48, 4, utf8_decode(isset( $data['imp_total']) ?  $data['imp_total'] : '$ 0,00'), 0, 2, 'R');
        
        // CUIT CAE y fecha CAE
        $pdf->SetDrawColor(...$negro); // borde negro
        $pdf->SetTextColor(...$negro);
        $pdf->SetFont($font, '', 6);
        $pdf->SetY(205);
        $pdf->SetX(13);
        $pdf->Cell(185, 4, utf8_decode('CUIT '.$data['cuit_empresa'].'    CAE  '.$data['fe_cae'].'      '.$data['fe_cbte_fecha']), 0, 2, 'C');

        // línea divisoria
        $pdf->SetLineWidth(0.25);
        $pdf->SetDrawColor(...$gris);
        $pdf->Line(5, 210, 205, 210); // horizontal

        // talon para el pago
        $pdf->SetFont($font, 'B', 6);
        $pdf->SetY(213);
        $pdf->SetX(13);
        $pdf->Cell(185, 3, utf8_decode('TALÓN DE PAGO'), 0, 2, 'L');
        // logo
        $pdf->Image(storage_path('app/public/images/logo_factura_osceara.png'), 12, 216, 40, 0, 'PNG');

        $pdf->SetFont($font, '', 6);
        $pdf->SetY(217);
        $pdf->SetX(66);
        $pdf->Cell(12, 3, utf8_decode('Afiliado N°: '), 0, 0, 'L');
        $pdf->SetFont($font, 'B', 6);
        $pdf->Cell(32, 3, utf8_decode($data['nro_afiliado']), 0, 0, 'L');
        $pdf->SetFont($font, '', 6);
        $pdf->Cell(12, 3, utf8_decode('Factura N°: '), 0, 0, 'L');
        $pdf->SetFont($font, 'B', 6);
        $pdf->Cell(32, 3, utf8_decode($data['numero_factura']), 0, 1, 'L');
        $pdf->SetY(222);
        $pdf->SetX(66);
        $pdf->Cell(119, 3, utf8_decode('Los importes que se detallan a continuación incluyen saldo anterior más facturación mensual'), 0, 1, 'L');
        
        $pdf->SetY(226);
        $pdf->SetX(66);
        $pdf->SetDrawColor(...$amarillo); // borde amarillo
        $pdf->SetFillColor(...$amarillo_claro);
        $pdf->Rect($pdf->GetX(), $pdf->GetY(), 50, 6, 'DF');
        $pdf->Rect($pdf->GetX(), ($pdf->GetY() + 6), 50, 6, 'DF');
        $pdf->SetFont($font, '', 6);
        $pdf->SetTextColor(...$negro);
        $pdf->SetY(227);
        $pdf->SetX(66);
        $pdf->Cell(50, 6, utf8_decode('Saldo total impago períodos anteriores'), 0, 1, 'C');
        $pdf->SetFont($font, 'B', 6);
        $pdf->SetX(66);
        $pdf->Cell(50, 6, utf8_decode(isset($data['deuda']) ? $data['deuda'] : '$ 0,00'), 0, 1, 'C');
        $pdf->SetFont($font, '', 6);

        $pdf->SetY(226);
        $pdf->SetX(120);
        $pdf->SetDrawColor(...$gris); // borde gris
        $pdf->SetFillColor(...$blanco);
        $pdf->Rect($pdf->GetX(), $pdf->GetY(), 40, 6, 'DF');
        $pdf->Rect($pdf->GetX() + 40, $pdf->GetY(), 40, 6, 'DF');
        $pdf->SetX(120);
        $pdf->Rect($pdf->GetX(), ($pdf->GetY() + 6), 40, 6, 'DF');
        $pdf->Rect($pdf->GetX() + 40, ($pdf->GetY() + 6), 40, 6, 'DF');
        $pdf->SetY(226);
        $pdf->SetX(120);
        $pdf->Cell(20, 6, utf8_decode('Hasta el '), 0, 0, 'C');
        $pdf->SetFont($font, 'B', 6);
        $pdf->Cell(20, 6, utf8_decode($data['vto1']), 0, 0, 'C');
        $pdf->SetFont($font, '', 6);
        $pdf->Cell(20, 6, utf8_decode('Importe '), 0, 0, 'C');
        $pdf->SetFont($font, 'B', 6);
        $pdf->Cell(20, 6, utf8_decode('$ '.$data['imp_vto1']), 0, 0, 'C');
        $pdf->SetY(232);
        $pdf->SetX(120);
        $pdf->SetFont($font, '', 6);
        $pdf->Cell(20, 6, utf8_decode('Hasta el '), 0, 0, 'C');
        $pdf->SetFont($font, 'B', 6);
        $pdf->Cell(20, 6, utf8_decode($data['vto2']), 0, 0, 'C');
        $pdf->SetFont($font, '', 6);
        $pdf->Cell(20, 6, utf8_decode('Importe '), 0, 0, 'C');
        $pdf->SetFont($font, 'B', 6);
        $pdf->Cell(20, 6, utf8_decode('$ '.$data['imp_vto2']), 0, 2, 'C');

        $pdf->SetY(267);
        $pdf->SetX(13);
        $pdf->SetFont($font, '', 8);
        $pdf->Cell(53, 6, utf8_decode($data['numero_link_pagos']), 0, 2, 'L');

        $pdf->SetY(244);
        $pdf->SetX(66);
        $pdf->Cell(53, 3, utf8_decode('Código para entes recaudadores'), 0, 2, 'L');
        $pdf->SetY(248);
        $pdf->SetX(66);
        // código de barras
        $pdf->SetFillColor(0,0,0);
        // $pdf, $xpos, $ypos, $code, $baseline = 0.5, $height = 5, $show_text = FALSE
        $codigo_barra = trim(chunk_split($data['cod_largo'], 2, ' '));
        $pdf->Cell(134, 15, get_code39($pdf, 73, 249, $data['cod_largo'], 0.5, 8), 1, 0, 'C');
        $pdf->SetY(258);
        $pdf->SetX(66);
        $pdf->SetFont($font, '', 9);
        $pdf->Cell(134, 4, $codigo_barra, 0, 0, 'C');





        return $pdf;
    }


    


}
