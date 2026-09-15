@if(session('success'))<div class="alert success">✓ {{ session('success') }}</div>@endif
@if(session('info'))<div class="alert info">ℹ {{ session('info') }}</div>@endif
@if($errors->any())<div class="alert danger"><strong>Revise la información:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
