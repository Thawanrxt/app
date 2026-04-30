<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_farmer_assignments')) {
            return;
        }

        Schema::create('admin_farmer_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('farmer_user_id');
            $table->uuid('admin_user_id');
            $table->uuid('assigned_by')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->text('note')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->index('farmer_user_id');
            $table->index('admin_user_id');
            $table->index('assigned_by');
            $table->unique(['farmer_user_id', 'admin_user_id'], 'admin_farmer_assignments_unique_pair');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_farmer_assignments');
    }
};
