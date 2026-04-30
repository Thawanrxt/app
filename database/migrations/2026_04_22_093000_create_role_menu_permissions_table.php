<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('role_menu_permissions')) {
            Schema::create('role_menu_permissions', function (Blueprint $table): void {
                $table->id();
                $table->string('role_code', 50);
                $table->string('menu_key', 100);
                $table->string('menu_label', 255);
                $table->string('menu_group', 100)->nullable();
                $table->boolean('can_view')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['role_code', 'menu_key'], 'role_menu_permissions_role_menu_unique');
            });
        }

        $roles = [];

        if (Schema::hasTable('roles')) {
            $roles = DB::table('roles')
                ->when(Schema::hasColumn('roles', 'is_active'), fn ($query) => $query->where('is_active', true))
                ->orderBy('code')
                ->pluck('code')
                ->all();
        }

        if ($roles === []) {
            $roles = ['ADMIN', 'FARMER'];
        }

        $menus = [
            ['menu_key' => 'dashboard', 'menu_label' => 'แดชบอร์ด', 'menu_group' => null, 'sort_order' => 10],
            ['menu_key' => 'users', 'menu_label' => 'ผู้ใช้งาน', 'menu_group' => null, 'sort_order' => 20],
            ['menu_key' => 'roles', 'menu_label' => 'ทะเบียนบทบาท', 'menu_group' => null, 'sort_order' => 25],
            ['menu_key' => 'tracking_prep', 'menu_label' => 'การเตรียมดิน', 'menu_group' => 'tracking', 'sort_order' => 30],
            ['menu_key' => 'tracking_water', 'menu_label' => 'การจัดการน้ำ', 'menu_group' => 'tracking', 'sort_order' => 40],
            ['menu_key' => 'tracking_fertilizer', 'menu_label' => 'หว่านปุ๋ย', 'menu_group' => 'tracking', 'sort_order' => 50],
            ['menu_key' => 'tracking_pest', 'menu_label' => 'การจัดการศัตรูพืช', 'menu_group' => 'tracking', 'sort_order' => 60],
            ['menu_key' => 'tracking_disease', 'menu_label' => 'การจัดการโรคพืช', 'menu_group' => 'tracking', 'sort_order' => 70],
            ['menu_key' => 'tracking_harvest', 'menu_label' => 'การเก็บเกี่ยว', 'menu_group' => 'tracking', 'sort_order' => 80],
            ['menu_key' => 'tracking_mill', 'menu_label' => 'ขายข้าวเข้าโรงสี', 'menu_group' => 'tracking', 'sort_order' => 90],
            ['menu_key' => 'srp_manual', 'menu_label' => 'คู่มือมาตรฐาน SRP', 'menu_group' => 'srp', 'sort_order' => 100],
            ['menu_key' => 'srp_farmers', 'menu_label' => 'ข้อมูลเกษตรกร', 'menu_group' => 'srp', 'sort_order' => 110],
            ['menu_key' => 'rice', 'menu_label' => 'พันธุ์ข้าว', 'menu_group' => null, 'sort_order' => 120],
            ['menu_key' => 'report_rice', 'menu_label' => 'รายงานปัญหาการปลูกข้าว', 'menu_group' => 'report', 'sort_order' => 130],
            ['menu_key' => 'report_system', 'menu_label' => 'รายงานปัญหาการใช้งานระบบ', 'menu_group' => 'report', 'sort_order' => 140],
            ['menu_key' => 'settings', 'menu_label' => 'ตั้งค่า', 'menu_group' => null, 'sort_order' => 150],
        ];

        $now = now();

        foreach ($roles as $roleCode) {
            foreach ($menus as $menu) {
                $exists = DB::table('role_menu_permissions')
                    ->where('role_code', $roleCode)
                    ->where('menu_key', $menu['menu_key'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('role_menu_permissions')->insert([
                    'role_code' => $roleCode,
                    'menu_key' => $menu['menu_key'],
                    'menu_label' => $menu['menu_label'],
                    'menu_group' => $menu['menu_group'],
                    'can_view' => true,
                    'sort_order' => $menu['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('role_menu_permissions')) {
            Schema::dropIfExists('role_menu_permissions');
        }
    }
};
