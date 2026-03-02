<?php

namespace App\Http\Controllers\Internos\Exportaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadeResponse;
use Illuminate\Support\Facades\Log;

use App\Models\User;

use setasign\Fpdi\Fpdi;
use Carbon\Carbon;
use Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


use App\Http\Controllers\ConexionSpController;

class ExportarValidacionController extends ConexionSpController
{
    /**
     * Exporta a pdf una validacion
     * @param codigo_interno codigo interno de la validacion
     * @param imprimir_rechazadas boolean numérico, 1 para true, 0 para false
     * @param accion string, valores posibles: 'mostrar', 'enviar', 'path'
     * @return blob pdf o string path de almacenamiento del pdf
     */
    public function exportar_validacion(Request $request)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $extras = [
            'api_software_version' => config('site.software_version'),
            'ambiente' => config('site.ambiente'),
            'url' => '/int/validaciones/exportar-validacion',
            'controller' => explode('\\', __CLASS__)[sizeof(explode('\\', __CLASS__))-1],
            'function' => __FUNCTION__,
            'sps' => [],
            'responses' => [],
            'queries' => []
        ];
        try {
            // obtenemos el usuario de la petición y sus permisos
            $user = User::with('roles', 'permissions')->find($request->user()->id);
            $logged_user = $this->get_logged_user($user);

            $this->params = [];
            // variables de respuesta
            $status = 'null';
            $count = -1;
            $errors = [];
            $message = 'sin comentarios';
            $line = null;
            $code = null;
            $data = null;

            $codigo_interno = request('codigo_interno');
            $imprimir_rechazadas = request('imprimir_rechazadas');
            $accion = request('accion');
            $contar_impresion = request('contar_impresion');
            $token_tach = request('token_tach');
            $this->params = [
                'codigo_interno' => $codigo_interno,
                'imprimir_rechazadas' => $imprimir_rechazadas,
                'accion' => $accion,
                'contar_impresion' => $contar_impresion,
                'token_tach' => $token_tach
            ];
            // return $this->params;

            if($user->hasPermissionTo('exportar datos')){
                //  suma la impresion (solo cuando se muestra)
                if ($accion == 'mostrar' && $contar_impresion == 1){
                    array_push($extras['sps'], ['AWEB_AutorizacionContarImpresion' => [
                        'codigo_interno' => $codigo_interno, 
                        'usuario' => $logged_user['usuario']
                    ]]);
                    array_push($extras['queries'], $this->get_query('validacion', 'AWEB_AutorizacionContarImpresion', [
                        'codigo_interno' => $codigo_interno, 
                        'usuario' => $logged_user['usuario']
                    ]));
                    $resp = $this->ejecutar_sp_directo('validacion', 'AWEB_AutorizacionContarImpresion', [
                        'codigo_interno' => $codigo_interno, 
                        'usuario' => $logged_user['usuario']
                    ]);
                    array_push($extras['responses'], ['AWEB_AutorizacionContarImpresion' => $resp]);
                }
                array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerAutorizacionCabecera', ['codigo_interno' => $codigo_interno]));
                array_push($extras['sps'], ['AWEB_TraerAutorizacionCabecera' => ['codigo_interno' => $codigo_interno]]);
                $validacion = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerAutorizacionCabecera', ['codigo_interno' => $codigo_interno]);
                array_push($extras['responses'], ['AWEB_TraerAutorizacionCabecera' => $validacion]);
                $extra['validacion'] = $validacion;
                // return is_array($validacion) && sizeof($validacion) > 0;
                if(is_array($validacion) && sizeof($validacion) > 0){
                    $validacion = $validacion[0];
                    $validacion->codigo_interno = $codigo_interno;
                    // $validacion->numero_formateado = get_agregar_ceros($validacion->id_sucursal, 3) . get_agregar_ceros($validacion->autorizacion, 8);
                    $validacion->numero_formateado = get_numero_autorizacion_formateado($validacion->id_sucursal, $validacion->autorizacion);
                    //  obtiene las prestaciones
                    array_push($extras['sps'], ['AWEB_TraerAutorizacionDetalle' => [
                        'codigo_interno' => $codigo_interno, 
                        'imprime_rechazos' => $imprimir_rechazadas
                    ]]);
                    array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerAutorizacionDetalle', [
                        'codigo_interno' => $codigo_interno, 
                        'imprime_rechazos' => $imprimir_rechazadas
                    ]));
                    $validacion->prestaciones = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerAutorizacionDetalle', 
                        [
                            'codigo_interno' => $codigo_interno, 
                            'imprime_rechazos' => $imprimir_rechazadas
                        ]);
                    array_push($extras['responses'], ['AWEB_TraerAutorizacionDetalle' => $validacion->prestaciones]);
    
                    array_push($extras['sps'], ['AWEB_TraerAutorizacionTextos' => ['codigo_interno' => $codigo_interno]]);
                    array_push($extras['queries'], $this->get_query('validacion', 'AWEB_TraerAutorizacionTextos', ['codigo_interno' => $codigo_interno]));
                    $validacion->textos = $this->ejecutar_sp_directo('validacion', 'AWEB_TraerAutorizacionTextos', ['codigo_interno' => $codigo_interno]);
                    array_push($extras['responses'], ['AWEB_TraerAutorizacionTextos' => $validacion->textos]);
                    //  datos de internacion
                    $validacion->es_internacion = (!empty($validacion->codigo_internacion) && strlen($validacion->codigo_internacion) > 10 ? TRUE : FALSE);
                    $etiqueta_internacion = '';
                    if ($validacion->es_internacion) {
                        switch ($validacion->id_accion_internacion) {
                            case 1:
                                $etiqueta_internacion = 'Apertura de Internación';
                                break;
                            case 2:
                                $etiqueta_internacion = 'Prórroga de internación';
                                break;
                            case 4:
                                $etiqueta_internacion = 'Egreso de Internación';
                                break;
                            default:
                                $etiqueta_internacion = 'Prestación de Internación';
                                break;
                        }
                    }
                    $validacion->etiqueta_internacion = $etiqueta_internacion;
                    // return $validacion;

                    return $this->generar_pdf($validacion, $accion);

                }else{
                    $status = 'fail';
                    $message = 'No se encontró la validación';
                    $code = -3;
                    $count  = 0;
                    $line = null;
                    $data = null;
                    array_push($errors, 'Error en la consulta a la validación');
                }
            }else{
                $status = 'unauthorized';
                $message = 'No puede acceder a esta ruta, el usuario con rol '.strtoupper($user->roles[0]->name).' no tiene permiso. Se requiere permiso para EXPORTAR DATOS';
                $code = -2;
                $count  = 0;
                $line = null;
                $data = null;
                array_push($errors, 'No autorizado');
            }
            // retorna el response
            return response()->json([
                'status' => $status,
                'code' => $code,
                'errors' => $errors,
                'message' => $message,
                'count' => $count,
                'line' => $line,
                'data' => $data,
                'params' => $this->params,
                'extras' => $extras,
                'logged_user' => $logged_user
            ]); 
        } catch (\Throwable $th) {
            array_push($errors, 'Fallo de backend');
            return response()->json([
                'status' => 'fail',
                'code' => -1,
                'errors' => $errors,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'count' => 0,
                'data' => null,
                'params' => null,
                'extras' => $extras,
                'logged_user' => null
            ]);
        }
    }

    private function generar_pdf($validacion, $accion){
        //  genera los datos para el PDF
        $exportar_observaciones = TRUE;
        $logo = storage_path() . '/app/public/images/'.env('LOGO');
        if(env('APP_ENV') == 'lealmedica'){
            // if($validacion->convenio_id != null && $validacion->convenio_id == 27){
            if($validacion->n_convenio != null && $validacion->n_convenio == 'BRINDAR SALUD'){
                // return public_path() . '/images/LOGO_Brindar_Salud.png';
                $logo = storage_path() . '/app/public/images/LOGO_Brindar_Salud.png';
            }
        }

        $data = [
            'logo' => $logo,
            'es_internacion' => $validacion->es_internacion,
            'etiqueta_internacion' => $validacion->etiqueta_internacion,
            'codigo_barra' => '*' . $validacion->numero_formateado . '*',
            'fecha' => Carbon::parse($validacion->fecha_solicitud)->format('d/m/Y'),
            'fecha_prescripcion' => Carbon::parse($validacion->fecha_prescripcion)->format('d/m/Y'),
            'fecha_realizacion' => isset($validacion->fecha_realizacion) ? Carbon::parse($validacion->fecha_realizacion)->format('d/m/Y') : null,
            'vencimiento' => $validacion->fecha_vencimiento ? Carbon::parse($validacion->fecha_vencimiento)->format('d/m/Y') : '-',
            'numero' => $validacion->numero_formateado,
            'afiliado' => $validacion->afiliado,
            'obra_social' => $validacion->empresa,
            'plan' => $validacion->plan_prestacional,
            'nombre_afiliado' => trim($validacion->nombre_afiliado),
            'documento' => $validacion->tipo_documento . ' ' . $validacion->documento,
            'localidad' => (!empty($validacion->localidad) ? trim($validacion->localidad) : ""),
            'prestaciones' => $validacion->prestaciones,
            'diagnostico' => $validacion->codigo_diagnostico . ' ' . $validacion->diagnostico,
            'coseguro' => $validacion->coseguro,
            'prescriptor' => trim($validacion->nombre_prescriptor),
            'efector' => trim($validacion->nombre_prestador),
            'id_estado' => $validacion->id_estado,
            'estado' => $validacion->n_estado,
            'usuario' => trim($validacion->usuario),
            'domicilio' => isset($validacion->n_domicilio) ? $validacion->n_domicilio : '',
            'contacto' => isset($validacion->n_contacto) ? $validacion->n_contacto : '',
            'fecha_ingreso_internacion' => $validacion->fecha_ingreso_internacion,
            'fecha_egreso_internacion' => $validacion->fecha_egreso_internacion,
            'fecha_vencimiento_internacion' => $validacion->fecha_vencimiento_internacion,
            'cantidad_dias' => $validacion->id_accion_internacion == 2 ? $validacion->q_dias : null,
            'id_internacion' => $validacion->id_internacion,
            'token' => $validacion->token,
            'qr_code' => $validacion->qr_code
        ];

        //  si tiene comentarios, lo agrega a los datos
        if (!empty($validacion->textos) && count($validacion->textos) > 0){
            $data['observaciones'] = $validacion->textos;
        }

        //  -------------------
        //	crea el objeto y establece variables
        // $pdf = new \fpdf\FPDF('portrait', 'mm', 'A4');
        $pdf = new Fpdi('portrait', 'mm', 'A4');

        $pdf->SetMargins(15, 15);

        //  ====================================================================
        //  autorizacion
        //  ====================================================================

        $numero_hoja = 1;
        $cant_hojas = 1;
        if(sizeof($data['prestaciones']) > 6){
            $cant_hojas = 2;
        }
        if(sizeof($data['prestaciones']) > 12){
            $cant_hojas = 3;
        }
        if(sizeof($data['prestaciones']) > 18){
            $cant_hojas = 4;
        }
        $pdf = $this->agregar_hoja_pdf($pdf, $data, $validacion, $numero_hoja, $cant_hojas);
        //  TABLA --------------------------------------------------------------
        
        //  por cada prestacion
        $row = 0;
        $contador_prestaciones = 0;
        $altura = 6;
        $height = 6;
        foreach ($data['prestaciones'] as $prestacion) {
            // si hay mas de 6 prestaciones agregamos otra hoja
            if($contador_prestaciones == 6){
                $numero_hoja = 2;
                $pdf = $this->agregar_hoja_pdf($pdf, $data, $validacion, $numero_hoja, $cant_hojas);
            }
            if($contador_prestaciones == 12){
                $numero_hoja = 3;
                $pdf = $this->agregar_hoja_pdf($pdf, $data, $validacion, $numero_hoja, $cant_hojas);
            }
            if($contador_prestaciones == 18){
                $numero_hoja = 4;
                $pdf = $this->agregar_hoja_pdf($pdf, $data, $validacion, $numero_hoja, $cant_hojas);
            }

            $new_y = $pdf->GetY();

            if($prestacion->imprimir==1){
                $descripcion = utf8_decode(trim($prestacion->practica));
                $numero_lineas_descripcion = intval(ceil(strlen($descripcion)/50));
                $altura_descipcion = $height*$numero_lineas_descripcion;
                $altura = $altura_descipcion;

                $pdf->Cell(30, $altura, $prestacion->codigo_nomenclador, 0, 0, 'L');
                // $new_y = $pdf->GetY();
                $next_y = $new_y + $altura;
                // descomentar para mostrar presupuesto en internación domiciliaria
                // $prestacion->presupuestada = 1;
                // $validacion->internacion_domiciliaria = 1;
                // $prestacion->precio = 39438.85;
                // dd('validacion', $validacion);

                if($validacion->internacion_domiciliaria == 1 && $this->verificar_presupuestada($validacion->prestaciones)){
                    $pdf->MultiCell(110, $height, $descripcion, 0, 'L');

                    $new_y = $pdf->GetY();
                    $pdf->SetY($new_y);
                    $pdf->SetY($new_y - $height);
                    $pdf->SetX($pdf->GetX() + 110 + 30);

                    $pdf->Cell(15, $height, $prestacion->cantidad, 0, 0, 'R');
                    $precio = number_format(floatval($prestacion->valor_total_practica), 2, ',', '.');
                    $pdf->Cell(25, $height, '$ '.$precio, 0, 1, 'R');
                    
                    $pdf->SetY($next_y);
                    $row++;
                }else{
                    $pdf->MultiCell(135, $height, $descripcion, 0, 'L');

                    $new_y = $pdf->GetY();
                    $pdf->SetY($new_y);
                    $pdf->SetY($new_y - $height);
                    $pdf->SetX($pdf->GetX() + 135 + 30);
                    $pdf->Cell(15, $height, $prestacion->cantidad, 0, 1, 'R');
                    
                    $pdf->SetY($next_y);
                    $row++;
                }

            }
            $contador_prestaciones++;
        }
        $pdf->SetY($pdf->GetY() + $altura);

        $pdf = $this->agregar_pie_pdf($pdf, $data, $exportar_observaciones);
        

        if ($accion == 'enviar') {
            // Guardar el PDF en storage/app/public/reportes/validaciones/
            $filename = 'validacion-' . $data['numero'] . '.pdf';
            $relative_path = 'reportes/validaciones/' . $filename;
            $full_path = storage_path('app/public/' . $relative_path);
            // Asegúrate que el directorio existe
            if (!file_exists(dirname($full_path))) {
                mkdir(dirname($full_path), 0775, true);
            }
            $pdf->Output($full_path, "F");
            // Obtener la URL pública
            $url_publica = \Storage::disk('public')->url($relative_path);
            $extras['path'] = $full_path;
            $extras['url_publica'] = $url_publica;
            $data = [
                'filename' => $filename,
                'url_publica' => $url_publica
            ];
            $status = 'ok';
            $count = 1;
            $errors = [];
            $params = [
                'codigo_interno' => $validacion->codigo_interno,
                'accion' => $accion,
            ];
            $message = 'Archivo generado satisfactoriamente';
            return response()->json([
                'status' => $status,
                'count' => $count,
                'errors' => $errors,
                'message' => null,
                'line' => null,
                'code' => 1,
                'data' => $data,
                'params' => $params,
                'logged_user' => null,
                'extras' => $extras
            ]);
        }else{
            $file_path = env('STORAGE_PATH').'reportes/validaciones/';
            $filename = 'validacion-' . $data['numero'] . '.pdf';
            $file = $file_path.$filename;
            $pdf->Output($file, "F");
            return FacadeResponse::make(Storage::disk('validaciones')->get($filename), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; ' . $filename,
            ]);
        }
    }

    /**
     * Añade una nueva hoja con las cabeceras al pdf
     */
    private function agregar_hoja_pdf($pdf, $data, $validacion, $numero_hoja, $cant_hojas)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        $pdf->AddPage('portrait');
        //  establece valor de tipografia
        $pdf->SetFont('Arial', '', 10);
                
        //  código de barras
        get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
        get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);
        
        //  cabecera
        // $pdf->Image(get_path_imagenes($data['logo']), 15, 16, 30, 7, 'PNG');
        // $pdf->Image(env('LOGO_PATH'), 15, 16, 50, 0, 'PNG');
        $pdf->Image($data['logo'], 15, 16, 50, 0, 'PNG');
        // if(env('APP_ENV') == 'lealmedica' && $data['plan'] == 'PLAN OSPAP'){ // id_plan 46 en lealmedica
        //     $logoOSPAP = public_path() . '/statics/images/LOGO_Brindar_Salud.png';
        //     $pdf->Image($logoOSPAP, 15, 30, 50, 0, 'PNG');
        // }
        // leyenda cruzada
        if ($data['id_estado'] == 2){
            $pdf->Image(env('IMAGE_PATH').'/pendiente.png', 5, 5, 200, 130, 'PNG');
        }
        if($data['id_estado'] == 8 || $data['id_estado'] == 3){
            $pdf->Image(env('IMAGE_PATH').'/rechazo.png', 5, 5, 200, 130, 'PNG');
        }
        if($data['id_estado'] == 4){
            $pdf->Image(env('IMAGE_PATH').'/anulado.png', 5, 5, 200, 130, 'PNG');
        }
        $pdf->Cell(60, 14, '', 0, 0);

        if($data['id_internacion'] != null){
            $pdf->Cell(60, 13, '', 0, 2, 'C');
            $pdf->Cell(60, 5, $data['numero'], 0, 2, 'C');
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(60, 5, utf8_decode('Internación N° '.$data['id_internacion']), 0, 0, 'C');
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetY($pdf->getY() - 18);
            $pdf->SetX($pdf->getX() + 120);
        }else{
            $pdf->Cell(60, 13, '', 0, 1, 'C');
            $pdf->Cell(60, 5, '', 0, 0, 'C');
            $pdf->Cell(60, 5, $data['numero'], 0, 1, 'C');
            $pdf->Cell(60, 5, '', 0, 0, 'C');
            $token = '';
            if($data['token'] != null && $date['token'] =! ''){
                $token = 'Token: '.$data['token'];
            }
            $pdf->Cell(60, 5, $token, 0, 0, 'C');
            $pdf->SetY($pdf->GetY()-20);
            $pdf->SetX(135);
        }
        $pdf->Cell(60, 5, utf8_decode('Hoja '.$numero_hoja.' de '.$cant_hojas), 0, 2, 'R');
        $pdf->Cell(60, 5, '', 0, 2, 'R');
        $pdf->Cell(60, 5, utf8_decode('Fecha: ') . $data['fecha'], 0, 2, 'R');
        $pdf->Cell(60, 5, utf8_decode('Fecha de Prescripción: ') . $data['fecha_prescripcion'], 0, 2, 'R');
        $pdf->Cell(60, 5, utf8_decode('Fecha de Vencimiento: ') . $data['vencimiento'], 0, 2, 'R');
        if($validacion->id_accion_internacion == 1 || $validacion->id_accion_internacion == 2 || $validacion->id_accion_internacion == 3){
            if($data['fecha_ingreso_internacion'] != null){
                $pdf->Cell(60, 5, utf8_decode('Fecha de Ingreso: ') . Carbon::parse($data['fecha_ingreso_internacion'])->format('d/m/Y'), 0, 2, 'R');
            }else{
                $pdf->Cell(60, 5, utf8_decode('Fecha de Ingreso:                   '), 0, 2, 'R');
            }
            if($data['fecha_vencimiento_internacion'] != null){
                $pdf->Cell(60, 5, utf8_decode('Fecha Vencimiento Internación: ') . Carbon::parse($data['fecha_vencimiento_internacion'])->format('d/m/Y'), 0, 1, 'R');
            }else{
                $pdf->Cell(60, 5, utf8_decode('Fecha Vencimiento Internación:                   '), 0, 2, 'R');
            }
        }
        if($data['fecha_realizacion'] != null && $data['fecha_realizacion'] != $data['fecha'] ){
            $pdf->SetX($pdf->getX() + 120);
            $pdf->Cell(60, 5, utf8_decode('Fecha de Realización: ') . $data['fecha_realizacion'], 0, 2, 'R');
        }else{
            $pdf->Cell(60, 5, '', 0, 2, 'R');
        }
        
        $pdf->Cell(180, 3, '', 0, 1, 'R');

        //  establece valor de tipografia
        $pdf->SetFont('Arial', 'B', 14);

        $pdf->Cell(60, 3, '', 0, 0, 'C');
        if($data['etiqueta_internacion'] != ''){
            $pdf->Cell(60, 7, mb_strtoupper(utf8_decode($data['etiqueta_internacion']), 'ISO-8859-1'), 0, 1, 'C');
        }else{
            if($validacion->guardia == 1){
                $pdf->Cell(60, 7, mb_strtoupper(utf8_decode('Prestacion Ambulatoria (Guardia)'), 'ISO-8859-1'), 0, 1, 'C');
            }else if($validacion->incluye_medicamentos == 1){
                $pdf->Cell(60, 7, mb_strtoupper(utf8_decode('Prestacion Ambulatoria (Medicamentos)'), 'ISO-8859-1'), 0, 1, 'C');
            }else{
                $pdf->Cell(60, 7, mb_strtoupper(utf8_decode('Prestacion Ambulatoria'), 'ISO-8859-1'), 0, 1, 'C');
            }
        }
        $n_tipo_internacion = $validacion->n_tipo_internacion != NULL ? $validacion->n_tipo_internacion : '';
        $pdf->Cell(180, 7, mb_strtoupper(utf8_decode($n_tipo_internacion), 'ISO-8859-1'), 0, 1, 'C');

        $pdf->SetFont('Arial', 'B', 8);
        // $pdf->Cell(60, 3, '', 0, 1, 'R');
        if(!empty($validacion->id_accion_internacion) && $validacion->id_accion_internacion == 2){
            $pdf->Cell(60, 3, '', 0, 0, 'C');
            $pdf->Cell(60, 3, utf8_decode('('.$data['cantidad_dias'].' días)'), 0, 0, 'C');
            $pdf->Cell(60, 3, '', 0, 1, 'R');
        }

        //  establece valor de tipografia
        $pdf->SetFont('Arial', '', 10);

        //  primera línea
        $pdf->Cell(60, 5, utf8_decode('Nro. Afiliado: ') . $data['afiliado'], 0, 0);
        $pdf->Cell(60, 5, utf8_decode('Obra Social: ') . utf8_decode($data['obra_social']), 0, 0, 'C');
        $pdf->Cell(60, 5, utf8_decode('Plan: ') . utf8_decode($data['plan']), 0, 1, 'R');

        //  segunda línea
        $pdf->Cell(110, 5, utf8_decode('Nombre: ') . utf8_decode($data['nombre_afiliado']), 0, 0);
        $pdf->Cell(70, 5, utf8_decode('Documento: ') . $data['documento'], 0, 1, 'R');

        //  tercera línea
        $pdf->Cell(180, 5, utf8_decode('Contacto: ') . $data['contacto'], 0, 1);
        
        // cuarta línea
        $pdf->Cell(180, 5, utf8_decode('Domicilio: ') . utf8_decode($data['domicilio']), 0, 1, 'L');

        // quinta línea
        $pdf->Cell(175, 5, utf8_decode('Localidad: ') . utf8_decode($data['localidad']), 0, 0, 'L');
        $pdf->Cell(5, 5, '', 0, 1, 'L');

        // =============================================================
        // CABECERA DE LA TABLA
        // =============================================================
        //  titulo de tabla
        $pdf->Cell(180, 2, '', 0, 1);
        $pdf->Cell(180, 0.01, '', 1, 1, 'C');
        $pdf->Cell(30, 10, utf8_decode('Práctica'), 0, 0, 'L');
        // $pdf->Cell(135, 10, utf8_decode('Descripción'), 0, 0);
        // descomentar para mostrar presupuesto en internación domiciliaria
        if($validacion->internacion_domiciliaria == 1 && $this->verificar_presupuestada($validacion->prestaciones)){
            $pdf->Cell(110, 10, utf8_decode('Descripción'), 0, 0, 'L');
            $pdf->Cell(15, 10, utf8_decode('Cantidad'), 0, 0, 'R');
            $pdf->Cell(25, 10, utf8_decode('Presupuesto'), 0, 1, 'R');
        }else{
            $pdf->Cell(130, 10, utf8_decode('Descripción'), 0, 0, 'L');
            $pdf->Cell(15, 10, utf8_decode('Cantidad'), 0, 1, 'R');
        }


        $pdf->Cell(180, 0.01, '', 1, 1, 'C');
        $pdf->Cell(180, 2, '', 0, 1);

        return $pdf;

    }

    /**
     * Añade el pie de página al pdf
     */
    private function agregar_pie_pdf($pdf, $data, $exportar_observaciones)
    {
        date_default_timezone_set('America/Argentina/Cordoba');
        
        // Guardar posición Y actual para el QR
        $qr_y_position = $pdf->GetY(); // Ajustar altura del QR
        // $pdf->Cell(50, 6, utf8_decode('qr_code: ' . $data['qr_code']), 0, 1, 'R');
        // QR TACH - colocarlo a la derecha después de posicionar el contenido
        if(env('GENERAR_TACH_QR') == 'SI' ){
           
            // Log::channel('tach')->info('QR_CODE'. $data['qr_code']);
            if($data['qr_code'] != null && $data['qr_code'] != ''){
                $temp_qr_path = storage_path('app/public/temp/qr_code_'.$data['numero'].'.png');
                
                try {
                    // Método 1: Usar API externa más confiable (qr-server.com)
                    // Limpiar más agresivamente el contenido del QR
                    $qrContent = preg_replace('/\s+/', ' ', trim($data['qr_code']));
                    $qrContent = trim($qrContent);
                    
                    // Log para debugging con información detallada
                    Log::channel('tach')->info('===========================================');
                    Log::channel('tach')->info('Iniciando generación de QR TACH');
                    Log::channel('tach')->info('QR Content original: [' . $data['qr_code'] . ']');
                    Log::channel('tach')->info('QR Content limpio: [' . $qrContent . ']');
                    Log::channel('tach')->info('Longitud: ' . strlen($qrContent));
                    Log::channel('tach')->info('URL encoded: ' . rawurlencode($qrContent));
                    
                    // Crear contexto HTTP con headers personalizados
                    $context = stream_context_create([
                        'http' => [
                            'method' => 'GET',
                            'header' => [
                                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                                'Accept: image/png,image/*,*/*'
                            ],
                            'timeout' => 10,
                            'ignore_errors' => true
                        ],
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false
                        ]
                    ]);
                    
                    // Usar qr-server.com que maneja mejor contenido largo
                    Log::channel('tach')->info('Llamando a qr-server.com...');
                    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&format=png&data=' . rawurlencode($qrContent);
                    
                    // Intentar con file_get_contents
                    $qrImageData = @file_get_contents($qrUrl, false, $context);
                    
                    // Verificar si hubo error
                    if ($qrImageData === false) {
                        $error = error_get_last();
                        Log::channel('tach')->error('file_get_contents falló: ' . json_encode($error));
                        Log::channel('tach')->error('URL intentada: ' . substr($qrUrl, 0, 200) . '...');
                        
                        // Intentar con cURL como alternativa
                        if (function_exists('curl_init')) {
                            Log::channel('tach')->info('Intentando con cURL...');
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $qrUrl);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                            $qrImageData = curl_exec($ch);
                            $curlError = curl_error($ch);
                            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);
                            
                            if ($curlError) {
                                Log::channel('tach')->error('cURL error: ' . $curlError);
                            }
                            Log::channel('tach')->info('cURL HTTP Code: ' . $httpCode);
                        } else {
                            Log::channel('tach')->error('cURL no está disponible');
                        }
                    }
                    
                    if ($qrImageData !== false && strlen($qrImageData) > 100) {
                        Log::channel('tach')->info('Imagen QR recibida, tamaño: ' . strlen($qrImageData) . ' bytes');
                        Log::channel('tach')->info('Imagen QR recibida, tamaño: ' . strlen($qrImageData) . ' bytes');
                        file_put_contents($temp_qr_path, $qrImageData);
                        Log::channel('tach')->info('Archivo QR guardado en: ' . $temp_qr_path);
                        
                        if (file_exists($temp_qr_path) && filesize($temp_qr_path) > 0) {
                            Log::channel('tach')->info('Archivo verificado, tamaño: ' . filesize($temp_qr_path) . ' bytes');
                            // Posicionar QR a la derecha (X=165) y encima del coseguro
                            $pdf->Image($temp_qr_path, 165, $qr_y_position, 30, 30, 'PNG');
                            Log::channel('tach')->info('QR TACH agregado al PDF exitosamente');
                        } else {
                            Log::channel('tach')->error('El archivo QR no existe o está vacío después de guardarlo');
                        }
                        
                        if (file_exists($temp_qr_path)) {
                            unlink($temp_qr_path);
                            Log::channel('tach')->info('Archivo temporal eliminado');
                        }
                    } else {
                        Log::channel('tach')->error('qr-server.com no pudo generar el QR - Datos recibidos: ' . 
                            ($qrImageData === false ? 'FALSE' : strlen($qrImageData) . ' bytes'));
                    }
                    Log::channel('tach')->info('===========================================');
                    Log::channel('tach')->info(''); // Línea vacía
                    
                } catch (\Exception $e) {
                    Log::channel('tach')->error('Error con qr-server.com: ' . $e->getMessage());
                    Log::channel('tach')->error('Stack trace: ' . $e->getTraceAsString());
                    
                    // Fallback 2: Usar otro servicio
                    try {
                        Log::channel('tach')->info('-------------------------------------------');
                        Log::channel('tach')->info('Intentando fallback con Google Charts API');
                        $qrContent = preg_replace('/\s+/', ' ', trim($data['qr_code']));
                        $qrContent = trim($qrContent);
                        
                        // Limitar contenido si es muy largo
                        if (strlen($qrContent) > 300) {
                            Log::channel('tach')->warning('Contenido muy largo (' . strlen($qrContent) . ' chars), truncando...');
                            $qrContent = 'TACH:' . substr($qrContent, 0, 280) . '...';
                        }
                        
                        $context = stream_context_create([
                            'http' => [
                                'method' => 'GET',
                                'header' => [
                                    'User-Agent: Mozilla/5.0 (compatible; QRGenerator/1.0)',
                                ],
                                'timeout' => 8,
                                'ignore_errors' => true
                            ],
                            'ssl' => [
                                'verify_peer' => false,
                                'verify_peer_name' => false
                            ]
                        ]);

                        Log::channel('tach')->info('Llamando a Google Charts...');
                        Log::channel('tach')->info('Contenido QR: [' . $qrContent . ']');

                        // Usar chart.apis.google.com (diferente endpoint)
                        $qrUrl = 'https://chart.apis.google.com/chart?chs=300x300&cht=qr&chl=' . rawurlencode($qrContent);
                        
                        $qrImageData = @file_get_contents($qrUrl, false, $context);
                        
                        if ($qrImageData === false && function_exists('curl_init')) {
                            Log::channel('tach')->info('file_get_contents falló, intentando con cURL...');
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $qrUrl);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            $qrImageData = curl_exec($ch);
                            curl_close($ch);
                        }
                        
                        if ($qrImageData !== false && strlen($qrImageData) > 100) {
                            Log::channel('tach')->info('Imagen recibida de Google Charts: ' . strlen($qrImageData) . ' bytes');
                            file_put_contents($temp_qr_path, $qrImageData);
                            
                            if (file_exists($temp_qr_path) && filesize($temp_qr_path) > 0) {
                                // Posicionar QR a la derecha (X=160) y encima del coseguro
                                $pdf->Image($temp_qr_path, 160, $qr_y_position, 30, 30, 'PNG');
                                Log::channel('tach')->info('QR TACH generado con Google Charts (fallback)');
                            }
                            
                            if (file_exists($temp_qr_path)) {
                                unlink($temp_qr_path);
                            }
                        } else {
                            Log::channel('tach')->error('Google Charts no pudo generar el QR');
                        }
                        
                    } catch (\Exception $fallbackError) {
                        Log::channel('tach')->error('Todos los métodos QR fallaron: ' . $fallbackError->getMessage());
                        Log::channel('tach')->warning('Generando PDF sin código QR TACH');
                        // Continúa sin QR - no es crítico para el PDF
                    }
                }
            }
        }

        $pdf->setY($pdf->getY() + 30);
        // diagnostico
        $diag = utf8_decode('Diagnóstico: ').utf8_decode($data['diagnostico']);
        $pdf->MultiCell(130, 6, $diag, 0, 'L');

        // coseguro
        $pdf->setY($pdf->getY()-6);
        $pdf->setX($pdf->getX()+130);
        $pdf->Cell(50, 6, utf8_decode('Coseguro: $ ') . number_format($data['coseguro'], 2), 0, 1, 'R');

        $pdf->Cell(180, 2, '', 0, 1);
        $pdf->Cell(180, 0.01, '', 1, 1);
        $pdf->Cell(180, 2, '', 0, 1);

        //  --------------------------------------------------------------------

        //  primera línea
        if(strlen($data['prescriptor'])>40){
            $prescriptor = substr(utf8_decode($data['prescriptor']), 0, 37);
            $prescriptor = $prescriptor.'...';
        }else{
            $prescriptor = utf8_decode($data['prescriptor']);
        }
        $pdf->Cell(110, 6, utf8_decode('Prescriptor: ') . $prescriptor, 0, 0);
        $pdf->Cell(70, 6, utf8_decode('Estado: ') . utf8_decode($data['estado']), 0, 1, 'R');
        
        //  segunda línea acortando los nombres
        // if(strlen($data['efector'])>38){
        //     $efector = substr(utf8_decode($data['efector']), 0, 35);
        //     $efector = $efector.'...';
        // }else{
        //     $efector = utf8_decode($data['efector']);
        // }
        // if(strlen($data['usuario'])>38){
        //     $usuario = substr(utf8_decode($data['usuario']), 0, 35);
        //     $usuario = $usuario.'...';
        // }else{
        //     $usuario = utf8_decode($data['usuario']);
        // }
        // $pdf->Cell(110, 6, utf8_decode('Efector: ') . $efector, 0, 0);
        // $pdf->Cell(70, 6, utf8_decode('Usuario: ') . $usuario, 0, 1, 'R');
        $pos_y = $pdf->getY();
        // segunda línea con multilínea
        $pdf->MultiCell(80, 6, utf8_decode('Efector: '.$data['efector']), 0, 'L');
        $pdf->setY($pos_y);
        $pdf->setX($pdf->getX()+100);
        $pdf->MultiCell(80, 6, utf8_decode('Usuario: '.$data['usuario']), 0, 'R');

        // segunda y tercera línea sin acortar los nombres
        // $pdf->Cell(180, 6, utf8_decode('Efector: ') . utf8_decode($data['efector']), 0, 2);
        // $pdf->Cell(180, 6, utf8_decode('Usuario: ') . utf8_decode($data['usuario']), 0, 1, 'L');

        //  separador
        $pdf->Cell(180, 21, '', 0, 1);

        //  lineas de firmas
        $pdf->Cell(2, 0.01, '', 0, 0, 'C');
        $pdf->Cell(56, 0.01, '', 1, 0, 'C');
        $pdf->Cell(4, 0.01, '', 0, 0, 'C');
        $pdf->Cell(56, 0.01, '', 1, 0, 'C');
        $pdf->Cell(4, 0.01, '', 0, 0, 'C');
        $pdf->Cell(56, 0.01, '', 1, 0, 'C');
        $pdf->Cell(2, 0.01, '', 0, 1, 'C');

        //  firmas
        $pdf->Cell(60, 6, utf8_decode('Fecha de Atención'), 0, 0, 'C');
        $pdf->Cell(60, 6, utf8_decode('Firma Profesional'), 0, 0, 'C');
        $pdf->Cell(60, 6, utf8_decode('Firma Afiliado'), 0, 1, 'C');
        
        //  si tiene que exportar observaciones
        if ($exportar_observaciones && !empty($data['observaciones'])) {
            //  ====================================================================
            //  observaciones
            //  ====================================================================

            $pdf->AddPage('portrait');
            $pdf->SetFont('Arial', '', 10);

            //  código de barras
            get_code39($pdf, 77, 17, $data['codigo_barra'], 0.7, 6);
            get_code39($pdf, 77, 22, $data['codigo_barra'], 0.7, 6);

            //  cabecera
            // $pdf->Image(get_path_imagenes($data['logo']), 15, 16, 30, 7, 'PNG');
            $pdf->Image($data['logo'], 15, 16, 40, 0, 'PNG');
            // if(env('APP_ENV') == 'lealmedica' && $data['plan'] == 'PLAN OSPAP'){ // id_plan 46 en lealmedica
            //     $logoOSPAP = public_path() . '/statics/images/LOGO_Brindar_Salud.png';
            //     $pdf->Image($logoOSPAP, 15, 30, 50, 0, 'PNG');
            // }

            $pdf->Cell(60, 14, '', 0, 0);
            $pdf->Cell(60, 30, strtoupper(utf8_decode('Observaciones y Comentarios')), 0, 0, 'C');
            $pdf->Cell(60, 14, utf8_decode('Fecha: ') . $data['fecha'], 0, 1, 'R');
            $pdf->Cell(180, 6, '', 0, 1);

            //  primera línea
            $pdf->Cell(90, 7, utf8_decode('Número: ') . $data['numero'], 0, 0);
            $pdf->Cell(90, 7, utf8_decode('Número de Afiliado: ') . $data['afiliado'], 0, 1, 'R');

            //  segunda línea
            $pdf->Cell(60, 7, utf8_decode('Solicitud: ') . $data['fecha'], 0, 0);
            $pdf->Cell(60, 7, utf8_decode('Obra Social: ') . utf8_decode($data['obra_social']), 0, 0, 'C');
            $pdf->Cell(60, 7, utf8_decode('Plan: ') . utf8_decode($data['plan']), 0, 1, 'R');

            //  tercera línea
            $pdf->Cell(180, 7, utf8_decode('Prescriptor: ') . utf8_decode($data['prescriptor']), 0, 1);

            //  cuarta línea
            $pdf->Cell(180, 7, utf8_decode('Efector: ') . utf8_decode($data['efector']), 0, 1);

            //  TABLA --------------------------------------------------------------

            //  titulo de tabla
            $pdf->Cell(180, 2, '', 0, 1);
            $pdf->Cell(180, 0.01, '', 1, 1, 'C');
            $pdf->Cell(30, 10, utf8_decode('Fecha'), 0, 0, 'C');
            $pdf->Cell(40, 10, utf8_decode('Destino'), 0, 0, 'C');
            $pdf->Cell(110, 10, utf8_decode('Observación'), 0, 1);
            $pdf->Cell(180, 0.01, '', 1, 1, 'C');
            $pdf->Cell(180, 2, '', 0, 1);

            //  por cada observacion
            foreach ($data['observaciones'] as $observacion) {
                $pdf->Cell(30, 7, $observacion->fecha, 0, 0, 'C');
                $pdf->Cell(40, 7, $observacion->usuario, 0, 0, 'C');
                $pdf->MultiCell(110, 7, utf8_decode($observacion->observacion), 0, 'L');
            }
        }
        return $pdf;
    }

    /** comprueba si alguno de los items tiene presupuestada en 1 */
    private function verificar_presupuestada($prestaciones){
        foreach($prestaciones as $p){
            if($p->presupuestada == 1){
                return true;
            }
        }
        return false;
    }

}

