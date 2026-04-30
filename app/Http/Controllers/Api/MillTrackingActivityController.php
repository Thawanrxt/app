<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LegacyTrackingApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MillTrackingActivityController extends Controller
{
    public function __construct(
        private readonly LegacyTrackingApiService $trackingApiService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->trackingApiService->listActivities('SALE'),
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
            'mill_name' => ['nullable', 'string', 'max:255'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'ticket_no' => ['nullable', 'string', 'max:255'],
            'queue_number' => ['nullable', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:255'],
            'plate_no' => ['nullable', 'string', 'max:255'],
            'vehicle_plate' => ['nullable', 'string', 'max:255'],
            'in_time' => ['nullable', 'date_format:H:i'],
            'time_in' => ['nullable', 'date_format:H:i'],
            'out_time' => ['nullable', 'date_format:H:i'],
            'time_out' => ['nullable', 'date_format:H:i'],
            'weight_total_kg' => ['nullable', 'numeric'],
            'pre_mill_weight_kg' => ['nullable'],
            'weight_net_kg' => ['nullable', 'numeric'],
            'post_mill_weight_kg' => ['nullable'],
            'net_weight_kg' => ['nullable'],
            'price_per_kg' => ['nullable', 'numeric'],
            'total_income' => ['nullable', 'numeric'],
            'issue_found' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $activity = $this->trackingApiService->createActivity('SALE', $validated);

        return response()->json([
            'message' => 'บันทึกกิจกรรมการขายผลผลิตเรียบร้อยแล้ว',
            'data' => $activity,
        ], 201);
    }
}
