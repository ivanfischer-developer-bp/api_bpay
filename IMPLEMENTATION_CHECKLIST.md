# ✅ CHECKLIST DE IMPLEMENTACIÓN - Microsoft Graph API

## FASE 1: PREPARACIÓN ✓ (Completada)

- [x] Instalar `league/oauth2-client` mediante Composer
- [x] Crear `config/msgraph.php`
- [x] Crear `app/Services/MicrosoftGraphService.php`
- [x] Crear `app/Services/EmailService.php`
- [x] Crear `app/Services/EmailServiceFacade.php`
- [x] Crear `app/Traits/SendsEmailsTrait.php`
- [x] Crear `app/Http/Controllers/MicrosoftGraphAuthController.php`
- [x] Registrar servicio en `AppServiceProvider.php`
- [x] Agregar rutas en `routes/api.php`
- [x] Crear comando `TestMicrosoftGraphEmail.php`
- [x] Crear archivo de ejemplo `.env.msgraph.example`
- [x] Crear documentación `MICROSOFT_GRAPH_SETUP.md`
- [x] Crear este checklist

## FASE 2: CONFIGURACIÓN EN AZURE 🔷 (DEBES HACER ESTO)

### 2.1 Registrar Aplicación
- [ ] Acceder a https://portal.azure.com
- [ ] Navegar a "Azure Entra ID" o "Azure Active Directory"
- [ ] Seleccionar "Registros de aplicaciones"
- [ ] Hacer clic en "Nuevo registro"
- [ ] Ingresar nombre: `BPay Email Service`
- [ ] Seleccionar "Solo cuentas de esta organización"
- [ ] Hacer clic en "Registrar"
- [ ] Copiar y guardar **Application (client) ID** → `MSGRAPH_CLIENT_ID`
- [ ] Copiar y guardar **Directory (tenant) ID** → `MSGRAPH_TENANT_ID`

### 2.2 Crear Secreto de Cliente
- [ ] En la aplicación registrada, ir a "Certificados y secretos"
- [ ] Hacer clic en "Nuevo secreto de cliente"
- [ ] Ingresar descripción: `BPay Email Service Secret`
- [ ] Seleccionar expiración: `24 meses` (o según política)
- [ ] Hacer clic en "Agregar"
- [ ] **IMPORTANTE**: Copiar inmediatamente el **Valor** → `MSGRAPH_CLIENT_SECRET`
  - ⚠️ Solo se muestra una vez
  - 💾 Guárdalo en un lugar seguro

### 2.3 Configurar Permisos de API
- [ ] En la aplicación, ir a "Permisos de API"
- [ ] Hacer clic en "Agregar un permiso"
- [ ] Seleccionar "Microsoft Graph"
- [ ] Seleccionar "Permisos delegados"
- [ ] Buscar y seleccionar:
  - [ ] `Mail.Send`
  - [ ] `offline_access`
- [ ] Hacer clic en "Agregar permisos"
- [ ] Hacer clic en "Otorgar consentimiento de administrador para [tu empresa]"

### 2.4 Configurar Redirect URI
- [ ] En la aplicación, ir a "Autenticación"
- [ ] En "plataformas configuradas", hacer clic en "Agregar una plataforma"
- [ ] Seleccionar "Aplicación web"
- [ ] En "URI de redirección", ingresar:
  - Desarrollo: `http://localhost:8000/api/msgraph/auth/callback`
  - Producción: `https://tudominio.com/api/msgraph/auth/callback`
- [ ] Hacer clic en "Configurar"
- [ ] Hacer clic en "Guardar"

### 2.5 Verificar Usuario
- [ ] El usuario que usará los emails debe estar registrado en tu tenant
- [ ] Ejemplo: `notificaciones@tuempresa.onmicrosoft.com`
- [ ] Obtener el email del usuario → `MSGRAPH_USER_EMAIL`

## FASE 3: CONFIGURACIÓN LOCAL 💻

