# Diagnóstico: Error Código -3 en Microsoft Graph

## Problema Identificado

El error `code: -3` con mensaje "Error al enviar email con Microsoft Graph" puede tener múltiples causas. He mejorado el sistema de logging para identificar exactamente cuál es.

## Cambios Realizados

### 1. **Configuración en config/mail.php** ✅
Agregué la configuración faltante:
```php
'use_microsoft_graph' => env('MAIL_USE_MICROSOFT_GRAPH', false),
```

**Problema que resolvía:** El trait `SendsEmailsTrait` checaba `config('mail.use_microsoft_graph')` que no existía, por lo que siempre retornaba `false` aunque `MAIL_USE_MICROSOFT_GRAPH=true` en `.env`.

### 2. **Logging Mejorado en SendsEmailsTrait** ✅
Ahora el método `sendEmail()` registra:
- Si se va a usar Microsoft Graph o SMTP
- El resultado de cada intento
- Cuándo hace fallback

### 3. **Logging Detallado en sendViaGraphAPI()** ✅
Ahora registra:
- Datos extraídos del Mailable (subject, body length, cc, bcc)
- Cantidad de adjuntos del Mailable vs parámetros
- Total de adjuntos a enviar
- Resultado final (SUCCESS o FAIL)

### 4. **Logging Detallado en MicrosoftGraphService** ✅
Cuando hay un error, ahora registra:
- Status HTTP completo
- Respuesta JSON de Microsoft Graph (el error exacto)
- Endpoint usado
- Email y subject del mensaje

### 5. **Comando Mejorado: `msgraph:test-email`** ✅
Ahora muestra:
- Configuración actual
- Credenciales verificadas
- Intento de envío con logging detallado

## Cómo Diagnosticar

### Paso 1: Ejecutar el comando de prueba
```bash
php artisan msgraph:test-email --to=tu_email@ejemplo.com
```

### Paso 2: Revisar los logs
```bash
# Ver últimos logs
tail -f storage/logs/laravel.log

# Filtrar solo Microsoft Graph
tail -f storage/logs/laravel.log | grep -i "graph\|sendEmail\|sendVia"
```

### Paso 3: Identificar el problema

#### Escenario 1: `use_microsoft_graph: FALSE`
```text
[INFO] sendEmail: Iniciando envío de email
[INFO] sendEmail: use_microsoft_graph: FALSE ❌
[INFO] sendEmail: Microsoft Graph deshabilitado, usando SMTP
```
**Solución:** Verificar que `.env` tiene:
```
MAIL_USE_MICROSOFT_GRAPH=true
```

#### Escenario 2: Token no se obtiene
```text
[ERROR] Error en sendViaGraphAPI: [error details about token]
```
**Solución:** Verificar credenciales en `.env`:
```
MSGRAPH_CLIENT_ID=xxx
MSGRAPH_CLIENT_SECRET=xxx
MSGRAPH_TENANT_ID=xxx
MSGRAPH_USER_EMAIL=xxx
```

#### Escenario 3: Microsoft Graph retorna error
```text
[ERROR] Error al enviar email por Microsoft Graph - Respuesta de la API
[ERROR] status: 403
[ERROR] json: {"error":{"code":"...","message":"..."}}
```
**Soluciones posibles:**
- Error 403: Permisos insuficientes en Azure
- Error 400: Formato de email inválido o datos malformados
- Error 401: Token expirado o inválido
- Error 503: Servicio no disponible

#### Escenario 4: Fallback a SMTP y error SMTP
```text
[WARNING] Fallback a SMTP después de que Microsoft Graph falló
[ERROR] Error en sendViaSMTP: Expected response code 354 but got code "503"
```
**Solución:** Configurar SMTP correctamente:
```
MAIL_MAILER=smtp
MAIL_HOST=mail.example.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=user@example.com
MAIL_PASSWORD=password
```

## Checklist de Verificación

- [ ] ¿`MAIL_USE_MICROSOFT_GRAPH=true` en `.env`?
- [ ] ¿`MSGRAPH_CLIENT_ID` está configurado en `.env`?
- [ ] ¿`MSGRAPH_CLIENT_SECRET` está configurado en `.env`?
- [ ] ¿`MSGRAPH_TENANT_ID` está configurado en `.env`?
- [ ] ¿`MSGRAPH_USER_EMAIL` está configurado en `.env`?
- [ ] ¿La aplicación Azure tiene permisos para enviar Mail?
- [ ] ¿El token se puede obtener correctamente?
- [ ] ¿Microsoft Graph API está disponible?

## Comandos Útiles

### Prueba rápida
```bash
php artisan msgraph:test-email --to=test@example.com
```

### Prueba forzando SMTP
```bash
php artisan msgraph:test-email --to=test@example.com --use-smtp
```

### Ver logs en tiempo real
```bash
tail -f storage/logs/laravel.log
```

### Filtrar solo errores de Graph
```bash
tail -f storage/logs/laravel.log | grep -E "sendViaGraphAPI|Error al enviar email por Microsoft|Fallback a SMTP"
```

### Borrar logs viejos
```bash
rm storage/logs/laravel.log
```

## Flujo Correcto de Ejecución

```
Request → EmailController
  ↓
if(env('MAIL_USE_MICROSOFT_GRAPH')) = TRUE
  ↓
$this->sendEmail($emails, $mailable)
  ↓
SendsEmailsTrait.sendEmail()
  ├─ Check: config('mail.use_microsoft_graph') = TRUE ✅
  ├─ sendViaGraphAPI()
  │  ├─ getMicrosoftGraphService()
  │  ├─ getAccessToken()
  │  ├─ prepareMessage()
  │  ├─ call Microsoft Graph API
  │  └─ Return true/false
  │
  ├─ Si TRUE → ✅ EXIT
  ├─ Si FALSE → Log warning
  └─ sendViaSMTP() (fallback)
     ├─ Mail::to()->send()
     └─ Return true/false
```

## Próximos Pasos

1. **Ejecuta el comando de prueba:**
   ```bash
   php artisan msgraph:test-email --to=tu_email@example.com
   ```

2. **Mira los logs:**
   ```bash
   tail -100 storage/logs/laravel.log
   ```

3. **Comparte conmigo:**
   - La salida del comando
   - Los últimos 100 líneas del log
   - Tu `.env` (sin secretos)

Con esta información podré identificar exactamente dónde está el problema.

