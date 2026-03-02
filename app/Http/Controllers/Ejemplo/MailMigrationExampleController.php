<?php

namespace App\Http\Controllers\Ejemplo;

use App\Http\Controllers\Controller;
use App\Traits\SendsEmailsTrait;
use App\Mail\NotificacionEmailRegistroUsuarioAfiliado;
use App\Mail\NotificacionEmailReseteoClave;

/**
 * Ejemplo de migración de Controller para usar el nuevo sistema de emails
 * 
 * Este archivo muestra cómo reemplazar Mail::to() con el nuevo servicio
 * que soporta tanto SMTP como Microsoft Graph API
 */
class MailMigrationExampleController extends Controller
{
    use SendsEmailsTrait;

    /**
     * ANTES - Forma antigua (usando Mail::to directamente)
     * 
     * public function registrarUsuario(Request $request)
     * {
     *     $usuario = $request->user();
     *     $email = $usuario->email;
     *     $asunto = 'Bienvenida';
     *     
     *     $datos = [
     *         'nombre' => $usuario->nombre,
     *         'email' => $email,
     *     ];
     *     
     *     Mail::to($email)->send(new NotificacionEmailRegistroUsuarioAfiliado($asunto, $datos));
     * }
     */

    /**
     * DESPUÉS - Forma nueva (con Trait y fallback automático)
     */
    public function registrarUsuario()
    {
        $email = 'usuario@ejemplo.com';
        $asunto = 'Bienvenida';
        
        $datos = [
            'nombre' => 'Juan Pérez',
            'email' => $email,
        ];
        
        // Simplemente cambiar esto:
        $mailable = new NotificacionEmailRegistroUsuarioAfiliado($asunto, $datos);
        $resultado = $this->sendEmail($email, $mailable);
        
        if ($resultado) {
            return response()->json(['mensaje' => 'Email enviado correctamente']);
        }
        return response()->json(['error' => 'Error al enviar email'], 500);
    }

    /**
     * Ejemplo 2: Enviar a múltiples usuarios
     */
    public function enviarAMultiples()
    {
        $emails = ['usuario1@ejemplo.com', 'usuario2@ejemplo.com', 'usuario3@ejemplo.com'];
        $asunto = 'Notificación importante';
        
        $datos = ['mensaje' => 'Este es un mensaje importante'];
        
        foreach ($emails as $email) {
            $mailable = new NotificacionEmailRegistroUsuarioAfiliado($asunto, $datos);
            $this->sendEmail($email, $mailable);
        }
        
        return response()->json(['mensaje' => 'Emails enviados']);
    }

    /**
     * Ejemplo 3: Reseteo de contraseña
     */
    public function resetearPassword()
    {
        $email = 'usuario@ejemplo.com';
        $usuario_nombre = 'Juan Pérez';
        $token = 'abc123token456';
        
        // ANTES:
        // Mail::to($email)->send(new NotificacionEmailReseteoClave(
        //     'Reseteo de contraseña', 
        //     $usuario_nombre, 
        //     $token
        // ));
        
        // DESPUÉS:
        $mailable = new NotificacionEmailReseteoClave(
            'Reseteo de contraseña',
            $usuario_nombre,
            $token
        );
        
        $resultado = $this->sendEmail($email, $mailable);
        
        return response()->json([
            'exito' => $resultado,
            'mensaje' => $resultado ? 'Email de reseteo enviado' : 'Error al enviar email'
        ]);
    }

    /**
     * Ejemplo 4: Enviar con adjuntos
     */
    public function enviarConAdjuntos()
    {
        $email = 'usuario@ejemplo.com';
        $asunto = 'Tu certificado';
        
        $datos = ['nombre' => 'Juan'];
        $mailable = new NotificacionEmailRegistroUsuarioAfiliado($asunto, $datos);
        
        // Ruta del archivo a adjuntar
        $attachments = [
            storage_path('app/certificados/cert_juan_2025.pdf'),
            storage_path('app/documentos/documento.xlsx'),
        ];
        
        $resultado = $this->sendEmail($email, $mailable, $attachments);
        
        return response()->json(['exito' => $resultado]);
    }

    /**
     * Ejemplo 5: Envío rápido sin Mailable (HTML directo)
     */
    public function envioRapido()
    {
        $email = 'usuario@ejemplo.com';
        $asunto = 'Notificación rápida';
        $body = '<h1>Hola</h1><p>Este es un email rápido</p>';
        
        // Método rápido para HTML simple
        $resultado = $this->sendQuickEmail($email, $asunto, $body);
        
        return response()->json(['exito' => $resultado]);
    }

    /**
     * Ejemplo 6: Acceder directamente al servicio de Microsoft Graph
     * (para casos especiales)
     */
    public function casoEspecial()
    {
        $this->initializeEmailService();
        $graphService = $this->emailService->getMicrosoftGraphService();
        
        // Usar el servicio directamente si necesitas control total
        $resultado = $graphService->sendEmail(
            'usuario@ejemplo.com',
            'Asunto',
            '<p>Contenido HTML</p>',
            [],
            ['cc@ejemplo.com'],
            ['bcc@ejemplo.com']
        );
        
        return response()->json(['exito' => $resultado]);
    }
}

/**
 * LISTA DE CONTROLADORES A MIGRAR
 * 
 * Controllers que usan Mail::to() y necesitan ser actualizados:
 * 
 * ✓ app/Http/Controllers/Mobile/MobileAuthController.php (5 usos)
 * ✓ app/Http/Controllers/Admin/ProfileDoctorController.php (1 uso)
 * ✓ app/Http/Controllers/Externos/Salud/Auditorias/ExternalAuditoriaEnTerrenoController.php (2 usos)
 * ✓ app/Http/Controllers/Auth/AuthController.php (6 usos)
 * ✓ app/Http/Controllers/Internos/Emails/EmailsUsuariosController.php (2 usos)
 * ✓ app/Http/Controllers/Internos/Emails/EmailsConsultorioController.php (1 uso)
 * ✓ app/Http/Controllers/Internos/Emails/EmailsValidacionesController.php (5 usos)
 * 
 * PASOS PARA CADA CONTROLADOR:
 * 
 * 1. Agregar: use App\Traits\SendsEmailsTrait;
 * 2. Agregar en la clase: use SendsEmailsTrait;
 * 3. Reemplazar cada Mail::to($email)->send($mailable) con:
 *    $this->sendEmail($email, $mailable);
 * 4. Probar que funcione
 * 5. Hacer commit
 */
