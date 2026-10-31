<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function login() { return view('auth.login', ['clientAccess' => false]); }
    public function clientLogin() { return view('auth.login', ['clientAccess' => true]); }
    public function register() { return view('auth.register'); }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'role' => ['required', 'in:admin,worker'],
            'email' => ['required','email'],
            'password' => ['required'],
        ]);
        if (Auth::attempt([...$credentials, 'active' => true], $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'))->with('success', 'Bienvenido al sistema.');
        }
        return back()->withErrors(['email' => 'Los datos ingresados no son correctos o la cuenta está inactiva.'])->onlyInput('email', 'role');
    }

    public function clientAuthenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt([...$credentials, 'role' => 'client', 'active' => true], $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'))->with('success', 'Bienvenido a tu cuenta.');
        }

        return back()
            ->withErrors(['email' => 'Los datos ingresados no son correctos o la cuenta está inactiva.'])
            ->onlyInput('email');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:100'], 'email' => ['required','email','unique:users'],
            'phone' => ['required','string','max:30'], 'address' => ['nullable','string','max:180'],
            'password' => ['required','confirmed', Password::min(8)],
        ]);
        $user = User::create([...$data, 'role' => 'client', 'active' => true]);
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('dashboard')->with('success', 'Su cuenta fue creada correctamente.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

}
