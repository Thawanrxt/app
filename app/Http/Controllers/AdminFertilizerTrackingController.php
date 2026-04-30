<?php

namespace App\Http\Controllers;

use App\Services\LegacyTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminFertilizerTrackingController extends Controller
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

        return view('admin.tracking-fertilizer', [
            'activities' => $this->trackingService->listActivities('FERT', $request->query()),
            'query' => $query,
            'round' => $round,
            'status' => $status,
            'date' => $date,
        ]);
    }

    public function show(?string $fertilizerTrackingActivity = null): View
    {
        $activity = $this->trackingService->findActivity('FERT', $fertilizerTrackingActivity);
        abort_if(! $activity, 404);

        $pageKey = 'tracking-fertilizer-detail-' . $activity->id;

        return view('admin.tracking-fertilizer-detail', [
            'activity' => $activity,
            'pageKey' => $pageKey,
            'pageTitle' => 'รายละเอียดการหว่านปุ๋ย',
            'advice' => $this->resolveTrackingAdvice($pageKey),
        ]);
    }

    public function updateStatus(Request $request, string $fertilizerTrackingActivity): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:passed,failed,pending_review,needs_fix'],
        ]);

        $this->trackingService->updateStatus($fertilizerTrackingActivity, $validated['status']);

        return back()->with('success', 'บันทึกสถานะการตรวจเรียบร้อยแล้ว');
    }

    public function destroy(string $fertilizerTrackingActivity): RedirectResponse
    {
        $this->trackingService->deleteActivity('FERT', $fertilizerTrackingActivity);

        return redirect('/admin/tracking/fertilizer')->with('success', 'ลบข้อมูลติดตามเรียบร้อยแล้ว');
    }

    public function print(): View
    {
        return view('admin.print-tracking', [
            'title' => 'รายงานการหว่านปุ๋ย',
            'rows' => $this->trackingService->printRows('FERT'),
        ]);
    }
}
