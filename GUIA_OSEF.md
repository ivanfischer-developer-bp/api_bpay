# Guía de Consumo del Servicio SOAP de OSEF

## Resumen Técnico

- **Servicio:** AuWebService de OSEF (Obra Social del Estado Federal)
- **Protocolo:** SOAP 1.1 (Document/Literal)
- **WSDL:** `https://validador.osef.gob.ar/SIA_OSPTF_PROD_WS/servlet/aauwebservice?wsdl`
- **Endpoint:** `https://validador.osef.gob.ar/SIA_OSPTF_PROD_WS/servlet/aauwebservice`
- **Operación única:** `Execute`
- **SOAPAction:** `com.sia.osefaction/AAUWEBSERVICE.Execute`

## Estructura SOAP

### Request

La operación `Execute` recibe un objeto de tipo `SDT_AutoWS` envuelto como `Sdt_entrada`.

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                   xmlns:tns="com.sia.osef">
  <soapenv:Header/>
  <soapenv:Body>
    <tns:AuWebService.Execute>
      <tns:Sdt_entrada>
        <tns:Usuario>USUARIO_OSEF</tns:Usuario>
        <tns:Password>PASSWORD_OSEF</tns:Password>
        <tns:Modalidad>1</tns:Modalidad>
        <tns:Modo>C</tns:Modo>
        <tns:Delegacion>0</tns:Delegacion>
        <tns:NumeroAutorizacion>123456</tns:NumeroAutorizacion>
        <!-- resto de campos según tipo de consulta -->
      </tns:Sdt_entrada>
    </tns:AuWebService.Execute>
  </soapenv:Body>
</soapenv:Envelope>
```

### Response

Retorna el mismo tipo `SDT_AutoWS` dentro de `Sdt_salida`, con datos poblados + errores, observaciones y detalles ambulatorios.

---

## Campos Principales de `SDT_AutoWS`

### Autenticación (OBLIGATORIO)
- `Usuario` (string): Usuario OSEF
- `Password` (string): Contraseña OSEF

### Control de Operación
- `Modalidad` (byte): 1=Consulta, 2=Inclusión, etc.
- `Modo` (string): 'C'=Consulta

### Datos de Afiliado y Autorización
- `Delegacion` (int): Delegación (default: 0)
- `NumeroAutorizacion` (long): Número de autorización a consultar
- `NumeroAfiliado` (string): Número de afiliado
- `Plan` (int): ID del plan
- `Gravamen` (byte): Código de gravamen
- `NumeroProveedor` (int): Número de proveedor

### Datos de Profesionales
- `MatriculaPrescriptor` (string): Matrícula del prescriptor
- `ProvinciaMatriculaPrescriptor` (string): Provincia de matrícula
- `EspecialidadMatriculaPrescriptor` (string): Especialidad
- `MatriculaEfector` (string): Matrícula del efector
- `ProvinciaMatriculaEfector` (string): Provincia del efector
- `EspecialidadMatriculaEfector` (string): Especialidad del efector
- `CategoriaEfector` (string): Categoría del efector

### Diagnóstico
- `CodigoDiagnostico` (string): Código CIE-10 del diagnóstico
- `SecuenciaDiagnostico` (byte): Secuencia (1, 2, 3, etc.)

### Fechas (nullable)
- `FechaSolicitud` (date): Fecha de solicitud
- `FechaPrescripcion` (date): Fecha de prescripción
- `FechaVencimiento` (date): Fecha de vencimiento
- `FechaEmision` (date): Fecha de emisión
- `FechaAnulacion` (date): Fecha de anulación

### Montos
- `MontoTotal` (double): Monto total solicitado
- `MontoAutorizado` (double): Monto autorizado
- `MontoCoseguroAfiliado` (double): Coseguro a cargo del afiliado
- `MontoCoseguroPrestador` (double): Coseguro a cargo del prestador
- `MontoExcedenteCoseguro` (double): Excedente de coseguro

### Respuesta de Estado
- `Estado` (byte): Código de estado (1=Activo, 2=Inactivo, etc.)
- `DescripcionEstado` (string): Descripción del estado
- `MarcaAutorizado` (string): Marca si está autorizado
- `MarcaAuditado` (string): Marca si fue auditado
- `Intervenida` (string): Indica si la autorización fue intervenida

### Colecciones de Respuesta
- `ErroresCabecera`: Array de errores de cabecera (ErrCabIt)
- `Observ`: Array de observaciones (ObservItem)
- `Ambulatorio`: Array de prácticas ambulatorias (AmbIt)

---

## Uso desde Endpoint de API BPay

### URL
```
POST /int/afiliaciones/afiliado/prueba-osef
```

**Headers requeridos:**
```
Authorization: Bearer {token_jwt}
Content-Type: application/json
```

### Ejemplo 1: Consultar una Autorización

```json
{
  "usuario_osef": "MI_USUARIO",
  "password_osef": "MI_PASSWORD",
  "tipo_consulta": "autorizacion",
  "numero_autorizacion": 123456,
  "numero_afiliado": "001234567",
  "delegacion": 0
}
```

**Respuesta exitosa:**
```json
{
  "status": "ok",
  "count": 1,
  "message": "Consulta realizada exitosamente",
  "data": {
    "status": "success",
    "codigo": 1,
    "datos_afiliado": {
      "numero_afiliado": "001234567",
      "nombre_apellido": "PEREZ, JUAN",
      "plan_id": 10,
      "descripcion_plan": "Plan Standard",
      "estado": 1,
      "descripcion_estado": "Activo",
      "fecha_emision": "2025-01-01",
      "fecha_vencimiento": "2026-12-31"
    },
    "datos_autorizacion": {
      "numero_autorizacion": 123456,
      "monto_total": 5000.00,
      "monto_autorizado": 5000.00,
      "monto_coseguro_afiliado": 500.00,
      "monto_coseguro_prestador": 0.00,
      "tipo_prestacion": "Consulta Especialista",
      "origen_autorizacion": "Web"
    },
    "errores_cabecera": [],
    "errores_ambulatorio": [],
    "observaciones": [],
    "ambulatorio": [
      {
        "numero_interno": 1,
        "codigo_practica": "001",
        "alias_practica": "CONSULTA OFTALMOLOGIA",
        "cantidad_practica": 1.0,
        "monto_autorizado": 5000.00,
        "marca_autorizado": "S",
        "marca_auditado": "N"
      }
    ],
    "soap_request": "<?xml version=...",
    "soap_response": "<?xml version=..."
  }
}
```

### Ejemplo 2: Consultar Datos de un Afiliado

```json
{
  "usuario_osef": "MI_USUARIO",
  "password_osef": "MI_PASSWORD",
  "tipo_consulta": "afiliado",
  "numero_afiliado": "001234567",
  "plan": 10,
  "gravamen": 0
}
```

### Ejemplo 3: Con cURL

```bash
curl -X POST https://tuapi.local/int/afiliaciones/afiliado/prueba-osef \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "usuario_osef": "USUARIO",
    "password_osef": "PASSWORD",
    "tipo_consulta": "autorizacion",
    "numero_autorizacion": 123456,
    "delegacion": 0
  }'
