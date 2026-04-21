<?php

namespace App\Services\Pdf;

use setasign\Fpdi\Fpdi;

class FacturaGeneralGeneratorPdf
{
    /**
     * Genera el PDF de la factura para el ambiente general
     * @param Fpdi $pdf
     * @param array $data
     * @return Fpdi
     */
    public function generar(Fpdi $pdf, array $data): Fpdi
    {
        // Aquí va la lógica para generar el PDF de la factura general
        // Ejemplo básico:
        // $pdf = new Fpdi('portrait', 'mm', 'A4');

        //  font setup
        $font = 'Arial';
        //  -------------------
        $pdf->SetMargins(15, 15);
        // Añade una página
        $pdf->AddPage('portrait');
        
        //  establece valor de tipografia
        $pdf->SetFont($font, '', 10);
        
        // cabecera -------------------------------------------------------------------------------------------
        // líneas 
        // borde exterior
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, 15, 15, 187); // línea verical izquierda
        $pdf->Line(195, 15, 195, 187); // línea verical derecha
        $pdf->Line(15, 15, 195, 15); // línea superior
        $pdf->Line(15, 187, 195, 187); // línea inferior
        //línea central y cuadrado central
        $pdf->SetLineWidth(0.3);
        $pdf->Line(105, 15, 105, 22); // vertical sobre recuadro
        $pdf->Line(89, 22, 89, 34); // izquierda
        $pdf->Line(121, 22, 121, 34); // derecha
        $pdf->Line(89, 22, 121, 22); // superior
        $pdf->Line(89, 34, 121, 34); // inferior
        $pdf->Line(105, 34, 105, 50); // vertical bajo recuadro
        $pdf->Line(15, 50, 195, 50); // división
        $pdf->Line(131, 50, 131, 68); // división vertical
        $pdf->Line(15, 68, 195, 68); // final cabecera
        $pdf->Line(131, 59, 195, 59); // final cabecera
        // datos
        // logo
        $pdf->Image(storage_path('app/public/images/'.$data['logo_cabecera']), 17, 20, 60, 0, 'PNG');
        // tipo
        $pdf->SetFont($font, '', 10);
        $pdf->SetY(22);
        $pdf->SetX(89);
        $pdf->MultiCell(32, 6, utf8_decode($data['tipo_documento']), 0, 'C');
        // tipo de documento
        $pdf->SetFont($font, '', 8);
        $pdf->SetY(22);
        $pdf->SetX(131);
        $pdf->Cell(22, 5, utf8_decode($data['tipo_documento']), 0, 0, 'L');
        //número de factura
        $pdf->SetFont($font, '', 8);
        $pdf->SetY(26);
        $pdf->SetX(131);
        $numero_factura = $data['fe_cbte_nro'] != null ? 'N°: '.$data['fe_cbte_nro'] : 'N°: '.$data['numero_factura'];
        $pdf->Cell(22, 5, utf8_decode($numero_factura), 0, 0, 'L');
        // fecha de factura
        $fecha_factura = $data['fe_cbte_fecha'] != null ? 'Fecha de emisión: '.$data['fe_cbte_fecha'] : 'Fecha de emisión: '.$data['fecha_factura'];
        $pdf->SetY(30);
        $pdf->SetX(131);
        $pdf->Cell(22, 5, utf8_decode($fecha_factura), 0, 0, 'L');
        // hoja n° de total
        $hoja = 'Hoja 1 de 1';
        $pdf->SetFont($font, '', 8);
        $pdf->SetY(22);
        $pdf->SetX(170);
        $pdf->Cell(19, 5, utf8_decode($hoja), 0, 0, 'L');
        // datos obra social recuadro izquierdo
        $pdf->SetFont($font, '', 6);
        $pdf->SetY(40);
        $pdf->SetX(17);
        $pdf->Cell(45, 3, utf8_decode($data['legal_direccion']), 0, 0, 'L');
        $pdf->Cell(40, 3, '', 0, 0, 'R');  // lugar para otro dato
        $pdf->SetY(43);
        $pdf->SetX(17);
        $pdf->Cell(45, 3, utf8_decode($data['telefono']), 0, 0, 'L');
        $pdf->SetFont($font, '', 6);
        $pdf->Cell(40, 3, utf8_decode($data['email']), 0, 0, 'R');
        $pdf->SetFont($font, 'B', 6);
        $pdf->SetY(46);
        $pdf->SetX(17);
        $pdf->Cell(45, 3, utf8_decode($data['telefono2']), 0, 0, 'L');
        $pdf->Cell(40, 3, utf8_decode($data['url']), 0, 0, 'R');
        // datos empresa recuadro derecho superior
        $pdf->SetFont($font, '', 6);
        $cuit = 'CUIT: '.$data['cuit_empresa'];
        $pdf->SetY(34);
        $pdf->SetX(131);
        $pdf->Cell(45, 3, utf8_decode($cuit), 0, 0, 'L'); 
        // $ingresos_brutos = 'Ingresos Brutos: '.$data['ingresos_brutos'];
        // $pdf->SetY(37);
        // $pdf->SetX(131);
        // $pdf->Cell(45, 3, utf8_decode($ingresos_brutos), 0, 0, 'L'); 
        // $ganancias = 'Ganancias: '.$data['ganancias'];
        // $pdf->SetY(40);
        // $pdf->SetX(131);
        // $pdf->Cell(45, 3, utf8_decode($ganancias), 0, 0, 'L'); 
        $pdf->SetFont($font, '', 6);
        $pdf->SetY(43);
        $pdf->SetX(131);
        $condicion_iva = 'Condición frente al IVA: ' . $data['condicion_iva'];
        $pdf->Cell(45, 3, utf8_decode($condicion_iva), 0, 0, 'L'); 
        $pdf->SetY(46);
        $pdf->SetX(131);
        $pdf->Cell(45, 3, utf8_decode($data['resolucion_iva']), 0, 0, 'L'); 
        // código qr
        // generamos el código y lo guardamos
        // QrCode::format('png')->size(100)->generate($data['codigoqr'], public_path("storage/images/qrcode.png"));
        // agregamos la imágen desde el archivo
        // $pdf->Image(public_path("storage/images/qrcode.png"), 106, 35, 14, 14, 'PNG');
        // datos recuadro iquierdo grande inferior
        // número afiliadoafiliado
        $pdf->SetFont($font, '', 6);
        $pdf->SetY(52);
        $pdf->SetX(17);
        $pdf->Cell(12, 3, utf8_decode('Afiliado N°: '), 0, 0, 'L');
        $pdf->SetFont($font, 'B', 6);
        $pdf->Cell(55, 3, utf8_decode($data['nro_afiliado']), 0, 0, 'L');
        // periodo
        $pdf->SetFont($font, '', 6);
        $pdf->SetY(52);
        $pdf->SetX(85);
        $pdf->Cell(10, 3, utf8_decode('Período: '), 0, 0, 'L');
        $pdf->SetFont($font, 'B', 6);
        $pdf->SetY(52);
        $pdf->SetX(95);
        $pdf->Cell(30, 3, utf8_decode($data['periodo']), 0, 0, 'L');
        // nombre afiliado
        $pdf->SetFont($font, '', 6);
        $pdf->SetY(56);
        $pdf->SetX(17);
        $pdf->Cell(12, 3, utf8_decode($data['tratamiento']), 0, 0, 'L');
        $pdf->SetFont($font, 'B', 6);
        $pdf->Cell(55, 3, utf8_decode($data['nombre']), 0, 0, 'L');
        // domicilio
        $pdf->SetFont($font, '', 6);
        $pdf->SetY(60);
        $pdf->SetX(17);
        $pdf->Cell(12, 3, utf8_decode('Domicilio'), 0, 0, 'L');
        $pdf->SetFont($font, 'B', 6);
        $pdf->Cell(55, 3, utf8_decode($data['domicilio']), 0, 0, 'L');
        // localidad
        $pdf->SetFont($font, '', 6);
        $pdf->SetY(64);
        $pdf->SetX(17);
        $pdf->Cell(12, 3, utf8_decode('Localidad'), 0, 0, 'L');
        $pdf->SetFont($font, 'B', 6);
        $pdf->Cell(55, 3, utf8_decode($data['localidad']), 0, 0, 'L');
        // datos recuadro derecho pequeño inferior
        // primer vencimiento
        $pdf->SetFont($font, '', 8);
        $pdf->SetY(53);
        $pdf->SetX(133);
        $pdf->Cell(12, 3, utf8_decode('Hasta el '), 0, 0, 'L');
        $pdf->SetFont($font, 'B', 8);
        $pdf->Cell(14, 3, utf8_decode($data['vto1']), 0, 0, 'L');
        // importe primer vencimiento
        $pdf->SetFont($font, '', 8);
        $pdf->SetY(53);
        $pdf->SetX(165);
        $pdf->Cell(14, 3, utf8_decode('Importe: $ '), 0, 0, 'L');
        $pdf->SetFont($font, 'B', 8);
        $pdf->Cell(16, 3, utf8_decode($data['imp_vto1']), 0, 0, 'L');
        // segundo vencimiento
        $pdf->SetFont($font, '', 8);
        $pdf->SetY(63);
        $pdf->SetX(133);
        $pdf->Cell(12, 3, utf8_decode('Hasta el '), 0, 0, 'L');
        $pdf->SetFont($font, 'B', 8);
        $pdf->Cell(14, 3, utf8_decode($data['vto2']), 0, 0, 'L');
        // importe segundo vencimiento
        $pdf->SetFont($font, '', 8);
        $pdf->SetY(63);
        $pdf->SetX(165);
        $pdf->Cell(14, 3, utf8_decode('Importe: $ '), 0, 0, 'L');
        $pdf->SetFont($font, 'B', 8);
        $pdf->Cell(16, 3, utf8_decode($data['imp_vto2']), 0, 0, 'L');
        // FIN CABECERA --------------------------------------------------------------------------------------------------

