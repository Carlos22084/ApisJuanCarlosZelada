<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function index(Request $request) { $q=User::where('role','client')->withCount('orders')->orderBy('name'); if($request->filled('search')) $q->where(fn($x)=>$x->where('name','like','%'.$request->search.'%')->orWhere('phone','like','%'.$request->search.'%')); return view('customers.index',['customers'=>$q->paginate(15)->withQueryString()]); }
    public function store(Request $request) { $d=$request->validate(['name'=>['required','max:100'],'email'=>['required','email','unique:users'],'phone'=>['required','max:30'],'address'=>['nullable','max:180']]); User::create([...$d,'role'=>'client','active'=>true,'password'=>Hash::make('Cliente123!')]); return back()->with('success','Cliente registrado. Contraseña temporal: Cliente123!'); }
    public function update(Request $request, User $user) { abort_unless($user->role==='client',404); $d=$request->validate(['name'=>['required','max:100'],'email'=>['required','email','unique:users,email,'.$user->id],'phone'=>['required','max:30'],'address'=>['nullable','max:180'],'active'=>['nullable','boolean']]); $user->update($d); return back()->with('success','Cliente actualizado.'); }
}
