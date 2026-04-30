<?php

namespace App\Http\Controllers\Internos\Afiliaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;

use File;
use Storage;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;
use App\Services\Pdf\FacturaGeneralGeneratorPdf;
use App\Services\Pdf\FacturaOscearaGeneratorPdf;

class FacturacionController extends ConexionSpController
{

    /**
     * Obtiene un listado de facturas
     */
    public function listar_facturas_globales(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => 'int/afiliaciones/facturacion/listar-facturas-globales  1.1.772-20260424',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'queries' => [],
            'responses' => [],
            'sps' => [],
            'verificado' => []
        ];
        $status = 'fail'; // 'ok', 'fail', 'empty', unauthorized', 'warning'  
        $message = '';
        $count = 0;
        $code = 0;
        $data = [
            'facturas' => null,
            'nombre_periodo' => null,
            'nombre_carpeta' => null,
            'cantidad_archivos' => null,
            'enviadas' => null
        ];
        $errors = [];
        $params = [];
        $params_sp = [];
        
        // obtenemos el usuario de la petición y sus permisos
        $user = User::with('roles', 'permissions')->find($request->user()->id);
        $logged_user = $this->get_logged_user($user);
        $usuario_sqlserver_default = 1;
        $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;
        try {
            date_default_timezone_set('America/Argentina/Cordoba');
            $permiso_requerido = 'ver facturacion';
            if($permiso_requerido == 'ver facturacion' || $user->hasPermissionTo($permiso_requerido)){
                $id_periodo = request('id_periodo');
                $nombre_periodo = trim(request('nombre_periodo'), '"'); //'JUBILADOS SEPTIEMBRE 2022';
                $nombre_carpeta = request('nombre_carpeta'); // '2022-09_29'
                $cantidad_archivos = 0; // cantidad de archivos en el directorio

                $params = [
                    'id_periodo' => $id_periodo,
                    'nombre_periodo' => $nombre_periodo,
                    'nombre_carpeta' => $nombre_carpeta
                ];
                
                $sp = 'sp_factura_select';
                $db = 'afiliacion';
                $params_sp = [
                    'p_id_periodo' => $id_periodo
                ];
                $files = glob(env('STORAGE_PATH').'/reportes/facturacion_global/'.$nombre_carpeta.'/*.pdf');
                $enviadas = 0;
                
                array_push($extras['verificado'], [$sp => ['id_periodo' => request('id_periodo')]]);
                if ( empty(request('id_periodo')) ){
                    array_push($errors, 'Parámetros incompletos o incorrectos');
                    $status = 'fail';
                    $message = 'Verifique los parámetros';
                    $count = 0;
                    $data = null;
                    $code = -5;
                }else{
                    array_push($extras['sps'], [$sp => $params_sp]);
                    array_push($extras['queries'], $this->get_query($db, $sp, $params_sp));
                    $response = $this->ejecutar_sp_directo($db, $sp, $params_sp);
                    array_push($extras['responses'], [$sp => $response]);
                    if(is_array($response) && array_key_exists('error', $response)){
                        array_push($errors, $response['error']);
                        $status = 'fail';
                        $message = 'Se produjo un error al realizar la petición';
                        $count = 0;
                        $data = null;
                        $code = -3;
                        // Log::channel('')->error(''); // buscar canales en config/loggin.php
                    }else if(empty($response)){
                        $status = 'empty';
                        $message = 'No se encontraron registros que coincidan con los parámetros de búsqueda';
                        $count = 0;
                        $data = $response;
                        $code = -4;
                        // Log::channel('')->info(''); // buscar canales en config/loggin.php
                    }else{
                        $status = 'ok';
                        $message = 'Transacción realizada con éxito.';
                        $count = sizeof($response);
                        $code = 1;
                        foreach($response as $factura){
                            if($factura->envia == 1){
                                $enviadas++;
                            }
                        }
                        // dd($facturas);
                        if(sizeof($files)>0){
                            $cantidad_archivos = count($files);
                        }
                        $data = [
                            'facturas' => $response,
                            'nombre_periodo' => $nombre_periodo,
                            'nombre_carpeta' => $nombre_carpeta,
                            'cantidad_archivos' => $cantidad_archivos,
                            'enviadas' => $enviadas
                        ];
                    }
                }
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => $code,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user,
                ]); 
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
            array_push($errors, 'Line: '.$th->getLine().' Error: '.$th->getMessage());
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

    /**
     * Simula una factura
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function simular_facturacion(Request $request)
    {
        $this->user_id = $request->user()->id;
        $this->controlador = explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1];
        $this->funcion = __FUNCTION__;
        $this->metodo_http = 'get';  //get, post
        $this->url = '';
        $this->permiso_requerido = 'ver facturacion';
        $this->db = 'afiliacion'; // afiliacion, validacion, admin, alfabeta
        $this->sp = 'sp_informe_genera_deuda';
        $this->tipo_id_usuario = 'usuario'; // id, usuario, email, param
        $this->param_id_usuario = 'id_usuario'; // nombre del parámetro id_usuario, id_n_usuario
        $this->param_usuario = ''; // el parámetro cuando no es el id o el nombre
        $this->params = [
            'nro_afiliado' => request('nro_afiliado')
        ];
        $this->params_sp = [
            'nro_afiliado' => $this->params['nro_afiliado'],
        ];
        return $this->ejecutar_sp_simple();
    }

    /**
     * Genera una facttura de ejemplo en pdf
     */
    public function generar_ejemplo_factura(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/afiliaciones/facturacion/generar-ejemplo-factura',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'path' => '',
            'queries' => [],
            'responses' => [],
            'sps' => []
        ];

