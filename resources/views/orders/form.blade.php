@extends('layouts.app')

@section('title', 'Nuevo pedido | A&A')
@section('heading', 'Registrar pedido')
@section('eyebrow', 'Nueva orden de trabajo')

@section('content')
<form method="post" action="{{ route('orders.store') }}" class="form-card">
    @csrf
    <div class="card-head">
        <div>
            <h2>Datos esenciales del pedido</h2>
            <p class="muted">Registra directamente el trabajo acordado con el cliente.</p>
        </div>
        <a class="btn btn-outline" href="{{ route('orders.index') }}">Cancelar</a>
    </div>

    <div class="field-grid">
        <div class="field">
            <label>Cliente *</label>
            <select name="client_id" required>
                <option value="">Seleccione…</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>
                        {{ $client->name }} · {{ $client->phone }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label>Servicio *</label>
            <select name="service_id" required>
                <option value="">Seleccione…</option>
                @foreach($services as $service)
                    <option value="{{ $service->id }}" @selected(old('service_id') == $service->id)>{{ $service->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label>Tipo de mueble *</label>
            <input name="furniture_type" value="{{ old('furniture_type') }}" placeholder="Ej. Sofá, sillón o silla" required>
        </div>

        <div class="field">
            <label>Prioridad *</label>
            <select name="priority" required>
                <option value="normal" @selected(old('priority', 'normal') === 'normal')>Normal</option>
                <option value="high" @selected(old('priority') === 'high')>Alta</option>
                <option value="urgent" @selected(old('priority') === 'urgent')>Urgente</option>
            </select>
        </div>

        <div class="field full">
            <label>Descripción del trabajo *</label>
            <textarea name="description" rows="4" placeholder="Describe el trabajo que se realizará…" required>{{ old('description') }}</textarea>
        </div>

        <div class="field">
            <label>Fecha de entrega</label>
            <input type="date" name="delivery_date" value="{{ old('delivery_date') }}">
        </div>

        <div class="field">
            <label>Precio acordado (Bs) *</label>
            <input type="number" step="0.01" min="0" name="subtotal" value="{{ old('subtotal', 0) }}" required>
        </div>

        <div class="field">
            <label>Adelanto recibido (Bs)</label>
            <input type="number" step="0.01" min="0" name="advance" value="{{ old('advance', 0) }}">
        </div>

        <div class="field full">
            <label>Notas internas</label>
            <textarea name="internal_notes" rows="3" placeholder="Observaciones para el personal del taller…">{{ old('internal_notes') }}</textarea>
        </div>

        <input type="hidden" name="status" value="pending">
        <input type="hidden" name="discount" value="0">

        <div class="field full">
            <button class="btn btn-primary">Guardar pedido y crear etapas →</button>
        </div>
    </div>
</form>
@endsection
