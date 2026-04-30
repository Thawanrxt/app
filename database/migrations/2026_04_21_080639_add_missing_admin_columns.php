<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmer_profiles', function (Blueprint $table): void {
            if (!Schema::hasColumn('farmer_profiles', 'subdistrict')) {
                $table->string('subdistrict')->nullable()->after('district_id');
            }

            if (!Schema::hasColumn('farmer_profiles', 'postcode')) {
                $table->string('postcode', 10)->nullable()->after('subdistrict');
            }
        });

        Schema::table('plots', function (Blueprint $table): void {
            if (!Schema::hasColumn('plots', 'subdistrict')) {
                $table->string('subdistrict')->nullable()->after('district_id');
            }

            if (!Schema::hasColumn('plots', 'postcode')) {
                $table->string('postcode', 10)->nullable()->after('subdistrict');
            }

            if (!Schema::hasColumn('plots', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('postcode');
            }
        });

        Schema::table('support_tickets', function (Blueprint $table): void {
            if (!Schema::hasColumn('support_tickets', 'assigned_to')) {
                $table->uuid('assigned_to')->nullable()->after('user_id');
                $table->index('assigned_to');
            }

            if (!Schema::hasColumn('support_tickets', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('message');
            }

            if (!Schema::hasColumn('support_tickets', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('created_at');
                $table->index('resolved_at');
            }
        });

        if (Schema::hasTable('plots') && Schema::hasColumn('plots', 'plot_name') && Schema::hasColumn('plots', 'is_primary')) {
            DB::table('plots')
                ->where('plot_name', 'แปลงหลัก')
                ->update(['is_primary' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            if (Schema::hasColumn('support_tickets', 'assigned_to')) {
                $table->dropIndex(['assigned_to']);
                $table->dropColumn('assigned_to');
            }

            if (Schema::hasColumn('support_tickets', 'admin_note')) {
                $table->dropColumn('admin_note');
            }

            if (Schema::hasColumn('support_tickets', 'resolved_at')) {
                $table->dropIndex(['resolved_at']);
                $table->dropColumn('resolved_at');
            }
        });

        Schema::table('plots', function (Blueprint $table): void {
            $dropColumns = [];

            if (Schema::hasColumn('plots', 'subdistrict')) {
                $dropColumns[] = 'subdistrict';
            }

            if (Schema::hasColumn('plots', 'postcode')) {
                $dropColumns[] = 'postcode';
            }

            if (Schema::hasColumn('plots', 'is_primary')) {
                $dropColumns[] = 'is_primary';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });

        Schema::table('farmer_profiles', function (Blueprint $table): void {
            $dropColumns = [];

            if (Schema::hasColumn('farmer_profiles', 'subdistrict')) {
                $dropColumns[] = 'subdistrict';
            }

            if (Schema::hasColumn('farmer_profiles', 'postcode')) {
                $dropColumns[] = 'postcode';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
