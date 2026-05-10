<?php

namespace App\Services;

use App\Support\SearchTextMatcher;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon as SupportCarbon;
use Throwable;

class LegacyTrackingService
{
    public function listActivities(string $typeCode, array $filters = []): Collection
    {
        $query = $this->baseQuery($typeCode);

        $search = trim((string) ($filters['q'] ?? ''));
        $round = trim((string) ($filters['round'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $date = trim((string) ($filters['date'] ?? ''));

        if ($round !== '') {
            $query->where('events.sequence_no', (int) $round);
        }

        if ($status !== '') {
            $query->where('events.status', $this->toLegacyStatusValue($status));
        }

        if ($date !== '') {
            $query->whereDate('events.performed_at', $date);
        }

        $activities = $this->normalize(
            $query
                ->orderByDesc('events.performed_at')
                ->get()
        );

        $activities = $this->hydrateMissingDetailFields($activities, $typeCode);

        return SearchTextMatcher::filterByPriority($activities, [
            fn ($activity) => $activity->farmer_name ?? null,
            fn ($activity) => $activity->plot_code ?? null,
            fn ($activity) => $activity->plot_reference ?? null,
            fn ($activity) => $activity->round_number ?? null,
            fn ($activity) => $activity->activity_name ?? null,
            fn ($activity) => $activity->details ?? null,
            fn ($activity) => $activity->performed_by_name ?? null,
        ], $search);
    }

    public function findActivity(string $typeCode, ?string $id = null): ?object
    {
        $query = $this->baseQuery($typeCode)->orderByDesc('events.performed_at');

        if ($id !== null) {
            $query->where('events.id', $id);
        }

        $row = $query->first();

        if (! $row) {
            return null;
        }

        return $this->hydrateMissingDetailFields($this->normalize(collect([$row])), $typeCode)->first();
    }

    public function updateStatus(string $activityId, string $status, ?string $reviewedBy = 'admin', ?string $adminNote = null): void
    {
        $payload = [
            'status' => $this->toLegacyStatusValue($status),
        ];

        if ($this->reviewFieldsAvailable()) {
            if ($status === 'pending_review') {
                $payload['reviewed_by'] = null;
                $payload['reviewed_at'] = null;
            } else {
                $payload['reviewed_by'] = filled($reviewedBy) ? $reviewedBy : 'admin';
                $payload['reviewed_at'] = SupportCarbon::now();
            }
        }

        if ($this->adminNoteFieldAvailable()) {
            $payload['admin_note'] = filled($adminNote) ? $adminNote : null;
        }

        DB::table('activity_events')
            ->where('id', $activityId)
            ->update($payload);
    }

    public function deleteActivity(string $typeCode, string $activityId): void
    {
        DB::transaction(function () use ($typeCode, $activityId): void {
            $detailTable = $this->detailTableForTypeCode($typeCode);
            if ($detailTable !== null) {
                DB::table($detailTable)
                    ->where('activity_id', $activityId)
                    ->delete();
            }

            if ($this->trackingAdviceTableAvailable()) {
                $this->deleteTrackingAdviceRows($typeCode, $activityId);
            }

            DB::table('activity_events')
                ->where('id', $activityId)
                ->delete();
        });
    }

    public function printRows(string $typeCode): array
    {
        return $this->listActivities($typeCode)
            ->map(fn ($activity): array => [
                'farmer' => $activity->farmer_name,
                'plot' => $activity->plot_code,
                'plot_reference' => $activity->plot_reference,
                'round' => $activity->round_number ?: '-',
                'activity' => $activity->activity_name,
                'date' => $activity->activity_date?->translatedFormat('d M Y') ?: '-',
                'status' => $this->statusLabel($activity->status),
            ])
            ->all();
    }

    private function baseQuery(string $typeCode)
    {
        $typeId = $this->typeIdFor($typeCode);

        $query = DB::table('activity_events as events')
            ->leftJoin('activity_types as types', 'types.id', '=', 'events.type_id')
            ->leftJoin('planting_plans as plans', 'plans.id', '=', 'events.plan_id')
            ->leftJoin('plots', 'plots.id', '=', 'plans.plot_id')
            ->leftJoin('users', 'users.id', '=', 'plots.user_id')
            ->leftJoin('farmer_profiles as profiles', 'profiles.user_id', '=', 'users.id')
            ->where('events.type_id', $typeId)
            ->select([
                'events.id',
                'events.sequence_no as round_number',
                'events.performed_by_name',
                'events.performed_at as activity_date',
                'events.issue_found',
                'events.status as source_status',
                'types.name_th as activity_name',
                DB::raw("COALESCE(NULLIF(profiles.full_name, ''), NULLIF(events.performed_by_name, ''), users.username, '-') as farmer_name"),
                DB::raw("COALESCE(NULLIF(plots.plot_name, ''), NULLIF(plots.farm_id, ''), '-') as plot_code"),
                DB::raw("COALESCE(NULLIF(plots.farm_id, ''), '-') as plot_reference"),
                DB::raw('NULL as image_url'),
            ]);

        if ($this->reviewFieldsAvailable()) {
            $query->addSelect([
                'events.reviewed_by',
                'events.reviewed_at',
            ]);
        } else {
            $query->addSelect([
                DB::raw('NULL as reviewed_by'),
                DB::raw('NULL as reviewed_at'),
            ]);
        }

        if ($this->adminNoteFieldAvailable()) {
            $query->addSelect('events.admin_note');
        } else {
            $query->addSelect(DB::raw('NULL as admin_note'));
        }

        return $this->applyDetailJoin($query, $typeCode);
    }

    private function applyDetailJoin($query, string $typeCode)
    {
        if ($typeCode === 'SOIL') {
            return $query
                ->leftJoin('soil_prep_details as details', 'details.activity_id', '=', 'events.id')
                ->addSelect([
                    DB::raw($this->soilBooleanLabelExpression('straw_burning', 'เผา', 'ไม่เผา') . ' as straw_burning_label'),
                    DB::raw($this->soilBooleanLabelExpression('land_leveling', 'ปรับระดับแล้ว', 'ยังไม่ปรับระดับ') . ' as land_leveling_label'),
                    DB::raw($this->soilResultExpression() . ' as soil_result'),
                    DB::raw(($this->soilPrepMethodColumnName() !== null ? "COALESCE(NULLIF(details.{$this->soilPrepMethodColumnName()}, ''), '-')" : "'-'") . " as method"),
                    DB::raw("COALESCE(events.issue_found, '-') as details"),
                ]);
        }

        return match ($typeCode) {
            'SOIL' => $query
                ->leftJoin('soil_prep_details as details', 'details.activity_id', '=', 'events.id')
                ->addSelect([
                    DB::raw("CASE WHEN LOWER(COALESCE(details.straw_burning::text, '')) IN ('true', 't', '1', 'yes', 'y', 'เผา') THEN 'เผา' WHEN LOWER(COALESCE(details.straw_burning::text, '')) IN ('false', 'f', '0', 'no', 'n', 'ไม่เผา') THEN 'ไม่เผา' ELSE '-' END as straw_burning_label"),
                    DB::raw("CASE WHEN LOWER(COALESCE(details.land_leveling::text, '')) IN ('true', 't', '1', 'yes', 'y', 'ปรับระดับแล้ว') THEN 'ปรับระดับแล้ว' WHEN LOWER(COALESCE(details.land_leveling::text, '')) IN ('false', 'f', '0', 'no', 'n', 'ยังไม่ปรับระดับ') THEN 'ยังไม่ปรับระดับ' ELSE '-' END as land_leveling_label"),
                    DB::raw("TRIM(BOTH ', ' FROM CONCAT('pH ', COALESCE(details.soil_ph::text, '-'), ', N ', COALESCE(details.soil_n::text, '-'), ', P ', COALESCE(details.soil_p::text, '-'), ', K ', COALESCE(details.soil_k::text, '-'), ', OM ', COALESCE(details.organic_matter, '-'))) as soil_result"),
                    DB::raw(($this->soilPrepMethodColumnName() !== null ? "COALESCE(NULLIF(details.{$this->soilPrepMethodColumnName()}, ''), '-')" : "'-'") . " as method"),
                    DB::raw("COALESCE(events.issue_found, '-') as details"),
                ]),
            'WATER' => $query
                ->leftJoin('water_control_details as details', 'details.activity_id', '=', 'events.id')
                ->addSelect([
                    DB::raw("COALESCE(details.method, '-') as method"),
                    DB::raw("CASE WHEN details.water_level_cm IS NOT NULL THEN CONCAT(details.water_level_cm, ' ซม.') ELSE '-' END as water_level"),
                    DB::raw("TRIM(BOTH ' -' FROM CONCAT(COALESCE(details.ref_point, ''), CASE WHEN details.note IS NOT NULL AND details.ref_point IS NOT NULL THEN ' - ' ELSE '' END, COALESCE(details.note, ''))) as details"),
                ]),
            'FERT' => $query
                ->leftJoin('fertilization_details as details', 'details.activity_id', '=', 'events.id')
                ->addSelect([
                    DB::raw("COALESCE(details.fertilizer_kind, '-') as method"),
                    DB::raw("COALESCE(details.fertilizer_formula, '-') as fertilizer_type"),
                    DB::raw("CASE WHEN details.qty_kg_per_rai IS NOT NULL THEN CONCAT(details.qty_kg_per_rai, ' กก./ไร่') ELSE '-' END as amount_per_rai"),
                    DB::raw("COALESCE(details.fertilizer_kind, '-') as details"),
                ]),
            'PEST' => $query
                ->leftJoin('pest_control_details as details', 'details.activity_id', '=', 'events.id')
                ->addSelect([
                    DB::raw("COALESCE(details.pest_type, '-') as pest_type"),
                    DB::raw("COALESCE(details.chemical_common_name, '-') as chemical_name"),
                    DB::raw("CASE WHEN details.amount_used IS NOT NULL OR details.water_liters IS NOT NULL THEN CONCAT(COALESCE(details.amount_used::text, '-'), ' / ', COALESCE(details.water_liters::text, '-'), ' ลิตร') ELSE '-' END as mix_ratio"),
                    DB::raw("COALESCE(details.chemical_common_name, '-') as details"),
                ]),
            'DISEASE' => $query
                ->leftJoin('disease_control_details as details', 'details.activity_id', '=', 'events.id')
                ->addSelect([
                    DB::raw("COALESCE(details.disease_type, '-') as disease_type"),
                    DB::raw("COALESCE(details.chemical_comm_name, '-') as chemical_name"),
                    DB::raw("CASE WHEN details.amount_used IS NOT NULL THEN CONCAT(details.amount_used, ' หน่วย') ELSE '-' END as used_amount"),
                    DB::raw("CASE WHEN details.water_liters IS NOT NULL THEN CONCAT(details.water_liters, ' ลิตร') ELSE '-' END as mix_ratio"),
                    DB::raw("COALESCE(details.chemical_comm_name, '-') as details"),
                ]),
            'HARVEST' => $query
                ->leftJoin('harvest_details as details', 'details.activity_id', '=', 'events.id')
                ->addSelect([
                    'details.harvest_start_date as started_at',
                    'details.harvest_end_date as ended_at',
                    'details.total_yield_kg as yield_amount_kg',
                    'details.moisture_percent',
                    DB::raw("COALESCE(events.issue_found, '-') as details"),
                ]),
            'SALE' => $query
                ->leftJoin('sale_details as details', 'details.activity_id', '=', 'events.id')
                ->addSelect([
                    'details.mill_name',
                    'details.product_name',
                    'details.ticket_no as document_number',
                    'details.ticket_no as queue_number',
                    'details.plate_no as vehicle_plate',
                    'details.in_time as time_in',
                    'details.out_time as time_out',
                    'details.weight_total_kg as pre_mill_weight_kg',
                    'details.weight_net_kg as net_weight_kg',
                    DB::raw('NULL as post_mill_weight_kg'),
                    'details.price_per_kg',
                    'details.total_income',
                    DB::raw("COALESCE(events.issue_found, '-') as details"),
                ]),
            default => $query,
        };
    }

    private function normalize(Collection $rows): Collection
    {
        return $rows->map(function ($row) {
            $row->status = $this->normalizeStatus(
                $row->source_status ?? null,
                $row->reviewed_by ?? null,
                $row->reviewed_at ?? null,
            );
            $row->activity_date = filled($row->activity_date ?? null) ? Carbon::parse($row->activity_date) : null;
            $row->reviewed_at = filled($row->reviewed_at ?? null) ? Carbon::parse($row->reviewed_at) : null;
            $row->started_at = filled($row->started_at ?? null) ? Carbon::parse($row->started_at) : null;
            $row->ended_at = filled($row->ended_at ?? null) ? Carbon::parse($row->ended_at) : null;
            $row->details = filled($row->details ?? null) ? $row->details : '-';

            return $row;
        });
    }

    private function hydrateMissingDetailFields(Collection $rows, string $typeCode): Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        if ($typeCode === 'SOIL') {
            $rows = $this->hydrateSoilFieldsFromDetailTable($rows);
        }

        $rows = $this->hydrateFieldsFromLegacyTable($rows, $typeCode);

        return $rows;
    }

    private function hydrateSoilFieldsFromDetailTable(Collection $rows): Collection
    {
        $methodColumn = $this->soilPrepMethodColumnName();

        if ($methodColumn === null) {
            return $rows;
        }

        $activityIds = $rows->pluck('id')->filter()->values();

        if ($activityIds->isEmpty()) {
            return $rows;
        }

        $detailsByActivityId = DB::table('soil_prep_details')
            ->whereIn('activity_id', $activityIds)
            ->select([
                'activity_id',
                DB::raw("COALESCE(NULLIF({$methodColumn}, ''), '-') as method_value"),
            ])
            ->get()
            ->keyBy('activity_id');

        return $rows->map(function ($row) use ($detailsByActivityId) {
            if (! $this->textLooksEmpty($row->method ?? null)) {
                return $row;
            }

            $detail = $detailsByActivityId->get($row->id ?? null);

            if ($detail && ! $this->textLooksEmpty($detail->method_value ?? null)) {
                $row->method = $detail->method_value;
            }

            return $row;
        });
    }

    private function hydrateFieldsFromLegacyTable(Collection $rows, string $typeCode): Collection
    {
        $legacyTable = $this->legacyTableForTypeCode($typeCode);

        if ($legacyTable === null || ! $this->legacyTableAvailable($legacyTable)) {
            return $rows;
        }

        $plotReferences = $rows->pluck('plot_reference')->filter()->unique()->values();
        $dates = $rows
            ->map(fn ($row) => $row->activity_date?->toDateString())
            ->filter()
            ->unique()
            ->values();

        if ($plotReferences->isEmpty() || $dates->isEmpty()) {
            return $rows;
        }

        $legacyRows = DB::table($legacyTable)
            ->whereIn('plot_code', $plotReferences)
            ->whereIn('activity_date', $dates)
            ->select($this->legacySelectColumnsForTypeCode($typeCode))
            ->get()
            ->keyBy(fn ($row) => $row->plot_code . '|' . $row->activity_date);

        return $rows->map(function ($row) use ($legacyRows, $typeCode) {
            $key = ($row->plot_reference ?? '') . '|' . ($row->activity_date?->toDateString() ?? '');
            $legacy = $legacyRows->get($key);

            if (! $legacy) {
                if ($typeCode === 'SOIL' && $this->soilResultLooksEmpty($row->soil_result ?? null)) {
                    $row->soil_result = '-';
                }

                return $row;
            }

            return $this->applyLegacyFallback($row, $legacy, $typeCode);
        });
    }

    private function applyLegacyFallback(object $row, object $legacy, string $typeCode): object
    {
        return match ($typeCode) {
            'SOIL' => $this->applySoilLegacyFallback($row, $legacy),
            'WATER' => $this->applyTextFieldFallback($row, $legacy, [
                'method' => ['method'],
                'water_level' => ['water_level'],
                'details' => ['details'],
            ]),
            'FERT' => $this->applyTextFieldFallback($row, $legacy, [
                'method' => ['method'],
                'fertilizer_type' => ['fertilizer_type'],
                'amount_per_rai' => ['amount_per_rai'],
                'details' => ['details'],
            ]),
            'PEST' => $this->applyTextFieldFallback($row, $legacy, [
                'pest_type' => ['pest_type'],
                'chemical_name' => ['chemical_name'],
                'mix_ratio' => ['mix_ratio'],
                'details' => ['details'],
            ]),
            'DISEASE' => $this->applyTextFieldFallback($row, $legacy, [
                'disease_type' => ['disease_type'],
                'chemical_name' => ['chemical_name'],
                'used_amount' => ['used_amount'],
                'mix_ratio' => ['mix_ratio'],
                'details' => ['details'],
            ]),
            'HARVEST' => $this->applyHarvestLegacyFallback($row, $legacy),
            'SALE' => $this->applySaleLegacyFallback($row, $legacy),
            default => $row,
        };
    }

    private function applySoilLegacyFallback(object $row, object $legacy): object
    {
        if ($this->textLooksEmpty($row->method ?? null)) {
            $legacyMethod = $legacy->method ?: $legacy->soil_preparation_method;
            if (! $this->textLooksEmpty($legacyMethod)) {
                $row->method = $legacyMethod;
            }
        }

        if ($this->soilResultLooksEmpty($row->soil_result ?? null)) {
            if (! $this->textLooksEmpty($legacy->soil_result ?? null)) {
                $row->soil_result = $legacy->soil_result;
            } else {
                $row->soil_result = '-';
            }
        }

        if ($this->textLooksEmpty($row->straw_burning_label ?? null) && ! $this->textLooksEmpty($legacy->details ?? null)) {
            $legacyDetails = (string) $legacy->details;

            if (str_contains($legacyDetails, 'ไม่เผา')) {
                $row->straw_burning_label = 'ไม่เผา';
            } elseif (str_contains($legacyDetails, 'เผา')) {
                $row->straw_burning_label = 'เผา';
            }
        }

        if ($this->textLooksEmpty($row->details ?? null) && ! $this->textLooksEmpty($legacy->details ?? null)) {
            $row->details = $legacy->details;
        }

        return $row;
    }

    private function applyTextFieldFallback(object $row, object $legacy, array $fieldMap): object
    {
        foreach ($fieldMap as $targetField => $sourceFields) {
            if (! $this->textLooksEmpty($row->{$targetField} ?? null)) {
                continue;
            }

            foreach ($sourceFields as $sourceField) {
                $candidate = $legacy->{$sourceField} ?? null;

                if (! $this->textLooksEmpty($candidate)) {
                    $row->{$targetField} = $candidate;
                    break;
                }
            }
        }

        return $row;
    }

    private function applyHarvestLegacyFallback(object $row, object $legacy): object
    {
        $row = $this->applyTextFieldFallback($row, $legacy, [
            'details' => ['details'],
        ]);

        if (blank($row->started_at ?? null) && filled($legacy->started_at ?? null)) {
            $row->started_at = Carbon::parse($legacy->started_at);
        }

        if (blank($row->ended_at ?? null) && filled($legacy->ended_at ?? null)) {
            $row->ended_at = Carbon::parse($legacy->ended_at);
        }

        foreach (['yield_amount_kg', 'moisture_percent'] as $field) {
            if (blank($row->{$field} ?? null) && filled($legacy->{$field} ?? null)) {
                $row->{$field} = $legacy->{$field};
            }
        }

        return $row;
    }

    private function applySaleLegacyFallback(object $row, object $legacy): object
    {
        $row = $this->applyTextFieldFallback($row, $legacy, [
            'mill_name' => ['mill_name'],
            'product_name' => ['product_name'],
            'document_number' => ['document_number'],
            'queue_number' => ['queue_number'],
            'vehicle_plate' => ['vehicle_plate'],
            'time_in' => ['time_in'],
            'time_out' => ['time_out'],
            'details' => ['details'],
        ]);

        foreach ([
            'pre_mill_weight_kg',
            'post_mill_weight_kg',
            'net_weight_kg',
            'price_per_kg',
            'total_income',
        ] as $field) {
            if (blank($row->{$field} ?? null) && filled($legacy->{$field} ?? null)) {
                $row->{$field} = $legacy->{$field};
            }
        }

        return $row;
    }

    private function legacyTableForTypeCode(string $typeCode): ?string
    {
        return match ($typeCode) {
            'SOIL' => 'prep_tracking_activities',
            'WATER' => 'water_tracking_activities',
            'FERT' => 'fertilizer_tracking_activities',
            'PEST' => 'pest_tracking_activities',
            'DISEASE' => 'disease_tracking_activities',
            'HARVEST' => 'harvest_tracking_activities',
            'SALE' => 'mill_tracking_activities',
            default => null,
        };
    }

    private function legacySelectColumnsForTypeCode(string $typeCode): array
    {
        $columns = ['plot_code', 'activity_date'];

        return match ($typeCode) {
            'SOIL' => array_merge($columns, ['method', 'soil_preparation_method', 'soil_result', 'details']),
            'WATER' => array_merge($columns, ['method', 'water_level', 'details']),
            'FERT' => array_merge($columns, ['method', 'fertilizer_type', 'amount_per_rai', 'details']),
            'PEST' => array_merge($columns, ['pest_type', 'chemical_name', 'mix_ratio', 'details']),
            'DISEASE' => array_merge($columns, ['disease_type', 'chemical_name', 'used_amount', 'mix_ratio', 'details']),
            'HARVEST' => array_merge($columns, ['started_at', 'ended_at', 'yield_amount_kg', 'moisture_percent', 'details']),
            'SALE' => array_merge($columns, [
                'mill_name',
                'product_name',
                'document_number',
                'queue_number',
                'vehicle_plate',
                'time_in',
                'time_out',
                'pre_mill_weight_kg',
                'post_mill_weight_kg',
                'net_weight_kg',
                'price_per_kg',
                'total_income',
                'details',
            ]),
            default => $columns,
        };
    }

    private function legacyTableAvailable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    private function hydrateSoilFieldsFromLegacyTable(Collection $rows): Collection
    {
        if (! $this->prepTrackingActivitiesTableAvailable()) {
            return $rows;
        }

        $plotReferences = $rows->pluck('plot_reference')->filter()->unique()->values();
        $dates = $rows
            ->map(fn ($row) => $row->activity_date?->toDateString())
            ->filter()
            ->unique()
            ->values();

        if ($plotReferences->isEmpty() || $dates->isEmpty()) {
            return $rows;
        }

        $legacyRows = DB::table('prep_tracking_activities')
            ->whereIn('plot_code', $plotReferences)
            ->whereIn('activity_date', $dates)
            ->select([
                'plot_code',
                'activity_date',
                'method',
                'soil_preparation_method',
                'soil_result',
                'details',
            ])
            ->get()
            ->keyBy(fn ($row) => $row->plot_code . '|' . $row->activity_date);

        return $rows->map(function ($row) use ($legacyRows) {
            $key = ($row->plot_reference ?? '') . '|' . ($row->activity_date?->toDateString() ?? '');
            $legacy = $legacyRows->get($key);

            if (! $legacy) {
                return $row;
            }

            if ($this->textLooksEmpty($row->method ?? null)) {
                $legacyMethod = $legacy->method ?: $legacy->soil_preparation_method;
                if (! $this->textLooksEmpty($legacyMethod)) {
                    $row->method = $legacyMethod;
                }
            }

            if ($this->soilResultLooksEmpty($row->soil_result ?? null) && ! $this->textLooksEmpty($legacy->soil_result ?? null)) {
                $row->soil_result = $legacy->soil_result;
            }

            if ($this->textLooksEmpty($row->straw_burning_label ?? null) && ! $this->textLooksEmpty($legacy->details ?? null)) {
                if (str_contains((string) $legacy->details, 'ไม่เผา')) {
                    $row->straw_burning_label = 'ไม่เผา';
                } elseif (str_contains((string) $legacy->details, 'เผา')) {
                    $row->straw_burning_label = 'เผา';
                }
            }

            return $row;
        });
    }

    private function typeIdFor(string $typeCode): int
    {
        return (int) DB::table('activity_types')->where('code', $typeCode)->value('id');
    }

    private function normalizeStatus(?string $status, mixed $reviewedBy = null, mixed $reviewedAt = null): string
    {
        if (
            strtoupper((string) $status) !== 'ACTIVE'
            && blank($reviewedBy)
            && blank($reviewedAt)
        ) {
            return 'pending_review';
        }

        return match (strtoupper((string) $status)) {
            'DONE' => 'passed',
            'NEEDS_FIX' => 'needs_fix',
            'FAILED' => 'failed',
            default => 'pending_review',
        };
    }

    private function toLegacyStatusValue(string $status): string
    {
        return match ($status) {
            'passed' => 'DONE',
            'needs_fix' => 'NEEDS_FIX',
            'failed' => 'FAILED',
            default => 'ACTIVE',
        };
    }

    private function detailTableForTypeCode(string $typeCode): ?string
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

    private function detailPageKey(string $typeCode, string $activityId): string
    {
        $prefix = match ($typeCode) {
            'SOIL' => 'tracking-prep-detail-',
            'WATER' => 'tracking-water-detail-',
            'FERT' => 'tracking-fertilizer-detail-',
            'PEST' => 'tracking-pest-detail-',
            'DISEASE' => 'tracking-disease-detail-',
            'HARVEST' => 'tracking-harvest-detail-',
            'SALE' => 'tracking-mill-detail-',
            default => 'tracking-detail-',
        };

        return $prefix . $activityId;
    }

    private function reviewFieldsAvailable(): bool
    {
        try {
            return Schema::hasColumns('activity_events', ['reviewed_by', 'reviewed_at']);
        } catch (Throwable) {
            return false;
        }
    }

    private function adminNoteFieldAvailable(): bool
    {
        try {
            return Schema::hasColumn('activity_events', 'admin_note');
        } catch (Throwable) {
            return false;
        }
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

    private function soilPrepDetailColumnAvailable(string $column): bool
    {
        try {
            return Schema::hasColumn('soil_prep_details', $column);
        } catch (Throwable) {
            return false;
        }
    }

    private function soilDetailTextExpression(string $column): string
    {
        return $this->soilPrepDetailColumnAvailable($column)
            ? "COALESCE(details.{$column}::text, '-')"
            : "'-'";
    }

    private function soilBooleanLabelExpression(string $column, string $trueLabel, string $falseLabel): string
    {
        if (! $this->soilPrepDetailColumnAvailable($column)) {
            return "'-'";
        }

        return "CASE "
            . "WHEN LOWER(COALESCE(details.{$column}::text, '')) IN ('true', 't', '1', 'yes', 'y', '{$trueLabel}') THEN '{$trueLabel}' "
            . "WHEN LOWER(COALESCE(details.{$column}::text, '')) IN ('false', 'f', '0', 'no', 'n', '{$falseLabel}') THEN '{$falseLabel}' "
            . "ELSE '-' END";
    }

    private function soilResultExpression(): string
    {
        $parts = [
            "'pH '",
            $this->soilDetailTextExpression('soil_ph'),
        ];

        if ($this->soilPrepDetailColumnAvailable('soil_n')) {
            $parts[] = "', N '";
            $parts[] = $this->soilDetailTextExpression('soil_n');
        }

        if ($this->soilPrepDetailColumnAvailable('soil_p')) {
            $parts[] = "', P '";
            $parts[] = $this->soilDetailTextExpression('soil_p');
        }

        if ($this->soilPrepDetailColumnAvailable('soil_k')) {
            $parts[] = "', K '";
            $parts[] = $this->soilDetailTextExpression('soil_k');
        }

        if ($this->soilPrepDetailColumnAvailable('organic_matter')) {
            $parts[] = "', OM '";
            $parts[] = $this->soilDetailTextExpression('organic_matter');
        } elseif ($this->soilPrepDetailColumnAvailable('soil_npk')) {
            $parts[] = "', NPK '";
            $parts[] = $this->soilDetailTextExpression('soil_npk');
        }

        return "TRIM(BOTH ', ' FROM CONCAT(" . implode(', ', $parts) . "))";
    }

    private function prepTrackingActivitiesTableAvailable(): bool
    {
        try {
            return Schema::hasTable('prep_tracking_activities');
        } catch (Throwable) {
            return false;
        }
    }

    private function textLooksEmpty(mixed $value): bool
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' || $text === '-';
    }

    private function soilResultLooksEmpty(mixed $value): bool
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '' || $text === '-') {
            return true;
        }

        $normalized = preg_replace('/\s+/', '', $text) ?? $text;

        return str_contains($normalized, 'pH-')
            && str_contains($normalized, 'N-')
            && str_contains($normalized, 'P-')
            && str_contains($normalized, 'K-')
            && str_contains($normalized, 'OM-');
    }