        // DETALLE DE FACTURA --------------------------------------------------------------------------------------------
        $pdf->SetY(70);
        $pdf->SetX(17);
        $pdf->SetLineWidth(0.1);
        $pdf->SetFont($font, 'B', 8);
        $pdf->SetFillColor(26,121,204);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetDrawColor(153,153,153);
        $pdf->Cell(176, 6, utf8_decode('DETALLE DEL PERÍODO'), 1, 0, 'C', true);
        $pdf->Rect(17,76,176,88);
        // imagen de fondo
        $pdf->Image($data['fondo'], 18, 78, 158, 77, 'JPG');
        $pdf->SetFont($font, '', 8);
        $pdf->SetTextColor(53,53,53);
        $pdf->SetY(78);
        $pdf->SetX(20);
        $pdf->Cell(120, 5, utf8_decode('CONCEPTO'), 0, 0, 'L');
        $pdf->Cell(50, 5, utf8_decode('IMPORTE  '), 0, 0, 'R');
        $pdf->SetLineWidth(0.3);
        $pdf->SetDrawColor(53,53,53);
        $pdf->Line(20, 83, 190, 83); // linea conceptos-importe
        // conceptos
        $pdf->SetFont($font, '', 7);
        $pdf->SetTextColor(5,5,5);
        $pdf->SetY(85);
        $pdf->SetX(20);
        $conceptos = explode('<br>', $data['conceptos']);
        foreach($conceptos as $concepto){
            $pdf->SetX(20);
            if(strlen(utf8_decode(trim($concepto)))>90){
                $concepto = substr(utf8_decode(trim($concepto)), 0, 88);
                $concepto = $concepto.'...';
            }else{
                $concepto = utf8_decode(trim($concepto));
            }
            $pdf->MultiCell(170, 3, $concepto, 0, 'L');
        }
        // importes
        $pdf->SetFont($font, 'B', 7);
        $pdf->SetY(85);
        $pdf->SetX(170);
        $importes = explode('<br>', $data['importes']);
        foreach($importes as $importe){
            if($importe != ''){
                $pdf->SetX(170);
                $pdf->MultiCell(20, 3, '$ '.utf8_decode($importe), 0, 'R');
            }
        }
        // aclaraciones finales
        $pdf->SetFont($font, 'B', 8);
        $pdf->SetTextColor(5,5,5);
        $pdf->SetY(155);
        $pdf->SetX(20);
        $pdf->Cell(50, 5, utf8_decode($data['texto1']), 0, 0, 'L');
        $pdf->SetY(159);
        $pdf->SetX(20);
        $pdf->SetFont($font, '', 8);
        $pdf->Cell(50, 5, utf8_decode($data['numero_link_pagos']), 0, 0, 'L');
        // CAE y vencimiento CAE para el afiliado
        if($data['fe_cae'] != null){
            $pdf->setFont('Arial', 'BI', 9);
            $pdf->SetTextColor(0,0,0);
            $pdf->SetY(160);
            $pdf->SetX(100);
            $pdf->Cell(45, 3, 'CAE: '.$data['fe_cae'], 0, 0, 'L');
            $pdf->setFont('Arial', '', 8);
            $pdf->SetTextColor(0,0,0);
            $pdf->SetY(160);
            $pdf->SetX(145);
            $pdf->Cell(50, 3, 'Vencimiento CAE: '.$data['fe_cae_vto'], 0, 0, 'L');
        }
        // mensajes de interes 
        if(isset($data['msg_interes']) && $data['msg_interes'] != null){
            $pdf->SetFont($font, '', 8);
            $pdf->SetY(167);
            $pdf->SetX(20);
            $pdf->Cell(50, 5, utf8_decode('MENSAJE DE SU INTERÉS'), 0, 0, 'L');
        }

