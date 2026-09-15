<?php
namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = Material::orderBy('category')->orderBy('name');
        if ($request->filled('search')) $query->where('name','like','%'.$request->search.'%')->orWhere('color','like','%'.$request->search.'%');
        return view('materials.index',['materials'=>$query->get()]);
    }
    public function store(Request $request) { Material::create($this->validated($request)); return back()->with('success','Material registrado.'); }
    public function update(Request $request, Material $material) { $material->update($this->validated($request)); return back()->with('success','Inventario actualizado.'); }
    private function validated(Request $request): array { return $request->validate(['name'=>['required','string','max:100'],'category'=>['required','string','max:60'],'color'=>['required','string','max:50'],'color_hex'=>['required','regex:/^#[0-9A-Fa-f]{6}$/'],'unit'=>['required','string','max:30'],'stock'=>['required','numeric','min:0'],'minimum_stock'=>['required','numeric','min:0'],'unit_price'=>['required','numeric','min:0'],'active'=>['nullable','boolean']]); }
}
