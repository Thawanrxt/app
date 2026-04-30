<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LegacyTrackingApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FertilizerTrackingActivityController extends Controller
{
    public function __construct(
        private readonly LegacyTrackingApiService $trackingApiService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->trackingApiService->listActivities('FERT'),
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
            'fertilizer_kind' => ['nullable', 'string', 'max:255'],
            'fertilizer_type' => ['nullable', 'string', 'max:255'],
            'fertilizer_formula' => ['nullable', 'string', 'max:255'],
            'qty_kg_per_rai' => ['nullable', 'numeric'],
            'amount_per_rai' => ['nullable'],
            'issue_found' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $activity = $this->trackingApiService->createActivity('FERT', $validated);

        return response()->json([
            'message' => 'บันทึกกิจกรรมการใส่ปุ๋ยเรียบร้อยแล้ว',
            'data' => $activity,
        ], 201);
    }
}
