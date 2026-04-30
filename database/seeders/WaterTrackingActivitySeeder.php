<?php

namespace Database\Seeders;

use App\Models\WaterTrackingActivity;
use Illuminate\Database\Seeder;

class WaterTrackingActivitySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'farmer_name' => 'สมศักดิ์ สุขสวย',
                'plot_code' => 'SM1/2345',
                'round_number' => 1,
                'activity_name' => 'การจัดการน้ำ',
                'method' => 'เปียกสลับแห้ง (AWD)',
                'activity_date' => '2026-03-29',
                'water_level' => 'ขังน้ำเหนือพื้นดิน 5 ซม.',
                'details' => 'ปล่อยน้ำเข้าช่วงเช้าและเว้นช่วงระบายน้ำช่วงบ่าย',
                'issue_found' => null,
                'image_url' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?q=80&w=1200&auto=format&fit=crop',
                'status' => 'pending_review',
            ],
            [
                'farmer_name' => 'สมศรี อวยพร',
                'plot_code' => 'SO/2504',
                'round_number' => 2,
                'activity_name' => 'การจัดการน้ำ',
                'method' => 'ปล่อยน้ำเข้าแปลงช่วงสั้น',
                'activity_date' => '2026-03-28',
                'water_level' => 'ขังน้ำ 3 ซม.',
                'details' => 'น้ำเข้าช่วงเย็นแต่ระดับน้ำไม่สม่ำเสมอ',
                'issue_found' => 'บางจุดน้ำไม่ทั่วแปลง',
                'image_url' => 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?q=80&w=1200&auto=format&fit=crop',
                'status' => 'needs_fix',
            ],
        ];

        foreach ($rows as $row) {
            WaterTrackingActivity::query()->updateOrCreate(
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
