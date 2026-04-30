<?php

namespace App\Http\Middleware;

use App\Support\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && AdminAccess::canAccessAdminPanel(Auth::user())) {
            return redirect('/admin');
        }

        return $next($request);
    }
}
