<?php

namespace App\Http\Middleware;

use App\Models\ApiAccessToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! filled($plainToken)) {
            return $this->unauthorized('กรุณาเข้าสู่ระบบก่อนใช้งาน');
        }

        $accessToken = ApiAccessToken::query()
            ->with('user.farmerProfile')
            ->where('token_hash', hash('sha256', $plainToken))
            ->first();

        if (! $accessToken || ! $accessToken->user) {
            return $this->unauthorized('โทเค็นไม่ถูกต้อง');
        }

        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return $this->unauthorized('โทเค็นหมดอายุแล้ว');
        }

        if ($accessToken->revoked_at) {
            return $this->unauthorized('โทเค็นนี้ถูกยกเลิกแล้ว');
        }

        $accessToken->forceFill([
            'last_used_at' => now(),
        ])->save();

        $request->attributes->set('apiAccessToken', $accessToken);
        $request->setUserResolver(fn () => $accessToken->user);

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 401);
    }
}
