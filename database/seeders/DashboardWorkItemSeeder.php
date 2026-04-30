<?php

namespace Database\Seeders;

use App\Models\DashboardWorkItem;
use Illuminate\Database\Seeder;

class DashboardWorkItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'farmer_name' => 'สมศักดิ์ สุขสวย',
                'plot_code' => 'SM1/2345',
                'task_title' => 'ตรวจแปลงรอบจัดการน้ำ',
                'issue_category' => 'น้ำ',
                'status' => 'in_progress',
                'priority' => 'urgent',
                'progress_percent' => 68,
                'due_date' => '2026-03-24',
                'last_activity_at' => '2026-03-24 09:20:00',
                'responded_at' => '2026-03-24 09:35:00',
                'response_required' => true,
                'latest_note' => 'รอคำแนะนำการจัดการน้ำหลังฝน',
            ],
            [
                'farmer_name' => 'สมศรี อวยพร',
                'plot_code' => 'SO/2504',
                'task_title' => 'ติดตามอาการโรคไหม้',
                'issue_category' => 'โรคพืช',
                'status' => 'needs_fix',
                'priority' => 'urgent',
                'progress_percent' => 51,
                'due_date' => '2026-03-24',
                'last_activity_at' => '2026-03-24 08:40:00',
                'responded_at' => null,
                'response_required' => true,
                'latest_note' => 'พบโรคไหม้ช่วงต้น ต้องอัปโหลดหลักฐานเพิ่ม',
            ],
            [
                'farmer_name' => 'สมชาย เกษตรดี',
                'plot_code' => 'SK/2345',
                'task_title' => 'ปิดรอบประเมิน SRP',
                'issue_category' => null,
                'status' => 'passed',
                'priority' => 'normal',
                'progress_percent' => 100,
                'due_date' => '2026-03-23',
                'last_activity_at' => '2026-03-23 15:10:00',
                'responded_at' => '2026-03-23 15:10:00',
                'response_required' => false,
                'latest_note' => 'ผ่านการประเมิน SRP เรียบร้อย',
            ],
            [
                'farmer_name' => 'มาลี คำดี',
                'plot_code' => 'ML/8891',
                'task_title' => 'สรุปผลการประเมินปลายรอบ',
                'issue_category' => null,
                'status' => 'passed',
                'priority' => 'normal',
                'progress_percent' => 100,
                'due_date' => '2026-03-22',
                'last_activity_at' => '2026-03-22 13:15:00',
                'responded_at' => '2026-03-22 13:15:00',
                'response_required' => false,
                'latest_note' => 'เอกสารครบและผ่านการประเมิน',
            ],
            [
                'farmer_name' => 'บุญช่วย มีสุข',
                'plot_code' => 'BM/4401',
                'task_title' => 'รอตรวจเอกสาร SRP รอบใหม่',
                'issue_category' => 'เอกสาร',
                'status' => 'pending_review',
                'priority' => 'medium',
                'progress_percent' => 24,
                'due_date' => '2026-03-24',
                'last_activity_at' => '2026-03-24 07:55:00',
                'responded_at' => null,
                'response_required' => true,
                'latest_note' => 'รอทีมตรวจสอบเอกสารที่อัปโหลด',
            ],
            [
                'farmer_name' => 'สายชล ใจดี',
                'plot_code' => 'SC/7712',
                'task_title' => 'ติดตามการแก้ไขปัญหาศัตรูพืช',
                'issue_category' => 'ศัตรูพืช',
                'status' => 'needs_fix',
                'priority' => 'medium',
                'progress_percent' => 46,
                'due_date' => '2026-03-25',
                'last_activity_at' => '2026-03-24 10:05:00',
                'responded_at' => null,
                'response_required' => true,
                'latest_note' => 'พบเพลี้ยกระโดดสีน้ำตาล ต้องติดตามผลซ้ำ',
            ],
        ];

        foreach ($items as $item) {
            DashboardWorkItem::query()->updateOrCreate(
                ['task_title' => $item['task_title'], 'plot_code' => $item['plot_code']],
                $item
            );
        }
    }
}
