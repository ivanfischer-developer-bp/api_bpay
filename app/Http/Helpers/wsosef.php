<?php

namespace App\Http\Helpers;

use SoapClient;
use SoapFault;
use Exception;

/**
 * Helper WSOSEF - Consumo de servicio SOAP de OSEF (Obra Social del Estado Federal)
 * 
 * Documentación WSDL:
 * - Endpoint: https://validador.osef.gob.ar/SIA_OSPTF_PROD_WS/servlet/aauwebservice
 * - WSDL: https://validador.osef.gob.ar/SIA_OSPTF_PROD_WS/servlet/aauwebservice?wsdl
 * - Operación: Execute (recibe SDT_entrada, retorna Sdt_salida, ambos tipo SDT_AutoWS)
 * - Binding: SOAP 1.1 Document/Literal
 */
class WsOsef
{
    /**
     * Configuración por defecto del cliente SOAP
     */
    private static $config = [
        'trace' => true,
        'exceptions' => true,
        'connection_timeout' => 30,
        'cache_wsdl' => WSDL_CACHE_BOTH,
        'user_agent' => 'PHP-SoapClient/BPay',
        'stream_context' => null,
    ];

    /**
     * URL del WSDL de OSEF
     */
    const WSDL_URL = 'https://validador.osef.gob.ar/SIA_OSPTF_PROD_WS/servlet/aauwebservice?wsdl';

    /**
     * Crea instancia de SoapClient para consumir el servicio OSEF
     * 
     * @param array $options Opciones adicionales para SoapClient
     * @return SoapClient
     * @throws Exception Si falla la conexión al WSDL
     */
    public static function getClient($options = [])
    {
        $config = array_merge(self::$config, $options);
        
        try {
            $client = new SoapClient(self::WSDL_URL, $config);
            return $client;
        } catch (SoapFault $e) {
            throw new Exception('Error al conectar con WSDL de OSEF: ' . $e->getMessage(), $e->getCode());
        }
    }

