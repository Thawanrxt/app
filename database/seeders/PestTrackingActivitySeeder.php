<?php

namespace Database\Seeders;

use App\Models\PestTrackingActivity;
use Illuminate\Database\Seeder;

class PestTrackingActivitySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'farmer_name' => 'สายชล ใจดี',
                'plot_code' => 'SC/7712',
                'round_number' => 1,
                'activity_name' => 'การจัดการศัตรูพืช',
                'pest_type' => 'เพลี้ยกระโดดสีน้ำตาล',
                'chemical_name' => 'คลอไพริฟอส',
                'mix_ratio' => '50 มิลลิลิตร / น้ำ 20 ลิตร',
                'activity_date' => '2026-03-29',
                'details' => 'ฉีดพ่นบริเวณที่พบการระบาดชัดเจนและเว้นแนวขอบแปลง',
                'issue_found' => 'ยังพบเพลี้ยกระโดดสีน้ำตาลบางจุด',
                'image_url' => 'https://images.unsplash.com/photo-1501004318641-b39e6451bec6?q=80&w=1200&auto=format&fit=crop',
                'status' => 'needs_fix',
            ],
            [
                'farmer_name' => 'สมบูรณ์ เกษตรสุข',
                'plot_code' => 'SB/4409',
                'round_number' => 2,
                'activity_name' => 'การจัดการศัตรูพืช',
                'pest_type' => 'หนอนกอ',
                'chemical_name' => 'ฟิโพรนิล',
                'mix_ratio' => '30 มิลลิลิตร / น้ำ 20 ลิตร',
                'activity_date' => '2026-03-28',
                'details' => 'วางแผนติดตามผลหลังการฉีดพ่น 3 วัน',
                'issue_found' => null,
                'image_url' => 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?q=80&w=1200&auto=format&fit=crop',
                'status' => 'pending_review',
            ],
        ];

        foreach ($rows as $row) {
            PestTrackingActivity::query()->updateOrCreate(
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
