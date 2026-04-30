<?php

namespace App\Http\Controllers;

use App\Models\DashboardWorkItem;
use App\Models\TrackingAdvice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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

        $advice = TrackingAdvice::query()->firstOrNew([
            'page_key' => $pageKey,
        ]);

        if ($request->hasFile('attachment')) {
            if ($advice->attachment_path) {
                Storage::disk('public')->delete($advice->attachment_path);
            }

            $file = $request->file('attachment');
            $advice->attachment_path = $file->store('tracking-advices', 'public');
            $advice->attachment_name = $file->getClientOriginalName();
        }

        $advice->page_title = $validated['page_title'];
        $advice->message = $validated['message'];
        $advice->sent_at = now();
        $advice->sent_by = 'à¹à¸­à¸”à¸¡à¸´à¸™';
        $advice->save();

        $this->syncDashboardAlert($pageKey, $validated);

        return back()->with('success', 'à¸ªà¹ˆà¸‡à¸„à¸³à¹à¸™à¸°à¸™à¸³à¸–à¸¶à¸‡à¹€à¸à¸©à¸•à¸£à¸à¸£à¹€à¸£à¸µà¸¢à¸šà¸£à¹‰à¸­à¸¢à¹à¸¥à¹‰à¸§');
    }

    private function syncDashboardAlert(string $pageKey, array $validated): void
    {
        try {
            if (! Schema::hasTable('dashboard_work_items')) {
                return;
            }

            $taskTitle = 'à¹à¸ˆà¹‰à¸‡à¹€à¸•à¸·à¸­à¸™à¸£à¸²à¸¢à¸‡à¸²à¸™à¸›à¸±à¸à¸«à¸²: ' . $validated['page_title'];
            $plotCode = trim((string) ($validated['plot_code'] ?? ''));
            $farmerName = trim((string) ($validated['farmer_name'] ?? ''));
            $roundNumber = trim((string) ($validated['round_number'] ?? ''));
            $detailUrl = trim((string) ($validated['detail_url'] ?? ''));
            $activityId = trim((string) ($validated['activity_id'] ?? ''));
            $payload = [
                'task_title' => $taskTitle,
                'issue_category' => 'à¹à¸ˆà¹‰à¸‡à¹€à¸•à¸·à¸­à¸™à¸£à¸²à¸¢à¸‡à¸²à¸™à¸›à¸±à¸à¸«à¸²',
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
                    'alert_topic' => 'à¹à¸ˆà¹‰à¸‡à¹€à¸•à¸·à¸­à¸™à¸£à¸²à¸¢à¸‡à¸²à¸™à¸›à¸±à¸à¸«à¸²',
                    'farmer_name' => $farmerName !== '' ? $farmerName : null,
                    'plot_code' => $plotCode !== '' ? $plotCode : null,
                ],
            ];

            if (Schema::hasColumn('dashboard_work_items', 'resolved_at')) {
                $payload['resolved_at'] = null;
            }

            DashboardWorkItem::query()->updateOrCreate(
                [
                    'task_title' => $taskTitle,
                ],
                $payload
            );
        } catch (Throwable) {
            // Keep advice sending successful even if dashboard alert sync fails.
        }
    }
}
