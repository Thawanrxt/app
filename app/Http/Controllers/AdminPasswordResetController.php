<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminPasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('admin.passwords.email');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('username', $validated['username'])
            ->first();

        if ($user && AdminAccess::canAccessAdminPanel($user)) {
            $token = Str::random(64);
            Cache::put('admin_pw_reset_' . $token, $validated['username'], now()->addMinutes(60));

            return redirect()->route('admin.password.reset', [
                'token' => $token,
                'email' => $validated['username'],
            ]);
        }

        return back()->with('status', 'หากพบบัญชีแอดมินที่ใช้ชื่อผู้ใช้นี้ ระบบจะนำไปยังหน้าตั้งรหัสผ่านใหม่');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('admin.passwords.reset', [
            'token'    => $token,
            'username' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $cacheKey = 'admin_pw_reset_' . $validated['token'];
        $username = Cache::get($cacheKey);

        if (! $username || $username !== $validated['email']) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'ลิงก์รีเซ็ตรหัสผ่านไม่ถูกต้องหรือหมดอายุแล้ว']);
        }

        $user = User::query()
            ->where('username', $username)
            ->first();

        if (! $user || ! AdminAccess::canAccessAdminPanel($user)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'ไม่พบบัญชีแอดมินที่ใช้ชื่อผู้ใช้นี้']);
        }

        $hashedPassword = Hash::make($validated['password']);
        $payload = [];

        if (Schema::hasColumn('users', 'password_hash')) {
            $payload['password_hash'] = $hashedPassword;
        }
        if (Schema::hasColumn('users', 'password')) {
            $payload['password'] = $hashedPassword;
        }
        if ($payload === []) {
            $payload['password'] = $hashedPassword;
        }
        if (Schema::hasColumn('users', 'remember_token')) {
            $payload['remember_token'] = Str::random(60);
        }

        User::query()->where('id', $user->id)->update($payload);
        Cache::forget($cacheKey);

        return redirect('/admin/login')->with('success', 'ตั้งรหัสผ่านใหม่เรียบร้อยแล้ว กรุณาเข้าสู่ระบบอีกครั้ง');
    }
}
