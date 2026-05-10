<?php

namespace App\Services;

use App\Models\DashboardWorkItem;
use App\Support\SearchTextMatcher;
use Carbon\Carbon;
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
            ->sortByDesc(fn (DashboardWorkItem $item) => $this->itemTimestampValue($item))
            ->values()
            ->map(function (DashboardWorkItem $item): array {
                $detail = trim((string) ($item->latest_note ?? ''));
                $timestamp = $this->itemTimestamp($item);
                $meta = trim(implode(' • ', array_filter([
                    $item->farmer_name,
                    $item->plot_code,
                ])));

                return [
                    'id' => $item->id,
                    'title' => $item->task_title,
                    'detail' => $detail !== '' ? $detail : 'ยังไม่มีรายละเอียดเพิ่มเติม',
                    'chip_label' => $this->statusLabel($item->status),
                    'chip_class' => $this->statusChipClass($item->status),
                    'dot_class' => $this->priorityDotClass($item->priority),
                    'meta' => $meta,
                    'sent_at_label' => $timestamp ? $timestamp->translatedFormat('d M Y H:i') : '-',
                    'detail_url' => $this->detailUrlForItem($item),
                    'detail_label' => 'ดูรายละเอียด',
                ];
            });
    }

    public function activityItems(array $filters = []): Collection
    {
        $today = now()->toDateString();

        return $this->filterActivityItems($this->dashboardItems(), $filters)
            ->filter(fn (DashboardWorkItem $item): bool => $this->isDueToday($item, $today) && $item->status !== 'passed')
            ->sortByDesc(fn (DashboardWorkItem $item) => $this->itemTimestampValue($item))
            ->take(5)
            ->values()
            ->map(function (DashboardWorkItem $item): array {
                $timestamp = $this->itemTimestamp($item);
                $subtitle = trim(implode(' • ', array_filter([
                    $item->farmer_name,
                    $item->plot_code,
                    $item->latest_note,
                ])));

                return [
                    'id' => $item->id,
                    'time' => $timestamp ? $timestamp->format('H:i') : '-',
                    'date_label' => $timestamp ? $timestamp->format('d/m/Y') : '-',
                    'title' => $item->task_title,
                    'subtitle' => $subtitle !== '' ? $subtitle : 'ยังไม่มีรายละเอียดเพิ่มเติม',
                    'category_key' => $this->activityCategoryKey($item),
                    'category_label' => $this->activityCategoryLabel($item),
                    'tag_label' => $this->statusLabel($item->status),
                    'tag_class' => $this->activityTagClass($item->status),
                    'detail_url' => $this->detailUrlForItem($item),
                    'detail_label' => 'ดูรายละเอียด',
                ];
            });
    }

    public function recentActivityItems(array $filters = []): Collection
    {
        return $this->filterDashboardItems($this->dashboardItems(), $filters)
            ->filter(fn (DashboardWorkItem $item): bool => $item->status !== 'passed')
            ->sortByDesc(fn (DashboardWorkItem $item) => $this->itemTimestampValue($item))
            ->values()
            ->map(function (DashboardWorkItem $item): array {
                $timestamp = $this->itemTimestamp($item);
                $subtitle = trim(implode(' • ', array_filter([
                    $item->farmer_name ?: (string) $this->metaValue($item, 'farmer_name'),
                    $item->plot_code ?: (string) $this->metaValue($item, 'plot_code'),
                    $item->latest_note,
                ])));

                return [
                    'id' => $item->id,
                    'time' => $timestamp ? $timestamp->format('H:i') : '-',
                    'date_label' => $timestamp ? $timestamp->translatedFormat('d M Y') : '-',
                    'title' => $item->task_title,
                    'subtitle' => $subtitle !== '' ? $subtitle : 'ยังไม่มีรายละเอียดเพิ่มเติม',
                    'category_key' => $this->activityCategoryKey($item),
                    'category_label' => $this->activityCategoryLabel($item),
                    'tag_label' => $this->statusLabel($item->status),
                    'tag_class' => $this->activityTagClass($item->status),
                    'detail_url' => $this->detailUrlForItem($item),
                    'detail_label' => 'ดูรายละเอียด',
                ];
            });
    }

    public function todayTaskItems(array $filters = []): Collection
    {
        $today = trim((string) ($filters['date'] ?? now()->toDateString()));

        return $this->filterActivityItems($this->dashboardItems(), $filters)
            ->filter(fn (DashboardWorkItem $item): bool => $this->isDueToday($item, $today) && $item->status !== 'passed')
            ->sortByDesc(fn (DashboardWorkItem $item) => $this->itemTimestampValue($item))
            ->values()
            ->map(fn (DashboardWorkItem $item): array => $this->mapFocusItem($item, 'today'));
    }

    public function issueReportFocusItems(array $filters = []): Collection
    {
        $filters['scope'] = 'report';

        return $this->filterDashboardItems($this->dashboardItems(), $filters)
            ->filter(fn (DashboardWorkItem $item): bool => $item->status !== 'passed')
            ->sortByDesc(fn (DashboardWorkItem $item) => $this->itemTimestampValue($item))
            ->values()
            ->map(fn (DashboardWorkItem $item): array => $this->mapFocusItem($item, 'issue_report'));
    }

    public function documentReviewItems(array $filters = []): Collection
    {
        return $this->filterDashboardItems($this->dashboardItems(), $filters)
            ->filter(function (DashboardWorkItem $item): bool {
                return $this->activityCategoryKey($item) === 'document'
                    || ($item->status === 'pending_review' && ! $this->isIssueReportItem($item));
            })
            ->filter(fn (DashboardWorkItem $item): bool => $item->status !== 'passed')
            ->sortByDesc(fn (DashboardWorkItem $item) => $this->itemTimestampValue($item))
            ->values()
            ->map(fn (DashboardWorkItem $item): array => $this->mapFocusItem($item, 'document'));
    }

    public function allIssueFocusItems(array $filters = []): Collection
    {
        return $this->allIssueBaseItems($filters)
            ->sortByDesc(fn (DashboardWorkItem $item) => $this->itemTimestampValue($item))
            ->values()
            ->map(fn (DashboardWorkItem $item): array => $this->mapFocusItem($item, 'issue_all'));
    }

    public function allIssueCount(): int
    {
        return $this->allIssueBaseItems([])->count();
    }

    private function allIssueBaseItems(array $filters): Collection
    {
        return $this->filterDashboardItems($this->dashboardItems(), $filters)
            ->filter(function (DashboardWorkItem $item): bool {
                return $item->status === 'needs_fix'
                    || $item->status === 'failed'
                    || $this->isIssueReportItem($item);
            });
    }

    public function printSummaryRows(array $filters = []): array
    {
        return $this->filterDashboardItems($this->dashboardItems(), $filters)
            ->sortByDesc(fn (DashboardWorkItem $item) => $this->itemTimestampValue($item))
            ->values()
            ->map(function (DashboardWorkItem $item): array {
                $timestamp = $this->itemTimestamp($item);

                return [
                    'farmer' => $item->farmer_name ?: '-',
                    'plot' => $item->plot_code ?: '-',
                    'round' => $this->metaValue($item, 'round') ?: '-',
                    'activity' => $item->task_title ?: '-',
                    'date' => $timestamp ? $timestamp->translatedFormat('d M Y H:i') : '-',
                    'status' => $this->statusLabelForPrint($item->status),
                ];
            })
            ->all();
    }

    private function dashboardItems(): Collection
    {
        if (! $this->safeHasTable('dashboard_work_items')) {
            return $this->issueReportItems();
        }

        $items = $this->safeValue(
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

        return $items
            ->merge($this->issueReportItems())
            ->sortByDesc(fn (DashboardWorkItem $item) => $this->itemTimestampValue($item))
            ->values();
    }

    private function issueReportItems(): Collection
    {
        return $this->systemIssueItems()
            ->merge($this->riceIssueItems())
            ->values();
    }

    private function systemIssueItems(): Collection
    {
        if (! $this->safeHasTable('support_tickets')) {
            return collect();
        }

        $hasFarmerProfiles = $this->safeHasTable('farmer_profiles');

        return $this->safeValue(function () use ($hasFarmerProfiles): Collection {
            $query = DB::table('support_tickets as tickets')
                ->leftJoin('users', 'users.id', '=', 'tickets.user_id');

            $selects = [
                'tickets.id',
                'tickets.subject',
                'tickets.message',
                'tickets.contact_email',
                'tickets.contact_phone',
                'tickets.status',
                'tickets.created_at',
                'users.username',
            ];

            if ($hasFarmerProfiles) {
                $query->leftJoin('farmer_profiles as profiles', 'profiles.user_id', '=', 'users.id');
                $selects[] = 'profiles.full_name as farmer_name';
            } else {
                $selects[] = DB::raw('NULL as farmer_name');
            }

            return $query
                ->select($selects)
                ->orderByDesc('tickets.created_at')
                ->get()
                ->map(function (object $ticket): DashboardWorkItem {
                    $reporter = trim((string) ($ticket->farmer_name ?? ''));
                    if ($reporter === '') {
                        $reporter = trim((string) ($ticket->username ?? ''));
                    }
                    if ($reporter === '') {
                        $reporter = trim((string) ($ticket->contact_email ?? ''));
                    }
                    if ($reporter === '') {
                        $reporter = trim((string) ($ticket->contact_phone ?? ''));
                    }

                    return new DashboardWorkItem([
                        'id' => 'system-ticket-' . $ticket->id,
                        'farmer_name' => $reporter !== '' ? $reporter : null,
                        'task_title' => 'รายงานปัญหาการใช้งานระบบ',
                        'issue_category' => 'รายงานปัญหาการใช้งานระบบ',
                        'status' => $this->systemTicketStatus((string) ($ticket->status ?? 'OPEN')),
                        'priority' => 'medium',
                        'response_required' => true,
                        'latest_note' => trim(implode(' - ', array_filter([
                            (string) ($ticket->subject ?? ''),
                            (string) ($ticket->message ?? ''),
                        ]))),
                        'last_activity_at' => $ticket->created_at,
                        'meta' => [
                            'report_type' => 'system',
                            'report_id' => $ticket->id,
                            'detail_url' => '/admin/report/system/detail?id=' . urlencode((string) $ticket->id),
                        ],
                    ]);
                });
        }, collect());
    }

    private function riceIssueItems(): Collection
    {
        foreach (['activity_events', 'activity_types'] as $table) {
            if (! $this->safeHasTable($table)) {
                return collect();
            }
        }

        return $this->safeValue(function (): Collection {
            return DB::table('activity_events as events')
                ->join('activity_types as types', 'types.id', '=', 'events.type_id')
                ->leftJoin('planting_plans as plans', 'plans.id', '=', 'events.plan_id')
                ->leftJoin('plots', 'plots.id', '=', 'plans.plot_id')
                ->leftJoin('users', 'users.id', '=', 'plots.user_id')
                ->leftJoin('farmer_profiles as profiles', 'profiles.user_id', '=', 'users.id')
                ->whereNotNull('events.issue_found')
                ->where('events.issue_found', '<>', '')
                ->select([
                    'events.id',
                    'events.status',
                    'events.issue_found',
                    'events.performed_at',
                    'types.name_th as activity_name',
                    DB::raw("COALESCE(NULLIF(profiles.full_name, ''), NULLIF(events.performed_by_name, ''), users.username, '-') as farmer_name"),
                    DB::raw("COALESCE(NULLIF(plots.plot_name, ''), NULLIF(plots.farm_id, ''), '-') as plot_code"),
                ])
                ->orderByDesc('events.performed_at')
                ->get()
                ->map(function (object $activity): DashboardWorkItem {
                    return new DashboardWorkItem([
                        'id' => 'rice-issue-' . $activity->id,
                        'farmer_name' => $activity->farmer_name,
                        'plot_code' => $activity->plot_code,
                        'task_title' => 'รายงานปัญหา: ' . ((string) ($activity->activity_name ?? 'กิจกรรม')),
                        'issue_category' => 'รายงานปัญหาการปลูกข้าว',
                        'status' => $this->riceIssueStatus((string) ($activity->status ?? 'ACTIVE')),
                        'priority' => 'medium',
                        'response_required' => true,
                        'latest_note' => (string) ($activity->issue_found ?? ''),
                        'last_activity_at' => $activity->performed_at,
                        'meta' => [
                            'report_type' => 'rice',
                            'report_id' => $activity->id,
                            'detail_url' => '/admin/report/rice/detail?id=' . urlencode((string) $activity->id),
                        ],
                    ]);
                });
        }, collect());
    }

    private function filterDashboardItems(Collection $items, array $filters = []): Collection
    {
        $query = trim((string) ($filters['q'] ?? ''));
        $scope = trim((string) ($filters['scope'] ?? 'all'));
        $status = trim((string) ($filters['status'] ?? ''));

        if ($status !== '' && $status !== 'all') {
            $items = $items->where('status', $status);
        }

        if ($scope === 'report') {
            $items = $items->filter(fn (DashboardWorkItem $item): bool => $this->isIssueReportItem($item));
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

    private function mapFocusItem(DashboardWorkItem $item, string $context): array
    {
        $timestamp = $this->itemTimestamp($item);
        $categoryLabel = $this->activityCategoryLabel($item);
        $reportLabel = $this->isIssueReportItem($item) ? 'รายงานปัญหา' : $categoryLabel;

        $farmerName = $item->farmer_name ?: (string) $this->metaValue($item, 'farmer_name');
        $plotCode = $item->plot_code ?: (string) $this->metaValue($item, 'plot_code');

        $metaParts = array_filter([
            $farmerName ?: null,
            $plotCode ?: null,
            $timestamp ? $timestamp->translatedFormat('d M Y H:i') : null,
        ]);

        $badge = match ($context) {
            'today' => 'งานติดตามวันนี้',
            'document' => 'เอกสารตรวจสอบ',
            'issue_report' => 'รายงานปัญหาใหม่',
            'issue_all' => 'ปัญหาที่พบ',
            default => $reportLabel,
        };

        return [
            'id' => $item->id,
            'title' => $item->task_title ?: '-',
            'detail' => trim((string) ($item->latest_note ?? '')) ?: 'ยังไม่มีรายละเอียดเพิ่มเติม',
            'meta' => implode(' • ', $metaParts),
            'category_label' => $categoryLabel,
            'badge_label' => $badge,
            'badge_class' => $this->statusChipClass($item->status),
            'status_label' => $this->statusLabel($item->status),
            'status_class' => $this->statusChipClass($item->status),
            'dot_class' => $this->priorityDotClass($item->priority),
            'detail_url' => $this->detailUrlForItem($item),
            'detail_label' => 'ดูรายละเอียด',
        ];
    }

    private function isIssueReportItem(DashboardWorkItem $item): bool
    {
        $reportType = trim((string) $this->metaValue($item, 'report_type'));
        $detailUrl = trim((string) $this->detailUrlForItem($item));

        if ($reportType !== '') {
            return true;
        }

        return str_starts_with($detailUrl, '/admin/report/');
    }

    private function filterActivityItems(Collection $items, array $filters = []): Collection
    {
        $items = $this->filterDashboardItems($items, $filters);

        $category = trim((string) ($filters['category'] ?? 'all'));
        if ($category !== '' && $category !== 'all') {
            $items = $items->filter(fn (DashboardWorkItem $item): bool => $this->activityCategoryKey($item) === $category);
        }

        $date = trim((string) ($filters['date'] ?? ''));
        if ($date !== '') {
            $items = $items->filter(function (DashboardWorkItem $item) use ($date): bool {
                $timestamp = $this->itemTimestamp($item);

                return $timestamp && $timestamp->format('Y-m-d') === $date;
            });
        }

        return $items->values();
    }

    private function itemTimestamp(DashboardWorkItem $item): mixed
    {
        return $item->last_activity_at ?? $item->updated_at ?? $item->created_at ?? null;
    }

    private function isDueToday(DashboardWorkItem $item, string $today): bool
    {
        return $this->plannedDateForItem($item)?->toDateString() === $today;
    }

    private function plannedDateForItem(DashboardWorkItem $item): ?Carbon
    {
        if ($item->due_date instanceof Carbon) {
            return $item->due_date;
        }

        if (filled($item->due_date)) {
            try {
                return Carbon::parse($item->due_date);
            } catch (Throwable) {
                // Fall through to meta lookup.
            }
        }

        $plannedDate = $this->metaValue($item, 'planned_date');

        if (! filled($plannedDate)) {
            return null;
        }

        try {
            return Carbon::parse((string) $plannedDate);
        } catch (Throwable) {
            return null;
        }
    }

    private function itemTimestampValue(DashboardWorkItem $item): int
    {
        $timestamp = $this->itemTimestamp($item);

        return $timestamp ? strtotime((string) $timestamp) ?: 0 : 0;
    }

    private function activityCategoryKey(DashboardWorkItem $item): string
    {
        return match ($this->normalizedIssueCategory($item)) {
            'prep' => 'prep',
            'water' => 'water',
            'fertilizer' => 'fertilizer',
            'pest' => 'pest',
            'disease' => 'disease',
            'harvest' => 'harvest',
            'mill' => 'mill',
            'document' => 'document',
            default => 'general',
        };
    }

    private function activityCategoryLabel(DashboardWorkItem $item): string
    {
        return match ($this->activityCategoryKey($item)) {
            'prep' => 'การเตรียมดิน',
            'water' => 'การจัดการน้ำ',
            'fertilizer' => 'หว่านปุ๋ย',
            'pest' => 'การจัดการศัตรูพืช',
            'disease' => 'การจัดการโรคพืช',
            'harvest' => 'การเก็บเกี่ยว',
            'mill' => 'ขายข้าวเข้าโรงสี',
            'document' => 'เอกสารและข้อมูล',
            default => 'งานติดตามทั่วไป',
        };
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
            str_contains($text, 'น้ำ') || str_contains($text, 'water') => 'water',
            str_contains($text, 'โรค') || str_contains($text, 'disease') => 'disease',
            str_contains($text, 'ศัตรูพืช') || str_contains($text, 'แมลง') || str_contains($text, 'pest') => 'pest',
            str_contains($text, 'ปุ๋ย') || str_contains($text, 'fert') => 'fertilizer',
            str_contains($text, 'เก็บเกี่ยว') || str_contains($text, 'harvest') => 'harvest',
            str_contains($text, 'โรงสี') || str_contains($text, 'ขายข้าว') || str_contains($text, 'mill') || str_contains($text, 'sale') => 'mill',
            str_contains($text, 'เตรียมดิน') || str_contains($text, 'soil') || str_contains($text, 'prep') => 'prep',
            str_contains($text, 'เอกสาร') || str_contains($text, 'srp') || str_contains($text, 'document') => 'document',
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
        if (is_array($item->meta)) {
            return $item->meta[$key] ?? null;
        }

        if (is_string($item->meta) && $item->meta !== '') {
            $decoded = json_decode($item->meta, true);

            return is_array($decoded) ? ($decoded[$key] ?? null) : null;
        }

        return null;
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
            'needs_fix', 'failed' => 'danger',
            'in_progress' => 'info',
            'passed' => 'ok',
            default => 'warn',
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

    private function systemTicketStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'RESOLVED', 'CLOSED' => 'passed',
            'IN_PROGRESS' => 'in_progress',
            'REJECTED' => 'failed',
            default => 'pending_review',
        };
    }

    private function riceIssueStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'DONE' => 'passed',
            'NEEDS_FIX' => 'needs_fix',
            'FAILED' => 'failed',
            default => 'pending_review',
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
