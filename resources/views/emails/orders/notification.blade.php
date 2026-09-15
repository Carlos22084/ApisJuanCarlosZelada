<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0;background:#f5eee7;color:#3d2a21;font-family:Arial,sans-serif">
    <div style="padding:32px 14px">
        <div style="max-width:620px;margin:0 auto;background:#fffaf5;border:1px solid #ead7c5;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(83,49,29,.12)">
            <div style="padding:25px 30px;background:linear-gradient(135deg,#4b291c,#815037);color:#fff">
                <div style="font-size:12px;letter-spacing:1.5px;text-transform:uppercase;color:#e8cdb4">A&amp;A Multiservicios</div>
                <h1 style="margin:8px 0 0;font-family:Georgia,serif;font-size:27px">{{ $heading }}</h1>
            </div>

            <div style="padding:30px">
                <p style="margin-top:0;font-size:16px">Hola, <strong>{{ $order->client->name }}</strong>:</p>
                <p style="line-height:1.65;color:#665249">{{ $intro }}</p>

                <div style="margin:24px 0;padding:18px;background:#f2e4d7;border-radius:13px">
                    <table style="width:100%;border-collapse:collapse;font-size:14px">
                        <tr><td style="padding:7px 0;color:#856d61">Código</td><td style="padding:7px 0;text-align:right;font-weight:bold">{{ $order->code }}</td></tr>
                        <tr><td style="padding:7px 0;color:#856d61">Trabajo</td><td style="padding:7px 0;text-align:right;font-weight:bold">{{ $order->furniture_type }}</td></tr>
                        <tr><td style="padding:7px 0;color:#856d61">Servicio</td><td style="padding:7px 0;text-align:right;font-weight:bold">{{ $order->service?->name ?? 'Tapicería' }}</td></tr>
                        <tr><td style="padding:7px 0;color:#856d61">Estado</td><td style="padding:7px 0;text-align:right;font-weight:bold;color:#75452e">{{ $statusLabel }}</td></tr>
                        @if($order->delivery_date)
                            <tr><td style="padding:7px 0;color:#856d61">Entrega estimada</td><td style="padding:7px 0;text-align:right;font-weight:bold">{{ $order->delivery_date->format('d/m/Y') }}</td></tr>
                        @endif
                        <tr><td style="padding:7px 0;color:#856d61">Total</td><td style="padding:7px 0;text-align:right;font-weight:bold">Bs {{ number_format((float) $order->total, 2, ',', '.') }}</td></tr>
                        <tr><td style="padding:7px 0;color:#856d61">Saldo pendiente</td><td style="padding:7px 0;text-align:right;font-weight:bold">Bs {{ number_format($order->balance, 2, ',', '.') }}</td></tr>
                    </table>
                </div>

                @if($comment)
                    <div style="margin:20px 0;padding:14px 16px;border-left:4px solid #b77a4c;background:#fff7ef;color:#60493d">
                        <strong>Observación del taller:</strong><br>{{ $comment }}
                    </div>
                @endif

                <p style="line-height:1.65;color:#665249">Puedes consultar el avance de tu pedido en la sección de seguimiento utilizando el código <strong>{{ $order->code }}</strong>.</p>

                <a href="{{ route('tracking', ['code' => $order->code]) }}" style="display:inline-block;margin-top:8px;padding:12px 20px;border-radius:10px;background:#75452e;color:#fff;text-decoration:none;font-weight:bold">Ver seguimiento</a>
            </div>

            <div style="padding:18px 30px;background:#eee0d2;color:#7a6256;font-size:12px;line-height:1.5">
                Este es un correo automático de A&amp;A Multiservicios. Si tienes consultas, comunícate directamente con el taller.
            </div>
        </div>
    </div>
</body>
</html>
