<?php

namespace App\Http\Controllers;

use App\Services\LegacyTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPestTrackingController extends Controller
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

        return view('admin.tracking-pest', [
            'activities' => $this->trackingService->listActivities('PEST', $request->query()),
            'query' => $query,
            'round' => $round,
            'status' => $status,
            'date' => $date,
        ]);
    }

    public function show(?string $pestTrackingActivity = null): View
    {
        $activity = $this->trackingService->findActivity('PEST', $pestTrackingActivity);
        abort_if(! $activity, 404);

        $pageKey = 'tracking-pest-detail-' . $activity->id;

        return view('admin.tracking-pest-detail', [
            'activity' => $activity,
            'pageKey' => $pageKey,
            'pageTitle' => 'รายละเอียดการจัดการศัตรูพืช',
            'advice' => $this->resolveTrackingAdvice($pageKey),
        ]);
    }

    public function updateStatus(Request $request, string $pestTrackingActivity): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:passed,failed,pending_review,needs_fix'],
        ]);

        $this->trackingService->updateStatus($pestTrackingActivity, $validated['status']);

        return back()->with('success', 'บันทึกสถานะการตรวจเรียบร้อยแล้ว');
    }

    public function destroy(string $pestTrackingActivity): RedirectResponse
    {
        $this->trackingService->deleteActivity('PEST', $pestTrackingActivity);

        return redirect('/admin/tracking/pest')->with('success', 'ลบข้อมูลติดตามเรียบร้อยแล้ว');
    }

    public function print(): View
    {
        return view('admin.print-tracking', [
            'title' => 'รายงานการจัดการศัตรูพืช',
            'rows' => $this->trackingService->printRows('PEST'),
        ]);
    }
}
