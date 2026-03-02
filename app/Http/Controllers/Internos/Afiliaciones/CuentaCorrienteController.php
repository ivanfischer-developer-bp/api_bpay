<?php

namespace App\Http\Controllers\Internos\Afiliaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
// use Illuminate\Support\Facades\File as FacadeFile;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use File;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;

class CuentaCorrienteController extends ConexionSpController
{
    /**
     * Cosnulta la cuenta corriente de un grupo familiar
     */
    public function consultar_cuenta_corriente(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = '/int/afiliaciones/cuenta-corriente/consultar-cuenta-corriente';
        $this->permiso_requerido = 'consultar afiliados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_cuenta_corriente';
        $this->params = [
            'id_grupo' => request('id_grupo')
        ];
        $this->params_sp = [
            'p_id_grupo' => $this->params['id_grupo']
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Consulta la cuenta corriente de un grupo familiar
     */
    public function buscar_detalle_cuenta_corriente(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = '/int/afiliaciones/cuenta-corriente/buscar-detalle-cuenta-corriente';
        $this->permiso_requerido = 'consultar afiliados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_factura_lst';
        $this->params = [
            'id_factura' => request('id_factura')  // probar con 14273
        ];
        $this->params_sp = [
            'id_factura' => $this->params['id_factura'],
            'tipo_dato' => 4  /// probar con VECA, LUCIA
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Busca las facturas de una cuenta corriente
     */
    public function buscar_facturas(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = 'int/afiliaciones/cuenta-corriente/buscar-facturas';
        $this->permiso_requerido = 'consultar afiliados';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_grupo_mov_Select';
        $this->params = [
            'id_grupo' => request('id_grupo')  // probar con 14273
        ];
        $this->params_sp = [
            'id_grupo' => $this->params['id_grupo'],
            'estado' => 1  
        ];
        return $this->ejecutar_sp_simple();
    }

    public function imprimir_estado_cuenta(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/afiliaciones/cuenta-corriente/imprimir-estado-cuenta',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail'; // 'ok', 'fail', 'empty', unauthorized', 'warning'  
        $message = '';
        $count = -1;
        $code = null;
        $data = null;
        $errors = [];
        $params_sp = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $permiso_requerido = 'exportar datos';
            if($permiso_requerido == '' || $user->hasPermissionTo($permiso_requerido)){
                $accion = request('accion') == '' ? 'mostrar' : request('accion');
                $elementos_a_exportar = [
                    'id_afiliado' => request('id_afiliado'), 
                    'pendientes' => request('pendientes') == 1 ? true : false,
                    'facturas' => request('facturas') == 1 ? true : false,
                    'recibos' => request('recibos') == 1 ? true : false,
                    'aportes' => request('aportes') == 1 ? true : false
                ];
                $params = [
                    'accion' => request('accion'),
                    'id_afiliado' => request('id_afiliado'), 
                    'pendientes' => request('pendientes'),
                    'facturas' => request('facturas'),
                    'recibos' => request('recibos'),
                    'aportes' => request('aportes')
                ];
                
                $params_sp = [
                    'p_id_afiliado' => $elementos_a_exportar['id_afiliado']
                ];
                
                array_push($extras['sps'], ['sp_afiliado_Select' => $params_sp]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_afiliado_Select', $params_sp));
                $afiliado = $this->ejecutar_sp_directo('afiliacion', 'sp_afiliado_Select', $params_sp);
                array_push($extras['responses'], ['sp_afiliado_Select' => $afiliado]);
                
                if ($afiliado) {
                    //  obtiene el primer registro (el único)
                    $afiliado = $afiliado[0];
                }

                $impresiones = [
                    'pendientes' => request('pendientes') == 1 ? true : false,
                    'facturas' => request('facturas') == 1 ? true : false,
                    'recibos' => request('recibos') == 1 ? true : false,
                    'aportes' => request('aportes') == 1 ? true : false
                ];

                // calcular el total de hojas a imprimir
                $lineas_por_pagina = 39;
                $total_paginas = 0;
                $pagina_actual = 0;
                $total_lineas_pendientes = 0;
                $total_lineas_facturas = 0;
                $total_lineas_recibos = 0;
                $total_lineas_aportes = 0;
                $total_paginas_pendientes = 0;
                $total_paginas_facturas = 0;
                $total_paginas_recibos = 0;
                $total_paginas_aportes = 0;

                // ejecuta los sp en funcion del array
                if($impresiones['facturas']){
                    
                    $params_facturas = [
                        'id_grupo' => $afiliado->id_grupo, 
                        'estado' => 1
                    ];
                    
                    array_push($extras['sps'], ['sp_grupo_mov_Select' => $params_facturas]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_grupo_mov_Select', $params_facturas));
                    $facturas = $this->ejecutar_sp_directo('afiliacion', 'sp_grupo_mov_Select', $params_facturas);
                    array_push($extras['responses'], ['sp_grupo_mov_Select' => $facturas]);
                    foreach($facturas as $factura){
                        $total_lineas_facturas++;
                    }
                    $total_paginas_facturas = intval(round(($total_lineas_facturas/$lineas_por_pagina), 0, PHP_ROUND_HALF_UP));
                    // lo siguiente suma 1 cuando el promedio da decimales menor a 0,50
                    $total_paginas_facturas = ($total_lineas_facturas/$lineas_por_pagina - intval(round(($total_lineas_facturas/$lineas_por_pagina), 0, PHP_ROUND_HALF_UP))) >  0 ? $total_paginas_facturas + 1 : $total_paginas_facturas;
                    // lo siguiente no haría falta después de lo anterior porque siempre el menor sería 1
                    // $total_paginas_facturas = $total_paginas_facturas > 1 ? $total_paginas_facturas : 1;
                }

                if($impresiones['pendientes']){
                    $params_pendientes = [
                        'id_grupo' => $afiliado->id_grupo, 
                        'estado' => 0
                    ];
                    array_push($extras['sps'], ['sp_grupo_mov_Select' => $params_pendientes]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_grupo_mov_Select', $params_pendientes));
                    $pendientes = $this->ejecutar_sp_directo('afiliacion', 'sp_grupo_mov_Select', $params_pendientes);
                    array_push($extras['responses'], ['sp_grupo_mov_Select' => $pendientes]);
                    foreach($pendientes as $pendiente){
                        $total_lineas_pendientes++;
                    }
                    $total_paginas_pendientes = intval(round(($total_lineas_pendientes/$lineas_por_pagina), 0, PHP_ROUND_HALF_UP));
                    // lo siguiente suma 1 cuando el promedio da decimales menor a 0,50
                    $total_paginas_pendientes = ($total_lineas_pendientes/$lineas_por_pagina - intval(round(($total_lineas_pendientes/$lineas_por_pagina), 0, PHP_ROUND_HALF_UP))) >  0 ? $total_paginas_pendientes + 1 : $total_paginas_pendientes;
                    // lo siguiente no haría falta después de lo anterior porque siempre el menor sería 1
                    // $total_paginas_pendientes = $total_paginas_pendientes > 1 ? $total_paginas_pendientes : 1;
                }
                
                if($impresiones['recibos']){
                    $params_recibos = [
                        'id_grupo' => $afiliado->id_grupo, 
                        'estado' => 2
                    ];
                    array_push($extras['sps'], ['sp_grupo_mov_Select' => $params_recibos]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_grupo_mov_Select', $params_recibos));
                    $recibos = $this->ejecutar_sp_directo('afiliacion', 'sp_grupo_mov_Select', $params_recibos);
                    array_push($extras['responses'], ['sp_grupo_mov_Select' => $recibos]);
                    foreach($recibos as $recibo){
                        $total_lineas_recibos++;
                    }
                    $total_paginas_recibos = intval(round(($total_lineas_recibos/$lineas_por_pagina), 0, PHP_ROUND_HALF_UP));
                    // lo siguiente suma 1 cuando el promedio da decimales menor a 0,50
                    $total_paginas_recibos = ($total_lineas_recibos/$lineas_por_pagina - intval(round(($total_lineas_recibos/$lineas_por_pagina), 0, PHP_ROUND_HALF_UP))) >  0 ? $total_paginas_recibos + 1 : $total_paginas_recibos;
                    // lo siguiente no haría falta después de lo anterior porque siempre el menor sería 1
                    // $total_paginas_recibos = $total_paginas_recibos > 1 ? $total_paginas_recibos : 1;
                }
                
                if($impresiones['aportes']){
                    $params_aportes = [
                        'id_grupo' => $afiliado->id_grupo, 
                        'estado' => 3
                    ];
                    array_push($extras['sps'], ['sp_grupo_mov_Select' => $params_aportes]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_grupo_mov_Select', $params_aportes));
                    $aportes = $this->ejecutar_sp_directo('afiliacion', 'sp_grupo_mov_Select', $params_aportes);
                    array_push($extras['responses'], ['sp_grupo_mov_Select' => $aportes]);
                    foreach($aportes as $aporte){
                        $total_lineas_aportes++;
                    }
                    $total_paginas_aportes = intval(round(($total_lineas_aportes/$lineas_por_pagina), 0, PHP_ROUND_HALF_UP));
                    // lo siguiente suma 1 cuando el promedio da decimales menor a 0,50
                    $total_paginas_aportes = ($total_lineas_aportes/$lineas_por_pagina - intval(round(($total_lineas_aportes/$lineas_por_pagina), 0, PHP_ROUND_HALF_UP))) >  0 ? $total_paginas_aportes + 1 : $total_paginas_aportes;
                    // lo siguiente no haría falta después de lo anterior porque siempre el menor sería 1
                    // $total_paginas_aportes = $total_paginas_aportes > 1 ? $total_paginas_aportes : 1;
                }
                
                // suma la cantidad de páginas a imprimir
                $total_paginas = $total_paginas_facturas + $total_paginas_recibos + $total_paginas_aportes + $total_paginas_pendientes;
            
                // determina la cantidad de elementos a imprimir
                $contadorImpresiones = 0;
                foreach($impresiones as $impresion){
                    if($impresion){
                        $contadorImpresiones++;
                    }
                }
                
                if (isset($afiliado)) {
                    //  genera los datos para el PDF
                    $numeroBarra = $afiliado->abrev_tipo_doc.$afiliado->nro_doc;
                    $data = [
                        'logo' => env('LOGO_PATH'),
                        'cuit' => $afiliado->cuit,
                        'es_titular' => $afiliado->isTitular == 1 ? 'Si' : 'No',
                        'n_persona' => $afiliado->n_persona,
                        'n_afiliado' => $afiliado->n_afiliado,
                        'abrev_tipo_doc' => $afiliado->abrev_tipo_doc,
                        'nro_doc' => $afiliado->nro_doc,
                        'sexo' => $afiliado->sexo == 'M' ? 'Masculino' : 'Femenino',
                        'fec_ingreso' => Carbon::parse($afiliado->fec_ingreso)->format('d/m/Y'),
                        'fec_impresion' => Carbon::today()->format('d/m/Y'),
                        'codigo_barra' => '*' . $numeroBarra . '*',
                        'n_plan' => $afiliado->n_plan,
                        'n_localidad' => (!empty($afiliado->n_localidad) ? trim($afiliado->n_localidad) : ""),
                        'n_convenio' => $afiliado->n_convenio,
                        'edad' => $afiliado->edad,
                        'n_afip' => $afiliado->n_afip,
                    ];
                    
                    //  -------------------
                    //	crea el objeto y establece variables
                    // $pdf = new \fpdf\FPDF('portrait', 'mm', 'A4');
                    $pdf = new Fpdi('portrait', 'mm', 'A4');

                    //  font setup
                    $font = 'Arial';
                    //  -------------------
                    $pdf->SetMargins(15, 15);
                    
                    // si no se seleccionó nada para imprimir se imprime la información del usuario y nada más
                    if($contadorImpresiones == 0){
                        
                        $pdf->AddPage('portrait');
                        $pagina_actual++;
                        //  establece valor de tipografia
                        $pdf->SetFont($font, '', 10);
                        
                        //  código de barras
                        get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
                        get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);
                        
                        //  cabecera
                        $pdf->Image($data['logo'], 15, 16, 40, 0, 'PNG');
                        
                        // $pdf->Cell(w, h, texto, border, ln, align, fill, link);
                        // w: ancho de celda(en mm), si es 0 se extiende hasta el márgen derecho.
                        // h: alto de celda(en mm), por omisión 0.
                        // texto: cadena a ser impresa, por omisión: cadena vacía.
                        // border: 
                        //      0=sin borde, 
                        //      1=marco 
                        // o una cadena: 
                        //      L=izq, 
                        //      T=sup, 
                        //      R=der, 
                        //      B=inf
                        // ln: indica dónde la posición activa debería ir antes de invocar. 
                        //      0=a la derecha, 
                        //      1=al comienzo de la siguiente línea, 
                        //      2=abajo
                        // align: 
                        //      L o cadena vacía = izquierda, 
                        //      C=centro, 
                        //      R=derecha
                        // fill: indica si el fonde debe ser pintado(true) o transparente(false), por omisión (false)
                        // link: un identificador devuelto por AddLink().
                        $pdf->Cell(60, 14, '', 0, 0);
                        
                        $pdf->Cell(60, 30, $numeroBarra, 0, 0, 'C');
                        $pdf->Cell(60, 5, utf8_decode('Fecha Impresión') . ': ' . $data['fec_impresion'], 0, 2, 'R');
                        $pdf->Cell(60, 5, utf8_decode('Fecha Ingreso') . ': ' . $data['fec_ingreso'], 0, 2, 'R');
                        // $pdf->Cell(60, 5, utf8_decode(trans('app.vencimiento')) . ': ' . $data['vencimiento'], 0, 1, 'R');
                        
                        $pdf->Cell(180, 6, '', 0, 1, 'R');  // dejamos un espacio de 6 mm hasta la siguiente línea
                        //  establece valor de tipografia
                        // $pdf->SetFont(family, style, size);
                        // family:  Courier(ancho fijo)
                        //          Helveticao Arial(sinónimo; sans serif)
                        //          Times(serif)
                        //          Symbol(simbólico)
                        //          ZapfDingbats(simbólico)
                        // style:   cadena vacía: regular
                        //          B: audaz
                        //          I: cursiva
                        //          U: subrayar
                        // size: tamaño de la fuente en puntos
                        $pdf->SetFont($font, 'B', 16);
                        $pdf->Cell(60, 6, '', 0, 0, 'C');
                        $pdf->Cell(60, 5, mb_strtoupper(utf8_decode($data['n_persona']), 'ISO-8859-1'), 0, 0, 'C');
                        $pdf->Cell(60, 6, '', 0, 1, 'R'); // dejamos un espacio de 6 mm hasta la siguiente línea

                        //  establece valor de tipografia
                        $pdf->SetFont($font, '', 8);

                        //  primera línea
                        $pdf->Cell(60, 5, utf8_decode('Número de Afiliado: ') . $data['n_afiliado'], 0, 0, 'L');
                        $pdf->Cell(60, 5, utf8_decode('Convenio: ') . $data['n_convenio'], 0, 0, 'C');
                        $pdf->Cell(60, 5, utf8_decode('Plan: ') . utf8_decode($data['n_plan']), 0, 1, 'R');

                        //  segunda línea
                        $pdf->Cell(60, 5, utf8_decode('Documento: ') . $data['abrev_tipo_doc'].' '.$data['nro_doc'], 0, 0, 'L');
                        $pdf->Cell(60, 5, utf8_decode($data['n_afip']), 0, 0, 'C');
                        $pdf->Cell(60, 5, utf8_decode('Localidad: ') . utf8_decode($data['n_localidad']), 0, 1, 'R');
                    }
                    
                    $elementosImpresos = 0;
                    
                    // imprime los pendientes
                    if($impresiones['pendientes']){
                        $pdf->AddPage('portrait');
                        $pagina_actual++;
                        //  establece valor de tipografia
                        $pdf->SetFont($font, '', 10);
                        
                        //  código de barras
                        get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
                        get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);
                        
                        //  cabecera
                        $pdf->Image($data['logo'], 15, 16, 40, 0, 'PNG');
                        $pdf->Cell(60, 14, '', 0, 0);
                        
                        $pdf->Cell(60, 30, $numeroBarra, 0, 0, 'C');
                        $pdf->Cell(60, 5, utf8_decode('Fecha Impresión') . ': ' . $data['fec_impresion'], 0, 2, 'R');
                        $pdf->Cell(60, 5, utf8_decode('Fecha Ingreso') . ': ' . $data['fec_ingreso'], 0, 2, 'R');
                        $pdf->Cell(60, 5, utf8_decode('Página') . ' ' . $pagina_actual . ' de ' . $total_paginas, 0, 1, 'R');
                        
                        $pdf->Cell(180, 6, '', 0, 1, 'R');  // dejamos un espacio de 6 mm hasta la siguiente línea
                        $pdf->SetFont($font, 'B', 16);
                        $pdf->Cell(60, 3, '', 0, 0, 'C');
                        $pdf->Cell(60, 5, mb_strtoupper(utf8_decode($data['n_persona']), 'ISO-8859-1'), 0, 0, 'C');
                        $pdf->Cell(60, 6, '', 0, 1, 'R'); // dejamos un espacio de 6 mm hasta la siguiente línea

                        //  establece valor de tipografia
                        $pdf->SetFont($font, '', 8);

                        //  primera línea
                        $pdf->Cell(60, 5, utf8_decode('Número de Afiliado: ') . $data['n_afiliado'], 0, 0, 'L');
                        $pdf->Cell(60, 5, utf8_decode('Convenio: ') . $data['n_convenio'], 0, 0, 'C');
                        $pdf->Cell(60, 5, utf8_decode('Plan: ') . utf8_decode($data['n_plan']), 0, 1, 'R');

                        //  segunda línea
                        $pdf->Cell(60, 5, utf8_decode('Documento: ') . $data['abrev_tipo_doc'].' '.$data['nro_doc'], 0, 0, 'L');
                        $pdf->Cell(60, 5, utf8_decode($data['n_afip']), 0, 0, 'C');
                        $pdf->Cell(60, 5, utf8_decode('Localidad: ') . utf8_decode($data['n_localidad']), 0, 1, 'R');

                        //  TABLA PENDIENTES --------------------------------------------------------------
                        // Título de la tabla
                        $pdf->Cell(180, 3, '', 0, 1);
                        $pdf->SetFont($font, 'B', 12);
                        $pdf->Cell(180, 1, 'Pendientes', 0, 1, 'L');
                        //  cabecera de tabla
                        $pdf->SetFont($font, 'B', 10);
                        $pdf->Cell(190, 2, '', 0, 1);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                        $pdf->Cell(25, 5, utf8_decode('Fecha Mov'), 0, 0, 'C'); // fecha movimiento
                        $pdf->Cell(30, 5, utf8_decode('Concepto'), 0, 0, 'L'); // concepto
                        $pdf->Cell(45, 5, utf8_decode('Referencia'), 0, 0, 'L'); //  referencia
                        $pdf->Cell(20, 5, utf8_decode('Fec Carga'), 0, 0, 'R'); // fecha de carga
                        $pdf->Cell(20, 5, utf8_decode('Imp. Uni'), 0, 0, 'R'); // importe unitario
                        $pdf->Cell(20, 5, utf8_decode('Imp. Mov'), 0, 0, 'R'); // importe movimiento
                        $pdf->Cell(30, 5, utf8_decode('Tipo Concepto'), 0, 0, 'L'); // tipo de concepto
                        $pdf->SetY($pdf->GetY() + 5);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                        
                        //  por cada pendiente
                        $row = 0;
                        $y = 0;
                        foreach ($pendientes as $pendiente) {
                            $pdf->SetFont($font, '', 8);
                            $fecha_mov = Carbon::parse($pendiente->fecha_mov)->format('d/m/Y');
                            $pdf->Cell(25, 5, $fecha_mov, 0, 0, 'C'); // fecha movimiento
                            if(strlen($pendiente->n_concepto)>20){
                                $concepto = substr($pendiente->n_concepto, 0, 17).'...';
                            }else{
                                $concepto = $pendiente->n_concepto;
                            }
                            $pdf->Cell(30, 5, utf8_decode($concepto), 0, 0, 'L'); // concepto
                            if(strlen($pendiente->referencia)>30){
                                $referencia = substr($pendiente->referencia, 0, 27).'...';
                            }else{
                                $referencia = $pendiente->referencia;
                            }
                            $pdf->Cell(45, 5, utf8_decode($referencia), 0, 0, 'L'); // referencia
                            $fecha_carga = Carbon::parse($pendiente->fec_carga)->format('d/m/Y');
                            $pdf->Cell(20, 5, $fecha_carga, 0, 0, 'R'); // fecha de carga
                            $imp_uni = number_format($pendiente->imp_unitario, 2, ',', '.');
                            $pdf->Cell(20, 5, '$'.$imp_uni, 0, 0, 'R'); // importe unitario
                            $imp_mov = number_format($pendiente->imp_mov, 2, ',', '.');
                            $pdf->Cell(20, 5, '$'.$imp_mov, 0, 0, 'R'); // importe movimiento
                            $pdf->Cell(30, 5, utf8_decode($pendiente->n_tipo_concepto), 0, 0, 'L'); // tipo de concepto
                            $pdf->SetY($pdf->getY()+5);
                            $pdf->SetX($pdf->GetX());
                            $pdf->Cell(190, 0.001, '', 1, 1, 'C'); // línea
                            $row++;
                            if($row > $lineas_por_pagina){ // si tiene mas de 40 filas generamos una nueva página
                                $pdf->AddPage('portrait');
                                $pagina_actual++;
                                $pdf->SetFont($font, '', 10);
                                //  código de barras
                                get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
                                get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);
                                //  cabecera
                                $pdf->Image($data['logo'], 15, 16, 40, 0, 'PNG');
                                $pdf->Cell(60, 14, '', 0, 0);
                                $pdf->Cell(60, 30, $numeroBarra, 0, 0, 'C');
                                $pdf->Cell(60, 5, utf8_decode('Fecha Impresión') . ': ' . $data['fec_impresion'], 0, 2, 'R');
                                $pdf->Cell(60, 5, utf8_decode('Fecha Ingreso') . ': ' . $data['fec_ingreso'], 0, 2, 'R');
                                $pdf->Cell(60, 5, utf8_decode('Página') . ' ' . $pagina_actual . ' de ' . $total_paginas, 0, 1, 'R');
                                // Nombre del afiliado
                                $pdf->Cell(180, 6, '', 0, 1, 'R');  // dejamos un espacio de 6 mm hasta la siguiente línea
                                $pdf->SetFont($font, 'B', 16);
                                $pdf->Cell(60, 3, '', 0, 0, 'C');
                                $pdf->Cell(60, 5, mb_strtoupper(utf8_decode($data['n_persona']), 'ISO-8859-1'), 0, 0, 'C');
                                $pdf->Cell(60, 6, '', 0, 1, 'R'); // dejamos un espacio de 6 mm hasta la siguiente línea
                                //  establece valor de tipografia
                                $pdf->SetFont($font, '', 8);
                                //  primera línea
                                $pdf->Cell(60, 5, utf8_decode('Número de Afiliado: ') . $data['n_afiliado'], 0, 0, 'L');
                                $pdf->Cell(60, 5, utf8_decode('Convenio: ') . $data['n_convenio'], 0, 0, 'C');
                                $pdf->Cell(60, 5, utf8_decode('Plan: ') . utf8_decode($data['n_plan']), 0, 1, 'R');
                                //  segunda línea
                                $pdf->Cell(60, 5, utf8_decode('Documento: ') . $data['abrev_tipo_doc'].' '.$data['nro_doc'], 0, 0, 'L');
                                $pdf->Cell(60, 5, utf8_decode($data['n_afip']), 0, 0, 'C');
                                $pdf->Cell(60, 5, utf8_decode('Localidad: ') . utf8_decode($data['n_localidad']), 0, 1, 'R');
        
                                //  TABLA PENDIENTES --------------------------------------------------------------
                                // Título de la tabla
                                $pdf->Cell(180, 3, '', 0, 1);
                                $pdf->SetFont($font, 'B', 12);
                                $pdf->Cell(180, 1, 'Pendientes', 0, 1, 'L');
                                //  cabecera de tabla
                                $pdf->SetFont($font, 'B', 10);
                                $pdf->Cell(190, 2, '', 0, 1);
                                $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                                $pdf->Cell(25, 5, utf8_decode('Fecha Mov'), 1, 0, 'C'); // fecha movimiento
                                $pdf->Cell(30, 5, utf8_decode('Concepto'), 1, 0, 'C'); // concepto
                                $pdf->Cell(30, 5, utf8_decode('Referencia'), 1, 0, 'C'); //  referencia
                                $pdf->Cell(25, 5, utf8_decode('Fecha Carga'), 1, 0, 'C'); // fecha de carga
                                $pdf->Cell(25, 5, utf8_decode('Importe Uni'), 1, 0, 'C'); // importe unitario
                                $pdf->Cell(25, 5, utf8_decode('Importe Mov'), 1, 0, 'C'); // importe movimiento
                                $pdf->Cell(30, 5, utf8_decode('Tipo Concepto'), 1, 0, 'C'); // tipo de concepto
                                $pdf->SetY($pdf->GetY() + 5);
                                $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                                // establece la $row en cero para empezar la cuenta nuevamente en la siguiente página
                                $row = 0; 
                                $actual_y = $pdf->GetY();
                            }
                        }
                        $pdf->SetY($pdf->GetY() + 5);
                        $elementosImpresos++;
                    }
                    
                    // imprime las facturas
                    if($impresiones['facturas']){
                        $pdf->AddPage('portrait');
                        $pagina_actual++;
                        //  establece valor de tipografia
                        $pdf->SetFont($font, '', 10);
                        
                        //  código de barras
                        get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
                        get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);
                        
                        //  cabecera
                        $pdf->Image($data['logo'], 15, 16, 40, 0, 'PNG');
                        $pdf->Cell(60, 14, '', 0, 0);
                        
                        $pdf->Cell(60, 30, $numeroBarra, 0, 0, 'C');
                        $pdf->Cell(60, 5, utf8_decode('Fecha Impresión') . ': ' . $data['fec_impresion'], 0, 2, 'R');
                        $pdf->Cell(60, 5, utf8_decode('Fecha Ingreso') . ': ' . $data['fec_ingreso'], 0, 2, 'R');
                        $pdf->Cell(60, 5, utf8_decode('Página') . ' ' . $pagina_actual . ' de ' . $total_paginas, 0, 1, 'R');
                        
                        $pdf->Cell(180, 6, '', 0, 1, 'R');  // dejamos un espacio de 6 mm hasta la siguiente línea
                        $pdf->SetFont($font, 'B', 16);
                        $pdf->Cell(60, 3, '', 0, 0, 'C');
                        $pdf->Cell(60, 5, mb_strtoupper(utf8_decode($data['n_persona']), 'ISO-8859-1'), 0, 0, 'C');
                        $pdf->Cell(60, 6, '', 0, 1, 'R'); // dejamos un espacio de 6 mm hasta la siguiente línea

                        //  establece valor de tipografia
                        $pdf->SetFont($font, '', 8);

                        //  primera línea
                        $pdf->Cell(60, 5, utf8_decode('Número de Afiliado: ') . $data['n_afiliado'], 0, 0, 'L');
                        $pdf->Cell(60, 5, utf8_decode('Convenio: ') . $data['n_convenio'], 0, 0, 'C');
                        $pdf->Cell(60, 5, utf8_decode('Plan: ') . utf8_decode($data['n_plan']), 0, 1, 'R');

                        //  segunda línea
                        $pdf->Cell(60, 5, utf8_decode('Documento: ') . $data['abrev_tipo_doc'].' '.$data['nro_doc'], 0, 0, 'L');
                        $pdf->Cell(60, 5, utf8_decode($data['n_afip']), 0, 0, 'C');
                        $pdf->Cell(60, 5, utf8_decode('Localidad: ') . utf8_decode($data['n_localidad']), 0, 1, 'R');

                        //  TABLA FACTURAS --------------------------------------------------------------
                        // Título de la tabla
                        $pdf->Cell(180, 3, '', 0, 1);
                        $pdf->SetFont($font, 'B', 12);
                        $pdf->Cell(180, 1, 'Facturas', 0, 1, 'L');
                        //  cabecera de tabla
                        $pdf->SetFont($font, 'B', 10);
                        $pdf->Cell(190, 2, '', 0, 1);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                        $pdf->Cell(20, 5, utf8_decode('Tipo'), 0, 0, 'L');
                        $pdf->Cell(30, 5, utf8_decode('Número'), 0, 0, 'R');
                        $pdf->Cell(20, 5, utf8_decode('Fecha'), 0, 0, 'R');
                        $pdf->Cell(30, 5, utf8_decode('Importe'), 0, 0,'R');
                        $pdf->Cell(30, 5, utf8_decode('N° Cbte'), 0, 0, 'R');
                        $pdf->Cell(30, 5, utf8_decode('CAE'), 0, 0, 'R');
                        $pdf->Cell(30, 5, utf8_decode('Vto CAE'), 0, 0, 'R');
                        $pdf->SetY($pdf->GetY()+5);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
        
                        //  por cada factura
                        $row = 0;
                        $y = 0;
                        foreach ($facturas as $factura) {
                            $pdf->SetFont($font, '', 8);
                            $pdf->Cell(20, 5, $factura->n_tipo_factura, 0, 0, 'L'); // tipo
                            $pdf->Cell(30, 5, $factura->nro_factura, 0, 0, 'R'); // numero
                            $pdf->Cell(20, 5, Carbon::parse($factura->fec_factura)->format('d/m/Y'), 0, 0, 'R'); // fecha factura
                            $importe = number_format($factura->imp_total, 2, ',', '.');
                            $pdf->Cell(30, 5, '$'.$importe, 0, 0, 'R'); // importe
                            $pdf->Cell(30, 5, $factura->fe_cbte_nro, 0, 0, 'R'); // número comprobante AFIP
                            $pdf->Cell(30, 5, $factura->fe_cae, 0, 0, 'R'); // CAE AFIP
                            $vto_cae = $factura->fe_cae_vto ? Carbon::parse($factura->fe_cae_vto)->format('d/m/Y') : '';
                            $pdf->Cell(30, 5, $vto_cae, 0, 0, 'R'); // VencimientoCAE AFIP
                            $pdf->SetY($pdf->GetY()+5);
                            $pdf->SetX($pdf->GetX());
                            $pdf->Cell(190, 0.001, '', 1, 1, 'C'); // línea
                            $row++;
                            if($row > $lineas_por_pagina){ // si tiene mas de 40 filas generamos una nueva página
                                $pdf->AddPage('portrait');
                                $pagina_actual++;
                                $pdf->SetFont($font, '', 10);
                                //  código de barras
                                get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
                                get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);
                                //  cabecera
                                $pdf->Image($data['logo'], 15, 16, 40, 0, 'PNG');
                                $pdf->Cell(60, 14, '', 0, 0);
                                $pdf->Cell(60, 30, $numeroBarra, 0, 0, 'C');
                                $pdf->Cell(60, 5, utf8_decode('Fecha Impresión') . ': ' . $data['fec_impresion'], 0, 2, 'R');
                                $pdf->Cell(60, 5, utf8_decode('Fecha Ingreso') . ': ' . $data['fec_ingreso'], 0, 2, 'R');
                                $pdf->Cell(60, 5, utf8_decode('Página') . ' ' . $pagina_actual . ' de ' . $total_paginas, 0, 1, 'R');
                                // Nombre del afiliado
                                $pdf->Cell(180, 6, '', 0, 1, 'R');  // dejamos un espacio de 6 mm hasta la siguiente línea
                                $pdf->SetFont($font, 'B', 16);
                                $pdf->Cell(60, 3, '', 0, 0, 'C');
                                $pdf->Cell(60, 5, mb_strtoupper(utf8_decode($data['n_persona']), 'ISO-8859-1'), 0, 0, 'C');
                                $pdf->Cell(60, 6, '', 0, 1, 'R'); // dejamos un espacio de 6 mm hasta la siguiente línea
                                //  establece valor de tipografia
                                $pdf->SetFont($font, '', 8);
                                //  primera línea
                                $pdf->Cell(60, 5, utf8_decode('Número de Afiliado: ') . $data['n_afiliado'], 0, 0, 'L');
                                $pdf->Cell(60, 5, utf8_decode('Convenio: ') . $data['n_convenio'], 0, 0, 'C');
                                $pdf->Cell(60, 5, utf8_decode('Plan: ') . utf8_decode($data['n_plan']), 0, 1, 'R');
                                //  segunda línea
                                $pdf->Cell(60, 5, utf8_decode('Documento: ') . $data['abrev_tipo_doc'].' '.$data['nro_doc'], 0, 0, 'L');
                                $pdf->Cell(60, 5, utf8_decode($data['n_afip']), 0, 0, 'C');
                                $pdf->Cell(60, 5, utf8_decode('Localidad: ') . utf8_decode($data['n_localidad']), 0, 1, 'R');
        
                                //  TABLA FACTURAS --------------------------------------------------------------
                                // Título de la tabla
                                $pdf->Cell(180, 3, '', 0, 1);
                                $pdf->SetFont($font, 'B', 12);
                                $pdf->Cell(180, 1, 'Facturas', 0, 1, 'L');
                                //  cabecera de tabla
                                $pdf->SetFont($font, 'B', 10);
                                $pdf->Cell(190, 2, '', 0, 1);
                                $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                                $pdf->Cell(20, 5, utf8_decode('Tipo'), 0, 0, 'C');
                                $pdf->Cell(30, 5, utf8_decode('Número'), 0, 0, 'C');
                                $pdf->Cell(20, 5, utf8_decode('Fecha'), 0, 0, 'C');
                                $pdf->Cell(30, 5, utf8_decode('Importe'), 0, 0,'C');
                                $pdf->Cell(30, 5, utf8_decode('N° Cbte'), 0, 0, 'C');
                                $pdf->Cell(30, 5, utf8_decode('CAE'), 0, 0, 'C');
                                $pdf->Cell(30, 5, utf8_decode('Vto CAE'), 0, 0, 'C');
                                $pdf->SetY($pdf->GetY()+5);
                                $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                                // establece la $row en cero para empezar la cuenta nuevamente en la siguiente página
                                $row = 0; 
                            }
                        }
                        $pdf->SetY($pdf->GetY() + 5);
                        $elementosImpresos++;
                    }
                    
                    // imprime los recibos
                    if($impresiones['recibos']){
                        $pdf->AddPage('portrait');
                        $pagina_actual++;
                        //  establece valor de tipografia
                        $pdf->SetFont($font, '', 10);
                        //  código de barras
                        get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
                        get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);
                        //  cabecera
                        $pdf->Image($data['logo'], 15, 16, 40, 0, 'PNG');
                        $pdf->Cell(60, 14, '', 0, 0);
                        $pdf->Cell(60, 30, $numeroBarra, 0, 0, 'C');
                        $pdf->Cell(60, 5, utf8_decode('Fecha Impresión') . ': ' . $data['fec_impresion'], 0, 2, 'R');
                        $pdf->Cell(60, 5, utf8_decode('Fecha Ingreso') . ': ' . $data['fec_ingreso'], 0, 2, 'R');
                        $pdf->Cell(60, 5, utf8_decode('Página') . ' ' . $pagina_actual . ' de ' . $total_paginas, 0, 1, 'R');
                        $pdf->Cell(180, 6, '', 0, 1, 'R');  // dejamos un espacio de 6 mm hasta la siguiente línea
                        $pdf->SetFont($font, 'B', 16);
                        $pdf->Cell(60, 3, '', 0, 0, 'C');
                        $pdf->Cell(60, 5, mb_strtoupper(utf8_decode($data['n_persona']), 'ISO-8859-1'), 0, 0, 'C');
                        $pdf->Cell(60, 6, '', 0, 1, 'R'); // dejamos un espacio de 6 mm hasta la siguiente línea
                        //  establece valor de tipografia
                        $pdf->SetFont($font, '', 8);
                        //  primera línea
                        $pdf->Cell(60, 5, utf8_decode('Número de Afiliado: ') . $data['n_afiliado'], 0, 0);
                        $pdf->Cell(60, 5, utf8_decode('Convenio: ') . $data['n_convenio'], 0, 0, 'C');
                        $pdf->Cell(60, 5, utf8_decode('Plan: ') . utf8_decode($data['n_plan']), 0, 1, 'R');
                        //  segunda línea
                        $pdf->Cell(60, 5, utf8_decode('Documento: ') . $data['abrev_tipo_doc'].' '.$data['nro_doc'], 0, 0, 'L');
                        $pdf->Cell(60, 5, utf8_decode($data['n_afip']), 0, 0, 'C');
                        $pdf->Cell(60, 5, utf8_decode('Localidad: ') . utf8_decode($data['n_localidad']), 0, 1, 'R');

                        //  TABLA RECIBOS --------------------------------------------------------------
                        // Título de la tabla
                        $pdf->Cell(180, 3, '', 0, 1);
                        $pdf->SetFont($font, 'B', 12);
                        $pdf->Cell(180, 1, 'Recibos', 0, 1, 'L');
                        //  cabecera de tabla
                        $pdf->SetFont($font, 'B', 10);
                        $pdf->Cell(190, 2, '', 0, 1);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                        $pdf->Cell(50, 5, utf8_decode('Número'), 0, 0, 'C'); // Número de recibo
                        $pdf->Cell(50, 5, utf8_decode('Fecha Recibo'), 0, 0, 'C'); // Fecha del recibo
                        $pdf->Cell(40, 5, utf8_decode('Importe'), 0, 0, 'R'); // Importe del recibo
                        $pdf->Cell(50, 5, utf8_decode('Fecha Carga'), 0, 0, 'C'); // Fecha de carga
                        $pdf->SetY($pdf->GetY()+5);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
        
                        //  por cada recibo
                        $actual_y = 5;
                        $row = 0;
                        $y = 0;
                        foreach ($recibos as $recibo) {
                            $pdf->SetFont($font, '', 8);
                            $pdf->Cell(50, 5, $recibo->nro_recibo, 0, 0, 'C'); // Número de recibo
                            $fecha = Carbon::parse($recibo->fec_recibo)->format('d/m/Y');
                            $pdf->Cell(50, 5, $fecha, 0, 0, 'C'); // Fecha del recibo
                            $importe = number_format($recibo->imp_recibo, 2, ',', '.');
                            $pdf->Cell(40, 5, '$'.$importe, 0, 0, 'R'); // Importe del recibo
                            $fecha_carga = Carbon::parse($recibo->fec_carga)->format('d/m/Y');
                            $pdf->Cell(50, 5, $fecha_carga, 0, 0, 'C'); // Fecha de carga
                            $pdf->SetY($pdf->getY() + 5);
                            $pdf->Cell(190, 0.001, '', 1, 1, 'C'); // línea
                            $row++;

                            if($row > $lineas_por_pagina){ // si tiene mas de 40 filas generamos una nueva página
                                $pdf->AddPage('portrait');
                                $pagina_actual++;
                                $pdf->SetFont($font, '', 10);
                                //  código de barras
                                get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
                                get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);
                                //  cabecera
                                $pdf->Image($data['logo'], 15, 16, 40, 0, 'PNG');
                                $pdf->Cell(60, 14, '', 0, 0);
                                $pdf->Cell(60, 30, $numeroBarra, 0, 0, 'C');
                                $pdf->Cell(60, 5, utf8_decode('Fecha Impresión') . ': ' . $data['fec_impresion'], 0, 2, 'R');
                                $pdf->Cell(60, 5, utf8_decode('Fecha Ingreso') . ': ' . $data['fec_ingreso'], 0, 2, 'R');
                                $pdf->Cell(60, 5, utf8_decode('Página') . ' ' . $pagina_actual . ' de ' . $total_paginas, 0, 1, 'R');
                                // Nombre del afiliado
                                $pdf->Cell(180, 6, '', 0, 1, 'R');  // dejamos un espacio de 6 mm hasta la siguiente línea
                                $pdf->SetFont($font, 'B', 16);
                                $pdf->Cell(60, 3, '', 0, 0, 'C');
                                $pdf->Cell(60, 5, mb_strtoupper(utf8_decode($data['n_persona']), 'ISO-8859-1'), 0, 0, 'C');
                                $pdf->Cell(60, 6, '', 0, 1, 'R'); // dejamos un espacio de 6 mm hasta la siguiente línea
                                //  establece valor de tipografia
                                $pdf->SetFont($font, '', 8);
                                //  primera línea
                                $pdf->Cell(60, 5, utf8_decode('Número de Afiliado: ') . $data['n_afiliado'], 0, 0, 'L');
                                $pdf->Cell(60, 5, utf8_decode('Convenio: ') . $data['n_convenio'], 0, 0, 'C');
                                $pdf->Cell(60, 5, utf8_decode('Plan: ') . utf8_decode($data['n_plan']), 0, 1, 'R');
                                //  segunda línea
                                $pdf->Cell(60, 5, utf8_decode('Documento: ') . $data['abrev_tipo_doc'].' '.$data['nro_doc'], 0, 0, 'L');
                                $pdf->Cell(60, 5, utf8_decode($data['n_afip']), 0, 0, 'C');
                                $pdf->Cell(60, 5, utf8_decode('Localidad: ') . utf8_decode($data['n_localidad']), 0, 1, 'R');
        
                                //  TABLA RECIBOS --------------------------------------------------------------
                                // Título de la tabla
                                $pdf->Cell(180, 3, '', 0, 1);
                                $pdf->SetFont($font, 'B', 12);
                                $pdf->Cell(180, 1, 'Recibos', 0, 1, 'L');
                                //  cabecera de tabla
                                $pdf->SetFont($font, 'B', 10);
                                $pdf->Cell(190, 2, '', 0, 1);
                                $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                                $pdf->Cell(50, 5, utf8_decode('Número'), 0, 0, 'C'); // Número de recibo
                                $pdf->Cell(50, 5, utf8_decode('Fecha Recibo'), 0, 0, 'C'); // Fecha del recibo
                                $pdf->Cell(40, 5, utf8_decode('Importe'), 0, 0, 'C'); // Importe del recibo
                                $pdf->Cell(50, 5, utf8_decode('Fecha Carga'), 0, 0, 'C'); // Fecha de carga
                                $pdf->SetY($pdf->GetY()+5);
                                $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                                // establece la $row en cero para empezar la cuenta nuevamente en la siguiente página
                                $row = 0; 
                                $actual_y = $pdf->GetY();
                            }
                        }
                        $pdf->SetY($pdf->GetY() + 5);
                        $elementosImpresos++;
                    }
                    //  ====================================================================
                    
                    //  -------------------
                    if ($accion == 'enviar') {  // almacena el archivo en una ubicación específica y devuelve el path
                        $file_path = env('STORAGE_PATH').'/reportes/informes_afiliados/';
                        $filename = 'estado-cuenta-afiliado-'.$data['n_afiliado'].'.pdf';
                        $file = $file_path.$filename;
                        $extras['path'] = $file;
                        $pdf->Output($file, "F");
                        $data = $filename;
                        $status = 'ok';
                        $count = 1;
                        $errors = ['No hay errores'];
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
                            'logged_user' => $logged_user != null ? $logged_user : null,
                            'extras' => $extras
                        ]);
                    }else{ // devuelve el archivo almacenado
                        $file_path = env('STORAGE_PATH').'/reportes/informes_afiliados/';
                        $filename = 'estado-cuenta-afiliado-' . $data['n_afiliado'] . '.pdf';
                        $file = $file_path.$filename;
                        $pdf->Output($file, "F");
                        return FacadeResponse::make(Storage::disk('informes_afiliados')->get($filename), 200, [
                            'Content-Type' => 'application/pdf',
                            'Content-Disposition' => 'inline; ' . $filename,
                        ]);
                    }
                    //  -------------------
                }

            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($permiso_requerido);
                $count  = -1;
                $data = null;
                array_push($errors, 'Error de permisos. '.$message);
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => -2,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
            }
        } catch (\Throwable $th) {
            array_push($errors, 'Line: '.$th->getLine().' Code: '.$th->getCode().' Error: '.$th->getMessage());
            return response()->json([
                'status' => 'fail',
                'count' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => null,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user,
            ]);
        }
    }
}