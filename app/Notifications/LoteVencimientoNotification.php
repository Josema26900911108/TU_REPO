<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LoteVencimientoNotification extends Notification
{
    use Queueable;

    public $lote;

    public function __construct($lote)
    {
        $this->lote = $lote;
    }

    public function via($notifiable): array
    {
        // 'database' para la campana del panel, 'mail' para el correo
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $dias = now()->diffInDays($this->lote->fecha_vencimiento, false);
        $estado = $dias <= 0 ? 'VENCIDO' : "vence en $dias días";

        return (new MailMessage)
            ->error()
            ->subject("ALERTA: Lote $estado")
            ->line("El lote #{$this->lote->numero_lote} del producto {$this->lote->producto->nombre} está $estado.")
            ->line("Fecha de vencimiento: {$this->lote->fecha_vencimiento}")
            ->line("Cantidad actual en stock: {$this->lote->cantidad}")
            ->action('Ver Inventario', url('/productos'));
    }

    public function toArray($notifiable): array
    {
        return [
            'titulo' => 'Alerta de Vencimiento',
            'mensaje' => "El lote #{$this->lote->numero_lote} vence el {$this->lote->fecha_vencimiento}",
            'lote_id' => $this->lote->id,
            'tipo' => 'vencimiento'
        ];
    }
}