```

---

## Estructura del Helper WsOsef

El archivo `app/Http/Helpers/wsosef.php` proporciona:

### Funciones Públicas

#### `WsOsef::getClient($options = [])`
Crea una instancia de `SoapClient` ya configurada.

```php
$client = \App\Http\Helpers\WsOsef::getClient();
```

#### `WsOsef::execute($usuario, $password, $datos = [])`
Operación SOAP genérica. Retorna array con:
- `success` (bool)
- `data` (stdClass | null) - Objeto SDT_AutoWS retornado
- `error` (string | null)
- `code` (int) - Código: 1=éxito, -2=SOAP error, -3=excepción
- `soap_request` (string) - XML enviado
- `soap_response` (string) - XML recibido

```php
$result = \App\Http\Helpers\WsOsef::execute($usuario, $password, [
    'NumeroAutorizacion' => 123456,
    'Delegacion' => 0,
]);

if ($result['success']) {
    $sdt_salida = $result['data'];
    echo $sdt_salida->NumeroAfiliado;
}
```

#### `WsOsef::consultarAutorizacion($usuario, $password, $numeroAutorizacion, $delegacion = 0, $numeroAfiliado = '')`
Consulta una autorización. Retorna igual estructura que `execute()`.

```php
$result = \App\Http\Helpers\WsOsef::consultarAutorizacion(
    'USUARIO',
    'PASSWORD',
    123456,
    0,
    '001234567'
);
```

#### `WsOsef::consultarAfiliado($usuario, $password, $numeroAfiliado, $plan = 0, $gravamen = 0)`
Consulta datos de afiliado.

```php
$result = \App\Http\Helpers\WsOsef::consultarAfiliado(
    'USUARIO',
    'PASSWORD',
    '001234567',
    10,
    0
);
```

#### `WsOsef::extraerErroresCabecera($sdt_salida)`
Extrae array de errores de cabecera.

```php
$errores = \App\Http\Helpers\WsOsef::extraerErroresCabecera($respuesta->Sdt_salida);
// Retorna: [['codigo' => 1, 'tipo' => 'Error', 'descripcion' => '...'], ...]
```

#### `WsOsef::extraerErroresAmbulatorio($sdt_salida)`
Extrae errores de prácticas ambulatorias.

#### `WsOsef::extraerObservaciones($sdt_salida)`
Extorna array de observaciones.

#### `WsOsef::extraerAmbulatorio($sdt_salida)`
Extrae prácticas ambulatorias.

#### `WsOsef::formatearRespuesta($result)`
Convierte resultado bruto en estructura legible para API.

```php
$formateado = \App\Http\Helpers\WsOsef::formatearRespuesta($result);
```

---

## Casos de Error

### Credenciales Inválidas
```json
{
  "status": "error",
  "codigo": -2,
  "mensaje": "Error SOAP: SOAP-ERROR: Server returned invalid XML.",
  "soap_response": "..."
}
```

### Número de Autorización No Existe
```json
{
  "status": "error",
  "codigo": -2,
  "mensaje": "Error SOAP: SOAP-ERROR: Server returned a fault in Body element...",
  "soap_response": "<soap:Body><soap:Fault>..."
}
```

### Autorización Rechazada (Sin Errores en Cabecera)
```json
{
  "status": "success",
  "codigo": 1,
  "datos_afiliado": { ... },
  "errores_cabecera": [
    {
      "codigo": 100,
      "tipo": "ERROR_AUTORIDAD",
      "descripcion": "Afiliado sin cobertura para esa práctica"
    }
  ],
  "errores_ambulatorio": [],
  "ambulatorio": []
}
```

---

## Notas Importantes

1. **Auditoría:** Todas las consultas a OSEF quedan registradas en `extras` con `soap_request` y `soap_response`. Usa esto para debugging pero **nunca expongas credenciales** en logs públicos.

2. **Timeout:** El cliente SOAP tiene timeout de 30 segundos. Si el servicio OSEF es lento, puede vencer.

3. **Cache WSDL:** El cliente cachea el WSDL en disco. Si OSEF actualiza el WSDL, puede haber incompatibilidades. Monitor de cambios recomendado.

4. **Horarios:** OSEF puede tener disponibilidad limitada. Verifica si hay mantenimiento programado.

5. **SSL/TLS:** El endpoint usa HTTPS. Asegúrate que PHP tiene certificados CA actualizados.

6. **Permiso requerido:** El endpoint `/prueba-osef` requiere permiso `gestionar afiliados` o rol `admin`. Configura en BD según necesidades.

---

## Integración Productiva

Una vez validado con `/prueba-osef`, crea funciones específicas en otras partes de tu lógica:

```php
// En Controllers/Services/OsefService.php o similar

