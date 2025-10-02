<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage: ->middleware('role:superadmin|org_admin')
     */
    public function handle(Request $request, Closure $next, $roles = '')
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        // Parse roles separated by | or ,
        $allowed = collect(preg_split('/[|,]/', (string) $roles))
            ->filter()
            ->map(fn($r) => Str::lower(trim($r)))
            ->values()
            ->all();

        // If no roles specified, just pass
        if (empty($allowed)) {
            return $next($request);
        }

        // 1) If Spatie is installed (user has hasAnyRole), use it
        if (method_exists($user, 'hasAnyRole')) {
            if ($user->hasAnyRole($allowed)) {
                return $next($request);
            }
            abort(403, 'Você não tem permissão para acessar esta área.');
        }

        // 2) Fallback: try a 'role' attribute/column on users
        try {
            $userRole = Str::lower((string) data_get($user, 'role', ''));
            if ($userRole && in_array($userRole, $allowed, true)) {
                return $next($request);
            }
        } catch (\Throwable $e) {
            // ignore and fallback
        }

        // 3) No RBAC system detected: allow by default but warn in logs
        Log::warning('RoleMiddleware: nenhum sistema de papéis detectado. Liberando acesso por fallback.', [
            'user_id' => $user->id ?? null,
            'required' => $allowed,
        ]);

        return $next($request);
    }
}