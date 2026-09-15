@extends('layouts.app')
@section('title','Personaliza con IA | A&A')
@section('content')
<section class="page-hero"><span class="eyebrow">✦ Estudio inteligente A&A</span><h1>Imagina tu mueble antes de tapizarlo</h1><p>Carga una fotografía, elige tela y color, y genera una propuesta visual con inteligencia artificial. También puedes girar el modelo conceptual para explorar la combinación.</p></section>
<div class="form-wrap ai-customizer"><form method="post" action="{{ route('quote') }}" class="form-grid" id="customizeForm">@csrf
  <section class="form-card"><div class="card-head"><div><span class="ai-kicker">PASO 1 · CONFIGURA</span><h2>Detalles del nuevo tapizado</h2></div><span class="ai-badge">✦ IA visual</span></div><div class="field-grid">
    <div class="field"><label>Servicio *</label><select name="service_id" required><option value="">Seleccione…</option>@foreach($services as $s)<option value="{{ $s->id }}" @selected(old('service_id',request('service'))==$s->id)>{{ $s->name }} · desde Bs {{ number_format($s->base_price,0) }}</option>@endforeach</select></div>
    <div class="field"><label>Tipo de mueble *</label><input name="furniture_type" id="furniture_type" value="{{ old('furniture_type') }}" placeholder="Ej. Living de 3 piezas" required></div>
    <div class="field"><label>Estilo</label><select name="style" id="design_style"><option>Contemporáneo</option><option>Clásico</option><option>Minimalista</option><option>Capitoné</option><option>Rústico</option></select></div>
    <div class="field"><label>Medidas / cantidad</label><input name="dimensions" value="{{ old('dimensions') }}" placeholder="Ej. 220 × 90 cm"></div>
    <div class="field full"><label>Elige el material del tapizado *</label><input type="hidden" name="material_id" id="material_id"><div class="material-gallery">@foreach($materials->whereIn('category',['Tela','Sintético']) as $m)<button type="button" class="material-sample {{ str_contains(strtolower($m->name),'terciopelo')?'velvet':(str_contains(strtolower($m->name),'lino')?'linen':(str_contains(strtolower($m->name),'chenille')?'chenille':'leather')) }}" data-material-card data-material-id="{{ $m->id }}" data-color="{{ $m->color }}" data-hex="{{ $m->color_hex }}" data-category="{{ $m->category }}" aria-label="Seleccionar {{ $m->name }} en {{ $m->color }}"><span class="material-photo" style="--sample-color:{{ $m->color_hex }}"><i></i></span><span class="material-info"><strong>{{ $m->name }}</strong><small>{{ $m->category }} · {{ $m->color }}</small></span><b>✓</b></button>@endforeach</div><small class="selection-help" id="materialHelp">Selecciona una muestra para aplicarla al modelo.</small></div>
    <div class="field full"><label>Color principal *</label><input type="hidden" name="primary_color" id="primary_color"><div class="color-options">@foreach($materials->unique('color') as $m)<button type="button" class="color-choice" data-color="{{ $m->color }}" data-hex="{{ $m->color_hex }}"><span style="background:{{ $m->color_hex }}"></span>{{ $m->color }}</button>@endforeach</div></div>
    <div class="field full"><label>Describe lo que necesitas *</label><textarea name="description" rows="4" placeholder="Estado actual, cambios deseados y detalles importantes…" required>{{ old('description') }}</textarea></div>
    <div class="field"><label>Fecha deseada</label><input type="date" name="requested_date" min="{{ now()->format('Y-m-d') }}"></div>
    <input type="hidden" name="ai_design_id" id="ai_design_id">
  </div></section>

  <aside class="form-card ai-studio" data-ai-endpoint="{{ route('customize.ai') }}" data-ai-latest-endpoint="{{ route('customize.ai.latest') }}">
    <div class="card-head"><div><span class="ai-kicker">PASO 2 · VISUALIZA</span><h2>Estudio IA + vista 3D</h2></div><span class="live-dot">En vivo</span></div>
    <div class="ai-tabs"><button type="button" class="active" data-preview-tab="model">Modelo 3D</button><button type="button" data-preview-tab="photo">Mi foto</button><button type="button" data-preview-tab="result" disabled>Resultado IA</button></div>
    <div class="ai-stage" data-preview-panel="model">
      <div class="room-scene"><div class="model-sofa" id="modelSofa"><i class="sofa-back"></i><i class="sofa-seat"></i><i class="sofa-arm left"></i><i class="sofa-arm right"></i><i class="sofa-leg one"></i><i class="sofa-leg two"></i></div><span class="floor-shadow"></span></div>
      <div class="rotation-control"><span>↶</span><input type="range" min="-24" max="24" value="0" id="modelRotation" aria-label="Girar modelo"><span>↷</span></div><p class="model-caption">Arrastra el control para girar el modelo conceptual.</p>
    </div>
    <div class="ai-stage photo-stage" data-preview-panel="photo" hidden><img id="originalPreview" alt="Fotografía original del mueble"><div class="empty-photo" id="emptyPhoto">▧<strong>Tu fotografía aparecerá aquí</strong></div></div>
    <div class="ai-stage photo-stage" data-preview-panel="result" hidden><img id="aiResult" alt="Propuesta de tapizado generada con IA"><div class="ai-processing" id="aiProcessing" hidden><span class="magic-orb">✦</span><strong>La IA está diseñando tu tapizado</strong><small>Puede demorar hasta 2 minutos…</small></div></div>

    <label class="upload-zone" for="furniture_photo"><span>⌁</span><strong>Carga una foto de frente o en ángulo</strong><small>JPG, PNG o WebP · máximo 10 MB</small><input type="file" id="furniture_photo" accept="image/jpeg,image/png,image/webp" hidden></label>
    <div class="field"><label>Indicación adicional para la IA</label><input id="design_notes" maxlength="300" placeholder="Ej. conservar patas de madera oscura"></div>
    @auth
      <button type="button" class="btn btn-ai" id="generateAi">✦ Generar propuesta con IA</button>
      <div class="ai-status" id="aiStatus">Selecciona material, color y fotografía.</div>
      <button class="btn btn-primary submit-order" style="width:100%">Enviar solicitud con este diseño →</button>
    @else
      <p class="hint">Necesitas una cuenta para generar y guardar propuestas con IA.</p><a class="btn btn-primary" style="width:100%" href="{{ route('register') }}">Crear cuenta</a><a class="btn btn-outline" style="width:100%;margin-top:8px" href="{{ route('login') }}">Ya tengo cuenta</a>
    @endauth
    <p class="ai-disclaimer">La imagen es una simulación orientativa. El tono y la textura final pueden variar según iluminación, pantalla y disponibilidad del material.</p>
  </aside>
</form></div>
@endsection
