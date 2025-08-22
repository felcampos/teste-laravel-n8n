<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class N8nApiAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->header('X-API-Token');
        $expectedToken = config('n8n.api_token');

        // Verificar se token foi fornecido
        if (!$token) {
            return response()->json([
                'status' => 'erro',
                'mensagem' => 'Token de autenticação obrigatório',
                'codigo' => 'TOKEN_MISSING'
            ], 401);
        }

        // Verificar se token é válido
        if (!hash_equals($expectedToken, $token)) {
            return response()->json([
                'status' => 'erro',
                'mensagem' => 'Token de autenticação inválido',
                'codigo' => 'TOKEN_INVALID'
            ], 401);
        }

        // Token válido, prosseguir
        return $next($request);
    }
}