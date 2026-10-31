<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class WorkerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'worker')
            ->withCount('assignedTasks')
            ->with([
                'assignedTasks' => fn ($tasks) => $tasks
                    ->with(['order.service'])
                    ->orderByDesc('created_at'),
            ])
            ->orderBy('name');
        if ($request->filled('search')) {
            $query->where(fn ($worker) => $worker
                ->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('phone', 'like', '%'.$request->search.'%')
                ->orWhere('email', 'like', '%'.$request->search.'%'));
        }

        return view('workers.index', ['workers' => $query->paginate(15)->withQueryString()]);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:180'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required' => 'El nombre completo es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'phone.required' => 'El teléfono es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ])->validateWithBag('createWorker');

        User::create([...$data, 'role' => 'worker', 'active' => true]);

        return back()->with('success', 'Trabajador registrado. Ya puede ingresar con su correo y contraseña.');
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->role === 'worker', 404);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:180'],
            'active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ], [
            'email.email' => 'Ingrese un correo electrónico válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('open_worker_modal', $user->id);
        }

        $data = $validator->validated();
        $data['active'] = $request->boolean('active');

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return back()->with('success', 'Trabajador actualizado.');
    }
}
