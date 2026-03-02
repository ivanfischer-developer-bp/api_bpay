<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificacionEmailReseteoClave extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The subject
     * 
     * @var String
     */
    public $asunto;

    /**
     * The user
     * 
     * @var String
     */
    public $usuario;

    /**
     * The token
     * 
     * @var String
     */
    public $token;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(String $asunto, $usuario, $token)
    {
        $this->asunto = $asunto;
        $this->usuario = $usuario;
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.notificacion_reseteo_clave')
                    ->subject($this->asunto);
    }
}
