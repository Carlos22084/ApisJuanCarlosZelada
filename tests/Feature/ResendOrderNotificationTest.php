<?php

namespace Tests\Feature;

use App\Mail\OrderNotificationMail;
use App\Models\Order;
use App\Models\ProductionTask;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResendOrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
        config([
            'services.resend.key' => 're_test_key',
            'services.resend.notifications_enabled' => true,
        ]);
    }

    public function test_order_creation_sends_a_resend_notification_to_the_client(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $client = User::where('role', 'client')->firstOrFail();
        $service = Service::firstOrFail();

        $response = $this->actingAs($admin)->post('/pedidos', [
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'pending',
            'priority' => 'normal',
            'furniture_type' => 'Sofá de prueba',
            'description' => 'Pedido utilizado para comprobar la notificación por correo.',
            'delivery_date' => now()->addWeek()->toDateString(),
            'subtotal' => 1200,
            'discount' => 100,
            'advance' => 500,
        ]);

        $order = Order::latest('id')->firstOrFail();

        $response->assertRedirect(route('orders.show', $order));
        Mail::assertSent(OrderNotificationMail::class, function (OrderNotificationMail $mail) use ($client, $order) {
            return $mail->hasTo($client->email)
                && $mail->order->is($order)
                && $mail->event === 'created';
        });
    }

    public function test_status_change_sends_an_email_with_the_comment(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $order = Order::firstOrFail();

        $response = $this->actingAs($admin)->patch("/pedidos/{$order->id}/estado", [
            'status' => 'ready',
            'comment' => 'El mueble está listo para recoger.',
        ]);

        $response->assertRedirect();
        Mail::assertSent(OrderNotificationMail::class, function (OrderNotificationMail $mail) use ($order) {
            return $mail->order->is($order)
                && $mail->event === 'status_changed'
                && $mail->comment === 'El mueble está listo para recoger.';
        });
    }

    public function test_client_quote_sends_a_confirmation_email(): void
    {
        $client = User::where('role', 'client')->firstOrFail();
        $service = Service::firstOrFail();

        $response = $this->actingAs($client)->post('/cotizacion', [
            'service_id' => $service->id,
            'furniture_type' => 'Sofá personalizado',
            'quantity' => 1,
            'description' => 'Solicitud creada desde el personalizador para probar Resend.',
            'requested_date' => now()->addWeek()->toDateString(),
        ]);

        $order = Order::latest('id')->firstOrFail();

        $response->assertRedirect(route('orders.show', $order));
        Mail::assertSent(OrderNotificationMail::class, fn (OrderNotificationMail $mail) =>
            $mail->hasTo($client->email)
            && $mail->order->is($order)
            && $mail->event === 'created'
        );
    }

    public function test_starting_production_notifies_the_client(): void
    {
        $worker = User::where('role', 'worker')->firstOrFail();
        $order = Order::where('status', 'confirmed')->firstOrFail();
        $task = ProductionTask::where('order_id', $order->id)->where('status', 'pending')->firstOrFail();

        $this->actingAs($worker)->patch(route('tasks.update', $task), [
            'status' => 'in_progress',
        ])->assertRedirect();

        Mail::assertSent(OrderNotificationMail::class, fn (OrderNotificationMail $mail) =>
            $mail->order->is($order)
            && $mail->order->status === 'in_progress'
            && $mail->event === 'status_changed'
        );
    }

    public function test_completing_all_stages_marks_the_order_ready_and_notifies_the_client_once(): void
    {
        $worker = User::where('role', 'worker')->firstOrFail();
        $order = Order::where('status', 'confirmed')->firstOrFail();
        $tasks = $order->tasks()->get();
        $payload = $tasks->mapWithKeys(fn ($task) => [
            $task->id => [
                'id' => $task->id,
                'status' => 'completed',
                'notes' => 'Etapa concluida.',
            ],
        ])->all();

        $this->actingAs($worker)
            ->patch(route('tasks.update-all', $order), ['tasks' => $payload])
            ->assertRedirect()
            ->assertSessionHas('success', 'Todas las etapas fueron completadas. El pedido está listo para entregar.');

        $this->assertSame('ready', $order->fresh()->status);
        $this->assertSame(100, $order->fresh()->progress);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'user_id' => $worker->id,
            'status' => 'ready',
        ]);
        Mail::assertSent(OrderNotificationMail::class, function (OrderNotificationMail $mail) use ($order) {
            return $mail->order->is($order)
                && $mail->order->status === 'ready'
                && $mail->event === 'status_changed'
                && $mail->comment === 'El trabajo ha concluido y está listo para entregar.';
        });

        $this->actingAs($worker)
            ->patch(route('tasks.update-all', $order), ['tasks' => $payload])
            ->assertRedirect();

        Mail::assertSent(OrderNotificationMail::class, 1);
        $this->assertSame(1, $order->history()->where('status', 'ready')->count());
    }

    public function test_missing_resend_key_does_not_block_order_creation(): void
    {
        config(['services.resend.key' => null]);
        $admin = User::where('role', 'admin')->firstOrFail();
        $client = User::where('role', 'client')->firstOrFail();
        $service = Service::firstOrFail();

        $response = $this->actingAs($admin)->post('/pedidos', [
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'pending',
            'priority' => 'normal',
            'furniture_type' => 'Silla de prueba',
            'description' => 'El pedido debe guardarse aunque Resend no esté configurado.',
            'subtotal' => 300,
            'discount' => 0,
            'advance' => 0,
        ]);

        $response->assertRedirect()->assertSessionHas('info', 'El correo no se envió porque Resend todavía no está configurado.');
        $this->assertDatabaseHas('orders', ['furniture_type' => 'Silla de prueba']);
        Mail::assertNothingSent();
    }
}
