<?php

namespace App\Http\Middleware;

use App\Support\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect('/admin/login');
        }

        $user = Auth::user();

        if (! AdminAccess::canAccessAdminPanel($user)) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/admin/login')->withErrors([
                'username' => 'บัญชีนี้ไม่มีสิทธิ์เข้าใช้งานระบบแอดมิน',
            ]);
        }

        return $next($request);
    }
}
