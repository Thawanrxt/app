<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_work_items', function (Blueprint $table) {
            $table->id();
            $table->string('farmer_name');
            $table->string('plot_code')->nullable();
            $table->string('task_title');
            $table->string('issue_category')->nullable();
            $table->string('status', 32);
            $table->string('priority', 16)->default('normal');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->date('due_date')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->boolean('response_required')->default(false);
            $table->text('latest_note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_work_items');
    }
};
