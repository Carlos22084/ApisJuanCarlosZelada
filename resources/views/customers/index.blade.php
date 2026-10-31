@extends('layouts.app')

@section('title', 'Clientes | A&A')
@section('heading', 'Gestión de clientes')
@section('eyebrow', 'Información centralizada')

@section('content')
<div class="toolbar">
    <form class="searchbar">
        <input name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, correo o teléfono…">
        <button class="btn btn-outline">Buscar</button>
    </form>
    <button class="btn btn-primary" data-modal-open="#newCustomer">+ Nuevo cliente</button>
</div>

<section class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Cliente</th><th>Contacto</th><th>Dirección</th><th>Pedidos</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td><strong>{{ $customer->name }}</strong><div class="muted">Cliente desde {{ $customer->created_at->format('m/Y') }}</div></td>
                        <td>{{ $customer->phone }}<div class="muted">{{ $customer->email }}</div></td>
                        <td>{{ $customer->address ?? '—' }}</td>
                        <td><strong>{{ $customer->orders_count }}</strong></td>
                        <td><span class="status {{ $customer->active ? 'delivered' : 'cancelled' }}">{{ $customer->active ? 'Activo' : 'Inactivo' }}</span></td>
                        <td>
                            <div style="display:flex;gap:7px;flex-wrap:wrap">
                                <button class="btn btn-outline btn-sm" data-modal-open="#customerOrders{{ $customer->id }}">Ver pedidos</button>
                                <button class="btn btn-outline btn-sm" data-modal-open="#customer{{ $customer->id }}">Editar</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">No hay clientes registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $customers->links() }}</div>
</section>

<dialog id="newCustomer">
    <form method="post" action="{{ route('customers.store') }}" class="modal-body">
        @csrf
        @include('customers.partials.form', ['customer' => null])
    </form>
</dialog>

@foreach($customers as $customer)
    <dialog id="customerOrders{{ $customer->id }}" style="max-width:900px">
        <div class="modal-body">
            <div class="modal-head">
                <div><span class="eyebrow">Historial del cliente</span><h2>Pedidos de {{ $customer->name }}</h2></div>
                <button type="button" data-modal-close>×</button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Código</th><th>Trabajo</th><th>Servicio</th><th>Fecha</th><th>Total</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        @forelse($customer->orders as $order)
                            <tr>
                                <td class="code">{{ $order->code }}</td>
                                <td>{{ $order->furniture_type }}<div class="muted">{{ $order->quantity }} {{ $order->quantity === 1 ? 'mueble' : 'muebles' }}</div></td>
                                <td>{{ $order->service->name }}</td>
                                <td>{{ $order->created_at->format('d/m/Y') }}</td>
                                <td>Bs {{ number_format($order->total, 2) }}</td>
                                <td><span class="status {{ $order->status }}">{{ ['pending'=>'Pendiente','confirmed'=>'Confirmado','in_progress'=>'Producción','quality'=>'Calidad','ready'=>'Listo','delivered'=>'Entregado','cancelled'=>'Cancelado'][$order->status] }}</span></td>
                                <td><a class="btn btn-outline btn-sm" href="{{ route('orders.show', $order) }}">Ver pedido</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty">Este cliente todavía no realizó pedidos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </dialog>

    <dialog id="customer{{ $customer->id }}">
        <form method="post" action="{{ route('customers.update', $customer) }}" class="modal-body">
            @csrf
            @method('PUT')
            @include('customers.partials.form', ['customer' => $customer])
        </form>
    </dialog>
@endforeach
@endsection
