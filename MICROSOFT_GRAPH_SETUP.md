# 📧 Guía de Integración de Microsoft Graph para envío de emails

## 1. Requisitos Previos

### En Azure Portal (Microsoft Entra ID)
- Acceso al portal: https://portal.azure.com
- Permisos para registrar aplicaciones

## 2. Configuración en Azure

### Paso A: Registrar la Aplicación

1. Ve a **Azure Portal** → **Azure Entra ID** (o **Directorio Activo**)
2. En el menú izquierdo, selecciona **Registros de aplicaciones**
3. Haz clic en **Nuevo registro**
4. Completa:
   - **Nombre**: `BPay Email Service` (o similar)
   - **Tipos de cuenta admitidos**: `Solo cuentas de esta organización`
5. Haz clic en **Registrar**

### Paso B: Obtener Client ID

1. En la página del registro, localiza y copia:
   - **Application (client) ID** → **ID_DEL_CLIENTE**
   - **Directory (tenant) ID** → **TENANT_ID**

### Paso C: Crear Cliente Secreto

1. En el menú izquierdo, ve a **Certificados y secretos**
2. Haz clic en **Nuevo secreto de cliente**
3. Completa:
   - **Descripción**: `BPay Email Service Secret`
   - **Expira en**: Selecciona un periodo (ej: 24 meses)
4. Copia el **Valor** → **SECRETO_DEL_CLIENTE** (⚠️ solo visible una vez)

### Paso D: Configurar Permisos de API

1. En el menú izquierdo, ve a **Permisos de API**
2. Haz clic en **Agregar una permiso**
3. Selecciona **Microsoft Graph**
4. Busca y agrega estos permisos:
   - `Mail.Send` (Permiso delegado)
   - `offline_access` (Permiso delegado)
5. Haz clic en **Agregar permisos**
6. Si lo solicita, haz clic en **Otorgar consentimiento como administrador para [tu empresa]**

### Paso E: Configurar Redirect URI

1. En el menú izquierdo, ve a **Autenticación**
2. En **URI de redirección**, haz clic en **Agregar URI**
3. Añade:
   ```
   http://tuaplicacion.com/api/msgraph/auth/callback
   ```
   (Reemplaza `tuaplicacion.com` con tu dominio real)
4. Haz clic en **Guardar**

## 3. Configuración en el Proyecto Laravel

### Paso 1: Configurar Variables de Entorno

Edita tu archivo `.env`:

```env
# Microsoft Graph Configuration
MAIL_USE_MICROSOFT_GRAPH=false
MSGRAPH_CLIENT_ID=aqui-tu-client-id
MSGRAPH_CLIENT_SECRET=aqui-tu-secret
MSGRAPH_TENANT_ID=aqui-tu-tenant-id
MSGRAPH_USER_EMAIL=tu-email@tuempresa.onmicrosoft.com
MSGRAPH_REDIRECT_URI=http://tuaplicacion.com/api/msgraph/auth/callback
```

### Paso 2: Instalar Dependencias

Ya se han instalado en los pasos anteriores:
- `league/oauth2-client` ✓

### Paso 3: Ejecutar las Migraciones de Cache (si es necesario)

```bash
php artisan cache:table
php artisan migrate
```

## 4. Autenticación con Microsoft

### Flujo Automático (Recomendado)

1. Abre en tu navegador:
   ```
   http://tuaplicacion.com/api/msgraph/auth/redirect
   ```

2. Se te redirigirá a Microsoft para autenticarse

3. Autoriza los permisos solicitados

4. Serás redirigido de vuelta a tu aplicación

5. Los tokens se guardarán automáticamente en caché

## 5. Usar el Servicio en tus Controllers

### Opción 1: Usar el Trait en Controllers (RECOMENDADO)

```php
<?php

namespace App\Http\Controllers;

use App\Traits\SendsEmailsTrait;
use App\Mail\NotificacionEmailRegistroUsuarioAfiliado;

class MiController extends Controller
{
    use SendsEmailsTrait;

    public function enviarEmail()
    {
        $email = 'usuario@ejemplo.com';
        $mailable = new NotificacionEmailRegistroUsuarioAfiliado(
            'Bienvenido',
            ['nombre' => 'Juan', 'email' => $email]
        );

        // Envía automáticamente con fallback
        $resultado = $this->sendEmail($email, $mailable);

        if ($resultado) {
            return response()->json(['mensaje' => 'Email enviado']);
        }
    }
}
```

