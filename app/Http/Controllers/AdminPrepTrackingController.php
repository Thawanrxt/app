<?php

namespace App\Http\Controllers;

use App\Services\LegacyTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPrepTrackingController extends Controller
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

        return view('admin.tracking-prep', [
            'activities' => $this->trackingService->listActivities('SOIL', $request->query()),
            'query' => $query,
            'round' => $round,
            'status' => $status,
            'date' => $date,
        ]);
    }

    public function show(?string $prepTrackingActivity = null): View
    {
        $activity = $this->trackingService->findActivity('SOIL', $prepTrackingActivity);
        abort_if(! $activity, 404);

        $pageKey = 'tracking-prep-detail-' . $activity->id;

        return view('admin.tracking-prep-detail', [
            'activity' => $activity,
            'pageKey' => $pageKey,
            'pageTitle' => 'รายละเอียดการเตรียมดิน',
            'advice' => $this->resolveTrackingAdvice($pageKey),
        ]);
    }

    public function updateStatus(Request $request, string $prepTrackingActivity): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:passed,failed,pending_review,needs_fix'],
            'admin_note' => ['nullable', 'string'],
        ]);

        $this->trackingService->updateStatus(
            $prepTrackingActivity,
            $validated['status'],
            'admin',
            $validated['admin_note'] ?? null,
        );

        return back()->with('success', 'บันทึกสถานะการตรวจเรียบร้อยแล้ว');
    }

    public function destroy(string $prepTrackingActivity): RedirectResponse
    {
        $this->trackingService->deleteActivity('SOIL', $prepTrackingActivity);

        return redirect('/admin/tracking/prep')->with('success', 'ลบข้อมูลติดตามเรียบร้อยแล้ว');
    }

    public function print(): View
    {
        return view('admin.print-tracking', [
            'title' => 'รายงานการเตรียมดิน',
            'rows' => $this->trackingService->printRows('SOIL'),
        ]);
    }
}
