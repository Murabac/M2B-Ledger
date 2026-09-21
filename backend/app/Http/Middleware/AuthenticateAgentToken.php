<?php

namespace App\Http\Middleware;

use App\Models\AgentToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAgentToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');

        if (! preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $plaintext = trim($matches[1]);

        if ($plaintext === '') {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = AgentToken::query()
            ->where('token_hash', AgentToken::hashToken($plaintext))
            ->whereNull('revoked_at')
            ->first();

        if ($token === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->attributes->set('agent_token', $token);
        $request->attributes->set('agent_token_plain', $plaintext);

        return $next($request);
    }
}
