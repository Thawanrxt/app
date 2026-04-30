<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('rice_varieties', 'is_active')) {
            Schema::table('rice_varieties', function (Blueprint $table): void {
                $table->boolean('is_active')->default(true)->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('rice_varieties', 'is_active')) {
            Schema::table('rice_varieties', function (Blueprint $table): void {
                $table->dropColumn('is_active');
            });
        }
    }
};
