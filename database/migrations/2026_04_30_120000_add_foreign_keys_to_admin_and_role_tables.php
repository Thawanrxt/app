<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addForeignKeyIfPossible(
            table: 'admin_profiles',
            column: 'user_id',
            referencedTable: 'users',
            referencedColumn: 'id',
            foreignKeyName: 'admin_profiles_user_id_fk',
            onDelete: 'cascade'
        );

        $this->addForeignKeyIfPossible(
            table: 'admin_area_scopes',
            column: 'admin_user_id',
            referencedTable: 'users',
            referencedColumn: 'id',
            foreignKeyName: 'admin_area_scopes_admin_user_id_fk',
            onDelete: 'cascade'
        );

        $this->addForeignKeyIfPossible(
            table: 'admin_farmer_assignments',
            column: 'admin_user_id',
            referencedTable: 'users',
            referencedColumn: 'id',
            foreignKeyName: 'admin_farmer_assignments_admin_user_id_fk',
            onDelete: 'cascade'
        );

        $this->addForeignKeyIfPossible(
            table: 'admin_farmer_assignments',
            column: 'farmer_user_id',
            referencedTable: 'users',
            referencedColumn: 'id',
            foreignKeyName: 'admin_farmer_assignments_farmer_user_id_fk',
            onDelete: 'cascade'
        );

        $this->addForeignKeyIfPossible(
            table: 'admin_farmer_assignments',
            column: 'assigned_by',
            referencedTable: 'users',
            referencedColumn: 'id',
            foreignKeyName: 'admin_farmer_assignments_assigned_by_fk',
            onDelete: 'set null'
        );

        $this->addForeignKeyIfPossible(
            table: 'role_menu_permissions',
            column: 'role_code',
            referencedTable: 'roles',
            referencedColumn: 'code',
            foreignKeyName: 'role_menu_permissions_role_code_fk',
            onDelete: 'cascade'
        );

        $this->addForeignKeyIfPossible(
            table: 'role_action_permissions',
            column: 'role_code',
            referencedTable: 'roles',
            referencedColumn: 'code',
            foreignKeyName: 'role_action_permissions_role_code_fk',
            onDelete: 'cascade'
        );

        $this->addForeignKeyIfPossible(
            table: 'dashboard_work_items',
            column: 'user_id',
            referencedTable: 'users',
            referencedColumn: 'id',
            foreignKeyName: 'dashboard_work_items_user_id_fk',
            onDelete: 'set null'
        );

        $this->addForeignKeyIfPossible(
            table: 'dashboard_work_items',
            column: 'plot_id',
            referencedTable: 'plots',
            referencedColumn: 'id',
            foreignKeyName: 'dashboard_work_items_plot_id_fk',
            onDelete: 'set null'
        );

        $this->addForeignKeyIfPossible(
            table: 'tracking_advices',
            column: 'user_id',
            referencedTable: 'users',
            referencedColumn: 'id',
            foreignKeyName: 'tracking_advices_user_id_fk',
            onDelete: 'set null'
        );

        $this->addForeignKeyIfPossible(
            table: 'tracking_advices',
            column: 'plot_id',
            referencedTable: 'plots',
            referencedColumn: 'id',
            foreignKeyName: 'tracking_advices_plot_id_fk',
            onDelete: 'set null'
        );

        $this->addForeignKeyIfPossible(
            table: 'admin_activity_logs',
            column: 'admin_user_id',
            referencedTable: 'users',
            referencedColumn: 'id',
            foreignKeyName: 'admin_activity_logs_admin_user_id_fk',
            onDelete: 'set null'
        );

        $this->addForeignKeyIfPossible(
            table: 'support_tickets',
            column: 'assigned_to',
            referencedTable: 'users',
            referencedColumn: 'id',
            foreignKeyName: 'support_tickets_assigned_to_fk',
            onDelete: 'set null'
        );

        $this->addForeignKeyIfPossible(
            table: 'api_access_tokens',
            column: 'user_id',
            referencedTable: 'users',
            referencedColumn: 'id',
            foreignKeyName: 'api_access_tokens_user_id_fk',
            onDelete: 'cascade'
        );
    }

    public function down(): void
    {
        $this->dropForeignKeyIfExists('api_access_tokens', 'api_access_tokens_user_id_fk');
        $this->dropForeignKeyIfExists('support_tickets', 'support_tickets_assigned_to_fk');
        $this->dropForeignKeyIfExists('admin_activity_logs', 'admin_activity_logs_admin_user_id_fk');
        $this->dropForeignKeyIfExists('tracking_advices', 'tracking_advices_plot_id_fk');
        $this->dropForeignKeyIfExists('tracking_advices', 'tracking_advices_user_id_fk');
        $this->dropForeignKeyIfExists('dashboard_work_items', 'dashboard_work_items_plot_id_fk');
        $this->dropForeignKeyIfExists('dashboard_work_items', 'dashboard_work_items_user_id_fk');
        $this->dropForeignKeyIfExists('role_action_permissions', 'role_action_permissions_role_code_fk');
        $this->dropForeignKeyIfExists('role_menu_permissions', 'role_menu_permissions_role_code_fk');
        $this->dropForeignKeyIfExists('admin_farmer_assignments', 'admin_farmer_assignments_assigned_by_fk');
        $this->dropForeignKeyIfExists('admin_farmer_assignments', 'admin_farmer_assignments_farmer_user_id_fk');
        $this->dropForeignKeyIfExists('admin_farmer_assignments', 'admin_farmer_assignments_admin_user_id_fk');
        $this->dropForeignKeyIfExists('admin_area_scopes', 'admin_area_scopes_admin_user_id_fk');
        $this->dropForeignKeyIfExists('admin_profiles', 'admin_profiles_user_id_fk');
    }

    private function addForeignKeyIfPossible(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $foreignKeyName,
        string $onDelete = 'cascade'
    ): void {
        if (! $this->canAddForeignKey($table, $column, $referencedTable, $referencedColumn, $foreignKeyName)) {
            return;
        }

        $this->deleteOrphanRows($table, $column, $referencedTable, $referencedColumn);

        Schema::table($table, function (Blueprint $blueprint) use (
            $column,
            $referencedTable,
            $referencedColumn,
            $foreignKeyName,
            $onDelete
        ): void {
            $foreign = $blueprint
                ->foreign($column, $foreignKeyName)
                ->references($referencedColumn)
                ->on($referencedTable)
                ->cascadeOnUpdate();

            if ($onDelete === 'set null') {
                $foreign->nullOnDelete();
            } else {
                $foreign->cascadeOnDelete();
            }
        });
    }

    private function canAddForeignKey(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $foreignKeyName
    ): bool {
        if (! Schema::hasTable($table) || ! Schema::hasTable($referencedTable)) {
            return false;
        }

        if (! Schema::hasColumn($table, $column) || ! Schema::hasColumn($referencedTable, $referencedColumn)) {
            return false;
        }

        if ($this->foreignKeyExists($table, $foreignKeyName)) {
            return false;
        }

        return $this->columnTypesAreCompatible(
            $this->columnType($table, $column),
            $this->columnType($referencedTable, $referencedColumn)
        );
    }

    private function deleteOrphanRows(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn
    ): void {
        DB::table($table)
            ->whereNotNull($column)
            ->whereNotExists(function ($query) use ($column, $referencedTable, $referencedColumn): void {
                $query
                    ->select(DB::raw(1))
                    ->from($referencedTable)
                    ->whereColumn($referencedTable . '.' . $referencedColumn, $table . '.' . $column);
            })
            ->delete();
    }

    private function dropForeignKeyIfExists(string $table, string $foreignKeyName): void
    {
        if (! Schema::hasTable($table) || ! $this->foreignKeyExists($table, $foreignKeyName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($foreignKeyName): void {
            $blueprint->dropForeign($foreignKeyName);
        });
    }

    private function foreignKeyExists(string $table, string $foreignKeyName): bool
    {
        try {
            $driver = DB::getDriverName();

            if ($driver === 'pgsql') {
                return DB::table('information_schema.table_constraints')
                    ->where('table_schema', 'public')
                    ->where('table_name', $table)
                    ->where('constraint_name', $foreignKeyName)
                    ->where('constraint_type', 'FOREIGN KEY')
                    ->exists();
            }

            return DB::table('information_schema.table_constraints')
                ->where('table_name', $table)
                ->where('constraint_name', $foreignKeyName)
                ->where('constraint_type', 'FOREIGN KEY')
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }

    private function columnType(string $table, string $column): ?string
    {
        try {
            $type = strtolower(Schema::getColumnType($table, $column));
        } catch (Throwable) {
            return null;
        }

        return match (true) {
            str_contains($type, 'uuid') => 'uuid',
            str_contains($type, 'bigint') => 'bigint',
            str_contains($type, 'int') => 'int',
            str_contains($type, 'char') || str_contains($type, 'string') || str_contains($type, 'varchar') => 'string',
            default => $type,
        };
    }

    private function columnTypesAreCompatible(?string $childType, ?string $parentType): bool
    {
        if (! $childType || ! $parentType) {
            return false;
        }

        if ($childType === $parentType) {
            return true;
        }

        return $childType === 'string' && $parentType === 'string';
    }
};
