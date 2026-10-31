@extends('layouts.app')

@section('title', ($clientAccess ? 'Mi cuenta' : 'Acceso al sistema').' | A&A')

@section('content')
<section class="auth-page">
    <form class="auth-card" method="post" action="{{ $clientAccess ? route('client.authenticate') : route('login') }}">
        @csrf
        <span class="eyebrow">{{ $clientAccess ? 'Acceso para clientes' : 'Acceso al sistema' }}</span>
        <h1>{{ $clientAccess ? 'Ingresa a tu cuenta' : 'Bienvenido' }}</h1>
        <p>{{ $clientAccess ? 'Consulta tus pedidos y crea nuevas solicitudes personalizadas.' : 'Selecciona tu tipo de cuenta e ingresa tus credenciales.' }}</p>

        @if($errors->any())
            <div class="alert danger" role="alert">
                <strong>No se pudo iniciar sesión:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @unless($clientAccess)
            <div class="field">
                <label>Ingresar como</label>
                <div class="login-role-grid">
                    @foreach(['admin' => ['Administrador', 'Gestiona y asigna trabajos'], 'worker' => ['Trabajador', 'Consulta sus tareas']] as $value => [$label, $description])
                        <label class="login-role-option">
                            <input type="radio" name="role" value="{{ $value }}" @checked(old('role', 'admin') === $value) required>
                            <span><strong>{{ $label }}</strong><small>{{ $description }}</small></span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endunless

        <div class="field">
            <label>Correo electrónico</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   oninvalid="this.setCustomValidity('Correo electrónico incorrecto')"
                   oninput="this.setCustomValidity('')">
        </div>
        <div class="field">
            <label>Contraseña</label>
            <input type="password" name="password" required>
        </div>
        <label class="muted"><input type="checkbox" name="remember"> Mantener mi sesión iniciada</label>
        <button class="btn btn-primary" style="margin-top:18px">Ingresar →</button>
        @if($clientAccess)
            <div class="auth-links">¿Aún no tienes cuenta? <a class="code" href="{{ route('register') }}">Regístrate</a></div>
        @else
            <div class="auth-links"><a class="code" href="{{ route('home') }}">← Volver al sitio web</a></div>
        @endif
    </form>
</section>
@endsection
