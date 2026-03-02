<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class EmailService
{
    protected $microsoftGraphService;
    protected $useMicrosoftGraph = false;

    public function __construct()
    {
        $this->microsoftGraphService = new MicrosoftGraphService();
        $this->useMicrosoftGraph = config('mail.use_microsoft_graph', false);
    }

    /**
     * Enviar email
     *
     * @param string|array $to
     * @param Mailable|string $mailable
     * @param array $attachments
     * @return bool
     */
    public function send($to, $mailable, $attachments = [])
    {
        if ($this->useMicrosoftGraph) {
            return $this->sendViaGraphAPI($to, $mailable, $attachments);
        } else {
            return $this->sendViaLaravel($to, $mailable);
        }
    }

    /**
     * Enviar email a través de Laravel Mail (SMTP)
     *
     * @param string|array $to
     * @param Mailable $mailable
     * @return bool
     */
    protected function sendViaLaravel($to, $mailable)
    {
        try {
            Mail::to($to)->send($mailable);
            return true;
        } catch (Exception $e) {
            Log::error('Error enviando email vía Laravel Mail: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar email a través de Microsoft Graph API
     *
     * @param string|array $to
     * @param Mailable $mailable
     * @param array $attachments
     * @return bool
     */
    protected function sendViaGraphAPI($to, $mailable, $attachments = [])
    {
        try {
            // Obtener datos del Mailable
            $subject = $mailable->subject ?? 'Sin asunto';
            
            // Renderizar la vista del Mailable
            $body = $this->renderMailableView($mailable);
            
            // Obtener CC, BCC y Reply-To si existen
            $cc = $mailable->cc ?? [];
            $bcc = $mailable->bcc ?? [];
            $replyTo = $mailable->replyTo ?? [];
            
            // Enviar a través de Microsoft Graph
            return $this->microsoftGraphService->sendEmail(
                $to,
                $subject,
                $body,
                $attachments,
                $cc,
                $bcc,
                $replyTo
            );
        } catch (Exception $e) {
            Log::error('Error enviando email vía Microsoft Graph: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Renderizar la vista del Mailable
     *
     * @param Mailable $mailable
     * @return string
     */
    protected function renderMailableView($mailable)
    {
        // Renderizar el Mailable usando Laravel's built-in rendering
        $message = $mailable->build();
        
        // Obtener el nombre de la vista
        if (isset($message->view) && $message->view) {
            return view($message->view, $message->viewData ?? [])->render();
        }
        
        return '';
    }

    /**
     * Obtener instancia del servicio de Microsoft Graph
     *
     * @return MicrosoftGraphService
     */
    public function getMicrosoftGraphService()
    {
        return $this->microsoftGraphService;
    }
}
