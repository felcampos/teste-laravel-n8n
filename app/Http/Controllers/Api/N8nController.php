<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\WhatsAppService;

class N8nController extends Controller
{
    /**
     * Processar dados de usuário vindos do n8n
     */
    private function processarDadosUsuario(array $dados): array
    {
        // Verificar se são dados de WhatsApp
        $isWhatsApp = isset($dados['telefone']) || 
                     isset($dados['from']) || 
                     isset($dados['mensagem']) || 
                     isset($dados['message']);
        
        if ($isWhatsApp) {
            return [
                'tipo' => 'whatsapp',
                'resultado' => $this->whatsappService->processarMensagemRecebida($dados)
            ];
        }
        
        return [
            'tipo' => 'dados_gerais',
            'processado' => false,
            'motivo' => 'Tipo de dados não reconhecido'
        ];
    }
    
    /**
     * Obter histórico de conversa
     */
    public function obterHistorico(Request $request)
    {
        try {
            $telefone = $request->input('telefone');
            
            if (!$telefone) {
                return response()->json([
                    'status' => 'erro',
                    'mensagem' => 'Telefone é obrigatório'
                ], 400);
            }
            
            $historico = $this->whatsappService->obterHistoricoConversa($telefone);
            
            return response()->json([
                'status' => 'sucesso',
                'telefone' => $telefone,
                'mensagens' => $historico
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'erro',
                'mensagem' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obter estatísticas dos usuários WhatsApp
     */
    public function obterEstatisticas()
    {
        try {
            $estatisticas = $this->whatsappService->obterEstatisticas();
            
            return response()->json([
                'status' => 'sucesso',
                'estatisticas' => $estatisticas
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'erro',
                'mensagem' => $e->getMessage()
            ], 500);
        }
    }
    
    // URL base do seu n8n local
    private $n8nUrl = 'https://webhook.felipecampos.dev';
    
    /**
     * Receber dados do n8n via webhook
     * Laravel recebe dados que o n8n envia
     */
    public function receberDados(Request $request)
    {
        try {
            // Pegar todos os dados enviados pelo n8n
            $dados = $request->all();
            
            // Log para debug
            Log::info('Dados recebidos do n8n:', $dados);
            
            // Verificar se são dados de usuário/contato
            $resultado = $this->processarDadosUsuario($dados);
            
            // Resposta para o n8n
            return response()->json([
                'status' => 'sucesso',
                'mensagem' => 'Dados recebidos e processados com sucesso',
                'dados_recebidos' => $dados,
                'resultado_processamento' => $resultado,
                'timestamp' => now()
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erro ao receber dados do n8n: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'erro',
                'mensagem' => 'Erro ao processar dados: ' . $e->getMessage(),
                'dados_recebidos' => $dados ?? []
            ], 500);
        }
    }
    
    /**
     * Enviar dados para o n8n
     * Laravel envia dados para um webhook do n8n
     */
    public function enviarDados(Request $request)
    {
        try {
            // Validar dados de entrada
            $request->validate([
                'webhook_url' => 'required|url',
                'dados' => 'required|array'
            ]);
            
            $webhookUrl = $request->webhook_url;
            $dados = $request->dados;
            
            // Enviar dados para o n8n via HTTP POST
            $response = Http::timeout(30)->post($webhookUrl, [
                'origem' => 'Laravel',
                'timestamp' => now(),
                'dados' => $dados
            ]);
            
            if ($response->successful()) {
                return response()->json([
                    'status' => 'sucesso',
                    'mensagem' => 'Dados enviados para n8n com sucesso',
                    'response_n8n' => $response->json()
                ]);
            } else {
                throw new \Exception('n8n retornou erro: ' . $response->status());
            }
            
        } catch (\Exception $e) {
            Log::error('Erro ao enviar dados para n8n: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'erro',
                'mensagem' => 'Erro ao enviar dados: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Endpoint para testar se a API está funcionando
     */
    public function teste()
    {
        return response()->json([
            'status' => 'online',
            'mensagem' => 'API Laravel conectada com n8n funcionando!',
            'timestamp' => now()
        ]);
    }
    
    /**
     * Exemplo: buscar dados de usuários para enviar ao n8n
     */
    public function obterUsuarios()
    {
        try {
            // Exemplo simples - você pode adaptar conforme sua necessidade
            $usuarios = [
                ['id' => 1, 'nome' => 'João', 'email' => 'joao@email.com'],
                ['id' => 2, 'nome' => 'Maria', 'email' => 'maria@email.com']
            ];
            
            return response()->json([
                'status' => 'sucesso',
                'usuarios' => $usuarios,
                'total' => count($usuarios)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'erro',
                'mensagem' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Método para testar envio de dados para n8n
     * Acesse via: GET /api/n8n/testar-envio
     */
    public function testarEnvio()
    {
        try {
            // URL do webhook do n8n (substitua pela sua)
            $webhookUrl = 'https://webhook.felipecampos.dev/webhook/receber-laravel';
            
            // Dados de teste
            $dadosTeste = [
                'origem' => 'Laravel Controller',
                'teste' => 'Envio automático',
                'usuario' => 'sistema_teste',
                'dados' => [
                    'produto' => 'Sistema XYZ',
                    'versao' => '1.0',
                    'ambiente' => 'desenvolvimento',
                    'timestamp' => now()->format('Y-m-d H:i:s')
                ]
            ];
            
            // Enviar para n8n
            $response = Http::timeout(30)->post($webhookUrl, [
                'origem' => 'Laravel',
                'timestamp' => now(),
                'dados' => $dadosTeste
            ]);
            
            if ($response->successful()) {
                return response()->json([
                    'status' => 'sucesso',
                    'mensagem' => 'Teste realizado com sucesso!',
                    'dados_enviados' => $dadosTeste,
                    'resposta_n8n' => $response->json(),
                    'status_code' => $response->status()
                ]);
            } else {
                throw new \Exception('n8n retornou erro: ' . $response->status() . ' - ' . $response->body());
            }
            
        } catch (\Exception $e) {
            Log::error('Erro no teste de envio para n8n: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'erro',
                'mensagem' => 'Erro no teste: ' . $e->getMessage(),
                'webhook_url' => $webhookUrl ?? 'não definida'
            ], 500);
        }
    }
    
    /**
     * Método para enviar dados de usuários para o n8n
     * Acesse via: GET /api/n8n/testar-envio-usuarios
     */
    public function testarEnvioUsuarios()
    {
        try {
            // URL do webhook do n8n
            $webhookUrl = 'https://webhook.felipecampos.dev/webhook/receber-laravel';
            
            // Buscar usuários (exemplo)
            $usuarios = [
                ['id' => 1, 'nome' => 'João Silva', 'email' => 'joao@email.com', 'ativo' => true],
                ['id' => 2, 'nome' => 'Maria Santos', 'email' => 'maria@email.com', 'ativo' => true],
                ['id' => 3, 'nome' => 'Pedro Costa', 'email' => 'pedro@email.com', 'ativo' => false]
            ];
            
            // Enviar cada usuário para o n8n
            $resultados = [];
            
            foreach ($usuarios as $usuario) {
                $response = Http::timeout(30)->post($webhookUrl, [
                    'tipo' => 'usuario',
                    'acao' => 'sincronizar',
                    'usuario' => $usuario,
                    'timestamp' => now()
                ]);
                
                $resultados[] = [
                    'usuario_id' => $usuario['id'],
                    'sucesso' => $response->successful(),
                    'status_code' => $response->status(),
                    'resposta' => $response->successful() ? $response->json() : $response->body()
                ];
            }
            
            return response()->json([
                'status' => 'concluído',
                'mensagem' => 'Envio de usuários realizado',
                'total_usuarios' => count($usuarios),
                'resultados' => $resultados
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro no envio de usuários para n8n: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'erro',
                'mensagem' => 'Erro no envio: ' . $e->getMessage()
            ], 500);
        }
    }
}