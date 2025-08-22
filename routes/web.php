<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TesteN8nController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rotas para teste do n8n
Route::get('/teste-n8n', [TesteN8nController::class, 'index'])->name('teste-n8n');
Route::post('/teste-n8n/executar', [TesteN8nController::class, 'executarTeste'])->name('teste-n8n.executar');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
