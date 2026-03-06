# 📊 Comparación: OAuth vs Client Credentials

## 🔄 Arquitectura Antigua (OAuth Authorization Code)

```
┌─────────────────────────────────────────────────────────────────┐
│                    Tu Aplicación                                │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                    1. Redirige a Azure
                           │
                    /api/msgraph/auth/redirect
                           │
         ┌─────────────────▼──────────────────┐
         │     Azure Login (Usuario)          │
         │  (Requiere credenciales usuario)   │
         └────────────┬──────────────────────┘
                      │
          2. Usuario autoriza la app
                      │
         ┌────────────▼──────────────────────┐
         │   Azure devuelve:                 │
         │   - authorization_code            │
         │   - access_token                  │
         │   - refresh_token ⭐              │
         └────────────┬──────────────────────┘
                      │
          3. callback URL
                      │
        /api/msgraph/auth/callback
                      │
         ┌────────────▼──────────────────────┐
         │  Guardar en Cache:                │
         │  - access_token (expira en 1h)    │
         │  - refresh_token (indefinido)     │
         └────────────┬──────────────────────┘
                      │
         4. Cuando access_token expira
                      │
         ┌────────────▼──────────────────────┐
         │  Usar refresh_token para          │
         │  obtener nuevo access_token       │
         └───────────────────────────────────┘

❌ PROBLEMAS:
- Requiere autenticación del usuario
- Redirección +1 paso manual
- Necesita guardar y gestionar refresh_token
- Complejo para servicios backend
- Si el usuario revoca, deja de funcionar
```

---

## ✨ Arquitectura Nueva (Client Credentials Flow)

```
┌──────────────────────────────────────────────────────────────────┐
│                    Tu Aplicación                                 │
└────────────────────────────┬─────────────────────────────────────┘
                             │
                   1. getAccessToken()
                             │
        ┌────────────────────▼─────────────────────┐
        │        Solicita Token a Azure            │
        │  POST /oauth2/v2.0/token con:            │
        │  - client_id                             │
        │  - client_secret                         │
        │  - grant_type=client_credentials         │
        │  - scope=https://graph.microsoft.com/... │
        │                                          │
        │        ⭐ SIN usuario, totalmente         │
        │           automatizado                   │
        └────────────────────┬─────────────────────┘
                             │
        ┌────────────────────▼─────────────────────┐
        │   Azure devuelve:                        │
        │   - access_token (expira en 1h)          │
        │   - expires_in (3600 segundos)           │
        │                                          │
        │   ❌ NO refresh_token                    │
        └────────────────────┬─────────────────────┘
                             │
        ┌────────────────────▼─────────────────────┐
        │   Guardar en Cache:                      │
        │   - Token válido por 1 hora              │
        │   - Caché automático (5 min margen)      │
        │                                          │
        │   Cuando vaya a expirar:                 │
        │   → Obtener nuevo token automáticamente  │
        └────────────────────┬─────────────────────┘
                             │
           2. Enviar email a Microsoft Graph
                             │
        ┌────────────────────▼─────────────────────┐
        │   POST /me/sendMail con:                 │
        │   Authorization: Bearer {access_token}   │
        │                                          │
        │   Email enviado ✓                        │
        └──────────────────────────────────────────┘

✅ VENTAJAS:
- SIN autenticación de usuario
- SIN redirección
- Completamente automatizado
- Perfecto para servicios backend
- Renovación automática de tokens
- Una única configuración en .env
```

---

## 📋 Tabla Comparativa

| Aspecto | OAuth Authorization Code | Client Credentials |
|--------|--------------------------|-------------------|
| **Autenticación** | Del usuario final | De la aplicación |
| **Redirección** | ✓ Requiere | ✗ NO requiere |
| **Tokens** | access_token + refresh_token | Solo access_token |
| **Renovación** | refresh_token manual o automática | Automática cada hora |
| **Configuración .env** | 4 variables + redirect_uri | 4 variables |
| **Rutas API** | /auth/redirect, /auth/callback | ✗ NO necesario |
| **Seguridad** | ⚠️ Usa credenciales usuario | ✓ Usa credenciales app |
| **Ideal para** | Apps de usuario + API | APIs backend/servicios |
| **Complejidad** | Media/Alta | Baja |
| **Mantenimiento** | Mayor | Menor |

---

## 🔧 Comparación de Código

### Autorización Antigua

```php
// ❌ Necesitaba GenericProvider y flujo OAuth
$this->provider = new GenericProvider([
    'clientId' => config('msgraph.client_id'),
    'clientSecret' => config('msgraph.client_secret'),
    'redirectUri' => config('msgraph.redirect_uri'), // 👈 Necesario
    'urlAuthorize' => config('msgraph.authority') . '/' . config('msgraph.tenant_id') . '/oauth2/v2.0/authorize',
    'urlAccessToken' => config('msgraph.authority') . '/' . config('msgraph.tenant_id') . '/oauth2/v2.0/token',
    'scopes' => ['Mail.Send', 'offline_access'],
]);

// En la solicitud había que gestionar refresh_token
if ($refreshToken) {
    $token = $this->provider->getAccessToken('refresh_token', [
        'refresh_token' => $refreshToken
    ]);
}
```

