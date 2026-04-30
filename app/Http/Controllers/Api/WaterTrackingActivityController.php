<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LegacyTrackingApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaterTrackingActivityController extends Controller
{
    public function __construct(
        private readonly LegacyTrackingApiService $trackingApiService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->trackingApiService->listActivities('WATER'),
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
            'method' => ['nullable', 'string', 'max:255'],
            'water_level_cm' => ['nullable', 'integer'],
            'water_level' => ['nullable'],
            'ref_point' => ['nullable', 'string', 'max:255'],
            'details' => ['nullable', 'string'],
            'issue_found' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $activity = $this->trackingApiService->createActivity('WATER', $validated);

        return response()->json([
            'message' => 'บันทึกกิจกรรมการจัดการน้ำเรียบร้อยแล้ว',
            'data' => $activity,
        ], 201);
    }
}
