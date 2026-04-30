<?php

namespace Database\Seeders;

use App\Models\DiseaseTrackingActivity;
use Illuminate\Database\Seeder;

class DiseaseTrackingActivitySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'farmer_name' => 'สมศรี อวยพร',
                'plot_code' => 'SO/2504',
                'round_number' => 2,
                'activity_name' => 'การจัดการโรคพืช',
                'disease_type' => 'โรคไหม้',
                'chemical_name' => 'ไอโซโปรไทโอเลน',
                'used_amount' => '500 มิลลิลิตร',
                'mix_ratio' => 'ผสมน้ำ 20 ลิตร',
                'activity_date' => '2026-03-29',
                'details' => 'ฉีดพ่นช่วงเช้าในบริเวณที่พบอาการระบาด',
                'issue_found' => 'เริ่มพบจุดสีน้ำตาลกระจายเป็นวงกว้าง',
                'image_url' => 'https://images.unsplash.com/photo-1519681393784-d120267933ba?q=80&w=1200&auto=format&fit=crop',
                'status' => 'needs_fix',
            ],
            [
                'farmer_name' => 'สมศักดิ์ สุขสวย',
                'plot_code' => 'SM1/2410',
                'round_number' => 1,
                'activity_name' => 'การจัดการโรคพืช',
                'disease_type' => 'โรคขอบใบแห้ง',
                'chemical_name' => 'คอปเปอร์ไฮดรอกไซด์',
                'used_amount' => '300 มิลลิลิตร',
                'mix_ratio' => 'ผสมน้ำ 20 ลิตร',
                'activity_date' => '2026-03-28',
                'details' => 'ติดตามผลหลังพ่นครั้งแรกและบันทึกอาการซ้ำ',
                'issue_found' => null,
                'image_url' => 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?q=80&w=1200&auto=format&fit=crop',
                'status' => 'pending_review',
            ],
        ];

        foreach ($rows as $row) {
            DiseaseTrackingActivity::query()->updateOrCreate(
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
