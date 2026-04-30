<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('api_access_tokens')) {
            return;
        }

        Schema::table('api_access_tokens', function (Blueprint $table): void {
            if (! Schema::hasColumn('api_access_tokens', 'device_id')) {
                $table->string('device_id')->nullable()->index();
            }

            if (! Schema::hasColumn('api_access_tokens', 'platform')) {
                $table->string('platform', 50)->nullable();
            }

            if (! Schema::hasColumn('api_access_tokens', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('api_access_tokens')) {
            return;
        }

        $columnsToDrop = array_values(array_filter([
            Schema::hasColumn('api_access_tokens', 'device_id') ? 'device_id' : null,
            Schema::hasColumn('api_access_tokens', 'platform') ? 'platform' : null,
            Schema::hasColumn('api_access_tokens', 'revoked_at') ? 'revoked_at' : null,
        ]));

        if ($columnsToDrop === []) {
            return;
        }

        Schema::table('api_access_tokens', function (Blueprint $table) use ($columnsToDrop): void {
            $table->dropColumn($columnsToDrop);
        });
    }
};
