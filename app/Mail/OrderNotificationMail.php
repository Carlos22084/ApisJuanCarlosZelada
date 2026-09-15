<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $statusLabel;

    public string $heading;

    public string $intro;

    public function __construct(
        public Order $order,
        public string $event,
        public ?string $comment = null,
    ) {
        $this->statusLabel = self::statusLabels()[$order->status] ?? ucfirst($order->status);
        $this->heading = $event === 'created'
            ? 'Tu pedido fue registrado'
            : 'Tu pedido cambió de estado';
        $this->intro = $event === 'created'
            ? "Registramos correctamente tu pedido {$order->code}."
            : "El pedido {$order->code} ahora se encuentra en: {$this->statusLabel}.";
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->event === 'created'
                ? "Pedido {$this->order->code} registrado"
                : "Actualización del pedido {$this->order->code}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.orders.notification');
    }

    /** @return array<string, string> */
    private static function statusLabels(): array
    {
        return [
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmado',
            'in_progress' => 'En producción',
            'quality' => 'Control de calidad',
            'ready' => 'Listo para entregar',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
        ];
    }
}
