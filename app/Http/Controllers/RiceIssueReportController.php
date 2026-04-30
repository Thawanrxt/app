<?php

namespace App\Http\Controllers;

use App\Support\SearchTextMatcher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RiceIssueReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);
        $activities = $this->loadIssueActivities($filters);

        return view('admin.report-rice', [
            'activities' => $activities,
            ...$filters,
            'activityOptions' => $this->activityOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function show(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);
        $activityId = trim((string) $request->query('id', ''));

        abort_if($activityId === '', 404);

        $activity = $this->baseIssueQuery()
            ->where('events.id', $activityId)
            ->first();

        abort_unless($activity, 404);

        $activity = $this->normalizeActivity($activity);

        return view('admin.report-rice-detail', [
            'activity' => $activity,
            'activityLabel' => $activity->activity_name ?: '-',
            'backUrl' => '/admin/report/rice?' . http_build_query([
                'q' => $filters['query'],
                'activity' => $filters['activity'],
                'status' => $filters['status'],
                'date' => $filters['date'],
            ]),
            'sourceDetailUrl' => $this->trackingDetailUrlForType($activity->type_code, $activity->id),
        ]);
    }

    public function print(Request $request): View
    {
        $activities = $this->loadIssueActivities($this->filtersFromRequest($request));

        return view('admin.print-tracking', [
            'title' => 'รายงานปัญหาการปลูกข้าว',
            'rows' => $activities->map(fn ($activity): array => [
                'farmer' => $activity->farmer_name,
                'plot' => $activity->plot_code,
                'round' => $activity->round_number ?: '-',
                'activity' => $activity->activity_name,
                'date' => optional($activity->activity_date)->translatedFormat('d M Y') ?: '-',
                'status' => $this->statusLabel($activity->status),
            ])->all(),
        ]);
    }

    private function loadIssueActivities(array $filters): Collection
    {
        $query = $this->baseIssueQuery();

        if ($filters['activity'] !== '') {
            $query->where('types.code', $filters['activity']);
        }

        if ($filters['status'] !== '') {
            $query->where('events.status', $this->toLegacyStatusValue($filters['status']));
        }

        if ($filters['date'] !== '') {
            $query->whereDate('events.performed_at', $filters['date']);
        }

        $activities = $query
            ->orderByDesc('events.performed_at')
            ->get()
            ->map(fn ($activity) => $this->normalizeActivity($activity))
            ->values();

        return SearchTextMatcher::filterByPriority($activities, [
            fn ($activity) => $activity->farmer_name ?? null,
            fn ($activity) => $activity->plot_code ?? null,
            fn ($activity) => $activity->plot_reference ?? null,
            fn ($activity) => $activity->round_number ?? null,
            fn ($activity) => $activity->activity_name ?? null,
            fn ($activity) => $activity->details ?? null,
            fn ($activity) => $activity->performed_by_name ?? null,
        ], $filters['query']);
    }

    private function baseIssueQuery()
    {
        return DB::table('activity_events as events')
            ->join('activity_types as types', 'types.id', '=', 'events.type_id')
            ->leftJoin('planting_plans as plans', 'plans.id', '=', 'events.plan_id')
            ->leftJoin('plots', 'plots.id', '=', 'plans.plot_id')
            ->leftJoin('users', 'users.id', '=', 'plots.user_id')
            ->leftJoin('farmer_profiles as profiles', 'profiles.user_id', '=', 'users.id')
            ->whereNotNull('events.issue_found')
            ->where('events.issue_found', '!=', '')
            ->select([
                'events.id',
                'events.sequence_no as round_number',
                'events.status as source_status',
                'events.issue_found',
                'events.performed_at as activity_date',
                'events.performed_by_name',
                'types.code as type_code',
                'types.name_th as activity_name',
                DB::raw("COALESCE(NULLIF(profiles.full_name, ''), NULLIF(events.performed_by_name, ''), users.username, '-') as farmer_name"),
                DB::raw("COALESCE(NULLIF(plots.plot_name, ''), NULLIF(plots.farm_id, ''), '-') as plot_code"),
                DB::raw("COALESCE(NULLIF(plots.farm_id, ''), '-') as plot_reference"),
                DB::raw("COALESCE(events.issue_found, '-') as details"),
                DB::raw('NULL as image_url'),
            ]);
    }

    private function normalizeActivity(object $activity): object
    {
        $activity->status = $this->normalizeStatus($activity->source_status ?? null);
        $activity->activity_date = filled($activity->activity_date ?? null) ? Carbon::parse($activity->activity_date) : null;
        $activity->detail_url = '/admin/report/rice/detail?' . http_build_query([
            'id' => $activity->id,
        ]);

        return $activity;
    }

    private function filtersFromRequest(Request $request): array
    {
        return [
            'query' => trim((string) $request->query('q', '')),
            'activity' => trim((string) $request->query('activity', '')),
            'status' => trim((string) $request->query('status', '')),
            'date' => trim((string) $request->query('date', '')),
        ];
    }

    private function activityOptions(): array
    {
        return [
            '' => 'กิจกรรม',
            'SOIL' => 'การเตรียมดิน',
            'WATER' => 'การจัดการน้ำ',
            'FERT' => 'หว่านปุ๋ย',
            'PEST' => 'การจัดการศัตรูพืช',
            'DISEASE' => 'การจัดการโรคพืช',
            'HARVEST' => 'การเก็บเกี่ยว',
            'SALE' => 'ขายข้าวเข้าโรงสี',
        ];
    }

    private function statusOptions(): array
    {
        return [
            '' => 'สถานะ',
            'pending_review' => 'รอตรวจสอบ',
            'passed' => 'ผ่านแล้ว',
            'needs_fix' => 'ต้องแก้ไข',
            'failed' => 'ไม่ผ่าน',
        ];
    }

    private function normalizeStatus(?string $status): string
    {
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

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'passed' => 'เสร็จสิ้นแล้ว',
            'needs_fix' => 'ต้องแก้ไข',
            'failed' => 'ไม่ผ่าน',
            default => 'รอตรวจสอบ',
        };
    }

    private function trackingDetailUrlForType(string $typeCode, string $activityId): string
    {
        return match ($typeCode) {
            'SOIL' => '/admin/tracking/prep/detail/' . $activityId,
            'WATER' => '/admin/tracking/water/detail/' . $activityId,
            'FERT' => '/admin/tracking/fertilizer/detail/' . $activityId,
            'PEST' => '/admin/tracking/pest/detail/' . $activityId,
            'DISEASE' => '/admin/tracking/disease/detail/' . $activityId,
            'HARVEST' => '/admin/tracking/harvest/detail/' . $activityId,
            'SALE' => '/admin/tracking/mill/detail/' . $activityId,
            default => '/admin/report/rice',
        };
    }
}
