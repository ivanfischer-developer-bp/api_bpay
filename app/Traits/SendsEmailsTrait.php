<?php

namespace App\Traits;

use App\Services\EmailService;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Exception;

trait SendsEmailsTrait
{
    /**
     * Instancia del servicio de email
     *
     * @var EmailService
     */
    protected $emailService;

    /**
     * Inicializar el trait
     *
     * @return void
     */
    protected function initializeEmailService()
    {
        if (!$this->emailService) {
            $this->emailService = app('email.service');
        }
    }

    /**
     * Enviar email con fallback automático a SMTP
     *
     * @param string|array $to
     * @param Mailable $mailable
     * @param array $attachments
     * @param string|null $fromEmail
     * @param string|null $fromName
     * @return bool
     */
    protected function sendEmail($to, Mailable $mailable, $attachments = [], $fromEmail = null, $fromName = null)
    {
        $this->initializeEmailService();

        try {
            $useGraph = config('mail.use_microsoft_graph', false);
            \Illuminate\Support\Facades\Log::channel('email')->info('sendEmail: Iniciando envío de email', [
                'use_microsoft_graph' => $useGraph,
                'to' => $to,
                'subject' => $mailable->subject ?? 'Sin asunto',
                'has_attachments' => count($attachments) > 0,
            ]);
            
            // Intentar enviar con Microsoft Graph si está configurado
            if ($useGraph) {
                \Illuminate\Support\Facades\Log::channel('email')->info('sendEmail: Intentando enviar por Microsoft Graph');
                $result = $this->sendViaGraphAPI($to, $mailable, $attachments, $fromEmail, $fromName);
                
                if ($result) {
                    \Illuminate\Support\Facades\Log::channel('email')->info('sendEmail: Email enviado exitosamente por Microsoft Graph');
                    \Illuminate\Support\Facades\Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                    return true;
                }
                
                // Si falla, hacer fallback a SMTP
                \Illuminate\Support\Facades\Log::channel('email')->warning('Fallback a SMTP después de que Microsoft Graph falló', [
                    'to' => $to,
                    'subject' => $mailable->subject ?? 'Sin asunto',
                ]);
            } else {
                \Illuminate\Support\Facades\Log::channel('email')->info('sendEmail: Microsoft Graph deshabilitado, usando SMTP');
            }

            // Enviar por SMTP (por defecto o como fallback)
            \Illuminate\Support\Facades\Log::channel('email')->info('sendEmail: Enviando por SMTP');
            $smtpResult = $this->sendViaSMTP($to, $mailable, $fromEmail, $fromName);
            
            if ($smtpResult) {
                \Illuminate\Support\Facades\Log::channel('email')->info('sendEmail: Email enviado exitosamente por SMTP');
                \Illuminate\Support\Facades\Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                return true;
            } else {
                \Illuminate\Support\Facades\Log::channel('email')->error('sendEmail: Error enviando por SMTP');
                \Illuminate\Support\Facades\Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
                return false;
            }
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::channel('email')->error('Error enviando email: ' . $e->getMessage(), [
                'to' => $to,
                'trace' => $e->getTraceAsString(),
            ]);
            \Illuminate\Support\Facades\Log::channel('email')->info('═══════════════════════════════════════════════════════════════════════════════════════════');
            return false;
        }
    }

