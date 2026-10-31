<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:seed-demo-if-empty', function () {
    if (! filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOL)) {
        $this->info('Carga de datos demostrativos desactivada.');

        return 0;
    }

    if (! Schema::hasTable('users') || \App\Models\User::query()->exists()) {
        $this->info('La base de datos ya contiene usuarios; no se modificaron sus datos.');

        return 0;
    }

    Artisan::call('db:seed', ['--force' => true]);
    $this->info('Datos demostrativos cargados por primera vez.');

    return 0;
})->purpose('Carga los datos demostrativos una sola vez en una base vacía');
