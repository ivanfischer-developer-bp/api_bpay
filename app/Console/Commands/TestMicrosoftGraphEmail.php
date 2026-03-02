<?php

namespace App\Console\Commands;

use App\Mail\NotificacionEmailRegistroUsuarioAfiliado;
use App\Traits\SendsEmailsTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestMicrosoftGraphEmail extends Command
{
    use SendsEmailsTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'msgraph:test-email 
                            {--to= : Email destino}
                            {--subject=Test : Asunto del email}
                            {--use-smtp : Usar SMTP en lugar de Microsoft Graph}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Prueba el envío de emails con Microsoft Graph API con debugging detallado';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $to = $this->option('to') ?? config('msgraph.user_email');
        $subject = $this->option('subject') ?? 'Test Email - Microsoft Graph API';
        $useSmtp = $this->option('use-smtp');

        if (!$to) {
            $this->error('Debes proporcionar un email destino con --to o configurar MSGRAPH_USER_EMAIL');
            return 1;
        }

        $this->info("╔═══════════════════════════════════════════════════════════╗");
        $this->info("║          Prueba de Configuración de Email                 ║");
        $this->info("╚═══════════════════════════════════════════════════════════╝");
        $this->newLine();
        
        // 1. Mostrar configuración
        $this->info("📋 CONFIGURACIÓN ACTUAL:");
        $useGraph = config('mail.use_microsoft_graph', false);
        $this->line("  • config('mail.use_microsoft_graph'): " . ($useGraph ? '✅ TRUE' : '❌ FALSE'));
        $this->line("  • env('MAIL_USE_MICROSOFT_GRAPH'): " . (env('MAIL_USE_MICROSOFT_GRAPH', false) ? '✅ TRUE' : '❌ FALSE'));
        $this->line("  • config('mail.default'): " . config('mail.default'));
        $this->line("  • config('mail.from.address'): " . config('mail.from.address'));
        $this->line("  • Email destino: $to");
        $this->line("  • Asunto: $subject");
        $this->newLine();
        
        // 2. Verificar credenciales si está habilitado Graph
        if (!$useSmtp && $useGraph) {
            $this->info("🔐 VERIFICANDO CREDENCIALES DE MICROSOFT GRAPH:");
            $clientId = config('msgraph.client_id');
            $clientSecret = config('msgraph.client_secret');
            $tenantId = config('msgraph.tenant_id');
            $userEmail = config('msgraph.user_email');
            
            $this->line("  • MSGRAPH_CLIENT_ID: " . ($clientId ? '✅ ' . substr($clientId, 0, 10) . '...' : '❌ NO CONFIGURADO'));
            $this->line("  • MSGRAPH_CLIENT_SECRET: " . ($clientSecret ? '✅ Configurado' : '❌ NO CONFIGURADO'));
            $this->line("  • MSGRAPH_TENANT_ID: " . ($tenantId ? '✅ ' . $tenantId : '❌ NO CONFIGURADO'));
            $this->line("  • MSGRAPH_USER_EMAIL: " . ($userEmail ? '✅ ' . $userEmail : '❌ NO CONFIGURADO'));
            $this->newLine();
        } else {
            $this->info("📨 VERIFICANDO CREDENCIALES SMTP:");
            $smtpHost = config('mail.mailers.smtp.host');
            $smtpPort = config('mail.mailers.smtp.port');
            $smtpUser = config('mail.mailers.smtp.username');
            
            $this->line("  • MAIL_HOST: " . ($smtpHost ? '✅ ' . $smtpHost : '❌ NO CONFIGURADO'));
            $this->line("  • MAIL_PORT: " . ($smtpPort ? '✅ ' . $smtpPort : '❌ NO CONFIGURADO'));
            $this->line("  • MAIL_USERNAME: " . ($smtpUser ? '✅ ' . substr($smtpUser, 0, 10) . '...' : '❌ NO CONFIGURADO'));
            $this->newLine();
        }

        try {
            // Crear un Mailable de prueba simple
            $testData = [
                'nombre' => 'Usuario Test',
                'apellido' => 'Test',
                'email' => $to,
                'enlace' => env('APP_URL') . '/test',
                'mensaje' => 'Email de prueba del sistema BPay',
            ];

            $mailable = new NotificacionEmailRegistroUsuarioAfiliado($subject, $testData);

            // Desactivar Microsoft Graph temporalmente si se solicita SMTP
            if ($useSmtp) {
                $this->info("⚙️  FORZANDO USO DE SMTP");
                config(['mail.use_microsoft_graph' => false]);
            } else if ($useGraph) {
                $this->info("⚙️  USANDO MICROSOFT GRAPH");
            }

            $this->newLine();
            $this->info("📧 INTENTANDO ENVIAR EMAIL...");
            
            $this->initializeEmailService();
            
            // Enviar email
            $result = $this->sendEmail($to, $mailable);

            $this->newLine();
            if ($result) {
                $this->info("╔═══════════════════════════════════════════════════════════╗");
                $this->info("║ ✅  EMAIL ENVIADO EXITOSAMENTE                            ║");
                $this->info("╚═══════════════════════════════════════════════════════════╝");
                $this->line("Revisa laravel.log para detalles del envío.");
                return 0;
            } else {
                $this->error("╔═══════════════════════════════════════════════════════════╗");
                $this->error("║ ❌  ERROR AL ENVIAR EMAIL                                 ║");
                $this->error("╚═══════════════════════════════════════════════════════════╝");
                $this->line("Revisa laravel.log para detalles del error:");
                $this->line("tail -f storage/logs/laravel.log | grep -i graph");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Excepción: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return 1;
        }
    }
}
