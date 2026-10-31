<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ProductionTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkerAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_must_choose_the_role_that_matches_their_account(): void
    {
        $this->post('/ingresar', [
            'role' => 'client',
            'email' => 'cliente@aya.bo',
            'password' => 'Cliente123!',
        ])->assertSessionHasErrors('role');

        $this->post('/ingresar', [
            'role' => 'worker',
            'email' => 'admin@aya.bo',
            'password' => 'Admin123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->post('/ingresar', [
            'role' => 'worker',
            'email' => 'trabajador@aya.bo',
            'password' => 'Trabajador123!',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))->assertOk()->assertSee('Mis trabajos');
    }

    public function test_admin_can_assign_all_pending_stages_to_a_worker(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $worker = User::create([
            'name' => 'Ana Trabajadora',
            'email' => 'ana@aya.bo',
            'phone' => '70000000',
            'role' => 'worker',
            'active' => true,
            'password' => 'Trabajador123!',
        ]);
        $order = Order::whereHas('tasks', fn ($tasks) => $tasks->where('status', 'pending'))->firstOrFail();
        $pendingTaskIds = $order->tasks()->where('status', '!=', 'completed')->pluck('id');

        $this->actingAs($admin)->patch(route('orders.assign', $order), [
            'worker_id' => $worker->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(
            $pendingTaskIds->count(),
            ProductionTask::whereIn('id', $pendingTaskIds)->where('assigned_to', $worker->id)->count()
        );
    }

    public function test_admin_can_register_a_worker_account(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)->post(route('workers.store'), [
            'name' => 'Laura Taller',
            'email' => 'laura@aya.bo',
            'phone' => '72222222',
            'address' => 'Cochabamba',
            'password' => 'ClaveSegura123!',
            'password_confirmation' => 'ClaveSegura123!',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'laura@aya.bo',
            'role' => 'worker',
            'active' => true,
        ]);
    }

    public function test_duplicate_worker_email_is_shown_inside_the_creation_form(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)->post(route('workers.store'), [
            'name' => 'Trabajador Duplicado',
            'email' => 'admin@aya.bo',
            'phone' => '70000000',
            'address' => 'Cochabamba',
            'password' => 'ClaveSegura123!',
            'password_confirmation' => 'ClaveSegura123!',
        ])->assertRedirect()
            ->assertSessionHasErrors([
                'email' => 'Este correo electrónico ya está registrado.',
            ], null, 'createWorker');

        $this->assertSame(1, User::where('email', 'admin@aya.bo')->count());

        $this->get(route('workers.index'))
            ->assertOk()
            ->assertSee('Este correo electrónico ya está registrado.')
            ->assertSee('newWorkerDialog.showModal()', false);
    }

    public function test_admin_can_optionally_update_a_workers_password(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $worker = User::where('role', 'worker')->firstOrFail();
        $originalPassword = $worker->password;

        $this->actingAs($admin)->put(route('workers.update', $worker), [
            'name' => $worker->name,
            'email' => $worker->email,
            'phone' => $worker->phone,
            'address' => $worker->address,
            'active' => '1',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame($originalPassword, $worker->fresh()->password);

        $this->actingAs($admin)->put(route('workers.update', $worker), [
            'name' => $worker->name,
            'email' => $worker->email,
            'phone' => $worker->phone,
            'address' => $worker->address,
            'active' => '1',
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue(Hash::check('NuevaClave123!', $worker->fresh()->password));
    }

    public function test_worker_can_update_all_assigned_stages_with_one_action(): void
    {
        $worker = User::where('email', 'trabajador@aya.bo')->firstOrFail();
        $order = Order::where('status', 'confirmed')->firstOrFail();
        $tasks = $order->tasks()->get();
        $payload = $tasks->mapWithKeys(fn ($task) => [
            $task->id => [
                'id' => $task->id,
                'status' => 'completed',
                'notes' => 'Etapa actualizada con el formulario general.',
            ],
        ])->all();

        $this->actingAs($worker)
            ->patch(route('tasks.update-all', $order), ['tasks' => $payload])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(5, $order->tasks()->where('status', 'completed')->count());
        $this->assertSame(5, $order->tasks()->whereNotNull('started_at')->count());
        $this->assertSame(5, $order->tasks()->whereNotNull('completed_at')->count());
        $this->assertSame(100, $order->fresh()->progress);
        $this->assertSame('ready', $order->fresh()->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'user_id' => $worker->id,
            'status' => 'ready',
        ]);

        $response = $this->actingAs($worker)->get(route('orders.show', $order));
        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'Actualizar etapas'));
    }

    public function test_worker_only_sees_assigned_orders_and_cannot_update_another_workers_task(): void
    {
        $otherWorker = User::create([
            'name' => 'Trabajador sin asignación',
            'email' => 'sin-asignacion@aya.bo',
            'phone' => '71111111',
            'role' => 'worker',
            'active' => true,
            'password' => 'Trabajador123!',
        ]);
        $task = ProductionTask::whereNotNull('assigned_to')->where('status', 'pending')->firstOrFail();

        $this->actingAs($otherWorker)
            ->get(route('orders.show', $task->order))
            ->assertForbidden();

        $this->actingAs($otherWorker)
            ->patch(route('tasks.update', $task), ['status' => 'completed'])
            ->assertForbidden();

        $this->actingAs($otherWorker)
            ->patch(route('tasks.update-all', $task->order), [
                'tasks' => [
                    $task->id => [
                        'id' => $task->id,
                        'status' => 'completed',
                    ],
                ],
            ])
            ->assertForbidden();

        $this->assertNotSame('completed', $task->fresh()->status);
    }

    public function test_worker_cannot_use_admin_order_routes(): void
    {
        $worker = User::where('role', 'worker')->firstOrFail();
        $order = Order::firstOrFail();

        $this->actingAs($worker)->get(route('orders.create'))->assertForbidden();
        $this->actingAs($worker)->patch(route('orders.status', $order), [
            'status' => 'ready',
        ])->assertForbidden();
    }
}