        try {
            $permiso_requerido = 'exportar datos';
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;
            $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

            $status = 'fail';
            $count = -1;
            $errors = [];
            $message = null;
            $line = null;
            $code = null;
            $data = [];
            
            $accion = request('accion');
            $nro_afiliado = request('nro_afiliado');

            $params = [
                'accion' => $accion,
                'nro_afiliado' => $nro_afiliado
            ];

            if($user->hasPermissionTo($permiso_requerido)){
                // consultamos los sps
                array_push($extras['sps'], ['sp_empresa_propia_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_empresa_propia_Select', null));
                $empresa_propia = $this->ejecutar_sp_directo('afiliacion','sp_empresa_propia_Select', null);
                array_push($extras['responses'], ['sp_empresa_propia_Select' => $empresa_propia]);

                array_push($extras['sps'], ['sp_periodo_Select' => ['activo' => 1]]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_periodo_Select', ['activo' => 1]));
                $periodo = $this->ejecutar_sp_directo('afiliacion','sp_periodo_Select', ['activo' => 1]);
                array_push($extras['responses'], ['sp_periodo_Select' => $periodo]);

                // 1.cabecera 2.aviso 3.estado_cuenta 4.detalle 5.totales 6.cod_barras 9.todos_los_resultados (1,5,6)
                array_push($extras['sps'], ['sp_factura_lst_tmp' => ['tipo_dato' => 1, 'n_afiliado' => $nro_afiliado]]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_factura_lst_tmp', ['tipo_dato' => 1, 'n_afiliado' => $nro_afiliado]));
                $cabecera = $this->ejecutar_sp_directo('afiliacion','sp_factura_lst_tmp', ['tipo_dato' => 1, 'n_afiliado' => $nro_afiliado]);
                array_push($extras['responses'], ['sp_factura_lst_tmp' => $cabecera]);

                array_push($extras['sps'], ['sp_factura_lst_tmp' => ['tipo_dato' => 5, 'n_afiliado' => $nro_afiliado]]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_factura_lst_tmp', ['tipo_dato' => 5, 'n_afiliado' => $nro_afiliado]));
                $vencimientos = $this->ejecutar_sp_directo('afiliacion','sp_factura_lst_tmp', ['tipo_dato' => 5, 'n_afiliado' => $nro_afiliado]);
                array_push($extras['responses'], ['sp_factura_lst_tmp' => $vencimientos]);

                array_push($extras['sps'], ['sp_factura_lst_tmp' => ['tipo_dato' => 6, 'n_afiliado' => $nro_afiliado]]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_factura_lst_tmp', ['tipo_dato' => 6, 'n_afiliado' => $nro_afiliado]));
                $codigos_barra = $this->ejecutar_sp_directo('afiliacion','sp_factura_lst_tmp', ['tipo_dato' => 6, 'n_afiliado' => $nro_afiliado]);
                array_push($extras['responses'], ['sp_factura_lst_tmp' => $codigos_barra]);

                array_push($extras['sps'], ['sp_factura_lst_tmp' => ['tipo_dato' => 4, 'n_afiliado' => $nro_afiliado]]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_factura_lst_tmp', ['tipo_dato' => 4, 'n_afiliado' => $nro_afiliado]));
                $conceptos = $this->ejecutar_sp_directo('afiliacion','sp_factura_lst_tmp', ['tipo_dato' => 4, 'n_afiliado' => $nro_afiliado]);
                array_push($extras['responses'], ['sp_factura_lst_tmp' => $conceptos]);

                $conceptoString = "";        
                $importeString = "";
                if(!isset($conceptos['error'])){
                    foreach ($conceptos as $concepto) {
                        $conceptoString = $conceptoString." ".$concepto->n_periodo. " ".$concepto->n_concepto."<br>";
                        $importeString = $importeString." ".number_format($concepto->importe,2,",",".")."<br>";
                    }
                }
               
                $options = [
                    'format' => ['pdf'],
                    'params' => [
                        'bg_image' => base_path() . '/public/storage/images/' . explode('/', $empresa_propia[0]->bg_image)[sizeof(explode('/', $empresa_propia[0]->bg_image))-1] ?? '',
                        'cod_corto' => $codigos_barra[0]->cod_corto,
                        'cod_largo' => $codigos_barra[0]->cod_largo,
                        'codigoqr' => $empresa_propia[0]->url_qr ?? '',
                        'comercial_direccion' => $empresa_propia[0]->comercial_direccion ?? '',
                        'conceptos' => $conceptoString ?? '',
                        'condicion_iva' => $empresa_propia[0]->condicion_iva ?? '',
                        'cuit_empresa' => $empresa_propia[0]->cuit ?? '',
                        'domicilio' => $cabecera[0]->domicilio == null ? "" : $cabecera[0]->domicilio,
                        'email' => $empresa_propia[0]->email ?? '',
                        'fec_factura' => Carbon::parse($cabecera[0]->fec_factura)->format('d/m/Y'),
                        'ganancias' => $empresa_propia[0]->ganancias ?? '',
                        'id_afip' => $cabecera[0]->id_afip ?? null,
                        'id_periodo' => $periodo[0]->id_periodo,
                        'id_pvta' => $cabecera[0]->id_pvta,
                        'id_tipo_factura' => $cabecera[0]->id_tipo_factura,
                        // 'id_vigencia' => $periodo[0]->id_vigencia,
                        'imp_total' => number_format($vencimientos[0]->imp_total, 2,",","."),
                        'imp_vto1' => number_format($vencimientos[0]->imp_vto1, 2,",","."),
                        'imp_vto2' => number_format($vencimientos[0]->imp_vto2, 2,",","."),
                        'imp_total_afip' => $vencimientos[0]->imp_total,
                        'imp_vto1_afip' => $vencimientos[0]->imp_vto1,
                        'imp_vto2_afip' => $vencimientos[0]->imp_vto2,
                        'importes' => $importeString,
                        'imp_descuento' => $cabecera[0]->imp_descuento,
                        'imp_exento' => $cabecera[0]->imp_exento,
                        'imp_gravamen' => $cabecera[0]->imp_gravamen,
                        'imp_neto' => $cabecera[0]->imp_neto,
                        'imp_recargo' => $cabecera[0]->imp_recargo,
                        'imp_saldo' => $cabecera[0]->imp_saldo,
                        'ingresos_brutos' => $empresa_propia[0]->ingresos_brutos ?? '',
                        'inicio_actividad' => '',
                        'legal_cp' => $empresa_propia[0]->legal_cp ?? '',
                        'legal_direccion' => $empresa_propia[0]->legal_direccion ?? '',
                        'legal_direccion' => $empresa_propia[0]->legal_direccion ?? '',
                        'legal_localidad' => $empresa_propia[0]->legal_localidad ?? '',
                        'legal_provincia' => $empresa_propia[0]->legal_provincia ?? '',
                        'localidad' => $cabecera[0]->n_localidad == null ? "" : $cabecera[0]->n_localidad,
                        'logo_cabecera' => $empresa_propia[0]->logo ?? '',
                        'logo_pie' => $empresa_propia[0]->logo ?? '',
                        'msg_interes' => $periodo[0]->n_texto ?? '', 
                        'msg1' => $empresa_propia[0]->msg1 ?? '',  
                        'n_afiliado' => $cabecera[0]->n_afiliado,
                        'n_persona' => $cabecera[0]->n_persona,
                        'nro_factura' => $cabecera[0]->nro_factura,
                        'numero_link_pagos' => $empresa_propia[0]->numero_link_pagos ?? '',
                        'periodo_image' => $periodo[0]->n_archivo ? '../storage/uploads/' . $periodo[0]->n_archivo : $empresa_propia[0]->logo,
                        'n_periodo' => $cabecera[0]->n_periodo,
                        'periodo' => $cabecera[0]->periodo,
                        'resolucion_iva' => $empresa_propia[0]->resolucion_iva ?? '',
                        'telefono' => $empresa_propia[0]->telefono ?? '',
                        'telefono2' => $empresa_propia[0]->telefono2 ?? '',
                        'texto1' => $empresa_propia[0]->texto1,
                        'texto_code_bar' => $empresa_propia[0]->texto_code_bar ?? '',
                        'texto_link_pagos' => $empresa_propia[0]->texto_link_pagos ?? '',
                        'tipo_documento' => $cabecera[0]->n_tipo_factura,
                        'tratamiento' => $empresa_propia[0]->texto_tratamiento ?? '',
                        'url' => $empresa_propia[0]->url ?? '',
                        'vto1' => Carbon::parse($vencimientos[0]->vto1)->format('d/m/Y'),
                        'vto2' => Carbon::parse($vencimientos[0]->vto2)->format('d/m/Y'),
                        'fch_servicio_desde' => '', // para afip
                        'fch_servicio_hasta' => '', //para afip
                        'fch_vto_pago' => '', // para afip
                        'nombre_carpeta' => $cabecera[0]->periodo.'_'.$periodo[0]->id_periodo,
                    ]
                ];

                $data = [
                    'calle' => $cabecera[0]->calle,
                    'cod_corto' => $options['params']['cod_corto'],
                    'cod_largo' => $options['params']['cod_largo'],
                    'codigo_pagos' => $options['params']['cod_corto'],
                    'codigoqr' => $options['params']['codigoqr'],
                    'comercial_direccion' => $options['params']['comercial_direccion'],
                    'conceptos' => $options['params']['conceptos'],
                    'condicion_iva' => $options['params']['condicion_iva'],
                    'cuit_empresa' => $options['params']['cuit_empresa'],
                    'cuil_afiliado' => $cabecera[0]->cuit,
                    'domicilio' => $cabecera[0]->domicilio,
                    'email' => $options['params']['email'],
                    'fec_factura' => $options['params']['fec_factura'],
                    'fondo' => $options['params']['bg_image'],
                    'ganancias' => $options['params']['ganancias'],
                    'id_factura' => $cabecera[0]->id_factura,
                    'id_periodo' => $options['params']['id_periodo'] ?? 'ultimo_periodo', 
                    'imagen' => $options['params']['periodo_image'],
                    'imp_total' => $options['params']['imp_total'],
                    'imp_vto1' => $options['params']['imp_vto1'],
                    'imp_vto2' => $options['params']['imp_vto2'],
                    'importes' => $options['params']['importes'],
                    'ingresos_brutos' => $options['params']['ingresos_brutos'],
                    'legal_cp' => $options['params']['legal_cp'],
                    'legal_direccion' => $options['params']['legal_direccion'],
                    'legal_direccion' => $options['params']['legal_direccion'],
                    'legal_localidad' => $options['params']['legal_localidad'],
                    'legal_provincia' => $options['params']['legal_provincia'],
                    'localidad' => $cabecera[0]->n_localidad,
                    'logo_cabecera' => $options['params']['logo_cabecera'],
                    'logo_pie' => $options['params']['logo_pie'],
                    'msg_interes' => $options['params']['msg_interes'],
                    'msg_interes' => $options['params']['msg_interes'],
                    'msg1' => $options['params']['msg1'],
                    'n_afiliado' => $options['params']['n_afiliado'],
                    'n_afip' => $cabecera[0]->n_afip,
                    'n_tipo_factura' => $cabecera[0]->n_tipo_factura,
                    'nombre' => $options['params']['n_persona'],
                    'nro_afiliado' => $cabecera[0]->n_afiliado,
                    'numero_factura' => $options['params']['nro_factura'],
                    'numero_link_pagos' => $options['params']['numero_link_pagos'], 
                    'n_periodo' => $options['params']['n_periodo'],
                    'n_plan' => $cabecera[0]->n_plan,
                    'periodo_image' => $options['params']['periodo_image'],
                    'periodo' => $options['params']['periodo'],
                    'resolucion_iva' => $options['params']['resolucion_iva'],
                    'telefono' => $options['params']['telefono'],
                    'telefono2' => $options['params']['telefono2'],
                    'texto_code_bar' => $options['params']['texto_code_bar'],
                    'texto_link_pagos' => $options['params']['texto_link_pagos'],
                    'texto1' => $options['params']['texto1'],
                    'tipo_documento' => $options['params']['tipo_documento'],
                    'tratamiento' => $options['params']['tratamiento'],
                    'url' => $options['params']['url'],
                    'vto1' => $options['params']['vto1'],
                    'vto2' => $options['params']['vto2'],
                    'fe_cae' => $cabecera[0]->fe_cae,
                    'fe_cae_vto' => $cabecera[0]->fe_cae_vto ? Carbon::parse($cabecera[0]->fe_cae_vto)->format('d/m/Y') : '',
                    'fe_cbte_nro' => $cabecera[0]->fe_cbte_nro,
                    'fe_cbte_fecha' => $cabecera[0]->fe_cbte_fecha ? Carbon::parse($cabecera[0]->fe_cbte_fecha)->format('d/m/Y') : '',
                    'nombre_carpeta' => $options['params']['nombre_carpeta'] ?? $options['params']['periodo'].'_'.$options['params']['id_periodo'], // si no viene el parámetro lo forma con el período +_+id_periodo ej: '2022-12_19'
                ];

                // return [
                //     'data' => $data,
                //     'options' => $options,
                //     'extras' => $extras
                // ];

                // Selección del generador de PDF según ambiente
                $pdf = new Fpdi('portrait', 'mm', 'A4');
                $ambiente = env('AMBIENTE_FACTURA', 'general');
                if ($ambiente === 'osceara') {
                    $generator = new FacturaOscearaGeneratorPdf();
                } else {
                    $generator = new FacturaGeneralGeneratorPdf();
                }
                
                $pdf = $generator->generar($pdf, $data);

                if ($accion == 'enviar') {  // almacena el archivo en una ubicación específica y devuelve el path
                    $file_path = env('STORAGE_PATH').'reportes/facturacion_global/'.$data['nombre_carpeta'].'/';
                    if(!File::exists($file_path)){
                        File::makeDirectory($file_path);
                    }
                    $filename = trim($cabecera[0]->pdf_file_name).'.pdf';
                    $file = $file_path.$filename;
                    $extras['path'] = $file;
                    $pdf->Output($file, "F");
                    $data = $filename;
                    $status = 'ok';
                    $count = 1;
                    $errors = [];
                    $message = 'Archivo generado satisfactoriamente';
                    return response()->json([
                        'status' => $status,
                        'count' => $count,
                        'errors' => $errors,
                        'line' => null,
                        'message' => 'Archivo generado y guardado con el nombre: '.$filename,
                        'code' => 1,
                        'data' => $data,
                        'params' => $params,
                        'extras' => $extras,
                        'logged_user' => $logged_user != null ? $logged_user : null
                    ]);
                }else{ // devuelve el archivo almacenado
                    $file_path = env('STORAGE_PATH').'reportes/facturacion_global/'.$data['nombre_carpeta'].'/';
                    if(!File::exists($file_path)){
                        File::makeDirectory($file_path);
                    }
                    $f = $data['nombre_carpeta'].'/'.trim($cabecera[0]->pdf_file_name).'.pdf';
                    $filename = trim($cabecera[0]->pdf_file_name).'.pdf';
                    $file = $file_path.$filename;
                    $extras['path'] = $file;
                    $pdf->Output($file, "F");
                    return FacadeResponse::make(Storage::disk('facturacion_global')->get($f), 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; ' . $f,
                    ]);
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($permiso_requerido);
                $count  = 0;
                $data = null;
                array_push($errors, 'Error de permisos');
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => null,
                    'data' => $data,
                    'params' => $params,
                    'extras' => $extras,
                    'logged_user' => $logged_user
                ]); 
            }
        } catch (\Throwable $th) {
            $extras['path'] = env('STORAGE_PATH').'/reportes/facturacion_global/';
            array_push($errors, 'Error de backend');
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => $data,
                'params' => $params,
                'extras' => $extras,
                'logged_user' => $logged_user != null ? $logged_user : null
            ]);
        }
    }

