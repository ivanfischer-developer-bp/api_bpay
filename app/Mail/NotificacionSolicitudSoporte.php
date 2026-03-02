<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificacionSolicitudSoporte extends Mailable
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
     * The message
     * 
     * @var String
     */
    public $mensaje;

    /**
     * The user
     * 
     * @var String
     */
    public $usuario;

    /**
     * The user email
     * 
     * @var String
     */
    public $email_usuario;

    /**
     * The environment
     * 
     * @var String
     */
    public $ambiente;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(String $asunto, String $mensaje, String $adjunto, String $usuario, String $email_usuario = '')
    {
        $this->adjunto = $adjunto;
        $this->asunto = $asunto;
        $this->mensaje = $mensaje;
        $this->usuario = $usuario;
        $this->email_usuario = $email_usuario;
        $this->ambiente = env('APP_ENV', 'local');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if($this->adjunto != ''){
            return $this->view('emails.solicitud_soporte')
                        ->subject($this->asunto)
                        ->attach($this->adjunto);
        }else{
            return $this->view('emails.solicitud_soporte')
                        ->subject($this->asunto);
        }
    }
}