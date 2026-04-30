<?php

namespace App\Http\Controllers;

use App\Services\LegacyTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDiseaseTrackingController extends Controller
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

        return view('admin.tracking-disease', [
            'activities' => $this->trackingService->listActivities('DISEASE', $request->query()),
            'query' => $query,
            'round' => $round,
            'status' => $status,
            'date' => $date,
        ]);
    }

    public function show(?string $diseaseTrackingActivity = null): View
    {
        $activity = $this->trackingService->findActivity('DISEASE', $diseaseTrackingActivity);
        abort_if(! $activity, 404);

        $pageKey = 'tracking-disease-detail-' . $activity->id;

        return view('admin.tracking-disease-detail', [
            'activity' => $activity,
            'pageKey' => $pageKey,
            'pageTitle' => 'รายละเอียดการจัดการโรคพืช',
            'advice' => $this->resolveTrackingAdvice($pageKey),
        ]);
    }

    public function updateStatus(Request $request, string $diseaseTrackingActivity): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:passed,failed,pending_review,needs_fix'],
        ]);

        $this->trackingService->updateStatus($diseaseTrackingActivity, $validated['status']);

        return back()->with('success', 'บันทึกสถานะการตรวจเรียบร้อยแล้ว');
    }

    public function destroy(string $diseaseTrackingActivity): RedirectResponse
    {
        $this->trackingService->deleteActivity('DISEASE', $diseaseTrackingActivity);

        return redirect('/admin/tracking/disease')->with('success', 'ลบข้อมูลติดตามเรียบร้อยแล้ว');
    }

    public function print(): View
    {
        return view('admin.print-tracking', [
            'title' => 'รายงานการจัดการโรคพืช',
            'rows' => $this->trackingService->printRows('DISEASE'),
        ]);
    }
}
