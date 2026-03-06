# Integración con OSEF - Guía de Inicio Rápido

Este directorio contiene ejemplos, herramientas y documentación para consumir el servicio SOAP de OSEF desde BPay API.

## Archivos Incluidos

### 1. **Helper PHP** (`app/Http/Helpers/wsosef.php`)
Clase de utilidad para consumir el servicio SOAP de OSEF desde PHP.

**Clases principales:**
- `WsOsef::getClient()` - Instancia de SoapClient
- `WsOsef::execute()` - Llamada SOAP genérica
- `WsOsef::consultarAutorizacion()` - Consulta de autorización
- `WsOsef::consultarAfiliado()` - Consulta de afiliado
- `WsOsef::extraerErroresCabecera()` - Parse de errores
- `WsOsef::formatearRespuesta()` - Formatea respuesta para API

**Ubicación:** `/app/Http/Helpers/wsosef.php`

---

### 2. **Controlador** (`AfiliadoController.php`)
Método `prueba_osef()` en `app/Http/Controllers/Internos/Afiliaciones/AfiliadoController.php`

**Endpoint:** `POST /int/afiliaciones/afiliado/prueba-osef`

**Requiere:**
- Autenticación JWT
- Permiso `gestionar afiliados` o rol `admin`

**Parámetros:**
```json
{
  "usuario_osef": "USUARIO",
  "password_osef": "PASSWORD",
  "tipo_consulta": "autorizacion|afiliado",
  "numero_autorizacion": 123456,
  "numero_afiliado": "001234567",
  "delegacion": 0,
  "plan": 0,
  "gravamen": 0
}
```

---

### 3. **Documentación** (`GUIA_OSEF.md`)
Guía completa con:
- Especificación SOAP
- Estructura de tipos `SDT_AutoWS`
- Ejemplos de uso
- Manejo de errores
- Integración productiva

**Ubicación:** `/GUIA_OSEF.md` (raíz del proyecto)

---

### 4. **Python Test Script** (`test_osef.py`)
Script Python para probar el endpoint sin necesidad de Postman.

**Uso:**
```bash
# Consultar autorización
python3 tools/test_osef.py \
  --tipo autorizacion \
  --numero 123456 \
  --usuario USUARIO \
  --password PASSWORD \
  --token "tu_jwt_token" \
  --url "http://localhost:8000"

# Consultar afiliado
python3 tools/test_osef.py \
  --tipo afiliado \
  --afiliado "001234567" \
  --usuario USUARIO \
  --password PASSWORD
```

---

### 5. **Shell Scripts** (`ejemplos_curl_osef.sh`)
Ejemplos de cURL y Bash para testing sin dependencias.

**Uso:**
```bash
source tools/ejemplos_curl_osef.sh

# Consultar autorización
consultar_autorizacion 123456 "001234567"

# Consultar afiliado
consultar_afiliado "001234567" 10
```

---

### 6. **Postman Collection** (`OSEF_BPay.postman_collection.json`)
Colección lista para importar en Postman.

**Pasos:**
1. Abre Postman
2. Click en **Import**
3. Selecciona `tools/OSEF_BPay.postman_collection.json`
4. Configura variables de entorno:
   - `api_url`: http://localhost:8000
   - `jwt_token`: tu_token_jwt
   - `usuario_osef`: TU_USUARIO
   - `password_osef`: TU_PASSWORD
5. Ejecuta las requests

---

## Inicio Rápido

### Opción A: Postman (Recomendado)

