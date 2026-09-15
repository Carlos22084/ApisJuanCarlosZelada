@extends('layouts.app')
@section('title','Servicios | A&A Multiservicios')
@section('content')
<section class="page-hero"><span class="eyebrow">Nuestro oficio</span><h1>Servicios de tapicería</h1><p>Soluciones cuidadas para muebles del hogar, desde la restauración estructural hasta el acabado que define tu espacio.</p></section>
<section class="section"><div class="service-grid">@foreach($services as $service)<article class="service-card"><div class="service-icon">◆</div><h3>{{ $service->name }}</h3><p>{{ $service->description }}</p><div><span class="price">Desde Bs {{ number_format($service->base_price,0) }}</span><p class="muted">Tiempo estimado: {{ $service->estimated_days }} días</p></div><a class="btn btn-outline" href="{{ route('customize',['service'=>$service->id]) }}">Personalizar</a></article>@endforeach</div></section>
@endsection
