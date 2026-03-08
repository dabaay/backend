<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $module
     * @param  string|null  $action
     */
    public function handle(Request $request, Closure $next, string $module, ?string $action = null): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Module access check (Supports multiple modules separated by |)
        $modules = explode('|', $module);
        $hasAccess = false;
        foreach ($modules as $m) {
            if ($user->hasModuleAccess($m)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            return response()->json([
                'message' => "Ma haysatid ogolaanshah qaybtan ({$module})."
            ], 403);
        }

        // Action check if provided
        if ($action && !$user->canPerformAction($action)) {
            return response()->json([
                'message' => "Ma haysatid ogolaanshah inaad fulisid falkan ({$action})."
            ], 403);
        }

        return $next($request);
    }
}
