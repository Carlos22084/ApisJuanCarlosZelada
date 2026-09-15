<?php

namespace App\Services;

use App\Mail\OrderNotificationMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OrderEmailService
{
    public const SENT = 'sent';

    public const SKIPPED = 'skipped';

    public const FAILED = 'failed';

    public function sendCreated(Order $order): string
    {
        return $this->send($order, 'created');
    }

    public function sendStatusChanged(Order $order, ?string $comment = null): string
    {
        return $this->send($order, 'status_changed', $comment);
    }

    private function send(Order $order, string $event, ?string $comment = null): string
    {
        if (! config('services.resend.notifications_enabled', true)
            || blank(config('services.resend.key'))) {
            return self::SKIPPED;
        }

        $order->loadMissing(['client', 'service', 'material']);

        if (! $order->client || blank($order->client->email)) {
            return self::SKIPPED;
        }

        try {
            Mail::mailer('resend')
                ->to($order->client->email, $order->client->name)
                ->send(new OrderNotificationMail($order, $event, $comment));

            return self::SENT;
        } catch (Throwable $exception) {
            Log::error('No se pudo enviar la notificación del pedido mediante Resend.', [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'event' => $event,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return self::FAILED;
        }
    }
}
