<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LegacyTrackingApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrepTrackingActivityController extends Controller
{
    public function __construct(
        private readonly LegacyTrackingApiService $trackingApiService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->trackingApiService->listActivities('SOIL'),
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
            'issue_found' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'straw_burning' => ['nullable'],
            'land_leveling' => ['nullable'],
            'soil_ph' => ['nullable', 'numeric'],
            'soil_n' => ['nullable', 'numeric'],
            'soil_p' => ['nullable', 'numeric'],
            'soil_k' => ['nullable', 'numeric'],
            'organic_matter' => ['nullable', 'numeric'],
        ]);

        $activity = $this->trackingApiService->createActivity('SOIL', $validated);

        return response()->json([
            'message' => 'บันทึกกิจกรรมการเตรียมดินเรียบร้อยแล้ว',
            'data' => $activity,
        ], 201);
    }
}
