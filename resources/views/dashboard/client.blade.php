@extends('layouts.app')
@section('title','Mis pedidos | A&A')
@section('heading','Hola, '.auth()->user()->name)
@section('eyebrow','Tu espacio personal')
@section('content')
<div class="card-head"><div><h2>Mis pedidos</h2><p class="muted">Consulta el avance y los detalles de tus trabajos.</p></div><a class="btn btn-primary" href="{{ route('customize') }}">+ Nueva solicitud</a></div>
<div class="service-grid">@forelse($orders as $order)<article class="card"><div class="card-head"><span class="code">{{ $order->code }}</span><span class="status {{ $order->status }}">{{ ['pending'=>'Pendiente','confirmed'=>'Confirmado','in_progress'=>'Producción','quality'=>'Calidad','ready'=>'Listo','delivered'=>'Entregado','cancelled'=>'Cancelado'][$order->status] }}</span></div><h3>{{ $order->furniture_type }}</h3><p class="muted">{{ $order->service->name }}<br>{{ $order->material?->name ?? 'Material por definir' }}</p><div class="progress"><span style="width:{{ $order->progress }}%"></span></div><p class="muted">{{ $order->progress }}% · Entrega {{ $order->delivery_date?->format('d/m/Y') ?? 'por confirmar' }}</p><a class="btn btn-outline" href="{{ route('orders.show',$order) }}">Ver detalles</a></article>@empty<div class="card empty"><h3>Aún no tienes pedidos</h3><p>Crea tu primera solicitud personalizada.</p></div>@endforelse</div>
@endsection
