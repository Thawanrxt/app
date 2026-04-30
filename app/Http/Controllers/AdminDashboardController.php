<?php

namespace App\Http\Controllers;

use App\Models\DashboardWorkItem;
use App\Services\DashboardMetricsService;
use App\Services\DashboardOperationsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
        ]);
    }

    public function activity(Request $request, DashboardOperationsService $dashboardOperations): View
    {
        return view('admin.activity', [
            'activities' => $dashboardOperations->activityItems($request->query()),
            'query' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
        ]);
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
                    return back()->with('error', 'à¸¢à¸±à¸‡à¹„à¸¡à¹ˆà¸žà¸šà¸„à¸­à¸¥à¸±à¸¡à¸™à¹Œà¸ªà¸³à¸«à¸£à¸±à¸šà¸¢à¸·à¸™à¸¢à¸±à¸™à¸à¸²à¸£à¸£à¸±à¸šà¸—à¸£à¸²à¸š');
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
}
