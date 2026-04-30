<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('theme')->default('light');
            $table->string('font_family')->default('Prompt');
            $table->string('font_size')->default('16');
            $table->string('language')->default('ภาษาไทย');
            $table->string('timezone')->default('Asia/Bangkok (GMT+7)');
            $table->string('date_format')->default('วัน/เดือน/ปี (25 มิ.ย. 2568)');
            $table->string('area_unit')->default('ไร่/งาน/ตารางวา');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
