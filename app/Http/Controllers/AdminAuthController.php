<?php

namespace App\Http\Controllers;

use App\Support\AdminAccess;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check() && AdminAccess::canAccessAdminPanel(Auth::user())) {
            return redirect('/admin');
        }

        return view('admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('username', $credentials['username'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], (string) $user->getAuthPassword())) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'username' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง',
                ]);
        }

        if (! AdminAccess::canAccessAdminPanel($user)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'username' => 'บัญชีนี้ไม่มีสิทธิ์เข้าใช้งานระบบแอดมิน',
                ]);
        }

        Auth::login($user, false);
        $request->session()->regenerate();

        return redirect()->intended('/admin');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login')->with('success', 'ออกจากระบบเรียบร้อยแล้ว');
    }
}
