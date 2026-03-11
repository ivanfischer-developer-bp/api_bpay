<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MicrosoftGraphService
{
    protected $graphUrl;
    protected $userEmail;
    protected $clientId;
    protected $clientSecret;
    protected $tenantId;
    protected $authority;

    public function __construct()
    {
        $this->graphUrl = config('msgraph.graph_url');
        $this->userEmail = config('msgraph.user_email');
        $this->clientId = config('msgraph.client_id');
        $this->clientSecret = config('msgraph.client_secret');
        $this->tenantId = config('msgraph.tenant_id');
        $this->authority = config('msgraph.authority');
    }

    /**
     * Obtener token de acceso usando Client Credentials Flow
     *
     * @return string
     * @throws Exception
     */
    protected function getAccessToken()
    {
        // Verificar si el token está en caché y es válido
        if (config('msgraph.cache_token')) {
            $token = Cache::get(config('msgraph.cache_key'));
            if ($token && $this->isTokenValid($token)) {
                Log::channel('email')->info('Usando token de acceso en caché');
                return $token['access_token'];
            }
        }

        try {
            // Solicitar token usando Client Credentials Flow
            $response = Http::asForm()->post(
                $this->authority . '/' . $this->tenantId . '/oauth2/v2.0/token',
                [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ]
            );

            if (!$response->successful()) {
                Log::channel('email')->error('Error al obtener token de Microsoft Graph', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                throw new Exception('Error al obtener token: ' . $response->body());
            }

            $data = $response->json();
            $token = [
                'access_token' => $data['access_token'],
                'expires_in' => $data['expires_in'],
                'created_at' => time(),
            ];

            // Guardar en caché (con 5 minutos de margen antes de expiración)
            if (config('msgraph.cache_token')) {
                Cache::put(
                    config('msgraph.cache_key'),
                    $token,
                    now()->addSeconds($data['expires_in'] - 300)
                );
                Log::channel('email')->info('Token guardado en caché');
            }

            return $token['access_token'];
        } catch (Exception $e) {
            Log::channel('email')->error('Excepción al obtener token de Microsoft Graph: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verificar si el token es válido
     *
     * @param array $token
     * @return bool
     */
    protected function isTokenValid($token)
    {
        if (!isset($token['expires_in']) || !isset($token['created_at'])) {
            return false;
        }
        
        $expiresAt = $token['created_at'] + $token['expires_in'];
        return $expiresAt > time();
    }

    /**
     * Enviar email a través de Microsoft Graph
     *
     * @param string|array $to
     * @param string $subject
     * @param string $body
     * @param array $attachments
     * @param array $cc
     * @param array $bcc
     * @param array $replyTo
     * @param string $fromEmail (opcional)
     * @param string $fromName (opcional)
     * @return bool
     */
    public function sendEmail($to, $subject, $body, $attachments = [], $cc = [], $bcc = [], $replyTo = [], $fromEmail = null, $fromName = null)
    {
        try {
            $token = $this->getAccessToken();

            // Usar email personalizado o el configurado por defecto
            $senderEmail = $fromEmail ?? $this->userEmail;
            $senderName = $fromName ?? config('mail.from.name', 'BPay');

            // Preparar destinatarios
            $toRecipients = $this->formatRecipients($to);
            $ccRecipients = $this->formatRecipients($cc);
            $bccRecipients = $this->formatRecipients($bcc);
            $replyToRecipients = $this->formatRecipients($replyTo);

            // Construir el mensaje - Estructura correcta para Microsoft Graph API
            $message = [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $body,
                ],
                'toRecipients' => $toRecipients,
                'ccRecipients' => $ccRecipients,
                'bccRecipients' => $bccRecipients,
            ];
            
            // Agregar replyTo solo si hay direcciones
            if (!empty($replyToRecipients)) {
                $message['replyTo'] = $replyToRecipients;
            }
            
            // Nota: Con Client Credentials Flow no se puede forzar 'from'
            // El email siempre se envía desde la cuenta del App Registrado
            // El campo 'from' se usa solo con Delegated Auth

            // Agregar adjuntos si existen
            if (!empty($attachments)) {
                $message['attachments'] = $this->prepareAttachments($attachments);
            }

            // Para Client Credentials Flow, se debe usar /users/{userId}/sendMail en lugar de /me/sendMail
            // /me requiere delegated authentication, pero estamos usando Client Credentials
            $userEmail = urlencode($this->userEmail);
            $endpoint = $this->graphUrl . '/users/' . $userEmail . '/sendMail';
            
            $response = Http::withToken($token)
                ->post($endpoint, [
                    'message' => $message,
                    'saveToSentItems' => true,
                ]);

            if ($response->successful()) {
                Log::channel('email')->info('Email enviado exitosamente a través de Microsoft Graph', [
                    'to' => $to,
                    'subject' => $subject,
                    'from' => $senderEmail,
                    'status_code' => $response->status(),
                ]);
                return true;
            } else {
                Log::channel('email')->error('Error al enviar email por Microsoft Graph - Respuesta de la API', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => $response->json(),
                    'to' => $to,
                    'subject' => $subject,
                    'from' => $senderEmail,
                    'endpoint' => $endpoint,
                ]);
                return false;
            }
        } catch (Exception $e) {
            Log::channel('email')->error('Excepción al enviar email por Microsoft Graph: ' . $e->getMessage(), [
                'to' => $to,
                'subject' => $subject,
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Formatear destinatarios al formato de Microsoft Graph
     *
     * @param string|array $recipients
     * @return array
     */
    protected function formatRecipients($recipients)
    {
        if (empty($recipients)) {
            return [];
        }

        if (is_string($recipients)) {
            $recipients = [$recipients];
        } elseif (!is_array($recipients)) {
            $recipients = [];
        }

        $formatted = [];
        foreach ($recipients as $recipient) {
            if (is_string($recipient)) {
                $formatted[] = [
                    'emailAddress' => [
                        'address' => $recipient,
                    ]
                ];
            } elseif (is_array($recipient) && (isset($recipient['email']) || isset($recipient['address']))) {
                $address = $recipient['email'] ?? $recipient['address'];
                $name = $recipient['name'] ?? '';
                $formatted[] = [
                    'emailAddress' => [
                        'address' => $address,
                        'name' => $name,
                    ]
                ];
            } elseif (is_object($recipient) && isset($recipient->address)) {
                $formatted[] = [
                    'emailAddress' => [
                        'address' => $recipient->address,
                        'name' => $recipient->name ?? '',
                    ]
                ];
            }
        }

        return $formatted;
    }

    /**
     * Preparar adjuntos para Microsoft Graph
     *
     * @param array $attachments
     * @return array
     */
    protected function prepareAttachments($attachments)
    {
        $prepared = [];

        foreach ($attachments as $attachment) {
            if (is_string($attachment) && file_exists($attachment)) {
                $prepared[] = [
                    '@odata.type' => '#microsoft.graph.fileAttachment',
                    'name' => basename($attachment),
                    'contentBytes' => base64_encode(file_get_contents($attachment)),
                ];
            }
        }

        return $prepared;
    }

}
