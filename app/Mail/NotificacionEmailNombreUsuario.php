<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificacionEmailNombreUsuario extends Mailable
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
    public function __construct(String $asunto, $usuario)
    {
        $this->asunto = $asunto;
        $this->usuario = $usuario;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.notificacion_nombre_usuario')
                    ->subject($this->asunto);
    }
}