### 3.1 Variables de Entorno
- [ ] Abrir `.env` en la raíz del proyecto
- [ ] Agregar las siguientes variables:

```env
# Microsoft Graph Configuration
MAIL_USE_MICROSOFT_GRAPH=false
MSGRAPH_CLIENT_ID=<copiar desde Azure>
MSGRAPH_CLIENT_SECRET=<copiar desde Azure>
MSGRAPH_TENANT_ID=<copiar desde Azure>
MSGRAPH_USER_EMAIL=<email del usuario>
MSGRAPH_REDIRECT_URI=http://localhost:8000/api/msgraph/auth/callback
```

### 3.2 Verificar SMTP Fallback
- [ ] Asegurarse que MAIL_MAILER está configurado:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=xxxx
MAIL_PASSWORD=xxxx
MAIL_FROM_ADDRESS=noreply@empresa.com
MAIL_FROM_NAME=BPay
```

### 3.3 Limpiar Caché
- [ ] Ejecutar en terminal:
```bash
php artisan config:cache
php artisan cache:clear
```

## FASE 4: AUTENTICACIÓN OAUTH 🔑

### 4.1 Iniciar Flujo de Autenticación
- [ ] Abrir navegador
- [ ] Navegar a: `http://localhost:8000/api/msgraph/auth/redirect`
- [ ] Serás redirigido a Microsoft
- [ ] Iniciar sesión con tu cuenta de Azure
- [ ] Autorizar los permisos solicitados
- [ ] Serás redirigido de vuelta a la aplicación
- [ ] ✓ Los tokens serán guardados automáticamente

### 4.2 Verificar Caché de Tokens (Opcional)
- [ ] En terminal:
```bash
php artisan tinker
>>> Cache::get('msgraph_access_token')
```
- [ ] Deberías ver un array con:
  - `access_token`
  - `refresh_token`
  - `expires`

## FASE 5: PRUEBAS 🧪

### 5.1 Prueba de Email
- [ ] Ejecutar en terminal:
```bash
php artisan msgraph:test-email --to=tuEmail@ejemplo.com
```
- [ ] Esperar respuesta:
  - ✓ "Email enviado exitosamente" = TODO OK
  - ✗ "Error al enviar" = Ver logs en `storage/logs/laravel.log`

### 5.2 Prueba con SMTP Manual
- [ ] Ejecutar:
```bash
php artisan msgraph:test-email --to=tuEmail@ejemplo.com --use-smtp
```
- [ ] Confirmar que SMTP también funciona

### 5.3 Revisar Logs
- [ ] Abrir: `storage/logs/laravel.log`
- [ ] Buscar mensajes de "Microsoft Graph" o "Email"
- [ ] Verificar que no hay errores

## FASE 6: MIGRACIÓN DE CÓDIGO ⚡

### 6.1 Controllers para Migrar
24 usos de `Mail::to()` encontrados en:

- [ ] `app/Http/Controllers/Mobile/MobileAuthController.php` (5 usos)
- [ ] `app/Http/Controllers/Admin/ProfileDoctorController.php` (1 uso)
- [ ] `app/Http/Controllers/Externos/Salud/Auditorias/ExternalAuditoriaEnTerrenoController.php` (2 usos)
- [ ] `app/Http/Controllers/Auth/AuthController.php` (6 usos)
- [ ] `app/Http/Controllers/Internos/Emails/EmailsUsuariosController.php` (2 usos)
- [ ] `app/Http/Controllers/Internos/Emails/EmailsConsultorioController.php` (1 uso)
- [ ] `app/Http/Controllers/Internos/Emails/EmailsValidacionesController.php` (5 usos)

### 6.2 Pasos para cada Controller

Para **cada uno** de los controllers anteriores:

#### 6.2.1 Agregar el Trait
- [ ] Agregar en la parte superior:
```php
use App\Traits\SendsEmailsTrait;
```

#### 6.2.2 Usar el Trait en la clase
- [ ] Agregar dentro de la clase:
```php
class MiController extends Controller
{
    use SendsEmailsTrait;
    // ... resto del código
}
```

