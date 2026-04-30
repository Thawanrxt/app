<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_area_scopes')) {
            return;
        }

        Schema::create('admin_area_scopes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('admin_user_id');
            $table->string('province_name')->nullable();
            $table->string('district_name')->nullable();
            $table->string('subdistrict_name')->nullable();
            $table->string('scope_label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('admin_user_id');
            $table->index(['province_name', 'district_name'], 'admin_area_scopes_province_district_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_area_scopes');
    }
};
