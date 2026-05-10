<?php

namespace App\Http\Controllers;

use App\Models\DashboardWorkItem;
use App\Services\DashboardMetricsService;
use App\Services\DashboardOperationsService;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class AdminDashboardController extends Controller
{
    public function index(Request $request, DashboardMetricsService $dashboardMetrics): View
    {
        return view('admin.dashboard', [
            'dashboard' => $dashboardMetrics->buildPayload((string) $request->query('status_filter', 'all')),
        ]);
    }

    public function alerts(Request $request, DashboardOperationsService $dashboardOperations): View
    {
        return view('admin.alerts', [
            'alerts' => $dashboardOperations->alertItems($request->query()),
            'query' => trim((string) $request->query('q', '')),
            'scope' => trim((string) $request->query('scope', 'all')),
            'status' => trim((string) $request->query('status', '')),
        ]);
    }

    public function activity(Request $request, DashboardOperationsService $dashboardOperations): RedirectResponse|View
    {
        $status = trim((string) $request->query('status', ''));
        $query = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', 'all'));

        if ($status === 'needs_fix' && $category === 'all') {
            $parameters = array_filter([
                'scope' => 'report',
                'status' => 'needs_fix',
                'q' => $query !== '' ? $query : null,
            ]);

            return redirect('/admin/alerts?' . http_build_query($parameters));
        }

        if ($status === 'pending_review' && $category === 'all') {
            $parameters = array_filter([
                'status' => 'pending_review',
                'q' => $query !== '' ? $query : null,
            ]);

            return redirect('/admin/alerts?' . http_build_query($parameters));
        }

        return view('admin.activity', [
            'activities' => $dashboardOperations->recentActivityItems($request->query()),
            'query' => $query,
            'category' => $category,
            'status' => $status,
        ]);
    }

    public function todayTasks(Request $request, DashboardOperationsService $dashboardOperations): View
    {
        return view('admin.dashboard-focus-list', [
            'pageTitle' => 'งานตรวจวันนี้',
            'pageDescription' => 'รายการงานที่ครบกำหนดตรวจติดตามภายในวันนี้ แยกออกจากหน้าแจ้งเตือนรวม',
            'items' => $dashboardOperations->todayTaskItems($request->query()),
            'emptyTitle' => 'ยังไม่มีงานตรวจวันนี้',
            'emptyDescription' => 'เมื่อมีงานที่ครบกำหนดในวันนี้ ระบบจะแสดงในหน้านี้โดยตรง',
        ]);
    }

    public function issueReports(Request $request, DashboardOperationsService $dashboardOperations): View
    {
        return view('admin.dashboard-focus-list', [
            'pageTitle' => 'รายงานปัญหาใหม่',
            'pageDescription' => 'รวมรายการรายงานปัญหาที่เข้ามาใหม่และต้องติดตามต่อในหน้าของตัวเอง',
            'items' => $dashboardOperations->issueReportFocusItems($request->query()),
            'emptyTitle' => 'ยังไม่มีรายงานปัญหาใหม่',
            'emptyDescription' => 'เมื่อมีรายงานปัญหาเข้ามา ระบบจะแสดงในหน้านี้โดยตรง',
        ]);
    }

    public function documentReviews(Request $request, DashboardOperationsService $dashboardOperations): View
    {
        return view('admin.dashboard-focus-list', [
            'pageTitle' => 'เอกสารรอตรวจ',
            'pageDescription' => 'รวมรายการเอกสารและข้อมูลที่รอตรวจสอบ แยกออกจากหน้าแจ้งเตือนรวม',
            'items' => $dashboardOperations->documentReviewItems($request->query()),
            'emptyTitle' => 'ยังไม่มีเอกสารรอตรวจ',
            'emptyDescription' => 'เมื่อมีเอกสารหรือข้อมูลที่ต้องตรวจ ระบบจะแสดงในหน้านี้โดยตรง',
        ]);
    }

    public function allIssues(Request $request, DashboardOperationsService $dashboardOperations): View
    {
        return view('admin.dashboard-focus-list', [
            'pageTitle' => 'ปัญหาที่พบทั้งหมด',
            'pageDescription' => 'รวมปัญหาที่ตรวจพบและรายการที่ต้องแก้ไขทั้งหมดในหน้าของตัวเอง',
            'items' => $dashboardOperations->allIssueFocusItems($request->query()),
            'emptyTitle' => 'ยังไม่พบปัญหาที่ต้องติดตาม',
            'emptyDescription' => 'เมื่อมีรายการที่ตรวจพบปัญหา ระบบจะแสดงในหน้านี้โดยตรง',
        ]);
    }

    public function createFollowupPlan(): View
    {
        return view('admin.followup-plan-create', [
            'farmers' => $this->followupPlanningFarmers(),
            'taskTypes' => $this->followupTaskTypes(),
        ]);
    }

    public function storeFollowupPlan(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('dashboard_work_items')) {
            return back()->withErrors([
                'task_date' => 'ยังไม่พบตารางแผนงานติดตามในระบบ',
            ])->withInput();
        }

        $taskTypes = $this->followupTaskTypes();
        $validated = $request->validate([
            'farmer_user_id'   => ['required', 'string', 'max:255'],
            'plot_id'          => ['required', 'string', 'max:255'],
            'task_type'        => ['required', 'string', 'max:100'],
            'task_date'        => ['required', 'date'],
            'priority'         => ['required', 'string', 'max:20'],
            'appointment_type' => ['required', 'in:visit,fix'],
            'latest_note'      => ['nullable', 'string', 'max:1000'],
        ]);

        if (! array_key_exists($validated['task_type'], $taskTypes)) {
            return back()->withErrors([
                'task_type' => 'กรุณาเลือกประเภทงานติดตามที่ถูกต้อง',
            ])->withInput();
        }

        if (! in_array($validated['priority'], ['normal', 'medium', 'urgent'], true)) {
            return back()->withErrors([
                'priority' => 'กรุณาเลือกระดับความสำคัญที่ถูกต้อง',
            ])->withInput();
        }

        $plot = DB::table('plots')
            ->where('id', $validated['plot_id'])
            ->when(
                Schema::hasColumn('plots', 'user_id'),
                fn ($query) => $query->where('user_id', $validated['farmer_user_id'])
            )
            ->select([
                'plots.id',
                Schema::hasColumn('plots', 'user_id') ? 'plots.user_id' : DB::raw('NULL as user_id'),
                DB::raw("COALESCE(NULLIF(plots.plot_name, ''), 'แปลงหลัก') as plot_name"),
                DB::raw("COALESCE(NULLIF(plots.farm_id, ''), NULLIF(plots.plot_name, ''), 'แปลงหลัก') as plot_code"),
            ])
            ->first();

        if (! $plot) {
            return back()->withErrors([
                'plot_id' => 'ไม่พบข้อมูลแปลงที่เลือก หรือแปลงนี้ไม่ได้อยู่ในเกษตรกรที่เลือก',
            ])->withInput();
        }

        $farmerQuery = DB::table('users')->where('users.id', $validated['farmer_user_id']);

        if (Schema::hasTable('farmer_profiles')) {
            $farmerQuery->leftJoin('farmer_profiles as profiles', 'profiles.user_id', '=', 'users.id');
        }

        $farmer = $farmerQuery
            ->select([
                'users.id',
                'users.username',
                Schema::hasTable('farmer_profiles') && Schema::hasColumn('farmer_profiles', 'id')
                    ? 'profiles.id as profile_id'
                    : DB::raw('NULL as profile_id'),
                Schema::hasTable('farmer_profiles')
                    ? DB::raw("COALESCE(NULLIF(profiles.full_name, ''), NULLIF(users.username, ''), 'เกษตรกร') as farmer_name")
                    : DB::raw("COALESCE(NULLIF(users.username, ''), 'เกษตรกร') as farmer_name"),
            ])
            ->first();

        if (! $farmer) {
            return back()->withErrors([
                'farmer_user_id' => 'ไม่พบข้อมูลเกษตรกรที่เลือก',
            ])->withInput();
        }

        $taskType = $taskTypes[$validated['task_type']];
        $detailUrl = '/admin/farmer-users/' . $farmer->id;
        $calendarStatus = $validated['appointment_type'] === 'fix' ? 'needs_fix' : 'pending_review';
        $payload = [
            'task_title' => $taskType['title'],
            'issue_category' => $taskType['category'],
            'status' => $calendarStatus,
            'priority' => $validated['priority'],
            'response_required' => true,
            'latest_note' => trim((string) ($validated['latest_note'] ?? '')) ?: 'นัดติดตามตามแผนงานที่กำหนดโดยแอดมิน',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('dashboard_work_items', 'meta')) {
            $payload['meta'] = json_encode([
                'source' => 'manual_followup_plan',
                'task_type' => $validated['task_type'],
                'task_label' => $taskType['label'],
                'planned_date' => $validated['task_date'],
                'detail_url' => $detailUrl,
                'plot_name' => $plot->plot_name ?? null,
            ], JSON_UNESCAPED_UNICODE);
        }

        if (Schema::hasColumn('dashboard_work_items', 'user_id')) {
            $payload['user_id'] = $farmer->id;
        }

        if (Schema::hasColumn('dashboard_work_items', 'profile_id') && filled($farmer->profile_id ?? null)) {
            $payload['profile_id'] = $farmer->profile_id;
        }

        if (Schema::hasColumn('dashboard_work_items', 'plot_id')) {
            $payload['plot_id'] = $plot->id;
        }

        if (Schema::hasColumn('dashboard_work_items', 'farmer_name')) {
            $payload['farmer_name'] = $farmer->farmer_name;
        }

        if (Schema::hasColumn('dashboard_work_items', 'plot_code')) {
            $payload['plot_code'] = $plot->plot_code;
        }

        if (Schema::hasColumn('dashboard_work_items', 'due_date')) {
            $payload['due_date'] = $validated['task_date'];
        }

        if (Schema::hasColumn('dashboard_work_items', 'last_activity_at')) {
            $payload['last_activity_at'] = now();
        }

        if (Schema::hasColumn('dashboard_work_items', 'progress_percent')) {
            $payload['progress_percent'] = 0;
        }

        if (Schema::hasColumn('dashboard_work_items', 'detail_url')) {
            $payload['detail_url'] = $detailUrl;
        }

        if (Schema::hasColumn('dashboard_work_items', 'resolved_at')) {
            $payload['resolved_at'] = null;
        }

        if ($this->dashboardPrimaryKeyUsesUuid()) {
            $payload['id'] = (string) Str::uuid();
        }

        DB::table('dashboard_work_items')->insert($payload);

        return redirect('/admin/followup-plans/create')->with(
            'success',
            'เพิ่มแผนงานติดตามเรียบร้อยแล้ว งานนี้จะไปแสดงในปฏิทินและรายการติดตามตามวันที่ที่กำหนด'
        );
    }

    public function printSummary(Request $request, DashboardOperationsService $dashboardOperations): View
    {
        return view('admin.print-tracking', [
            'title' => 'รายงานสรุปภาพรวมงานติดตาม',
            'rows' => $dashboardOperations->printSummaryRows($request->query()),
        ]);
    }

    public function markAlertsRead(): RedirectResponse
    {
        try {
            if (Schema::hasTable('dashboard_work_items')) {
                $responseColumn = Schema::hasColumn('dashboard_work_items', 'responded_at')
                    ? 'responded_at'
                    : (Schema::hasColumn('dashboard_work_items', 'resolved_at') ? 'resolved_at' : null);

                if ($responseColumn === null) {
                    return back()->with('error', 'ยังไม่พบคอลัมน์สำหรับยืนยันการรับทราบ');
                }

                DashboardWorkItem::query()
                    ->where('response_required', true)
                    ->whereNull($responseColumn)
                    ->update([
                        $responseColumn => now(),
                        'updated_at' => now(),
                    ]);
            }
        } catch (Throwable) {
            return back()->with('error', 'ยังไม่สามารถอัปเดตรายการแจ้งเตือนได้');
        }

        return back()->with('success', 'ทำเครื่องหมายรายการแจ้งเตือนเรียบร้อยแล้ว');
    }

    public function toggleFollowup(Request $request, DashboardWorkItem $dashboardWorkItem): RedirectResponse
    {
        $checked = $request->boolean('checked');
        $statusFilter = (string) $request->input('status_filter', 'all');
        $responseColumn = Schema::hasColumn('dashboard_work_items', 'responded_at')
            ? 'responded_at'
            : (Schema::hasColumn('dashboard_work_items', 'resolved_at') ? 'resolved_at' : null);

        if ($responseColumn !== null) {
            $dashboardWorkItem->forceFill([
                $responseColumn => $checked ? now() : null,
            ])->save();
        }

        return redirect()->to('/admin?status_filter=' . urlencode($statusFilter));
    }

    private function followupPlanningFarmers(): array
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('plots')) {
            return [];
        }

        $actingUser = Auth::user();
        $query = DB::table('users')
            ->join('plots', 'plots.user_id', '=', 'users.id')
            ->where('users.role', 'FARMER');

        if (Schema::hasTable('farmer_profiles')) {
            $query->leftJoin('farmer_profiles as profiles', 'profiles.user_id', '=', 'users.id');
        }

        if (! AdminAccess::isSuperAdmin($actingUser) && Schema::hasTable('admin_farmer_assignments')) {
            $assignedIds = DB::table('admin_farmer_assignments')
                ->where('admin_user_id', $actingUser?->id)
                ->pluck('farmer_user_id')
                ->filter()
                ->values();

            if ($assignedIds->isNotEmpty()) {
                $query->whereIn('users.id', $assignedIds->all());
            }
        }

        return $query
            ->select([
                'users.id as user_id',
                'users.username',
                Schema::hasTable('farmer_profiles')
                    ? DB::raw("COALESCE(NULLIF(profiles.full_name, ''), NULLIF(users.username, ''), 'เกษตรกร') as farmer_name")
                    : DB::raw("COALESCE(NULLIF(users.username, ''), 'เกษตรกร') as farmer_name"),
                'plots.id as plot_id',
                DB::raw("COALESCE(NULLIF(plots.plot_name, ''), 'แปลงหลัก') as plot_name"),
                DB::raw("COALESCE(NULLIF(plots.farm_id, ''), NULLIF(plots.plot_name, ''), 'แปลงหลัก') as plot_code"),
            ])
            ->orderBy('farmer_name')
            ->orderBy('plots.plot_name')
            ->get()
            ->groupBy('user_id')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'user_id' => $first->user_id,
                    'farmer_name' => $first->farmer_name,
                    'username' => $first->username,
                    'plots' => $rows->map(fn ($plot) => [
                        'plot_id' => $plot->plot_id,
                        'plot_name' => $plot->plot_name,
                        'plot_code' => $plot->plot_code,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function followupTaskTypes(): array
    {
        return [
            'prep' => ['label' => 'การเตรียมดิน', 'title' => 'นัดติดตามการเตรียมดิน', 'category' => 'การเตรียมดิน'],
            'water' => ['label' => 'การจัดการน้ำ', 'title' => 'นัดติดตามการจัดการน้ำ', 'category' => 'การจัดการน้ำ'],
            'fertilizer' => ['label' => 'หว่านปุ๋ย', 'title' => 'นัดติดตามการหว่านปุ๋ย', 'category' => 'หว่านปุ๋ย'],
            'pest' => ['label' => 'การจัดการศัตรูพืช', 'title' => 'นัดติดตามการจัดการศัตรูพืช', 'category' => 'การจัดการศัตรูพืช'],
            'disease' => ['label' => 'การจัดการโรคพืช', 'title' => 'นัดติดตามการจัดการโรคพืช', 'category' => 'การจัดการโรคพืช'],
            'harvest' => ['label' => 'การเก็บเกี่ยว', 'title' => 'นัดติดตามการเก็บเกี่ยว', 'category' => 'การเก็บเกี่ยว'],
            'mill' => ['label' => 'ขายข้าวเข้าโรงสี', 'title' => 'นัดติดตามการขายข้าวเข้าโรงสี', 'category' => 'ขายข้าวเข้าโรงสี'],
            'srp' => ['label' => 'การประเมิน SRP', 'title' => 'นัดติดตามการประเมิน SRP', 'category' => 'SRP'],
            'document' => ['label' => 'เอกสาร/ข้อมูลเกษตรกร', 'title' => 'นัดติดตามเอกสารและข้อมูลเกษตรกร', 'category' => 'เอกสาร'],
        ];
    }

    private function dashboardPrimaryKeyUsesUuid(): bool
    {
        try {
            return DB::table('information_schema.columns')
                ->where('table_schema', 'public')
                ->where('table_name', 'dashboard_work_items')
                ->where('column_name', 'id')
                ->value('data_type') === 'uuid';
        } catch (Throwable) {
            return false;
        }
    }
}