    /**
     * Construye estructura mínima de SDT_AutoWS para enviar al servicio
     * 
     * @param string $usuario Usuario OSEF
     * @param string $password Password OSEF
     * @param array $datos Datos opcionales (NumeroAutorizacion, NumeroAfiliado, etc.)
     * @return stdClass Objeto SDT_AutoWS construido
     */
    public static function buildRequest($usuario, $password, $datos = [])
    {
        $request = new \stdClass();
        
        // Credenciales obligatorias
        $request->Usuario = $usuario;
        $request->Password = $password;
        
        // Modalidad (1=consulta, 2=inclusión, etc.) - default consulta
        $request->Modalidad = $datos['Modalidad'] ?? 1;
        
        // Modo (C=Consulta, etc.)
        $request->Modo = $datos['Modo'] ?? 'C';
        
        // Delegación
        $request->Delegacion = $datos['Delegacion'] ?? 0;
        
        // Párametros de autorización/afiliado
        $request->NumeroAutorizacion = $datos['NumeroAutorizacion'] ?? 0;
        $request->DelegacionAutorizacionRef = $datos['DelegacionAutorizacionRef'] ?? 0;
        $request->NumeroAutorizacionRef = $datos['NumeroAutorizacionRef'] ?? 0;
        $request->NumeroAfiliado = $datos['NumeroAfiliado'] ?? '';
        $request->Plan = $datos['Plan'] ?? 0;
        $request->Gravamen = $datos['Gravamen'] ?? 0;
        $request->NumeroProveedor = $datos['NumeroProveedor'] ?? 0;
        $request->NumeroSucursal = $datos['NumeroSucursal'] ?? 0;
        $request->NumeroResponsableFacturacion = $datos['NumeroResponsableFacturacion'] ?? 0;
        
        // Fechas y profesionales
        $request->FechaSolicitud = empty($datos['FechaSolicitud']) ? null : $datos['FechaSolicitud'];
        $request->FechaPrescripcion = empty($datos['FechaPrescripcion']) ? null : $datos['FechaPrescripcion'];
        $request->MatriculaPrescriptor = $datos['MatriculaPrescriptor'] ?? '';
        $request->ProvinciaMatriculaPrescriptor = $datos['ProvinciaMatriculaPrescriptor'] ?? '';
        $request->EspecialidadMatriculaPrescriptor = $datos['EspecialidadMatriculaPrescriptor'] ?? '';
        
        // Diagnóstico y prescriptor
        $request->CodigoDiagnostico = $datos['CodigoDiagnostico'] ?? '';
        $request->SecuenciaDiagnostico = $datos['SecuenciaDiagnostico'] ?? 0;
        $request->MatriculaEfector = $datos['MatriculaEfector'] ?? '';
        $request->ProvinciaMatriculaEfector = $datos['ProvinciaMatriculaEfector'] ?? '';
        $request->EspecialidadMatriculaEfector = $datos['EspecialidadMatriculaEfector'] ?? '';
        $request->CategoriaEfector = $datos['CategoriaEfector'] ?? '';
        
        // Números internos y descripcioness
        $request->NumeroInternoAfiliado = $datos['NumeroInternoAfiliado'] ?? 0;
        $request->NumeroInternoGrupo = $datos['NumeroInternoGrupo'] ?? 0;
        $request->DescripcionPlan = $datos['DescripcionPlan'] ?? '';
        $request->NombreApellidoAfiliado = $datos['NombreApellidoAfiliado'] ?? '';
        $request->DescripcionGravamen = $datos['DescripcionGravamen'] ?? '';
        $request->RazonSocialProveedor = $datos['RazonSocialProveedor'] ?? '';
        $request->NombreResponsableFacturacion = $datos['NombreResponsableFacturacion'] ?? '';
        $request->NumeroInternoPrescriptor = $datos['NumeroInternoPrescriptor'] ?? 0;
        $request->NombrePrescriptor = $datos['NombrePrescriptor'] ?? '';
        $request->NumeroInternoEfector = $datos['NumeroInternoEfector'] ?? 0;
        $request->NombreEfector = $datos['NombreEfector'] ?? '';
        
        // Marcas de estado
        $request->MarcaAutorizado = $datos['MarcaAutorizado'] ?? '';
        $request->MarcaAuditado = $datos['MarcaAuditado'] ?? '';
        
        // Fechas y montos
        $request->FechaVencimiento = empty($datos['FechaVencimiento']) ? null : $datos['FechaVencimiento'];
        $request->MontoTotal = $datos['MontoTotal'] ?? 0.0;
        $request->MontoAutorizado = $datos['MontoAutorizado'] ?? 0.0;
        $request->MontoCoseguroAfiliado = $datos['MontoCoseguroAfiliado'] ?? 0.0;
        $request->MontoCoseguroPrestador = $datos['MontoCoseguroPrestador'] ?? 0.0;
        $request->MontoExcedenteCoseguro = $datos['MontoExcedenteCoseguro'] ?? 0.0;
        
        // Descripciones de diagnóstico
        $request->DescripcionCodigoDiagnostico = $datos['DescripcionCodigoDiagnostico'] ?? '';
        $request->DescripcionSecuenciaDiagnostico = $datos['DescripcionSecuenciaDiagnostico'] ?? '';
        
        // Otras fechas
        $request->FechaEmision = empty($datos['FechaEmision']) ? null : $datos['FechaEmision'];
        
        // Estados
        $request->Intervenida = $datos['Intervenida'] ?? '';
        $request->Estado = $datos['Estado'] ?? 0;
        $request->DescripcionEstado = $datos['DescripcionEstado'] ?? '';
        $request->OrigenAutorizacion = $datos['OrigenAutorizacion'] ?? '';
        $request->TipoPrestacion = $datos['TipoPrestacion'] ?? '';
        $request->NumeroExpediente = $datos['NumeroExpediente'] ?? '';
        $request->FechaAnulacion = empty($datos['FechaAnulacion']) ? null : $datos['FechaAnulacion'];
        $request->CodigoOrganizacion = $datos['CodigoOrganizacion'] ?? 0;
        $request->DescripcionOrganizacion = $datos['DescripcionOrganizacion'] ?? '';
        $request->CoberturaAdicionalId = $datos['CoberturaAdicionalId'] ?? 0;
        $request->CoberturaAdicionalDescr = $datos['CoberturaAdicionalDescr'] ?? '';
        $request->CantidadCuotas = $datos['CantidadCuotas'] ?? 0;
        
        // Colecciones vacías por defecto
        $request->ErroresCabecera = new \stdClass();
        $request->ErroresCabecera->ErrCabIt = [];
        
        $request->Observ = new \stdClass();
        $request->Observ->ObservItem = [];
        
        $request->Ambulatorio = new \stdClass();
        $request->Ambulatorio->AmbIt = [];
        
        return $request;
    }

