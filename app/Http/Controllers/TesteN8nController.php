<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TesteN8nController extends Controller
{
    /**
     * Mostrar página de testes
     */
    public function index()
    {
        $config = $this->getN8nConfig();
        
        return Inertia::render('TesteN8n', [
            'webhookUrl' => $config['webhook_url'],
            'ambiente' => $config['ambiente'],
            'configuracao' => $config,
            'resultadoTeste' => session('resultado_teste'),
            'tipoTesteExecutado' => session('tipo_teste_executado'),
        ]);
    }
    
    /**
     * Obter configuração do n8n baseada no ambiente
     */
    private function getN8nConfig()
    {
        $ambiente = config('app.env');
        $isProducao = $ambiente === 'production';
        
        // URLs base do n8n
        $baseUrl = config('n8n.base_url', 'https://webhook.felipecampos.dev');
        
        return [
            'ambiente' => $ambiente,
            'is_producao' => $isProducao,
            'base_url' => $baseUrl,
            'webhook_url' => $isProducao 
                ? $baseUrl . '/webhook/n8n'  // Produção
                : $baseUrl . '/webhook-test/n8n',  // Teste/Local
            'webhook_teste' => $baseUrl . '/webhook-test/n8n',
            'webhook_producao' => $baseUrl . '/webhook/n8n',
        ];
    }
    
    /**
     * Executar teste de envio para n8n
     */
    public function executarTeste(Request $request)
    {
        try {
            $tipo = $request->input('tipo', 'simples');
            $forcarAmbiente = $request->input('forcar_ambiente'); // 'teste' ou 'producao'
            
            // Obter configuração baseada no ambiente ou forçar específico
            $config = $this->getN8nConfig();
            
            if ($forcarAmbiente) {
                $webhookUrl = $forcarAmbiente === 'teste' 
                    ? $config['webhook_teste'] 
                    : $config['webhook_producao'];
                $ambienteUsado = $forcarAmbiente;
            } else {
                $webhookUrl = $config['webhook_url'];
                $ambienteUsado = $config['is_producao'] ? 'producao' : 'teste';
            }
            
            // Override manual se fornecido
            if ($request->has('webhook_url') && !empty($request->webhook_url)) {
                $webhookUrl = $request->webhook_url;
                $ambienteUsado = 'personalizado';
            }
            
            $resultado = match($tipo) {
                'simples' => $this->testeSimples($webhookUrl, $ambienteUsado, $config),
                'usuarios' => $this->testeUsuarios($webhookUrl, $ambienteUsado, $config),
                'personalizado' => $this->testePersonalizado($webhookUrl, $request->input('dados'), $ambienteUsado, $config),
                'conectividade' => $this->testeConectividade($config),
                default => throw new \Exception('Tipo de teste inválido')
            };
            
            // Para Inertia, retornamos para a mesma página com o resultado
            return back()->with([
                'resultado_teste' => $resultado,
                'tipo_teste_executado' => $tipo
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro no teste n8n: ' . $e->getMessage(), [
                'ambiente' => config('app.env'),
                'webhook_url' => $webhookUrl ?? 'não definida',
                'tipo_teste' => $tipo ?? 'não definido'
            ]);
            
            return back()->with([
                'resultado_teste' => [
                    'status' => 'erro',
                    'mensagem' => $e->getMessage(),
                    'ambiente' => config('app.env')
                ],
                'tipo_teste_executado' => $tipo ?? 'erro'
            ])->withErrors(['teste' => $e->getMessage()]);
        }
    }
    
    private function testeSimples($webhookUrl, $ambienteUsado, $config)
    {
        $dados = [
            'tipo' => 'teste_simples',
            'origem' => 'Laravel VILT',
            'ambiente_laravel' => config('app.env'),
            'ambiente_webhook' => $ambienteUsado,
            'dados' => [
                'mensagem' => 'Teste de integração via Vue + Inertia',
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'ambiente' => config('app.env'),
                'webhook_usado' => $webhookUrl,
                'configuracao' => $config
            ]
        ];
        
        $inicioTempo = microtime(true);
        $response = Http::timeout(30)->post($webhookUrl, $dados);
        $tempoResposta = microtime(true) - $inicioTempo;
        
        return [
            'status' => $response->successful() ? 'sucesso' : 'erro',
            'ambiente_webhook' => $ambienteUsado,
            'webhook_url' => $webhookUrl,
            'dados_enviados' => $dados,
            'resposta_n8n' => $response->successful() ? $response->json() : $response->body(),
            'status_code' => $response->status(),
            'tempo_resposta' => $tempoResposta,
            'headers_resposta' => $response->headers()
        ];
    }
    
    private function testeUsuarios($webhookUrl, $ambienteUsado, $config)
    {
        $usuarios = [
            ['id' => 1, 'nome' => 'João Silva', 'email' => 'joao@teste.com', 'ativo' => true],
            ['id' => 2, 'nome' => 'Maria Santos', 'email' => 'maria@teste.com', 'ativo' => true],
            ['id' => 3, 'nome' => 'Pedro Costa', 'email' => 'pedro@teste.com', 'ativo' => false]
        ];
        
        $resultados = [];
        $tempoTotal = microtime(true);
        
        foreach ($usuarios as $usuario) {
            $inicioTempo = microtime(true);
            
            $response = Http::timeout(30)->post($webhookUrl, [
                'tipo' => 'usuario',
                'acao' => 'sincronizar',
                'ambiente_laravel' => config('app.env'),
                'ambiente_webhook' => $ambienteUsado,
                'usuario' => $usuario,
                'timestamp' => now()
            ]);
            
            $tempoResposta = microtime(true) - $inicioTempo;
            
            $resultados[] = [
                'usuario' => $usuario,
                'sucesso' => $response->successful(),
                'status_code' => $response->status(),
                'tempo_resposta' => $tempoResposta,
                'resposta' => $response->successful() ? $response->json() : $response->body()
            ];
        }
        
        $tempoTotal = microtime(true) - $tempoTotal;
        
        return [
            'status' => 'concluído',
            'ambiente_webhook' => $ambienteUsado,
            'webhook_url' => $webhookUrl,
            'total_usuarios' => count($usuarios),
            'tempo_total' => $tempoTotal,
            'resultados' => $resultados
        ];
    }
    
    private function testePersonalizado($webhookUrl, $dados, $ambienteUsado, $config)
    {
        $dadosEnvio = [
            'tipo' => 'personalizado',
            'origem' => 'Laravel VILT',
            'ambiente_laravel' => config('app.env'),
            'ambiente_webhook' => $ambienteUsado,
            'dados_customizados' => $dados,
            'timestamp' => now()
        ];
        
        $inicioTempo = microtime(true);
        $response = Http::timeout(30)->post($webhookUrl, $dadosEnvio);
        $tempoResposta = microtime(true) - $inicioTempo;
        
        return [
            'status' => $response->successful() ? 'sucesso' : 'erro',
            'ambiente_webhook' => $ambienteUsado,
            'webhook_url' => $webhookUrl,
            'dados_enviados' => $dadosEnvio,
            'resposta_n8n' => $response->successful() ? $response->json() : $response->body(),
            'status_code' => $response->status(),
            'tempo_resposta' => $tempoResposta
        ];
    }
    
    private function testeConectividade($config)
    {
        $resultados = [];
        
        // Testar ambos os webhooks
        $webhooks = [
            'teste' => $config['webhook_teste'],
            'producao' => $config['webhook_producao']
        ];
        
        foreach ($webhooks as $tipo => $url) {
            try {
                $inicioTempo = microtime(true);
                
                $response = Http::timeout(10)->post($url, [
                    'tipo' => 'ping',
                    'ambiente_laravel' => config('app.env'),
                    'teste_conectividade' => true,
                    'timestamp' => now()
                ]);
                
                $tempoResposta = microtime(true) - $inicioTempo;
                
                $resultados[$tipo] = [
                    'url' => $url,
                    'sucesso' => $response->successful(),
                    'status_code' => $response->status(),
                    'tempo_resposta' => $tempoResposta,
                    'resposta' => $response->successful() ? $response->json() : $response->body()
                ];
                
            } catch (\Exception $e) {
                $resultados[$tipo] = [
                    'url' => $url,
                    'sucesso' => false,
                    'erro' => $e->getMessage(),
                    'tempo_resposta' => null
                ];
            }
        }
        
        // Verificar health do n8n
        try {
            $healthResponse = Http::timeout(5)->get($config['base_url'] . '/healthz');
            $healthStatus = [
                'sucesso' => $healthResponse->successful(),
                'status_code' => $healthResponse->status(),
                'resposta' => $healthResponse->successful() ? $healthResponse->json() : $healthResponse->body()
            ];
        } catch (\Exception $e) {
            $healthStatus = [
                'sucesso' => false,
                'erro' => $e->getMessage()
            ];
        }
        
        return [
            'status' => 'concluído',
            'ambiente_atual' => config('app.env'),
            'webhook_ativo' => $config['webhook_url'],
            'health_n8n' => $healthStatus,
            'testes_webhooks' => $resultados,
            'configuracao' => $config
        ];
    }
}