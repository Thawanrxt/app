<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('water_tracking_activities', function (Blueprint $table): void {
            $table->id();
            $table->string('farmer_name');
            $table->string('plot_code');
            $table->unsignedInteger('round_number')->nullable();
            $table->string('activity_name')->default('การจัดการน้ำ');
            $table->string('method')->nullable();
            $table->date('activity_date');
            $table->string('water_level')->nullable();
            $table->text('details')->nullable();
            $table->text('issue_found')->nullable();
            $table->string('image_url')->nullable();
            $table->string('status')->default('pending_review');
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('water_tracking_activities');
    }
};
