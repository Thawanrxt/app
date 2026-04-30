<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LegacyTrackingApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiseaseTrackingActivityController extends Controller
{
    public function __construct(
        private readonly LegacyTrackingApiService $trackingApiService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->trackingApiService->listActivities('DISEASE'),
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
            'disease_type' => ['nullable', 'string', 'max:255'],
            'chemical_comm_name' => ['nullable', 'string', 'max:255'],
            'chemical_name' => ['nullable', 'string', 'max:255'],
            'amount_used' => ['nullable', 'numeric'],
            'used_amount' => ['nullable'],
            'water_liters' => ['nullable', 'numeric'],
            'mix_ratio' => ['nullable'],
            'issue_found' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $activity = $this->trackingApiService->createActivity('DISEASE', $validated);

        return response()->json([
            'message' => 'บันทึกกิจกรรมการจัดการโรคพืชเรียบร้อยแล้ว',
            'data' => $activity,
        ], 201);
    }
}
