<?php
namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ProductionTask;
use App\Models\Service;
use App\Models\User;
use App\Services\OrderEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['client','service','material'])->latest();
        if (Auth::user()->role === 'client') $query->where('client_id', Auth::id());
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) $query->where(function ($q) use ($request) {
            $q->where('code', 'like', '%'.$request->search.'%')->orWhere('furniture_type', 'like', '%'.$request->search.'%')->orWhereHas('client', fn($c) => $c->where('name','like','%'.$request->search.'%'));
        });
        return view('orders.index', ['orders' => $query->paginate(12)->withQueryString()]);
    }

    public function create()
    {
        return view('orders.form', ['clients' => User::where('role','client')->where('active',true)->orderBy('name')->get(), 'services' => Service::where('active',true)->get(), 'materials' => Material::where('active',true)->get()]);
    }

    public function store(Request $request, OrderEmailService $emails)
    {
        $data = $this->validated($request);
        $data['code'] = 'AYA-'.now()->format('ym').'-'.strtoupper(Str::random(5));
        $data['total'] = max(0, $data['subtotal'] - ($data['discount'] ?? 0));
        $order = Order::create($data);
        foreach (['Evaluación y desarmado','Corte de materiales','Costura','Tapizado','Acabado y control de calidad'] as $index => $name) ProductionTask::create(['order_id'=>$order->id,'name'=>$name,'sequence'=>$index+1]);
        OrderStatusHistory::create(['order_id'=>$order->id,'user_id'=>Auth::id(),'status'=>$order->status,'comment'=>'Pedido registrado.']);
        $emailResult = $emails->sendCreated($order);

        $response = redirect()->route('orders.show',$order)
            ->with('success','Pedido creado correctamente.');

        if ($emailResult === OrderEmailService::SKIPPED) {
            $response->with('info', 'El correo no se envió porque Resend todavía no está configurado.');
        } elseif ($emailResult === OrderEmailService::FAILED) {
            $response->with('info', 'El pedido se guardó, pero Resend no pudo enviar el correo. Revise el registro del sistema.');
        }

        return $response;
    }

    public function show(Order $order)
    {
        if (Auth::user()->role === 'client') abort_unless($order->client_id === Auth::id(), 403);
        $order->load(['client','service','material','aiDesign','tasks.worker','history.user']);
        $workers = User::where('role','worker')->where('active',true)->get();
        return view('orders.show', compact('order','workers'));
    }

    public function update(Request $request, Order $order)
    {
        $data = $this->validated($request);
        $data['total'] = max(0, $data['subtotal'] - ($data['discount'] ?? 0));
        $order->update($data);
        return back()->with('success','Datos del pedido actualizados.');
    }

    public function status(Request $request, Order $order, OrderEmailService $emails)
    {
        $data = $request->validate(['status'=>['required','in:pending,confirmed,in_progress,quality,ready,delivered,cancelled'],'comment'=>['nullable','string','max:300']]);
        $order->update(['status'=>$data['status']]);
        OrderStatusHistory::create(['order_id'=>$order->id,'user_id'=>Auth::id(),'status'=>$data['status'],'comment'=>$data['comment'] ?? 'Estado actualizado.']);
        $emailResult = $emails->sendStatusChanged($order, $data['comment'] ?? null);

        $response = back()->with('success','Estado actualizado y registrado en el historial.');

        if ($emailResult === OrderEmailService::SKIPPED) {
            $response->with('info', 'El correo no se envió porque Resend todavía no está configurado.');
        } elseif ($emailResult === OrderEmailService::FAILED) {
            $response->with('info', 'El estado se guardó, pero Resend no pudo enviar el correo. Revise el registro del sistema.');
        }

        return $response;
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'client_id'=>['required','exists:users,id'], 'service_id'=>['required','exists:services,id'], 'material_id'=>['nullable','exists:materials,id'],
            'status'=>['required','in:pending,confirmed,in_progress,quality,ready,delivered,cancelled'], 'priority'=>['required','in:normal,high,urgent'],
            'furniture_type'=>['required','string','max:100'], 'style'=>['nullable','string','max:80'], 'primary_color'=>['nullable','string','max:50'],
            'dimensions'=>['nullable','string','max:100'], 'description'=>['required','string','max:1500'], 'requested_date'=>['nullable','date'], 'delivery_date'=>['nullable','date'],
            'subtotal'=>['required','numeric','min:0'], 'discount'=>['nullable','numeric','min:0'], 'advance'=>['nullable','numeric','min:0'], 'internal_notes'=>['nullable','string','max:1000'],
        ]);
    }
}
