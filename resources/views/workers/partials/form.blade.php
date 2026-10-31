@php($createErrors = $errors->getBag('createWorker'))
@php($showUpdateErrors = $worker && (int) session('open_worker_modal') === $worker->id)
<div class="field-grid">
    <div class="field">
        <label>Nombre completo</label>
        <input name="name" value="{{ old('name', $worker?->name) }}" @class(['is-invalid' => !$worker && $createErrors->has('name')]) required>
        @if(!$worker && $createErrors->has('name'))
            <span class="field-error" role="alert"><strong>!</strong> {{ $createErrors->first('name') }}</span>
        @endif
    </div>
    <div class="field">
        <label>Correo electrónico</label>
        <input
            type="email"
            name="email"
            value="{{ old('email', $worker?->email) }}"
            @if(!$worker && $createErrors->has('email'))
                class="is-invalid"
                aria-invalid="true"
                aria-describedby="new-worker-email-error"
            @endif
            required
        >
        @if(!$worker && $createErrors->has('email'))
            <span class="field-error" id="new-worker-email-error" role="alert">
                <strong>!</strong> {{ $createErrors->first('email') }}
            </span>
        @endif
    </div>
    <div class="field">
        <label>Teléfono</label>
        <input name="phone" value="{{ old('phone', $worker?->phone) }}" @class(['is-invalid' => !$worker && $createErrors->has('phone')]) required>
        @if(!$worker && $createErrors->has('phone'))
            <span class="field-error" role="alert"><strong>!</strong> {{ $createErrors->first('phone') }}</span>
        @endif
    </div>
    <div class="field">
        <label>Dirección</label>
        <input name="address" value="{{ old('address', $worker?->address) }}" @class(['is-invalid' => !$worker && $createErrors->has('address')])>
        @if(!$worker && $createErrors->has('address'))
            <span class="field-error" role="alert"><strong>!</strong> {{ $createErrors->first('address') }}</span>
        @endif
    </div>
    @if($worker)
        <div class="field">
            <label>Nueva contraseña</label>
            <input
                type="password"
                name="password"
                minlength="8"
                @if($showUpdateErrors && $errors->has('password')) class="is-invalid" aria-invalid="true" @endif
            >
            @if($showUpdateErrors && $errors->has('password'))
                <span class="field-error" role="alert"><strong>!</strong> {{ $errors->first('password') }}</span>
            @endif
        </div>
        <div class="field">
            <label>Confirmar nueva contraseña</label>
            <input
                type="password"
                name="password_confirmation"
                minlength="8"
                @if($showUpdateErrors && $errors->has('password')) class="is-invalid" aria-invalid="true" @endif
            >
        </div>
        <p class="password-update-hint full">Deje estos campos vacíos si no desea cambiar la contraseña.</p>
        <div class="field full"><label><input type="checkbox" name="active" value="1" @checked($worker->active)> Cuenta activa</label></div>
    @else
        <div class="field">
            <label>Contraseña</label>
            <input type="password" name="password" minlength="8" @class(['is-invalid' => $createErrors->has('password')]) required>
            @if($createErrors->has('password'))
                <span class="field-error" role="alert"><strong>!</strong> {{ $createErrors->first('password') }}</span>
            @endif
        </div>
        <div class="field">
            <label>Confirmar contraseña</label>
            <input type="password" name="password_confirmation" minlength="8" @class(['is-invalid' => $createErrors->has('password')]) required>
        </div>
    @endif
</div>
<button class="btn btn-primary" style="margin-top:18px">{{ $worker ? 'Guardar cambios' : 'Registrar trabajador' }}</button>
