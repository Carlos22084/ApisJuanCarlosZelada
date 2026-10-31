<?php

use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AiDesignController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WorkerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/servicios', [PublicController::class, 'services'])->name('services');
Route::get('/personalizar', [PublicController::class, 'customize'])->name('customize');
Route::post('/cotizacion', [PublicController::class, 'quote'])->name('quote');
Route::match(['get', 'post'], '/seguimiento', [PublicController::class, 'tracking'])->name('tracking');
Route::post('/asistente', AssistantController::class)->name('assistant');

Route::middleware('guest')->group(function () {
    Route::get('/ingresar', [AuthController::class, 'login'])->name('login');
    Route::post('/ingresar', [AuthController::class, 'authenticate']);
    Route::get('/cliente/ingresar', [AuthController::class, 'clientLogin'])->name('client.login');
    Route::post('/cliente/ingresar', [AuthController::class, 'clientAuthenticate'])->name('client.authenticate');
    Route::get('/registro', [AuthController::class, 'register'])->name('register');
    Route::post('/registro', [AuthController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/personalizar/generar-ia', [AiDesignController::class, 'store'])->middleware('throttle:6,1')->name('customize.ai');
    Route::get('/personalizar/disenos/ultimo', [AiDesignController::class, 'latest'])->name('customize.ai.latest');
    Route::get('/personalizar/disenos/{aiDesign}/original', [AiDesignController::class, 'original'])->name('customize.ai.original');
    Route::get('/personalizar/disenos/{aiDesign}/imagen', [AiDesignController::class, 'image'])->name('customize.ai.image');
    Route::post('/salir', [AuthController::class, 'logout'])->name('logout');
    Route::get('/panel', DashboardController::class)->name('dashboard');
    Route::get('/pedidos', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');

    Route::middleware('role:admin,worker')->group(function () {
        Route::patch('/tareas/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::patch('/pedidos/{order}/tareas', [TaskController::class, 'updateAll'])->name('tasks.update-all');
        Route::get('/materiales', [MaterialController::class, 'index'])->name('materials.index');
        Route::post('/materiales', [MaterialController::class, 'store'])->name('materials.store');
        Route::put('/materiales/{material}', [MaterialController::class, 'update'])->name('materials.update');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/pedidos-nuevo', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/pedidos', [OrderController::class, 'store'])->name('orders.store');
        Route::put('/pedidos/{order}', [OrderController::class, 'update'])->name('orders.update');
        Route::patch('/pedidos/{order}/estado', [OrderController::class, 'status'])->name('orders.status');
        Route::patch('/pedidos/{order}/asignar', [OrderController::class, 'assign'])->name('orders.assign');
        Route::get('/trabajadores', [WorkerController::class, 'index'])->name('workers.index');
        Route::post('/trabajadores', [WorkerController::class, 'store'])->name('workers.store');
        Route::put('/trabajadores/{user}', [WorkerController::class, 'update'])->name('workers.update');
        Route::get('/clientes', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('/clientes', [CustomerController::class, 'store'])->name('customers.store');
        Route::put('/clientes/{user}', [CustomerController::class, 'update'])->name('customers.update');
        Route::get('/reportes', ReportController::class)->name('reports');
    });
});
