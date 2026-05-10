<?php

namespace App\Services;

use App\Models\DashboardWorkItem;
use App\Models\RiceVariety;
use App\Models\User;
use App\Support\AdminAccess;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DashboardMetricsService
{
    public function __construct(private DashboardOperationsService $operations) {}

    public function buildPayload(string $filter = 'all'): array
    {
        $filter = in_array($filter, ['all', 'in_progress', 'needs_fix'], true) ? $filter : 'all';
        $allItems = $this->loadDashboardItems();
        $srpAssessments = $this->loadSrpAssessments();
        $accessibleFarmerIds = $this->accessibleFarmerIds();

        $filteredItems = $this->filterItems($allItems, $filter)->values();
        $today = now()->toDateString();

        $farmersTotal = $accessibleFarmerIds->count();
        [$areaTotalRai, $areaAverageRai] = $this->plotAreaMetrics($accessibleFarmerIds);

        $riceVarietiesTotal = $this->safeValue(fn (): int => RiceVariety::query()->count(), 0);
        $passedFarmers = $srpAssessments
            ->filter(fn (array $assessment): bool => $accessibleFarmerIds->contains($assessment['user_id'] ?? null))
            ->where('status', 'passed')
            ->count();

        if ($passedFarmers === 0) {
            $passedFarmers = $allItems
                ->where('status', 'passed')
                ->pluck('farmer_name')
                ->filter()
                ->unique()
                ->count();
        }

        $srpPassRate = $farmersTotal > 0 ? (int) round(($passedFarmers / $farmersTotal) * 100) : 0;

        $responseRequiredItems = $filteredItems->where('response_required', true);
        $responseRespondedTotal = $responseRequiredItems->filter(fn (DashboardWorkItem $item): bool => $this->responseTimestamp($item) !== null)->count();
        $responseRequiredTotal = $responseRequiredItems->count();
        $responseRatePercent = $responseRequiredTotal > 0
            ? (int) round(($responseRespondedTotal / $responseRequiredTotal) * 100)
            : 0;

        $updatedAt = $allItems->max('updated_at');
        $allIssuesTotal = $this->operations->allIssueCount();
        $todayDueItems = $allItems
            ->filter(fn (DashboardWorkItem $item): bool => $this->isDueToday($item, $today) && $item->status !== 'passed');
        $fallbackOpenItems = $allItems
            ->filter(fn (DashboardWorkItem $item): bool => $item->status !== 'passed')
            ->take(4);
        $todayTasksTotal = $todayDueItems->count();
        $dueTodayTotal = $filteredItems
            ->filter(fn (DashboardWorkItem $item): bool => $this->isDueToday($item, $today) && $item->status !== 'passed')
            ->count();

        if ($todayTasksTotal === 0 && $fallbackOpenItems->isNotEmpty()) {
            $todayTasksTotal = $fallbackOpenItems->count();
        }

        if ($dueTodayTotal === 0) {
            $dueTodayTotal = $filteredItems
                ->filter(fn (DashboardWorkItem $item): bool => $item->status !== 'passed')
                ->take(4)
                ->count();
        }

        return [
            'active_filter' => $filter,
            'updated_at' => filled($updatedAt) ? (string) $updatedAt : null,
            'summary' => [
                'farmers_total' => $farmersTotal,
                'area_total_rai' => (int) round($areaTotalRai),
                'area_average_rai' => $areaAverageRai,
                'rice_varieties_total' => $riceVarietiesTotal,
                'srp_pass_rate' => $srpPassRate,
                'srp_passed_total' => $passedFarmers,
            ],
            'quick_stats' => [
                'today_tasks_total' => $todayTasksTotal,
                'today_overdue_total' => $allItems->filter(fn (DashboardWorkItem $item): bool => $this->isOverdue($item, $today))->count(),
                'new_issue_reports_total' => $allItems
                    ->filter(fn (DashboardWorkItem $item): bool => $this->isIssueReportItem($item) && $item->status !== 'passed')
                    ->count(),
                'pending_documents_total' => $allItems->where('status', 'pending_review')->count(),
                'all_issues_total' => $allIssuesTotal,
                'urgent_alerts_total' => $allItems->where('priority', 'urgent')->where('status', '!=', 'passed')->count(),
            ],
            'status_overview' => [
                'pending_review_total' => $filteredItems->where('status', 'pending_review')->count(),
                'issues_found_total' => $filteredItems->where('status', 'needs_fix')->count(),
                'due_today_total' => $dueTodayTotal,
                'response_rate_percent' => $responseRatePercent,
                'response_responded_total' => $responseRespondedTotal,
                'response_required_total' => $responseRequiredTotal,
            ],
            'urgent_alerts' => $this->buildUrgentAlerts($filteredItems),
            'recent_activities' => $this->buildRecentActivities($filteredItems),
            'today_followups' => $this->buildTodayFollowups($filteredItems, $today),
            'latest_assessments' => $srpAssessments->isNotEmpty()
                ? $this->buildLatestAssessmentsFromSrp(
                    $srpAssessments->filter(
                        fn (array $assessment): bool => $accessibleFarmerIds->contains($assessment['user_id'] ?? null)
                    )->values()
                )
                : $this->buildLatestAssessments($filteredItems),
            'common_issues' => $this->buildCommonIssues($filteredItems),
            'calendar_events' => $this->buildCalendarEvents($allItems),
        ];
    }

    private function plotAreaMetrics(Collection $accessibleFarmerIds): array
    {
        if (! $this->safeHasTable('plots') || $accessibleFarmerIds->isEmpty()) {
            return [0.0, 0.0];
        }

        $raiExpression = $this->safeHasColumn('plots', 'area_rai')
            ? "CAST(COALESCE(NULLIF(plots.area_rai::text, ''), '0') AS NUMERIC)"
            : '0';
        $squareWaExpression = $this->safeHasColumn('plots', 'area_sq_wa')
            ? "CAST(COALESCE(NULLIF(plots.area_sq_wa::text, ''), '0') AS NUMERIC)"
            : '0';
        $totalAreaExpression = "({$raiExpression} + ({$squareWaExpression} / 400.0))";

        $total = $this->safeValue(
            fn (): float => (float) (DB::table('plots')
                ->whereIn('plots.user_id', $accessibleFarmerIds->all())
                ->selectRaw("COALESCE(SUM({$totalAreaExpression}), 0) as aggregate")
                ->value('aggregate') ?? 0),
            0.0,
        );

        $average = $this->safeValue(
            fn (): float => (float) (DB::table('plots')
                ->whereIn('plots.user_id', $accessibleFarmerIds->all())
                ->selectRaw("COALESCE(AVG({$totalAreaExpression}), 0) as aggregate")
                ->value('aggregate') ?? 0),
            0.0,
        );

        return [$total, $average];
    }

    private function loadDashboardItems(): Collection
    {
        if (! $this->safeHasTable('dashboard_work_items')) {
            return collect();
        }

        $query = DashboardWorkItem::query();

        if ($this->safeHasColumn('dashboard_work_items', 'last_activity_at')) {
            $query->orderByDesc('last_activity_at');
        }

        if ($this->safeHasColumn('dashboard_work_items', 'updated_at')) {
            $query->orderByDesc('updated_at');
        }

        return $this->safeValue(fn (): Collection => $query->get(), collect());
    }

    private function filterItems(Collection $items, string $filter): Collection
    {
        return match ($filter) {
            'in_progress' => $items->whereIn('status', ['pending_review', 'in_progress']),
            'needs_fix' => $items->where('status', 'needs_fix'),
            default => $items,
        };
    }

    private function buildUrgentAlerts(Collection $items): array
    {
        return $items
            ->where('priority', 'urgent')
            ->take(4)
            ->map(function (DashboardWorkItem $item): array {
                $note = trim((string) ($item->latest_note ?? ''));

                return [
                    'title' => $item->task_title,
                    'detail' => $note !== '' ? $note : null,
                    'meta' => trim(implode(' • ', array_filter([
                        $item->farmer_name,
                        $item->plot_code,
                        optional($item->last_activity_at)->translatedFormat('d M Y'),
                    ]))),
                    'chip_label' => $this->statusLabel($item->status),
                    'chip_class' => $this->statusChipClass($item->status),
                    'dot_class' => $this->priorityDotClass($item->priority),
                    'detail_url' => $this->detailUrlForItem($item),
                    'detail_label' => 'ดูรายละเอียด',
                ];
            })
            ->all();
    }

    private function buildRecentActivities(Collection $items): array
    {
        return $items
            ->take(4)
            ->map(function (DashboardWorkItem $item): array {
                $activityTimestamp = $item->last_activity_at ?? $item->updated_at ?? $item->created_at;

                return [
                    'time' => optional($activityTimestamp)->translatedFormat('d M Y') ?: '-',
                    'title' => $item->task_title,
                    'subtitle' => trim(implode(' • ', array_filter([$item->farmer_name, $item->plot_code, $item->latest_note]))),
                    'tag_label' => $this->statusLabel($item->status),
                    'tag_class' => $this->activityTagClass($item->status),
                ];
            })
            ->all();
    }

    private function buildTodayFollowups(Collection $items, string $today): array
    {
        $followups = $items
            ->filter(fn (DashboardWorkItem $item): bool => $this->isDueToday($item, $today) && $item->status !== 'passed')
            ->take(5)
            ->map(function (DashboardWorkItem $item): array {
                return [
                    'id' => $item->id,
                    'title' => trim(implode(' ', array_filter([$item->task_title, $item->plot_code ? '(' . $item->plot_code . ')' : null]))),
                    'checked' => filled($item->responded_at),
                ];
            })
            ->values();

        if ($followups->isEmpty()) {
            $followups = $items
                ->filter(function (DashboardWorkItem $item) use ($today): bool {
                    if ($item->status === 'passed') {
                        return false;
                    }
                    $planned = $this->plannedDateForItem($item);

                    // Only show items with no planned date OR planned date is today/past (not future)
                    return $planned === null || $planned->toDateString() <= $today;
                })
                ->sortByDesc(fn (DashboardWorkItem $item) => $item->last_activity_at ?? $item->updated_at ?? $item->created_at)
                ->take(5)
                ->map(function (DashboardWorkItem $item): array {
                    $plannedDate = $this->plannedDateForItem($item);
                    $dateLabel = $plannedDate?->translatedFormat('d M Y')
                        ?: optional($item->last_activity_at ?? $item->updated_at ?? $item->created_at)->translatedFormat('d M Y');

                    $title = trim(implode(' ', array_filter([
                        $item->task_title,
                        $item->plot_code ? '(' . $item->plot_code . ')' : null,
                    ])));

                    if (filled($dateLabel)) {
                        $title .= ' • ' . $dateLabel;
                    }

                    return [
                        'id' => $item->id,
                        'title' => $title,
                        'checked' => filled($item->responded_at),
                    ];
                })
                ->values();
        }

        return $followups->all();
    }

    private function buildLatestAssessments(Collection $items): array
    {
        return $items
            ->take(5)
            ->map(function (DashboardWorkItem $item): array {
                return [
                    'name' => $item->farmer_name ?: '-',
                    'progress_percent' => (int) $item->progress_percent,
                    'status_label' => $this->statusLabel($item->status),
                    'status_class' => $this->statusChipClass($item->status),
                    'issue_label' => $item->issue_category ?: ($item->latest_note ?: '-'),
                    'updated_at' => optional($item->last_activity_at)->translatedFormat('d M Y'),
                ];
            })
            ->all();
    }

    private function buildLatestAssessmentsFromSrp(Collection $assessments): array
    {
        return $assessments
            ->sortByDesc('updated_at_ts')
            ->take(5)
            ->map(function (array $assessment): array {
                return [
                    'name' => $assessment['name'] ?: '-',
                    'progress_percent' => (int) $assessment['progress_percent'],
                    'status_label' => $this->statusLabel($assessment['status']),
                    'status_class' => $this->statusChipClass($assessment['status']),
                    'issue_label' => $assessment['issue_label'] ?: '-',
                    'updated_at' => $assessment['updated_at_label'] ?: '-',
                ];
            })
            ->values()
            ->all();
    }

    private function responseTimestamp(DashboardWorkItem $item): mixed
    {
        return $item->responded_at ?? $item->resolved_at ?? null;
    }

    private function buildCommonIssues(Collection $items): array
    {
        $issueItems = $items
            ->map(function (DashboardWorkItem $item): ?array {
                $label = $this->commonIssueLabel($item);

                if ($label === null) {
                    return null;
                }

                return [
                    'label' => $label,
                ];
            })
            ->filter()
            ->values();

        $total = $issueItems->count();

        if ($total === 0) {
            return [];
        }

        return $issueItems
            ->groupBy('label')
            ->map(function (Collection $group, string $issue) use ($total): array {
                $count = $group->count();

                return [
                    'label' => $issue,
                    'count' => $count,
                    'total' => $total,
                    'percent' => (int) round(($count / $total) * 100),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->take(5)
            ->map(function (array $issue, int $index): array {
                $issue['rank'] = $index + 1;

                return $issue;
            })
            ->all();
    }

    private function countCommonIssues(Collection $items): int
    {
        return $items
            ->filter(fn (DashboardWorkItem $item): bool => $this->commonIssueLabel($item) !== null)
            ->count();
    }

    private function commonIssueLabel(DashboardWorkItem $item): ?string
    {
        $text = mb_strtolower(trim(implode(' ', array_filter([
            (string) $item->issue_category,
            (string) $item->task_title,
            (string) $item->latest_note,
        ]))));

        if ($text === '') {
            return null;
        }

        return match (true) {
            str_contains($text, 'รายงานปัญหาการใช้งานระบบ'),
            str_contains($text, 'support'),
            str_contains($text, 'system') => 'รายงานปัญหาการใช้งานระบบ',

            str_contains($text, 'รายงานปัญหาการปลูกข้าว'),
            str_contains($text, 'issue_found'),
            str_contains($text, 'แจ้งเตือนรายงานปัญหา') => 'รายงานปัญหาการปลูกข้าว',

            str_contains($text, 'water'),
            str_contains($text, 'จัดการน้ำ'),
            str_contains($text, 'น้ำ') => 'การจัดการน้ำ',

            str_contains($text, 'disease'),
            str_contains($text, 'โรคพืช'),
            str_contains($text, 'โรค') => 'การจัดการโรคพืช',

            str_contains($text, 'pest'),
            str_contains($text, 'ศัตรูพืช'),
            str_contains($text, 'แมลง') => 'การจัดการศัตรูพืช',

            str_contains($text, 'fert'),
            str_contains($text, 'fertilizer'),
            str_contains($text, 'ปุ๋ย') => 'หว่านปุ๋ย',

            str_contains($text, 'soil'),
            str_contains($text, 'prep'),
            str_contains($text, 'เตรียมดิน'),
            str_contains($text, 'ดิน') => 'การเตรียมดิน',

            str_contains($text, 'harvest'),
            str_contains($text, 'เก็บเกี่ยว') => 'การเก็บเกี่ยว',

            str_contains($text, 'mill'),
            str_contains($text, 'ขายข้าว'),
            str_contains($text, 'โรงสี') => 'ขายข้าวเข้าโรงสี',

            str_contains($text, 'test'),
            str_contains($text, 'ทดสอบ') => 'ทดสอบระบบ',

            str_contains($text, 'เอกสาร'),
            str_contains($text, 'document'),
            str_contains($text, 'srp') => null,

            default => filled($item->issue_category) ? (string) $item->issue_category : null,
        };
    }

    private function buildCalendarEvents(Collection $items): array
    {
        return $items
            ->map(function (DashboardWorkItem $item): ?array {
                $plannedDate = $this->plannedDateForItem($item);

                if (! $plannedDate) {
                    return null;
                }

                return [
                    'date' => $plannedDate->toDateString(),
                    'status' => $item->status,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function loadSrpAssessments(): Collection
    {
        $requiredTables = [
            'users',
            'plots',
            'planting_plans',
            'activity_events',
            'activity_types',
        ];

        foreach ($requiredTables as $table) {
            if (! $this->safeHasTable($table)) {
                return collect();
            }
        }

        $plotRows = $this->safeValue(function (): Collection {
            return DB::table('plots')
                ->join('users', 'users.id', '=', 'plots.user_id')
                ->leftJoin('farmer_profiles as profiles', 'profiles.user_id', '=', 'users.id')
                ->leftJoin('farmer_registrations as registrations', 'registrations.profile_id', '=', 'profiles.id')
                ->where('users.role', 'FARMER')
                ->select([
                    'plots.id as plot_id',
                    'plots.user_id',
                    'users.username',
                    'profiles.full_name',
                    'registrations.reg_number as farmer_code',
                ])
                ->get();
        }, collect());

        if ($plotRows->isEmpty()) {
            return collect();
        }

        $typeCount = max(1, $this->safeValue(fn (): int => (int) DB::table('activity_types')->count(), 1));
        $activityRows = $this->safeValue(function (): Collection {
            return DB::table('activity_events as events')
                ->join('planting_plans as plans', 'plans.id', '=', 'events.plan_id')
                ->join('plots', 'plots.id', '=', 'plans.plot_id')
                ->select([
                    'plots.id as plot_id',
                    'events.type_id',
                    'events.status',
                    'events.issue_found',
                    'events.performed_at',
                ])
                ->orderByDesc('events.performed_at')
                ->get()
                ->map(function ($row) {
                    $row->performed_at = filled($row->performed_at) ? Carbon::parse($row->performed_at) : null;

                    return $row;
                });
        }, collect());

        $activitiesByPlot = $activityRows->groupBy('plot_id');

        $plotAssessments = $plotRows->map(function ($plot) use ($activitiesByPlot, $typeCount): array {
            $plotActivities = $activitiesByPlot->get($plot->plot_id, collect());
            $latestByType = $plotActivities
                ->groupBy('type_id')
                ->map(fn (Collection $rows) => $rows->sortByDesc('performed_at')->first());

            $completedSteps = $latestByType
                ->filter(fn ($row) => strtoupper((string) ($row->status ?? '')) === 'DONE')
                ->count();

            $latestActivity = $plotActivities->sortByDesc('performed_at')->first();
            $progressPercent = $latestByType->isNotEmpty()
                ? (int) round(($completedSteps / $typeCount) * 100)
                : 0;

            return [
                'user_id' => (string) $plot->user_id,
                'plot_id' => $plot->plot_id,
                'name' => $plot->full_name ?: $plot->username ?: '-',
                'farmer_code' => $plot->farmer_code ?: '-',
                'status' => $this->srpStatusFromActivities($latestByType, $typeCount),
                'progress_percent' => $progressPercent,
                'issue_label' => filled($latestActivity?->issue_found) ? (string) $latestActivity->issue_found : null,
                'updated_at_ts' => $latestActivity?->performed_at,
            ];
        });

        return $plotAssessments
            ->groupBy('user_id')
            ->map(function (Collection $rows): array {
                $status = $this->summarizeSrpStatus($rows->pluck('status'));
                $updatedAt = $rows->pluck('updated_at_ts')->filter()->sortDesc()->first();
                $issueLabel = $rows->pluck('issue_label')->filter()->first();

                return [
                    'user_id' => $rows->first()['user_id'] ?? null,
                    'name' => $rows->first()['name'] ?? '-',
                    'farmer_code' => $rows->first()['farmer_code'] ?? '-',
                    'status' => $status,
                    'progress_percent' => (int) round((float) $rows->avg('progress_percent')),
                    'issue_label' => $issueLabel ?: 'ไม่มี',
                    'updated_at_ts' => $updatedAt,
                    'updated_at_label' => $updatedAt?->translatedFormat('d M Y') ?: '-',
                ];
            })
            ->values();
    }

    private function srpStatusFromActivities(Collection $latestByType, int $typeCount): string
    {
        if ($latestByType->isEmpty()) {
            return 'pending_review';
        }

        if ($latestByType->contains(fn ($row) => in_array(strtoupper((string) ($row->status ?? '')), ['FAILED', 'NEEDS_FIX'], true))) {
            return 'needs_fix';
        }

        $completedSteps = $latestByType
            ->filter(fn ($row) => strtoupper((string) ($row->status ?? '')) === 'DONE')
            ->count();

        if ($completedSteps >= $typeCount) {
            return 'passed';
        }

        return 'in_progress';
    }

    private function summarizeSrpStatus(Collection $statuses): string
    {
        if ($statuses->isEmpty()) {
            return 'pending_review';
        }

        if ($statuses->contains('needs_fix')) {
            return 'needs_fix';
        }

        if ($statuses->every(fn ($status) => $status === 'passed')) {
            return 'passed';
        }

        if ($statuses->contains('in_progress')) {
            return 'in_progress';
        }

        return 'pending_review';
    }

    private function isDueToday(DashboardWorkItem $item, string $today): bool
    {
        return $this->plannedDateForItem($item)?->toDateString() === $today;
    }

    private function isOverdue(DashboardWorkItem $item, string $today): bool
    {
        $plannedDate = $this->plannedDateForItem($item);

        return filled($plannedDate)
            && $plannedDate->toDateString() < $today
            && $item->status !== 'passed';
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

        $meta = $item->meta;

        if (is_string($meta)) {
            $decoded = json_decode($meta, true);
            $meta = is_array($decoded) ? $decoded : [];
        }

        $plannedDate = is_array($meta) ? ($meta['planned_date'] ?? null) : null;

        if (! filled($plannedDate)) {
            return null;
        }

        try {
            return Carbon::parse((string) $plannedDate);
        } catch (Throwable) {
            return null;
        }
    }

    private function isIssueReportItem(DashboardWorkItem $item): bool
    {
        $meta = $item->meta;
        if (is_string($meta) && $meta !== '') {
            $decoded = json_decode($meta, true);
            $meta = is_array($decoded) ? $decoded : [];
        }

        return is_array($meta) && isset($meta['report_type']) && (string) $meta['report_type'] !== '';
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending_review' => 'รอตรวจสอบ',
            'in_progress' => 'กำลังตรวจ',
            'needs_fix' => 'ต้องแก้ไข',
            'passed' => 'ผ่านแล้ว',
            default => 'ไม่ระบุสถานะ',
        };
    }

    private function statusChipClass(string $status): string
    {
        return match ($status) {
            'needs_fix' => 'danger',
            'in_progress' => 'info',
            'passed' => 'success',
            default => 'warning',
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

    private function activityTagClass(string $status): string
    {
        return match ($status) {
            'needs_fix' => 'warn',
            'passed' => 'ok',
            default => 'info',
        };
    }

    private function detailUrlForItem(DashboardWorkItem $item): string
    {
        return match ($item->issue_category) {
            'น้ำ' => '/admin/tracking/water/detail',
            'โรคพืช' => '/admin/tracking/disease/detail',
            'ศัตรูพืช' => '/admin/tracking/pest/detail',
            'เอกสาร' => '/admin/alerts',
            default => '/admin/alerts',
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

    /**
     * @template TValue
     *
     * @param  callable(): TValue  $callback
     * @param  TValue  $fallback
     * @return TValue
     */
    private function safeValue(callable $callback, mixed $fallback): mixed
    {
        try {
            return $callback();
        } catch (Throwable) {
            return $fallback;
        }
    }

    private function accessibleFarmerIds(): Collection
    {
        if (! $this->safeHasTable('users')) {
            return collect();
        }

        $actingUser = Auth::user();

        $farmerIds = $this->safeValue(
            fn (): Collection => User::query()
                ->where('role', 'FARMER')
                ->pluck('id'),
            collect()
        );

        if (AdminAccess::isSuperAdmin($actingUser)) {
            return $farmerIds->values();
        }

        if ($this->safeHasTable('admin_farmer_assignments') && filled($actingUser?->id)) {
            $assignedIds = $this->safeValue(
                fn (): Collection => DB::table('admin_farmer_assignments')
                    ->where('admin_user_id', $actingUser->id)
                    ->pluck('farmer_user_id'),
                collect()
            );

            if ($assignedIds->isNotEmpty()) {
                return $farmerIds->intersect($assignedIds)->values();
            }
        }

        return $farmerIds->values();
    }
}
