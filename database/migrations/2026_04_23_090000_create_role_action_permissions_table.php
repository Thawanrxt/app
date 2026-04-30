<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('role_action_permissions')) {
            Schema::create('role_action_permissions', function (Blueprint $table): void {
                $table->id();
                $table->string('role_code', 50);
                $table->string('resource_key', 100);
                $table->string('resource_label', 255)->nullable();
                $table->string('action_key', 100);
                $table->string('action_label', 255)->nullable();
                $table->string('action_group', 100)->nullable();
                $table->boolean('is_allowed')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['role_code', 'resource_key', 'action_key'], 'role_action_permissions_unique');
            });
        }

        if (! Schema::hasTable('roles')) {
            return;
        }

        $roleCodes = DB::table('roles')
            ->when(Schema::hasColumn('roles', 'is_active'), fn ($query) => $query->where('is_active', true))
            ->pluck('code')
            ->all();

        $definitions = [
            ['resource_key' => 'dashboard', 'resource_label' => 'แดชบอร์ด', 'action_key' => 'view', 'action_label' => 'ดู', 'action_group' => 'general', 'sort_order' => 10],
            ['resource_key' => 'dashboard', 'resource_label' => 'แดชบอร์ด', 'action_key' => 'manage', 'action_label' => 'จัดการการแจ้งเตือน', 'action_group' => 'general', 'sort_order' => 20],
            ['resource_key' => 'farmer_users', 'resource_label' => 'ผู้ใช้งานเกษตรกร', 'action_key' => 'view', 'action_label' => 'ดูรายการ/รายละเอียด', 'action_group' => 'general', 'sort_order' => 30],
            ['resource_key' => 'farmer_users', 'resource_label' => 'ผู้ใช้งานเกษตรกร', 'action_key' => 'create', 'action_label' => 'เพิ่มผู้ใช้งาน', 'action_group' => 'general', 'sort_order' => 40],
            ['resource_key' => 'farmer_users', 'resource_label' => 'ผู้ใช้งานเกษตรกร', 'action_key' => 'edit', 'action_label' => 'แก้ไขข้อมูล', 'action_group' => 'general', 'sort_order' => 50],
            ['resource_key' => 'farmer_users', 'resource_label' => 'ผู้ใช้งานเกษตรกร', 'action_key' => 'delete', 'action_label' => 'ลบข้อมูล', 'action_group' => 'general', 'sort_order' => 60],
            ['resource_key' => 'admin_users', 'resource_label' => 'ผู้ดูแลระบบ', 'action_key' => 'view', 'action_label' => 'ดูรายการ/รายละเอียด', 'action_group' => 'admin', 'sort_order' => 70],
            ['resource_key' => 'admin_users', 'resource_label' => 'ผู้ดูแลระบบ', 'action_key' => 'create', 'action_label' => 'เพิ่มผู้ดูแล', 'action_group' => 'admin', 'sort_order' => 80],
            ['resource_key' => 'admin_users', 'resource_label' => 'ผู้ดูแลระบบ', 'action_key' => 'edit', 'action_label' => 'แก้ไขผู้ดูแล', 'action_group' => 'admin', 'sort_order' => 90],
            ['resource_key' => 'admin_users', 'resource_label' => 'ผู้ดูแลระบบ', 'action_key' => 'delete', 'action_label' => 'ลบผู้ดูแล', 'action_group' => 'admin', 'sort_order' => 100],
            ['resource_key' => 'roles', 'resource_label' => 'ทะเบียนบทบาท', 'action_key' => 'view', 'action_label' => 'ดูรายการบทบาท', 'action_group' => 'admin', 'sort_order' => 110],
            ['resource_key' => 'roles', 'resource_label' => 'ทะเบียนบทบาท', 'action_key' => 'manage', 'action_label' => 'จัดการสิทธิ์บทบาท', 'action_group' => 'admin', 'sort_order' => 120],
            ['resource_key' => 'srp_farmers', 'resource_label' => 'ข้อมูลเกษตรกร', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'general', 'sort_order' => 130],
            ['resource_key' => 'srp_manual', 'resource_label' => 'คู่มือมาตรฐาน SRP', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'general', 'sort_order' => 140],
            ['resource_key' => 'rice', 'resource_label' => 'พันธุ์ข้าว', 'action_key' => 'view', 'action_label' => 'ดูรายการ', 'action_group' => 'general', 'sort_order' => 150],
            ['resource_key' => 'rice', 'resource_label' => 'พันธุ์ข้าว', 'action_key' => 'create', 'action_label' => 'เพิ่มข้อมูล', 'action_group' => 'general', 'sort_order' => 160],
            ['resource_key' => 'rice', 'resource_label' => 'พันธุ์ข้าว', 'action_key' => 'edit', 'action_label' => 'แก้ไขข้อมูล', 'action_group' => 'general', 'sort_order' => 170],
            ['resource_key' => 'rice', 'resource_label' => 'พันธุ์ข้าว', 'action_key' => 'delete', 'action_label' => 'ลบ/กู้คืนข้อมูล', 'action_group' => 'general', 'sort_order' => 180],
            ['resource_key' => 'settings', 'resource_label' => 'ตั้งค่า', 'action_key' => 'view', 'action_label' => 'ดูหน้าตั้งค่า', 'action_group' => 'admin', 'sort_order' => 190],
            ['resource_key' => 'settings', 'resource_label' => 'ตั้งค่า', 'action_key' => 'edit', 'action_label' => 'แก้ไขค่าในระบบ', 'action_group' => 'admin', 'sort_order' => 200],
            ['resource_key' => 'report_rice', 'resource_label' => 'รายงานปัญหาการปลูกข้าว', 'action_key' => 'view', 'action_label' => 'ดูรายงาน', 'action_group' => 'report', 'sort_order' => 210],
            ['resource_key' => 'report_rice', 'resource_label' => 'รายงานปัญหาการปลูกข้าว', 'action_key' => 'export', 'action_label' => 'พิมพ์/ส่งออก', 'action_group' => 'report', 'sort_order' => 220],
            ['resource_key' => 'report_system', 'resource_label' => 'รายงานปัญหาระบบ', 'action_key' => 'view', 'action_label' => 'ดูรายงาน', 'action_group' => 'report', 'sort_order' => 230],
            ['resource_key' => 'report_system', 'resource_label' => 'รายงานปัญหาระบบ', 'action_key' => 'delete', 'action_label' => 'ลบรายการ', 'action_group' => 'report', 'sort_order' => 240],
            ['resource_key' => 'tracking_prep', 'resource_label' => 'ติดตามการเตรียมดิน', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'tracking', 'sort_order' => 250],
            ['resource_key' => 'tracking_prep', 'resource_label' => 'ติดตามการเตรียมดิน', 'action_key' => 'manage', 'action_label' => 'อัปเดตสถานะ/ลบ', 'action_group' => 'tracking', 'sort_order' => 260],
            ['resource_key' => 'tracking_prep', 'resource_label' => 'ติดตามการเตรียมดิน', 'action_key' => 'export', 'action_label' => 'พิมพ์/ส่งออก', 'action_group' => 'tracking', 'sort_order' => 270],
            ['resource_key' => 'tracking_water', 'resource_label' => 'ติดตามการจัดการน้ำ', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'tracking', 'sort_order' => 280],
            ['resource_key' => 'tracking_water', 'resource_label' => 'ติดตามการจัดการน้ำ', 'action_key' => 'manage', 'action_label' => 'อัปเดตสถานะ/ลบ', 'action_group' => 'tracking', 'sort_order' => 290],
            ['resource_key' => 'tracking_water', 'resource_label' => 'ติดตามการจัดการน้ำ', 'action_key' => 'export', 'action_label' => 'พิมพ์/ส่งออก', 'action_group' => 'tracking', 'sort_order' => 300],
            ['resource_key' => 'tracking_fertilizer', 'resource_label' => 'ติดตามการหว่านปุ๋ย', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'tracking', 'sort_order' => 310],
            ['resource_key' => 'tracking_fertilizer', 'resource_label' => 'ติดตามการหว่านปุ๋ย', 'action_key' => 'manage', 'action_label' => 'อัปเดตสถานะ/ลบ', 'action_group' => 'tracking', 'sort_order' => 320],
            ['resource_key' => 'tracking_fertilizer', 'resource_label' => 'ติดตามการหว่านปุ๋ย', 'action_key' => 'export', 'action_label' => 'พิมพ์/ส่งออก', 'action_group' => 'tracking', 'sort_order' => 330],
            ['resource_key' => 'tracking_pest', 'resource_label' => 'ติดตามศัตรูพืช', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'tracking', 'sort_order' => 340],
            ['resource_key' => 'tracking_pest', 'resource_label' => 'ติดตามศัตรูพืช', 'action_key' => 'manage', 'action_label' => 'อัปเดตสถานะ/ลบ', 'action_group' => 'tracking', 'sort_order' => 350],
            ['resource_key' => 'tracking_pest', 'resource_label' => 'ติดตามศัตรูพืช', 'action_key' => 'export', 'action_label' => 'พิมพ์/ส่งออก', 'action_group' => 'tracking', 'sort_order' => 360],
            ['resource_key' => 'tracking_disease', 'resource_label' => 'ติดตามโรคพืช', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'tracking', 'sort_order' => 370],
            ['resource_key' => 'tracking_disease', 'resource_label' => 'ติดตามโรคพืช', 'action_key' => 'manage', 'action_label' => 'อัปเดตสถานะ/ลบ', 'action_group' => 'tracking', 'sort_order' => 380],
            ['resource_key' => 'tracking_disease', 'resource_label' => 'ติดตามโรคพืช', 'action_key' => 'export', 'action_label' => 'พิมพ์/ส่งออก', 'action_group' => 'tracking', 'sort_order' => 390],
            ['resource_key' => 'tracking_harvest', 'resource_label' => 'ติดตามการเก็บเกี่ยว', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'tracking', 'sort_order' => 400],
            ['resource_key' => 'tracking_harvest', 'resource_label' => 'ติดตามการเก็บเกี่ยว', 'action_key' => 'manage', 'action_label' => 'อัปเดตสถานะ/ลบ', 'action_group' => 'tracking', 'sort_order' => 410],
            ['resource_key' => 'tracking_harvest', 'resource_label' => 'ติดตามการเก็บเกี่ยว', 'action_key' => 'export', 'action_label' => 'พิมพ์/ส่งออก', 'action_group' => 'tracking', 'sort_order' => 420],
            ['resource_key' => 'tracking_mill', 'resource_label' => 'ติดตามการขายเข้าโรงสี', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'tracking', 'sort_order' => 430],
            ['resource_key' => 'tracking_mill', 'resource_label' => 'ติดตามการขายเข้าโรงสี', 'action_key' => 'manage', 'action_label' => 'อัปเดตสถานะ/ลบ', 'action_group' => 'tracking', 'sort_order' => 440],
            ['resource_key' => 'tracking_mill', 'resource_label' => 'ติดตามการขายเข้าโรงสี', 'action_key' => 'export', 'action_label' => 'พิมพ์/ส่งออก', 'action_group' => 'tracking', 'sort_order' => 450],
        ];

        foreach ($roleCodes as $roleCode) {
            foreach ($definitions as $definition) {
                $exists = DB::table('role_action_permissions')
                    ->where('role_code', $roleCode)
                    ->where('resource_key', $definition['resource_key'])
                    ->where('action_key', $definition['action_key'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('role_action_permissions')->insert([
                    'role_code' => $roleCode,
                    'resource_key' => $definition['resource_key'],
                    'resource_label' => $definition['resource_label'],
                    'action_key' => $definition['action_key'],
                    'action_label' => $definition['action_label'],
                    'action_group' => $definition['action_group'],
                    'is_allowed' => $this->defaultAllowed($roleCode, $definition['resource_key']),
                    'sort_order' => $definition['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('role_action_permissions')) {
            Schema::dropIfExists('role_action_permissions');
        }
    }

    private function defaultAllowed(string $roleCode, string $resourceKey): bool
    {
        $roleCode = strtoupper(trim($roleCode));

        if ($roleCode === 'SUPERADMIN') {
            return true;
        }

        if ($roleCode !== 'ADMIN') {
            return false;
        }

        return ! in_array($resourceKey, ['admin_users', 'roles'], true);
    }
};
