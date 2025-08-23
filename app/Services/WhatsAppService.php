<?php

namespace App\Services;

use App\Models\User;
use App\Models\Mensagem;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Processar mensagem recebida do WhatsApp via n8n
     */
    public function processarMensagemRecebida(array $dados): array
    {
        try {
            // Extrair dados essenciais
            $telefone = $this->normalizarTelefone($dados['telefone'] ?? $dados['from'] ?? '');
            $conteudo = $dados['mensagem'] ?? $dados['message'] ?? $dados['text'] ?? '';
            $nome = $dados['nome'] ?? $dados['name'] ?? $dados['pushname'] ?? null;

            if (!$telefone || !$conteudo) {
                throw new \InvalidArgumentException('Telefone e mensagem são obrigatórios');
            }

            // Criar ou encontrar usuário
            $usuario = User::criarOuEncontrarPorTelefone($telefone, $nome);

            // Salvar mensagem do usuário
            $mensagem = Mensagem::criarMensagemUsuario(
                $usuario->id,
                $conteudo,
                $telefone,
            );

            // Gerar resposta da IA (aqui você pode integrar com OpenAI, etc.)
            $respostaIA = $this->gerarRespostaIA($conteudo, $usuario);

            // Salvar resposta da IA
            $mensagemIA = null;
            if ($respostaIA) {
                $mensagemIA = Mensagem::criarMensagemIA(
                    $usuario->id,
                    $respostaIA,
                    $telefone
                );
            }

            Log::info('Mensagem WhatsApp processada', [
                'usuario_id' => $usuario->id,
                'telefone' => $telefone,
                'mensagem_id' => $mensagem->id,
                'resposta_ia_id' => $mensagemIA?->id
            ]);

            return [
                'status' => 'sucesso',
                'usuario' => [
                    'id' => $usuario->id,
                    'nome' => $usuario->name,
                    'telefone' => $usuario->telefone
                ],
                'mensagem' => [
                    'id' => $mensagem->id,
                    'conteudo' => $mensagem->conteudo,
                    'timestamp' => $mensagem->created_at
                ],
                'resposta_ia' => $mensagemIA ? [
                    'id' => $mensagemIA->id,
                    'conteudo' => $mensagemIA->conteudo,
                    'timestamp' => $mensagemIA->created_at
                ] : null,
                'para_enviar_whatsapp' => $respostaIA // Resposta para o n8n enviar
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao processar mensagem WhatsApp', [
                'erro' => $e->getMessage(),
                'dados' => $dados
            ]);

            return [
                'status' => 'erro',
                'mensagem' => $e->getMessage()
            ];
        }
    }

    /**
     * Gerar resposta da IA
     */
    private function gerarRespostaIA(string $mensagem, User $usuario): ?string
    {
        // Por enquanto, resposta simples
        // Aqui você pode integrar com OpenAI, Claude, etc.
        
        $mensagemLower = strtolower($mensagem);
        
        if (str_contains($mensagemLower, 'oi') || str_contains($mensagemLower, 'olá') || str_contains($mensagemLower, 'ola')) {
            return "Olá, {$usuario->name}! 👋 Como posso ajudar você hoje?";
        }
        
        if (str_contains($mensagemLower, 'preço') || str_contains($mensagemLower, 'valor') || str_contains($mensagemLower, 'quanto custa')) {
            return "Ótima pergunta! Nossos serviços têm valores variados dependendo da sua necessidade. Posso te passar mais detalhes. Qual seria o seu interesse específico?";
        }
        
        if (str_contains($mensagemLower, 'obrigado') || str_contains($mensagemLower, 'obrigada')) {
            return "De nada! 😊 Estou aqui sempre que precisar. Posso ajudar com mais alguma coisa?";
        }
        
        // Resposta padrão
        return "Entendi! Deixe-me processar sua mensagem e te dar uma resposta mais detalhada. Um momento! 🤔";
    }

    /**
     * Normalizar telefone
     */
    private function normalizarTelefone(string $telefone): string
    {
        // Remove tudo exceto números e +
        $telefone = preg_replace('/[^\d+]/', '', $telefone);
        
        // Adiciona +55 se não tiver código do país
        if (!str_starts_with($telefone, '+')) {
            if (strlen($telefone) === 11) {
                $telefone = '+55' . $telefone;
            }
        }
        
        return $telefone;
    }

    /**
     * Obter histórico de conversa
     */
    public function obterHistoricoConversa(string $telefone, int $limite = 50): array
    {
        $telefoneNormalizado = $this->normalizarTelefone($telefone);
        
        $mensagens = Mensagem::porTelefone($telefoneNormalizado)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limite)
            ->get()
            ->reverse()
            ->values();

        return $mensagens->map(function ($mensagem) {
            return [
                'id' => $mensagem->id,
                'conteudo' => $mensagem->conteudo,
                'tipo' => $mensagem->tipo,
                'usuario' => $mensagem->user->name,
                'timestamp' => $mensagem->created_at->format('d/m/Y H:i'),
            ];
        })->toArray();
    }

    /**
     * Obter estatísticas gerais
     */
    public function obterEstatisticas(): array
    {
        $totalUsuarios = User::whereNotNull('telefone')->count();
        $totalMensagens = Mensagem::count();
        $conversasAtivas = User::whereHas('mensagens', function ($query) {
            $query->where('created_at', '>=', now()->subDays(1));
        })->count();

        return [
            'total_usuarios_whatsapp' => $totalUsuarios,
            'total_mensagens' => $totalMensagens,
            'conversas_ativas_24h' => $conversasAtivas,
            'mensagens_hoje' => Mensagem::whereDate('created_at', today())->count()
        ];
    }
}