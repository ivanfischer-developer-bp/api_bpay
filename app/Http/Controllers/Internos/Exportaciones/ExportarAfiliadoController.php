<?php

namespace App\Http\Controllers\Internos\Exportaciones;

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

class ExportarAfiliadoController extends ConexionSpController
{
    /**
     * Exporta a pdf datos de un afiliado: 
     * consumos, internaciones, terapeuticas especiales, grupos familiares, domicilios, etc
     * dependiendo de lo solicitado en el request
     */
    public function exportar_datos_afiliado(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/afiliaciones/afiliado/exportar-datos',
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
            $id_afiliado = request('id_afiliado');
            // crea el array de elementos a imprimir
            $elementos_a_exportar = [
                'consumos' => request('consumos') == 'true' ? true : false, // == 1 ? true : false,
                'consumos_liquidados' => request('consumos_liquidados') == 'true' ? true : false, //1 ? true : false,
                'domicilios' => request('domicilios') == 'true' ? true : false, // == 1 ? true : false,
                'internaciones' => request('internaciones') == 'true' ? true : false, // == 1 ? true : false,
                'patologias' => request('patologias') == 'true' ? true : false, // == 1 ? true : false,
                'grupos' => request('grupos') == 'true' ? true : false // == 1 ? true : false
            ];
            $opciones = [
                'fecha_desde' => request('fecha_desde') != null && request('fecha_desde') != '' ? Carbon::parse(request('fecha_desde'))->format('Ymd'): null,
                'fecha_hasta' => request('fecha_hasta') != null && request('fecha_hasta') != '' ? Carbon::parse(request('fecha_hasta'))->format('Ymd') : null,
                'incluir_prestaciones_liquidadas' => request('incluir_prestaciones_liquidadas')  == 'true' ? true : false,
                'grupo_familiar_completo' => request('grupo_familiar_completo') == 'true' ? true : false
            ];

            $this->params = [
                'id_afiliado' => $id_afiliado,
                'accion' => $accion,
                'consumos' => request('consumos'),
                'consumos_liquidados' => request('consumos_liquidados'),
                'domicilios' => request('domicilios'),
                'internaciones' => request('internaciones'),
                'patologias' => request('patologias'),
                'grupos' => request('grupos'),
                'fecha_desde' => request('fecha_desde'),
                'fecha_hasta' => request('fecha_hasta'),
                'incluir_prestaciones_liquidadas' => request('incluir_prestaciones_liquidadas')
            ];
            
            // return $this->params;
            // calcular el total de hojas a imprimir
            $lineas_por_pagina = 37;
            $total_paginas = 0;
            $pagina_actual = 0;
            $total_lineas_consumos = 0;
            $total_lineas_consumos_liquidados = 0;
            $total_lineas_domicilios = 0;
            $total_lineas_patologias = 0;
            $total_lineas_internaciones = 0;
            $total_lineas_grupos = 0;
            $total_paginas_consumos = 0;
            $total_paginas_consumos_liquidados = 0;
            $total_paginas_domicilios = 0;
            $total_paginas_patologias = 0;
            $total_paginas_internaciones = 0;
            $total_paginas_grupos = 0;
            
            $afiliado = null;
            if($user->hasPermissionTo('exportar datos')){
                if (!empty($id_afiliado)) {
                    //  obtiene el afiliado
                    array_push($extras['sps'], ['sp_afiliado_Select' => ['p_id_afiliado' => $this->params['id_afiliado']]]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_afiliado_Select', ['p_id_afiliado' => $this->params['id_afiliado']]));
                    $afiliado = $this->ejecutar_sp_directo('afiliacion', 'sp_afiliado_Select', ['p_id_afiliado' => $this->params['id_afiliado']]);
                    array_push($extras['responses'], ['sp_afiliado_Select' => $afiliado]);
                    // dd('afiliado', $afiliado);
                    if ($afiliado) {
                        //  obtiene el primer registro (el único)
                        $afiliado = $afiliado[0];
                    }
                }
                //  obtiene los datos
                // $afv = new Afiliado('validacion');
                // $afa = new Afiliado('afiliacion');

                // ejecuta los sp en funcion del array
                if($elementos_a_exportar['consumos']){
                    
                    $params = [
                        'tipo_documento' => $afiliado->abrev_tipo_doc,
                        'numero_documento' => $afiliado->nro_doc
                    ];
                    // descomentar cuando esté listo el sp
                    if(!empty($opciones) && isset($opciones['fecha_desde']) && $opciones['fecha_desde'] != null){
                        $params['fecha_desde'] = $opciones['fecha_desde'];
                    }
                    if(!empty($opciones) && isset($opciones['fecha_hasta']) && $opciones['fecha_hasta'] != null){
                        $params['fecha_hasta'] = $opciones['fecha_hasta'];
                    }
                    if(!empty($opciones) && isset($opciones['incluir_prestaciones_liquidadas']) && $opciones['incluir_prestaciones_liquidadas'] != null){
                        $params['incluir_prestaciones_liquidadas'] = $opciones['incluir_prestaciones_liquidadas'];
                    }
                    array_push($extras['sps'], ['AWEB_TraerConsumos' => $params]);
                    array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerConsumos', $params));
                    $consumos = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerConsumos', $params);
                    array_push($extras['responses'], ['AWEB_TraerConsumos' => $consumos]);
                    // return response()->json(['consumos' => $consumos]);
                    foreach($consumos as $consumo){
                        // a lo siguiente lo comentamos porque no vamos a tener más
                        // de una línea por columna
                        // if(strlen(utf8_decode(trim($consumo->practica)))>37 
                        //     || strlen(utf8_decode(trim($consumo->efector)))>20){
                        //     $total_lineas_consumos+=2;
                        // }else{
                            $total_lineas_consumos++;
                        // }
                    }
                    $total_paginas_consumos = intval(round(($total_lineas_consumos/$lineas_por_pagina), 0, PHP_ROUND_HALF_UP));
                    $total_paginas_consumos = $total_paginas_consumos > 1 ? $total_paginas_consumos : 1;
                }

                if($elementos_a_exportar['consumos_liquidados']){
                    $params = [
                        'tipo_documento' => $afiliado->abrev_tipo_doc,
                        'numero_documento' => $afiliado->nro_doc
                    ];
                    // descomentar cuando esté listo el sp
                    if(!empty($opciones) && isset($opciones['fecha_desde']) && $opciones['fecha_desde'] != null){
                        $params['fecha_desde'] = $opciones['fecha_desde'];
                    }
                    if(!empty($opciones) && isset($opciones['fecha_hasta']) && $opciones['fecha_hasta'] != null){
                        $params['fecha_hasta'] = $opciones['fecha_hasta'];
                    }
                    if(!empty($opciones) && isset($opciones['grupo_familiar_completo']) && $opciones['grupo_familiar_completo'] != null){
                        $params['grupo_familiar'] = $opciones['grupo_familiar_completo'];
                    }
                    // dd('opciones', $opciones);
                    // dd('params', $params);
                    array_push($extras['sps'], ['AWEB_TraerConsumosLiquidados' => $params]);
                    array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerConsumosLiquidados', $params));
                    $consumos_liquidados = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerConsumosLiquidados', $params);
                    array_push($extras['responses'], ['AWEB_TraerConsumos' => $consumos_liquidados]);
                  
                    foreach($consumos_liquidados as $consumo){
                        // a lo siguiente lo comentamos porque no vamos a tener más
                        // de una línea por columna
                        // if(strlen(utf8_decode(trim($consumo->practica)))>37 
                        //     || strlen(utf8_decode(trim($consumo->efector)))>20){
                        //     $total_lineas_consumos+=2;
                        // }else{
                            $total_lineas_consumos_liquidados++;
                        // }
                    }
                    $total_paginas_consumos_liquidados = intval(round(($total_lineas_consumos_liquidados/$lineas_por_pagina), 0, PHP_ROUND_HALF_UP));
                    $total_paginas_consumos_liquidados = $total_paginas_consumos_liquidados > 1 ? $total_paginas_consumos_liquidados : 1;
                }

                if($elementos_a_exportar['internaciones']){
                    array_push($extras['sps'], ['AWEB_TraerInternaciones' => [
                        'doc_tipo' => $afiliado->abrev_tipo_doc,
                        'doc_numero' => $afiliado->nro_doc
                    ]]);
                    array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerInternaciones', [
                        'doc_tipo' => $afiliado->abrev_tipo_doc,
                        'doc_numero' => $afiliado->nro_doc
                    ]));
                    $internaciones = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerInternaciones', [
                        'doc_tipo' => $afiliado->abrev_tipo_doc,
                        'doc_numero' => $afiliado->nro_doc
                    ]);
                    array_push($extras['responses'], ['AWEB_TraerInternaciones' => $internaciones]);
                    foreach($internaciones as $internacion){
                        if(strlen(utf8_decode(trim($internacion->prescriptor)))>20 
                            || strlen(utf8_decode(trim($internacion->prescriptor)))>19){
                            $total_lineas_internaciones+=2;
                        }else{
                            $total_lineas_internaciones++;
                        }
                    }
                    $total_paginas_internaciones = intval(round(($total_lineas_internaciones/$lineas_por_pagina), 0, PHP_ROUND_HALF_UP));
                    $total_paginas_internaciones = $total_paginas_internaciones > 1 ? $total_paginas_internaciones : 1;
                }
                
                if($elementos_a_exportar['patologias']){
                    array_push($extras['sps'], ['sp_persona_patologia_select' => [
                        'p_id_persona' => $afiliado->PerId
                    ]]);
                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_persona_patologia_select', [
                        'p_id_persona' => $afiliado->PerId
                    ]));
                    $patologias = $this->ejecutar_sp_directo('afiliacion', 'sp_persona_patologia_select', [
                        'p_id_persona' => $afiliado->PerId
                    ]);
                    array_push($extras['responses'], ['sp_persona_patologia_select' => $patologias]);
                    foreach($patologias as $patologia){
                        if(strlen(utf8_decode(trim($patologia->n_patologia)))>40 
                            || strlen(utf8_decode(trim($patologia->n_cie_item)))>50
                            || strlen(utf8_decode(trim($patologia->n_capitulo)))>50
                            || strlen(utf8_decode(trim($patologia->n_sub_capitulo)))>50){
                            $total_lineas_patologias+=2;
                        }else{
                            $total_lineas_patologias++;
                        }
                    }
                    $total_paginas_patologias = intval(round(($total_lineas_patologias/$lineas_por_pagina), 0, PHP_ROUND_HALF_UP));
                    $total_paginas_patologias = $total_paginas_patologias > 1 ? $total_paginas_patologias : 1;
                }
                
                if($elementos_a_exportar['grupos']){
                    array_push($extras['sps'], [
                        'sp_grupo_afiliado_Select' => [
                            'p_id_grupo' => $afiliado->id_grupo,
                            'p_activos' => $afiliado->activo
                        ], 
                        'sp_grupo_domicilio_select' => [
                            'p_id_grupo' => $afiliado->id_grupo,
                        ], 
                        'sp_grupo_contacto_select' =>  [
                            'p_id_grupo' => $afiliado->id_grupo,
                        ]
                    ]);

                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_grupo_afiliado_Select', [
                        'p_id_grupo' => $afiliado->id_grupo,
                        'p_activos' => $afiliado->activo
                    ])); 
                    $grupos = $this->ejecutar_sp_directo('afiliacion', 'sp_grupo_afiliado_Select', [
                        'p_id_grupo' => $afiliado->id_grupo,
                        'p_activos' => $afiliado->activo
                    ]);
                    array_push($extras['responses'], ['sp_grupo_afiliado_Select' => $grupos]);

                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_grupo_domicilio_select', [
                        'p_id_grupo' => $afiliado->id_grupo,
                    ])); 
                    $domicilios = $this->ejecutar_sp_directo('afiliacion', 'sp_grupo_domicilio_select', [
                        'p_id_grupo' => $afiliado->id_grupo,
                    ]);
                    array_push($extras['responses'], ['sp_grupo_domicilio_select' => $domicilios]);

                    array_push($extras['queries'], $this->get_query('afiliacion', 'sp_grupo_contacto_select', [
                        'p_id_grupo' => $afiliado->id_grupo,
                    ]));
                    $contactos = $this->ejecutar_sp_directo('afiliacion', 'sp_grupo_contacto_select', [
                        'p_id_grupo' => $afiliado->id_grupo,
                    ]);
                    array_push($extras['responses'], ['sp_grupo_contacto_select' => $contactos]);
                    foreach($grupos as $grupo){
                        if(strlen(utf8_decode(trim($grupo->n_plan)))>20 
                            || strlen(utf8_decode(trim($grupo->n_convenio)))>20){
                            $total_lineas_grupos+=2;
                        }else{
                            $total_lineas_grupos++;
                        }
                    }
                    foreach($domicilios as $domicilio){
                        if(strlen(utf8_decode(trim($domicilio->pertenece)))>25 
                            || strlen(utf8_decode(trim($domicilio->calle)))>20
                            || strlen(utf8_decode(trim($domicilio->n_localidad)))>20
                            || strlen(utf8_decode(trim($domicilio->n_provincia)))>20){
                            $total_lineas_grupos+=2;
                        }else{
                            $total_lineas_grupos++;
                        }
                    }
                    foreach($contactos as $contacto){
                        if(strlen(utf8_decode(trim($contacto->pertenece)))>25 
                            || strlen(utf8_decode(trim($contacto->n_contacto)))>20){
                            $total_lineas_grupos+=2;
                        }else{
                            $total_lineas_grupos++;
                        }
                    }
                    
                    $total_paginas_grupos = intval(round(($total_lineas_grupos/$lineas_por_pagina), 0, PHP_ROUND_HALF_UP));
                    $total_paginas_grupos = $total_paginas_grupos > 1 ? $total_paginas_grupos : 1;
                }

                // suma la cantidad de páginas a imprimir
                $total_paginas = $total_paginas_consumos + $total_paginas_consumos_liquidados + $total_paginas_patologias + $total_paginas_internaciones + $total_paginas_grupos;
            
                // determina la cantidad de elementos a imprimir
                $contadorImpresiones = 0;
                foreach($elementos_a_exportar as $impresion){
                    if($impresion){
                        $contadorImpresiones++;
                    }
                }

                if ($afiliado) {
                    //  genera los datos para el PDF
                    $numeroBarra = $afiliado->abrev_tipo_doc.$afiliado->nro_doc;
                    $data = [
                        'logo' => env('IMAGE_PATH').env('LOGO'),
                        'cuit' => $afiliado->cuit,
                        'es_titular' => $afiliado->isTitular == 1 ? 'Si' : 'No',
                        'n_persona' => $afiliado->n_persona,
                        'n_afiliado' => $afiliado->n_afiliado,
                        'abrev_tipo_doc' => $afiliado->abrev_tipo_doc,
                        'nro_doc' => $afiliado->nro_doc,
                        'sexo' => $afiliado->sexo == 'M' ? 'Masculino' : 'Femenino',
                        'fec_ingreso' => $afiliado->fec_ingreso,
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
                        // $pdf->Image(get_path_imagenes($data['logo']), 15, 16, 30, 7, 'PNG');
                        // $pdf->Image(env(env('IMAGE_PATH').env('LOGO')), 15, 16, 30, 7, 'PNG');
                        // $pdf->Image(env('LOGO_PATH), 15, 16, 30, 7, 'PNG');
                        $pdf->Image(storage_path('app/public/images/logo.png'), 15, 16, 30, 7, 'PNG');
                        
                        
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

                    // imprime los consumos
                    if($elementos_a_exportar['consumos']){
                        $pdf->AddPage('portrait');
                        $pagina_actual++;
                        //  establece valor de tipografia
                        $pdf->SetFont($font, '', 10);
                        
                        //  código de barras
                        get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
                        get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);
                        
                        //  cabecera
                        // $pdf->Image(get_path_imagenes($data['logo']), 15, 16, 50, 6, 'PNG');
                        $pdf->Image(storage_path('app/public/images/logo.png'), 15, 16, 50, 6, 'PNG');
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

                        //  TABLA CONSUMOS --------------------------------------------------------------
                        // Título de la tabla
                        $pdf->Cell(180, 3, '', 0, 1);
                        $pdf->SetFont($font, 'B', 12);
                        $pdf->Cell(180, 1, 'Consumos', 0, 1, 'L');
                        //  cabecera de tabla
                        $pdf->SetFont($font, 'B', 10);
                        $pdf->Cell(190, 2, '', 0, 1);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                        $pdf->Cell(30, 7, utf8_decode('Estado'), 0, 0, 'L');
                        $pdf->Cell(20, 7, utf8_decode('#'), 0, 0, 'C');
                        $pdf->Cell(20, 7, utf8_decode('Fecha'), 0, 0, 'C');
                        $pdf->Cell(20, 7, utf8_decode('Código'), 0, 0, 'L');
                        $pdf->Cell(50, 7, utf8_decode('Descripción'), 0, 0, 'L');
                        $pdf->Cell(10, 7, utf8_decode('Cant.'), 0, 0, 'R');
                        $pdf->Cell(40, 7, utf8_decode('Efector'), 0, 0, 'L');
                        $pdf->SetY($pdf->GetY() + 7);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
        
                        //  por cada consumo
                        $row = 0;
                        $y = 0;
                        foreach ($consumos as $consumo) {
                            
                            $pdf->SetFont($font, '', 8);
                            $pdf->Cell(30, 5, substr(utf8_decode(trim($consumo->n_estado)), 0, 13), 0, 0, 'L'); // estado
                            $new_y = $pdf->GetY(); // devuelve la ordenada de la posición actual
                            //  formatea el número
                            if(property_exists($consumo, 'id_sucursal') && property_exists($consumo, 'autorizacion')){
                                $numero_formateado = get_agregar_ceros($consumo->id_sucursal, 3) . get_agregar_ceros($consumo->autorizacion, 8);
                            }else{
                                $numero_formateado = '';
                            }
                            $pdf->Cell(20, 5, $numero_formateado, 0, 0, 'C'); // #
                            if(property_exists($consumo, 'fecha')){
                                $pdf->Cell(20, 5, $consumo->fecha, 0, 0, 'C'); // fecha
                            }else{
                                $pdf->Cell(20, 5, '          ', 0, 0, 'C'); // fecha
                            }
                            // $pdf->Cell(20, 5, Carbon::parse($consumo->fecha_ingreso)->format('d/m/Y'), 0, 0, 'L'); // fecha ingreso
                            if(property_exists($consumo, 'codigo_nomenclador')){
                                if(strlen($consumo->codigo_nomenclador)>9){
                                    $pdf->SetFont($font, '', 6);
                                    $pdf->Cell(20, 5, $consumo->codigo_nomenclador, 0, 0, 'L'); // código
                                    $pdf->SetFont($font, '', 8);
                                }else{
                                    $pdf->Cell(20, 5, $consumo->codigo_nomenclador, 0, 0, 'L'); // código
                                }
                            }else{
                                $pdf->Cell(20, 5, '        ', 0, 0, 'L'); // código
                            }
                            // descripción
                            $pdf->SetFont($font, '', 6);
                            if(property_exists($consumo, 'practica')){
                                if(strlen(utf8_decode(trim($consumo->practica)))>40){
                                    // si es mayor a 54 caracteres (que son los que entran en dos líneas)
                                    // lo cortamos a los 54 caracteres y le agregamos tre puntitos para indicar 
                                    // que hay mas información que no se muestra
                                    $descripcion = substr(utf8_decode(trim($consumo->practica)), 0, 40);
                                    $descripcion = $descripcion.'...';
                                }else{
                                    $descripcion = utf8_decode(trim($consumo->practica));
                                }
                            }else{
                                $descripcion = '          ';
                            }
                            $pdf->Cell(50, 5, $descripcion, 0, 0, 'L');
                            $pdf->SetFont($font, '', 8);
                            if(property_exists($consumo, 'cantidad')){
                                $pdf->Cell(10, 5, $consumo->cantidad, 0, 0, 'R');  // cantidad
                            }else{
                                $pdf->Cell(10, 5, '  ', 0, 0, 'R');  // cantidad
                            }
                            // efector
                            $pdf->SetFont($font, '', 6);
                            if(property_exists($consumo, 'efector')){
                                if(strlen(utf8_decode(trim($consumo->efector)))>30){
                                    // si efector es mayor a 30 lo cortamos en 30 y le agregamos
                                    // tres puntitos para indicar que hay más información que no se muestra
                                    $efector = substr(utf8_decode(trim($consumo->efector)), 0, 30);
                                    $efector = $efector.'...';
                                }else{
                                    $efector = utf8_decode(trim($consumo->efector));
                                }
                            }else{
                                $efector = '         ';
                            }
                            $pdf->Cell(40, 5, $efector, 0, 'L'); // efector
                            
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
                                // $pdf->Image(get_path_imagenes($data['logo']), 15, 16, 50, 6, 'PNG');
                                // $pdf->Image(env('LOGO_PATH'), 15, 16, 50, 6, 'PNG');
                                $pdf->Image(storage_path('app/public/images/logo.png'), 15, 16, 50, 0, 'PNG');
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
        
                                //  TABLA CONSUMOS --------------------------------------------------------------
                                // Título de la tabla
                                $pdf->Cell(180, 3, '', 0, 1);
                                $pdf->SetFont($font, 'B', 12);
                                $pdf->Cell(180, 1, 'Consumos', 0, 1, 'L');
                                //  cabecera de tabla
                                $pdf->SetFont($font, 'B', 10);
                                $pdf->Cell(190, 2, '', 0, 1);
                                $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                                $pdf->Cell(30, 7, utf8_decode('Estado'), 0, 0, 'L');
                                $pdf->Cell(20, 7, utf8_decode('#'), 0, 0, 'C');
                                $pdf->Cell(20, 7, utf8_decode('Fecha'), 0, 0, 'C');
                                $pdf->Cell(20, 7, utf8_decode('Código'), 0, 0, 'L');
                                $pdf->Cell(50, 7, utf8_decode('Descripción'), 0, 0, 'L');
                                $pdf->Cell(10, 7, utf8_decode('Cant.'), 0, 0, 'R');
                                $pdf->Cell(40, 7, utf8_decode('Efector'), 0, 0, 'L');
                                $pdf->SetY($pdf->GetY() + 7);
                                $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                                // establece la $row en cero para empezar la cuenta nuevamente en la siguiente página
                                $row = 0; 
                                $actual_y = $pdf->GetY();
                            }
                        }
                        $pdf->SetY($pdf->GetY() + 5);
                        $elementosImpresos++;
                    }

                    // imprime los consumos liquidados
                    if($elementos_a_exportar['consumos_liquidados']){
                        $pdf->AddPage('portrait');
                        $pagina_actual++;
                        //  establece valor de tipografia
                        $pdf->SetFont($font, '', 10);
                        
                        //  código de barras
                        get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
                        get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);
                        
                        //  cabecera
                        // $pdf->Image(get_path_imagenes($data['logo']), 15, 16, 50, 6, 'PNG');
                        $pdf->Image(storage_path('app/public/images/logo.png'), 15, 16, 50, 6, 'PNG');
                        $pdf->Cell(60, 14, '', 0, 0);
                        
                        $pdf->Cell(60, 30, $numeroBarra, 0, 0, 'C');
                        $pdf->Cell(60, 7, utf8_decode('Fecha Impresión') . ': ' . $data['fec_impresion'], 0, 2, 'R');
                        $pdf->Cell(60, 7, utf8_decode('Fecha Ingreso') . ': ' . $data['fec_ingreso'], 0, 2, 'R');
                        $pdf->Cell(60, 7, utf8_decode('Página') . ' ' . $pagina_actual . ' de ' . $total_paginas, 0, 1, 'R');
                        
                        $pdf->Cell(180, 6, '', 0, 1, 'R');  // dejamos un espacio de 6 mm hasta la siguiente línea
                        $pdf->SetFont($font, 'B', 16);
                        $pdf->Cell(60, 3, '', 0, 0, 'C');
                        $pdf->Cell(60, 7, mb_strtoupper(utf8_decode($data['n_persona']), 'ISO-8859-1'), 0, 0, 'C');
                        $pdf->Cell(60, 6, '', 0, 1, 'R'); // dejamos un espacio de 6 mm hasta la siguiente línea

                        //  establece valor de tipografia
                        $pdf->SetFont($font, '', 8);

                        //  primera línea
                        $pdf->Cell(60, 7, utf8_decode('Número de Afiliado: ') . $data['n_afiliado'], 0, 0, 'L');
                        $pdf->Cell(60, 7, utf8_decode('Convenio: ') . $data['n_convenio'], 0, 0, 'C');
                        $pdf->Cell(60, 7, utf8_decode('Plan: ') . utf8_decode($data['n_plan']), 0, 1, 'R');

                        //  segunda línea
                        $pdf->Cell(60, 7, utf8_decode('Documento: ') . $data['abrev_tipo_doc'].' '.$data['nro_doc'], 0, 0, 'L');
                        $pdf->Cell(60, 7, utf8_decode($data['n_afip']), 0, 0, 'C');
                        $pdf->Cell(60, 7, utf8_decode('Localidad: ') . utf8_decode($data['n_localidad']), 0, 1, 'R');

                        //  TABLA CONSUMOS LIQUIDADOS --------------------------------------------------------------
                        // Título de la tabla
                        $pdf->SetFont($font, '', 6);
                        $pdf->Cell(190, 3, '', 0, 1);
                        $pdf->Cell(190, 3, utf8_decode('C. N.: Código Nomenclador'), 0, 1);
                        $pdf->Cell(190, 3, utf8_decode('C. N. C.: Código Nomenclador Convenido'), 0, 1);
                        $pdf->Cell(180, 3, '', 0, 1);
                        $pdf->SetFont($font, 'B', 12);
                        $pdf->Cell(180, 1, 'Consumos Liquidados', 0, 1, 'L');
                        //  cabecera de tabla
                        $pdf->SetFont($font, '', 8);
                        $pdf->Cell(190, 2, '', 0, 1);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                        $pdf->Cell(32, 5, utf8_decode('Razón Social'), 0, 0, 'L');
                        $pdf->Cell(27, 5, utf8_decode('Factura'), 0, 0, 'C');
                        $pdf->Cell(13, 5, utf8_decode('Fecha'), 0, 0, 'C');
                        // $pdf->MultiCell(20, 5, utf8_decode('Fecha Ingreso'), 0, 'L');
                        // $pdf->SetY($pdf->GetY() - 10);
                        // $pdf->SetX($pdf->GetX() + 70);
                        // $pdf->Cell(15, 8, utf8_decode('Fecha Ingreso'), 0, 0, 'C');
                        $pdf->Cell(7, 5, utf8_decode('Cant'), 0, 0, 'C');
                        $pdf->Cell(12, 5, utf8_decode('C. N.'), 0, 0, 'R');
                        $pdf->Cell(12, 5, utf8_decode('C. N. C.'), 0, 0, 'R');
                        $pdf->Cell(30, 5, utf8_decode('Descripción'), 0, 0, 'L');
                        $pdf->Cell(30, 5, utf8_decode('Descripción Conven.'), 0, 0, 'L');
                        $pdf->Cell(14, 5, utf8_decode('Val. Unit.'), 0, 0, 'C');
                        $pdf->Cell(14, 5, utf8_decode('Imp. Conv.'), 0, 0, '');
                        $pdf->SetY($pdf->GetY() + 5);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
        
                        //  por cada consumo
                        $row = 0;
                        $y = 0;
                        foreach ($consumos_liquidados as $consumo) {
                            $pdf->SetFont($font, '', 6);
                            // $pdf->Cell(32, 5, substr(utf8_decode(trim($consumo->razon_social)), 0, 25), 0, 0, 'L'); // razon_social
                            // $pdf->MultiCell(32, 3, utf8_decode(trim($consumo->razon_social)), 0, 'L');
                            // $pdf->SetY($pdf->GetY() - 6); // devuelve la ordenada de la posición actual
                            // $pdf->SetX($pdf->GetX() + 32);
                            $pdf->Cell(30, 5, ucwords(strtolower(utf8_decode(trim($consumo->razon_social)))), 0, 0, 'L');
                            $pdf->SetFont($font, '', 6);
                            $pdf->Cell(27, 5, $consumo->factura, 0, 0, 'C'); // factura
                            $pdf->Cell(13, 5, Carbon::parse($consumo->fecha)->format('d/m/Y'), 0, 0, 'L'); // fecha
                            $pdf->Cell(7, 5, $consumo->cantidad, 0, 0, 'C'); // fecha
                            $pdf->Cell(12, 5, $consumo->codigo_nomenclador, 0, 0, 'R'); // código_nomenclador
                            $pdf->Cell(12, 5, $consumo->codigo_nomenclador_convenido, 0, 0, 'R'); // código_nomenclador_convenido
                            // descripcion
                            $pdf->SetFont($font, '', 6);
                            if(strlen(utf8_decode(trim($consumo->descripcion)))>40){
                                // si es mayor a 40 caracteres (que son los que entran en dos líneas)
                                // lo cortamos a los 40 caracteres y le agregamos tres puntitos para indicar 
                                // que hay mas información que no se muestra
                                $descripcion = substr(utf8_decode(trim($consumo->descripcion)), 0, 40);
                                $descripcion = $descripcion.'...';
                            }else{
                                $descripcion = utf8_decode(trim($consumo->descripcion));
                            }
                            if(strlen(utf8_decode(trim($consumo->descripcion)))>20){
                                $pdf->MultiCell(30, 3, utf8_decode(trim(ucwords(strtolower($descripcion)))), 0, 'L');
                                $pdf->SetY($pdf->GetY() - 5); // devuelve la ordenada de la posición actual
                                $pdf->SetX($pdf->GetX() + 133);
                            }else{
                                $pdf->Cell(30, 5, ucwords(strtolower($descripcion)), 0, 0, 'L');
                            }
                            // descripcion_convenido
                            if(strlen(utf8_decode(trim($consumo->descripcion_convenido)))>40){
                                // si es mayor a 40 caracteres (que son los que entran en dos líneas)
                                // lo cortamos a los 40 caracteres y le agregamos tre puntitos para indicar 
                                // que hay mas información que no se muestra
                                $descripcion_convenido = substr(utf8_decode(trim($consumo->descripcion_convenido)), 0, 40);
                                $descripcion_convenido = $descripcion_convenido.'...';
                            }else{
                                $descripcion_convenido = utf8_decode(trim($consumo->descripcion_convenido));
                            }
                            if(strlen(utf8_decode(trim($consumo->descripcion_convenido)))>20){
                                $pdf->MultiCell(30, 3, utf8_decode(trim(ucwords(strtolower($descripcion_convenido)))), 0, 'L');
                                // $pdf->SetY($pdf->GetY() - 6); // devuelve la ordenada de la posición actual
                                $pdf->SetX($pdf->GetX() + 163);
                            }else{
                                $pdf->Cell(30, 5, ucwords(strtolower($descripcion_convenido)), 0, 0, 'L');
                            }
                            if($consumo->valor_unitario != null || $consumo->valor_unitario != ''){
                                $pdf->Cell(14, 5, '$'.$consumo->valor_unitario, 0, 0, 'R');  // valor_unitario
                            }else{
                                $pdf->Cell(14, 5, $consumo->valor_unitario, 0, 0, 'R');  // valor_unitario
                            }
                            if($consumo->importe_convenido != null || $consumo->importe_convenido != ''){
                                $pdf->Cell(14, 5, '$'.$consumo->importe_convenido, 0, 0, 'R');  // importe_convenido
                            }else{
                                $pdf->Cell(14, 5, $consumo->importe_convenido, 0, 0, 'R');  // importe_convenido
                            }
        
                            $pdf->SetY($pdf->GetY()+5);
                            $pdf->SetX($pdf->GetX());
                            $pdf->Cell(190, 0.001, '', 1, 1, 'C'); // línea
                            $row++;
                            if($row > $lineas_por_pagina){ // si tiene mas de 37 filas generamos una nueva página
                                $pdf->AddPage('portrait');
                                $pagina_actual++;
                                $pdf->SetFont($font, '', 10);
                                //  código de barras
                                get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
                                get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);
                                //  cabecera
                                // $pdf->Image(get_path_imagenes($data['logo']), 15, 16, 50, 6, 'PNG');
                                $pdf->Image(storage_path('app/public/images/logo.png'), 15, 16, 50, 6, 'PNG');
                                $pdf->Cell(60, 14, '', 0, 0);
                                $pdf->Cell(60, 30, $numeroBarra, 0, 0, 'C');
                                $pdf->Cell(60, 5, utf8_decode('Fecha Impresión') . ': ' . $data['fec_impresion'], 0, 2, 'R');
                                $pdf->Cell(60, 5, utf8_decode('Fecha Ingreso') . ': ' . $data['fec_ingreso'], 0, 2, 'R');
                                $pdf->Cell(60, 5, utf8_decode('Página') . ' ' . $pagina_actual . ' de ' . $total_paginas, 0, 1, 'R');
                                // Nombre del afiliado
                                $pdf->Cell(180, 5, '', 0, 1, 'R');  // dejamos un espacio de 5 mm hasta la siguiente línea
                                $pdf->SetFont($font, 'B', 16);
                                $pdf->Cell(60, 3, '', 0, 0, 'C');
                                $pdf->Cell(60, 5, mb_strtoupper(utf8_decode($data['n_persona']), 'ISO-8859-1'), 0, 0, 'C');
                                $pdf->Cell(60, 5, '', 0, 1, 'R'); // dejamos un espacio de 5 mm hasta la siguiente línea
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
        
                                //  TABLA CONSUMOS --------------------------------------------------------------
                                // Título de la tabla
                                $pdf->SetFont($font, '', 6);
                                $pdf->Cell(190, 3, '', 0, 1);
                                $pdf->Cell(190, 3, utf8_decode('C. N.: Código Nomenclador'), 0, 1);
                                $pdf->Cell(190, 3, utf8_decode('C. N. C.: Código Nomenclador Convenido'), 0, 1);
                                $pdf->Cell(180, 3, '', 0, 1);
                                $pdf->SetFont($font, 'B', 12);
                                $pdf->Cell(180, 1, 'Consumos Liquidados', 0, 1, 'L');
                                //  cabecera de tabla
                                $pdf->SetFont($font, '', 8);
                                $pdf->Cell(190, 2, '', 0, 1);
                                $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                                $pdf->Cell(32, 5, utf8_decode('Razón Social'), 0, 0, 'L');
                                $pdf->Cell(27, 5, utf8_decode('Factura'), 0, 0, 'C');
                                $pdf->Cell(13, 5, utf8_decode('Fecha'), 0, 0, 'C');
                                // $pdf->MultiCell(20, 5, utf8_decode('Fecha Ingreso'), 0, 'L');
                                // $pdf->SetY($pdf->GetY() - 10);
                                // $pdf->SetX($pdf->GetX() + 70);
                                // $pdf->Cell(15, 8, utf8_decode('Fecha Ingreso'), 0, 0, 'C');
                                $pdf->Cell(7, 5, utf8_decode('Cant'), 0, 0, 'C');
                                $pdf->Cell(12, 5, utf8_decode('C. N.'), 0, 0, 'R');
                                $pdf->Cell(12, 5, utf8_decode('C. N. C.'), 0, 0, 'R');
                                $pdf->Cell(30, 5, utf8_decode('Descripción'), 0, 0, 'L');
                                $pdf->Cell(30, 5, utf8_decode('Descripción Conven.'), 0, 0, 'L');
                                $pdf->Cell(14, 5, utf8_decode('Val. Unit.'), 0, 0, 'C');
                                $pdf->Cell(14, 5, utf8_decode('Imp. Conv.'), 0, 0, '');
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
                    
                    // imprime las internaciones
                    if($elementos_a_exportar['internaciones']){
                        $pdf->AddPage('portrait');
                        $pagina_actual++;
                        //  establece valor de tipografia
                        $pdf->SetFont($font, '', 10);
                        //  código de barras
                        get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
                        get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);
                        //  cabecera
                        // $pdf->Image(get_path_imagenes($data['logo']), 15, 16, 30, 7, 'PNG');
                        // $pdf->Image(env('LOGO_PATH'), 15, 16, 30, 7, 'PNG');
                        $pdf->Image(storage_path('app/public/images/logo.png'), 15, 16, 50, 0, 'PNG');
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

                        //  TABLA INTERNACIONES --------------------------------------------------------------
                        // Título de la tabla
                        $pdf->Cell(180, 3, '', 0, 1);
                        $pdf->SetFont($font, 'B', 12);
                        $pdf->Cell(180, 1, 'Internaciones', 0, 1, 'L');
                        //  cabecera de tabla
                        $pdf->SetFont($font, 'B', 10);
                        $pdf->Cell(190, 2, '', 0, 1);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea

                        $pdf->Cell(15, 10, utf8_decode('Estado'), 0, 0, 'C'); // estado
                        $pdf->Cell(15, 10, utf8_decode('#'), 0, 0, 'C'); // numero_internacion con 4 ceros
                        $pdf->MultiCell(15, 5, utf8_decode('Fecha Ingreso'), 0, 'C'); // fecha ingreso
                        $pdf->SetY($pdf->GetY() - 10);
                        $pdf->SetX($pdf->GetX() + 45);
                        $pdf->MultiCell(15, 5, utf8_decode('Fecha Egreso'), 0, 'C'); // fecha egreso
                        $pdf->SetY($pdf->GetY() - 10);
                        $pdf->SetX($pdf->GetX() + 60);
                        $pdf->Cell(30, 10, utf8_decode('Tipo Internación'), 0, 0, 'C'); // tipo internación
                        $pdf->Cell(30, 10, utf8_decode('Prescriptor'), 0, 0, 'C'); // prescriptor
                        $pdf->Cell(30, 10, utf8_decode('Efector'), 0, 0, 'C'); // efector
                        $pdf->Cell(20, 10, utf8_decode('Tipo Alta'), 0, 0, 'C'); //tipo alta
                        $pdf->SetFont($font, 'B', 8);
                        $pdf->MultiCell(20, 5, utf8_decode('Fecha Vencimiento'), 0, 'C'); // fecha vencimiento

                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
        
                        //  por cada internacion
                        $actual_y = 5;
                        $row = 0;
                        $y = 0;
                        foreach ($internaciones as $internacion) {
                            $pdf->SetFont($font, '', 7);
                            $pdf->Cell(15, 5, $internacion->estado, 0, 0, 'C'); // estado
                            $new_y = $pdf->GetY(); // devuelve la ordenada de la posición actual
                            //  formatea el número
                            $numero_formateado = get_agregar_ceros($internacion->numero_internacion, 8);
                            $pdf->Cell(15, 5, $numero_formateado, 0, 0, 'C'); // numero internación
                            $pdf->Cell(15, 5, $internacion->fecha_ingreso, 0, 0, 'C'); // fecha ingreso
                            $pdf->Cell(15, 5, $internacion->fecha_probable_egreso, 0, 0, 'C'); // fecha egreso
                            $pdf->Cell(30, 5, $internacion->tipo_internacion, 0, 0, 'C'); // tipo internacion
                            // prescriptor
                            if(strlen(utf8_decode(trim($internacion->prescriptor)))>40){
                                $pdf->SetFont($font, '', 6);
                                $prescriptor = substr(utf8_decode(trim($internacion->prescriptor)), 0, 40);
                                $prescriptor = $prescriptor.'...';
                                $pdf->SetFont($font, '', 7);
                            }else{
                                $prescriptor = substr(utf8_decode(trim($internacion->prescriptor)), 0, 40);
                            }
                            $pdf->MultiCell(30, 5, $prescriptor, 0, 'L');// prescriptor
                            if(strlen($prescriptor)>19){
                                $next_y = 10;
                                $actual_y = 10;
                                $row++;
                                $y++;
                            }else{
                                $next_y = 5;
                            }
                            $prescriptor_y = $pdf->GetY();
                            $pdf->SetY($prescriptor_y - $next_y);
                            $pdf->SetX($pdf->GetX() + 120);
                            // efector
                            if(strlen(utf8_decode(trim($internacion->efector)))>40){
                                $pdf->SetFont($font, '', 6);
                                $efector = substr(utf8_decode(trim($internacion->efector)), 0, 40);
                                $efector = $efector.'...';
                                $pdf->SetFont($font, '', 7);
                            }else{
                                $efector = substr(utf8_decode(trim($internacion->efector)), 0, 40);
                            }
                            $pdf->MultiCell(30, 5, $efector, 0, 'L');// efector
                            if(strlen($efector)>20){
                                $next_y = 10;
                                $actual_y = 10;
                                $row++;
                                $y++;
                            }else{
                                $next_y = 5;
                            }
                            $efector_y = $pdf->GetY();
                            $pdf->SetY($efector_y - $next_y);
                            $pdf->SetX($pdf->GetX() + 150);
                            $pdf->Cell(20, 5, $internacion->tipo_alta, 0, 0, 'C'); // tipo alta
                            $pdf->Cell(20, 5, $internacion->fecha_vencimiento, 0, 0, 'C'); // fecha vencimiento

                            $pdf->SetY($pdf->getY() + $actual_y);
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
                                // $pdf->Image(get_path_imagenes($data['logo']), 15, 16, 30, 7, 'PNG');
                                // $pdf->Image(env('LOGO_PATH'), 15, 16, 30, 7, 'PNG');
                                $pdf->Image(storage_path('app/public/images/logo.png'), 15, 16, 50, 0, 'PNG');
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
        
                                //  TABLA INTERNACIONES --------------------------------------------------------------
                                // Título de la tabla
                                $pdf->Cell(180, 3, '', 0, 1);
                                $pdf->SetFont($font, 'B', 12);
                                $pdf->Cell(180, 1, 'Internaciones', 0, 1, 'L');
                                //  cabecera de tabla
                                $pdf->SetFont($font, 'B', 10);
                                $pdf->Cell(190, 2, '', 0, 1);
                                $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                                $pdf->Cell(15, 10, utf8_decode('Estado'), 0, 0, 'C'); // estado
                                $pdf->Cell(15, 10, utf8_decode('#'), 0, 0, 'C'); // numero_internacion con 4 ceros
                                $pdf->MultiCell(15, 5, utf8_decode('Fecha Ingreso'), 0, 'C'); // fecha ingreso
                                $pdf->SetY($pdf->GetY() - 10);
                                $pdf->SetX($pdf->GetX() + 45);
                                $pdf->MultiCell(15, 5, utf8_decode('Fecha Egreso'), 0, 'C'); // fecha egreso
                                $pdf->SetY($pdf->GetY() - 10);
                                $pdf->SetX($pdf->GetX() + 60);
                                $pdf->Cell(30, 10, utf8_decode('Tipo Internación'), 0, 0, 'C'); // tipo internación
                                $pdf->Cell(30, 10, utf8_decode('Prescriptor'), 0, 0, 'C'); // prescriptor
                                $pdf->Cell(30, 10, utf8_decode('Efector'), 0, 0, 'C'); // efector
                                $pdf->Cell(20, 10, utf8_decode('Tipo Alta'), 0, 0, 'C'); //tipo alta
                                $pdf->SetFont($font, 'B', 8);
                                $pdf->MultiCell(20, 5, utf8_decode('Fecha Vencimiento'), 0, 'C'); // fecha vencimiento
                                $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                                // establece la $row en cero para empezar la cuenta nuevamente en la siguiente página
                                $row = 0; 
                                $actual_y = $pdf->GetY();
                            }
                        }
                        $pdf->SetY($pdf->GetY() + 5);
                        $elementosImpresos++;
                    }

                    // imprime las terapéuticas especiales
                    if($elementos_a_exportar['patologias']){
                        $pdf->AddPage('portrait');
                        $pagina_actual++;
                        //  establece valor de tipografia
                        $pdf->SetFont($font, '', 10);
                        //  código de barras
                        get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
                        get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);
                        //  cabecera
                        // $pdf->Image(get_path_imagenes($data['logo']), 15, 16, 30, 7, 'PNG');
                        // $pdf->Image(env('LOGO_PATH'), 15, 16, 30, 7, 'PNG');
                        $pdf->Image(storage_path('app/public/images/logo.png'), 15, 16, 50, 0, 'PNG');
                        $pdf->Cell(60, 14, '', 0, 0);
                        $pdf->Cell(60, 30, $numeroBarra, 0, 0, 'C');
                        $pdf->Cell(60, 5, utf8_decode('Fecha Impresión: ') . $data['fec_impresion'], 0, 2, 'R');
                        $pdf->Cell(60, 5, utf8_decode('Fecha Ingreso: ') . $data['fec_ingreso'], 0, 2, 'R');
                        $pdf->Cell(60, 5, utf8_decode('Página ') . $pagina_actual . ' de ' . $total_paginas, 0, 1, 'R');
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
                        
                        //  TABLA TERAPÉUTICAS ESPECIALES --------------------------------------------------------------
                        // Título de la tabla
                        $pdf->Cell(180, 3, '', 0, 1);
                        $pdf->SetFont($font, 'B', 12);
                        $pdf->Cell(180, 1, utf8_decode('Terapéuticas Especiales'), 0, 1, 'L');
                        //  cabecera de tabla
                        $pdf->SetFont($font, 'B', 10);
                        $pdf->Cell(190, 2, '', 0, 1);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                        $pdf->Cell(30, 5, utf8_decode('Nombre'), 0, 0, 'L'); // nombre
                        $pdf->Cell(40, 5, utf8_decode('Diagnósito'), 0, 0, 'L'); // diagnóstico
                        $pdf->SetFont($font, 'B', 8);
                        $pdf->Cell(10, 5, utf8_decode('Prev.'), 0, 0, 'L'); // prevalente
                        $pdf->Cell(10, 5, utf8_decode('Perm.'), 0, 0, 'L'); // permanente
                        $pdf->SetFont($font, 'B', 10);
                        $pdf->Cell(20, 5, utf8_decode('Fecha Vto.'), 0, 0, 'L'); // fecha vencimiento
                        $pdf->Cell(40, 5, utf8_decode('Capítulo'), 0, 0, 'L'); // capítulo
                        $pdf->Cell(40, 5, utf8_decode('Sub Capítulo'), 0, 0, 'L'); // sub capítulo
                        $pdf->SetY($pdf->GetY() + 5);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                        //  por cada patologia
                        $row = 0;
                        $y = 0;
                        foreach ($patologias as $patologia) {
                            $pdf->SetFont($font, '', 8);
                            $actual_y = 5;
                            // nombre
                            if(strlen(utf8_decode(trim($patologia->n_patologia)))>40){
                                $pdf->SetFont($font, '', 6);
                                $nombre = substr(utf8_decode(trim($patologia->n_patologia)), 0, 40);
                                $nombre = $nombre.'...';
                                $pdf->SetFont($font, '', 8);
                            }else{
                                $nombre = substr(utf8_decode(trim($patologia->n_patologia)), 0, 40);
                            }
                            $pdf->MultiCell(30, 5, $nombre, 0, 'L');
                            if(strlen($nombre)>20){
                                $next_y = 10;
                                $actual_y = 10;
                                $row++;
                                $y++;
                            }else{
                                $next_y = 5;
                            }
                            $nombre_y = $pdf->GetY();
                            $pdf->SetY($nombre_y - $next_y);
                            $pdf->SetX($pdf->GetX() + 30);
                            // diagnostico
                            if(strlen(utf8_decode(trim($patologia->n_cie_item)))>50){
                                $pdf->SetFont($font, '', 6);
                                $diagnostico = substr(utf8_decode(trim($patologia->n_cie_item)), 0, 50);
                                $diagnostico = $diagnostico.'...';
                                $pdf->SetFont($font, '', 8);
                            }else{
                                $diagnostico = substr(utf8_decode(trim($patologia->n_cie_item)), 0, 50);
                            }
                            $pdf->MultiCell(40, 5, $diagnostico, 0, 'L');
                            if(strlen($diagnostico)>25){
                                $next_y = 10;
                                $actual_y = 10;
                                $row++;
                                $y++;
                            }else{
                                $next_y = 5;
                            }
                            $diagnostico_y = $pdf->GetY();
                            $pdf->SetY($diagnostico_y - $next_y);
                            $pdf->SetX($pdf->GetX() + 70);

                            $pdf->Cell(10, 5, ($patologia->prevalente == 1 ? 'Si' : 'No'), 0, 0, 'L'); // prevalente
                            $pdf->Cell(10, 5, ($patologia->permanente == 1 ? 'Si' : 'No'), 0, 0, 'L'); // permanente
                            $pdf->Cell(20, 5, Carbon::parse($patologia->fec_vto)->format('d/m/Y'), 0, 0, 'L'); // fecha vencimiento
                            // $pdf->Cell(40, 5, utf8_decode($patologia->n_capitulo), 1, 0, 'L'); // capítulo
                            // capítulo
                            if(strlen(utf8_decode(trim($patologia->n_capitulo)))>50){
                                $pdf->SetFont($font, '', 6);
                                $capitulo = substr(utf8_decode(trim($patologia->n_capitulo)), 0, 50);
                                $capitulo = $capitulo.'...';
                                $pdf->SetFont($font, '', 8);
                            }else{
                                $capitulo = substr(utf8_decode(trim($patologia->n_capitulo)), 0, 50);
                            }
                            $pdf->MultiCell(40, 5, $capitulo, 0, 'L');
                            if(strlen($capitulo)>25){
                                $next_y = 10;
                                $actual_y = 10;
                                $row++;
                                $y++;
                            }else{
                                $next_y = 5;
                            }
                            $capitulo_y = $pdf->GetY();
                            $pdf->SetY($capitulo_y - $next_y);
                            $pdf->SetX($pdf->GetX() + 150);
                            // $pdf->Cell(40, 5, utf8_decode($patologia->n_sub_capitulo), 1, 0, 'L'); // sub capítulo
                            // sub capítulo
                            if(strlen(utf8_decode(trim($patologia->n_sub_capitulo)))>50){
                                $pdf->SetFont($font, '', 6);
                                $subcapitulo = substr(utf8_decode(trim($patologia->n_sub_capitulo)), 0, 50);
                                $subcapitulo = $subcapitulo.'...';
                                $pdf->SetFont($font, '', 8);
                            }else{
                                $subcapitulo = substr(utf8_decode(trim($patologia->n_sub_capitulo)), 0, 50);
                            }
                            $pdf->MultiCell(40, 5, $subcapitulo, 0, 'L');
                            if(strlen($subcapitulo)>25){
                                $next_y = 10;
                                $actual_y = 10;
                                $row++;
                                $y++;
                            }else{
                                $next_y = 5;
                            }
                            $subcapitulo_y = $pdf->GetY();
                            $pdf->SetY($subcapitulo_y - $next_y);
                            $pdf->SetY($pdf->getY() + $actual_y);
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
                                // $pdf->Image(get_path_imagenes($data['logo']), 15, 16, 30, 7, 'PNG');
                                // $pdf->Image(env('LOGO_PATH'), 15, 16, 30, 7, 'PNG');
                                $pdf->Image(storage_path('app/public/images/logo.png'), 15, 16, 50, 0, 'PNG');
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
                                $pdf->Cell(60, 5, utf8_decode('Número de Afiliado: ') . $data['n_afiliado'], 0, 0);
                                $pdf->Cell(60, 5, utf8_decode('Convenio: ') . $data['n_convenio'], 0, 0, 'C');
                                $pdf->Cell(60, 5, utf8_decode('Plan: ') . utf8_decode($data['n_plan']), 0, 1, 'R');
                                //  segunda línea
                                $pdf->Cell(60, 5, utf8_decode('Documento: ') . $data['abrev_tipo_doc'].' '.$data['nro_doc'], 0, 0, 'L');
                                $pdf->Cell(60, 5, utf8_decode($data['n_afip']), 0, 0, 'C');
                                $pdf->Cell(60, 5, utf8_decode('Localidad: ') . utf8_decode($data['n_localidad']), 0, 1, 'R');
        
                                //  TABLA TERAPÉUTICAS ESPECIALES --------------------------------------------------------------
                                // Título de la tabla
                                $pdf->Cell(180, 3, '', 0, 1);
                                $pdf->SetFont($font, 'B', 12);
                                $pdf->Cell(180, 1, utf8_decode('Terapéuticas Especiales'), 0, 1, 'L');
                                //  cabecera de tabla
                                $pdf->SetFont($font, 'B', 10);
                                $pdf->Cell(190, 2, '', 0, 1);
                                $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                                $pdf->Cell(30, 5, utf8_decode('Nombre'), 0, 0, 'L'); // nombre
                                $pdf->Cell(40, 5, utf8_decode('Diagnósito'), 0, 0, 'L'); // diagnóstico
                                $pdf->SetFont($font, 'B', 8);
                                $pdf->Cell(10, 5, utf8_decode('Prev.'), 0, 0, 'L'); // prevalente
                                $pdf->Cell(10, 5, utf8_decode('Perm.'), 0, 0, 'L'); // permanente
                                $pdf->SetFont($font, 'B', 10);
                                $pdf->Cell(20, 5, utf8_decode('Fecha Vto.'), 0, 0, 'L'); // fecha vencimiento
                                $pdf->Cell(40, 5, utf8_decode('Capítulo'), 0, 0, 'L'); // capítulo
                                $pdf->Cell(40, 5, utf8_decode('Sub Capítulo'), 0, 0, 'L'); // sub capítulo
                                $pdf->SetY($pdf->GetY() + 5);
                                $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                                // establece la $row en cero para empezar la cuenta nuevamente en la siguiente página
                                $row = 0; 
                                $actual_y = $pdf->GetY();
                            }
                        }
                        $elementosImpresos++;
                    }

                    // imprime los grupos familiares, la ficha familiar, los domicilios y los contactos
                    if($elementos_a_exportar['grupos']){
                        $pdf->AddPage('portrait');
                        $pagina_actual++;
                        //  establece valor de tipografia
                        $pdf->SetFont($font, '', 10);
                        
                        //  código de barras
                        get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
                        get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);
                        
                        //  cabecera
                        // $pdf->Image(get_path_imagenes($data['logo']), 15, 16, 30, 7, 'PNG');
                        // $pdf->Image(env('LOGO_PATH'), 15, 16, 30, 7, 'PNG');
                        $pdf->Image(storage_path('app/public/images/logo.png'), 15, 16, 50, 0, 'PNG');
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

                        //  TABLA GRUPOS FAMILIARES--------------------------------------------------------------
                        // Título de la tabla
                        $pdf->Cell(180, 3, '', 0, 1);
                        $pdf->SetFont($font, 'B', 12);
                        $pdf->Cell(180, 1, 'Grupo Familiar', 0, 1, 'L');
                        //  cabecera de tabla
                        $pdf->SetFont($font, 'B', 10);
                        $pdf->Cell(190, 2, '', 0, 1);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                        $pdf->Cell(20, 7, utf8_decode('Afiliado'), 0, 0, 'C');
                        $pdf->Cell(80, 7, utf8_decode('Nombre'), 0, 0, 'L');
                        $pdf->Cell(20, 7, utf8_decode('DNI'), 0, 0, 'C');
                        $pdf->Cell(20, 7, utf8_decode('Fec. Nac.'), 0, 0, 'C');
                        $pdf->Cell(50, 7, utf8_decode('Parentesco'), 0, 0, 'L');
                        $pdf->SetY($pdf->GetY() + 7);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                        
                        $total_row = 0;
                        //  por cada familiar del grupo
                        $row = 0;
                        foreach ($grupos as $grupo) {
                            $pdf->SetFont($font, '', 8);
                            $pdf->Cell(20, 5, $grupo->n_afiliado, 0, 0, 'C'); // número de afiliado
                            $new_y = $pdf->GetY(); // devuelve la ordenada de la posición actual
                            // nombre
                            $pdf->SetFont($font, '', 8);
                            if(strlen(utf8_decode(trim($grupo->n_persona)))>50){
                                $nombre = substr(utf8_decode(trim($grupo->n_persona)), 0, 40);
                                $nombre = $nombre.'...';
                            }else{
                                $nombre = utf8_decode(trim($grupo->n_persona));
                            }
                            $pdf->MultiCell(80, 5, $nombre, 0, 'L');
                            if(strlen($nombre)>40){
                                $row++;
                                $nombre_y = 10;
                                $pdf->SetY($pdf->GetY() - 10);
                            }else{
                                $nombre_y = 5;
                                $pdf->SetY($pdf->GetY() - 5);
                            }
                            $pdf->setX(115);
                            $pdf->Cell(20, 5, $grupo->abrev_tipo_doc.' '.$grupo->nro_doc, 0, 0, 'C'); // tipo y número de documento
                            $pdf->Cell(20, 5, Carbon::parse($grupo->fec_nac)->format('d/m/Y'), 0, 0, 'C'); // fecha nacimiento
                            // parentesco
                            $pdf->SetFont($font, '', 8);
                            if(strlen(utf8_decode(trim($grupo->n_parentesco)))>54){
                                $parentesco = substr(utf8_decode(trim($grupo->n_parentesco)), 0, 54);
                                $parentesco = $parentesco.'...';
                            }else{
                                $parentesco = substr(utf8_decode(trim($grupo->n_parentesco)), 0, 54);
                            }
                            $pdf->MultiCell(50, 5, $parentesco, 0, 'L');
                            if(strlen($parentesco)>40){
                                $row++;
                                $parentesco_y = 10;
                                $pdf->SetY($pdf->GetY() - 10);
                            }else{
                                $parentesco_y = 5;
                                $pdf->SetY($pdf->GetY() - 5);
                            }
                            // ubicamos el marcador para comenzar la siguiente línea
                            $actual_y = $parentesco_y > $nombre_y ? $parentesco_y : $nombre_y;
                            $pdf->SetY($pdf->GetY() + $actual_y);
                            $pdf->SetX($pdf->GetX());
                            $pdf->Cell(190, 0.001, '', 1, 1, 'C'); // línea
                            $row++;
                            // incrementamos la cantidad de filas de acuerdo al alto de la fila
                            if($actual_y == 5){
                                $total_row++;
                            }else{
                                $total_row = $total_row + 2;
                            }
                            // como son pocos items siempre nunca sobrepasarían los 40
                        }
                        $pdf->SetY($pdf->GetY() + 10);
                        $total_row = $total_row + 2; // sumamos 2 a la cantidad de filas por el espacio entra tablas

                        //  TABLA FICHA FAMILIAR --------------------------------------------------------------
                        $pdf->setX(20);
                        // Título de la tabla
                        $pdf->Cell(180, 3, '', 0, 1);
                        $pdf->SetFont($font, 'B', 12);
                        $pdf->Cell(180, 1, 'Ficha Familiar', 0, 1, 'L');
                        //  cabecera de tabla
                        $pdf->SetFont($font, 'B', 10);
                        $pdf->Cell(190, 2, '', 0, 1);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                        // $pdf->Cell(20, 10, utf8_decode('Afiliado'), 0, 0, 'C');
                        $pdf->Cell(60, 7, utf8_decode('Nombre'), 0, 0, 'L');
                        $pdf->Cell(30, 7, utf8_decode('Género'), 0, 0, 'L');
                        $pdf->Cell(50, 7, utf8_decode('Plan'), 0, 0, 'L');
                        $pdf->Cell(50, 7, utf8_decode('Convenio'), 0, 0, 'L');
                        $pdf->SetY($pdf->GetY() + 7);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                        $total_row = $total_row + 4; // sumamos 4 a la cantidad de filas por el espacio de la cabecera

                        // por cada familiar del grupo
                        foreach ($grupos as $grupo) {
                            $pdf->SetFont($font, '', 8);
                            $new_y = $pdf->GetY(); // devuelve la ordenada de la posición actual
                            $pdf->Cell(60, 5, $grupo->n_persona, 0, 0, 'L'); // nombre
                            $sexo = $grupo->sexo == 'M' ? 'Masculino' : ($grupo->sexo == 'F' ? 'Femenino': 'No Binario');
                            $pdf->Cell(30, 5, $sexo, 0, 0, 'L'); // sexo
                            // plan
                            if(strlen(utf8_decode(trim($grupo->n_plan)))>40){
                                // si tiene mas de 50 caracteres achicamos la fuente cortamos la cadena 
                                // y agregamos '...' al final
                                $pdf->SetFont($font, '', 6);
                                $plan = substr(utf8_decode(trim($grupo->n_plan)), 0, 37);
                                $plan = $plan.'...';
                            }else{
                                // sino dejamos la fuente en 8
                                $pdf->SetFont($font, '', 8);
                                $plan = utf8_decode(trim($grupo->n_plan));
                            }
                            $pdf->MultiCell(50, 5, $plan, 0, 'L');  // plan
                            if(strlen($plan)>20){
                                $row++;
                                $pdf->SetY($pdf->GetY() - 10);
                            }else{
                                $pdf->SetY($pdf->GetY() - 5);
                            }
                            $plan_y = $pdf->GetY();

                            $pdf->setX(155);
                            // convenio
                            if(strlen(utf8_decode(trim($grupo->n_convenio)))>40){
                                // si tiene mas de 50 caracteres achicamos la fuente cortamos la cadena 
                                // y agregamos '...' al final
                                $pdf->SetFont($font, '', 6);
                                $convenio = substr(utf8_decode(trim($grupo->n_convenio)), 0, 37);
                                $convenio = $convenio.'...';
                            }else{
                                // sino dejamos la fuente en 8
                                $pdf->SetFont($font, '', 8);
                                $convenio = utf8_decode(trim($grupo->n_convenio));
                            }
                            $pdf->MultiCell(50, 5, $convenio, 0, 'L');  // convenio
                            if(strlen($convenio)>20){
                                $row++;
                            }
                            $convenio_y = $pdf->getY();
                            // reubicamos para la siguiente fila
                            $actual_y = $convenio_y > $plan_y ? $convenio_y : $plan_y;
                            $pdf->SetY($actual_y);
                            $pdf->SetX($pdf->GetX());
                            $pdf->Cell(190, 0.001, '', 1, 1, 'C'); // línea
                            $row++;

                            // incrementamos la cantidad de filas de acuerdo al alto de la fila
                            if($actual_y == 5){
                                $total_row++;
                            }else{
                                $total_row = $total_row + 2;
                            }
                            // como son pocos items siempre nunca sobrepasarían los 40
                        }
                        $pdf->SetY($pdf->GetY() + 10);
                        $total_row = $total_row + 2; // sumamos 2 a la cantidad de filas por el espacio entra tablas

                        //  TABLA DOMICILIOS --------------------------------------------------------------
                        $pdf->setX(20);
                        // Título de la tabla
                        $pdf->Cell(180, 3, '', 0, 1);
                        $pdf->SetFont($font, 'B', 12);
                        $pdf->Cell(180, 1, 'Domicilios', 0, 1, 'L');
                        //  cabecera de tabla
                        $pdf->SetFont($font, 'B', 10);
                        $pdf->Cell(190, 2, '', 0, 1);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                        $pdf->Cell(60, 7, utf8_decode('Nombre'), 0, 0, 'L');
                        $pdf->Cell(20, 7, utf8_decode('Tipo'), 0, 0, 'C');
                        $pdf->Cell(30, 7, utf8_decode('Calle'), 0, 0, 'L');
                        $pdf->Cell(10, 7, utf8_decode('N°'), 0, 0, 'L');
                        $pdf->Cell(30, 7, utf8_decode('Localidad'), 0, 0, 'L');
                        $pdf->Cell(30, 7, utf8_decode('Provincia'), 0, 0, 'L');
                        $pdf->Cell(10, 7, utf8_decode('C.P.'), 0, 0, 'L');
                        $pdf->SetY($pdf->GetY() + 7);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                        $total_row = $total_row + 4; // sumamos 4 a la cantidad de filas por el espacio de la cabecera
                        
                        //  por cada familiar del grupo
                        $row = 0;
                        foreach ($domicilios as $domicilio) {
                            $pdf->SetFont($font, '', 8);
                            $new_y = $pdf->GetY(); // devuelve la ordenada de la posición actual
                            // nombre
                            $pdf->SetFont($font, '', 8);
                            if(strlen(utf8_decode(trim($domicilio->pertenece)))>50){
                                $nombre = substr(utf8_decode(trim($domicilio->pertenece)), 0, 47);
                                $nombre = $nombre.'...';
                            }else{
                                $nombre = utf8_decode(trim($domicilio->pertenece));
                            }
                            $pdf->MultiCell(60, 5, $nombre, 0, 'L');
                            if(strlen($nombre)>40){
                                $row++;
                                $nombre_y = 10;
                                $pdf->SetY($pdf->GetY() - 10);
                            }else{
                                $nombre_y = 5;
                                $pdf->SetY($pdf->GetY() - 5);
                            }
                            $pdf->setX(75);
                            $pdf->Cell(20, 5, utf8_decode(trim($domicilio->n_tipo_domicilio)), 0, 0, 'C'); // tipo 
                            // calle
                            $pdf->SetFont($font, '', 8);
                            if(strlen(utf8_decode(trim($domicilio->calle)))>40){
                                $calle = substr(utf8_decode(trim($domicilio->calle)), 0, 37);
                                $calle = $calle.'...';
                            }else{
                                $calle = utf8_decode(trim($domicilio->calle));
                            }
                            $pdf->MultiCell(30, 5, $calle, 0, 'L');
                            if(strlen($calle)>30){
                                $row++;
                                $calle_y = 10;
                                $pdf->SetY($pdf->GetY() - 10);
                            }else{
                                $calle_y = 5;
                                $pdf->SetY($pdf->GetY() - 5);
                            }
                            $pdf->setX(125);
                            $pdf->Cell(10, 5, utf8_decode(trim($domicilio->puerta)), 0, 0, 'C'); // puerta
                            // localidad
                            $pdf->SetFont($font, '', 8);
                            if(strlen(utf8_decode(trim($domicilio->n_localidad)))>40){
                                $localidad = substr(utf8_decode(trim($domicilio->n_localidad)), 0, 37);
                                $localidad = $localidad.'...';
                            }else{
                                $localidad = utf8_decode(trim($domicilio->n_localidad));
                            }
                            $pdf->MultiCell(30, 5, $localidad, 0, 'L');
                            if(strlen($localidad)>40){
                                $row++;
                                $localidad_y = 10;
                                $pdf->SetY($pdf->GetY() - 10);
                            }else{
                                $localidad_y = 5;
                                $pdf->SetY($pdf->GetY() - 5);
                            }
                            $pdf->setX(165);
                            // provincia
                            $pdf->SetFont($font, '', 8);
                            if(strlen(utf8_decode(trim($domicilio->n_provincia)))>40){
                                $provincia = substr(utf8_decode(trim($domicilio->n_provincia)), 0, 37);
                                $provincia = $provincia.'...';
                            }else{
                                $provincia = utf8_decode(trim($domicilio->n_provincia));
                            }
                            $pdf->MultiCell(30, 5, $provincia, 0, 'L');
                            if(strlen($provincia)>40){
                                $row++;
                                $provincia_y = 10;
                                $pdf->SetY($pdf->GetY() - 10);
                            }else{
                                $provincia_y = 5;
                                $pdf->SetY($pdf->GetY() - 5);
                            }
                            $pdf->setX(195);
                            $pdf->Cell(10, 5, utf8_decode(trim($domicilio->cpostal)), 0, 0, 'C'); // cp

                            // ubicamos el marcador para comenzar la siguiente línea
                            $actual_y = $calle_y > $nombre_y ? $calle_y : ($localidad_y > $nombre_y ? $localidad_y : ($provincia_y > $nombre ? $provincia_y : $nombre_y));
                            $pdf->SetY($pdf->GetY() + $actual_y);
                            $pdf->SetX($pdf->GetX());
                            $pdf->Cell(190, 0.001, '', 1, 1, 'C'); // línea
                            $row++;
                            // incrementamos la cantidad de filas de acuerdo al alto de la fila
                            if($actual_y == 5){
                                $total_row++;
                            }else{
                                $total_row = $total_row + 2;
                            }
                            // como son pocos items siempre nunca sobrepasarían los 40
                        }
                        $pdf->SetY($pdf->GetY() + 10);
                        $total_row = $total_row + 2; // sumamos 2 a la cantidad de filas por el espacio entra tablas

                        //  TABLA CONTACTOS --------------------------------------------------------------
                        $pdf->setX(20);
                        // Título de la tabla
                        $pdf->Cell(180, 3, '', 0, 1);
                        $pdf->SetFont($font, 'B', 12);
                        $pdf->Cell(180, 1, 'Contactos', 0, 1, 'L');
                        //  cabecera de tabla
                        $pdf->SetFont($font, 'B', 10);
                        $pdf->Cell(190, 2, '', 0, 1);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                        $pdf->Cell(60, 7, utf8_decode('Nombre'), 0, 0, 'L');
                        $pdf->Cell(20, 7, utf8_decode('Tipo'), 0, 0, 'L');
                        $pdf->Cell(100, 7, utf8_decode('Contacto'), 0, 0, 'L');
                        $pdf->SetY($pdf->GetY() + 7);
                        $pdf->Cell(190, 0.01, '', 1, 1, 'C'); // línea
                        $total_row = $total_row + 4; // sumamos 4 a la cantidad de filas por el espacio de la cabecera

                        //  por cada familiar del grupo
                        $row = 0;
                        foreach ($contactos as $contacto) {
                            $pdf->SetFont($font, '', 8);
                            $new_y = $pdf->GetY(); // devuelve la ordenada de la posición actual
                            // nombre
                            $pdf->SetFont($font, '', 8);
                            if(strlen(utf8_decode(trim($contacto->pertenece)))>50){
                                $nombre = substr(utf8_decode(trim($contacto->pertenece)), 0, 40);
                                $nombre = $nombre.'...';
                            }else{
                                $nombre = utf8_decode(trim($contacto->pertenece));
                            }
                            $pdf->MultiCell(60, 5, $nombre, 0, 'L');
                            if(strlen($nombre)>40){
                                $row++;
                                $nombre_y = 10;
                                $pdf->SetY($pdf->GetY() - 10);
                            }else{
                                $nombre_y = 5;
                                $pdf->SetY($pdf->GetY() - 5);
                            }
                            $pdf->setX(75);
                            $pdf->Cell(20, 5, utf8_decode(trim($contacto->n_tipo_contacto)), 0, 0, 'L'); // tipo 
                            // n_contacto
                            $pdf->SetFont($font, '', 8);
                            if(strlen(utf8_decode(trim($contacto->n_contacto)))>64){
                                $n_contacto = substr(utf8_decode(trim($contacto->n_contacto)), 0, 60);
                                $n_contacto = $n_contacto.'...';
                            }else{
                                $n_contacto = utf8_decode(trim($contacto->n_contacto));
                            }
                            $pdf->MultiCell(100, 5, $n_contacto, 0, 'L');
                            if(strlen($n_contacto)>40){
                                $row++;
                                $n_contacto_y = 10;
                                $pdf->SetY($pdf->GetY() - 10);
                            }else{
                                $n_contacto_y = 5;
                                $pdf->SetY($pdf->GetY() - 5);
                            }
                            // ubicamos el marcador para comenzar la siguiente línea
                            $actual_y = $n_contacto_y > $nombre_y ? $n_contacto_y : $nombre_y;
                            $pdf->SetY($pdf->GetY() + $actual_y);
                            $pdf->SetX($pdf->GetX());
                            $pdf->Cell(190, 0.001, '', 1, 1, 'C'); // línea
                            $row++;
                            // incrementamos la cantidad de filas de acuerdo al alto de la fila
                            if($actual_y == 5){
                                $total_row++;
                            }else{
                                $total_row = $total_row + 2;
                            }

                        }
                        $pdf->SetY($pdf->GetY() + 10);
                        $total_row = $total_row + 2; // sumamos 2 a la cantidad de filas por el espacio entra tablas
                        
                        $pdf->SetY($pdf->GetY() + 5);
                        $elementosImpresos++;
                    }

                    //  ====================================================================
                    
                    if ($accion == 'enviar') {  // almacena el archivo en una ubicación específica y devuelve el path
                        $file_path = env('STORAGE_PATH').'/reportes/informes_afiliados/';
                        $filename = 'afiliado-'.$data['n_afiliado'].'.pdf';
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
                        $filename = 'afiliado-' . $data['n_afiliado'] . '.pdf';
                        $file = $file_path.$filename;
                        $pdf->Output($file, "F");
                        return FacadeResponse::make(Storage::disk('informes_afiliados')->get($filename), 200, [
                            'Content-Type' => 'application/pdf',
                            'Content-Disposition' => 'inline; ' . $filename,
                        ]);
                    }
                
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para EXPORTAR DATOS';
                $count  = 0;
                $data = null;
                array_push($errors, 'Error de permisos');
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => 0,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => -2,
                    'data' => $data,
                    'params' => $this->params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]); 
            }
        } catch (\Throwable $th) {
            $extras['path'] = env('STORAGE_PATH').'/reportes/informes_afiliados/';
            array_push($errors, 'Error de Backend');
            return response()->json([
                'status' => $status,
                'count' => 0,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => -1,
                'data' => $data,
                'params' => $this->params,
                'logged_user' => $logged_user != null ? $logged_user : null,
                'extras' => $extras
            ]);
        }
        
    }

    /**
     * genera un certificado de afiliacion en pdf
     */
    public function generar_certificado_afiliacion(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/afiliaciones/afiliacion/generar-certificado-afiliacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [
                'sp_afiliado_Select'
            ],
            'responses' => [],
            'queries' => [],
            'verificado' => [],
            'path' => ''
        ];
        try {
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $errors = [];

            if($user->hasPermissionTo('exportar datos')){
                $nro_afiliado = request('nro_afiliado');
                $accion = request('accion') != null ? request('accion') : 'mostrar';
                $this->params['nro_afiliado'] = $nro_afiliado;
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_afiliado_Select', $this->params));
                $afiliados = $this->ejecutar_sp_directo('afiliacion', 'sp_afiliado_Select', null);
                array_push($extras['responses'], ['sp_afiliado_Select' => $afiliados]);
                if(!empty($afiliados) && sizeof($afiliados)){
                    $afiliado = $afiliados[0];
                    $data = [
                        'logo' => env('LOGO_PATH'),
                        'n_persona' => $afiliado->n_persona,
                        'nro_doc' => $afiliado->nro_doc,
                        'n_plan' => $afiliado->n_plan,
                        'fec_ingreso' => $afiliado->fec_ingreso,
                        'n_afiliado' => $afiliado->n_afiliado,
                        'cuit' => $afiliado->cuit,
                        'estado_afiliado' => $afiliado->n_estado,
                        'dia' => Carbon::now()->day,
                        'mes' => Carbon::now()->month,
                        'anio' => Carbon::now()->year 
                    ];
                    $pdf = new Fpdi('portrait', 'mm', 'A4');
                    
                    //  font setup
                    $font = 'Arial';
                    //  -------------------
                    $pdf->SetMargins(15, 15);
                    $pdf->AddPage('portrait');
                    // logo
                    // $pdf->Image(storage_path('app/public/images/certificado-afiliacion.jpg'), 0, 0, 210, 297, 'JPG');
                    // $pdf->Cell(180, 100, '', 0, 1); // espacio en blanco para ubicar el texto
                    
                    if(env('APP_ENV') == 'osceara'){
                        // $pdf->Image(storage_path('app/public/images/certificado-afiliacion-osceara.jpg'), 0, 0, 210, 297, 'JPG');
                        $pdf->Cell(180, 63, '', 0, 1); // espacio en blanco para ubicar el texto
                        
                        // logo comentar si se usa la imagen de fondo certificado-afiliacion-osceara.jpg porque ya tiene el logo
                        $pdf->Image(env('IMAGE_PATH').'/Logo_Osceara-sin-fondo.png', 50, 20, 100, 50, 'PNG');

                        $pdf->SetFont($font, 'B', 20);
                        $pdf->Cell(180, 7, utf8_decode('Certificado de Afiliación'), 0, 2, 'C');
                        $pdf->Cell(180, 21, '', 0, 1); // espacio en blanco entre textos

                        $pdf->SetFont($font, '', 14);
                        $pdf->Cell(180, 7, utf8_decode('Por medio del presente, certificamos que'), 0, 2, 'C');
                        $pdf->SetFont($font, 'B', 16);
                        $pdf->Cell(180, 7, utf8_decode($data['n_persona'].','), 0, 2, 'C');
                        $pdf->SetFont($font, '', 14);
                        $pdf->Cell(180, 7, utf8_decode('con CUIL '.$data['cuit'].', se encuentra '.$data['estado_afiliado']), 0, 2, 'C');
                        $pdf->Cell(180, 7, utf8_decode('en el padrón de beneficiarios de la Obra Social al día de la fecha. '), 0, 2, 'C');
                        // $pdf->Cell(180, 7, utf8_decode('Se extiende el presente certificado a los '.$data['dia'].' días del mes '.$data['mes'].' de '.$data['anio'].'.'), 0, 2, 'C');
                        $pdf->Cell(180, 7, utf8_decode('Se extiende el presente certificado a los '.Carbon::now()->day.' días del mes de '.Carbon::now()->locale('es')->translatedFormat('F').' de '.Carbon::now()->year.'.'), 0, 2, 'C');
                        
                        $pdf->Cell(180, 10, '', 0, 1); // espacio en blanco entre textos
                        // Obtener posición actual para colocar la imagen centrada
                        $x_actual = $pdf->GetX();
                        $y_actual = $pdf->GetY();
                        $ancho_imagen = 30;
                        $alto_imagen = 30;
                        $x_centrado = ($pdf->GetPageWidth() - $ancho_imagen) / 2;
                        $pdf->Image(env('IMAGE_PATH').'/firma_certificado.png', $x_centrado, $y_actual, $ancho_imagen, $alto_imagen, 'PNG');
                        $pdf->SetY($y_actual + $alto_imagen + 5); // Mover cursor debajo de la imagen
                        $pdf->Cell(180, 7, utf8_decode('Departamento Afiliaciones - Padrón.'), 0, 2, 'C');
                        $pdf->Cell(180, 63, '', 0, 1); // espacio en blanco entre textos

                        $pdf->SetFont($font, '', 12);
                        $pdf->Cell(180, 7, utf8_decode('Avenida Rivadavia 1545 2F - CABA 0810-222-3333'), 0, 2, 'C');
                        $pdf->Cell(180, 7, utf8_decode('info@osceara.com.ar'), 0, 2, 'C');
                    }else{
                        $pdf->Image(storage_path('app/public/images/certificado-afiliacion.jpg'), 0, 0, 210, 297, 'JPG');
                        $pdf->Cell(180, 100, '', 0, 1); // espacio en blanco para ubicar el texto
                        $pdf->Cell(180, 3, '', 0, 1); // espacio en blanco entre textos
                        $pdf->SetFont($font, '', 14);
                        $pdf->Cell(180, 7, utf8_decode('Por medio del presente, certifico que'), 0, 2, 'C');
                        $pdf->SetFont($font, 'B', 16);
                        $pdf->Cell(180, 7, utf8_decode($data['n_persona'].','), 0, 2, 'C');
                        $pdf->SetFont($font, '', 14);
                        $pdf->Cell(180, 7, utf8_decode('con DNI N° '.$data['nro_doc'].', es afiliado al plan '.$data['n_plan']), 0, 2, 'C');
                        $pdf->Cell(180, 7, utf8_decode('desde el '.$data['fec_ingreso'].', bajo el número '.$data['n_afiliado']), 0, 2, 'C');
                        $pdf->Cell(180, 7, utf8_decode('Asimismo, informamos que el afiliado no registra deudas y que tiene acceso'), 0, 2, 'C');
                        $pdf->Cell(180, 7, utf8_decode('a la cobertura contemplada por el plan.'), 0, 2, 'C');
                        $pdf->Cell(180, 7, '', 0, 1); // espacio en blanco entre textos
                        $pdf->Cell(180, 7, utf8_decode('Nota: La validez de este certificado es de 30 días a partir de su emisión.'), 0, 2, 'C');
                        $pdf->Cell(180, 7, '', 0, 1); // espacio en blanco entre textos
                        $pdf->Cell(180, 7, utf8_decode('Se extiende el presente certificado a los '.$data['dia'].' días del mes de '.Carbon::now()->locale('es')->translatedFormat('F').' de '.$data['anio'].'.'), 0, 2, 'C');
                    }
            
                    //  -------------------
                    if ($accion == 'enviar') {
                        if(!File::exists(env('STORAGE_PATH').'reportes/certificados_afiliacion/')){
                            File::makeDirectory(env('STORAGE_PATH').'reportes/certificados_afiliacion/');
                        }
                        $file_path = env('STORAGE_PATH').'reportes/certificados_afiliacion/';
                        if(!File::exists($file_path)){
                            File::makeDirectory($file_path);
                        }
                        $filename = 'certificado-afiliacion-'.$data['n_afiliado'].'.pdf';
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
                            'params' => ['nro_afiliado' => $nro_afiliado, 'accion' => $accion],
                            'logged_user' => $logged_user,
                            'extras' => $extras
                        ]);
                    }else{
                        if(!File::exists(env('STORAGE_PATH').'reportes/certificados_afiliacion/')){
                            File::makeDirectory(env('STORAGE_PATH').'reportes/certificados_afiliacion/');
                        }
                        $file_path = env('STORAGE_PATH').'reportes/certificados_afiliacion/';
                        if(!File::exists($file_path)){
                            File::makeDirectory($file_path);
                        }
                        $filename = 'certificado-afiliacion-' . $data['n_afiliado'] . '.pdf';
                        $file = $file_path.$filename;
                        $pdf->Output($file, "F");
                        return FacadeResponse::make(Storage::disk('certificados_afiliacion')->get($filename), 200, [
                            'Content-Type' => 'application/pdf',
                            'Content-Disposition' => 'inline; ' . $filename,
                        ]);
                    }
                }else{
                    array_push($errors, 'Afiliado no encontrado');
                    return response()->json([
                        'status' => 'fail',
                        'count' => 0,
                        'errors' => $errors,
                        'message' => 'No Se econtró afiliado con el número '.$nro_afiliado,
                        'line' => null,
                        'code' => -1,
                        'data' => null,
                        'params' => $this->params,
                        'logged_user' => $logged_user,
                        'extras' => $extras
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
                    'params' => $this->params,
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
                'params' => null,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }
    }
}