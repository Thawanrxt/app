<?php

namespace App\Http\Controllers;

use App\Services\LegacyTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMillTrackingController extends Controller
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

        return view('admin.tracking-mill', [
            'activities' => $this->trackingService->listActivities('SALE', $request->query()),
            'query' => $query,
            'round' => $round,
            'status' => $status,
            'date' => $date,
        ]);
    }

    public function show(?string $millTrackingActivity = null): View
    {
        $activity = $this->trackingService->findActivity('SALE', $millTrackingActivity);
        abort_if(! $activity, 404);

        $pageKey = 'tracking-mill-detail-' . $activity->id;

        return view('admin.tracking-mill-detail', [
            'activity' => $activity,
            'pageKey' => $pageKey,
            'pageTitle' => 'รายละเอียดการขายข้าวเข้าโรงสี',
            'advice' => $this->resolveTrackingAdvice($pageKey),
        ]);
    }

    public function updateStatus(Request $request, string $millTrackingActivity): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:passed,failed,pending_review,needs_fix'],
        ]);

        $this->trackingService->updateStatus($millTrackingActivity, $validated['status']);

        return back()->with('success', 'บันทึกสถานะการตรวจเรียบร้อยแล้ว');
    }

    public function destroy(string $millTrackingActivity): RedirectResponse
    {
        $this->trackingService->deleteActivity('SALE', $millTrackingActivity);

        return redirect('/admin/tracking/mill')->with('success', 'ลบข้อมูลติดตามเรียบร้อยแล้ว');
    }

    public function print(): View
    {
        return view('admin.print-tracking', [
            'title' => 'รายงานการขายข้าวเข้าโรงสี',
            'rows' => $this->trackingService->printRows('SALE'),
        ]);
    }
}
