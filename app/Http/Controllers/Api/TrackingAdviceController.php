<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TrackingAdviceController extends Controller
{
    /**
     * GET /api/v1/advice
     * คำแนะนำทั้งหมดของเกษตรกรที่ล็อกอิน (ดึงผ่าน activity_events → plots → user)
     */
    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tracking_advices')) {
            return response()->json(['data' => []]);
        }

        try {
            $user = $request->user();

            $query = DB::table('tracking_advices as adv');

            // Link via activity_events → planting_plans → plots → user_id
            if (
                Schema::hasColumn('tracking_advices', 'activity_event_id') &&
                Schema::hasTable('activity_events') &&
                Schema::hasTable('planting_plans') &&
                Schema::hasTable('plots')
            ) {
                $advisedActivityIds = DB::table('activity_events as ev')
                    ->join('planting_plans as plans', 'plans.id', '=', 'ev.plan_id')
                    ->join('plots', 'plots.id', '=', 'plans.plot_id')
                    ->where('plots.user_id', $user->id)
                    ->pluck('ev.id');

                $query->whereIn('adv.activity_event_id', $advisedActivityIds);
            } elseif (Schema::hasColumn('tracking_advices', 'farmer_name')) {
                $farmerName = optional($user->farmerProfile)->full_name ?? '';
                $query->where('adv.farmer_name', $farmerName);
            } else {
                return response()->json(['data' => []]);
            }

            $rows = $query
                ->orderByDesc('adv.sent_at')
                ->get();

            return response()->json([
                'data' => $rows->map(fn ($row) => $this->transform($row))->values(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['data' => []]);
        }
    }

    /**
     * GET /api/v1/advice/{activityId}
     * คำแนะนำสำหรับ activity นั้นๆ
     */
    public function show(string $activityId): JsonResponse
    {
        if (! Schema::hasTable('tracking_advices')) {
            return response()->json(['message' => 'ยังไม่มีคำแนะนำ', 'data' => null], 200);
        }

        try {
            $row = $this->findByActivityId($activityId);

            if (! $row) {
                return response()->json(['message' => 'ไม่พบคำแนะนำสำหรับกิจกรรมนี้', 'data' => null], 200);
            }

            return response()->json([
                'data' => $this->transform($row),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'เกิดข้อผิดพลาด', 'data' => null], 500);
        }
    }

    private function findByActivityId(string $activityId): ?object
    {
        $query = DB::table('tracking_advices');

        if (Schema::hasColumn('tracking_advices', 'activity_event_id')) {
            $row = (clone $query)->where('activity_event_id', $activityId)->first();
            if ($row) {
                return $row;
            }
        }

        if (Schema::hasColumn('tracking_advices', 'activity_id')) {
            $row = (clone $query)->where('activity_id', $activityId)->first();
            if ($row) {
                return $row;
            }
        }

        // Fallback: ค้นจาก page_key ที่มี activityId อยู่ท้าย
        if (Schema::hasColumn('tracking_advices', 'page_key')) {
            return (clone $query)->where('page_key', 'like', '%-' . $activityId)->first();
        }

        return null;
    }

    private function transform(object $row): array
    {
        $message = $row->message ?? $row->advice_message ?? null;
        $sentAt = $row->sent_at ?? null;
        $isSent = $sentAt !== null || ($row->advice_status ?? null) === 'sent';

        $attachmentUrl = null;
        $attachmentPath = $row->attachment_path ?? null;
        if ($attachmentPath) {
            $attachmentUrl = Storage::disk('public')->exists($attachmentPath)
                ? Storage::disk('public')->url($attachmentPath)
                : null;
        }

        return [
            'activity_id'     => $row->activity_event_id ?? $row->activity_id ?? null,
            'page_key'        => $row->page_key ?? null,
            'page_title'      => $row->page_title ?? null,
            'farmer_name'     => $row->farmer_name ?? null,
            'plot_code'       => $row->plot_code ?? null,
            'round_number'    => $row->round_number ?? null,
            'message'         => $message,
            'is_sent'         => $isSent,
            'sent_at'         => $sentAt,
            'sent_by'         => $row->sent_by ?? null,
            'attachment_url'  => $attachmentUrl,
            'attachment_name' => $row->attachment_name ?? null,
            'created_at'      => $row->created_at ?? null,
            'updated_at'      => $row->updated_at ?? null,
        ];
    }
}