#### 6.2.3 Reemplazar Mail::to()
**ANTES:**
```php
Mail::to($email)->send(new NotificacionEvento($asunto, $datos));
```

**DESPUÉS:**
```php
$mailable = new NotificacionEvento($asunto, $datos);
$this->sendEmail($email, $mailable);
```

#### 6.2.4 Probar el Controller
- [ ] Ejecutar la funcionalidad del controller
- [ ] Verificar que el email se envía
- [ ] Revisar logs para confirmar

#### 6.2.5 Hacer Commit
```bash
git add .
git commit -m "Migrar [NombreController] a Microsoft Graph API con fallback SMTP"
```

## FASE 7: PRODUCCIÓN 🚀

### 7.1 Pruebas finales
- [ ] Probar en ambiente staging
- [ ] Correr suite completa de tests
- [ ] Verificar logs en producción

### 7.2 Monitoreo
- [ ] Activar alertas en logs para errores de email
- [ ] Monitorear ratio de envíos con Graph vs SMTP
- [ ] Tener plan B si Microsoft Graph falla

### 7.3 Activar en Producción
- [ ] En variables de entorno de producción:
```env
MAIL_USE_MICROSOFT_GRAPH=true
```
- [ ] Verificar que SMTP sigue configurado como fallback
- [ ] Monitorear durante 24-48 horas

## SOLUCIÓN DE PROBLEMAS 🔧

### ❌ Error: "No refresh token available"
**Causa**: No has ejecutado el flow de autenticación OAuth
**Solución**:
- [ ] Ve a `http://localhost:8000/api/msgraph/auth/redirect`
- [ ] Completa la autenticación con Microsoft

### ❌ Error: "Token expired"
**Causa**: El token expiró
**Solución**:
- [ ] El sistema lo renueva automáticamente
- [ ] Si no, ejecuta: `php artisan cache:clear`

### ❌ Error: "Client credential request failed"
**Causa**: Las credenciales en Azure son incorrectas
**Solución**:
- [ ] Verifica `MSGRAPH_CLIENT_ID` en `.env`
- [ ] Verifica `MSGRAPH_CLIENT_SECRET` en `.env`
- [ ] Verifica `MSGRAPH_TENANT_ID` en `.env`

### ❌ Email no se envía incluso con SMTP
**Causa**: Configuración de SMTP incompleta
**Solución**:
- [ ] Verifica `MAIL_HOST` en `.env`
- [ ] Verifica `MAIL_USERNAME` en `.env`
- [ ] Verifica `MAIL_PASSWORD` en `.env`
- [ ] Prueba credenciales en herramienta externa

## REFERENCIAS RÁPIDAS 📎

| Tarea | Comando |
|-------|---------|
| Probar email | `php artisan msgraph:test-email --to=x@x.com` |
| Limpiar caché | `php artisan cache:clear` |
| Ver logs | `tail -f storage/logs/laravel.log` |
| Revocar autenticación | `POST /api/msgraph/auth/revoke` |
| Tinker / Debugging | `php artisan tinker` |

## 📝 NOTAS

```
Nota 1: Mantén los secretos seguros
- NUNCA subas .env a git
- NUNCA compartas MSGRAPH_CLIENT_SECRET
- Usa .gitignore correctamente

Nota 2: El fallback a SMTP es automático
- No necesitas hacer nada si Graph falla
- El error se registra en logs

Nota 3: Puedes cambiar drivers en cualquier momento
- Cambia MAIL_USE_MICROSOFT_GRAPH en .env
- No necesitas cambiar código
```

## ✨ PRÓXIMO PASO

**Comienza por FASE 2**: Configura la aplicación en Azure Portal. Este es el paso MÁS IMPORTANTE.

Una vez tengas las credenciales de Azure, todo lo demás es configuración simple.

---

**Fecha**: 10 de febrero de 2026
**Versión**: 1.0
**Estado**: Listo para comenzar FASE 2
