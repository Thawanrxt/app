<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mill_tracking_activities', function (Blueprint $table): void {
            $table->id();
            $table->string('farmer_name');
            $table->string('plot_code');
            $table->unsignedInteger('round_number')->nullable();
            $table->string('activity_name')->default('ขายข้าวเข้าโรงสี');
            $table->date('activity_date');
            $table->string('mill_name')->nullable();
            $table->string('queue_number')->nullable();
            $table->string('document_number')->nullable();
            $table->string('product_name')->nullable();
            $table->string('vehicle_plate')->nullable();
            $table->string('time_in')->nullable();
            $table->string('time_out')->nullable();
            $table->decimal('pre_mill_weight_kg', 12, 2)->nullable();
            $table->decimal('post_mill_weight_kg', 12, 2)->nullable();
            $table->decimal('net_weight_kg', 12, 2)->nullable();
            $table->decimal('price_per_kg', 10, 2)->nullable();
            $table->decimal('total_income', 12, 2)->nullable();
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
        Schema::dropIfExists('mill_tracking_activities');
    }
};
