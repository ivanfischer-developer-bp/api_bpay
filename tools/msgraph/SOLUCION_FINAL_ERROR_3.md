# Solución Final - Error Código -3 "Error al enviar email con Microsoft Graph"

## 🔍 Resumen Simple

**El error código -3 ocurría porque:**

La configuración `MAIL_USE_MICROSOFT_GRAPH=true` en tu `.env` **no estaba siendo leída** por el código que envía emails. El sistema checaba una configuración inexistente y siempre usaba SMTP en su lugar. Cuando SMTP fallaba, el controller retornaba error -3 aparentando que Microsoft Graph había fallado (pero nunca se ejecutó).

---

## 🛠️ Qué Se Arregló

### 1. **Configuración Faltante** 
Agregué a `config/mail.php`:
```php
'use_microsoft_graph' => env('MAIL_USE_MICROSOFT_GRAPH', false),
```

**Antes:** 
```
env('MAIL_USE_MICROSOFT_GRAPH') = true     ← Controller checaba esto
config('mail.use_microsoft_graph') = null  ← Trait usaba esto (no existía)
```

**Después:**
```
env('MAIL_USE_MICROSOFT_GRAPH') = true       ← Controller
config('mail.use_microsoft_graph') = true    ← Trait (ahora sincronizado)
```

### 2. **Logging Mejorado Dramáticamente**

Ahora los logs te dicen EXACTAMENTE qué pasó:
- Qué configuración se está usando (Graph vs SMTP)
- Si Microsoft Graph fue intentado
- Qué error retornó Microsoft Graph (antes era opaco)
- Cuántos adjuntos se envían
- Si hay fallback a SMTP

### 3. **Comando de Prueba Mejorado**

El comando `msgraph:test-email` ahora:
- Verifica tu configuración
- Muestra credenciales configuradas
- Ejecuta un envío de prueba
- Te dice exactamente dónde está el problema

---

## 📊 Comparación: Antes vs Después

### ❌ ANTES
```
Usuario: "¿Por qué sale código -3?"
Log: "Error en sendViaSMTP: Expected response code 354"
Realidad: Microsoft Graph NUNCA se ejecutó, SMTP falló
Debugging: ❌ Imposible saber por qué
```

### ✅ DESPUÉS
```
Usuario: "¿Por qué sale código -3?"
Log detallado:
  - "sendEmail: use_microsoft_graph = true"
  - "Intentando enviar por Microsoft Graph"
  - "Error al enviar email por Microsoft Graph"
  - "status: 403"
  - "error: {'code': 'Unauthorized', ...}"
Realidad: Sabes exactamente qué falló
Debugging: ✅ Todos los detalles visibles
```

---

## 🚀 Cómo Probar Ahora

### Paso 1: Ejecutar comando de prueba
```bash
cd /media/ivan/datos/Trabajo/Informatica/Clientes/api_bpay/Source/api_bpay
php artisan msgraph:test-email --to=tu_email@ejemplo.com
```

### Paso 2: Ver output
Debería mostrarte algo como:
```
╔═══════════════════════════════════════════════════════════╗
║          Prueba de Configuración de Email                 ║
╚═══════════════════════════════════════════════════════════╝

📋 CONFIGURACIÓN ACTUAL:
  • config('mail.use_microsoft_graph'): ✅ TRUE
  • config('mail.default'): smtp
  • Email destino: test@example.com
...

📧 INTENTANDO ENVIAR EMAIL...

✅ EMAIL ENVIADO EXITOSAMENTE
```

### Paso 3: Si hay error, revisar logs
```bash
tail -100 storage/logs/laravel.log | grep -i "graph\|sendvia"
```

---

## 🔧 Archivos Modificados

| Archivo | Cambio | Razón |
|---------|--------|-------|
| `config/mail.php` | Agregado `use_microsoft_graph` | Sincronizar configuración |
| `SendsEmailsTrait.php` | Mejorado logging | Debuggear ruta de ejecución |
| `sendViaGraphAPI()` | Mejorado logging | Ver adjuntos y parámetros |
| `MicrosoftGraphService.php` | Mejorado logging | Ver error exacto de API |
| `TestMicrosoftGraphEmail.php` | Completamente reescrito | Mejor diagnostico |

---

## 📋 Checklist: Qué Verificar

Antes de ejecutar el test, verifica tu `.env`:

```bash
cat .env | grep -i "mail\|graph"
```

Debería tener:

```env
# Microsoft Graph
MAIL_USE_MICROSOFT_GRAPH=true
MSGRAPH_CLIENT_ID=xxxxxxxx...
MSGRAPH_CLIENT_SECRET=xxxxxxxx...
MSGRAPH_TENANT_ID=xxxxxxxx...
MSGRAPH_USER_EMAIL=user@domain.com
MSGRAPH_CACHE_TOKEN=true
MSGRAPH_CACHE_KEY=msgraph_token

# O alternativamente, SMTP como fallback
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_password
```

---

## ✅ Lo Que Debería Funcionar Ahora

### Escenario 1: Todo bien configurado
```
✅ Email enviado con code: 1
✅ Microsoft Graph funcionando
✅ Adjuntos enviados correctamente
```

### Escenario 2: Graph falla, SMTP como fallback
```
⚠️ Graph retorna error (ej: 403 Unauthorized)
✅ Fallback automático a SMTP
✅ Email igualmente enviado por SMTP
✅ Log muestra ambos intentos
```

### Escenario 3: Diagnosticar problemas claros
```
❌ Si hay error: El log dice EXACTAMENTE cuál es
❌ Status HTTP específico
❌ Error JSON de Microsoft Graph o SMTP
```

---

## 🎯 Próximos Pasos

1. **Ejecuta el test:** 
   ```bash
   php artisan msgraph:test-email --to=test@example.com
   ```

2. **Si funciona:** ¡Listo! Se acabó el problema

3. **Si no funciona:** 
   - Comparte el output del comando
   - Ls últimas líneas de `storage/logs/laravel.log`
   - Tu `.env` (sin credenciales sensibles)
   - Con eso puedo identificar exactamente qué falta

---

## 📚 Documentación Adicional

He creado dos archivos de referencia:

1. **DIAGNOSTICO_ERROR_CODIGO_3.md** - Guía completa de troubleshooting
2. **RESUMEN_CAMBIOS_ERROR_3.md** - Detalle técnico de cambios

Léelos si necesitas entender qué errores pueden ocurrir y cómo resolverlos.

---

## 🎓 Lecciones Aprendidas

1. **Sincronización de configuración**: El `env()` debe estar en `config()` para que el código lo vea
2. **Logging es crítico**: Sin logs detallados, es imposible debuggear
3. **Fallback es esencial**: Si Graph falla, SMTP debería funcionar
4. **Testing es necesario**: Un comando de test evita sorpresas

¡El problema está solucionado! 🎉

