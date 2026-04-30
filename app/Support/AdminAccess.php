<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminAccess
{
    private static array $tableExistsCache = [];

    private static array $columnExistsCache = [];

    private static array $actionPermissionCache = [];

    public static function canAccessAdminPanel(?object $user): bool
    {
        $role = strtoupper((string) ($user->role ?? ''));

        return in_array($role, ['ADMIN', 'SUPERADMIN'], true);
    }

    public static function isSuperAdmin(?object $user): bool
    {
        $role = strtoupper((string) ($user->role ?? ''));

        if ($role === 'SUPERADMIN') {
            return true;
        }

        return $role === 'ADMIN' && ! self::hasExplicitSuperAdmin();
    }

    public static function isScopedAdmin(?object $user): bool
    {
        return self::canAccessAdminPanel($user) && ! self::isSuperAdmin($user);
    }

    public static function shouldRestrictByScope(?object $user): bool
    {
        return self::isScopedAdmin($user) && self::activeScopes($user)->isNotEmpty();
    }

    public static function activeScopes(?object $user): Collection
    {
        if (! $user || ! self::hasTable('admin_area_scopes')) {
            return collect();
        }

        $query = DB::table('admin_area_scopes')
            ->select('province_name', 'district_name', 'subdistrict_name')
            ->where('admin_user_id', $user->id);

        if (self::hasColumn('admin_area_scopes', 'is_active')) {
            $query->where('is_active', true);
        }

        return $query
            ->get()
            ->map(function ($scope) {
                return [
                    'province_name' => self::normalize($scope->province_name ?? null),
                    'district_name' => self::normalize($scope->district_name ?? null),
                    'subdistrict_name' => self::normalize($scope->subdistrict_name ?? null),
                ];
            })
            ->filter(fn (array $scope) => filled($scope['province_name']) || filled($scope['district_name']) || filled($scope['subdistrict_name']))
            ->values();
    }

    public static function locationMatches(Collection $scopes, ?string $provinceName, ?string $districtName = null, ?string $subdistrictName = null): bool
    {
        if ($scopes->isEmpty()) {
            return true;
        }

        $province = self::normalize($provinceName);
        $district = self::normalize($districtName);
        $subdistrict = self::normalize($subdistrictName);

        return $scopes->contains(function (array $scope) use ($province, $district, $subdistrict): bool {
            if (filled($scope['province_name']) && $scope['province_name'] !== $province) {
                return false;
            }

            if (filled($scope['district_name']) && $scope['district_name'] !== $district) {
                return false;
            }

            if (filled($scope['subdistrict_name']) && $scope['subdistrict_name'] !== $subdistrict) {
                return false;
            }

            return true;
        });
    }

    public static function canManageUser(?object $actingUser, ?object $targetUser, ?string $provinceName = null, ?string $districtName = null, ?string $subdistrictName = null): bool
    {
        if (! self::canAccessAdminPanel($actingUser) || ! $targetUser) {
            return false;
        }

        if (! self::canPerform($actingUser, 'farmer_users', 'edit')) {
            return false;
        }

        if (self::isSuperAdmin($actingUser)) {
            return true;
        }

        if (strtoupper((string) ($targetUser->role ?? '')) !== 'FARMER') {
            return false;
        }

        return self::locationMatches(self::activeScopes($actingUser), $provinceName, $districtName, $subdistrictName);
    }

    public static function canPerform(?object $user, string $resourceKey, string $actionKey = 'view'): bool
    {
        if (! self::canAccessAdminPanel($user)) {
            return false;
        }

        if (self::isSuperAdmin($user)) {
            return true;
        }

        $roleCode = strtoupper((string) ($user->role ?? ''));

        if (! self::hasTable('role_action_permissions')) {
            return self::defaultActionAllowedForRole($roleCode, $resourceKey, $actionKey);
        }

        $permissionMap = self::actionPermissionMapForRole($roleCode);
        $permissionKey = "{$resourceKey}.{$actionKey}";

        if (array_key_exists($permissionKey, $permissionMap)) {
            return (bool) $permissionMap[$permissionKey];
        }

        return self::defaultActionAllowedForRole($roleCode, $resourceKey, $actionKey);
    }

    private static function actionPermissionMapForRole(string $roleCode): array
    {
        if (array_key_exists($roleCode, self::$actionPermissionCache)) {
            return self::$actionPermissionCache[$roleCode];
        }

        if (! self::hasTable('role_action_permissions')) {
            return self::$actionPermissionCache[$roleCode] = [];
        }

        $map = DB::table('role_action_permissions')
            ->select('resource_key', 'action_key', 'is_allowed')
            ->where('role_code', $roleCode)
            ->get()
            ->mapWithKeys(fn ($row) => ["{$row->resource_key}.{$row->action_key}" => (bool) $row->is_allowed])
            ->all();

        return self::$actionPermissionCache[$roleCode] = $map;
    }

    private static function defaultActionAllowedForRole(string $roleCode, string $resourceKey, string $actionKey): bool
    {
        if ($roleCode === 'SUPERADMIN') {
            return true;
        }

        if ($roleCode !== 'ADMIN') {
            return false;
        }

        if (in_array($resourceKey, ['admin_users', 'roles'], true)) {
            return false;
        }

        return true;
    }

    private static function hasExplicitSuperAdmin(): bool
    {
        if (! self::hasTable('users')) {
            return false;
        }

        return DB::table('users')
            ->where('role', 'SUPERADMIN')
            ->exists();
    }

    private static function normalize(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        return mb_strtolower(trim($value));
    }

    private static function hasTable(string $table): bool
    {
        if (array_key_exists($table, self::$tableExistsCache)) {
            return self::$tableExistsCache[$table];
        }

        try {
            return self::$tableExistsCache[$table] = Schema::hasTable($table);
        } catch (\Throwable) {
            return self::$tableExistsCache[$table] = false;
        }
    }

    private static function hasColumn(string $table, string $column): bool
    {
        $cacheKey = "{$table}.{$column}";

        if (array_key_exists($cacheKey, self::$columnExistsCache)) {
            return self::$columnExistsCache[$cacheKey];
        }

        try {
            return self::$columnExistsCache[$cacheKey] = Schema::hasColumn($table, $column);
        } catch (\Throwable) {
            return self::$columnExistsCache[$cacheKey] = false;
        }
    }
}
