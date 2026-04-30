<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $now = now();

        $exists = DB::table('roles')
            ->where('code', 'SUPERADMIN')
            ->exists();

        if ($exists) {
            DB::table('roles')
                ->where('code', 'SUPERADMIN')
                ->update([
                    'name_th' => 'ผู้ดูแลระบบสูงสุด',
                    'description' => 'ผู้ใช้งานที่ดูแลได้ทั้งระบบและกำหนดสิทธิ์ให้แอดมินคนอื่น',
                    'is_active' => true,
                    'sort_order' => 0,
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table('roles')->insert([
            'code' => 'SUPERADMIN',
            'name_th' => 'ผู้ดูแลระบบสูงสุด',
            'description' => 'ผู้ใช้งานที่ดูแลได้ทั้งระบบและกำหนดสิทธิ์ให้แอดมินคนอื่น',
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        DB::table('roles')
            ->where('code', 'SUPERADMIN')
            ->delete();
    }
};
