@extends('layouts.app')
@section('title','Materiales | A&A')
@section('heading','Materiales e inventario')
@section('eyebrow','Control de existencias')
@section('content')
<div class="toolbar"><form class="searchbar"><input name="search" value="{{ request('search') }}" placeholder="Buscar material o color…"><button class="btn btn-outline">Buscar</button></form><button class="btn btn-primary" data-modal-open="#newMaterial">+ Nuevo material</button></div>
<section class="card"><div class="table-wrap"><table><thead><tr><th>Material</th><th>Categoría</th><th>Color</th><th>Precio unitario</th><th>Existencia</th><th>Nivel</th><th></th></tr></thead><tbody>@foreach($materials as $m)<tr><td><strong>{{ $m->name }}</strong></td><td>{{ $m->category }}</td><td><span class="dot" style="background:{{ $m->color_hex }}"></span>{{ $m->color }}</td><td>Bs {{ number_format($m->unit_price,2) }} / {{ $m->unit }}</td><td><strong>{{ $m->stock }}</strong> {{ $m->unit }}</td><td><span class="status {{ $m->low_stock?'cancelled':'delivered' }}">{{ $m->low_stock?'Stock bajo':'Disponible' }}</span></td><td><button class="btn btn-outline btn-sm" data-modal-open="#material{{ $m->id }}">Editar</button></td></tr>@endforeach</tbody></table></div></section>
<dialog id="newMaterial"><form method="post" action="{{ route('materials.store') }}" class="modal-body">@csrf @include('materials.partials.form',['material'=>null])</form></dialog>
@foreach($materials as $m)<dialog id="material{{ $m->id }}"><form method="post" action="{{ route('materials.update',$m) }}" class="modal-body">@csrf @method('PUT') @include('materials.partials.form',['material'=>$m])</form></dialog>@endforeach
@endsection