        $pdf->SetFont($font, 'B', 8);
        $pdf->SetY(167);
        $pdf->SetX(70);
        $pdf->Cell(65, 5, utf8_decode($data['msg1']), 0, 0, 'L');
        $pdf->SetFont($font, '', 8);
        $pdf->SetY(172);
        $pdf->SetX(20);
        $pdf->MultiCell(115, 3, utf8_decode($data['msg_interes']), 0, 'J');
        
        // aviso importante
        if(isset($data['aviso']) && $data['aviso'] != null){
            $pdf->SetFont($font, 'B', 8);
            $pdf->SetY(178);
            $pdf->SetX(20);
            $pdf->Cell(115, 5, utf8_decode($data['aviso']), 0, 0, 'L');
            $pdf->SetFont($font, '', 8);
        }
        // total
        $pdf->SetFont($font, '', 10);
        $pdf->SetY(173);
        $pdf->SetX(135);
        $pdf->Cell(50, 5, utf8_decode('TOTAL'), 0, 0, 'L');
        $pdf->SetFont($font, 'B', 12);
        $pdf->SetY(170);
        $pdf->SetX(150);
        $pdf->SetFillColor(220,220,220);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetDrawColor(73,73,73);
        $pdf->SetLineWidth(0.5);
        $pdf->Cell(43, 10, '$ '.utf8_decode($data['imp_total']), 1, 0, 'R', TRUE);
        // FIN DETALLE --------------------------------------------------------------------------------------------------------
        
