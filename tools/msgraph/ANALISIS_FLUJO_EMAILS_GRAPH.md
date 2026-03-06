# Análisis del Flujo de Emails con Microsoft Graph - Código -3

## Problema Identificado

El método `enviar_email_certificado_afiliacion` estaba retornando código `-3` (Error al enviar email con Microsoft Graph) sin dar un error visible. El email se procesaba pero **no se enviaba con los adjuntos**.

### Causa Raíz

Había **tres problemas principales**:

### 1. **Ruta de Adjuntos Inválida** ❌
```php
// En enviar_email_certificado_afiliacion (INCORRECTO)
$path_adjunto = env('STORAGE_PATH').'reportes/certificados_afiliacion/'.$notificacion['adjunto'];
```

- `env('STORAGE_PATH')` no está definido o es `null`
- La ruta resultante no es válida
- El archivo no existe en esa ubicación

**Solución correcta:**
```php
// Usar storage_path() de Laravel (CORRECTO)
$path_adjunto = storage_path('app/public/reportes/certificados_afiliacion/'.$notificacion['adjunto']);
```

---

### 2. **Parámetro Adjuntos Duplicado** ❌
```php
// INCORRECTO - Pasar adjuntos como parámetro adicional
$resultado = $this->sendEmail($trimemails, $mailable, [$path_adjunto]);
```

- El `Mailable` ya está adjuntando el archivo en su método `build()`:
  ```php
  public function build() {
      return $this->view('...')
                  ->subject($this->asunto)
                  ->attach($this->adjunto);  // ← Adjunto aquí
  }
  ```
- Pasar `[$path_adjunto]` como tercer parámetro causa conflicto

**Solución correcta:**
```php
// NO pasar parámetro de adjuntos
$resultado = $this->sendEmail($trimemails, $mailable);
```

El trait `SendsEmailsTrait` ahora **extrae automáticamente** los adjuntos del Mailable construido.

---

### 3. **Adjuntos No Se Extraían del Mailable** ❌

El método `sendViaGraphAPI()` en `SendsEmailsTrait` no estaba extrayendo los adjuntos que el Mailable agreg

aba en su método `build()`.

**Antes (INCORRECTO):**
```php
protected function sendViaGraphAPI($to, Mailable $mailable, $attachments = [], ...)
{
    $graphService = $this->emailService->getMicrosoftGraphService();
    
    // Solo pasaba los adjuntos del parámetro, ignoraba los del Mailable
    return $graphService->sendEmail(
        $to,
        $subject,
        $body,
        $attachments,  // ← No incluye adjuntos del Mailable
        ...
    );
}
```

**Después (CORRECTO):**
```php
protected function sendViaGraphAPI($to, Mailable $mailable, $attachments = [], ...)
{
    $graphService = $this->emailService->getMicrosoftGraphService();
    
    // Construir el Mailable para obtener todos sus datos
    $message = $mailable->build();
    
    // Extraer adjuntos del Mailable construido
    $mailableAttachments = [];
    if (method_exists($message, 'getAttachments')) {
        $mailableAttachments = $message->getAttachments();
    } elseif (method_exists($message, 'getSymfonyMessage')) {
        $symfonyMessage = $message->getSymfonyMessage();
        if (method_exists($symfonyMessage, 'getAttachments')) {
            $mailableAttachments = $symfonyMessage->getAttachments();
        }
    }
    
    // Combinar adjuntos
    $allAttachments = array_merge((array)$mailableAttachments, (array)$attachments);
    
    // Ahora sí pasar todos los adjuntos
    return $graphService->sendEmail(
        $to,
        $subject,
        $body,
        $allAttachments,  // ← Incluye adjuntos del Mailable
        ...
    );
}
```

---

## Flujo Correcto Ahora

```
Controller
├─ Crear Mailable con ruta adjunto
├─ Mailable.build() → adjunta archivo en su método build()
├─ Llamar sendEmail($emails, $mailable)
│
├─ Trait SendsEmailsTrait.sendEmail()
│  ├─ sendViaGraphAPI()
│  │  ├─ Construir Mailable: $message = $mailable->build()
│  │  ├─ Extraer adjuntos de $message
│  │  ├─ MicrosoftGraphService.sendEmail()
│  │  │  ├─ Validar archivos existen
│  │  │  ├─ Leer y codificar en base64
│  │  │  ├─ Enviar a Microsoft Graph API
│  │  │  └─ Retornar true/false
│  │  └─ Retornar resultado
│  └─ Si falla, fallback a SMTP
└─ Retornar true/false
```

---

## Cambios Aplicados

### 1. **SendsEmailsTrait.php** ✅
- Mejorado `sendViaGraphAPI()` para extraer adjuntos del Mailable
- Mejorado logging para debugging
- Manejo de diferentes versiones de Symfony/Illuminate

### 2. **EmailsAfiliacionesController.php** ✅
- Actualizado `enviar_email_certificado_afiliacion`:
  - Usar `storage_path()` en lugar de `env('STORAGE_PATH')`
  - No pasar `[$path_adjunto]` a `sendEmail()`
  - Dejar que el Mailable adjunte automáticamente

---

## Cómo Verificar que Funciona

1. **Verificar archivos existen:**
   ```bash
   ls -la storage/app/public/reportes/certificados_afiliacion/
   ```

2. **Revisar logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Buscar mensajes:**
   - ✅ "Email enviado exitosamente a través de Microsoft Graph"
   - ❌ "Error al enviar email por Microsoft Graph"
   - ⚠️ "Fallback a SMTP después de que Microsoft Graph falló"

---

## Recomendaciones

1. **Validar rutas antes de enviar:**
   ```php
   if (!file_exists($path_adjunto)) {
       throw new Exception("Archivo no encontrado: {$path_adjunto}");
   }
   ```

2. **Usar rutas consistentes:**
   ```php
   // Siempre usar storage_path() para archivos en storage/
   $path = storage_path('app/public/reportes/...');
   ```

3. **Nunca duplicar adjuntos:**
   - Si el Mailable ya adjunta: no pasar como parámetro
   - Si se necesita adjuntos adicionales: pasar como `[]` en `sendEmail()`

4. **Monitorear Microsoft Graph:**
   - Revisar logs cuando retorna `false`
   - Validar permisos de la app
   - Verificar token de acceso válido

