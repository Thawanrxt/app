<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LegacyTrackingApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PestTrackingActivityController extends Controller
{
    public function __construct(
        private readonly LegacyTrackingApiService $trackingApiService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->trackingApiService->listActivities('PEST'),
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
            'pest_type' => ['nullable', 'string', 'max:255'],
            'chemical_common_name' => ['nullable', 'string', 'max:255'],
            'chemical_name' => ['nullable', 'string', 'max:255'],
            'amount_used' => ['nullable', 'numeric'],
            'water_liters' => ['nullable', 'numeric'],
            'mix_ratio' => ['nullable'],
            'issue_found' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $activity = $this->trackingApiService->createActivity('PEST', $validated);

        return response()->json([
            'message' => 'บันทึกกิจกรรมการจัดการศัตรูพืชเรียบร้อยแล้ว',
            'data' => $activity,
        ], 201);
    }
}
