# 💡 Ejemplos Prácticos - Client Credentials Flow

## 📝 Tabla de Contenidos

1. [Ejemplo Básico](#ejemplo-básico)
2. [LEAL MEDICA con Remitente Dinámico](#leal-medica-con-remitente-dinámico)
3. [Validaciones con Adjuntos](#validaciones-con-adjuntos)
4. [Encuestas con Fallback](#encuestas-con-fallback)
5. [Manejo de Errores](#manejo-de-errores)
6. [Debug y Logs](#debug-y-logs)

---

## Ejemplo Básico

### Antes (OAuth - Complicado)

```php
<?php

namespace App\Http\Controllers\Internos\Emails;

use Mail;
use App\Mail\NotificacionEmailEncuestaAtencion;

class EmailsEncuestasController extends Controller
{
    public function enviar_email_encuesta()
    {
        $email = 'usuario@ejemplo.com';
        $data = ['nombre' => 'Juan', 'apellido' => 'Pérez'];
        
        // ❌ Requería que antes se hubiera autenticado vía OAuth
        // ❌ Requería que se guardara refresh_token
        Mail::to($email)->send(new NotificacionEmailEncuestaAtencion('Asunto', $data));
        
        if(Mail::failures()){
            return response()->json(['error' => 'Email no enviado'], 400);
        }
        
        return response()->json(['message' => 'Email enviado'], 200);
    }
}
```

### Ahora (Client Credentials - Simple)

```php
<?php

namespace App\Http\Controllers\Internos\Emails;

use Mail;
use App\Traits\SendsEmailsTrait;
use App\Mail\NotificacionEmailEncuestaAtencion;

class EmailsEncuestasController extends Controller
{
    use SendsEmailsTrait; // ← Agregar trait
    
    public function enviar_email_encuesta()
    {
        $email = 'usuario@ejemplo.com';
        $data = ['nombre' => 'Juan', 'apellido' => 'Pérez'];
        
        // ✅ Automático - Token se obtiene cuando se necesita
        $mailable = new NotificacionEmailEncuestaAtencion('Asunto', $data);
        $resultado = $this->sendEmail($email, $mailable);
        
        if(!$resultado){
            return response()->json(['error' => 'Email no enviado'], 400);
        }
        
        return response()->json(['message' => 'Email enviado'], 200);
    }
}
```

---

## LEAL MEDICA con Remitente Dinámico

### Caso de Uso: Enviar emails de diferentes empresas con remitentes personalizados

```php
<?php

namespace App\Http\Controllers\Internos\Emails;

use Illuminate\Http\Request;
use Mail;
use App\Traits\SendsEmailsTrait;
use App\Mail\NotificacionEmailEncuestaAtencion;

class EmailsEncuestasController extends Controller
{
    use SendsEmailsTrait;
    
    public function enviar_email_encuesta_atencion(Request $request)
    {
        try {
            // Obtener datos
            $email = request('email');
            $n_empresa = request('n_empresa');
            $data = [
                'nombre' => request('nombre'),
                'apellido' => request('apellido'),
                // ... otros datos
            ];
            
            // ✅ NEW: Implementación con Client Credentials
            if(env('COMPANY_NAME') == 'LEAL MEDICA'){
                if ($n_empresa != null) {
                    // Remitente personalizado por empresa
                    $fromAddress = config('mail.from.address');
                    $fromName = $n_empresa;
                    
                    if(strtoUpper($n_empresa) == 'BRINDAR SALUD ONLINE'){
                        $data['header'] = 'BRINDAR SALUD';
                    } else {
                        $data['header'] = env('HEADER_EMAIL');
                    }
                    
                    // Si Microsoft Graph está habilitado
                    if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                        $mailable = new NotificacionEmailEncuestaAtencion(
                            'Experiencia con tu consulta',
                            $data
                        );
                        
                        // ✅ Enviar con remitente personalizado
                        $resultado = $this->sendEmail(
                            [$email],
                            $mailable,
                            [],              // attachments
                            $fromAddress,    // fromEmail
                            $fromName        // fromName
                        );
                        
                        if ($resultado) {
                            $message = 'Email enviado con Microsoft Graph';
                            $status = 'ok';
                            $code = 1;
                        } else {
                            $message = 'Error al enviar email con Microsoft Graph';
                            $status = 'fail';
                            $code = -3;
                        }
                    } else {
                        // Fallback a SMTP
                        $mailable = new NotificacionEmailEncuestaAtencion(
                            'Experiencia con tu consulta',
                            $data
                        );
                        $mailable->from($fromAddress, $fromName);
                        Mail::to([$email])->send($mailable);
                        
                        if(Mail::failures()){
                            $message = 'Error al enviar email por SMTP';
                            $status = 'fail';
                            $code = -4;
                        } else {
                            $message = 'Email enviado por SMTP';
                            $status = 'ok';
                            $code = 2;
                        }
                    }
                }
            } else {
                // Empresas que no son LEAL MEDICA
                $mailable = new NotificacionEmailEncuestaAtencion('Asunto', $data);
                $resultado = $this->sendEmail([$email], $mailable);
                
                $status = $resultado ? 'ok' : 'fail';
                $code = $resultado ? 1 : -1;
                $message = $resultado ? 'Email enviado' : 'Error al enviar';
            }
            
            return [
                'status' => $status,
                'message' => $message,
                'code' => $code,
            ];
            
        } catch (\Throwable $th) {
            return [
                'status' => 'fail',
                'message' => $th->getMessage(),
                'code' => -1,
            ];
        }
    }
}
```

---

## Validaciones con Adjuntos

```php
<?php

namespace App\Http\Controllers\Internos\Emails;

use Illuminate\Http\Request;
use App\Traits\SendsEmailsTrait;
use App\Mail\NotificacionEmailValidaciones;

class EmailsValidacionesController extends Controller
{
    use SendsEmailsTrait;
    
    public function enviar_email_validacion(Request $request)
    {
        try {
            $notificacion = request('notificacion');
            $emails = $notificacion['emails']; // Array de emails
            $n_empresa = $notificacion['n_empresa'] ?? null;
            
            // Ruta del archivo adjunto
            $adjunto = env('STORAGE_PATH') . 'reportes/validaciones/' . $notificacion['adjunto'];
            
            // ✅ Enviar con adjunto
            if(env('MAIL_USE_MICROSOFT_GRAPH', false)){
                $mailable = new NotificacionEmailValidaciones(
                    $notificacion['asunto'],
                    $adjunto,
                    $notificacion['observaciones'],
                    ['header' => env('HEADER_EMAIL')]
                );
                
                // Remitente personalizado si aplicable
                $fromAddress = config('mail.from.address');
                $fromName = $n_empresa ?? config('mail.from.name');
                
                $resultado = $this->sendEmail(
                    $emails,
                    $mailable,
                    [$adjunto],  // ← Adjunto aquí
                    $fromAddress,
                    $fromName
                );
                
                if($resultado){
                    $status = 'ok';
                    $message = 'Email con adjunto enviado vía Microsoft Graph';
                } else {
                    $status = 'fail';
                    $message = 'Error al enviar email con Microsoft Graph';
                }
            } else {
                // SMTP con adjunto
                $mailable = new NotificacionEmailValidaciones(
                    $notificacion['asunto'],
                    $adjunto,
                    $notificacion['observaciones'],
                    ['header' => env('HEADER_EMAIL')]
                );
                
                Mail::to($emails)->send($mailable);
                
                if(Mail::failures()){
                    $status = 'fail';
                    $message = 'Error al enviar email por SMTP';
                } else {
                    $status = 'ok';
                    $message = 'Email con adjunto enviado vía SMTP';
                }
            }
            
            return ['status' => $status, 'message' => $message];
            
        } catch (\Throwable $th) {
            return [
                'status' => 'fail',
                'message' => 'Error: ' . $th->getMessage(),
            ];
        }
    }
}
```

---

## Encuestas con Fallback

```php
<?php

namespace App\Http\Controllers\Internos\Emails;

use App\Traits\SendsEmailsTrait;
use App\Mail\NotificacionEmailSolicitudInformacion;

class EmailsSolicitudesController extends Controller
{
    use SendsEmailsTrait;
    
    public function enviar_solicitud_informacion(Request $request)
    {
        $email = request('email');
        $asunto = request('asunto');
        $datos = request('datos');
        
        $mailable = new NotificacionEmailSolicitudInformacion($asunto, $datos);
        
        // ✅ Fallback automático: Intenta GraphAPI, si falla → SMTP
        $resultado = $this->sendEmail($email, $mailable);
        
        if($resultado){
            // ✓ Enviado exitosamente (con Graph o SMTP)
            return response()->json(['message' => 'Email enviado'], 200);
        } else {
            // ✗ Fallo total en ambos métodos
            \Log::error('No se pudo enviar email a ' . $email);
            return response()->json(['error' => 'No se pudo enviar'], 500);
        }
    }
}
```

---

## Manejo de Errores

```php
<?php

namespace App\Http\Controllers;

use App\Traits\SendsEmailsTrait;
use App\Mail\NotificacionEmail;
use Illuminate\Support\Facades\Log;

class EmailController extends Controller
{
    use SendsEmailsTrait;
    
    public function enviar_con_manejo_errores()
    {
        try {
            $emails = ['usuario1@ejemplo.com', 'usuario2@ejemplo.com'];
            $mailable = new NotificacionEmail('Asunto', []);
            
            // ✅ Enviar a múltiples destinatarios
            $resultado = $this->sendEmail($emails, $mailable);
            
            if($resultado){
                Log::info('Emails enviados exitosamente', [
                    'to' => $emails,
                    'method' => env('MAIL_USE_MICROSOFT_GRAPH') ? 'GraphAPI' : 'SMTP',
                ]);
                return ['status' => 'ok'];
            }
            
        } catch (\Exception $e) {
            // ✗ Error en la obtención del token o envío
            Log::error('Error enviando email: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
```

---

## Debug y Logs

### Verificar que todo funciona

```bash
# 1. Activar en .env
MAIL_USE_MICROSOFT_GRAPH=true

# 2. Ejecutar prueba
php artisan msgraph:test-email --to=usuario@ejemplo.com

# 3. Ver logs en tiempo real
tail -f storage/logs/laravel.log

# 4. Buscar menciones de Microsoft Graph
tail -f storage/logs/laravel.log | grep "Microsoft Graph"

# 5. Limpiar caché si hay problemas
php artisan cache:clear
```

### Logs esperados

```
[2026-02-11 10:30:15] local.INFO: Token guardado en caché
[2026-02-11 10:30:16] local.INFO: Email enviado exitosamente a través de Microsoft Graph {
  "to": "usuario@ejemplo.com",
  "subject": "Asunto del email",
  "from": "remitente@empresa.com"
}
```

### Si algo falla

```php
// Ver qué método se está usando
if(env('MAIL_USE_MICROSOFT_GRAPH')){
    Log::info('Intentando enviar con Microsoft Graph');
} else {
    Log::info('Usando SMTP como transporte');
}

// Ver el token
$token = Cache::get('msgraph_access_token');
if($token){
    Log::info('Token disponible en caché', ['expires_in' => $token['expires_in']]);
} else {
    Log::info('No hay token en caché');
}

// Ver error específico
Log::error('Error: ' . json_encode($e->getMessage()));
```

---

## De Un Vistazo

| Tarea | Código |
|-------|--------|
| **Envío simple** | `$this->sendEmail($to, $mailable)` |
| **Con adjunto** | `$this->sendEmail($to, $mailable, [$archivo])` |
| **Remitente personalizado** | `$this->sendEmail($to, $mailable, [], $from, $name)` |
| **Con todo** | `$this->sendEmail($to, $mailable, [$archivo], $from, $name)` |
| **Múltiples emails** | `$this->sendEmail(['email1', 'email2'], $mailable)` |
| **Sin trait** | `app('email.service')->send($to, $mailable)` |

---

## Resumen

- ✅ **Automático**: Token se obtiene automáticamente
- ✅ **Seguro**: Credenciales de app, no de usuario
- ✅ **Simple**: Una sola línea de código para enviar
- ✅ **Flexible**: Soporta remitente dinámico
- ✅ **Robusto**: Fallback automático a SMTP
- ✅ **Escalable**: Funciona con 1 o 1000 emails

