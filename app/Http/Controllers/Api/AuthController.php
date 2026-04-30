<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiAccessToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $this->findUserForLogin($validated['username']);

        if (! $user || ! $this->passwordMatches($validated['password'], $user)) {
            return response()->json([
                'message' => 'ชื่อผู้ใช้ รหัสทะเบียนเกษตรกร หรือรหัสผ่านไม่ถูกต้อง',
            ], 401);
        }

        $plainToken = Str::random(80);

        $token = ApiAccessToken::query()->create([
            'user_id' => $user->id,
            'name' => $validated['device_name'] ?? 'mobile-app',
            'device_id' => $validated['device_id'] ?? null,
            'platform' => $validated['platform'] ?? null,
            'token_hash' => hash('sha256', $plainToken),
            'last_used_at' => now(),
        ]);

        return response()->json([
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => $plainToken,
                'expires_at' => $token->expires_at,
                'device_id' => $token->device_id,
                'platform' => $token->platform,
                'user' => $this->transformUser($user),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => $this->transformUser($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var ApiAccessToken|null $accessToken */
        $accessToken = $request->attributes->get('apiAccessToken');

        if ($accessToken) {
            $accessToken->forceFill([
                'revoked_at' => now(),
            ])->save();
        }

        return response()->json([
            'message' => 'ออกจากระบบเรียบร้อยแล้ว',
        ]);
    }

    private function findUserForLogin(string $identifier): ?User
    {
        $user = User::query()
            ->with('farmerProfile')
            ->where('username', $identifier)
            ->first();

        if ($user) {
            return $user;
        }

        try {
            if (! Schema::hasTable('farmer_registrations') || ! Schema::hasTable('farmer_profiles')) {
                return null;
            }

            $userId = DB::table('farmer_registrations as registrations')
                ->join('farmer_profiles as profiles', 'profiles.id', '=', 'registrations.profile_id')
                ->where('registrations.reg_number', $identifier)
                ->value('profiles.user_id');

            if (! $userId) {
                return null;
            }

            return User::query()
                ->with('farmerProfile')
                ->where('id', $userId)
                ->first();
        } catch (Throwable) {
            return null;
        }
    }

    private function passwordMatches(string $plainPassword, User $user): bool
    {
        $candidates = array_filter([
            $user->password_hash ?? null,
            $user->getAttribute('password'),
        ]);

        foreach ($candidates as $hashedPassword) {
            if (is_string($hashedPassword) && $hashedPassword !== '' && Hash::check($plainPassword, $hashedPassword)) {
                return true;
            }
        }

        return false;
    }

    private function transformUser(User $user): array
    {
        $registration = DB::table('farmer_registrations')
            ->where('profile_id', optional($user->farmerProfile)->id)
            ->first();

        return [
            'id' => $user->id,
            'username' => $user->username,
            'phone' => $user->phone,
            'role' => $user->role,
            'full_name' => $user->farmerProfile->full_name ?? null,
            'citizen_id' => $user->farmerProfile->id_card_number ?? null,
            'farmer_registration_number' => $registration->reg_number ?? null,
            'registered_at' => $registration->reg_date ?? null,
        ];
    }
}
