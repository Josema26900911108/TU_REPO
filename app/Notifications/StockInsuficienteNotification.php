<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StockInsuficienteNotification extends Notification
{
    use Queueable;

    public $movimiento;
    public $usuario;

    // Recibimos el objeto del movimiento y el usuario desde el Observer
    public function __construct($movimiento, $usuario)
    {
        $this->movimiento = $movimiento;
        $this->usuario = $usuario;
    }

    public function via(object $notifiable): array
    {
        // Activamos 'mail' para correo y 'database' para la campana del panel
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->error() // Pone el botón en rojo
                    ->subject('ALERTA: Intento de salida sin stock')
                    ->greeting('Hola Administrador,')
                    ->line('Se ha detectado un intento de salida de material que supera el stock disponible.')
                    ->line('**Usuario:** ' . $this->usuario->name)
                    ->line('**Material (ID):** ' . $this->movimiento->fkMateriales)
                    ->line('**Cantidad solicitada:** ' . $this->movimiento->cantidad)
                    ->line('**Clase de Movimiento:** ' . $this->movimiento->clase_movimiento)
                    ->action('Revisar Inventario', url('/productos'))
                    ->line('El sistema bloqueó esta operación automáticamente.');
    }

    public function toArray(object $notifiable): array
    {
        // Esto es lo que se guarda en la tabla 'notifications' para la campana
        return [
            'mensaje' => 'Intento fallido de salida: ' . $this->usuario->name,
            'producto_id' => $this->movimiento->fkMateriales,
            'cantidad' => $this->movimiento->cantidad,
            'tipo' => $this->movimiento->clase_movimiento
        ];
    }
}