    private function trackingAdviceTableAvailable(): bool
    {
        try {
            return Schema::hasTable('tracking_advices');
        } catch (Throwable) {
            return false;
        }
    }

    private function deleteTrackingAdviceRows(string $typeCode, string $activityId): void
    {
        if ($this->trackingAdviceColumnAvailable('activity_event_id')) {
            DB::table('tracking_advices')
                ->where('activity_event_id', $activityId)
                ->delete();

            return;
        }

        if ($this->trackingAdviceColumnAvailable('page_key')) {
            DB::table('tracking_advices')
                ->where('page_key', $this->detailPageKey($typeCode, $activityId))
                ->delete();

            return;
        }

        if ($this->trackingAdviceColumnAvailable('detail_url')) {
            DB::table('tracking_advices')
                ->where('detail_url', 'like', '%' . $activityId)
                ->delete();
        }
    }

    private function trackingAdviceColumnAvailable(string $column): bool
    {
        try {
            return Schema::hasColumn('tracking_advices', $column);
        } catch (Throwable) {
            return false;
        }
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'passed' => 'เสร็จสิ้นแล้ว',
            'needs_fix' => 'ต้องแก้ไข',
            'failed' => 'ไม่ผ่าน',
            default => 'รอตรวจสอบ',
        };
    }
}
