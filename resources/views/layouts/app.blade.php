<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'A&A Multiservicios')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
@php($isApp = auth()->check() && !request()->routeIs('home','services','customize','tracking','login','register'))
<body class="{{ $isApp ? 'app-shell' : 'public-shell' }}">
@if($isApp)
    <aside class="sidebar" id="sidebar">
        <a class="brand brand-side" href="{{ route('dashboard') }}"><span class="brand-mark">A&A</span><span><strong>Multiservicios</strong><small>Gestión de tapicería</small></span></a>
        <nav class="side-nav">
            <a class="{{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}"><span class="nav-icon">⌂</span> Resumen</a>
            <a class="{{ request()->routeIs('orders.*')?'active':'' }}" href="{{ route('orders.index') }}"><span class="nav-icon">▤</span> Pedidos</a>
            @if(auth()->user()->role !== 'client')<a class="{{ request()->routeIs('materials.*')?'active':'' }}" href="{{ route('materials.index') }}"><span class="nav-icon">◆</span> Materiales</a>@endif
            @if(auth()->user()->role === 'admin')
                <a class="{{ request()->routeIs('customers.*')?'active':'' }}" href="{{ route('customers.index') }}"><span class="nav-icon">♙</span> Clientes</a>
                <a class="{{ request()->routeIs('reports')?'active':'' }}" href="{{ route('reports') }}"><span class="nav-icon">▥</span> Reportes</a>
            @endif
            @if(auth()->user()->role === 'client')<a href="{{ route('customize') }}"><span class="nav-icon">✦</span> Nuevo pedido</a>@endif
        </nav>
        <div class="sidebar-foot">
            <div class="user-chip"><span>{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>{{ ['admin'=>'Administrador','worker'=>'Trabajador','client'=>'Cliente'][auth()->user()->role] }}</small></div></div>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="link-button">↪ Cerrar sesión</button></form>
        </div>
    </aside>
    <div class="app-main">
        <header class="topbar"><button class="menu-button" data-menu>☰</button><div><small>@yield('eyebrow','Panel de control')</small><h1>@yield('heading','Resumen')</h1></div><div class="topbar-actions"><span class="welcome-pill">✦ {{ explode(' ', auth()->user()->name)[0] }}</span><a class="top-link" href="{{ route('home') }}">Ver sitio web ↗</a></div></header>
        <main class="content">
            @include('partials.alerts')
            @yield('content')
        </main>
    </div>
@else
    <header class="public-nav">
        <a class="brand" href="{{ route('home') }}"><span class="brand-mark">A&A</span><span><strong>Multiservicios</strong><small>Tapicería & diseño</small></span></a>
        <button class="menu-button" data-public-menu>☰</button>
        <nav data-public-nav><a href="{{ route('home') }}">Inicio</a><a href="{{ route('services') }}">Servicios</a><a href="{{ route('customize') }}">Personalizar</a><a href="{{ route('tracking') }}">Seguimiento</a>@auth<a class="btn btn-outline" href="{{ route('dashboard') }}">Mi panel</a>@else<a class="btn btn-outline" href="{{ route('login') }}">Ingresar</a>@endauth</nav>
    </header>
    @include('partials.alerts')
    <main>@yield('content')</main>
    <footer class="public-footer"><div class="brand"><span class="brand-mark">A&A</span><span><strong>Multiservicios</strong><small>El arte de renovar tus espacios.</small></span></div><div><strong>Contacto</strong><p>Cochabamba, Bolivia · +591 70700001</p></div><div><strong>Horario</strong><p>Lun–Sáb · 08:30 a 18:30</p></div></footer>
@endif

<button class="assistant-toggle" data-assistant-toggle aria-label="Abrir asistente">✦</button>
<section class="assistant" data-assistant hidden>
    <header><span>✦</span><div><strong>Asistente A&A</strong><small>En línea · respuesta inmediata</small></div><button data-assistant-close>×</button></header>
    <div class="assistant-messages" data-assistant-messages><p class="bot">¡Hola! Puedo ayudarte con materiales, colores, precios, tiempos o el seguimiento de tu pedido.</p></div>
    <form data-assistant-form><input name="message" placeholder="Escribe tu consulta…" autocomplete="off"><button>➤</button></form>
</section>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
@stack('scripts')
</body>
</html>
