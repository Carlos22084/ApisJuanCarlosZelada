@extends('layouts.app')

@section('title', 'Panel del trabajador | A&A')
@section('heading', 'Mis trabajos')
@section('eyebrow', 'Panel del trabajador')

@section('content')
<div class="stats-grid">
    <article class="stat-card"><span>Pedidos asignados</span><strong class="metric-number">{{ $stats['orders'] }}</strong></article>
    <article class="stat-card"><span>Etapas pendientes</span><strong class="metric-number">{{ $stats['pending'] }}</strong></article>
    <article class="stat-card"><span>En proceso</span><strong class="metric-number">{{ $stats['in_progress'] }}</strong></article>
    <article class="stat-card"><span>Completadas</span><strong class="metric-number">{{ $stats['completed'] }}</strong></article>
</div>

<section class="card" style="margin-top:18px">
    <div class="card-head">
        <div><span class="eyebrow">Producción</span><h2>Etapas asignadas</h2></div>
        <a class="btn btn-outline btn-sm" href="{{ route('orders.index') }}">Ver pedidos</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Pedido</th><th>Cliente</th><th>Etapa</th><th>Servicio</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                @forelse($tasks as $task)
                    <tr>
                        <td class="code">{{ $task->order->code }}</td>
                        <td>{{ $task->order->client->name }}</td>
                        <td><strong>{{ $task->name }}</strong></td>
                        <td>{{ $task->order->service->name }}</td>
                        <td><span class="status {{ $task->status === 'completed' ? 'delivered' : ($task->status === 'in_progress' ? 'in_progress' : 'pending') }}">{{ ['pending'=>'Pendiente','in_progress'=>'En proceso','completed'=>'Completada'][$task->status] }}</span></td>
                        <td><a class="btn btn-outline btn-sm" href="{{ route('orders.show', $task->order) }}">Gestionar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">El administrador todavía no te asignó trabajos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
