<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_events', function (Blueprint $table): void {
            if (! Schema::hasColumn('activity_events', 'reviewed_by')) {
                $table->string('reviewed_by')->nullable();
            }

            if (! Schema::hasColumn('activity_events', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable();
            }

            if (! Schema::hasColumn('activity_events', 'admin_note')) {
                $table->text('admin_note')->nullable();
            }
        });

        DB::table('activity_events')
            ->whereIn('status', ['DONE', 'FAILED', 'NEEDS_FIX'])
            ->update([
                'status' => 'ACTIVE',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'admin_note' => null,
            ]);
    }

    public function down(): void
    {
        Schema::table('activity_events', function (Blueprint $table): void {
            $columns = [];

            if (Schema::hasColumn('activity_events', 'reviewed_by')) {
                $columns[] = 'reviewed_by';
            }

            if (Schema::hasColumn('activity_events', 'reviewed_at')) {
                $columns[] = 'reviewed_at';
            }

            if (Schema::hasColumn('activity_events', 'admin_note')) {
                $columns[] = 'admin_note';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
