<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Test simple para ver si el middleware se está ejecutando
        return response()->json(['test' => 'Middleware executed']);

        // La lógica original está comentada temporalmente
        /*
        Log::info('AdminMiddleware: Iniciando verificación');

        if (!auth()->check()) {
            Log::warning('AdminMiddleware: Usuario no autenticado');
            return response()->json(['error' => 'No autorizado'], 403);
        }

        Log::info('AdminMiddleware: Usuario autenticado', [
            'user_id' => auth()->id(),
            'role' => auth()->user()->role
        ]);

        if (auth()->user()->role !== 'admin') {
            Log::warning('AdminMiddleware: Usuario no es admin', [
                'user_id' => auth()->id(),
                'role' => auth()->user()->role
            ]);
            return response()->json(['error' => 'No autorizado'], 403);
        }

        Log::info('AdminMiddleware: Verificación exitosa - Usuario es admin');
        return $next($request);
        */
    }
}