    /**
     * Enviar a través de Microsoft Graph API
     *
     * @param string|array $to
     * @param Mailable $mailable
     * @param array $attachments
     * @param string|null $fromEmail
     * @param string|null $fromName
     * @return bool
     */
    protected function sendViaGraphAPI($to, Mailable $mailable, $attachments = [], $fromEmail = null, $fromName = null)
    {
        try {
            $graphService = $this->emailService->getMicrosoftGraphService();
            
            // Obtener asunto: primero intenta propiedad personalizada 'asunto', luego 'subject'
            $subject = $mailable->asunto ?? $mailable->subject ?? 'Sin asunto';
            
            Log::channel('email')->info('Iniciando sendViaGraphAPI', ['to' => $to, 'subject' => $subject]);
            
            // Construir el Mailable para obtener vista y adjuntos
            $message = $mailable->build();
            
            // El body viene del renderizado de la vista
            $body = $this->renderMailableBody($mailable);
            
            // Las propiedades cc, bcc, replyTo están en el Mailable
            $cc = $mailable->cc ?? [];
            $bcc = $mailable->bcc ?? [];
            $replyTo = $mailable->replyTo ?? [];
            
            // Si se proporciona un fromEmail personalizado, agregarlo al replyTo
            // Esto permite que las respuestas vayan al usuario que envía, no al remitente técnico
            if ($fromEmail && $fromEmail !== config('mail.from.address')) {
                $replyTo = array_merge(
                    (array)$replyTo, 
                    [['email' => $fromEmail, 'name' => $fromName ?? $fromEmail]]
                );
                Log::channel('email')->info('Agregado replyTo personalizado', [
                    'fromEmail' => $fromEmail,
                    'fromName' => $fromName
                ]);
            }
            
            Log::channel('email')->debug('Datos del Mailable extraídos', [
                'subject' => $subject,
                'body_length' => strlen($body),
                'cc_count' => count($cc),
                'bcc_count' => count($bcc),
            ]);
            
            // Extraer adjuntos del Mailable construido
            $mailableAttachments = [];
            if (method_exists($message, 'getAttachments')) {
                $mailableAttachments = $message->getAttachments() ?? [];
                Log::channel('email')->debug('Adjuntos del Mailable (getAttachments)', ['count' => count($mailableAttachments)]);
            }
            
            // Si el Mailable tiene adjuntos en la propiedad $attachments o $adjunto
            if (property_exists($mailable, 'attachments') && !empty($mailable->attachments)) {
                $mailableAttachments = array_merge((array)$mailableAttachments, $mailable->attachments);
                Log::channel('email')->debug('Adjuntos de propiedad $attachments', ['count' => count($mailable->attachments)]);
            } elseif (property_exists($mailable, 'adjunto') && !empty($mailable->adjunto)) {
                // Algunos Mailables usan 'adjunto' en lugar de 'attachments'
                $mailableAttachments = array_merge((array)$mailableAttachments, [$mailable->adjunto]);
                Log::channel('email')->debug('Adjuntos de propiedad $adjunto', ['count' => 1]);
            }
            
            // Combinar adjuntos: del Mailable + los pasados como parámetro
            $allAttachments = array_merge((array)$mailableAttachments, (array)$attachments);
            Log::channel('email')->debug('Total de adjuntos a enviar', [
                'mailable_attachments' => count($mailableAttachments),
                'parameter_attachments' => count($attachments),
                'total' => count($allAttachments),
            ]);

            $result = $graphService->sendEmail(
                $to,
                $subject,
                $body,
                $allAttachments,
                $cc,
                $bcc,
                $replyTo,
                $fromEmail,
                $fromName
            );
            
            Log::channel('email')->info('Resultado de Microsoft Graph: ' . ($result ? 'SUCCESS' : 'FAIL'), [
                'result' => $result,
            ]);
            
            return $result;
        } catch (Exception $e) {
            Log::channel('email')->error('Error en sendViaGraphAPI: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Enviar a través de SMTP (Laravel Mail)
     *
     * @param string|array $to
     * @param Mailable $mailable
     * @param string|null $fromEmail
     * @param string|null $fromName
     * @return bool
     */
    protected function sendViaSMTP($to, Mailable $mailable, $fromEmail = null, $fromName = null)
    {
        try {
            $mail = \Illuminate\Support\Facades\Mail::to($to);
            
            // Si se proporciona remitente personalizado, configurarlo
            if ($fromEmail) {
                $mail->from($fromEmail, $fromName);
            }
            
            $mail->send($mailable);
            return true;
        } catch (Exception $e) {
            Log::channel('email')->error('Error en sendViaSMTP: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Renderizar el cuerpo del Mailable
     *
     * @param Mailable $mailable
     * @return string
     */
    protected function renderMailableBody(Mailable $mailable)
    {
        try {
            // Construir el Mailable
            $message = $mailable->build();

            // Renderizar la vista si existe
            if (isset($message->view) && $message->view) {
                // Extraer propiedades públicas del mailable para pasarlas a la vista
                $reflection = new \ReflectionClass($mailable);
                $publicProperties = [];
                
                foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                    if (!$property->isStatic()) {
                        $propertyName = $property->getName();
                        $publicProperties[$propertyName] = $mailable->$propertyName;
                    }
                }

                // Combinar propiedades públicas con viewData del mensaje
                $viewData = array_merge($publicProperties, $message->viewData ?? []);
                
                Log::channel('email')->debug('Renderizando vista con datos', [
                    'view' => $message->view,
                    'public_properties' => array_keys($publicProperties),
                    'viewData_keys' => array_keys($message->viewData ?? []),
                ]);

                return view($message->view, $viewData)->render();
            }

            return '';
        } catch (Exception $e) {
            Log::channel('email')->error('Error renderizando Mailable: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return '';
        }
    }

    /**
     * Enviar email rápidamente (sin preparación adicional)
     *
     * @param string|array $to
     * @param string $subject
     * @param string $body
     * @param array $attachments
     * @return bool
     */
    protected function sendQuickEmail($to, $subject, $body, $attachments = [])
    {
        $this->initializeEmailService();

        try {
            $graphService = $this->emailService->getMicrosoftGraphService();
            
            if (config('mail.use_microsoft_graph', false)) {
                $result = $graphService->sendEmail($to, $subject, $body, $attachments);
                
                if ($result) {
                    return true;
                }
            }

            // // Fallback a SMTP - usar una clase simple Mailable
            $mailable = new class extends \Illuminate\Mail\Mailable {
                public $emailSubject;
                public $emailBody;

                public function __construct($subject, $body)
                {
                    $this->emailSubject = $subject;
                    $this->emailBody = $body;
                }

                public function build()
                {
                    return $this->subject($this->emailSubject)
                                ->html($this->emailBody);
                }
            };
            
            return $this->sendViaSMTP($to, new $mailable($subject, $body));
        } catch (Exception $e) {
            Log::error('Error en sendQuickEmail: ' . $e->getMessage());
            return false;
        }
    }
}
