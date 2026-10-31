@extends('layouts.app')
@section('title','Personaliza con IA | A&A')
@section('content')
@php($upholsteryMaterials = $materials->whereIn('category', ['Tela', 'Sintético', 'Cuero']))
<section class="page-hero"><span class="eyebrow">✦ Estudio inteligente A&A</span><h1>Imagina tu mueble antes de tapizarlo</h1><p>Carga una fotografía, elige tela y color, y genera una propuesta visual con inteligencia artificial.</p></section>
<div class="form-wrap ai-customizer"><form method="post" action="{{ route('quote') }}" class="form-grid" id="customizeForm">@csrf
  <div class="customizer-config-column">
  <section class="form-card"><div class="card-head"><div><span class="ai-kicker">PASO 1 · CONFIGURA</span><h2>Detalles del nuevo tapizado</h2></div></div><div class="field-grid">
    <div class="field"><label>Servicio *</label><select name="service_id" id="service_id" required><option value="">Seleccione…</option>@foreach($services as $s)<option value="{{ $s->id }}" data-name="{{ $s->name }}" data-price="{{ $s->base_price }}" @selected(old('service_id',request('service'))==$s->id)>{{ $s->name }}</option>@endforeach</select></div>
    <div class="field"><label for="furniture_type">Tipo de mueble *</label><select name="furniture_type" id="furniture_type" required><option value="">Seleccione un tipo…</option><optgroup label="Sofás y livings"><option value="Sofá de 2 cuerpos" @selected(old('furniture_type')==='Sofá de 2 cuerpos')>Sofá de 2 cuerpos</option><option value="Sofá de 3 cuerpos" @selected(old('furniture_type')==='Sofá de 3 cuerpos')>Sofá de 3 cuerpos</option><option value="Sofá de 4 cuerpos" @selected(old('furniture_type')==='Sofá de 4 cuerpos')>Sofá de 4 cuerpos</option><option value="Living de 3 piezas" @selected(old('furniture_type')==='Living de 3 piezas')>Living de 3 piezas</option></optgroup><optgroup label="Sillones"><option value="Sillón individual" @selected(old('furniture_type')==='Sillón individual')>Sillón individual</option><option value="Sillón reclinable" @selected(old('furniture_type')==='Sillón reclinable')>Sillón reclinable</option></optgroup><optgroup label="Sillas"><option value="Silla de comedor" @selected(old('furniture_type')==='Silla de comedor')>Silla de comedor</option><option value="Juego de sillas" @selected(old('furniture_type')==='Juego de sillas')>Juego de sillas</option><option value="Silla de oficina" @selected(old('furniture_type')==='Silla de oficina')>Silla de oficina</option></optgroup><optgroup label="Otros muebles"><option value="Cabecera de cama" @selected(old('furniture_type')==='Cabecera de cama')>Cabecera de cama</option><option value="Banqueta u otomán" @selected(old('furniture_type')==='Banqueta u otomán')>Banqueta u otomán</option><option value="Puf" @selected(old('furniture_type')==='Puf')>Puf</option></optgroup></select></div>
    <div class="field"><label>Estilo</label><select name="style" id="design_style"><option>Contemporáneo</option><option>Clásico</option><option>Minimalista</option><option>Capitoné</option><option>Rústico</option></select></div>
    <div class="field"><label>Cantidad de muebles *</label><input type="number" name="quantity" id="furniture_quantity" value="{{ old('quantity', 1) }}" min="1" max="100" step="1" required></div>
    <div class="field full"><label for="material_id">Elige el material del tapizado *</label><select name="material_id" id="material_id" required><option value="">Seleccione un material…</option>@foreach($upholsteryMaterials as $m)<option value="{{ $m->id }}" data-name="{{ $m->name }}" data-color="{{ $m->color }}" data-hex="{{ $m->color_hex }}" data-category="{{ $m->category }}" data-texture="{{ $m->texture_type }}" @selected(old('material_id')==$m->id)>{{ $m->name }} · {{ $m->category }} · {{ $m->color }}</option>@endforeach</select><div class="material-preview" id="materialPreview" hidden><span class="material-photo" id="materialPreviewPhoto"><i></i></span><span class="material-info"><small>Muestra seleccionada</small><strong id="materialPreviewName"></strong><span id="materialPreviewDetails"></span></span><b>✓</b></div><small class="selection-help" id="materialHelp">Selecciona una opción para visualizar el material y aplicarlo al modelo.</small></div>
    <div class="field full"><label>Colores disponibles para el material *</label><input type="hidden" name="primary_color" id="primary_color" value="{{ old('primary_color') }}"><div class="color-options">@foreach($upholsteryMaterials as $m)<button type="button" class="color-choice" data-material-id="{{ $m->id }}" data-material-name="{{ $m->name }}" data-color="{{ $m->color }}" data-hex="{{ $m->color_hex }}" hidden><span style="background:{{ $m->color_hex }}"></span>{{ $m->color }}</button>@endforeach</div></div>
    <div class="field full"><label>Describe lo que necesitas *</label><textarea name="description" rows="4" placeholder="Estado actual, cambios deseados y detalles importantes…" required>{{ old('description') }}</textarea></div>
    <div class="field"><label>Fecha deseada</label><input type="date" name="requested_date" min="{{ now()->format('Y-m-d') }}"></div>
    <input type="hidden" name="ai_design_id" id="ai_design_id">
  </div></section>

  <section class="form-card quote-estimate-card" aria-live="polite">
    <div class="quote-estimate-head"><div><span class="ai-kicker">RESUMEN</span><h2>Cotización estimada</h2></div><span class="quote-estimate-badge">Bs</span></div>
    <div class="quote-estimate-lines">
      <div><span>Servicio</span><strong id="quoteService">Por seleccionar</strong></div>
      <div><span>Material</span><strong id="quoteMaterial">Por seleccionar</strong></div>
      <div><span>Precio base por mueble</span><strong id="quoteUnitPrice">Bs 0,00</strong></div>
      <div><span>Cantidad</span><strong id="quoteQuantity">1</strong></div>
    </div>
    <div class="quote-estimate-total"><span>Total estimado</span><strong id="quoteTotal">Bs 0,00</strong></div>
    <p>Estimación calculada con el precio base del servicio. El costo final puede variar después de revisar el estado, tamaño y materiales necesarios para cada mueble.</p>
  </section>
  </div>

  <aside class="form-card ai-studio" data-ai-endpoint="{{ route('customize.ai') }}" data-ai-latest-endpoint="{{ route('customize.ai.latest') }}">
    <div class="card-head"><div><span class="ai-kicker">PASO 2 · VISUALIZA</span><h2>Estudio visual</h2></div></div>
    <div class="ai-tabs"><button type="button" class="active" data-preview-tab="model">Modelo 3D</button><button type="button" data-preview-tab="photo">Foto original</button><button type="button" data-preview-tab="result" disabled>Resultado IA</button></div>
    <div class="ai-stage furniture-3d-stage" data-preview-panel="model">
      <div id="furniture3d" class="furniture-3d-canvas" role="img" aria-label="Modelo tridimensional interactivo del mueble tapizado"></div>
      <div class="furniture-3d-overlay"><span>MODELO LOCAL</span><strong id="furniture3dLabel">Vista tridimensional del tapizado</strong><small>Arrastra para girar · rueda para acercar</small></div>
      <div class="furniture-3d-actions"><button type="button" id="reset3dView" aria-label="Restablecer vista del modelo">⌂ Vista inicial</button><button type="button" id="toggle3dRotation" aria-pressed="false">↻ Giro automático</button></div>
      <div class="furniture-3d-loading" id="furniture3dLoading"><span></span><strong>Preparando el modelo 3D…</strong></div>
    </div>
    <div class="ai-stage photo-stage" data-preview-panel="photo" hidden><img class="preview-image-contain" id="originalPreview" alt="Fotografía original completa del mueble"><div class="empty-photo" id="emptyPhoto">▧<strong>Tu fotografía aparecerá aquí</strong></div></div>
    <div class="ai-stage photo-stage" data-preview-panel="result" hidden><img class="preview-image-contain" id="aiResult" alt="Propuesta completa de tapizado generada con IA"><div class="ai-processing" id="aiProcessing" hidden><span class="magic-orb">✦</span><strong>Creando el diseño personalizado de tu mueble…</strong><small>Puede demorar hasta 2 minutos…</small></div></div>

    <label class="upload-zone" for="furniture_photo"><span>⌁</span><strong>Carga una foto de frente o en ángulo</strong><small>JPG, PNG o WebP · máximo 10 MB</small><input type="file" id="furniture_photo" accept="image/jpeg,image/png,image/webp" hidden></label>
    <div class="field"><label>Indicación adicional para la IA</label><input id="design_notes" maxlength="300" placeholder="Ej. conservar patas de madera oscura"></div>
    @auth
      <button type="button" class="btn btn-ai" id="generateAi">✦ Generar propuesta con IA</button>
      <div class="ai-status" id="aiStatus">Selecciona material, color y fotografía.</div>
      <button class="btn btn-primary submit-order" style="width:100%">Enviar solicitud con este diseño →</button>
    @else
      <p class="hint">Necesitas una cuenta para generar y guardar propuestas con IA.</p><a class="btn btn-primary" style="width:100%" href="{{ route('register') }}">Crear cuenta</a><a class="btn btn-outline" style="width:100%;margin-top:8px" href="{{ route('client.login') }}">Ya tengo cuenta</a>
    @endauth
    <p class="ai-disclaimer">La imagen es una simulación orientativa. El tono y la textura final pueden variar según iluminación, pantalla y disponibilidad del material.</p>
  </aside>
</form></div>
@endsection

@push('scripts')
    @vite('resources/js/furniture-3d.js')
@endpush
