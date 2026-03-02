<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificacionEmailRegistroUsuarioDoctor extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The subject
     * 
     * @var String
     */
    public $asunto;

    /**
     * The message data
     * 
     * @var Array
     */
    public $data;

    public $mostrar_mensaje;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(String $asunto, Array $data)
    {
        $this->asunto = $asunto;
        $this->data = $data;
        if ( $data['matricula_tipo'] != '' && $data['matricula_numero'] != '' ){
            $this->mostrar_mensaje = true;
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.notificacion_registro_usuario_doctor')
                    ->subject($this->asunto);
    }
}