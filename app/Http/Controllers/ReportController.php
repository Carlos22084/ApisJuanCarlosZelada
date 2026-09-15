<?php
namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $from=$request->date('from') ?? now()->startOfMonth(); $to=$request->date('to') ?? now()->endOfMonth();
        $orders=Order::with(['client','service'])->whereBetween('created_at',[$from->startOfDay(),$to->endOfDay()])->get();
        $byStatus=$orders->groupBy('status')->map->count(); $byService=$orders->groupBy(fn($o)=>$o->service->name)->map->count();
        return view('reports.index',compact('orders','byStatus','byService','from','to'));
    }
}
