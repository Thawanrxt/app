<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LegacyTrackingApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HarvestTrackingActivityController extends Controller
{
    public function __construct(
        private readonly LegacyTrackingApiService $trackingApiService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->trackingApiService->listActivities('HARVEST'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farmer_name' => ['nullable', 'string', 'max:255'],
            'performed_by_name' => ['nullable', 'string', 'max:255'],
            'plot_code' => ['required', 'string', 'max:255'],
            'round_number' => ['nullable', 'integer', 'min:1'],
            'activity_date' => ['required', 'date'],
            'harvest_start_date' => ['nullable', 'date'],
            'started_at' => ['nullable', 'date'],
            'harvest_end_date' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date'],
            'total_yield_kg' => ['nullable', 'numeric'],
            'yield_amount_kg' => ['nullable'],
            'moisture_percent' => ['nullable', 'numeric'],
            'issue_found' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $activity = $this->trackingApiService->createActivity('HARVEST', $validated);

        return response()->json([
            'message' => 'บันทึกกิจกรรมการเก็บเกี่ยวเรียบร้อยแล้ว',
            'data' => $activity,
        ], 201);
    }
}
