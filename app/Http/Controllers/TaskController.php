<?php
namespace App\Http\Controllers;

use App\Models\ProductionTask;
use App\Services\OrderEmailService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function update(Request $request, ProductionTask $task, OrderEmailService $emails)
    {
        $data = $request->validate(['status'=>['required','in:pending,in_progress,completed'],'assigned_to'=>['nullable','exists:users,id'],'notes'=>['nullable','string','max:500']]);
        if ($data['status'] === 'in_progress' && !$task->started_at) $data['started_at'] = now();
        if ($data['status'] === 'completed') $data['completed_at'] = now();
        if ($data['status'] !== 'completed') $data['completed_at'] = null;
        $task->update($data);
        $orderStatusChanged = $task->order->status === 'confirmed' && $data['status'] === 'in_progress';
        if ($orderStatusChanged) $task->order->update(['status'=>'in_progress']);

        $response = back()->with('success','Etapa de producción actualizada.');
        if ($orderStatusChanged) {
            $emailResult = $emails->sendStatusChanged($task->order->fresh(), 'El trabajo ingresó a la etapa de producción.');
            if ($emailResult === OrderEmailService::SKIPPED) {
                $response->with('info', 'El correo no se envió porque Resend todavía no está configurado.');
            } elseif ($emailResult === OrderEmailService::FAILED) {
                $response->with('info', 'La etapa se guardó, pero Resend no pudo enviar el correo.');
            }
        }

        return $response;
    }
}
