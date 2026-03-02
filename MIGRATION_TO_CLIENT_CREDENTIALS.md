# 🔄 Migración de OAuth Authorization Code a Client Credentials

## Resumen de Cambios

Se ha actualizado la implementación de Microsoft Graph de **OAuth Authorization Code** (requiere redirección y refresh tokens) a **Client Credentials Flow** (sin redirección, completamente automatizado).

### ✅ Ventajas del Client Credentials Flow

- ✅ **SIN redirección**: No necesitas credenciales de usuario ni autenticación interactiva
- ✅ **Totalmente automatizado**: El token se obtiene automáticamente cada vez
- ✅ **Más seguro para servicios backend**: Ideal para APIs que envían emails automáticamente
- ✅ **Más simple**: Una sola llamada al endpoint de token sin gestión de `refresh_token`
- ✅ **Escalable**: Funciona sin interacción del usuario

---

## 📝 Archivos Modificados

### 1. **config/msgraph.php**
- ❌ Removido: `'redirect_uri' => env('MSGRAPH_REDIRECT_URI')`
- ✅ Actualizado: Comentario indicando Client Credentials Flow
- Ahora solo necesita: `client_id`, `client_secret`, `tenant_id`, `user_email`

### 2. **app/Services/MicrosoftGraphService.php**
**Cambios principales:**

```php
// ❌ ANTES: Usaba GenericProvider con OAuth
use League\OAuth2\Client\Provider\GenericProvider;

// ✅ AHORA: Obtiene token directamente vía HTTP
protected function getAccessToken()
{
    // Usar Client Credentials Flow
    $response = Http::asForm()->post(
        $this->authority . '/' . $this->tenantId . '/oauth2/v2.0/token',
        [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',  // ← Cliente, no usuario
        ]
    );
}
```

**Métodos removidos:**
- ❌ `saveRefreshToken()` - Ya no necesario
- ❌ `getAuthorizationUrl()` - Sin OAuth redirect
- ❌ `$this->provider` - Eliminado GenericProvider

**Métodos actualizados:**
- ✅ `sendEmail()` - Ahora acepta parámetros `$fromEmail` y `$fromName` para remitente personalizado
- ✅ `getAccessToken()` - Ahora usa solo Client Credentials

### 3. **app/Traits/SendsEmailsTrait.php**
**Nuevos parámetros:**

```php
// ANTES
protected function sendEmail($to, Mailable $mailable, $attachments = [])

// AHORA
protected function sendEmail($to, Mailable $mailable, $attachments = [], $fromEmail = null, $fromName = null)
{
    // Pasa los parámetros a sendViaGraphAPI y sendViaSMTP
}
```

### 4. **.env**
**Cambios:**

```
# ❌ REMOVIDO
MSGRAPH_REDIRECT_URI=http://localhost:8002/api/msgraph/auth/callback

# ✅ ACTUALIZADO - Comentario reflejando Client Credentials
# MICROSOFT GRAPH API CONFIGURATION FOR SENDING EMAILS (Client Credentials Flow)
# No requiere autenticación OAuth ni redirección
```

---

## 🚀 Cómo Usar el Nuevo Flujo

### Caso 1: Enviar email simple (sin remitente personalizado)

```php
$mailable = new NotificacionEmailEncuestaAtencion('Asunto', $data);
$this->sendEmail($trimemails, $mailable);
```

### Caso 2: Enviar email con remitente personalizado (LEAL MEDICA)

```php
if(env('COMPANY_NAME') == 'LEAL MEDICA' && $n_empresa != null) {
    $fromAddress = config('mail.from.address');
    $fromName = $n_empresa;
    
    $mailable = new NotificacionEmailEncuestaAtencion('Asunto', $data);
    
    // Con remitente personalizado
    $this->sendEmail($trimemails, $mailable, [], $fromAddress, $fromName);
}
```

### Ejemplo Real (EmailsEncuestasController.php)