### Opción 2: Usar directamente en una clase

```php
<?php

use App\Traits\SendsEmailsTrait;

class MiClase
{
    use SendsEmailsTrait;

    public function hacer()
    {
        $this->sendEmail($email, $mailable);
    }
}
```

### Opción 3: Usar el Servicio manualmente

```php
<?php

use App\Services\EmailService;

class MiController
{
    public function enviarEmail()
    {
        $emailService = app('email.service');
        $emailService->send($email, $mailable);
    }
}
```

## 6. Pruebas

### Prueba desde Terminal

```bash
php artisan msgraph:test-email --to=usuario@ejemplo.com
```

### Prueba con SMTP (fallback)

```bash
php artisan msgraph:test-email --to=usuario@ejemplo.com --use-smtp
```

## 7. Migración de Código Existente

Para cada `Mail::to()` existente:

**Antes:**
```php
Mail::to($email)->send(new NotificacionEmailRegistroUsuarioAfiliado($asunto, $datos));
```

**Después (opción 1 - Recomendada con Trait):**
```php
// En el controller
use App\Traits\SendsEmailsTrait;

class MiController extends Controller {
    use SendsEmailsTrait;
    
    public function metodo() {
        $this->sendEmail($email, new NotificacionEmailRegistroUsuarioAfiliado($asunto, $datos));
    }
}
```

**Después (opción 2 - Sin cambios en controllers):**
```php
// Crear un Facade personalizado y usarlo igual que antes
// (implementación adicional si lo prefieres)
```

## 8. Cambiar entre Drivers

Para cambiar entre Microsoft Graph y SMTP:

```env
# Usar Microsoft Graph
MAIL_USE_MICROSOFT_GRAPH=true

# Usar SMTP
MAIL_USE_MICROSOFT_GRAPH=false
```

## 9. Monitoreo y Logs

Los errores se registran en:
```
storage/logs/laravel.log
```

Busca logs con:
```bash
tail -f storage/logs/laravel.log | grep "Email"
```

## 10. Troubleshooting

### Error: "No refresh token available"

**Solución**: Ejecuta el flujo de autenticación:
```
http://tuaplicacion.com/api/msgraph/auth/redirect
```

### Error: "Token expired"

**Solución**: El sistema renueva automáticamente, pero puedes ejecutar:
```bash
php artisan cache:clear
```

### Email no se envía

**Pasos**:
1. Verifica que `MAIL_USE_MICROSOFT_GRAPH=true` en `.env`
2. Revisa los logs: `tail -f storage/logs/laravel.log`
3. Prueba con SMTP: `MAIL_USE_MICROSOFT_GRAPH=false`

### Permisos insuficientes

**Solución**: En Azure Portal, ve a **Permisos de API** y haz clic en **Otorgar consentimiento**

## 11. Próximos Pasos

1. ✅ Completar configuración de Azure
2. ✅ Configurar variables `.env`
3. ✅ Ejecutar autenticación OAuth
4. ✅ Prueba con comando `msgraph:test-email`
5. ✅ Reemplazar `Mail::to()` gradualmente en los controllers
6. ✅ Hacer pruebas en ambiente de staging
7. ✅ Activar en producción

## 12. Referencia Rápida

| Concepto | Ubicación |
|----------|-----------|
| Configuración | `config/msgraph.php` |
| Servicio | `app/Services/MicrosoftGraphService.php` |
| Service Wrapper | `app/Services/EmailService.php` |
| Trait Reutilizable | `app/Traits/SendsEmailsTrait.php` |
| Auth Controller | `app/Http/Controllers/MicrosoftGraphAuthController.php` |
| Comando Prueba | `app/Console/Commands/TestMicrosoftGraphEmail.php` |
| Rutas | `routes/api.php` |

---

**Fecha de creación**: 10 de febrero de 2026
**Versión**: 1.0
