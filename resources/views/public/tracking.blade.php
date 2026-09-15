@extends('layouts.app')
@section('title','Seguimiento de pedido | A&A')
@section('content')
<section class="page-hero"><span class="eyebrow">Tu pedido, siempre visible</span><h1>Consulta el avance</h1><p>Ingresa el código que recibiste al registrar tu pedido.</p></section>
<div class="tracking-box"><form method="get" class="tracking-search"><input name="code" value="{{ request('code') }}" placeholder="Ej. AYA-2608-K7P2A" required><button class="btn btn-primary">Consultar</button></form>
@if(request()->filled('code') && !$order)<div class="alert danger" style="margin:20px 0">No encontramos un pedido con ese código. Verifica los caracteres e intenta nuevamente.</div>@endif
@if($order)<section class="card" style="margin-top:24px"><div class="card-head"><div><span class="code">{{ $order->code }}</span><h2>{{ $order->furniture_type }}</h2></div><span class="status {{ $order->status }}">{{ ['pending'=>'Pendiente','confirmed'=>'Confirmado','in_progress'=>'En producción','quality'=>'Control de calidad','ready'=>'Listo para entregar','delivered'=>'Entregado','cancelled'=>'Cancelado'][$order->status] }}</span></div><p class="muted">{{ $order->service->name }} · Entrega prevista: {{ $order->delivery_date?->format('d/m/Y') ?? 'Por confirmar' }}</p><div class="progress"><span style="width:{{ $order->progress }}%"></span></div><p class="muted">{{ $order->progress }}% de avance</p><ul class="timeline">@foreach($order->tasks as $task)<li class="{{ $task->status==='completed'?'done':'' }}"><strong>{{ $task->name }}</strong><br><span class="muted">{{ ['pending'=>'Pendiente','in_progress'=>'En proceso','completed'=>'Completado'][$task->status] }}</span></li>@endforeach</ul></section>@endif
</div>
@endsection
