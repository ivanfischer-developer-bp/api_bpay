<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificacionEmailSolicitudInformacionPreautorizaciones extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The subject
     * 
     * @var String
     */
    public $asunto;

    /**
     * The observations
     * 
     * @var String
     */
    public $observaciones;

    /**
     * The data
     * @var Array
     */
    public $datos;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(String $asunto, String $observaciones, Array $datos)
    {

        $this->asunto = $asunto;
        $this->observaciones = $observaciones;
        $this->datos = $datos;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.solicitud_informacion_preautorizacion')
                    ->subject($this->asunto);
    }
}
