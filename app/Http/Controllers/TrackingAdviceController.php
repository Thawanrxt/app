<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class TrackingAdviceController extends Controller
{
    public function store(Request $request, string $pageKey): RedirectResponse
    {
        $validated = $request->validate([
            'page_title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120'],
            'farmer_name' => ['nullable', 'string', 'max:255'],
            'plot_code' => ['nullable', 'string', 'max:255'],
            'round_number' => ['nullable'],
            'detail_url' => ['nullable', 'string', 'max:255'],
            'activity_id' => ['nullable', 'string', 'max:255'],
        ]);

        $this->persistTrackingAdvice($pageKey, $validated, $request);
        $this->syncDashboardAlert($pageKey, $validated);

        return back()->with('advice_success', 'ส่งคำแนะนำถึงเกษตรกรเรียบร้อยแล้ว');
    }

    private function persistTrackingAdvice(string $pageKey, array $validated, Request $request): void
    {
        if (! Schema::hasTable('tracking_advices')) {
            return;
        }

        $columns = $this->tableColumns('tracking_advices');
        $match = $this->trackingAdviceMatch($columns, $pageKey, $validated);

        $query = DB::table('tracking_advices');
        foreach ($match as $column => $value) {
            $query->where($column, $value);
        }

        $existing = ! empty($match) ? $query->first() : null;
        $payload = [];

        if ($request->hasFile('attachment')) {
            if (in_array('attachment_path', $columns, true) && filled($existing?->attachment_path ?? null)) {
                Storage::disk('public')->delete($existing->attachment_path);
            }

            $file = $request->file('attachment');

            if (in_array('attachment_path', $columns, true)) {
                $payload['attachment_path'] = $file->store('tracking-advices', 'public');
            }

            if (in_array('attachment_name', $columns, true)) {
                $payload['attachment_name'] = $file->getClientOriginalName();
            }
        }

        if (in_array('page_key', $columns, true)) {
            $payload['page_key'] = $pageKey;
        }

        if (in_array('page_title', $columns, true)) {
            $payload['page_title'] = $validated['page_title'];
        }

        if (in_array('farmer_name', $columns, true)) {
            $payload['farmer_name'] = $validated['farmer_name'] ?? null;
        }

        if (in_array('plot_code', $columns, true)) {
            $payload['plot_code'] = $validated['plot_code'] ?? null;
        }

        if (in_array('round_number', $columns, true)) {
            $payload['round_number'] = $validated['round_number'] ?? null;
        }

        if (in_array('detail_url', $columns, true)) {
            $payload['detail_url'] = $validated['detail_url'] ?? null;
        }

        if (in_array('activity_event_id', $columns, true)) {
            $payload['activity_event_id'] = $validated['activity_id'] ?? null;
        }

        if (in_array('activity_id', $columns, true)) {
            $payload['activity_id'] = $validated['activity_id'] ?? null;
        }

        if (in_array('message', $columns, true)) {
            $payload['message'] = $validated['message'];
        }

        if (in_array('advice_message', $columns, true)) {
            $payload['advice_message'] = $validated['message'];
        }

        if (in_array('advice_status', $columns, true)) {
            $payload['advice_status'] = 'sent';
        }

        if (in_array('sent_at', $columns, true)) {
            $payload['sent_at'] = now();
        }

        if (in_array('sent_by', $columns, true)) {
            $payload['sent_by'] = 'แอดมิน';
        }

        if ($existing) {
            if (in_array('updated_at', $columns, true)) {
                $payload['updated_at'] = now();
            }

            DB::table('tracking_advices')
                ->where($this->firstMatchColumn($match), $this->firstMatchValue($match))
                ->update($payload);

            return;
        }

        if (in_array('id', $columns, true) && $this->tablePrimaryKeyUsesUuid('tracking_advices')) {
            $payload['id'] = (string) Str::uuid();
        }

        if (in_array('created_at', $columns, true)) {
            $payload['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $payload['updated_at'] = now();
        }

        DB::table('tracking_advices')->insert(array_merge($match, $payload));
    }

    private function syncDashboardAlert(string $pageKey, array $validated): void
    {
        try {
            if (! Schema::hasTable('dashboard_work_items')) {
                return;
            }

            $taskTitle = 'แจ้งเตือนรายงานปัญหา: ' . $validated['page_title'];
            $plotCode = trim((string) ($validated['plot_code'] ?? ''));
            $farmerName = trim((string) ($validated['farmer_name'] ?? ''));
            $roundNumber = trim((string) ($validated['round_number'] ?? ''));
            $detailUrl = trim((string) ($validated['detail_url'] ?? ''));
            $activityId = trim((string) ($validated['activity_id'] ?? ''));
            [$userId, $plotId] = $this->resolveDashboardTargetIds($activityId);

            $payload = [
                'task_title' => $taskTitle,
                'issue_category' => 'แจ้งเตือนรายงานปัญหา',
                'status' => 'pending_review',
                'priority' => 'medium',
                'response_required' => true,
                'latest_note' => $validated['message'],
                'meta' => [
                    'source' => 'tracking_advice',
                    'page_key' => $pageKey,
                    'page_title' => $validated['page_title'],
                    'round' => $roundNumber,
                    'detail_url' => $detailUrl,
                    'activity_id' => $activityId,
                    'alert_topic' => 'แจ้งเตือนรายงานปัญหา',
                    'farmer_name' => $farmerName !== '' ? $farmerName : null,
                    'plot_code' => $plotCode !== '' ? $plotCode : null,
                ],
            ];

            if (Schema::hasColumn('dashboard_work_items', 'farmer_name')) {
                $payload['farmer_name'] = $farmerName !== '' ? $farmerName : null;
            }

            if (Schema::hasColumn('dashboard_work_items', 'plot_code')) {
                $payload['plot_code'] = $plotCode !== '' ? $plotCode : null;
            }

            if (Schema::hasColumn('dashboard_work_items', 'detail_url')) {
                $payload['detail_url'] = $detailUrl !== '' ? $detailUrl : null;
            }

            if (Schema::hasColumn('dashboard_work_items', 'last_activity_at')) {
                $payload['last_activity_at'] = now();
            }

            if (Schema::hasColumn('dashboard_work_items', 'user_id') && filled($userId)) {
                $payload['user_id'] = $userId;
            }

            if (Schema::hasColumn('dashboard_work_items', 'plot_id') && filled($plotId)) {
                $payload['plot_id'] = $plotId;
            }

            if (Schema::hasColumn('dashboard_work_items', 'activity_event_id') && $activityId !== '') {
                $payload['activity_event_id'] = $activityId;
            }

            if (Schema::hasColumn('dashboard_work_items', 'resolved_at')) {
                $payload['resolved_at'] = null;
            }

            $match = [
                'task_title' => $taskTitle,
            ];

            if (Schema::hasColumn('dashboard_work_items', 'activity_event_id') && $activityId !== '') {
                $match['activity_event_id'] = $activityId;
            } elseif (Schema::hasColumn('dashboard_work_items', 'user_id') && filled($userId)) {
                $match['user_id'] = $userId;
            }

            $query = DB::table('dashboard_work_items');
            foreach ($match as $column => $value) {
                $query->where($column, $value);
            }

            if ($query->exists()) {
                $query->update(array_merge($payload, [
                    'updated_at' => now(),
                ]));

                return;
            }

            $insertPayload = array_merge($match, $payload, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($this->tablePrimaryKeyUsesUuid('dashboard_work_items')) {
                $insertPayload['id'] = (string) Str::uuid();
            }

            DB::table('dashboard_work_items')->insert($insertPayload);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function resolveDashboardTargetIds(string $activityId): array
    {
        if ($activityId === '') {
            return [null, null];
        }

        foreach (['activity_events', 'planting_plans', 'plots'] as $table) {
            if (! Schema::hasTable($table)) {
                return [null, null];
            }
        }

        $row = DB::table('activity_events as events')
            ->leftJoin('planting_plans as plans', 'plans.id', '=', 'events.plan_id')
            ->leftJoin('plots', 'plots.id', '=', 'plans.plot_id')
            ->where('events.id', $activityId)
            ->select('plots.user_id', 'plots.id as plot_id')
            ->first();

        return [
            $row->user_id ?? null,
            $row->plot_id ?? null,
        ];
    }

    private function trackingAdviceMatch(array $columns, string $pageKey, array $validated): array
    {
        if (in_array('page_key', $columns, true)) {
            return ['page_key' => $pageKey];
        }

        $detailUrl = trim((string) ($validated['detail_url'] ?? ''));
        if ($detailUrl !== '' && in_array('detail_url', $columns, true)) {
            return ['detail_url' => $detailUrl];
        }

        $activityId = trim((string) ($validated['activity_id'] ?? ''));
        if ($activityId !== '' && in_array('activity_event_id', $columns, true)) {
            return ['activity_event_id' => $activityId];
        }

        if ($activityId !== '' && in_array('activity_id', $columns, true)) {
            return ['activity_id' => $activityId];
        }

        return [];
    }

    private function firstMatchColumn(array $match): string
    {
        return (string) array_key_first($match);
    }

    private function firstMatchValue(array $match): mixed
    {
        $key = array_key_first($match);

        return $key !== null ? $match[$key] : null;
    }

    private function tableColumns(string $table): array
    {
        try {
            return DB::table('information_schema.columns')
                ->where('table_schema', 'public')
                ->where('table_name', $table)
                ->orderBy('ordinal_position')
                ->pluck('column_name')
                ->map(fn ($column) => (string) $column)
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function tablePrimaryKeyUsesUuid(string $table): bool
    {
        try {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'id')) {
                return false;
            }

            $column = DB::table('information_schema.columns')
                ->where('table_schema', 'public')
                ->where('table_name', $table)
                ->where('column_name', 'id')
                ->value('data_type');

            return $column === 'uuid';
        } catch (Throwable) {
            return false;
        }
    }
}
