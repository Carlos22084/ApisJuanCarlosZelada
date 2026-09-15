<?php
namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\AiDesign;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ProductionTask;
use App\Models\Service;
use App\Services\OrderEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PublicController extends Controller
{
    public function home() { return view('public.home', ['services' => Service::where('active', true)->take(4)->get()]); }
    public function services() { return view('public.services', ['services' => Service::where('active', true)->get()]); }
    public function customize() { return view('public.customize', ['services' => Service::where('active', true)->get(), 'materials' => Material::where('active', true)->get()]); }

    public function quote(Request $request, OrderEmailService $emails)
    {
        if (!Auth::check()) return redirect()->route('login')->with('info', 'Inicie sesión o cree una cuenta para enviar su solicitud personalizada.');
        $data = $request->validate([
            'service_id' => ['required','exists:services,id'], 'material_id' => ['nullable','exists:materials,id'],
            'furniture_type' => ['required','string','max:100'], 'style' => ['nullable','string','max:80'],
            'primary_color' => ['nullable','string','max:50'], 'dimensions' => ['nullable','string','max:100'],
            'description' => ['required','string','max:1500'], 'requested_date' => ['nullable','date','after_or_equal:today'],
            'ai_design_id' => ['nullable','exists:ai_designs,id'],
        ]);
        if (!empty($data['ai_design_id'])) {
            $design = AiDesign::whereKey($data['ai_design_id'])->where('user_id', Auth::id())->firstOrFail();
            $data['reference_image'] = $design->original_path;
        }
        $service = Service::findOrFail($data['service_id']);
        $order = Order::create([...$data, 'client_id' => Auth::id(), 'code' => 'AYA-'.now()->format('ym').'-'.strtoupper(Str::random(5)), 'subtotal' => $service->base_price, 'total' => $service->base_price]);
        foreach (['Evaluación y desarmado','Corte de materiales','Costura','Tapizado','Acabado y control de calidad'] as $index => $task) {
            ProductionTask::create(['order_id' => $order->id, 'name' => $task, 'sequence' => $index + 1]);
        }
        OrderStatusHistory::create(['order_id' => $order->id, 'user_id' => Auth::id(), 'status' => 'pending', 'comment' => 'Solicitud registrada desde el personalizador.']);
        $emailResult = $emails->sendCreated($order);
        $response = redirect()->route('orders.show', $order)->with('success', 'Solicitud enviada. Nos comunicaremos para confirmar la cotización.');

        if ($emailResult === OrderEmailService::SKIPPED) {
            $response->with('info', 'La solicitud se guardó, pero el correo no se envió porque Resend todavía no está configurado.');
        } elseif ($emailResult === OrderEmailService::FAILED) {
            $response->with('info', 'La solicitud se guardó, pero Resend no pudo enviar el correo.');
        }

        return $response;
    }

    public function tracking(Request $request)
    {
        $order = null;
        if ($request->filled('code')) {
            $request->validate(['code' => ['required','string','max:30']]);
            $order = Order::with(['service','tasks','history'])->where('code', strtoupper(trim($request->code)))->first();
        }
        return view('public.tracking', compact('order'));
    }
}
