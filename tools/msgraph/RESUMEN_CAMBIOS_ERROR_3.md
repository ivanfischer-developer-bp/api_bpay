# Resumen de Cambios - Error Código -3

## Problemas Encontrados y Solucionados

### 🔴 Problema Principal: Configuración Missing

**Ubicación:** `/config/mail.php`

**Problema:**
- El trait `SendsEmailsTrait` checaba `config('mail.use_microsoft_graph', false)`
- Pero esta configuración **NO EXISTÍA** en `config/mail.php`
- Resultado: Siempre retornaba `false` como valor por defecto
- El email **NUNCA** usaba Microsoft Graph, iba directamente a SMTP
- SMTP fallaba con error "503 RCPT command expected"
- El controller mostraba error -3 porque Microsoft Graph "falló" (pero nunca se ejecutó)

**Solución:**
```php
// Añadido a config/mail.php
'use_microsoft_graph' => env('MAIL_USE_MICROSOFT_GRAPH', false),
```

---

### 🟡 Problema Secundario: Logging Insuficiente

**Ubicación:** Multiple files

**Problema:**
- No había forma de saber EXACTAMENTE por qué Microsoft Graph fallaba
- Los logs no mostraban la respuesta de la API
- No había forma de debuggear adjuntos o datos del Mailable

**Soluciones:**

#### 1. SendsEmailsTrait.php - Mejorado logging
- Log cuándo se inicia el envío
- Log qué configuración se está usando (Graph vs SMTP)
- Log resultado de cada intento
- Log detallado de adjuntos

#### 2. sendViaGraphAPI() - Mejorado logging  
- Log datos extraídos del Mailable
- Log cantidad de adjuntos
- Log exacto de qué parámetros se envían a Microsoft Graph

#### 3. MicrosoftGraphService.php - Mejorado logging
- Log respuesta HTTP completa
- Log JSON de error de Microsoft Graph
- Log endpoint usado
- Log todos los detalles del mensaje

#### 4. TestMicrosoftGraphEmail.php - Mejorado comando
- Muestra configuración actual
- Verifica credenciales
- Muestra paso a paso qué está haciendo
- Instrucciones de debugging

---

## Archivos Modificados

### 1. ✅ `/config/mail.php`
**Change:** Añadida configuración `use_microsoft_graph`
```diff
+ 'use_microsoft_graph' => env('MAIL_USE_MICROSOFT_GRAPH', false),
```

### 2. ✅ `/app/Traits/SendsEmailsTrait.php`
**Changes:**
- Mejorado método `sendEmail()`: Más logging
- Mejorado método `sendViaGraphAPI()`: Extrae y loguea adjuntos

### 3. ✅ `/app/Services/MicrosoftGraphService.php`
**Change:** Mejorado error logging en `sendEmail()`
```diff
- Log::error('Error al enviar email por Microsoft Graph', [
-     'status' => $response->status(),
-     'response' => $response->body(),
+ Log::error('Error al enviar email por Microsoft Graph - Respuesta de la API', [
+     'status' => $response->status(),
+     'body' => $response->body(),
+     'json' => $response->json(),
+     'endpoint' => $endpoint,
```

### 4. ✅ `/app/Console/Commands/TestMicrosoftGraphEmail.php`
**Change:** Completamente reescrito para mejor debugging
- Output más legible
- Verifica configuración
- Muestra credenciales configuradas
- Instrucciones de debugging

### 5. 📄 `DIAGNOSTICO_ERROR_CODIGO_3.md` (NUEVO)
**Content:** Guía completa de debugging y troubleshooting

---

## Flujo de Ejecución - ANTES vs DESPUÉS

### ❌ ANTES (Incorrecto)
```
1. Controller: env('MAIL_USE_MICROSOFT_GRAPH') = true ✅
2. Trait: config('mail.use_microsoft_graph') = false ❌ (NO EXISTÍA)
3. Resultado: Usa SMTP en lugar de Graph
4. SMTP falla: "503 RCPT command expected"
5. Response: code -3 (Parece que Graph falló, pero NUNCA se ejecutó)
```

### ✅ DESPUÉS (Correcto)
```
1. Controller: env('MAIL_USE_MICROSOFT_GRAPH') = true ✅
2. Trait: config('mail.use_microsoft_graph') = true ✅ (Existe y está sincronizado)
3. Resultado: Intenta Microsoft Graph
4. Si Graph funciona: ✅ Email enviado exitosamente
5. Si Graph falla: Log detallado + Fallback a SMTP
6. Response: code 1 (Graph éxito) o código apropiado con error detallado
```

---

## Cómo Probar

### 1. Verificar configuración
```bash
grep "use_microsoft_graph" config/mail.php
```

### 2. Ejecutar prueba
```bash
php artisan msgraph:test-email --to=tu_email@ejemplo.com
```

### 3. Ver logs
```bash
tail -50 storage/logs/laravel.log
```

---

## Qué Esperar Ahora

### Si Microsoft Graph está bien configurado:
```log
[INFO] sendEmail: Iniciando envío de email
[INFO] sendEmail: use_microsoft_graph: TRUE
[INFO] sendEmail: Intentando enviar por Microsoft Graph
[INFO] sendViaGraphAPI: Iniciando sendViaGraphAPI
[INFO] Resultado de Microsoft Graph: EXIT
[INFO] sendEmail: Email enviado exitosamente por Microsoft Graph
```
✅ Email enviado con **code: 1**

### Si hay error de Microsoft Graph:
```log
[ERROR] Error al enviar email por Microsoft Graph - Respuesta de la API
[ERROR] status: 403
[ERROR] json: {"error":{"code":"Unauthorized",...}}
```
Ahora SABES exactamente qué falló en Microsoft Graph (no es un -3 genérico)

### Si hay fallback a SMTP:
```log
[WARNING] Fallback a SMTP después de que Microsoft Graph falló
[INFO] sendEmail: Enviando por SMTP
```
El sistema intentó Graph, falló, y ahora intenta SMTP

---

## Beneficios de los Cambios

1. ✅ **Sincronización correcta** entre controller y trait
2. ✅ **Debugging claro** - Sabes exactamente qué está pasando
3. ✅ **Fallback automático** - Si Graph falla, intenta SMTP
4. ✅ **Logs detallados** - Error específico de Microsoft Graph API visible
5. ✅ **Comando de prueba** - Fácil de diagnosticar problemas

---

## Próximas Acciones Recomendadas

Si aún tienes problemas después de estos cambios:

1. Ejecuta: `php artisan msgraph:test-email --to=test@example.com`
2. Comparte los logs de output
3. Verifica que tu `.env` tiene:
   - `MAIL_USE_MICROSOFT_GRAPH=true`
   - Todas las credenciales de Microsoft Graph
   - SMTP configurado como fallback

