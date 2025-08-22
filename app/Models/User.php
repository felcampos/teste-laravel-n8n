<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'telefone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relacionamento com mensagens
     */
    public function mensagens()
    {
        return $this->hasMany(Mensagem::class)->orderBy('created_at', 'desc');
    }

    /**
     * Obter última mensagem
     */
    public function ultimaMensagem()
    {
        return $this->hasOne(Mensagem::class)->latestOfMany();
    }

    /**
     * Obter mensagens do usuário
     */
    public function mensagensUsuario()
    {
        return $this->hasMany(Mensagem::class)->where('tipo', 'usuario');
    }

    /**
     * Obter mensagens da IA
     */
    public function mensagensIA()
    {
        return $this->hasMany(Mensagem::class)->where('tipo', 'ia');
    }

    /**
     * Criar ou encontrar usuário por telefone
     */
    public static function criarOuEncontrarPorTelefone(string $telefone, string $nome = null): self
    {
        // Normalizar telefone
        $telefoneNormalizado = preg_replace('/[^\d+]/', '', $telefone);
        
        $usuario = self::where('telefone', $telefoneNormalizado)->first();
        
        if (!$usuario) {
            $usuario = self::create([
                'name' => $nome ?: 'Usuário WhatsApp',
                'telefone' => $telefoneNormalizado,
                'password' => bcrypt('temp_' . uniqid()),
            ]);
        } elseif ($nome && $usuario->name === 'Usuário WhatsApp') {
            // Atualizar nome se ainda estiver com o padrão
            $usuario->update(['name' => $nome]);
        }
        
        return $usuario;
    }

    /**
     * Obter estatísticas de conversa
     */
    public function getEstatisticasConversa(): array
    {
        $totalMensagens = $this->mensagens()->count();
        $mensagensUsuario = $this->mensagensUsuario()->count();
        $mensagensIA = $this->mensagensIA()->count();
        $ultimaMensagem = $this->ultimaMensagem;
        
        return [
            'total_mensagens' => $totalMensagens,
            'mensagens_usuario' => $mensagensUsuario,
            'mensagens_ia' => $mensagensIA,
            'ultima_mensagem_em' => $ultimaMensagem?->created_at,
            'conversa_ativa' => $ultimaMensagem && $ultimaMensagem->created_at->isAfter(now()->subDays(1))
        ];
    }
}