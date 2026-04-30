<?php

namespace App\Http\Middleware;

use App\Support\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminActionPermission
{
    public function handle(Request $request, Closure $next, string $resourceKey, string $actionKey = 'view'): Response
    {
        $user = $request->user();

        if (! $user || ! AdminAccess::canAccessAdminPanel($user)) {
            abort(403);
        }

        abort_unless(AdminAccess::canPerform($user, $resourceKey, $actionKey), 403);

        return $next($request);
    }
}
