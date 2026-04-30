<?php

namespace App\Http\Controllers;

use App\Services\LegacyTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminHarvestTrackingController extends Controller
{
    public function __construct(private readonly LegacyTrackingService $trackingService)
    {
    }

    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $round = trim((string) $request->query('round', ''));
        $status = trim((string) $request->query('status', ''));
        $date = trim((string) $request->query('date', ''));

        return view('admin.tracking-harvest', [
            'activities' => $this->trackingService->listActivities('HARVEST', $request->query()),
            'query' => $query,
            'round' => $round,
            'status' => $status,
            'date' => $date,
        ]);
    }

    public function show(?string $harvestTrackingActivity = null): View
    {
        $activity = $this->trackingService->findActivity('HARVEST', $harvestTrackingActivity);
        abort_if(! $activity, 404);

        $pageKey = 'tracking-harvest-detail-' . $activity->id;

        return view('admin.tracking-harvest-detail', [
            'activity' => $activity,
            'pageKey' => $pageKey,
            'pageTitle' => 'รายละเอียดการเก็บเกี่ยว',
            'advice' => $this->resolveTrackingAdvice($pageKey),
        ]);
    }

    public function updateStatus(Request $request, string $harvestTrackingActivity): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:passed,failed,pending_review,needs_fix'],
        ]);

        $this->trackingService->updateStatus($harvestTrackingActivity, $validated['status']);

        return back()->with('success', 'บันทึกสถานะการตรวจเรียบร้อยแล้ว');
    }

    public function destroy(string $harvestTrackingActivity): RedirectResponse
    {
        $this->trackingService->deleteActivity('HARVEST', $harvestTrackingActivity);

        return redirect('/admin/tracking/harvest')->with('success', 'ลบข้อมูลติดตามเรียบร้อยแล้ว');
    }

    public function print(): View
    {
        return view('admin.print-tracking', [
            'title' => 'รายงานการเก็บเกี่ยว',
            'rows' => $this->trackingService->printRows('HARVEST'),
        ]);
    }
}
