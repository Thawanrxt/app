<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LegacyTrackingApiService
{
    public function __construct(
        private readonly LegacyTrackingService $legacyTrackingService,
    ) {
    }

    public function listActivities(string $typeCode): Collection
    {
        return $this->legacyTrackingService->listActivities($typeCode)->values();
    }

    public function createActivity(string $typeCode, array $payload): object
    {
        $context = DB::transaction(function () use ($typeCode, $payload): array {
            $typeId = $this->resolveTypeId($typeCode);
            $plan = $this->resolvePlan($payload['plot_code']);
            $activityId = (string) Str::uuid();

            DB::table('activity_events')->insert([
                'id' => $activityId,
                'plan_id' => $plan->id,
                'type_id' => $typeId,
                'sequence_no' => $payload['round_number'] ?? $this->nextSequenceNumber($plan->id),
                'performed_by_name' => $payload['performed_by_name'] ?? $payload['farmer_name'] ?? null,
                'performed_at' => $payload['activity_date'],
                'issue_found' => $payload['issue_found'] ?? null,
                // New activities from end users must always start in the admin review queue.
                'status' => 'ACTIVE',
            ]);

            $detailTable = $this->detailTableFor($typeCode);
            if ($detailTable !== null) {
                DB::table($detailTable)->updateOrInsert(
                    ['activity_id' => $activityId],
                    $this->detailPayloadFor($typeCode, $payload)
                );
            }

            return [
                'activity_id' => $activityId,
                'plan' => $plan,
            ];
        });

        $activityId = (string) ($context['activity_id'] ?? '');
        $plan = $context['plan'] ?? null;

        $this->syncDashboardWorkItem($typeCode, $payload, $activityId, $plan);

        return $this->legacyTrackingService->findActivity($typeCode, $activityId)
            ?? (object) ['id' => $activityId];
    }

    private function resolveTypeId(string $typeCode): int
    {
        $typeId = DB::table('activity_types')
            ->where('code', $typeCode)
            ->value('id');

        if (!$typeId) {
            throw ValidationException::withMessages([
                'activity_type' => 'ไม่พบประเภทกิจกรรมที่ต้องการบันทึก',
            ]);
        }

        return (int) $typeId;
    }

    private function resolvePlan(string $plotCode): object
    {
        $plan = DB::table('planting_plans as plans')
            ->join('plots', 'plots.id', '=', 'plans.plot_id')
            ->where('plots.farm_id', $plotCode)
            ->orderByRaw("CASE WHEN COALESCE(plans.status, '') = 'ACTIVE' THEN 0 ELSE 1 END")
            ->orderByDesc('plans.start_date')
            ->orderByDesc('plans.expected_harvest_date')
            ->select(['plans.id', 'plots.id as plot_id', 'plots.farm_id', 'plots.user_id'])
            ->first();

        if (!$plan) {
            throw ValidationException::withMessages([
                'plot_code' => 'ไม่พบแปลงหรือแผนการปลูกสำหรับรหัสแปลงนี้',
            ]);
        }

        return $plan;
    }

    private function nextSequenceNumber(string $planId): int
    {
        return ((int) DB::table('activity_events')
            ->where('plan_id', $planId)
            ->max('sequence_no')) + 1;
    }

    private function detailTableFor(string $typeCode): ?string
    {
        return match ($typeCode) {
            'SOIL' => 'soil_prep_details',
            'WATER' => 'water_control_details',
            'FERT' => 'fertilization_details',
            'PEST' => 'pest_control_details',
            'DISEASE' => 'disease_control_details',
            'HARVEST' => 'harvest_details',
            'SALE' => 'sale_details',
            default => null,
        };
    }

    private function detailPayloadFor(string $typeCode, array $payload): array
    {
        return match ($typeCode) {
            'SOIL' => [
                ...($this->soilPrepMethodPayload($payload)),
                'straw_burning' => $this->boolOrNull($payload['straw_burning'] ?? null),
                'land_leveling' => $this->boolOrNull($payload['land_leveling'] ?? null),
                'soil_ph' => $this->decimalOrNull($payload['soil_ph'] ?? null),
                'soil_n' => $this->decimalOrNull($payload['soil_n'] ?? null),
                'soil_p' => $this->decimalOrNull($payload['soil_p'] ?? null),
                'soil_k' => $this->decimalOrNull($payload['soil_k'] ?? null),
                'organic_matter' => $this->decimalOrNull($payload['organic_matter'] ?? null),
            ],
            'WATER' => [
                'method' => $payload['method'] ?? null,
                'water_level_cm' => $this->integerOrNull($payload['water_level_cm'] ?? $payload['water_level'] ?? null),
                'ref_point' => $payload['ref_point'] ?? null,
                'note' => $payload['details'] ?? null,
            ],
            'FERT' => [
                'fertilizer_kind' => $payload['fertilizer_kind'] ?? $payload['fertilizer_type'] ?? null,
                'fertilizer_formula' => $payload['fertilizer_formula'] ?? null,
                'qty_kg_per_rai' => $this->decimalOrNull($payload['qty_kg_per_rai'] ?? $payload['amount_per_rai'] ?? null),
            ],
            'PEST' => [
                'pest_type' => $payload['pest_type'] ?? null,
                'chemical_common_name' => $payload['chemical_common_name'] ?? $payload['chemical_name'] ?? null,
                'amount_used' => $this->decimalOrNull($payload['amount_used'] ?? null),
                'water_liters' => $this->decimalOrNull($payload['water_liters'] ?? $payload['mix_ratio'] ?? null),
            ],
            'DISEASE' => [
                'disease_type' => $payload['disease_type'] ?? null,
                'chemical_comm_name' => $payload['chemical_comm_name'] ?? $payload['chemical_name'] ?? null,
                'amount_used' => $this->decimalOrNull($payload['amount_used'] ?? $payload['used_amount'] ?? null),
                'water_liters' => $this->decimalOrNull($payload['water_liters'] ?? $payload['mix_ratio'] ?? null),
            ],
            'HARVEST' => [
                'harvest_start_date' => $payload['harvest_start_date'] ?? $payload['started_at'] ?? null,
                'harvest_end_date' => $payload['harvest_end_date'] ?? $payload['ended_at'] ?? null,
                'total_yield_kg' => $this->decimalOrNull($payload['total_yield_kg'] ?? $payload['yield_amount_kg'] ?? null),
                'moisture_percent' => $this->decimalOrNull($payload['moisture_percent'] ?? null),
            ],
            'SALE' => [
                'mill_name' => $payload['mill_name'] ?? null,
                'product_name' => $payload['product_name'] ?? null,
                'ticket_no' => $payload['ticket_no'] ?? $payload['document_number'] ?? $payload['queue_number'] ?? null,
                'plate_no' => $payload['plate_no'] ?? $payload['vehicle_plate'] ?? null,
                'in_time' => $this->timeOrNull($payload['in_time'] ?? $payload['time_in'] ?? null),
                'out_time' => $this->timeOrNull($payload['out_time'] ?? $payload['time_out'] ?? null),
                'weight_total_kg' => $this->decimalOrNull($payload['weight_total_kg'] ?? $payload['pre_mill_weight_kg'] ?? null),
                'weight_net_kg' => $this->decimalOrNull($payload['weight_net_kg'] ?? $payload['net_weight_kg'] ?? $payload['post_mill_weight_kg'] ?? null),
                'price_per_kg' => $this->decimalOrNull($payload['price_per_kg'] ?? null),
                'total_income' => $this->decimalOrNull($payload['total_income'] ?? null),
            ],
            default => [],
        };
    }

    private function soilPrepMethodPayload(array $payload): array
    {
        $column = $this->soilPrepMethodColumnName();

        if ($column === null) {
            return [];
        }

        return [
            $column => $payload['method'] ?? null,
        ];
    }

    private function soilPrepMethodColumnName(): ?string
    {
        try {
            if (Schema::hasColumn('soil_prep_details', 'method')) {
                return 'method';
            }

            if (Schema::hasColumn('soil_prep_details', 'soil_preparation_method')) {
                return 'soil_preparation_method';
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }

    private function decimalOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function integerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function boolOrNull(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }

    private function timeOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', (string) $value) ? (string) $value : null;
    }

    private function syncDashboardWorkItem(string $typeCode, array $payload, string $activityId, ?object $plan): void
    {
        try {
            if ($activityId === '' || ! Schema::hasTable('dashboard_work_items')) {
                return;
            }

            $taskTitle = $this->dashboardTaskTitle($typeCode);
            $note = trim((string) ($payload['issue_found'] ?? $payload['details'] ?? ''));
            $plotCode = trim((string) ($payload['plot_code'] ?? ($plan->farm_id ?? '')));
            $farmerName = trim((string) ($payload['farmer_name'] ?? $payload['performed_by_name'] ?? ''));
            $roundNumber = trim((string) ($payload['round_number'] ?? ''));
            $activityDate = $payload['activity_date'] ?? now();

            $payloadForDashboard = [
                'task_title' => $taskTitle,
                'issue_category' => $this->dashboardIssueCategory($typeCode),
                'status' => 'pending_review',
                'priority' => 'medium',
                'response_required' => true,
                'latest_note' => $note !== '' ? $note : 'มีการอัปเดตกิจกรรมใหม่จากแอพเกษตรกร',
                'meta' => [
                    'source' => 'mobile_tracking',
                    'type_code' => $typeCode,
                    'activity_id' => $activityId,
                    'round' => $roundNumber !== '' ? $roundNumber : null,
                    'plot_code' => $plotCode !== '' ? $plotCode : null,
                    'farmer_name' => $farmerName !== '' ? $farmerName : null,
                ],
            ];

            if (Schema::hasColumn('dashboard_work_items', 'farmer_name')) {
                $payloadForDashboard['farmer_name'] = $farmerName !== '' ? $farmerName : null;
            }

            if (Schema::hasColumn('dashboard_work_items', 'plot_code')) {
                $payloadForDashboard['plot_code'] = $plotCode !== '' ? $plotCode : null;
            }

            if (Schema::hasColumn('dashboard_work_items', 'activity_event_id')) {
                $payloadForDashboard['activity_event_id'] = $activityId;
            }

            if (Schema::hasColumn('dashboard_work_items', 'user_id') && filled($plan->user_id ?? null)) {
                $payloadForDashboard['user_id'] = $plan->user_id;
            }

            if (Schema::hasColumn('dashboard_work_items', 'plot_id') && filled($plan->plot_id ?? null)) {
                $payloadForDashboard['plot_id'] = $plan->plot_id;
            }

            if (Schema::hasColumn('dashboard_work_items', 'last_activity_at')) {
                $payloadForDashboard['last_activity_at'] = $activityDate;
            }

            if (Schema::hasColumn('dashboard_work_items', 'due_date')) {
                $payloadForDashboard['due_date'] = $activityDate;
            }

            if (Schema::hasColumn('dashboard_work_items', 'resolved_at')) {
                $payloadForDashboard['resolved_at'] = null;
            }

            $match = Schema::hasColumn('dashboard_work_items', 'activity_event_id')
                ? ['activity_event_id' => $activityId]
                : ['task_title' => $taskTitle, 'plot_code' => $plotCode];

            $query = DB::table('dashboard_work_items');
            foreach ($match as $column => $value) {
                $query->where($column, $value);
            }

            if ($query->exists()) {
                $query->update(array_merge($payloadForDashboard, [
                    'updated_at' => now(),
                ]));

                return;
            }

            $insertPayload = array_merge($match, $payloadForDashboard, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($this->tablePrimaryKeyUsesUuid('dashboard_work_items')) {
                $insertPayload['id'] = (string) Str::uuid();
            }

            DB::table('dashboard_work_items')->insert($insertPayload);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function dashboardTaskTitle(string $typeCode): string
    {
        return match ($typeCode) {
            'SOIL' => 'ติดตามการเตรียมดินรอบล่าสุด',
            'WATER' => 'ติดตามการจัดการน้ำรอบล่าสุด',
            'FERT' => 'ติดตามการหว่านปุ๋ยรอบล่าสุด',
            'PEST' => 'ติดตามการจัดการศัตรูพืชรอบล่าสุด',
            'DISEASE' => 'ติดตามการจัดการโรคพืชรอบล่าสุด',
            'HARVEST' => 'ติดตามการเก็บเกี่ยวรอบล่าสุด',
            'SALE' => 'ติดตามการขายข้าวเข้าโรงสีรอบล่าสุด',
            default => 'ติดตามกิจกรรมรอบล่าสุด',
        };
    }

    private function dashboardIssueCategory(string $typeCode): string
    {
        return match ($typeCode) {
            'SOIL' => 'การเตรียมดิน',
            'WATER' => 'การจัดการน้ำ',
            'FERT' => 'หว่านปุ๋ย',
            'PEST' => 'การจัดการศัตรูพืช',
            'DISEASE' => 'การจัดการโรคพืช',
            'HARVEST' => 'การเก็บเกี่ยว',
            'SALE' => 'ขายข้าวเข้าโรงสี',
            default => 'กิจกรรมจากแอพ',
        };
    }

    private function tablePrimaryKeyUsesUuid(string $table): bool
    {
        try {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'id')) {
                return false;
            }

            $column = DB::table('information_schema.columns')
                ->where('table_schema', 'public')
                ->where('table_name', $table)
                ->where('column_name', 'id')
                ->value('data_type');

            return $column === 'uuid';
        } catch (Throwable) {
            return false;
        }
    }
}
