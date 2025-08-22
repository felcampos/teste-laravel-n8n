<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\N8nController;

// Rota padrão do Laravel Sanctum
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Rotas para integração com n8n
|--------------------------------------------------------------------------
*/

// Grupo de rotas para integração com n8n
Route::prefix('n8n')->group(function () {
    
    // Rota para RECEBER dados do n8n (webhook)
    // O n8n vai chamar esta URL quando quiser enviar dados
    Route::post('/webhook', [N8nController::class, 'receberDados']);
    
    // Rota para ENVIAR dados para o n8n
    // Você chama esta rota quando quiser enviar dados para o n8n
    Route::post('/enviar', [N8nController::class, 'enviarDados']);
    
    // Rota para testar se está funcionando
    Route::get('/teste', [N8nController::class, 'teste']);
    
    // Rota para o n8n buscar dados de usuários (exemplo)
    Route::get('/usuarios', [N8nController::class, 'obterUsuarios']);
    
    // Rotas de teste para integração
    Route::get('/testar-envio', [N8nController::class, 'testarEnvio']);
    Route::get('/testar-envio-usuarios', [N8nController::class, 'testarEnvioUsuarios']);
});