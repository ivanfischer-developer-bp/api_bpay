<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificacionEmailAuditoriaEnTerreno extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The subject
     * 
     * @var String
     */
    public $asunto;

    /**
     * The type
     * 
     * @var String
     */
    public $tipo;

    /**
     * The params of the message
     * 
     * @var Array
     */
    public $parametros_mensaje;

    /**
     * La plantilla a usar
     * 
     * @var String
     */
    private $plantilla;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(String $asunto, String $tipo, Array $parametros_mensaje)
    {
        $this->asunto = $asunto;
        $this->tipo = $tipo;
        $this->parametros_mensaje = $parametros_mensaje;
        $this->plantilla = $tipo === 'solicitud' ? 'emails.auditorias.auditoria_en_terreno_solicitar' : 'emails.auditorias.auditoria_en_terreno_actualizar';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        
        return $this->view($this->plantilla, $this->parametros_mensaje)
                    ->subject($this->asunto);
                    
    }
}
