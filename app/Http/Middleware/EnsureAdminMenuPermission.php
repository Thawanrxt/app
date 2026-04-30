<?php

namespace App\Http\Middleware;

use App\Support\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminMenuPermission
{
    public function handle(Request $request, Closure $next, string $menuKey): Response
    {
        $user = $request->user();

        if (! $user || ! AdminAccess::canAccessAdminPanel($user)) {
            abort(403);
        }

        if (AdminAccess::isSuperAdmin($user)) {
            return $next($request);
        }

        if (! $this->hasTable('role_menu_permissions')) {
            return $next($request);
        }

        $permission = DB::table('role_menu_permissions')
            ->where('role_code', $user->role)
            ->where('menu_key', $menuKey)
            ->value('can_view');

        if ($permission === null) {
            return $next($request);
        }

        abort_unless((bool) $permission, 403);

        return $next($request);
    }

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
