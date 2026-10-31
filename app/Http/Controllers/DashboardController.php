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
            $orders = Order::with(['service','material'])
                ->withCount([
                    'tasks',
                    'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'completed'),
                ])
                ->where('client_id', $user->id)
                ->latest()
                ->get();
            return view('dashboard.client', compact('orders'));
        }
        if ($user->role === 'worker') {
            $tasks = ProductionTask::with(['order.client', 'order.service'])
                ->where('assigned_to', $user->id)
                ->orderByRaw("case status when 'in_progress' then 1 when 'pending' then 2 else 3 end")
                ->orderBy('sequence')
                ->get();
            $stats = [
                'pending' => $tasks->where('status', 'pending')->count(),
                'in_progress' => $tasks->where('status', 'in_progress')->count(),
                'completed' => $tasks->where('status', 'completed')->count(),
                'orders' => $tasks->pluck('order_id')->unique()->count(),
            ];
            return view('dashboard.worker', compact('tasks', 'stats'));
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
