<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificacionEmailListaPrestaciones extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The attach
     * 
     * @var String
     */
    public $adjunto;

    /**
     * The subject
     * 
     * @var String
     */
    public $asunto;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(String $asunto, String $adjunto)
    {
        $this->adjunto = $adjunto;
        $this->asunto = $asunto;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.notificacion_informacion_afiliado')
                    ->subject($this->asunto)
                    ->attach($this->adjunto);
    }
}