public function verificarSaldo($numeroAfiliado, $plan)
{
    $usuario = config('services.osef.usuario');
    $password = config('services.osef.password');
    
    $result = \App\Http\Helpers\WsOsef::consultarAfiliado(
        $usuario,
        $password,
        $numeroAfiliado,
        $plan
    );

    if (!$result['success']) {
        throw new Exception('OSEF no respondió: ' . $result['error']);
    }

    $errores = \App\Http\Helpers\WsOsef::extraerErroresCabecera($result['data']);
    
    if (!empty($errores)) {
        throw new Exception('Afiliado sin cobertura: ' . $errores[0]['descripcion']);
    }

    return [
        'activo' => $result['data']->Estado == 1,
        'plan' => $result['data']->DescripcionPlan,
        'vencimiento' => $result['data']->FechaVencimiento,
    ];
}
```

---

## Testing Offline

Si OSEF no está disponible en desarrollo, puedes mockearlo:

```php
// En Servicio de prueba:

if (env('OSEF_MOCK', false)) {
    return [
        'success' => true,
        'data' => (object)[
            'NumeroAfiliado' => '001234567',
            'NombreApellidoAfiliado' => 'PEREZ, JUAN',
            'Estado' => 1,
            'MontoAutorizado' => 5000,
            // ... más campos
        ],
    ];
}

// En .env.testing:
OSEF_MOCK=true
```

---

## Logs Recomendados

```php
// Antes de llamar a OSEF:
Log::info('Consultando OSEF', [
    'numero_autorizacion' => $numeroAutorizacion,
    'tipo_consulta' => $tipoConsulta,
    'usuario' => '***', // Nunca loguees password
    'timestamp' => now(),
]);

// Después:
Log::info('Respuesta OSEF', [
    'codigo' => $result['code'],
    'success' => $result['success'],
    'errores' => $errores,
]);
```

---

## Referencias

- WSDL oficial: `https://validador.osef.gob.ar/SIA_OSPTF_PROD_WS/servlet/aauwebservice?wsdl`
- Helper ubicado en: `app/Http/Helpers/wsosef.php`
- Endpoint de prueba: `POST /int/afiliaciones/afiliado/prueba-osef`
- Método del controlador: `AfiliadoController::prueba_osef()`

