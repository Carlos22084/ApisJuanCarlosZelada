@extends('layouts.app')

@section('title', $order->code.' | A&A')
@section('heading', 'Pedido '.$order->code)
@section('eyebrow', 'Detalle y producción')

@section('content')
<div class="toolbar">
    <a class="btn btn-outline" href="{{ route('orders.index') }}">← Volver</a>
    @if(auth()->user()->role !== 'client')
        <button class="btn btn-primary" data-modal-open="#statusModal">Actualizar estado</button>
    @endif
</div>

<div class="detail-grid">
    <div>
        <section class="card">
            <div class="card-head">
                <div>
                    <span class="status {{ $order->status }}">{{ ['pending'=>'Pendiente','confirmed'=>'Confirmado','in_progress'=>'En producción','quality'=>'Control de calidad','ready'=>'Listo para entregar','delivered'=>'Entregado','cancelled'=>'Cancelado'][$order->status] }}</span>
                    <h2 style="margin-top:12px">{{ $order->furniture_type }}</h2>
                </div>
                <div style="text-align:right">
                    <small class="muted">Avance general</small>
                    <strong style="display:block;font-size:24px">{{ $order->progress }}%</strong>
                </div>
            </div>
            <div class="progress"><span style="width:{{ $order->progress }}%"></span></div>
            <p>{{ $order->description }}</p>

            @if($order->aiDesign)
                <div class="design-comparison">
                    <figure>
                        <img src="{{ asset('storage/'.$order->aiDesign->original_path) }}" alt="Mueble original">
                        <figcaption>Fotografía original</figcaption>
                    </figure>
                    <figure>
                        <img src="{{ asset('storage/'.$order->aiDesign->generated_path) }}" alt="Propuesta generada con IA">
                        <figcaption>✦ Propuesta visual IA</figcaption>
                    </figure>
                </div>
            @endif

            <div class="info-grid">
                <div class="info-box"><small>Servicio</small><strong>{{ $order->service->name }}</strong></div>
                <div class="info-box"><small>Material</small><strong>{{ $order->material?->name ?? 'Por definir' }}</strong></div>
                <div class="info-box"><small>Color / estilo</small><strong>{{ $order->primary_color ?? '—' }} · {{ $order->style ?? '—' }}</strong></div>
                <div class="info-box"><small>Medidas</small><strong>{{ $order->dimensions ?? '—' }}</strong></div>
                <div class="info-box"><small>Registro</small><strong>{{ $order->created_at->format('d/m/Y') }}</strong></div>
                <div class="info-box"><small>Entrega prevista</small><strong>{{ $order->delivery_date?->format('d/m/Y') ?? 'Por confirmar' }}</strong></div>
            </div>
        </section>

        <section class="card" style="margin-top:18px">
            <div class="card-head">
                <h2>Etapas de producción</h2>
                <span class="muted">{{ $order->tasks->where('status', 'completed')->count() }} de {{ $order->tasks->count() }} completadas</span>
            </div>
            @foreach($order->tasks as $task)
                <div class="task-row">
                    <span class="task-number">{{ $task->status === 'completed' ? '✓' : $task->sequence }}</span>
                    <div>
                        <strong>{{ $task->name }}</strong>
                        <div class="muted">{{ $task->worker?->name ?? 'Sin responsable' }} @if($task->completed_at) · {{ $task->completed_at->format('d/m H:i') }} @endif</div>
                    </div>
                    @if(auth()->user()->role !== 'client')
                        <form class="inline-form" method="post" action="{{ route('tasks.update', $task) }}">
                            @csrf
                            @method('PATCH')
                            <select name="assigned_to">
                                <option value="">Sin asignar</option>
                                @foreach($workers as $worker)
                                    <option value="{{ $worker->id }}" @selected($task->assigned_to === $worker->id)>{{ $worker->name }}</option>
                                @endforeach
                            </select>
                            <select name="status">
                                <option value="pending" @selected($task->status === 'pending')>Pendiente</option>
                                <option value="in_progress" @selected($task->status === 'in_progress')>En proceso</option>
                                <option value="completed" @selected($task->status === 'completed')>Completada</option>
                            </select>
                            <button class="btn btn-outline btn-sm">Guardar</button>
                        </form>
                    @else
                        <span class="status {{ $task->status === 'completed' ? 'delivered' : ($task->status === 'in_progress' ? 'in_progress' : 'pending') }}">{{ ['pending'=>'Pendiente','in_progress'=>'En proceso','completed'=>'Completada'][$task->status] }}</span>
                    @endif
                </div>
            @endforeach
        </section>

    </div>

    <aside>
        <section class="card">
            <div class="card-head"><h2>Cliente</h2></div>
            <strong>{{ $order->client->name }}</strong>
            <p class="muted">{{ $order->client->phone }}<br>{{ $order->client->email }}<br>{{ $order->client->address }}</p>
        </section>
        <section class="card" style="margin-top:18px">
            <div class="card-head"><h2>Resumen económico</h2></div>
            <div class="stock-item"><span>Precio acordado</span><strong>Bs {{ number_format($order->total, 2) }}</strong></div>
            <div class="stock-item"><span>Adelanto</span><strong>Bs {{ number_format($order->advance, 2) }}</strong></div>
            <div class="stock-item"><span>Saldo pendiente</span><strong class="code">Bs {{ number_format($order->balance, 2) }}</strong></div>
        </section>
        <section class="card" style="margin-top:18px">
            <div class="card-head"><h2>Historial</h2></div>
            <ul class="timeline">
                @foreach($order->history as $entry)
                    <li class="done">
                        <strong>{{ ['pending'=>'Pendiente','confirmed'=>'Confirmado','in_progress'=>'En producción','quality'=>'Control de calidad','ready'=>'Listo','delivered'=>'Entregado','cancelled'=>'Cancelado'][$entry->status] ?? $entry->status }}</strong>
                        <div class="muted">{{ $entry->comment }}<br>{{ $entry->created_at->format('d/m/Y H:i') }}</div>
                    </li>
                @endforeach
            </ul>
        </section>
    </aside>
</div>

@if(auth()->user()->role !== 'client')
    <dialog id="statusModal">
        <form method="post" action="{{ route('orders.status', $order) }}" class="modal-body">
            @csrf
            @method('PATCH')
            <div class="modal-head"><h2>Actualizar estado</h2><button type="button" data-modal-close>×</button></div>
            <div class="field">
                <label>Nuevo estado</label>
                <select name="status" required>
                    @foreach(['pending'=>'Pendiente','confirmed'=>'Confirmado','in_progress'=>'En producción','quality'=>'Control de calidad','ready'=>'Listo para entregar','delivered'=>'Entregado','cancelled'=>'Cancelado'] as $key => $label)
                        <option value="{{ $key }}" @selected($order->status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Comentario para el historial</label>
                <textarea name="comment" rows="3" placeholder="Detalle del avance o indicación al cliente"></textarea>
            </div>
            <button class="btn btn-primary" style="margin-top:15px">Guardar cambio</button>
        </form>
    </dialog>
@endif
@endsection
