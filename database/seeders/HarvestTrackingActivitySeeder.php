<?php

namespace Database\Seeders;

use App\Models\HarvestTrackingActivity;
use Illuminate\Database\Seeder;

class HarvestTrackingActivitySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'farmer_name' => 'บุญช่วย มีสุข',
                'plot_code' => 'BM/4401',
                'round_number' => 1,
                'activity_name' => 'การเก็บเกี่ยว',
                'activity_date' => '2026-03-21',
                'started_at' => '2026-03-21',
                'ended_at' => '2026-03-22',
                'yield_amount_kg' => 15000,
                'moisture_percent' => 25,
                'details' => 'เก็บเกี่ยวครบทั้งแปลงภายใน 2 วัน',
                'issue_found' => null,
                'image_url' => 'https://images.unsplash.com/photo-1501004318641-b39e6451bec6?q=80&w=1200&auto=format&fit=crop',
                'status' => 'passed',
            ],
            [
                'farmer_name' => 'สมศรี อวยพร',
                'plot_code' => 'SO/2504',
                'round_number' => 2,
                'activity_name' => 'การเก็บเกี่ยว',
                'activity_date' => '2026-03-23',
                'started_at' => '2026-03-23',
                'ended_at' => '2026-03-24',
                'yield_amount_kg' => 11240,
                'moisture_percent' => 27,
                'details' => 'เก็บเกี่ยวล่าช้าเพราะฝนช่วงบ่าย',
                'issue_found' => 'รถเกี่ยวเสียระหว่างวัน',
                'image_url' => 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?q=80&w=1200&auto=format&fit=crop',
                'status' => 'needs_fix',
            ],
        ];

        foreach ($rows as $row) {
            HarvestTrackingActivity::query()->updateOrCreate(
                [
                    'farmer_name' => $row['farmer_name'],
                    'plot_code' => $row['plot_code'],
                    'activity_date' => $row['activity_date'],
                ],
                $row
            );
        }
    }
}
