<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$permissions
    ): Response {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $hasPermission = $user->hasAnyPermission($permissions);

        if (!$hasPermission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied.',
                'debug' => [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'permissions_requested' => $permissions,
                    'roles' => $user->roles()->pluck('slug'),
                    'has_manage_payments' => $user->hasPermission('manage_payments'),
                    'has_any_permission' => $hasPermission,
                ],
            ], 403);
        }

        return $next($request);
    }
}
