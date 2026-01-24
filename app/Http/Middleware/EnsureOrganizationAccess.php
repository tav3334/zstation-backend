<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        // SuperAdmin peut tout faire
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Vérifier que l'utilisateur a une organisation
        if (!$user->organization_id) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non assigné à une organisation'
            ], 403);
        }

        // Vérifier que l'organisation est active
        if ($user->organization && !$user->organization->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Organisation désactivée'
            ], 403);
        }

        return $next($request);
    }
}
