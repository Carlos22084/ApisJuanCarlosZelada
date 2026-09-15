<?php
namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Order;
use App\Models\ProductionTask;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        if ($user->role === 'client') {
            $orders = Order::with(['service','material'])->where('client_id', $user->id)->latest()->get();
            return view('dashboard.client', compact('orders'));
        }
        $stats = [
            'active' => Order::whereNotIn('status', ['delivered','cancelled'])->count(),
            'month' => Order::whereMonth('created_at', now()->month)->count(),
            'revenue' => Order::whereMonth('created_at', now()->month)->sum('total'),
            'clients' => User::where('role', 'client')->count(),
        ];
        $recent = Order::with(['client','service'])->latest()->take(6)->get();
        $lowStock = Material::whereColumn('stock', '<=', 'minimum_stock')->get();
        $tasks = ProductionTask::with(['order','worker'])->where('status', '!=', 'completed')->orderBy('sequence')->take(8)->get();
        $byStatus = Order::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status');
        return view('dashboard.admin', compact('stats','recent','lowStock','tasks','byStatus'));
    }
}
