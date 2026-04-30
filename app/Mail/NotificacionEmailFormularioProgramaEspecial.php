<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificacionEmailFormularioProgramaEspecial extends Mailable
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
     * The observations
     * 
     * @var String
     */
    public $observaciones;

    /**
     * The data
     * @var Array
     */
    // public $datos;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    // public function __construct(String $asunto, String $adjunto, String $observaciones, Array $datos)
    public function __construct(String $asunto, String $adjunto, String $observaciones)
    {
        $this->adjunto = $adjunto;
        $this->asunto = $asunto;
        $this->observaciones = $observaciones;
        // $this->datos = $datos;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.notificacion_formulario_programa_especial')
                    ->subject($this->asunto)
                    ->attach($this->adjunto);
    }
}