1. Descarga e instala [Postman](https://www.postman.com/downloads/)
2. Importa `tools/OSEF_BPay.postman_collection.json`
3. Configura variables de entorno
4. Ejecuta "Login" primero (obtiene JWT token)
5. Luego ejecuta "Consultar Autorización"

### Opción B: cURL (Línea de comandos)

```bash
# 1. Obtén token de autenticación
TOKEN=$(curl -s -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{"email":"tu_email@ejemplo.com","password":"tu_password"}' \
  | jq -r '.token')

# 2. Consulta autorización
curl -X POST http://localhost:8000/int/afiliaciones/afiliado/prueba-osef \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "usuario_osef": "USUARIO",
    "password_osef": "PASSWORD",
    "tipo_consulta": "autorizacion",
    "numero_autorizacion": 123456
  }' | jq '.'
```

### Opción C: Python

```bash
python3 tools/test_osef.py \
  --tipo autorizacion \
  --numero 123456 \
  --usuario USUARIO \
  --password PASSWORD \
  --token "tu_jwt" \
  --url http://localhost:8000
```

---

## Estructura de Respuesta

### Respuesta Exitosa

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
      "estado": 1,
      "fecha_vencimiento": "2026-12-31"
    },
    "datos_autorizacion": {
      "numero_autorizacion": 123456,
      "monto_autorizado": 5000.0,
      "tipo_prestacion": "Consulta Especialista"
    },
    "errores_cabecera": [],
    "ambulatorio": [...]
  }
}
```

### Con Errores

```json
{
  "status": "ok",
  "data": {
    "errores_cabecera": [
      {
        "codigo": 100,
        "tipo": "ERROR",
        "descripcion": "Afiliado sin cobertura"
      }
    ]
  }
}
```

### Error de Conexión

```json
{
  "status": "error",
  "codigo": -2,
  "mensaje": "Error SOAP: SOAP-ERROR: Server returned invalid XML.",
  "soap_request": "<?xml...",
  "soap_response": "<?xml..."
}
```

---

## Debugging

### Ver XML SOAP

Todas las respuestas incluyen `soap_request` y `soap_response` cuando hay error:

```bash
curl http://localhost:8000/int/afiliaciones/afiliado/prueba-osef ... \
  | jq '.data.soap_request' | head -n 50
```

### Simular Offline

En `.env`:
```
OSEF_MOCK=true
```

En controller:
```php
if (env('OSEF_MOCK', false)) {
    // retorna respuesta ficticia
}
```

### Logs

Busca en `storage/logs/laravel.log`:
```bash
grep "OSEF" storage/logs/laravel.log
```

---

## Próximos Pasos

1. **Valida credenciales:** Asegúrate que `usuario_osef` y `password_osef` sea correctos
2. **Test en QA:** Prueba con datos reales en ambiente QA de OSEF
3. **Integración:** Usa funciones del helper en tus servicios
4. **Productivo:** Configura credenciales en `.env` (nunca hardcodear)
5. **Monitoreo:** Implementa logs y alertas para consultas fallidas

---

## Soporte

- **WSDL:** https://validador.osef.gob.ar/SIA_OSPTF_PROD_WS/servlet/aauwebservice?wsdl
- **Contacto OSEF:** validador.osef.gob.ar
- **Helper:** `app/Http/Helpers/wsosef.php`
- **Documentación completa:** `GUIA_OSEF.md`

---

## Notas de Seguridad

⚠️ **IMPORTANTE:**
- Nunca commitees credenciales de OSEF a Git
- Usa `.env` para almacenar `usuario_osef` y `password_osef`
- Los endpoints de "prueba" (`prueba_osef`) deben estar protegidos por permisos
- Los `soap_request` y `soap_response` contienen datos sensibles; cuidado al loguear
- Implementa rate limiting para evitar saturar OSEF

---

## Checklist de Producción

- [ ] Credenciales OSEF configuradas en `config/services.php` + `.env`
- [ ] Permiso `gestionar afiliados` asignado a usuarios autorizados
- [ ] Logs configurados para auditoría
- [ ] Rate limiting implementado
- [ ] Manejo de errores SOAP documentado
- [ ] Tests unitarios implementados
- [ ] Plan de fallback si OSEF está unavailable
- [ ] Monitoreo de disponibilidad de OSEF