    /**
     * Genera un pdf de una factura a partir de id_factura
     * @param id_factura number
     */
    public function generar_factura_pdf(Request $request)
    {
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/afiliaciones/facturacion/generar-factura-pdf',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'path' => '',
            'queries' => [],
            'responses' => [],
            'sps' => []
        ];

        try {
            $permiso_requerido = 'exportar datos';
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);
            $usuario_sqlserver_default = 1;
            $id_usuario = $logged_user['id_usuario_sqlserver'] != null ? $logged_user['id_usuario_sqlserver'] : $usuario_sqlserver_default;

            $status = 'fail';
            $count = -1;
            $errors = [];
            $message = null;
            $line = null;
            $code = null;
            $data = [];
            
            $accion = request('accion');
            $id_factura = request('id_factura');

            $params = [
                'accion' => $accion,
                'id_factura' => $id_factura
            ];

            if($user->hasPermissionTo($permiso_requerido)){
                array_push($extras['sps'], ['sp_empresa_propia_Select' => null]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_empresa_propia_Select', null));
                $empresa_propia = $this->ejecutar_sp_directo('afiliacion','sp_empresa_propia_Select', null);
                array_push($extras['responses'], ['sp_empresa_propia_Select' => $empresa_propia]);

                // 1.cabecera 2.aviso 3.estado_cuenta 4.detalle 5.totales 6.cod_barras 9.todos_los_resultados (1,5,6)
                array_push($extras['sps'], ['sp_factura_lst' => ['tipo_dato' => 1, 'id_factura' => $id_factura]]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_factura_lst', ['tipo_dato' => 1, 'id_factura' => $id_factura]));
                $cabecera = $this->ejecutar_sp_directo('afiliacion','sp_factura_lst', ['tipo_dato' => 1, 'id_factura' => $id_factura]);
                array_push($extras['responses'], ['sp_factura_lst' => $cabecera]);

                array_push($extras['sps'], ['sp_factura_lst' => ['tipo_dato' => 4, 'id_factura' => $id_factura]]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_factura_lst', ['tipo_dato' => 4, 'id_factura' => $id_factura]));
                $conceptos = $this->ejecutar_sp_directo('afiliacion','sp_factura_lst', ['tipo_dato' => 4, 'id_factura' => $id_factura]);
                array_push($extras['responses'], ['sp_factura_lst' => $conceptos]);

                array_push($extras['sps'], ['sp_factura_lst' => ['tipo_dato' => 5, 'id_factura' => $id_factura]]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_factura_lst', ['tipo_dato' => 5, 'id_factura' => $id_factura]));
                $vencimientos = $this->ejecutar_sp_directo('afiliacion','sp_factura_lst', ['tipo_dato' => 5, 'id_factura' => $id_factura]);
                array_push($extras['responses'], ['sp_factura_lst' => $vencimientos]);

                array_push($extras['sps'], ['sp_factura_lst' => ['tipo_dato' => 6, 'id_factura' => $id_factura]]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_factura_lst', ['tipo_dato' => 6, 'id_factura' => $id_factura]));
                $codigos_barra = $this->ejecutar_sp_directo('afiliacion','sp_factura_lst', ['tipo_dato' => 6, 'id_factura' => $id_factura]);
                array_push($extras['responses'], ['sp_factura_lst' => $codigos_barra]);

                array_push($extras['sps'], ['sp_periodo_Select' => ['activo' => 1]]);
                array_push($extras['queries'], $this->get_query('afiliacion', 'sp_periodo_Select', ['activo' => 1]));
                $periodo = $this->ejecutar_sp_directo('afiliacion','sp_periodo_Select', ['activo' => 1]);
                array_push($extras['responses'], ['sp_periodo_Select' => $periodo]);

                $conceptoString = "";        
                $importeString = "";
                foreach ($conceptos as $concepto) {
                    $conceptoString = $conceptoString." ".$concepto->n_periodo. " ".$concepto->n_concepto."<br>";
                    $importeString = $importeString." ".number_format($concepto->importe,2,",",".")."<br>";
                }

                
                $options = [
                    'format' => ['pdf'],
                    'params' => [
                        'aviso' => null,
                        'bg_image' => base_path() . '/public/storage/images/' . explode('/', $empresa_propia[0]->bg_image)[sizeof(explode('/', $empresa_propia[0]->bg_image))-1] ?? '',
                        'cod_corto' => $codigos_barra[0]->cod_corto,
                        'cod_largo' => $codigos_barra[0]->cod_largo,
                        'codigoqr' => $empresa_propia[0]->url_qr ?? '',
                        'comercial_direccion' => $empresa_propia[0]->comercial_direccion ?? '',
                        'conceptos' => $conceptoString ?? '',
                        'condicion_iva' => $empresa_propia[0]->condicion_iva ?? '',
                        'cuit_empresa' => $empresa_propia[0]->cuit ?? '',
                        'domicilio' => $cabecera[0]->domicilio == null ? "" : $cabecera[0]->domicilio,
                        'email' => $empresa_propia[0]->email ?? '',
                        'fec_factura' => Carbon::parse($cabecera[0]->fec_factura)->format('d/m/Y'),
                        'ganancias' => $empresa_propia[0]->ganancias ?? '',
                        'id_afip' => $cabecera[0]->id_afip ?? null,
                        'id_periodo' => $periodo[0]->id_periodo,
                        'id_pvta' => $cabecera[0]->id_pvta,
                        'id_tipo_factura' => $cabecera[0]->id_tipo_factura,
                        'imp_total' => number_format($vencimientos[0]->imp_total, 2,",","."),
                        'imp_vto1' => number_format($vencimientos[0]->imp_vto1, 2,",","."),
                        'imp_vto2' => number_format($vencimientos[0]->imp_vto2, 2,",","."),
                        'imp_total_afip' => $vencimientos[0]->imp_total,
                        'imp_vto1_afip' => $vencimientos[0]->imp_vto1,
                        'imp_vto2_afip' => $vencimientos[0]->imp_vto2,
                        'importes' => $importeString,
                        'imp_descuento' => $cabecera[0]->imp_descuento,
                        'imp_exento' => $cabecera[0]->imp_exento,
                        'imp_gravamen' => $cabecera[0]->imp_gravamen,
                        'imp_neto' => $cabecera[0]->imp_neto,
                        'imp_recargo' => $cabecera[0]->imp_recargo,
                        'imp_saldo' => $cabecera[0]->imp_saldo,
                        'ingresos_brutos' => $empresa_propia[0]->ingresos_brutos ?? '',
                        'legal_cp' => $empresa_propia[0]->legal_cp ?? '',
                        'legal_direccion' => $empresa_propia[0]->legal_direccion ?? '',
                        'legal_direccion' => $empresa_propia[0]->legal_direccion ?? '',
                        'legal_localidad' => $empresa_propia[0]->legal_localidad ?? '',
                        'legal_provincia' => $empresa_propia[0]->legal_provincia ?? '',
                        'localidad' => $cabecera[0]->n_localidad == null ? "" : $cabecera[0]->n_localidad,
                        'logo_cabecera' => $empresa_propia[0]->logo ?? '',
                        'logo_pie' => $empresa_propia[0]->logo ?? '',
                        'msg_interes' => $periodo[0]->n_texto ?? '', 
                        'msg1' => $empresa_propia[0]->msg1 ?? '',  
                        'n_afiliado' => $cabecera[0]->n_afiliado,
                        'n_persona' => $cabecera[0]->n_persona,
                        'nro_factura' => $cabecera[0]->nro_factura,
                        'numero_link_pagos' => $empresa_propia[0]->numero_link_pagos ?? '',
                        'periodo_image' => $periodo[0]->n_archivo ? '../storage/uploads/' . $periodo[0]->n_archivo : $empresa_propia[0]->logo,
                        'n_periodo' => $cabecera[0]->n_periodo,
                        'periodo' => $cabecera[0]->periodo,
                        'resolucion_iva' => $empresa_propia[0]->resolucion_iva ?? '',
                        'telefono' => $empresa_propia[0]->telefono ?? '',
                        'telefono2' => $empresa_propia[0]->telefono2 ?? '',
                        'texto1' => $empresa_propia[0]->texto1,
                        'texto_code_bar' => $empresa_propia[0]->texto_code_bar ?? '',
                        'texto_link_pagos' => $empresa_propia[0]->texto_link_pagos ?? '',
                        'tipo_documento' => $cabecera[0]->n_tipo_factura,
                        'tratamiento' => $empresa_propia[0]->texto_tratamiento ?? '',
                        'url' => $empresa_propia[0]->url ?? '',
                        'vto1' => Carbon::parse($vencimientos[0]->vto1)->format('d/m/Y'),
                        'vto2' => Carbon::parse($vencimientos[0]->vto2)->format('d/m/Y'),
                        'fch_servicio_desde' => Carbon::parse($periodo[0]->fec_dde)->format('Ymd'), // para afip
                        'fch_servicio_hasta' => Carbon::parse($periodo[0]->fec_hta)->format('Ymd'), //para afip
                        'fch_vto_pago' => $periodo[0]->fec_1vto, // para afip
                        'nombre_carpeta' => $cabecera[0]->periodo.'_'.$periodo[0]->id_periodo,
                        
                        // 'texto1' => 'El pago de la presente cuota no hace presumir la cancelación de las anteriores',
                        // 'bg_image' => base_path() . '/public/storage/reportes/cuenta_corriente_bg.jpg',
                        // 'ms_children' => base_path() .'/public/storage/reportes/ms_children.jpg',
                        // 'msg_interes' => 'Ingrese a www.casa.org.ar para enterarse de como realizar todas las '.
                        //     'gestiones online. Se prorrogó el plazo para presentar certificados de '.
                        //     'alumno regular de hijos de entre 21 y 25 años hasta el 31/05/20.',
                    ]
                ];

                $data = [
                    'calle' => $cabecera[0]->calle,
                    'cod_corto' => $options['params']['cod_corto'],
                    'cod_largo' => $options['params']['cod_largo'],
                    'codigo_pagos' => $options['params']['cod_corto'],
                    'codigoqr' => $options['params']['codigoqr'],
                    'comercial_direccion' => $options['params']['comercial_direccion'],
                    'conceptos' => $options['params']['conceptos'],
                    'condicion_iva' => $options['params']['condicion_iva'],
                    'cuit_empresa' => $options['params']['cuit_empresa'],
                    'domicilio' => $cabecera[0]->domicilio,
                    'email' => $options['params']['email'],
                    'fecha_factura' => $options['params']['fec_factura'],
                    'fondo' => $options['params']['bg_image'],
                    'ganancias' => $options['params']['ganancias'],
                    'id_factura' => $cabecera[0]->id_factura,
                    'id_periodo' => $options['params']['id_periodo'] ?? 'ultimo_periodo', 
                    'imagen' => $options['params']['periodo_image'],
                    'imp_total' => $options['params']['imp_total'],
                    'imp_vto1' => $options['params']['imp_vto1'],
                    'imp_vto2' => $options['params']['imp_vto2'],
                    'importes' => $options['params']['importes'],
                    'ingresos_brutos' => $options['params']['ingresos_brutos'],
                    'legal_cp' => $options['params']['legal_cp'],
                    'legal_direccion' => $options['params']['legal_direccion'],
                    'legal_direccion' => $options['params']['legal_direccion'],
                    'legal_localidad' => $options['params']['legal_localidad'],
                    'legal_provincia' => $options['params']['legal_provincia'],
                    'localidad' => $cabecera[0]->n_localidad,
                    'logo_cabecera' => $options['params']['logo_cabecera'],
                    'logo_pie' => $options['params']['logo_pie'],
                    'msg_interes' => $options['params']['msg_interes'],
                    'msg_interes' => $options['params']['msg_interes'],
                    'msg1' => $options['params']['msg1'],
                    'n_afiliado' => $options['params']['n_afiliado'],
                    'n_afip' => $cabecera[0]->n_afip,
                    'n_tipo_factura' => $cabecera[0]->n_tipo_factura,
                    'nombre' => $options['params']['n_persona'],
                    'nro_afiliado' => $cabecera[0]->n_afiliado,
                    'numero_factura' => $options['params']['nro_factura'],
                    'numero_link_pagos' => $options['params']['numero_link_pagos'], 
                    'periodo_image' => $options['params']['periodo_image'],
                    'n_periodo' => $options['params']['n_periodo'],
                    'periodo' => $options['params']['periodo'],
                    'resolucion_iva' => $options['params']['resolucion_iva'],
                    'telefono' => $options['params']['telefono'],
                    'telefono2' => $options['params']['telefono2'],
                    'texto_code_bar' => $options['params']['texto_code_bar'],
                    'texto_link_pagos' => $options['params']['texto_link_pagos'],
                    'texto1' => $options['params']['texto1'],
                    'tipo_documento' => $options['params']['tipo_documento'],
                    'tratamiento' => $options['params']['tratamiento'],
                    'url' => $options['params']['url'],
                    'vto1' => $options['params']['vto1'],
                    'vto2' => $options['params']['vto2'],
                    'fe_cae' => $cabecera[0]->fe_cae,
                    'fe_cae_vto' => $cabecera[0]->fe_cae_vto ? Carbon::parse($cabecera[0]->fe_cae_vto)->format('d/m/Y') : '',
                    'fe_cbte_nro' => $cabecera[0]->fe_cbte_nro,
                    'fe_cbte_fecha' => $cabecera[0]->fe_cbte_fecha ? Carbon::parse($cabecera[0]->fe_cbte_fecha)->format('d/m/Y') : '',
                    'nombre_carpeta' => $options['params']['nombre_carpeta'] ?? $options['params']['periodo'].'_'.$options['params']['id_periodo'], // si no viene el parámetro lo forma con el período +_+id_periodo ej: '2022-12_19'
                    'aviso' => $options['params']['aviso']
                ];
                //  -------------------
                //	crea el objeto y establece variables
                // // $pdf = new \fpdf\FPDF('portrait', 'mm', 'A4');
                // $pdf = new Fpdi('portrait', 'mm', 'A4');
        
                // //  font setup
                // $font = 'Arial';
                // //  -------------------
                // $pdf->SetMargins(15, 15);
                // // Añade una página
                // $pdf->AddPage('portrait');
                
                // //  establece valor de tipografia
                // $pdf->SetFont($font, '', 10);
                
                // // cabecera -------------------------------------------------------------------------------------------
                // // líneas 
                // // borde exterior
                // $pdf->SetLineWidth(0.5);
                // $pdf->Line(15, 15, 15, 187); // línea verical izquierda
                // $pdf->Line(195, 15, 195, 187); // línea verical derecha
                // $pdf->Line(15, 15, 195, 15); // línea superior
                // $pdf->Line(15, 187, 195, 187); // línea inferior
                // //línea central y cuadrado central
                // $pdf->SetLineWidth(0.3);
                // $pdf->Line(105, 15, 105, 22); // vertical sobre recuadro
                // $pdf->Line(89, 22, 89, 34); // izquierda
                // $pdf->Line(121, 22, 121, 34); // derecha
                // $pdf->Line(89, 22, 121, 22); // superior
                // $pdf->Line(89, 34, 121, 34); // inferior
                // $pdf->Line(105, 34, 105, 50); // vertical bajo recuadro
                // $pdf->Line(15, 50, 195, 50); // división
                // $pdf->Line(131, 50, 131, 68); // división vertical
                // $pdf->Line(15, 68, 195, 68); // final cabecera
                // $pdf->Line(131, 59, 195, 59); // final cabecera
                // // datos
                // // logo
                // $pdf->Image(storage_path('app/public/images/'.$data['logo_cabecera']), 17, 20, 60, 0, 'PNG');
                // // tipo
                // $pdf->SetFont($font, '', 10);
                // $pdf->SetY(22);
                // $pdf->SetX(89);
                // $pdf->MultiCell(32, 6, utf8_decode($data['tipo_documento']), 0, 'C');
                // // tipo de documento
                // $pdf->SetFont($font, '', 8);
                // $pdf->SetY(22);
                // $pdf->SetX(131);
                // $pdf->Cell(22, 5, utf8_decode($data['tipo_documento']), 0, 0, 'L');
                // //número de factura
                // $pdf->SetFont($font, '', 8);
                // $pdf->SetY(26);
                // $pdf->SetX(131);
                // $numero_factura = $data['fe_cbte_nro'] != null ? 'N°: '.$data['fe_cbte_nro'] : 'N°: '.$data['numero_factura'];
                // $pdf->Cell(22, 5, utf8_decode($numero_factura), 0, 0, 'L');
                // // fecha de factura
                // $fecha_factura = $data['fe_cbte_fecha'] != null ? 'Fecha de emisión: '.$data['fe_cbte_fecha'] : 'Fecha de emisión: '.$data['fecha_factura'];
                // $pdf->SetY(30);
                // $pdf->SetX(131);
                // $pdf->Cell(22, 5, utf8_decode($fecha_factura), 0, 0, 'L');
                // // hoja n° de total
                // $hoja = 'Hoja 1 de 1';
                // $pdf->SetFont($font, '', 8);
                // $pdf->SetY(22);
                // $pdf->SetX(170);
                // $pdf->Cell(19, 5, utf8_decode($hoja), 0, 0, 'L');
                // // datos obra social recuadro izquierdo
                // $pdf->SetFont($font, '', 6);
                // $pdf->SetY(40);
                // $pdf->SetX(17);
                // $pdf->Cell(45, 3, utf8_decode($data['legal_direccion']), 0, 0, 'L');
                // $pdf->Cell(40, 3, '', 0, 0, 'R');  // lugar para otro dato
                // $pdf->SetY(43);
                // $pdf->SetX(17);
                // $pdf->Cell(45, 3, utf8_decode($data['telefono']), 0, 0, 'L');
                // $pdf->SetFont($font, '', 6);
                // $pdf->Cell(40, 3, utf8_decode($data['email']), 0, 0, 'R');
                // $pdf->SetFont($font, 'B', 6);
                // $pdf->SetY(46);
                // $pdf->SetX(17);
                // $pdf->Cell(45, 3, utf8_decode($data['telefono2']), 0, 0, 'L');
                // $pdf->Cell(40, 3, utf8_decode($data['url']), 0, 0, 'R');
                // // datos empresa recuadro derecho superior
                // $pdf->SetFont($font, '', 6);
                // $cuit = 'CUIT: '.$data['cuit_empresa'];
                // $pdf->SetY(34);
                // $pdf->SetX(131);
                // $pdf->Cell(45, 3, utf8_decode($cuit), 0, 0, 'L'); 
                // // $ingresos_brutos = 'Ingresos Brutos: '.$data['ingresos_brutos'];
                // // $pdf->SetY(37);
                // // $pdf->SetX(131);
                // // $pdf->Cell(45, 3, utf8_decode($ingresos_brutos), 0, 0, 'L'); 
                // // $ganancias = 'Ganancias: '.$data['ganancias'];
                // // $pdf->SetY(40);
                // // $pdf->SetX(131);
                // // $pdf->Cell(45, 3, utf8_decode($ganancias), 0, 0, 'L'); 
                // $pdf->SetFont($font, '', 6);
                // $pdf->SetY(43);
                // $pdf->SetX(131);
                // $condicion_iva = 'Condición frente al IVA: ' . $data['condicion_iva'];
                // $pdf->Cell(45, 3, utf8_decode($condicion_iva), 0, 0, 'L'); 
                // $pdf->SetY(46);
                // $pdf->SetX(131);
                // $pdf->Cell(45, 3, utf8_decode($data['resolucion_iva']), 0, 0, 'L'); 
                // // código qr
                // // generamos el código y lo guardamos
                // // QrCode::format('png')->size(100)->generate($data['codigoqr'], public_path("storage/images/qrcode.png"));
                // // agregamos la imágen desde el archivo
                // // $pdf->Image(public_path("storage/images/qrcode.png"), 106, 35, 14, 14, 'PNG');
                // // datos recuadro iquierdo grande inferior
                // // número afiliadoafiliado
                // $pdf->SetFont($font, '', 6);
                // $pdf->SetY(52);
                // $pdf->SetX(17);
                // $pdf->Cell(12, 3, utf8_decode('Afiliado N°: '), 0, 0, 'L');
                // $pdf->SetFont($font, 'B', 6);
                // $pdf->Cell(55, 3, utf8_decode($data['nro_afiliado']), 0, 0, 'L');
                // // periodo
                // $pdf->SetFont($font, '', 6);
                // $pdf->SetY(52);
                // $pdf->SetX(85);
                // $pdf->Cell(10, 3, utf8_decode('Período: '), 0, 0, 'L');
                // $pdf->SetFont($font, 'B', 6);
                // $pdf->SetY(52);
                // $pdf->SetX(95);
                // $pdf->Cell(30, 3, utf8_decode($data['periodo']), 0, 0, 'L');
                // // nombre afiliado
                // $pdf->SetFont($font, '', 6);
                // $pdf->SetY(56);
                // $pdf->SetX(17);
                // $pdf->Cell(12, 3, utf8_decode($data['tratamiento']), 0, 0, 'L');
                // $pdf->SetFont($font, 'B', 6);
                // $pdf->Cell(55, 3, utf8_decode($data['nombre']), 0, 0, 'L');
                // // domicilio
                // $pdf->SetFont($font, '', 6);
                // $pdf->SetY(60);
                // $pdf->SetX(17);
                // $pdf->Cell(12, 3, utf8_decode('Domicililo'), 0, 0, 'L');
                // $pdf->SetFont($font, 'B', 6);
                // $pdf->Cell(55, 3, utf8_decode($data['domicilio']), 0, 0, 'L');
                // // localidad
                // $pdf->SetFont($font, '', 6);
                // $pdf->SetY(64);
                // $pdf->SetX(17);
                // $pdf->Cell(12, 3, utf8_decode('Localidad'), 0, 0, 'L');
                // $pdf->SetFont($font, 'B', 6);
                // $pdf->Cell(55, 3, utf8_decode($data['localidad']), 0, 0, 'L');
                // // datos recuadro derecho pequeño inferior
                // // primer vencimiento
                // $pdf->SetFont($font, '', 8);
                // $pdf->SetY(53);
                // $pdf->SetX(133);
                // $pdf->Cell(12, 3, utf8_decode('Hasta el '), 0, 0, 'L');
                // $pdf->SetFont($font, 'B', 8);
                // $pdf->Cell(14, 3, utf8_decode($data['vto1']), 0, 0, 'L');
                // // importe primer vencimiento
                // $pdf->SetFont($font, '', 8);
                // $pdf->SetY(53);
                // $pdf->SetX(165);
                // $pdf->Cell(14, 3, utf8_decode('Importe: $ '), 0, 0, 'L');
                // $pdf->SetFont($font, 'B', 8);
                // $pdf->Cell(16, 3, utf8_decode($data['imp_vto1']), 0, 0, 'L');
                // // segundo vencimiento
                // $pdf->SetFont($font, '', 8);
                // $pdf->SetY(63);
                // $pdf->SetX(133);
                // $pdf->Cell(12, 3, utf8_decode('Hasta el '), 0, 0, 'L');
                // $pdf->SetFont($font, 'B', 8);
                // $pdf->Cell(14, 3, utf8_decode($data['vto2']), 0, 0, 'L');
                // // importe segundo vencimiento
                // $pdf->SetFont($font, '', 8);
                // $pdf->SetY(63);
                // $pdf->SetX(165);
                // $pdf->Cell(14, 3, utf8_decode('Importe: $ '), 0, 0, 'L');
                // $pdf->SetFont($font, 'B', 8);
                // $pdf->Cell(16, 3, utf8_decode($data['imp_vto2']), 0, 0, 'L');
                // // FIN CABECERA --------------------------------------------------------------------------------------------------
        
                // // DETALLE DE FACTURA --------------------------------------------------------------------------------------------
                // $pdf->SetY(70);
                // $pdf->SetX(17);
                // $pdf->SetLineWidth(0.1);
                // $pdf->SetFont($font, 'B', 8);
                // $pdf->SetFillColor(26,121,204);
                // $pdf->SetTextColor(255,255,255);
                // $pdf->SetDrawColor(153,153,153);
                // $pdf->Cell(176, 6, utf8_decode('DETALLE DEL PERÍODO'), 1, 0, 'C', true);
                // $pdf->Rect(17,76,176,88);
                // // imagen de fondo
                // $pdf->Image($data['fondo'], 18, 78, 158, 77, 'JPG');
                // $pdf->SetFont($font, '', 8);
                // $pdf->SetTextColor(53,53,53);
                // $pdf->SetY(78);
                // $pdf->SetX(20);
                // $pdf->Cell(120, 5, utf8_decode('CONCEPTO'), 0, 0, 'L');
                // $pdf->Cell(50, 5, utf8_decode('IMPORTE  '), 0, 0, 'R');
                // $pdf->SetLineWidth(0.3);
                // $pdf->SetDrawColor(53,53,53);
                // $pdf->Line(20, 83, 190, 83); // linea conceptos-importe
                // // conceptos
                // $pdf->SetFont($font, '', 7);
                // $pdf->SetTextColor(5,5,5);
                // $pdf->SetY(85);
                // $pdf->SetX(20);
                // $conceptos = explode('<br>', $data['conceptos']);
                // foreach($conceptos as $concepto){
                //     $pdf->SetX(20);
                //     if(strlen(utf8_decode(trim($concepto)))>90){
                //         $concepto = substr(utf8_decode(trim($concepto)), 0, 88);
                //         $concepto = $concepto.'...';
                //     }else{
                //         $concepto = utf8_decode(trim($concepto));
                //     }
                //     $pdf->MultiCell(170, 3, $concepto, 0, 'L');
                // }
                // // importes
                // $pdf->SetFont($font, 'B', 7);
                // $pdf->SetY(85);
                // $pdf->SetX(170);
                // $importes = explode('<br>', $data['importes']);
                // foreach($importes as $importe){
                //     if($importe != ''){
                //         $pdf->SetX(170);
                //         $pdf->MultiCell(20, 3, '$ '.utf8_decode($importe), 0, 'R');
                //     }
                // }
                // // aclaraciones finales
                // $pdf->SetFont($font, 'B', 8);
                // $pdf->SetTextColor(5,5,5);
                // $pdf->SetY(155);
                // $pdf->SetX(20);
                // $pdf->Cell(50, 5, utf8_decode($data['texto1']), 0, 0, 'L');
                // $pdf->SetY(159);
                // $pdf->SetX(20);
                // $pdf->SetFont($font, '', 8);
                // $pdf->Cell(50, 5, utf8_decode($data['numero_link_pagos']), 0, 0, 'L');
                // // CAE y vencimiento CAE para el afiliado
                // if($data['fe_cae'] != null){
                //     $pdf->setFont('Arial', 'BI', 9);
                //     $pdf->SetTextColor(0,0,0);
                //     $pdf->SetY(160);
                //     $pdf->SetX(100);
                //     $pdf->Cell(45, 3, 'CAE: '.$data['fe_cae'], 0, 0, 'L');
                //     $pdf->setFont('Arial', '', 8);
                //     $pdf->SetTextColor(0,0,0);
                //     $pdf->SetY(160);
                //     $pdf->SetX(145);
                //     $pdf->Cell(50, 3, 'Vencimiento CAE: '.$data['fe_cae_vto'], 0, 0, 'L');
                // }
                // // mensajes de interes
                // $pdf->SetFont($font, '', 8);
                // $pdf->SetY(167);
                // $pdf->SetX(20);
                // $pdf->Cell(50, 5, utf8_decode('MENSAJE DE SU INTERÉS'), 0, 0, 'L');
                // $pdf->SetFont($font, 'B', 8);
                // $pdf->SetY(167);
                // $pdf->SetX(85);
                // $pdf->Cell(50, 5, utf8_decode($data['msg1']), 0, 0, 'R');
                // $pdf->SetFont($font, '', 8);
                // $pdf->SetY(172);
                // $pdf->SetX(20);
                // $pdf->MultiCell(115, 3, utf8_decode($data['msg_interes']), 0, 'J');
                // if($data['aviso'] != null){
                //     $pdf->SetFont($font, 'B', 8);
                //     $pdf->SetY(178);
                //     $pdf->SetX(20);
                //     $pdf->Cell(115, 5, utf8_decode($data['aviso']), 0, 0, 'L');
                //     $pdf->SetFont($font, '', 8);
                // }
                // // total
                // $pdf->SetFont($font, '', 10);
                // $pdf->SetY(173);
                // $pdf->SetX(135);
                // $pdf->Cell(50, 5, utf8_decode('TOTAL'), 0, 0, 'L');
                // $pdf->SetFont($font, 'B', 12);
                // $pdf->SetY(170);
                // $pdf->SetX(150);
                // $pdf->SetFillColor(220,220,220);
                // $pdf->SetTextColor(0,0,0);
                // $pdf->SetDrawColor(73,73,73);
                // $pdf->SetLineWidth(0.5);
                // $pdf->Cell(43, 10, '$ '.utf8_decode($data['imp_total']), 1, 0, 'R', TRUE);
                // // FIN DETALLE --------------------------------------------------------------------------------------------------------
                
                // // "talón para el afiliado"
                // $pdf->SetFont($font, 'B', 8);
                // $pdf->SetTextColor(0,0,0);
                // $pdf->SetY(188);
                // $pdf->SetX(15);
                // $pdf->Cell(40, 3, utf8_decode('TALÓN PARA EL AFILIADO'), 0, 0, 'L');
                // // imagen ms_children
                // $pdf->Image(base_path().'/public/storage/images/'.$data['imagen'], 15, 192, 110, 0, 'PNG');
                
                // // código barras Pada Caja de Abogados
                // $pdf->SetFont($font, 'B', 10);
                // $pdf->SetTextColor(0,0,0);
                // $pdf->SetY(192);
                // $pdf->SetX(130);
                // $pdf->Cell(65, 4, $data['texto_code_bar'], 0, 0, 'C');
                // // $pdf, $xpos, $ypos, $code, $baseline = 0.5, $height = 5, $show_text = FALSE
                // // código de barras
                // $pdf->SetFillColor(0,0,0);
                // $pdf->Cell(65, 4, get_code39($pdf, 130, 196, $data['cod_corto'], 0.5, 15), 0, 0, 'C');
                // // número del código de barras
                // $pdf->SetFont('Arial','',9);
                // $pdf->SetTextColor(0,0,0);
                // $pdf->SetY(212);
                // $pdf->SetX(130);
                // $pdf->Cell(65, 4, $data['cod_corto'], 0, 0, 'C');
        
                // // PIE DE PÁGINA ------------------------------------------------------------------------------------------------------
                // // línea de separación
                // // línea de puntos 
                // for( $i=1; $i < 210; $i+=2){
                //     $pdf->SetX($i);
                //     $pdf->Line($i, 223, $i+1, 223); 
                // }
                // // logo chico
                // $pdf->Image(storage_path('app/public/images/'.$data['logo_pie']), 15, 226, 50, 0, 'PNG');
                // // datos afiliado talon banco
                // $pdf->SetFont($font, '', 6);
                // $pdf->SetTextColor(30,30,30);
                // $cuit = 'CUIT: '.$data['cuit_empresa'];
                // $pdf->SetY(255);
                // $pdf->SetX(15);
                // $pdf->Cell(50, 3, utf8_decode($cuit), 0, 0, 'L'); 
                // $pdf->SetFont($font, '', 6);
                // $pdf->SetY(258);
                // $pdf->SetX(15);
                // $pdf->Cell(50, 3, 'IVA '.utf8_decode($data['condicion_iva']), 0, 0, 'L'); 
                // $pdf->SetY(261);
                // $pdf->SetX(15);
                // $pdf->Cell(50, 3, utf8_decode($data['resolucion_iva']), 0, 0, 'L'); 
                // // talon para el banco
                // $pdf->SetFont($font, 'B', 8);
                // $pdf->SetTextColor(0,0,0);
                // $pdf->SetY(267);
                // $pdf->SetX(15);
                // $pdf->Cell(50, 3, utf8_decode('TALÓN PARA EL BANCO'), 0, 0, 'L'); 
                // // N° Resumen
                // $pdf->SetY(226);
                // $pdf->SetX(70);
                // $pdf->SetLineWidth(0.1);
                // $pdf->SetFont($font, 'B', 8);
                // $pdf->SetFillColor(26,121,204);
                // $pdf->SetTextColor(255,255,255);
                // $pdf->SetDrawColor(153,153,153);
                // $pdf->Cell(40, 6, utf8_decode('N° Resumen'), 1, 2, 'C', true);
                // $pdf->SetFillColor(255,255,255);
                // $pdf->SetTextColor(0,0,0);
                // $pdf->SetFont($font, '', 8);
                // $pdf->Cell(40, 6, utf8_decode($data['numero_factura']), 1, 0, 'C', true);
                // // Afiliado y período
                // $pdf->SetY(241);
                // $pdf->SetX(70);
                // $pdf->SetLineWidth(0.1);
                // $pdf->SetFont($font, 'B', 8);
                // $pdf->SetFillColor(26,121,204);
                // $pdf->SetTextColor(255,255,255);
                // $pdf->SetDrawColor(153,153,153);
                // $pdf->Cell(20, 6, utf8_decode('N° Afiliado'), 1, 0, 'C', true);
                // $pdf->Cell(20, 6, utf8_decode('Período'), 1, 2, 'C', true);
                // $pdf->SetX(70);
                // $pdf->SetFillColor(255,255,255);
                // $pdf->SetTextColor(0,0,0);
                // $pdf->SetFont($font, '', 8);
                // $pdf->Cell(20, 6, utf8_decode($data['n_afiliado']), 1, 0, 'C', true);
                // $pdf->SetFont($font, '', 8);
                // $pdf->Multicell(20, 6, utf8_decode($data['periodo']), 1, 'C');
                // // Fecha Importe
                // $pdf->SetY(226);
                // $pdf->SetX(112);
                // $pdf->SetLineWidth(0.1);
                // $pdf->SetFont($font, '', 8);
                // $pdf->SetFillColor(26,121,204);
                // $pdf->SetTextColor(255,255,255);
                // $pdf->SetDrawColor(153,153,153);
                // $pdf->Cell(20, 6, utf8_decode('Hasta el'), 1, 0, 'C', true);
                // $pdf->Cell(20, 6, utf8_decode('Importe'), 1, 2, 'C', true);
                // $pdf->SetX(112);
                // $pdf->SetFillColor(255,255,255);
                // $pdf->SetTextColor(0,0,0);
                // $pdf->SetFont($font, '', 8);
                // $pdf->Cell(20, 6, utf8_decode($data['vto1']), 1, 0, 'C', true);
                // $pdf->Cell(20, 6, '$ '.utf8_decode($data['imp_vto1']), 1, 2, 'C');
                // // Fecha Importe 2
                // $pdf->SetY(241);
                // $pdf->SetX(112);
                // $pdf->SetLineWidth(0.1);
                // $pdf->SetFont($font, 'B', 8);
                // $pdf->SetFillColor(26,121,204);
                // $pdf->SetTextColor(255,255,255);
                // $pdf->SetDrawColor(153,153,153);
                // $pdf->Cell(20, 6, utf8_decode('Hasta el'), 1, 0, 'C', true);
                // $pdf->Cell(20, 6, utf8_decode('Importe'), 1, 2, 'C', true);
                // $pdf->SetX(112);
                // $pdf->SetFillColor(255,255,255);
                // $pdf->SetTextColor(0,0,0);
                // $pdf->SetFont($font, '', 8);
                // $pdf->Cell(20, 6, utf8_decode($data['vto2']), 1, 0, 'C', true);
                // $pdf->Cell(20, 6, '$ '.utf8_decode($data['imp_vto2']), 1, 2, 'C');
                // // pago
                // // $pdf->SetLineWidth(0.1);
                // // $pdf->SetFont($font, '', 6);
                // // $pdf->SetFillColor(235,235,235);
                // // $pdf->SetTextColor(0,0,0);
                // // $pdf->SetDrawColor(153,153,153);
                // // $pdf->SetY(226);
                // // $pdf->SetX(155);
                // // $pdf->Cell(8, 4, utf8_decode('24 Hs.'), 1, 0, 'C', true);
                // // $pdf->SetY(226);
                // // $pdf->SetX(165);
                // // $pdf->Cell(8, 4, utf8_decode('48 Hs.'), 1, 0, 'C', true);
                // // $pdf->SetY(226);
                // // $pdf->SetX(175);
                // // $pdf->Cell(8, 4, utf8_decode('72 Hs.'), 1, 0, 'C', true);
                // // $pdf->SetY(226);
                // // $pdf->SetX(185);
                // // $pdf->Cell(12, 4, utf8_decode('Efectivo'), 1, 0, 'C', true);
                // // $pdf->SetY(232);
                // // $pdf->SetX(158);
                // // $pdf->Cell(8, 3, utf8_decode('Banco: '), 0, 0, 'R');
                // // $pdf->Line(167, 235, 197, 235);
                // // $pdf->SetY(237);
                // // $pdf->SetX(158);
                // // $pdf->Cell(8, 3, utf8_decode('Ch. N°: '), 0, 0, 'R');
                // // $pdf->Line(167, 240, 197, 240);
                // // $pdf->SetY(242);
                // // $pdf->SetX(158);
                // // $pdf->Cell(8, 3, utf8_decode('En Letras: '), 0, 0, 'R');
                // // $pdf->Line(167, 245, 197, 245);
                // // código de barras
                // $pdf->SetFont('Arial','',9);
                // $pdf->SetTextColor(0,0,0);
                // $pdf->SetY(256);
                // $pdf->SetX(55);
                // $pdf->Cell(144, 4, $data['texto_link_pagos'], 0, 0, 'C');
                // $pdf->SetFillColor(0,0,0);
                // $pdf->Cell(65, 4, get_code39($pdf, 130, 196, $data['cod_largo'], 0.5, 15), 0, 0, 'C');
                // // número del código de barras
                // $pdf->SetFont('Arial','',9);
                // $pdf->SetTextColor(0,0,0);
                // $pdf->SetY(268);
                // $pdf->SetX(55);
                // $pdf->Cell(144, 4, $data['cod_largo'], 0, 0, 'C');
                // // CAE y vencimiento CAE para el banco
                // if($data['fe_cae'] != null){
                //     $pdf->setFont('Arial', 'I', 9);
                //     $pdf->SetTextColor(0,0,0);
                //     $pdf->SetY(272);
                //     $pdf->SetX(15);
                //     $pdf->Cell(50, 3, 'CAE: '.$data['fe_cae'], 0, 0, 'L');
                //     $pdf->setFont('Arial', '', 9);
                //     $pdf->SetTextColor(0,0,0);
                //     $pdf->SetY(272);
                //     $pdf->SetX(65);
                //     $pdf->Cell(50, 3, 'Vencimiento CAE: '.$data['fe_cae_vto'], 0, 0, 'L');
                // }
                // // FIN PIE DE PÁGINA -------------------------------------------------------------------------------------------------
                
                if ($accion == 'enviar') {  // almacena el archivo en una ubicación específica y devuelve el path
                    $file_path = env('STORAGE_PATH').'reportes/facturacion_global/'.$data['nombre_carpeta'].'/';
                    if(!File::exists($file_path)){
                        File::makeDirectory($file_path);
                    }
                    $filename = trim($cabecera[0]->n_afiliado.'_'.$cabecera[0]->nro_factura).'.pdf';
                    $file = $file_path.$filename;
                    $extras['path'] = $file;
                    $pdf->Output($file, "F");
                    $data = $filename;
                    $status = 'ok';
                    $count = 1;
                    $errors = [];
                    $message = 'Archivo generado satisfactoriamente';
                    return response()->json([
                        'status' => $status,
                        'count' => $count,
                        'errors' => $errors,
                        'line' => null,
                        'message' => 'Archivo generado y guardado con el nombre: '.$filename,
                        'code' => 1,
                        'data' => $data,
                        'params' => $params,
                        'extras' => $extras,
                        'logged_user' => $logged_user != null ? $logged_user : null
                    ]);
                }else{ // devuelve el archivo almacenado
                    $file_path = env('STORAGE_PATH').'reportes/facturacion_global/'.$data['nombre_carpeta'].'/';
                    if(!File::exists($file_path)){
                        File::makeDirectory($file_path);
                    }
                    $f = $data['nombre_carpeta'].'/'.trim($cabecera[0]->n_afiliado.'_'.$cabecera[0]->nro_factura).'.pdf';
                    $filename = trim($cabecera[0]->n_afiliado.'_'.$cabecera[0]->nro_factura).'.pdf';
                    $file = $file_path.$filename;
                    $extras['path'] = $file;
                    $pdf->Output($file, "F");
                    return FacadeResponse::make(Storage::disk('facturacion_global')->get($f), 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; ' . $f,
                    ]);
                }

            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para '.strtoupper($permiso_requerido);
                $count  = 0;
                $data = null;
                array_push($errors, 'Error de permisos');
                // retorna el response
                return response()->json([
                    'status' => $status,
                    'count' => $count,
                    'errors' => $errors,
                    'message' => $message,
                    'line' => null,
                    'code' => null,
                    'data' => $data,
                    'params' => $params,
                    'logged_user' => $logged_user,
                    'extras' => $extras
                ]); 
            }
        } catch (\Throwable $th) {
            $extras['path'] = env('STORAGE_PATH').'/reportes/facturacion_global/';
            array_push($errors, 'Error de backend');
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'code' => $th->getCode(),
                'data' => $data,
                'params' => $params,
                'logged_user' => $logged_user != null ? $logged_user : null,
                'extras' => $extras
            ]);
        }
    }
}