// {
//     // afiliado: "80696700",
//     // anularpic: 0,
//     autorizacion: 107156,
//     // codigo_diagnostico: "R10",
//     // codigo_internacion: null,
//     // codigo_interno: "B06AD362-93D8-4003-8F10-90DA02A6C246",
//     // comentarios: "REQUIERE AUDITORIA MEDICA  - 205124 - 205124 - 205124 - 205124 - 205124 - 20<Br/><Br/>SBrunialti@hbritanico.com.a",
//     // compra_pendiente: 0,
//     // convenio: "(453) HOSPITAL BRITANICO DE BUENOS AIRES - 03-2025",
//     coseguro: "1000.00",
//     // diagnostico: "Dolor abdominal y pélvico",
//     // documento: "31743589",
//     // edicion: 0,
//     // empresa: "CASA",
//     // es_internacion: false,
//     // escallcenter: 0,
//     // estado: 1,
//     // etiqueta_internacion: ""
//     // factura: "NO FACTURADA",
//     // fecha_alta: "2025-03-01 00:00:00.000",
//     // fecha_egreso_internacion: null,
//     // fecha_ingreso_internacion: null,
//     // fecha_prescripcion: "2025-03-29 00:00:00.000",
//     // fecha_realizacion: "2025-04-04 00:00:00.000",
//     // fecha_solicitud: "2025-04-04 10:32:30.803",
//     // fecha_vencimiento_internacion: null,
//     // fecha_vencimiento: "2025-05-28 00:00:00.000",
//     // guardia: 0,
//     // id_accion_internacion: null,
//     // id_convenio: "7804F8DA-C1E6-0F64-CA43-DE161FD0F073",
//     // id_empresa: 1,
//     // id_estado: 1,
//     // id_internacion: null,
//     // id_movimiento_compra: "",
//     // id_prestador_efector: 453,
//     // id_prestadores_prescriptor: 1,
//     id_sucursal: 9,
//     // id_tipo_documento: 1,
//     // idzona: 9,
//     // incluye_medicamentos: 0,
//     // insumo_pendiente: 0,
//     // internacion_anulada: null,
//     // internacion_domiciliaria: 0,
//     // localidad: "(1900) LA PLATA",
//     // mail_prestador: "amiranda@hbritanico.com.ar",
//     // mails: "mrocco@cajaabogados.org.ar",
//     // n_contacto: "+54 9 221 4595197",
//     // n_convenio: "CASA",
//     // n_domicilio: "CALLE 6 Nro 6 Piso  Depto. ",
//     // n_estado: "AUTORIZADO",
//     // n_tipo_internacion: null,
//     // no_convenido: 0,
//     // nombre_afiliado: "ROCCO, MARIA GUILLERMINA",
//     // nombre_prescriptor: "PRESCRIPTOR GENERICO",
//     // nombre_prestador: "HOSPITAL BRITANICO DE BUENOS AIRES",
//     // numero_formateado: "00900107156",
//     // plan_prestacional: "PLAN INTEGRAL",
//     // prestaciones: [
//     //     {
//     //         id_autorizaciones_item: "BE95DFBF-6C0B-4DB2-9F4F-24FFFC3CFE5C",
//     //         item: 1,
//     //         cantidad: 1,
//     //         codigo_nomenclador: "205124",
//     //         practica: "VCC - COLONOSCOPIA DIAGNOST. C/VIDEO - VIDEO ENDOSCOPIA DIGESTIVA BAJA",
//     //         valor_total_practica: "246362.08",
//     //         id_estado: 1,
//     //         comentarios: null,
//     //         imprimir: 1,
//     //         id_frecuencia: null,
//     //         frecuencia_cantidad: 1,
//     //         finaliza: null,
//     //         retiro: null,
//     //         audita: 1,
//     //         carencia: 0,
//     //         presupuestada: 0,
//     //         rechaza: 0,
//     //         consumida: 0,
//     //         n_estado: "AUTORIZADO",
//     //         extra_capita: 0
//     //     }
//     // ],
//     // q_dias: 0,
//     // sucursal: "LA PLATA",
//     // texto_afiliado: null,
//     // texto_efector: null,
//     // texto_validacion: "SBrunialti@hbritanico.com.a",
//     // textos: [],
//     // tiene_insumos: 0,
//     // tiene_pic: 0,
//     // tipo_documento: "DNI",
//     // token: "S1Y25B",
//     // usuario: "ROCCO GUILLERMINA",
    
// }