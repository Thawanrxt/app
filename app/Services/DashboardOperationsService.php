<?php

namespace App\Services;

use App\Models\DashboardWorkItem;
use App\Support\SearchTextMatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DashboardOperationsService
{
    public function resolveDetailUrl(DashboardWorkItem $item): string
    {
        return $this->detailUrlForItem($item);
    }

    public function alertItems(array $filters = []): Collection
    {
        return $this->filterDashboardItems($this->dashboardItems(), $filters)
            ->filter(fn (DashboardWorkItem $item): bool => $item->status !== 'passed')
            ->sortByDesc(fn (DashboardWorkItem $item) => optional($this->itemTimestamp($item))->timestamp ?? 0)
            ->values()
            ->map(function (DashboardWorkItem $item): array {
                $detail = trim((string) ($item->latest_note ?? ''));
                $meta = trim(implode(' • ', array_filter([
                    $item->farmer_name,
                    $item->plot_code,
                    optional($item->last_activity_at)->translatedFormat('d M Y H:i'),
                ])));

                return [
                    'id' => $item->id,
                    'title' => $item->task_title,
                    'detail' => $detail !== '' ? $detail : 'ยังไม่มีรายละเอียดเพิ่มเติม',
                    'chip_label' => $this->statusLabel($item->status),
                    'chip_class' => $this->statusChipClass($item->status),
                    'dot_class' => $this->priorityDotClass($item->priority),
                    'meta' => $meta,
                    'detail_url' => $this->detailUrlForItem($item),
                    'detail_label' => 'ดูรายละเอียด',
                ];
            });
    }

    public function activityItems(array $filters = []): Collection
    {
        return $this->filterDashboardItems($this->dashboardItems(), $filters)
            ->sortByDesc(fn (DashboardWorkItem $item) => optional($this->itemTimestamp($item))->timestamp ?? 0)
            ->values()
            ->map(function (DashboardWorkItem $item): array {
                $subtitle = trim(implode(' • ', array_filter([
                    $item->farmer_name,
                    $item->plot_code,
                    $item->latest_note,
                ])));

                return [
                    'id' => $item->id,
                    'time' => optional($item->last_activity_at)->format('H:i') ?: '-',
                    'title' => $item->task_title,
                    'subtitle' => $subtitle !== '' ? $subtitle : 'ยังไม่มีรายละเอียดเพิ่มเติม',
                    'tag_label' => $this->statusLabel($item->status),
                    'tag_class' => $this->activityTagClass($item->status),
                ];
            });
    }

    public function printSummaryRows(array $filters = []): array
    {
        return $this->filterDashboardItems($this->dashboardItems(), $filters)
            ->sortByDesc(fn (DashboardWorkItem $item) => optional($this->itemTimestamp($item))->timestamp ?? 0)
            ->values()
            ->map(function (DashboardWorkItem $item): array {
                return [
                    'farmer' => $item->farmer_name ?: '-',
                    'plot' => $item->plot_code ?: '-',
                    'round' => $this->metaValue($item, 'round') ?: '-',
                    'activity' => $item->task_title ?: '-',
                    'date' => optional($item->last_activity_at)->translatedFormat('d M Y H:i') ?: '-',
                    'status' => $this->statusLabelForPrint($item->status),
                ];
            })
            ->all();
    }

    private function dashboardItems(): Collection
    {
        if (! $this->safeHasTable('dashboard_work_items')) {
            return collect();
        }

        return $this->safeValue(
            function (): Collection {
                $query = DashboardWorkItem::query();

                if ($this->safeHasColumn('dashboard_work_items', 'last_activity_at')) {
                    $query->orderByDesc('last_activity_at');
                }

                if ($this->safeHasColumn('dashboard_work_items', 'updated_at')) {
                    $query->orderByDesc('updated_at');
                }

                return $query->get();
            },
            collect(),
        );
    }

    private function filterDashboardItems(Collection $items, array $filters = []): Collection
    {
        $query = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        if ($status !== '' && $status !== 'all') {
            $items = $items->where('status', $status);
        }

        if ($query === '') {
            return $items;
        }

        return SearchTextMatcher::filterByPriority($items, [
            fn (DashboardWorkItem $item) => $item->task_title,
            fn (DashboardWorkItem $item) => $item->farmer_name,
            fn (DashboardWorkItem $item) => $item->plot_code,
            fn (DashboardWorkItem $item) => $item->issue_category,
            fn (DashboardWorkItem $item) => $this->metaValue($item, 'round'),
            fn (DashboardWorkItem $item) => $item->latest_note,
        ], $query);
    }

    private function itemTimestamp(DashboardWorkItem $item): mixed
    {
        return $item->last_activity_at ?? $item->updated_at ?? $item->created_at ?? null;
    }

    private function detailUrlForItem(DashboardWorkItem $item): string
    {
        $metaDetailUrl = $this->metaValue($item, 'detail_url');
        if (filled($metaDetailUrl)) {
            return (string) $metaDetailUrl;
        }

        $metaTypeCode = strtoupper((string) $this->metaValue($item, 'type_code'));
        $metaActivityId = $this->metaValue($item, 'activity_id');
        if ($metaTypeCode !== '' && filled($metaActivityId)) {
            return $this->trackingDetailUrlForTypeCode($metaTypeCode, (string) $metaActivityId);
        }

        $matchedTrackingUrl = $this->findTrackingDetailUrl($item);
        if ($matchedTrackingUrl !== null) {
            return $matchedTrackingUrl;
        }

        $reportType = (string) $this->metaValue($item, 'report_type');
        $reportId = $this->metaValue($item, 'report_id');
        if ($reportType === 'system' && filled($reportId)) {
            return '/admin/report/system/detail?id=' . urlencode((string) $reportId);
        }
        if ($reportType === 'rice' && filled($reportId)) {
            return '/admin/report/rice/detail?id=' . urlencode((string) $reportId);
        }

        return match ($this->normalizedIssueCategory($item)) {
            'document' => '/admin/srp/farmers',
            default => '/admin',
        };
    }

    private function findTrackingDetailUrl(DashboardWorkItem $item): ?string
    {
        $typeCode = $this->trackingTypeCodeForItem($item);

        if ($typeCode === null) {
            return null;
        }

        foreach (['activity_events', 'activity_types', 'planting_plans', 'plots', 'users'] as $table) {
            if (! $this->safeHasTable($table)) {
                return null;
            }
        }

        $activityId = $this->safeValue(function () use ($item, $typeCode): ?string {
            $query = DB::table('activity_events as events')
                ->join('activity_types as types', 'types.id', '=', 'events.type_id')
                ->leftJoin('planting_plans as plans', 'plans.id', '=', 'events.plan_id')
                ->leftJoin('plots', 'plots.id', '=', 'plans.plot_id')
                ->leftJoin('users', 'users.id', '=', 'plots.user_id')
                ->leftJoin('farmer_profiles as profiles', 'profiles.user_id', '=', 'users.id')
                ->where('types.code', $typeCode);

            $plotCode = trim((string) $item->plot_code);
            $farmerName = trim((string) $item->farmer_name);

            if ($plotCode !== '' || $farmerName !== '') {
                $query->where(function ($builder) use ($plotCode, $farmerName): void {
                    if ($plotCode !== '') {
                        $builder
                            ->orWhere('plots.plot_name', $plotCode)
                            ->orWhere('plots.farm_id', $plotCode);
                    }

                    if ($farmerName !== '') {
                        $builder
                            ->orWhere('profiles.full_name', $farmerName)
                            ->orWhere('users.username', $farmerName)
                            ->orWhere('events.performed_by_name', $farmerName);
                    }
                });
            }

            return $query
                ->orderByDesc('events.performed_at')
                ->value('events.id');
        }, null);

        if (! filled($activityId)) {
            return null;
        }

        return $this->trackingDetailUrlForTypeCode($typeCode, (string) $activityId);
    }

    private function trackingTypeCodeForItem(DashboardWorkItem $item): ?string
    {
        return match ($this->normalizedIssueCategory($item)) {
            'water' => 'WATER',
            'disease' => 'DISEASE',
            'pest' => 'PEST',
            'fertilizer' => 'FERT',
            'harvest' => 'HARVEST',
            'mill' => 'SALE',
            'prep' => 'SOIL',
            default => null,
        };
    }

    private function normalizedIssueCategory(DashboardWorkItem $item): string
    {
        $text = mb_strtolower(trim(implode(' ', array_filter([
            (string) $item->issue_category,
            (string) $item->task_title,
            (string) $item->latest_note,
        ]))));

        if ($text === '') {
            return '';
        }

        return match (true) {
            str_contains($text, 'น้ำ') => 'water',
            str_contains($text, 'โรค') => 'disease',
            str_contains($text, 'ศัตรูพืช') || str_contains($text, 'แมลง') => 'pest',
            str_contains($text, 'ปุ๋ย') => 'fertilizer',
            str_contains($text, 'เก็บเกี่ยว') => 'harvest',
            str_contains($text, 'โรงสี') || str_contains($text, 'ขายข้าว') => 'mill',
            str_contains($text, 'เตรียมดิน') || str_contains($text, 'ดิน') => 'prep',
            str_contains($text, 'เอกสาร') || str_contains($text, 'srp') => 'document',
            default => '',
        };
    }

    private function trackingDetailUrlForTypeCode(string $typeCode, string $activityId): string
    {
        return match ($typeCode) {
            'SOIL' => '/admin/tracking/prep/detail/' . $activityId,
            'WATER' => '/admin/tracking/water/detail/' . $activityId,
            'FERT' => '/admin/tracking/fertilizer/detail/' . $activityId,
            'PEST' => '/admin/tracking/pest/detail/' . $activityId,
            'DISEASE' => '/admin/tracking/disease/detail/' . $activityId,
            'HARVEST' => '/admin/tracking/harvest/detail/' . $activityId,
            'SALE' => '/admin/tracking/mill/detail/' . $activityId,
            default => '/admin',
        };
    }

    private function metaValue(DashboardWorkItem $item, string $key): mixed
    {
        return is_array($item->meta) ? ($item->meta[$key] ?? null) : null;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending_review' => 'รอตรวจสอบ',
            'in_progress' => 'กำลังตรวจ',
            'needs_fix' => 'ต้องแก้ไข',
            'passed' => 'ผ่านแล้ว',
            'failed' => 'ไม่ผ่าน',
            default => 'ไม่ระบุสถานะ',
        };
    }

    private function statusChipClass(string $status): string
    {
        return match ($status) {
            'needs_fix', 'failed' => 'danger',
            'in_progress' => 'info',
            'passed' => 'success',
            default => 'warning',
        };
    }

    private function activityTagClass(string $status): string
    {
        return match ($status) {
            'needs_fix', 'failed' => 'warn',
            'passed' => 'ok',
            default => 'info',
        };
    }

    private function priorityDotClass(string $priority): string
    {
        return match ($priority) {
            'urgent' => 'danger',
            'medium' => 'warning',
            default => 'info',
        };
    }

    private function statusLabelForPrint(string $status): string
    {
        return match ($status) {
            'passed' => 'เสร็จสิ้นแล้ว',
            'needs_fix' => 'ต้องแก้ไข',
            'failed' => 'ไม่ผ่าน',
            'in_progress' => 'กำลังตรวจ',
            default => 'รอตรวจสอบ',
        };
    }

    private function safeHasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    private function safeHasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (Throwable) {
            return false;
        }
    }

    private function safeValue(callable $callback, mixed $fallback): mixed
    {
        try {
            return $callback();
        } catch (Throwable) {
            return $fallback;
        }
    }
}
