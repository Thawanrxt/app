<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_work_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->uuid('profile_id')->nullable();
            $table->uuid('plot_id')->nullable();
            $table->uuid('activity_event_id')->nullable();
            $table->uuid('support_ticket_id')->nullable();

            $table->string('task_title');
            $table->string('issue_category', 100);
            $table->string('farmer_name')->nullable();
            $table->string('plot_code')->nullable();

            $table->string('status', 50)->default('pending_review');
            $table->string('priority', 20)->default('medium');
            $table->boolean('response_required')->default(true);

            $table->text('latest_note')->nullable();
            $table->text('detail_url')->nullable();
            $table->json('meta')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();

            $table->index('user_id');
            $table->index('profile_id');
            $table->index('plot_id');
            $table->index('activity_event_id');
            $table->index('support_ticket_id');
            $table->index('status');
            $table->index('priority');
            $table->index('created_at');
        });

        Schema::create('tracking_advices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('activity_event_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->uuid('profile_id')->nullable();
            $table->uuid('plot_id')->nullable();

            $table->string('page_key');
            $table->string('page_title')->nullable();
            $table->string('farmer_name')->nullable();
            $table->string('plot_code')->nullable();
            $table->integer('round_number')->nullable();

            $table->text('advice_message');
            $table->string('advice_status', 50)->default('sent');
            $table->string('sent_by')->nullable();
            $table->text('detail_url')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->timestamp('read_at')->nullable();

            $table->index('activity_event_id');
            $table->index('user_id');
            $table->index('profile_id');
            $table->index('plot_id');
            $table->index('page_key');
            $table->index('advice_status');
            $table->index('created_at');
        });

        Schema::create('admin_activity_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('admin_user_id')->nullable();

            $table->string('action', 100);
            $table->string('target_type', 100);
            $table->uuid('target_id')->nullable();

            $table->text('description');
            $table->json('meta')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('admin_user_id');
            $table->index('action');
            $table->index('target_type');
            $table->index('target_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_logs');
        Schema::dropIfExists('tracking_advices');
        Schema::dropIfExists('dashboard_work_items');
    }
};
