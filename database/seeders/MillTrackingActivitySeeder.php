<?php

namespace Database\Seeders;

use App\Models\MillTrackingActivity;
use Illuminate\Database\Seeder;

class MillTrackingActivitySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'farmer_name' => 'สมศักดิ์ สุขสวย',
                'plot_code' => 'SM1/2345',
                'round_number' => 1,
                'activity_name' => 'ขายข้าวเข้าโรงสี',
                'activity_date' => '2026-03-24',
                'mill_name' => 'โรงสีข้าว เจริญทรัพย์',
                'queue_number' => '97',
                'document_number' => '07937',
                'product_name' => 'ข้าวหอมมะลิ 105',
                'vehicle_plate' => '81-3928',
                'time_in' => '14:09',
                'time_out' => '14:17',
                'pre_mill_weight_kg' => 7000,
                'post_mill_weight_kg' => 4780,
                'net_weight_kg' => 2220,
                'price_per_kg' => 13.69,
                'total_income' => 30391.80,
                'details' => 'ส่งมอบข้าวเรียบร้อยและรับใบชั่งครบ',
                'issue_found' => null,
                'image_url' => null,
                'status' => 'passed',
            ],
            [
                'farmer_name' => 'มาลี คำดี',
                'plot_code' => 'ML/4401',
                'round_number' => 1,
                'activity_name' => 'ขายข้าวเข้าโรงสี',
                'activity_date' => '2026-03-27',
                'mill_name' => 'โรงสีอีสานพัฒนา',
                'queue_number' => '102',
                'document_number' => '08125',
                'product_name' => 'ข้าวเหนียว กข6',
                'vehicle_plate' => '70-1184',
                'time_in' => '09:35',
                'time_out' => '10:02',
                'pre_mill_weight_kg' => 5200,
                'post_mill_weight_kg' => 3610,
                'net_weight_kg' => 1590,
                'price_per_kg' => 12.85,
                'total_income' => 20431.50,
                'details' => 'เอกสารรับซื้อยังรอสแกนเข้าระบบ',
                'issue_found' => 'ยังไม่อัปโหลดเอกสารใบรับซื้อ',
                'image_url' => null,
                'status' => 'needs_fix',
            ],
        ];

        foreach ($rows as $row) {
            MillTrackingActivity::query()->updateOrCreate(
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
