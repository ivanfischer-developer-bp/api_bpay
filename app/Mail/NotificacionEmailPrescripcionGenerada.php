<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificacionEmailPrescripcionGenerada extends Mailable
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
     * The obs
     * 
     * @var String
     */
    public $observaciones;

    /**
     * The params of the message
     * 
     * @var Array
     */
    public $link_recetas;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    // public function __construct(String $asunto, Array $link_recetas, String $observaciones)
    public function __construct(String $asunto, String $adjunto, String $observaciones, Array $link_recetas)
    {
        $this->asunto = $asunto;
        $this->link_recetas = $link_recetas;
        $this->adjunto = $adjunto;
        $this->observaciones = $observaciones;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if($this->adjunto == ''){
            return $this->view('emails.notificacion_prescripcion_generada')
                        ->subject($this->asunto);
        }else{
            return $this->view('emails.notificacion_prescripcion_generada')
                        ->subject($this->asunto)
                        ->attach($this->adjunto);
        }
                    
    }
}
