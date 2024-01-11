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

            return response()->json([
                'msg' => 'No tienes permisos para acceder a esta página.',
                "success" => false
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'msg' => 'Token no válido',
                "success" => false
            ], 401);
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