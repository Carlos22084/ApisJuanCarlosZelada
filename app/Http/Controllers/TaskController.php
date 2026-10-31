<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ProductionTask;
use App\Services\OrderEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function updateAll(Request $request, Order $order, OrderEmailService $emails)
    {
        $rules = [
            'tasks' => ['required', 'array', 'min:1', 'max:5'],
            'tasks.*.id' => ['required', 'integer', 'distinct', 'exists:production_tasks,id'],
            'tasks.*.status' => ['required', 'in:pending,in_progress,completed'],
            'tasks.*.notes' => ['nullable', 'string', 'max:500'],
        ];

        if (Auth::user()->role === 'admin') {
            $rules['tasks.*.assigned_to'] = [
                'nullable',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->where('role', 'worker')->where('active', true)
                ),
            ];
        }

        $data = $request->validate($rules);
        $updates = collect($data['tasks'])->keyBy(fn ($task) => (int) $task['id']);
        $tasks = $order->tasks()->whereIn('id', $updates->keys())->get();

        abort_unless($tasks->count() === $updates->count(), 422);
        if (Auth::user()->role === 'worker') {
            abort_unless($tasks->every(fn ($task) => $task->assigned_to === Auth::id()), 403);
        }

        $orderEvent = null;

        DB::transaction(function () use ($tasks, $updates, $order, &$orderEvent) {
            $productionStarted = false;

            foreach ($tasks as $task) {
                $taskData = $updates->get($task->id);
                unset($taskData['id']);

                if (in_array($taskData['status'], ['in_progress', 'completed'], true) && !$task->started_at) {
                    $taskData['started_at'] = now();
                }
                if ($taskData['status'] === 'completed') {
                    if ($task->status !== 'completed' || !$task->completed_at) {
                        $taskData['completed_at'] = now();
                    }
                } else {
                    $taskData['completed_at'] = null;
                }

                $productionStarted = $productionStarted || $taskData['status'] === 'in_progress';

                $task->update($taskData);
            }

            $orderEvent = $this->synchronizeOrderStatus($order, $productionStarted);
        });

        $response = back()->with(
            'success',
            $orderEvent === 'completed'
                ? 'Todas las etapas fueron completadas. El pedido está listo para entregar.'
                : 'Etapas de producción actualizadas correctamente.'
        );

        if ($orderEvent) {
            $comment = $orderEvent === 'completed'
                ? 'El trabajo ha concluido y está listo para entregar.'
                : 'El trabajo ingresó a la etapa de producción.';
            $emailResult = $emails->sendStatusChanged($order->fresh(), $comment);
            if ($emailResult === OrderEmailService::SKIPPED) {
                $response->with('info', 'El correo no se envió porque Resend todavía no está configurado.');
            } elseif ($emailResult === OrderEmailService::FAILED) {
                $response->with('info', 'Las etapas se guardaron, pero Resend no pudo enviar el correo.');
            }
        }

        return $response;
    }

    public function update(Request $request, ProductionTask $task, OrderEmailService $emails)
    {
        if (Auth::user()->role === 'worker') {
            abort_unless($task->assigned_to === Auth::id(), 403);
            $data = $request->validate([
                'status'=>['required','in:pending,in_progress,completed'],
                'notes'=>['nullable','string','max:500'],
            ]);
        } else {
            $data = $request->validate([
                'status'=>['required','in:pending,in_progress,completed'],
                'assigned_to'=>[
                    'nullable',
                    Rule::exists('users', 'id')->where(
                        fn ($query) => $query->where('role', 'worker')->where('active', true)
                    ),
                ],
                'notes'=>['nullable','string','max:500'],
            ]);
        }
        if (in_array($data['status'], ['in_progress', 'completed'], true) && !$task->started_at) $data['started_at'] = now();
        if ($data['status'] === 'completed') $data['completed_at'] = now();
        if ($data['status'] !== 'completed') $data['completed_at'] = null;
        $task->update($data);
        $order = $task->order;
        $orderEvent = $this->synchronizeOrderStatus($order, $data['status'] === 'in_progress');

        $response = back()->with(
            'success',
            $orderEvent === 'completed'
                ? 'Todas las etapas fueron completadas. El pedido está listo para entregar.'
                : 'Etapa de producción actualizada.'
        );
        if ($orderEvent) {
            $comment = $orderEvent === 'completed'
                ? 'El trabajo ha concluido y está listo para entregar.'
                : 'El trabajo ingresó a la etapa de producción.';
            $emailResult = $emails->sendStatusChanged($order->fresh(), $comment);
            if ($emailResult === OrderEmailService::SKIPPED) {
                $response->with('info', 'El correo no se envió porque Resend todavía no está configurado.');
            } elseif ($emailResult === OrderEmailService::FAILED) {
                $response->with('info', 'La etapa se guardó, pero Resend no pudo enviar el correo.');
            }
        }

        return $response;
    }

    private function synchronizeOrderStatus(Order $order, bool $productionStarted): ?string
    {
        $allTasksCompleted = $order->tasks()->exists()
            && !$order->tasks()->where('status', '!=', 'completed')->exists();

        if ($allTasksCompleted && !in_array($order->status, ['ready', 'delivered', 'cancelled'], true)) {
            $order->update(['status' => 'ready']);
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'status' => 'ready',
                'comment' => 'Todas las etapas de producción fueron completadas. Trabajo listo para entregar.',
            ]);

            return 'completed';
        }

        if ($productionStarted && $order->status === 'confirmed') {
            $order->update(['status' => 'in_progress']);

            return 'started';
        }

        return null;
    }
}
