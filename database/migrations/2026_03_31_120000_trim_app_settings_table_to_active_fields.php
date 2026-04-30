<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        $columnsToDrop = array_values(array_filter([
            Schema::hasColumn('app_settings', 'data_density') ? 'data_density' : null,
            Schema::hasColumn('app_settings', 'list_display') ? 'list_display' : null,
            Schema::hasColumn('app_settings', 'email_notifications') ? 'email_notifications' : null,
            Schema::hasColumn('app_settings', 'system_notifications') ? 'system_notifications' : null,
            Schema::hasColumn('app_settings', 'weekly_summary') ? 'weekly_summary' : null,
            Schema::hasColumn('app_settings', 'two_factor_enabled') ? 'two_factor_enabled' : null,
            Schema::hasColumn('app_settings', 'auto_logout_minutes') ? 'auto_logout_minutes' : null,
            Schema::hasColumn('app_settings', 'backup_enabled') ? 'backup_enabled' : null,
            Schema::hasColumn('app_settings', 'backup_frequency') ? 'backup_frequency' : null,
            Schema::hasColumn('app_settings', 'data_retention') ? 'data_retention' : null,
        ]));

        if ($columnsToDrop === []) {
            return;
        }

        Schema::table('app_settings', function (Blueprint $table) use ($columnsToDrop): void {
            $table->dropColumn($columnsToDrop);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        Schema::table('app_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('app_settings', 'data_density')) {
                $table->string('data_density')->default('ปกติ');
            }

            if (! Schema::hasColumn('app_settings', 'list_display')) {
                $table->string('list_display')->default('แสดงแบบย่อ');
            }

            if (! Schema::hasColumn('app_settings', 'email_notifications')) {
                $table->boolean('email_notifications')->default(true);
            }

            if (! Schema::hasColumn('app_settings', 'system_notifications')) {
                $table->boolean('system_notifications')->default(true);
            }

            if (! Schema::hasColumn('app_settings', 'weekly_summary')) {
                $table->boolean('weekly_summary')->default(false);
            }

            if (! Schema::hasColumn('app_settings', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false);
            }

            if (! Schema::hasColumn('app_settings', 'auto_logout_minutes')) {
                $table->string('auto_logout_minutes')->default('60 นาที');
            }

            if (! Schema::hasColumn('app_settings', 'backup_enabled')) {
                $table->boolean('backup_enabled')->default(true);
            }

            if (! Schema::hasColumn('app_settings', 'backup_frequency')) {
                $table->string('backup_frequency')->default('ทุกสัปดาห์');
            }

            if (! Schema::hasColumn('app_settings', 'data_retention')) {
                $table->string('data_retention')->default('90 วัน');
            }
        });
    }
};
