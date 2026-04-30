<?php

namespace Database\Seeders;

use App\Models\PrepTrackingActivity;
use Illuminate\Database\Seeder;

class PrepTrackingActivitySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'farmer_name' => 'สมศักดิ์ สุขสวย',
                'plot_code' => 'SM1/2345',
                'round_number' => 1,
                'activity_name' => 'การเตรียมดิน',
                'method' => 'ไถดะและไถแปร',
                'activity_date' => '2026-03-25',
                'soil_preparation_method' => 'ใช้รถไถเดินตาม',
                'tillage_depth' => '15 ซม.',
                'soil_result' => 'ค่า pH 6.5',
                'details' => 'เตรียมแปลงก่อนหว่าน 3 วัน และเก็บตัวอย่างดินส่งตรวจ',
                'issue_found' => null,
                'image_url' => 'https://images.unsplash.com/photo-1501004318641-b39e6451bec6?q=80&w=1200&auto=format&fit=crop',
                'status' => 'passed',
            ],
            [
                'farmer_name' => 'สมศรี อวยพร',
                'plot_code' => 'SO/2504',
                'round_number' => 2,
                'activity_name' => 'การเตรียมดิน',
                'method' => 'ไถกลบตอซัง',
                'activity_date' => '2026-03-27',
                'soil_preparation_method' => 'จ้างรถไถใหญ่',
                'tillage_depth' => '12 ซม.',
                'soil_result' => 'อินทรียวัตถุต่ำกว่าที่แนะนำ',
                'details' => 'ไถกลบเสร็จแล้วแต่พื้นที่บางส่วนยังแน่น',
                'issue_found' => 'ยังปรับระดับแปลงไม่เสมอ',
                'image_url' => 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?q=80&w=1200&auto=format&fit=crop',
                'status' => 'needs_fix',
            ],
        ];

        foreach ($rows as $row) {
            PrepTrackingActivity::query()->updateOrCreate(
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
