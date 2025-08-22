<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mensagem extends Model
{
    use HasFactory;

    protected $table = 'mensagens';

    protected $fillable = [
        'user_id',
        'conteudo',
        'tipo',
        'whatsapp_id',
        'telefone',
    ];

    protected $casts = [
        // Removido metadata e enviada_em
    ];

    /**
     * Relacionamento com usuário
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para mensagens do usuário
     */
    public function scopeDoUsuario($query)
    {
        return $query->where('tipo', 'usuario');
    }

    /**
     * Scope para mensagens da IA
     */
    public function scopeDaIA($query)
    {
        return $query->where('tipo', 'ia');
    }

    /**
     * Scope para mensagens por telefone
     */
    public function scopePorTelefone($query, string $telefone)
    {
        return $query->where('telefone', $telefone);
    }

    /**
     * Scope para conversas recentes
     */
    public function scopeRecentes($query, int $horas = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($horas));
    }

    /**
     * Criar mensagem do usuário
     */
    public static function criarMensagemUsuario(
        int $userId,
        string $conteudo,
        string $telefone,
        string $whatsappId = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'conteudo' => $conteudo,
            'tipo' => 'usuario',
            'telefone' => $telefone,
            'whatsapp_id' => $whatsappId,
        ]);
    }

    /**
     * Criar mensagem da IA
     */
    public static function criarMensagemIA(
        int $userId,
        string $conteudo,
        string $telefone
    ): self {
        return self::create([
            'user_id' => $userId,
            'conteudo' => $conteudo,
            'tipo' => 'ia',
            'telefone' => $telefone,
        ]);
    }

    /**
     * Verificar se é mensagem do usuário
     */
    public function isDoUsuario(): bool
    {
        return $this->tipo === 'usuario';
    }

    /**
     * Verificar se é mensagem da IA
     */
    public function isDaIA(): bool
    {
        return $this->tipo === 'ia';
    }
}