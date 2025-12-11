<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if ($user && !$user->is_active) {
            // Se for requisição API, retornar JSON
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Sua conta está bloqueada. Entre em contato com o suporte.'
                ], 403);
            }
            
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Sua conta está bloqueada. Entre em contato com o suporte.');
        }

        return $next($request);
    }
}

