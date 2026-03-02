<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificacionEmailAfiliadoVerificado extends Mailable
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

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(String $asunto, Array $data)
    {
        $this->asunto = $asunto;
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.notificacion_email_afiliado_verificado')
                    ->subject($this->asunto);
    }
}