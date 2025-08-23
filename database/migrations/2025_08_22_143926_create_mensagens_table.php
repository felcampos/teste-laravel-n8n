<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mensagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Conteúdo da mensagem
            $table->text('conteudo');
            $table->enum('tipo', ['usuario', 'ia', 'system']); // Quem enviou a mensagem
            
            // Dados do WhatsApp / n8n
            $table->string('telefone'); // Telefone que enviou/recebeu
            
            $table->timestamps();
            
            // Índices para consultas eficientes
            $table->index('user_id');
            $table->index('telefone');
            $table->index('tipo');
            $table->index(['user_id', 'created_at']);
            $table->index(['telefone', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensagens');
    }
};
