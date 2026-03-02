<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificacionEmailDocumentacionAfiliado extends Mailable
{
    use Queueable, SerializesModels;

   /**
     * The attach
     * 
     * @var String
     */
    // public $adjunto;

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
    public function __construct(String $asunto, $data)
    {
        $this->asunto = $asunto;
        $this->data = $data;
        // $this->adjunto = $adjunto;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.notificacion_documentacion')
                    ->subject($this->asunto);
                    // ->attach($this->adjunto);
    }
}
