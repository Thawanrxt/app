<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rice_varieties', function (Blueprint $table): void {
            $table->id();
            $table->string('rice_type');
            $table->string('name');
            $table->string('standard_duration_days');
            $table->string('disease_resistance')->nullable();
            $table->json('pest_resistances')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rice_varieties');
    }
};
