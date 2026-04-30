<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class SettingController extends Controller
{
    public function edit(): View
    {
        $settingsAvailable = $this->settingsTableAvailable();
        $missingColumns = $this->missingRequiredColumns();
        $settings = $settingsAvailable ? $this->loadSettings() : null;

        return view('admin.settings', [
            'settings' => (object) ($settings?->toArray() ?? $this->defaultSettings()),
            'settingsAvailable' => $settingsAvailable,
            'missingColumns' => $missingColumns,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['required', 'string', 'max:255'],
            'font_family' => ['required', 'string', 'max:255'],
            'font_size' => ['required', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'max:255'],
            'date_format' => ['required', 'string', 'max:255'],
            'area_unit' => ['required', 'string', 'max:255'],
        ]);

        if (! $this->settingsTableAvailable()) {
            $message = 'ยังไม่สามารถบันทึกได้ เพราะตาราง app_settings ยังไม่พร้อมใช้งาน';

            if ($this->missingRequiredColumns() !== []) {
                $message .= ' คอลัมน์ที่ยังขาด: ' . implode(', ', $this->missingRequiredColumns());
            }

            return redirect('/admin/settings')->with('warning', $message);
        }

        DB::table('app_settings')->updateOrInsert(
            ['user_id' => $this->settingsOwnerId()],
            [
                ...$validated,
                'updated_at' => now(),
            ],
        );

        return redirect('/admin/settings')->with('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
    }

    private function loadSettings(): ?AppSetting
    {
        return AppSetting::query()
            ->where('user_id', $this->settingsOwnerId())
            ->first();
    }

    private function settingsTableAvailable(): bool
    {
        try {
            return Schema::hasTable('app_settings') && $this->missingRequiredColumns() === [];
        } catch (Throwable) {
            return false;
        }
    }

    private function missingRequiredColumns(): array
    {
        if (! Schema::hasTable('app_settings')) {
            return ['app_settings'];
        }

        $requiredColumns = array_keys($this->defaultSettings());

        return array_values(array_filter(
            $requiredColumns,
            fn (string $column): bool => ! Schema::hasColumn('app_settings', $column)
        ));
    }

    private function defaultSettings(): array
    {
        return [
            'theme' => 'light',
            'font_family' => 'Prompt',
            'font_size' => '16',
            'language' => 'th',
            'timezone' => 'Asia/Bangkok',
            'date_format' => 'DD/MM/YYYY',
            'area_unit' => 'rai',
        ];
    }

    private function settingsOwnerId(): string
    {
        $adminId = User::query()
            ->where('role', 'ADMIN')
            ->orderBy('username')
            ->value('id');

        if ($adminId) {
            return (string) $adminId;
        }

        $userId = User::query()
            ->orderBy('username')
            ->value('id');

        if (! $userId) {
            abort(500, 'ไม่พบผู้ใช้สำหรับผูกข้อมูลการตั้งค่า');
        }

        return (string) $userId;
    }
}
