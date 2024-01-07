<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckRole
{
    public function handle($request, Closure $next, ...$roles)
    {
        try {

            $user = JWTAuth::parseToken()->authenticate();
           
            if ($this->userHasRoles($user, $roles)) {
                return $next($request);
            }

            return response()->json(['error' => 'No tienes permisos para acceder a esta página.'], 403);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token no válido'], 401);
        }
    }

    private function userHasRoles($user, $roles)
    {

        $userRoles = collect(is_array($user->rol) ? $user->rol : [$user->rol]);
        $requiredRoles = collect($roles);

        // Verifica si hay una intersección no vacía
        return $userRoles->intersect($requiredRoles)->isNotEmpty();
    }
}