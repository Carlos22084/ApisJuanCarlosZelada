@extends('layouts.app')

@section('title', 'Trabajadores | A&A')
@section('heading', 'Gestión de trabajadores')
@section('eyebrow', 'Personal y asignaciones')

@section('content')
<div class="toolbar">
    <form class="searchbar"><input name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, correo o teléfono…"><button class="btn btn-outline">Buscar</button></form>
    <button class="btn btn-primary" data-modal-open="#newWorker">+ Nuevo trabajador</button>
</div>
<section class="card">
    <div class="table-wrap"><table>
        <thead><tr><th>Trabajador</th><th>Contacto</th><th>Dirección</th><th>Etapas asignadas</th><th>Estado</th><th></th></tr></thead>
        <tbody>
            @forelse($workers as $worker)
                <tr>
                    <td><strong>{{ $worker->name }}</strong><div class="muted">Registrado {{ $worker->created_at->format('m/Y') }}</div></td>
                    <td>{{ $worker->phone }}<div class="muted">{{ $worker->email }}</div></td>
                    <td>{{ $worker->address ?? '—' }}</td>
                    <td><strong>{{ $worker->assigned_tasks_count }}</strong></td>
                    <td><span class="status {{ $worker->active ? 'delivered' : 'cancelled' }}">{{ $worker->active ? 'Activo' : 'Inactivo' }}</span></td>
                    <td>
                        <div style="display:flex;gap:7px;flex-wrap:wrap">
                            <button class="btn btn-outline btn-sm" data-modal-open="#workerJobs{{ $worker->id }}">Ver trabajos</button>
                            <button class="btn btn-outline btn-sm" data-modal-open="#worker{{ $worker->id }}">Editar</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No hay trabajadores registrados.</td></tr>
            @endforelse
        </tbody>
    </table></div>
    <div class="pagination">{{ $workers->links() }}</div>
</section>
<dialog id="newWorker"><form method="post" action="{{ route('workers.store') }}" class="modal-body">@csrf<div class="modal-head"><h2>Nuevo trabajador</h2><button type="button" data-modal-close>×</button></div>@include('workers.partials.form', ['worker' => null])</form></dialog>
@foreach($workers as $worker)
    <dialog id="workerJobs{{ $worker->id }}" style="max-width:900px">
        <div class="modal-body">
            <div class="modal-head">
                <div><span class="eyebrow">Asignaciones</span><h2>Trabajos asignados a {{ $worker->name }}</h2></div>
                <button type="button" data-modal-close>×</button>
            </div>
            @php($assignedOrders = $worker->assignedTasks->groupBy('order_id'))
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Pedido</th><th>Trabajo</th><th>Servicio</th><th>Etapas asignadas</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        @forelse($assignedOrders as $tasks)
                            @php($assignedOrder = $tasks->first()->order)
                            <tr>
                                <td class="code">{{ $assignedOrder->code }}</td>
                                <td>{{ $assignedOrder->furniture_type }}</td>
                                <td>{{ $assignedOrder->service->name }}</td>
                                <td><strong>{{ $tasks->where('status', 'completed')->count() }} de {{ $tasks->count() }}</strong> completadas</td>
                                <td><span class="status {{ $assignedOrder->status }}">{{ ['pending'=>'Pendiente','confirmed'=>'Confirmado','in_progress'=>'Producción','quality'=>'Calidad','ready'=>'Listo','delivered'=>'Entregado','cancelled'=>'Cancelado'][$assignedOrder->status] }}</span></td>
                                <td><a class="btn btn-outline btn-sm" href="{{ route('orders.show', $assignedOrder) }}">Ver pedido</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty">Este trabajador todavía no tiene trabajos asignados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </dialog>
    <dialog id="worker{{ $worker->id }}"><form method="post" action="{{ route('workers.update', $worker) }}" class="modal-body">@csrf @method('PUT')<div class="modal-head"><h2>Editar trabajador</h2><button type="button" data-modal-close>×</button></div>@include('workers.partials.form', ['worker' => $worker])</form></dialog>
@endforeach
@endsection

@if($errors->getBag('createWorker')->any())
    @push('scripts')
        <script>
            const newWorkerDialog = document.querySelector('#newWorker');
            if (newWorkerDialog && !newWorkerDialog.open) {
                newWorkerDialog.showModal();
            }
        </script>
    @endpush
@endif

@if(session('open_worker_modal'))
    @push('scripts')
        <script>
            const editWorkerDialog = document.querySelector('#worker{{ (int) session('open_worker_modal') }}');
            if (editWorkerDialog && !editWorkerDialog.open) {
                editWorkerDialog.showModal();
            }
        </script>
    @endpush
@endif