    /**
     * Invoca el método Execute del servicio OSEF
     * 
     * @param string $usuario Usuario OSEF
     * @param string $password Password OSEF
     * @param array $datos Parámetros de la consulta (NumeroAutorizacion, NumeroAfiliado, etc.)
     * @return array ['success' => bool, 'data' => stdClass|null, 'error' => string|null, 'soap_request' => string, 'soap_response' => string]
     */
    public static function execute($usuario, $password, $datos = [])
    {
        $result = [
            'success' => false,
            'data' => null,
            'error' => null,
            'soap_request' => null,
            'soap_response' => null,
            'code' => null,
        ];

        try {
            // Obtiene cliente SOAP
            $client = self::getClient();

            // Construye request
            $sdt_entrada = self::buildRequest($usuario, $password, $datos);

            // Ejecuta operación Execute
            $response = $client->Execute(['Sdt_entrada' => $sdt_entrada]);

            // Captura request y response SOAP para debugging
            $result['soap_request'] = $client->__getLastRequest();
            $result['soap_response'] = $client->__getLastResponse();

            // Valida respuesta
            if (isset($response->Sdt_salida)) {
                $result['success'] = true;
                $result['data'] = $response->Sdt_salida;
                $result['code'] = 1;
            } else {
                $result['error'] = 'Respuesta inválida del servidor OSEF';
                $result['code'] = -1;
            }

        } catch (SoapFault $e) {
            $result['error'] = 'Error SOAP: ' . $e->getMessage();
            $result['code'] = -2;
            try {
                $result['soap_request'] = $client->__getLastRequest();
                $result['soap_response'] = $client->__getLastResponse();
            } catch (\Throwable $th) {
                // No hay cliente activo
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            $result['code'] = -3;
        }

        return $result;
    }

    /**
     * Consulta datos de una autorización en OSEF
     * 
     * @param string $usuario Usuario OSEF
     * @param string $password Password OSEF
     * @param int $numeroAutorizacion Número de autorización
     * @param int $delegacion Delegación (default 0)
     * @param string $numeroAfiliado Número de afiliado (opcional)
     * @return array Resultado con estructura similar a execute()
     */
    public static function consultarAutorizacion($usuario, $password, $numeroAutorizacion, $delegacion = 0, $numeroAfiliado = '')
    {
        $datos = [
            'Modalidad' => 1,  // Consulta
            'Modo' => 'C',
            'NumeroAutorizacion' => $numeroAutorizacion,
            'Delegacion' => $delegacion,
            'NumeroAfiliado' => $numeroAfiliado,
        ];

        return self::execute($usuario, $password, $datos);
    }

    /**
     * Consulta datos de un afiliado en OSEF
     * 
     * @param string $usuario Usuario OSEF
     * @param string $password Password OSEF
     * @param string $numeroAfiliado Número de afiliado
     * @param int $plan Plan (opcional)
     * @param int $gravamen Gravamen (optional)
     * @return array Resultado con estructura similar a execute()
     */
    public static function consultarAfiliado($usuario, $password, $numeroAfiliado, $plan = 0, $gravamen = 0)
    {
        $datos = [
            'Modalidad' => 1,  // Consulta
            'Modo' => 'C',
            'NumeroAfiliado' => $numeroAfiliado,
            'Plan' => $plan,
            'Gravamen' => $gravamen,
        ];

        return self::execute($usuario, $password, $datos);
    }

    /**
     * Extrae errores de la cabecera de respuesta
     * 
     * @param stdClass $sdt_salida Objeto SDT_AutoWS de respuesta
     * @return array Array de errores encontrados
     */
    public static function extraerErroresCabecera($sdt_salida)
    {
        $errores = [];

        if (!empty($sdt_salida->ErroresCabecera) && !empty($sdt_salida->ErroresCabecera->ErrCabIt)) {
            $items = $sdt_salida->ErroresCabecera->ErrCabIt;
            
            // Si es un objeto único, convertir a array
            if (!is_array($items)) {
                $items = [$items];
            }

            foreach ($items as $err) {
                $errores[] = [
                    'codigo' => $err->CodigoError ?? null,
                    'tipo' => $err->TipoError ?? null,
                    'descripcion' => $err->DescripcionError ?? null,
                ];
            }
        }

        return $errores;
    }

    /**
     * Extrae errores ambulatorios de respuesta
     * 
     * @param stdClass $sdt_salida Objeto SDT_AutoWS de respuesta
     * @return array Array de errores ambulatorios
     */
    public static function extraerErroresAmbulatorio($sdt_salida)
    {
        $errores = [];

        if (!empty($sdt_salida->Ambulatorio) && !empty($sdt_salida->Ambulatorio->AmbIt)) {
            $items = $sdt_salida->Ambulatorio->AmbIt;
            
            if (!is_array($items)) {
                $items = [$items];
            }

            foreach ($items as $amb) {
                if (!empty($amb->ErroresAmb) && !empty($amb->ErroresAmb->ErrAmbIt)) {
                    $errores_amb = $amb->ErroresAmb->ErrAmbIt;
                    
                    if (!is_array($errores_amb)) {
                        $errores_amb = [$errores_amb];
                    }

                    foreach ($errores_amb as $err) {
                        $errores[] = [
                            'seccion_nomenclador' => $amb->SeccionNomenclador ?? null,
                            'codigo_practica' => $amb->CodigoPractica ?? null,
                            'codigo_error' => $err->CodigoError ?? null,
                            'tipo_error' => $err->TipoError ?? null,
                            'descripcion_error' => $err->DescripcionError ?? null,
                        ];
                    }
                }
            }
        }

        return $errores;
    }

    /**
     * Extrae observaciones de respuesta
     * 
     * @param stdClass $sdt_salida Objeto SDT_AutoWS de respuesta
     * @return array Array de observaciones
     */
    public static function extraerObservaciones($sdt_salida)
    {
        $observaciones = [];

        if (!empty($sdt_salida->Observ) && !empty($sdt_salida->Observ->ObservItem)) {
            $items = $sdt_salida->Observ->ObservItem;
            
            if (!is_array($items)) {
                $items = [$items];
            }

            foreach ($items as $obs) {
                $observaciones[] = [
                    'numero' => $obs->ObservNro ?? null,
                    'texto' => $obs->ObservTexto ?? null,
                    'tipo' => $obs->ObservTipo ?? null,
                    'usuario' => $obs->ObservUsu ?? null,
                    'usuario_nom' => $obs->ObservUsuNom ?? null,
                    'fecha_hora' => $obs->ObservFechaHora ?? null,
                    'respuesta' => $obs->ObservRespuesta ?? null,
                    'usuario_resp' => $obs->ObservUsuarioResp ?? null,
                    'usuario_nom_resp' => $obs->ObservUsuNomResp ?? null,
                    'fecha_resp' => $obs->ObservFechaResp ?? null,
                ];
            }
        }

        return $observaciones;
    }

    /**
     * Extrae datos de prácticas ambulatorias
     * 
     * @param stdClass $sdt_salida Objeto SDT_AutoWS de respuesta
     * @return array Array de prácticas ambulatorias
     */
    public static function extraerAmbulatorio($sdt_salida)
    {
        $practicas = [];

        if (!empty($sdt_salida->Ambulatorio) && !empty($sdt_salida->Ambulatorio->AmbIt)) {
            $items = $sdt_salida->Ambulatorio->AmbIt;
            
            if (!is_array($items)) {
                $items = [$items];
            }

            foreach ($items as $amb) {
                $practicas[] = [
                    'numero_interno' => $amb->NumeroInterno ?? null,
                    'codigo_practica' => $amb->CodigoPractica ?? null,
                    'alias_practica' => $amb->AliasPractica ?? null,
                    'cantidad_practica' => $amb->CantidadPractica ?? 0,
                    'codigo_operacion' => $amb->CodigoOperacion ?? null,
                    'descripcion_practica' => $amb->DescripcionNomenclador ?? null,
                    'monto_autorizado' => $amb->MontoAutorizado ?? 0.0,
                    'monto_coseguro_afiliado' => $amb->MontoCoseguroAfiliado ?? 0.0,
                    'marca_autorizado' => $amb->MarcaAutorizado ?? null,
                    'marca_auditado' => $amb->MarcaAuditado ?? null,
                ];
            }
        }

        return $practicas;
    }

    /**
     * Formatea una respuesta completa para devolución en API
     * 
     * @param array $result Resultado de execute()
     * @return array Respuesta formateada
     */
    public static function formatearRespuesta($result)
    {
        if (!$result['success']) {
            return [
                'status' => 'error',
                'codigo' => $result['code'],
                'mensaje' => $result['error'],
                'soap_request' => $result['soap_request'],
                'soap_response' => $result['soap_response'],
            ];
        }

        $sdt = $result['data'];
        $errores_cabecera = self::extraerErroresCabecera($sdt);
        $errores_ambulatorio = self::extraerErroresAmbulatorio($sdt);
        $observaciones = self::extraerObservaciones($sdt);
        $ambulatorio = self::extraerAmbulatorio($sdt);

        return [
            'status' => 'success',
            'codigo' => $result['code'],
            'datos_afiliado' => [
                'numero_afiliado' => $sdt->NumeroAfiliado ?? null,
                'nombre_apellido' => $sdt->NombreApellidoAfiliado ?? null,
                'plan_id' => $sdt->Plan ?? null,
                'descripcion_plan' => $sdt->DescripcionPlan ?? null,
                'estado' => $sdt->Estado ?? null,
                'descripcion_estado' => $sdt->DescripcionEstado ?? null,
                'fecha_emision' => $sdt->FechaEmision ?? null,
                'fecha_vencimiento' => $sdt->FechaVencimiento ?? null,
            ],
            'datos_autorizacion' => [
                'numero_autorizacion' => $sdt->NumeroAutorizacion ?? null,
                'monto_total' => $sdt->MontoTotal ?? 0.0,
                'monto_autorizado' => $sdt->MontoAutorizado ?? 0.0,
                'monto_coseguro_afiliado' => $sdt->MontoCoseguroAfiliado ?? 0.0,
                'monto_coseguro_prestador' => $sdt->MontoCoseguroPrestador ?? 0.0,
                'tipo_prestacion' => $sdt->TipoPrestacion ?? null,
                'origen_autorizacion' => $sdt->OrigenAutorizacion ?? null,
            ],
            'errores_cabecera' => $errores_cabecera,
            'errores_ambulatorio' => $errores_ambulatorio,
            'observaciones' => $observaciones,
            'ambulatorio' => $ambulatorio,
            'soap_request' => $result['soap_request'],
            'soap_response' => $result['soap_response'],
        ];
    }
}