### Autorización Nueva

```php
// ✅ Simple - Solo HTTP POST
$response = Http::asForm()->post(
    'https://login.microsoftonline.com/{tenantId}/oauth2/v2.0/token',
    [
        'client_id' => config('msgraph.client_id'),
        'client_secret' => config('msgraph.client_secret'),
        'scope' => 'https://graph.microsoft.com/.default',
        'grant_type' => 'client_credentials', // 👈 Sin usuario
    ]
);

$token = $response->json()['access_token'];
// ✓ Token obtenido, listo para usar
```

---

## 🎯 Caso de Uso: Enviar Email LEAL MEDICA

### Antes (OAuth)
```php
// 1. Requería que el admin autenticara la app via /api/msgraph/auth/redirect
// 2. Guardaba refresh_token en caché
// 3. Cada hora, renovaba token con refresh_token
// 4. Finalmente enviaba el email

if(env('COMPANY_NAME') == 'LEAL MEDICA' && $n_empresa != null){
    $fromAddress = config('mail.from.address');
    $fromName = $n_empresa;
    
    $email = new NotificacionEmailEncuestaAtencion('Asunto', $data);
    // ❌ No había manera de cambiar remitente dinámicamente
    Mail::from($fromAddress, $fromName)
        ->to($trimemails)
        ->send($email);
}
```

### Ahora (Client Credentials)
```php
// 1. Automático - El token se obtiene cuando se necesita
// 2. Sin intervención manual
// 3. Remitente dinámico soportado
// 4. Email enviado

if(env('COMPANY_NAME') == 'LEAL MEDICA' && $n_empresa != null){
    $fromAddress = config('mail.from.address');
    $fromName = $n_empresa;
    
    if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
        $mailable = new NotificacionEmailEncuestaAtencion('Asunto', $data);
        // ✅ Pasamos remitente dinámico directamente
        $this->sendEmail($trimemails, $mailable, [], $fromAddress, $fromName);
    } else {
        // Fallback SMTP
        $mailable = new NotificacionEmailEncuestaAtencion('Asunto', $data);
        $mailable->from($fromAddress, $fromName);
        Mail::to($trimemails)->send($mailable);
    }
}
```

---

## 🚀 Flujo de Ejecución

### Enviar Email con Client Credentials

```
1. Controller llama:
   $this->sendEmail($to, $mailable, [], $fromEmail, $fromName)
                              ↓
2. SendsEmailsTrait.sendEmail():
   - Detecta MAIL_USE_MICROSOFT_GRAPH=true
   - Llama sendViaGraphAPI($to, $mailable, [], $fromEmail, $fromName)
                              ↓
3. SendsEmailsTrait.sendViaGraphAPI():
   - Extrae subject, body, cc, bcc del Mailable
   - Llama graphService->sendEmail(..., $fromEmail, $fromName)
                              ↓
4. MicrosoftGraphService.sendEmail():
   - ✓ Si fromEmail está set: usa ese remitente
   - ✗ Si no: usa MSGRAPH_USER_EMAIL del .env
   - Construye el mensaje con email y nombre personalizados
   - Envía a /me/sendMail de Microsoft Graph
                              ↓
5. Microsoft Graph API:
   - Recibe el mensaje con remitente personalizado
   - Valida usando el access_token
   - ✓ Envía el email exitosamente
                              ↓
6. EmailsEncuestasController:
   - Recibe $resultado = true
   - Registra envío: "Email enviado con Microsoft Graph"
   - Registra en base de datos
   - Devuelve respuesta al cliente
```

---

## 📚 Documentación Oficial

- [OAuth 2.0 Client Credentials Flow](https://learn.microsoft.com/en-us/azure/active-directory/develop/v2-oauth2-client-creds-grant-flow)
- [Microsoft Graph - Send Mail](https://learn.microsoft.com/en-us/graph/api/user-sendmail)
- [Azure AD Permissions and Consent](https://learn.microsoft.com/en-us/azure/active-directory/develop/v2-permissions-and-consent)

---

## ✅ Checklist de Migración

- [x] Configuración actualizada (config/msgraph.php)
- [x] Servicio actualizado (MicrosoftGraphService.php)
- [x] Trait actualizado (SendsEmailsTrait.php)
- [x] Variables .env limpiadas
- [x] Rutas OAuth deshabilitadas
- [x] Soporte para remitente dinámico
- [x] Documentación de migración creada
- [ ] Pruebas en desarrollo (MAIL_USE_MICROSOFT_GRAPH=true)
- [ ] Validación en producción
- [ ] Remover MicrosoftGraphAuthController.php cuando ya no sea necesario
