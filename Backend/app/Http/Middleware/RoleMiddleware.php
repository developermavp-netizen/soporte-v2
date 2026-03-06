<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $userRole = $request->user()->role->name ?? null;
        
        if (!in_array($userRole, $roles)) {
            return response()->json([
                'error' => 'No tienes permisos para acceder a este recurso'
            ], 403);
        }

        return $next($request);
    }
}