<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name_th', 255);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        $now = now();

        foreach ([
            [
                'code' => 'FARMER',
                'name_th' => 'เกษตรกร',
                'description' => 'ผู้ใช้งานทั่วไปสำหรับเกษตรกร',
                'sort_order' => 1,
            ],
            [
                'code' => 'ADMIN',
                'name_th' => 'ผู้ดูแลระบบ',
                'description' => 'ผู้ใช้งานสำหรับจัดการระบบหลังบ้าน',
                'sort_order' => 2,
            ],
        ] as $role) {
            $exists = DB::table('roles')->where('code', $role['code'])->exists();

            if ($exists) {
                DB::table('roles')
                    ->where('code', $role['code'])
                    ->update([
                        'name_th' => $role['name_th'],
                        'description' => $role['description'],
                        'is_active' => true,
                        'sort_order' => $role['sort_order'],
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('roles')->insert([
                'code' => $role['code'],
                'name_th' => $role['name_th'],
                'description' => $role['description'],
                'is_active' => true,
                'sort_order' => $role['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            Schema::dropIfExists('roles');
        }
    }
};
