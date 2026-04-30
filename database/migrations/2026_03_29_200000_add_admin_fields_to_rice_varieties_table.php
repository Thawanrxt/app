<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rice_varieties', function (Blueprint $table): void {
            if (!Schema::hasColumn('rice_varieties', 'rice_type')) {
                $table->string('rice_type')->nullable();
            }

            if (!Schema::hasColumn('rice_varieties', 'standard_duration_days')) {
                $table->string('standard_duration_days')->nullable();
            }

            if (!Schema::hasColumn('rice_varieties', 'disease_resistance')) {
                $table->string('disease_resistance')->nullable();
            }

            if (!Schema::hasColumn('rice_varieties', 'pest_resistances')) {
                $table->json('pest_resistances')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('rice_varieties', function (Blueprint $table): void {
            $columns = [
                'rice_type',
                'standard_duration_days',
                'disease_resistance',
                'pest_resistances',
            ];

            $existingColumns = array_values(array_filter($columns, fn (string $column): bool => Schema::hasColumn('rice_varieties', $column)));

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
