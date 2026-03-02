<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificacionEmailPase extends Mailable
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
        if ( $data['nombre_usuario_origen'] != '' && $data['observaciones'] != '' ){
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
        return $this->view('emails.notificacion_pase')
                    ->subject($this->asunto);
    }
}
