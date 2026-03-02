<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificacionEmailSolicitudPedidoPresupuestoExpediente extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The subject
     * 
     * @var String
     */
    public $asunto;

    /**
     * The link
     * 
     * @var String
     */
    public $link;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(String $asunto, String $link)
    {
        $this->asunto = $asunto;
        $this->link = $link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.solicitud_pedido_presupuesto_expediente')
                    ->subject($this->asunto);
    }
}
