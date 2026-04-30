<?php

namespace Database\Seeders;

use App\Models\FertilizerTrackingActivity;
use Illuminate\Database\Seeder;

class FertilizerTrackingActivitySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'farmer_name' => 'สมชาย เกษตรดี',
                'plot_code' => 'SK/3345',
                'round_number' => 1,
                'activity_name' => 'การหว่านปุ๋ย',
                'method' => 'หว่านด้วยแรงงานคน',
                'activity_date' => '2026-03-26',
                'fertilizer_type' => '16-20-0',
                'amount_per_rai' => '25 กก./ไร่',
                'details' => 'หว่านรอบแรกหลังข้าวอายุ 20 วัน',
                'issue_found' => null,
                'image_url' => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?q=80&w=1200&auto=format&fit=crop',
                'status' => 'pending_review',
            ],
            [
                'farmer_name' => 'มาลี คำดี',
                'plot_code' => 'ML/4401',
                'round_number' => 1,
                'activity_name' => 'การหว่านปุ๋ย',
                'method' => 'ใช้เครื่องหว่าน',
                'activity_date' => '2026-03-28',
                'fertilizer_type' => '46-0-0',
                'amount_per_rai' => '18 กก./ไร่',
                'details' => 'หว่านซ่อมในจุดที่ต้นข้าวเหลือง',
                'issue_found' => 'หลักฐานรูปถ่ายไม่ชัด',
                'image_url' => 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?q=80&w=1200&auto=format&fit=crop',
                'status' => 'needs_fix',
            ],
        ];

        foreach ($rows as $row) {
            FertilizerTrackingActivity::query()->updateOrCreate(
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
