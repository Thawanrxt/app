<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('role')->default('เกษตรกร')->after('email');
            $table->string('status')->default('ใช้งาน')->after('role');
            $table->string('citizen_id')->nullable()->after('status');
            $table->string('phone')->nullable()->after('citizen_id');
            $table->date('birth_date')->nullable()->after('phone');
            $table->string('address_line')->nullable()->after('birth_date');
            $table->string('province')->nullable()->after('address_line');
            $table->string('district')->nullable()->after('province');
            $table->string('subdistrict')->nullable()->after('district');
            $table->string('postcode')->nullable()->after('subdistrict');
            $table->string('farmer_code')->nullable()->after('postcode');
            $table->date('registered_at')->nullable()->after('farmer_code');
            $table->string('registered_province')->nullable()->after('registered_at');
            $table->string('farm_province')->nullable()->after('registered_province');
            $table->string('farm_area_rai')->nullable()->after('farm_province');
            $table->string('farm_area_square_wa')->nullable()->after('farm_area_rai');
            $table->string('crop_type')->nullable()->after('farm_area_square_wa');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'username',
                'role',
                'status',
                'citizen_id',
                'phone',
                'birth_date',
                'address_line',
                'province',
                'district',
                'subdistrict',
                'postcode',
                'farmer_code',
                'registered_at',
                'registered_province',
                'farm_province',
                'farm_area_rai',
                'farm_area_square_wa',
                'crop_type',
            ]);
        });
    }
};
