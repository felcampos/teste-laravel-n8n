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

Route::prefix('n8n')->group(function () {
    
    // ===== ROTAS PÚBLICAS (sem autenticação) =====
    // Rotas que o próprio Laravel chama ou para testes básicos
    
    // Rota para ENVIAR dados para o n8n
    Route::post('/enviar', [N8nController::class, 'enviarDados']);
    
    // Rota de teste básica
    Route::get('/teste', [N8nController::class, 'teste']);

    // Rotas de teste para integração
    Route::get('/testar-envio', [N8nController::class, 'testarEnvio']);
    Route::get('/testar-envio-usuarios', [N8nController::class, 'testarEnvioUsuarios']);
    
    
    // ===== ROTAS PROTEGIDAS (com token obrigatório) =====
    // Rotas que o n8n vai chamar no Laravel - TODAS protegidas por token
    
    Route::middleware(['n8n.auth'])->group(function () {
        
        // Processar mensagem do usuário (criar usuário + salvar mensagem)
        Route::post('/processar-mensagem', [N8nController::class, 'processarMensagem']);

        // Capturar e salvar email do lead
        Route::post('/capturar-email', [N8nController::class, 'capturarEmail']);
        
        // Verificar se usuário existe por telefone/email
        Route::post('/verificar-usuario', [N8nController::class, 'verificarUsuario']);
        
        // Rota para RECEBER dados do n8n (webhook)
        Route::post('/webhook', [N8nController::class, 'receberDados']);
        
        // Obter dados de usuários
        Route::get('/usuarios', [N8nController::class, 'obterUsuarios']);
        
        // Obter estatísticas dos usuários WhatsApp
        Route::get('/estatisticas', [N8nController::class, 'obterEstatisticas']);
        
        // Obter histórico de conversa
        Route::get('/historico', [N8nController::class, 'obterHistorico']);
        
    });
});