<?php

namespace Tests\Feature;

use App\Models\AiDesign;
use App\Models\Material;
use App\Models\Order;
use App\Models\ProductionTask;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_must_authenticate_before_sending_a_quote(): void
    {
        $ordersBefore = Order::count();

        $response = $this->post('/cotizacion', $this->validQuoteData());

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('info');
        $this->assertSame($ordersBefore, Order::count());
    }

    public function test_client_can_create_a_quote_with_tasks_and_status_history(): void
    {
        $client = User::where('email', 'cliente@aya.bo')->firstOrFail();
        $service = Service::firstOrFail();

        $response = $this->actingAs($client)->post('/cotizacion', $this->validQuoteData([
            'service_id' => $service->id,
            'furniture_type' => 'Sofá automatizado de prueba',
        ]));

        $order = Order::where('furniture_type', 'Sofá automatizado de prueba')->firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('success');
        $this->assertSame($client->id, $order->client_id);
        $this->assertSame('pending', $order->status);
        $this->assertEquals((float) $service->base_price, (float) $order->total);
        $this->assertSame(5, $order->tasks()->count());
        $this->assertSame([1, 2, 3, 4, 5], $order->tasks()->pluck('sequence')->all());
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'user_id' => $client->id,
            'status' => 'pending',
        ]);
    }

    public function test_client_cannot_attach_another_clients_ai_design(): void
    {
        $client = User::where('email', 'cliente@aya.bo')->firstOrFail();
        $otherClient = User::where('email', 'carlos@example.com')->firstOrFail();
        $material = Material::firstOrFail();
        $design = AiDesign::create([
            'user_id' => $otherClient->id,
            'material_id' => $material->id,
            'original_path' => 'designs/originals/other.jpg',
            'generated_path' => 'designs/generated/other.jpg',
            'color' => 'Arena',
            'style' => 'Clásico',
            'model' => '@cf/black-forest-labs/flux-2-klein-4b',
            'prompt' => 'Diseño perteneciente a otro cliente.',
        ]);

        $response = $this->actingAs($client)->post('/cotizacion', $this->validQuoteData([
            'ai_design_id' => $design->id,
        ]));

        $response->assertNotFound();
        $this->assertDatabaseMissing('orders', ['ai_design_id' => $design->id]);
    }

    public function test_worker_starting_a_task_updates_timestamps_and_order_status(): void
    {
        $worker = User::where('email', 'trabajador@aya.bo')->firstOrFail();
        $order = Order::where('status', 'confirmed')->firstOrFail();
        $task = $order->tasks()->where('status', 'pending')->firstOrFail();

        $response = $this->actingAs($worker)->patch(route('tasks.update', $task), [
            'status' => 'in_progress',
            'assigned_to' => $worker->id,
            'notes' => 'Inicio registrado por la prueba automatizada.',
        ]);

        $response->assertSessionHas('success');
        $task->refresh();
        $order->refresh();
        $this->assertSame('in_progress', $task->status);
        $this->assertNotNull($task->started_at);
        $this->assertNull($task->completed_at);
        $this->assertSame('in_progress', $order->status);
    }

    public function test_client_cannot_update_a_production_task(): void
    {
        $client = User::where('email', 'cliente@aya.bo')->firstOrFail();
        $task = ProductionTask::where('status', 'pending')->firstOrFail();
        $statusBefore = $task->status;

        $this->actingAs($client)->patch(route('tasks.update', $task), [
            'status' => 'completed',
        ])->assertForbidden();

        $this->assertSame($statusBefore, $task->fresh()->status);
    }

    public function test_invalid_order_data_is_rejected_without_writing_to_database(): void
    {
        $admin = User::where('email', 'admin@aya.bo')->firstOrFail();
        $ordersBefore = Order::count();

        $response = $this->actingAs($admin)
            ->from(route('orders.create'))
            ->post(route('orders.store'), [
                'client_id' => 999999,
                'service_id' => 999999,
                'status' => 'estado_invalido',
                'priority' => 'desconocida',
                'furniture_type' => '',
                'description' => '',
                'subtotal' => -1,
                'discount' => -5,
                'advance' => -10,
            ]);

        $response->assertRedirect(route('orders.create'));
        $response->assertSessionHasErrors([
            'client_id',
            'service_id',
            'status',
            'priority',
            'furniture_type',
            'description',
            'subtotal',
            'discount',
            'advance',
        ]);
        $this->assertSame($ordersBefore, Order::count());
    }

    private function validQuoteData(array $overrides = []): array
    {
        return array_replace([
            'service_id' => Service::firstOrFail()->id,
            'material_id' => Material::firstOrFail()->id,
            'furniture_type' => 'Sofá de tres plazas',
            'style' => 'Contemporáneo',
            'primary_color' => 'Arena',
            'dimensions' => '210 x 90 cm',
            'description' => 'Solicitud completa para verificar el flujo automatizado de cotización.',
            'requested_date' => now()->addWeek()->toDateString(),
        ], $overrides);
    }
}