```php
if(env('COMPANY_NAME') == 'LEAL MEDICA'){
    if ($n_empresa != null) {
        $fromAddress = config('mail.from.address');
        $fromName = $n_empresa;

        if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
            $mailable = new NotificacionEmailEncuestaAtencion('Experiencia con tu consulta', $data);
            // Ahora pasa email y nombre como parámetros
            $resultado = $this->sendEmail($trimemails, $mailable, [], $fromAddress, $fromName);
            
            if ($resultado) {
                $message = 'Email enviado con Microsoft Graph';
            }
        } else {
            // SMTP como fallback
            $mailable = new NotificacionEmailEncuestaAtencion('Experiencia con tu consulta', $data);
            $mailable->from($fromAddress, $fromName);
            Mail::to($trimemails)->send($mailable);
        }
    }
}
```

---

## ⚙️ Configuración Requerida en Azure

Para que Client Credentials Flow funcione, tu aplicación Azure debe tener:

1. **Client ID**: `MSGRAPH_CLIENT_ID`
2. **Client Secret**: `MSGRAPH_CLIENT_SECRET` (ya tienes)
3. **Tenant ID**: `MSGRAPH_TENANT_ID`
4. **Permisos de API**: 
   - `Mail.Send` (con consentimiento de admin)
   - Tipo: **Permisos de aplicación** (no delegados)

### Verificar en Azure Portal:

```
Azure Portal 
→ App Registrations 
→ Tu Aplicación
→ API Permissions
→ ✓ Mail.Send (Application permission)
→ Grant admin consent
```

---

## 🧪 Pruebas

### Activar Microsoft Graph en .env

```env
MAIL_USE_MICROSOFT_GRAPH=true
```

### Enviar email de prueba

```bash
php artisan msgraph:test-email --to=usuario@ejemplo.com
```

### Ver logs

```bash
tail -f storage/logs/laravel.log | grep "Microsoft Graph"
```

### Rollback a SMTP si hay problemas

```env
MAIL_USE_MICROSOFT_GRAPH=false
```

---

## 🔐 Seguridad

### ✅ Lo que está mejor:

- **Sin tokens de usuario**: No almacenas credenciales de usuarios reales
- **Scope limitado**: Solo puede enviar emails (`Mail.Send`)
- **Expiración automática**: Tokens expiran y se renuevan automáticamente
- **Caché de tokens**: Reutiliza tokens válidos en caché

### ⚠️ Consideraciones de seguridad:

1. **Client Secret**: Es crítico, debe estar solo en `.env` (nunca commitear)
2. **Azure Permisos**: Otorga solo lo necesario (`Mail.Send`)
3. **Logs**: No loguea el `client_secret` pero sí registra envíos

---

## 📊 Flujo de Autenticación

### Antes (OAuth Authorization Code)
```
1. Usuario hace login en Azure
2. Azure devuelve authorization code
3. App usa code para obtener access_token + refresh_token
4. App guarda refresh_token en caché
5. Cuando expira access_token, usa refresh_token para renovar
```

### Ahora (Client Credentials)
```
1. App envía: client_id + client_secret + scope
2. Azure devuelve: access_token + expires_in
3. App guarda token en caché (5 minutos antes de expiración)
4. Cuando expira, se obtiene uno nuevo automáticamente
```

---

## 🎯 Próximos Pasos

1. ✅ Actualizar todos los controllers que usan `sendEmail()` para pasar remitente si es necesario
2. ✅ Probar con `MAIL_USE_MICROSOFT_GRAPH=true` en desarrollo
3. ✅ Validar logs para asegurar envíos correctos
4. ✅ Monitorear fallbacks a SMTP
5. ✅ Una vez validado, cambiar a `true` en producción

---

## 📚 Referencias

- [Microsoft Graph - Send Mail](https://learn.microsoft.com/en-us/graph/api/user-sendmail)
- [OAuth 2.0 Client Credentials Flow](https://learn.microsoft.com/en-us/azure/active-directory/develop/v2-oauth2-client-creds-grant-flow)
- [Microsoft Identity Platform](https://learn.microsoft.com/en-us/azure/active-directory/develop/)
