<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_menu_permissions')) {
            return;
        }

        $menus = [
            ['menu_key' => 'admin_users', 'menu_label' => 'Admin Users', 'menu_group' => null, 'sort_order' => 20],
            ['menu_key' => 'farmer_users', 'menu_label' => 'Farmer Users', 'menu_group' => null, 'sort_order' => 30],
            ['menu_key' => 'roles', 'menu_label' => 'Role Registry', 'menu_group' => null, 'sort_order' => 40],
        ];

        $roleCodes = DB::table('roles')->pluck('code')->all();

        foreach ($roleCodes as $roleCode) {
            foreach ($menus as $menu) {
                $exists = DB::table('role_menu_permissions')
                    ->where('role_code', $roleCode)
                    ->where('menu_key', $menu['menu_key'])
                    ->exists();

                $payload = [
                    'menu_label' => $menu['menu_label'],
                    'menu_group' => $menu['menu_group'],
                    'sort_order' => $menu['sort_order'],
                    'updated_at' => now(),
                ];

                if ($exists) {
                    DB::table('role_menu_permissions')
                        ->where('role_code', $roleCode)
                        ->where('menu_key', $menu['menu_key'])
                        ->update($payload + [
                            'can_view' => $this->defaultCanView($roleCode, $menu['menu_key']),
                        ]);

                    continue;
                }

                DB::table('role_menu_permissions')->insert($payload + [
                    'role_code' => $roleCode,
                    'menu_key' => $menu['menu_key'],
                    'can_view' => $this->defaultCanView($roleCode, $menu['menu_key']),
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('role_menu_permissions')) {
            return;
        }

        DB::table('role_menu_permissions')
            ->whereIn('menu_key', ['admin_users', 'farmer_users'])
            ->delete();
    }

    private function defaultCanView(string $roleCode, string $menuKey): bool
    {
        $roleCode = strtoupper(trim($roleCode));

        if ($roleCode === 'SUPERADMIN') {
            return true;
        }

        if ($roleCode === 'ADMIN') {
            return ! in_array($menuKey, ['admin_users', 'roles'], true);
        }

        return false;
    }
};
