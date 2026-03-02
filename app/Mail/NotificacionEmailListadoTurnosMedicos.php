<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificacionEmailListadoTurnosMedicos extends Mailable
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

    public $medico;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(String $asunto, String $adjunto, $medico)
    {
        $this->adjunto = $adjunto;
        $this->asunto = $asunto;
        $this->medico = $medico;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.notificacion_listado_turnos_medicos', [$this->medico])
                    ->subject($this->asunto)
                    ->attach($this->adjunto);
    }
}
