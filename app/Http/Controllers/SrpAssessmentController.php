<?php

namespace App\Http\Controllers;

use App\Services\LegacyTrackingService;
use App\Services\SrpFarmerDirectoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SrpAssessmentController extends Controller
{
    public function index(Request $request, SrpFarmerDirectoryService $farmerDirectory): View
    {
        $farmers = $this->applyListFilters($farmerDirectory->farmers(), $request);

        return view('admin.srp-farmers', [
            'farmers' => $farmers,
            'databaseAvailable' => $farmerDirectory->requiredTablesAvailable(),
            'missingTables' => $farmerDirectory->missingRequiredTables(),
            'summary' => $farmerDirectory->summary($farmers),
        ]);
    }

    public function passed(SrpFarmerDirectoryService $farmerDirectory): View
    {
        $farmers = $farmerDirectory->passedFarmers();

        return view('admin.srp-passed-farmers', [
            'farmers' => $farmers,
            'databaseAvailable' => $farmerDirectory->requiredTablesAvailable(),
            'missingTables' => $farmerDirectory->missingRequiredTables(),
            'summary' => $farmerDirectory->summary($farmers),
        ]);
    }

    public function show(string $farmer, SrpFarmerDirectoryService $farmerDirectory): View
    {
        $farmerData = $farmerDirectory->farmers()->firstWhere('slug', $farmer);

        abort_unless($farmerData, 404);

        return view('admin.srp-farmer-detail', [
            'farmer' => $farmerData,
            'databaseAvailable' => $farmerDirectory->requiredTablesAvailable(),
            'missingTables' => $farmerDirectory->missingRequiredTables(),
            'plotSummary' => [
                'total' => $farmerData['plot_count'],
                'with_activity' => collect($farmerData['plots'])->filter(fn (array $plot) => $plot['activity_count'] > 0)->count(),
                'average_progress' => $farmerData['average_progress'],
                'latest_activity_date' => $farmerData['last_activity_at'] ?: '-',
            ],
        ]);
    }

    public function createPlot(string $farmer, SrpFarmerDirectoryService $farmerDirectory): View
    {
        $farmerData = $farmerDirectory->farmers()->firstWhere('slug', $farmer);

        abort_unless($farmerData, 404);

        $riceVarieties = DB::table('rice_varieties')
            ->where('is_active', true)
            ->orderBy('name')
            ->select('id', 'name')
            ->get();

        return view('admin.srp-plot-create', [
            'farmer'        => $farmerData,
            'riceVarieties' => $riceVarieties,
        ]);
    }

    public function storePlot(string $farmer, Request $request, SrpFarmerDirectoryService $farmerDirectory)
    {
        $farmerData = $farmerDirectory->farmers()->firstWhere('slug', $farmer);

        abort_unless($farmerData, 404);

        $validated = $request->validate([
            'plot_name'             => 'required|string|max:255',
            'season_type'           => 'required|string|in:นาปี,นาปรัง 1,นาปรัง 2,นาปรัง 3',
            'crop_type'             => 'required|string|max:100',
            'rice_id'               => 'required|uuid',
            'start_date'            => 'required|date',
            'expected_harvest_date' => 'nullable|date|after_or_equal:start_date',
            'latitude'              => 'nullable|numeric|between:-90,90',
            'longitude'             => 'nullable|numeric|between:-180,180',
            'area_rai'              => 'nullable|integer|min:0',
            'area_ngan'             => 'nullable|integer|min:0',
            'area_sq_wa'            => 'nullable|integer|min:0',
            'area_sq_meter'         => 'nullable|integer|min:0',
        ]);

        // สร้าง farm_id แบบเดียวกับแอพ เช่น FARM-AB1C2D
        do {
            $farmId = 'FARM-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (DB::table('plots')->where('farm_id', $farmId)->exists());

        $plotId = (string) \Illuminate\Support\Str::uuid();

        DB::table('plots')->insert([
            'id'          => $plotId,
            'user_id'     => $farmerData['id'],
            'farm_id'     => $farmId,
            'plot_name'   => $validated['plot_name'],
            'crop_type'   => $validated['crop_type'],
            'area_rai'    => (int) ($validated['area_rai'] ?? 0),
            'area_ngan'   => (int) ($validated['area_ngan'] ?? 0),
            'area_sq_wa'  => (int) ($validated['area_sq_wa'] ?? 0),
            'area_sq_meter' => (int) ($validated['area_sq_meter'] ?? 0),
            'latitude'    => $validated['latitude'] ?? null,
            'longitude'   => $validated['longitude'] ?? null,
            'lat'         => $validated['latitude'] ?? null,
            'lon'         => $validated['longitude'] ?? null,
            'status'      => 'ACTIVE',
        ]);

        DB::table('planting_plans')->insert([
            'id'                    => (string) \Illuminate\Support\Str::uuid(),
            'plot_id'               => $plotId,
            'rice_id'               => $validated['rice_id'],
            'season_type'           => $validated['season_type'],
            'start_date'            => $validated['start_date'],
            'expected_harvest_date' => $validated['expected_harvest_date'] ?? null,
            'status'                => 'ACTIVE',
        ]);

        return redirect("/admin/srp/farmers/{$farmer}")->with('success', "เพิ่มแปลง \"{$validated['plot_name']}\" เรียบร้อยแล้ว ข้อมูลจะปรากฏในแอพของเกษตรกรทันที");
    }

    public function plotOverview(string $farmer, string $plot, SrpFarmerDirectoryService $farmerDirectory, LegacyTrackingService $legacyTracking): View
    {
        $farmerData = $farmerDirectory->farmers()->firstWhere('slug', $farmer);

        abort_unless($farmerData, 404);

        $plotData = collect($farmerData['plots'] ?? [])
            ->first(fn (array $item): bool => (string) ($item['plot_id'] ?? '') === $plot);

        abort_unless($plotData, 404);

        $activities = $this->plotActivities($plotData, $legacyTracking);

        return view('admin.srp-plot-overview', [
            'farmer' => $farmerData,
            'plot' => $plotData,
            'activities' => $activities,
            'statusSummary' => [
                'passed' => $activities->where('status', 'passed')->count(),
                'pending_review' => $activities->where('status', 'pending_review')->count(),
                'needs_fix' => $activities->where('status', 'needs_fix')->count(),
                'in_progress' => $activities->where('status', 'in_progress')->count(),
            ],
            'activityBreakdown' => $activities
                ->groupBy('activity_name')
                ->map(fn (Collection $rows, string $label): array => [
                    'label' => $label,
                    'count' => $rows->count(),
                ])
                ->sortByDesc('count')
                ->values()
                ->take(6),
        ]);
    }

    private function applyListFilters(Collection $farmers, Request $request): Collection
    {
        if ($request->boolean('passed')) {
            $farmers = $farmers
                ->filter(fn (array $farmer): bool => (int) ($farmer['average_progress'] ?? 0) >= 100)
                ->values();
        }

        return $farmers;
    }

    private function plotActivities(array $plot, LegacyTrackingService $legacyTracking): Collection
    {
        $typeMap = [
            'SOIL' => '/admin/tracking/prep/detail/',
            'WATER' => '/admin/tracking/water/detail/',
            'FERT' => '/admin/tracking/fertilizer/detail/',
            'PEST' => '/admin/tracking/pest/detail/',
            'DISEASE' => '/admin/tracking/disease/detail/',
            'HARVEST' => '/admin/tracking/harvest/detail/',
            'SALE' => '/admin/tracking/mill/detail/',
        ];

        $plotId = (string) ($plot['plot_id'] ?? '');

        if ($plotId === '') {
            return collect();
        }

        $rows = DB::table('activity_events as events')
            ->leftJoin('activity_types as types', 'types.id', '=', 'events.type_id')
            ->leftJoin('planting_plans as plans', 'plans.id', '=', 'events.plan_id')
            ->leftJoin('plots', 'plots.id', '=', 'plans.plot_id')
            ->leftJoin('users', 'users.id', '=', 'plots.user_id')
            ->leftJoin('farmer_profiles as profiles', 'profiles.user_id', '=', 'users.id')
            ->where('plots.id', $plotId)
            ->orderByDesc('events.performed_at')
            ->select([
                'events.id',
                'events.sequence_no as round_number',
                'events.performed_by_name',
                'events.performed_at as activity_date',
                'events.issue_found',
                'events.status as source_status',
                'events.reviewed_by',
                'events.reviewed_at',
                DB::raw("COALESCE(NULLIF(types.code, ''), '') as type_code"),
                DB::raw("COALESCE(NULLIF(types.name_th, ''), '-') as activity_name"),
            ])
            ->get();

        return collect($rows)
            ->map(function ($activity) use ($typeMap, $legacyTracking): array {
                $typeCode = (string) ($activity->type_code ?? '');
                $detailPrefix = $typeMap[$typeCode] ?? null;
                $status = $this->normalizeOverviewStatus(
                    $activity->source_status ?? null,
                    $activity->reviewed_by ?? null,
                    $activity->reviewed_at ?? null,
                );
                $activityDate = filled($activity->activity_date ?? null)
                    ? Carbon::parse($activity->activity_date)
                    : null;

                return [
                    'id' => (string) ($activity->id ?? ''),
                    'activity_name' => (string) ($activity->activity_name ?? '-'),
                    'round_number' => (string) ($activity->round_number ?? '-'),
                    'status' => $status,
                    'status_label' => $this->statusLabel($status),
                    'status_class' => $this->statusClass($status),
                    'activity_date' => $activityDate?->translatedFormat('d/m/Y') ?: '-',
                    'activity_timestamp' => $activityDate?->timestamp ?? 0,
                    'performed_by_name' => filled($activity->performed_by_name ?? null)
                        ? (string) $activity->performed_by_name
                        : '-',
                    'details' => filled($activity->issue_found ?? null)
                        ? (string) $activity->issue_found
                        : '-',
                    'detail_url' => $detailPrefix !== null
                        ? $detailPrefix . $activity->id
                        : null,
                ];
            })
            ->sortByDesc('activity_timestamp')
            ->values();
    }

    private function normalizeOverviewStatus(mixed $sourceStatus, mixed $reviewedBy, mixed $reviewedAt): string
    {
        if (filled($reviewedBy) || filled($reviewedAt)) {
            return 'passed';
        }

        return match ((string) $sourceStatus) {
            'passed', 'approved', 'done', 'completed' => 'passed',
            'needs_fix', 'rejected', 'failed' => 'needs_fix',
            'in_progress', 'processing' => 'in_progress',
            default => 'pending_review',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'passed' => 'ผ่านแล้ว',
            'needs_fix' => 'ต้องแก้ไข',
            'in_progress' => 'กำลังตรวจ',
            default => 'รอตรวจสอบ',
        };
    }

    private function statusClass(string $status): string
    {
        return match ($status) {
            'passed' => 'success',
            'needs_fix' => 'danger',
            'in_progress' => 'info',
            default => 'warning',
        };
    }
}
