# 🚀 GUÍA RÁPIDA - Microsoft Graph API para Emails

## ⚡ En 5 minutos

### 1️⃣ Copiar variables de entorno
```bash
# En tu .env, agregar:
MAIL_USE_MICROSOFT_GRAPH=false
MSGRAPH_CLIENT_ID=tu_client_id_aqui
MSGRAPH_CLIENT_SECRET=tu_secret_aqui
MSGRAPH_TENANT_ID=tu_tenant_id
MSGRAPH_USER_EMAIL=notificaciones@empresa.onmicrosoft.com
MSGRAPH_REDIRECT_URI=http://localhost:8000/api/msgraph/auth/callback
```

### 2️⃣ Autenticarse con Microsoft
```
Abrir navegador:
http://localhost:8000/api/msgraph/auth/redirect

→ Será redirigido a Microsoft
→ Iniciar sesión
→ Autorizar permisos
→ Volver a la app (tokens guardados automáticamente)
```

### 3️⃣ Probar
```bash
php artisan msgraph:test-email --to=tu@email.com
```

## 📖 En 1 Controller

### Antes (Mail::to)
```php
<?php

namespace App\Http\Controllers;

use App\Mail\NotificacionEmailRegistroUsuarioAfiliado;
use Illuminate\Support\Facades\Mail;

class MiController extends Controller
{
    public function registrar()
    {
        $email = 'usuario@ejemplo.com';
        $mailable = new NotificacionEmailRegistroUsuarioAfiliado('Bienvenida', []);
        
        // ❌ Forma antigua
        Mail::to($email)->send($mailable);
    }
}
```

### Después (Trait con fallback)
```php
<?php

namespace App\Http\Controllers;

use App\Mail\NotificacionEmailRegistroUsuarioAfiliado;
use App\Traits\SendsEmailsTrait;

class MiController extends Controller
{
    use SendsEmailsTrait;  // ← Agregar esto
    
    public function registrar()
    {
        $email = 'usuario@ejemplo.com';
        $mailable = new NotificacionEmailRegistroUsuarioAfiliado('Bienvenida', []);
        
        // ✅ Forma nueva (usa Graph si está habilitado, SMTP si no)
        $this->sendEmail($email, $mailable);
    }
}
```

## 📚 Métodos Disponibles

```php
// Enviar email (Graph con fallback a SMTP)
$this->sendEmail($to, $mailable, $attachments = [])

// Enviar solo HTML
$this->sendQuickEmail($to, $subject, $html, $attachments = [])

// Acceder al servicio de Graph directamente
$this->emailService->getMicrosoftGraphService()
```

## 🔧 Configuración por Ambiente

### Desarrollo (usa SMTP)
```env
MAIL_USE_MICROSOFT_GRAPH=false
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=xxx
MAIL_PASSWORD=xxx
```

### Producción (usa Microsoft Graph)
```env
MAIL_USE_MICROSOFT_GRAPH=true
MSGRAPH_CLIENT_ID=xxx
MSGRAPH_CLIENT_SECRET=xxx
# SMTP como fallback
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
...
```

## 🧪 Pruebas Rápidas

```bash
# Test con Graph
php artisan msgraph:test-email --to=admin@empresa.com

# Test con SMTP
php artisan msgraph:test-email --to=admin@empresa.com --use-smtp

# Ver logs
tail -f storage/logs/laravel.log | grep Email

# Limpiar caché de tokens
php artisan cache:clear
```

## 🔑 Obtener Credenciales de Azure

### Client ID y Tenant ID
1. Azure Portal → Entra ID → Registros de aplicaciones → Tu app
2. Copiar: "Application (client) ID" y "Directory (tenant) ID"

### Secret
1. Certificados y secretos → Nuevo secreto de cliente
2. ⚠️ Copia el VALOR inmediatamente (solo se muestra una vez)

### Permisos
1. Permisos de API → Agregar permiso → Microsoft Graph
2. Agregar: `Mail.Send` y `offline_access`
3. Otorgar consentimiento de administrador

### Redirect URI
1. Autenticación → Agregar plataforma → Aplicación web
2. Agregar: `http://localhost:8000/api/msgraph/auth/callback`

## 🎯 Flujo Visual

```
Tu Controlador
    ↓
SendsEmailsTrait (tu trait)
    ↓
¿MAIL_USE_MICROSOFT_GRAPH?
    ├→ YES: Usar Microsoft Graph API
    │        ├→ ¿Token válido? NO → Renovar
    │        ├→ ¿Email enviado? SI → ✅
    │        └→ ¿Error? → FALLBACK a SMTP
    │
    └→ NO: Usar SMTP directo
             ├→ ¿Email enviado? SI → ✅
             └→ ¿Error? → ❌ Log error
```

## 🆘 Problemas Comunes

| Problema | Solución |
|----------|----------|
| No refresh token | Ve a `/api/msgraph/auth/redirect` |
| Token expirado | Sistema auto-renueva, sino: `cache:clear` |
| Email no se envía | Verifica `MAIL_USE_MICROSOFT_GRAPH` en `.env` |
| Credenciales inválidas | Verifica Client ID, Secret, Tenant ID |
| Permisos insuficientes | Azure: Permisos API → Otorgar consentimiento |

## 📁 Archivos Clave

```
config/msgraph.php                          ← Configuración
app/Services/MicrosoftGraphService.php       ← Servicio principal
app/Services/EmailService.php                ← Wrapper
app/Traits/SendsEmailsTrait.php              ← Trait para Controllers
app/Http/Controllers/MicrosoftGraphAuthController.php  ← OAuth
MICROSOFT_GRAPH_SETUP.md                    ← Documentación completa
```

## 🔐 Seguridad

✅ Tokens se cachean (no en sesión)
✅ Secreto no se expone en respuestas
✅ CSRF protegido en OAuth
✅ Fallback automático si falla

## 🚨 Monitoreo

Todos los emails se registran en: `storage/logs/laravel.log`

Buscar errores:
```bash
grep -i "email\|graph\|mail" storage/logs/laravel.log
```

## ✨ Ventajas

✅ Cambiar entre drivers con 1 línea en `.env`
✅ Sin cambios en clases Mailable
✅ Fallback automático a SMTP
✅ Reutilizable en toda la app
✅ Logging completo

## 📞 Soporte Rápido

**Pregunta**: ¿Los emails se envían?
**Verificar**:
1. `MAIL_USE_MICROSOFT_GRAPH` value → logs
2. ¿Token válido? → `php artisan cache:clear`
3. ¿SMTP funciona? → Cambiar a `false` en `.env`

---

**Versión**: 1.0  
**Fecha**: 10 de febrero de 2026  
**Estado**: Listo para usar