        // "talón para el afiliado"
        $pdf->SetFont($font, 'B', 8);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetY(188);
        $pdf->SetX(15);
        $pdf->Cell(40, 3, utf8_decode('TALÓN PARA EL AFILIADO'), 0, 0, 'L');
        // imagen ms_children
        $pdf->Image(base_path().'/public/storage/images/'.$data['imagen'], 15, 192, 70, 0, 'PNG');
        
        // código barras Pada Caja de Abogados
        $pdf->SetFont($font, 'B', 10);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetY(192);
        $pdf->SetX(130);
        $pdf->Cell(65, 4, $data['texto_code_bar'], 0, 0, 'C');
        $pdf->SetFillColor(0,0,0);
        // código de barras
        // $pdf, $xpos, $ypos, $code, $baseline = 0.5, $height = 5, $show_text = FALSE
        $pdf->Cell(65, 4, get_code39($pdf, 130, 200, $data['cod_corto'], 0.5, 10), 0, 0, 'C');
        // número del código de barras
        $pdf->SetFont('Arial','',9);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetY(212);
        $pdf->SetX(130);
        $pdf->Cell(65, 4, $data['cod_corto'], 0, 0, 'C');

        // PIE DE PÁGINA ------------------------------------------------------------------------------------------------------
        // línea de separación
        // línea de puntos 
        for( $i=1; $i < 210; $i+=2){
            $pdf->SetX($i);
            $pdf->Line($i, 223, $i+1, 223); 
        }
        // logo chico
        $pdf->Image(storage_path('app/public/images/'.$data['logo_pie']), 15, 226, 50, 0, 'PNG');
        // datos afiliado talon banco
        $pdf->SetFont($font, '', 6);
        $pdf->SetTextColor(30,30,30);
        $cuit = 'CUIT: '.$data['cuit_empresa'];
        $pdf->SetY(255);
        $pdf->SetX(15);
        $pdf->Cell(50, 3, utf8_decode($cuit), 0, 0, 'L'); 
        $pdf->SetFont($font, '', 6);
        $pdf->SetY(258);
        $pdf->SetX(15);
        $pdf->Cell(50, 3, 'IVA '.utf8_decode($data['condicion_iva']), 0, 0, 'L'); 
        $pdf->SetY(261);
        $pdf->SetX(15);
        $pdf->Cell(50, 3, utf8_decode($data['resolucion_iva']), 0, 0, 'L'); 
        // talon para el banco
        $pdf->SetFont($font, 'B', 8);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetY(267);
        $pdf->SetX(15);
        $pdf->Cell(50, 3, utf8_decode('TALÓN PARA EL BANCO'), 0, 0, 'L'); 
        // N° Resumen
        $pdf->SetY(226);
        $pdf->SetX(70);
        $pdf->SetLineWidth(0.1);
        $pdf->SetFont($font, 'B', 8);
        $pdf->SetFillColor(26,121,204);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetDrawColor(153,153,153);
        $pdf->Cell(40, 6, utf8_decode('N° Resumen'), 1, 2, 'C', true);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont($font, '', 8);
        $pdf->Cell(40, 6, utf8_decode($data['numero_factura']), 1, 0, 'C', true);
        // Afiliado y período
        $pdf->SetY(241);
        $pdf->SetX(70);
        $pdf->SetLineWidth(0.1);
        $pdf->SetFont($font, 'B', 8);
        $pdf->SetFillColor(26,121,204);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetDrawColor(153,153,153);
        $pdf->Cell(20, 6, utf8_decode('N° Afiliado'), 1, 0, 'C', true);
        $pdf->Cell(20, 6, utf8_decode('Período'), 1, 2, 'C', true);
        $pdf->SetX(70);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont($font, '', 8);
        $pdf->Cell(20, 6, utf8_decode($data['n_afiliado']), 1, 0, 'C', true);
        $pdf->SetFont($font, '', 8);
        $pdf->Multicell(20, 6, utf8_decode($data['periodo']), 1, 'C');
        // Fecha Importe
        $pdf->SetY(226);
        $pdf->SetX(112);
        $pdf->SetLineWidth(0.1);
        $pdf->SetFont($font, '', 8);
        $pdf->SetFillColor(26,121,204);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetDrawColor(153,153,153);
        $pdf->Cell(20, 6, utf8_decode('Hasta el'), 1, 0, 'C', true);
        $pdf->Cell(20, 6, utf8_decode('Importe'), 1, 2, 'C', true);
        $pdf->SetX(112);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont($font, '', 8);
        $pdf->Cell(20, 6, utf8_decode($data['vto1']), 1, 0, 'C', true);
        $pdf->Cell(20, 6, '$ '.utf8_decode($data['imp_vto1']), 1, 2, 'C');
        // Fecha Importe 2
        $pdf->SetY(241);
        $pdf->SetX(112);
        $pdf->SetLineWidth(0.1);
        $pdf->SetFont($font, 'B', 8);
        $pdf->SetFillColor(26,121,204);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetDrawColor(153,153,153);
        $pdf->Cell(20, 6, utf8_decode('Hasta el'), 1, 0, 'C', true);
        $pdf->Cell(20, 6, utf8_decode('Importe'), 1, 2, 'C', true);
        $pdf->SetX(112);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont($font, '', 8);
        $pdf->Cell(20, 6, utf8_decode($data['vto2']), 1, 0, 'C', true);
        $pdf->Cell(20, 6, '$ '.utf8_decode($data['imp_vto2']), 1, 2, 'C');
        // pago
        // $pdf->SetLineWidth(0.1);
        // $pdf->SetFont($font, '', 6);
        // $pdf->SetFillColor(235,235,235);
        // $pdf->SetTextColor(0,0,0);
        // $pdf->SetDrawColor(153,153,153);
        // $pdf->SetY(226);
        // $pdf->SetX(155);
        // $pdf->Cell(8, 4, utf8_decode('24 Hs.'), 1, 0, 'C', true);
        // $pdf->SetY(226);
        // $pdf->SetX(165);
        // $pdf->Cell(8, 4, utf8_decode('48 Hs.'), 1, 0, 'C', true);
        // $pdf->SetY(226);
        // $pdf->SetX(175);
        // $pdf->Cell(8, 4, utf8_decode('72 Hs.'), 1, 0, 'C', true);
        // $pdf->SetY(226);
        // $pdf->SetX(185);
        // $pdf->Cell(12, 4, utf8_decode('Efectivo'), 1, 0, 'C', true);
        // $pdf->SetY(232);
        // $pdf->SetX(158);
        // $pdf->Cell(8, 3, utf8_decode('Banco: '), 0, 0, 'R');
        // $pdf->Line(167, 235, 197, 235);
        // $pdf->SetY(237);
        // $pdf->SetX(158);
        // $pdf->Cell(8, 3, utf8_decode('Ch. N°: '), 0, 0, 'R');
        // $pdf->Line(167, 240, 197, 240);
        // $pdf->SetY(242);
        // $pdf->SetX(158);
        // $pdf->Cell(8, 3, utf8_decode('En Letras: '), 0, 0, 'R');
        // $pdf->Line(167, 245, 197, 245);
        // código de barras
        $pdf->SetFont('Arial','',9);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetY(256);
        $pdf->SetX(55);
        $pdf->Cell(144, 4, $data['texto_link_pagos'], 0, 0, 'C');
        $pdf->SetFillColor(0,0,0);
        // código de barras
        // $pdf, $xpos, $ypos, $code, $baseline = 0.5, $height = 5, $show_text = FALSE
        $pdf->Cell(65, 4, get_code39($pdf, 65, 260, $data['cod_largo'], 0.5, 8), 0, 0, 'C');
        // número del código de barras
        $pdf->SetFont('Arial','',9);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetY(268);
        $pdf->SetX(55);
        $pdf->Cell(144, 4, $data['cod_largo'], 0, 0, 'C');
        // CAE y vencimiento CAE para el banco
        if($data['fe_cae'] != null){
            $pdf->setFont('Arial', 'I', 9);
            $pdf->SetTextColor(0,0,0);
            $pdf->SetY(272);
            $pdf->SetX(15);
            $pdf->Cell(50, 3, 'CAE: '.$data['fe_cae'], 0, 0, 'L');
            $pdf->setFont('Arial', '', 9);
            $pdf->SetTextColor(0,0,0);
            $pdf->SetY(272);
            $pdf->SetX(65);
            $pdf->Cell(50, 3, 'Vencimiento CAE: '.$data['fe_cae_vto'], 0, 0, 'L');
        }
        // FIN PIE DE PÁGINA -------------------------------------------------------------------------------------------------
        
        return $pdf;
    }
}
