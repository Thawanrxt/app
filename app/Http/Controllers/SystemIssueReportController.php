<?php

namespace App\Http\Controllers;

use App\Support\SearchTextMatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SystemIssueReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);

        return view('admin.report-system', [
            'tickets' => $this->loadTickets($filters),
            'filters' => $filters,
            'subjectOptions' => $this->subjectOptions(),
        ]);
    }

    public function show(Request $request): View
    {
        $ticketId = trim((string) $request->query('id', ''));

        abort_if($ticketId === '', 404);

        $ticket = $this->baseQuery()
            ->where('tickets.id', $ticketId)
            ->first();

        abort_if($ticket === null, 404);

        // โหลดคำแนะนำที่เคยส่งไปแล้ว (ถ้ามีคอลัมน์)
        $adminReply = null;
        $adminReplyAt = null;
        try {
            $raw = DB::table('support_tickets')->where('id', $ticketId)->first(['admin_reply', 'admin_reply_at', 'status']);
            $adminReply = $raw->admin_reply ?? null;
            $adminReplyAt = $raw->admin_reply_at ?? null;
        } catch (\Throwable) {}

        return view('admin.report-system-detail', [
            'ticket'      => $this->normalizeTicket($ticket),
            'adminReply'  => $adminReply,
            'adminReplyAt' => $adminReplyAt,
            'backUrl'     => url('/admin/report/system'),
        ]);
    }

    public function reply(string $ticket, Request $request): RedirectResponse
    {
        $exists = DB::table('support_tickets')->where('id', $ticket)->exists();
        abort_if(! $exists, 404);

        $validated = $request->validate([
            'admin_reply' => 'required|string|max:2000',
            'new_status'  => 'required|string|in:IN_PROGRESS,RESOLVED,CLOSED',
        ]);

        // เพิ่มคอลัมน์ถ้ายังไม่มี
        try {
            DB::statement('ALTER TABLE support_tickets ADD COLUMN IF NOT EXISTS admin_reply TEXT');
            DB::statement('ALTER TABLE support_tickets ADD COLUMN IF NOT EXISTS admin_reply_at TIMESTAMP');
        } catch (\Throwable) {}

        // อัปเดต ticket
        DB::table('support_tickets')->where('id', $ticket)->update([
            'admin_reply'    => $validated['admin_reply'],
            'admin_reply_at' => now(),
            'status'         => $validated['new_status'],
        ]);

        // ส่ง Notification ไปที่แอพของผู้ใช้
        $ticketRow = DB::table('support_tickets')->where('id', $ticket)->first(['user_id', 'subject']);
        if ($ticketRow?->user_id) {
            try {
                DB::statement("
                    INSERT INTO notifications (id, user_id, title, message, is_read, created_at)
                    VALUES (gen_random_uuid(), ?, ?, ?, false, NOW())
                ", [
                    $ticketRow->user_id,
                    'มีคำตอบกลับจากแอดมิน',
                    "เรื่อง: " . ($ticketRow->subject ?: 'รายงานปัญหา') . "\n\n" . $validated['admin_reply'],
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Notification insert failed: ' . $e->getMessage());
            }
        }

        return redirect('/admin/report/system/detail?id=' . $ticket)
            ->with('success', 'ส่งคำแนะนำและแจ้งเตือนผู้ใช้เรียบร้อยแล้ว');
    }

    public function print(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);

        return view('admin.report-system-print', [
            'title' => 'รายงานปัญหาการใช้งานระบบ',
            'tickets' => $this->loadTickets($filters),
        ]);
    }

    public function destroy(string $ticket): RedirectResponse
    {
        $exists = DB::table('support_tickets')
            ->where('id', $ticket)
            ->exists();

        abort_if(! $exists, 404);

        DB::table('support_tickets')
            ->where('id', $ticket)
            ->delete();

        return redirect('/admin/report/system')->with('success', 'ลบรายการปัญหาการใช้งานระบบเรียบร้อยแล้ว');
    }

    private function loadTickets(array $filters): Collection
    {
        $query = $this->baseQuery()->orderByDesc('tickets.created_at');

        if ($filters['subject'] !== '') {
            $query->where('tickets.subject', $filters['subject']);
        }

        if ($filters['status'] !== '') {
            $query->where('tickets.status', $filters['status']);
        }

        if ($filters['date'] !== '') {
            $query->whereDate('tickets.created_at', $filters['date']);
        }

        $tickets = $query
            ->get()
            ->map(fn (object $ticket) => $this->normalizeTicket($ticket))
            ->values();

        return SearchTextMatcher::filterByPriority($tickets, [
            fn (object $ticket) => $ticket->reporter_name ?? null,
            fn (object $ticket) => $ticket->farmer_name ?? null,
            fn (object $ticket) => $ticket->subject ?? null,
            fn (object $ticket) => $ticket->contact_email ?? null,
            fn (object $ticket) => $ticket->contact_phone ?? null,
            fn (object $ticket) => $ticket->message ?? null,
            fn (object $ticket) => $ticket->username ?? null,
        ], $filters['q']);
    }

    private function baseQuery(): Builder
    {
        return DB::table('support_tickets as tickets')
            ->leftJoin('users', 'users.id', '=', 'tickets.user_id')
            ->leftJoin('farmer_profiles as profiles', 'profiles.user_id', '=', 'users.id')
            ->select([
                'tickets.id',
                'tickets.user_id',
                'tickets.subject',
                'tickets.message',
                'tickets.contact_email',
                'tickets.contact_phone',
                'tickets.status as source_status',
                'tickets.created_at',
                'users.username',
                'profiles.full_name as farmer_name',
            ]);
    }

    private function subjectOptions(): Collection
    {
        return DB::table('support_tickets')
            ->whereNotNull('subject')
            ->where('subject', '<>', '')
            ->distinct()
            ->orderBy('subject')
            ->pluck('subject');
    }

    private function normalizeTicket(object $ticket): object
    {
        $statusCode = strtoupper((string) ($ticket->source_status ?? 'OPEN'));
        $statusMap = [
            'OPEN' => ['label' => 'เปิดเคส', 'class' => 'pending'],
            'PENDING' => ['label' => 'รอตรวจสอบ', 'class' => 'pending'],
            'IN_PROGRESS' => ['label' => 'กำลังดำเนินการ', 'class' => 'warning'],
            'RESOLVED' => ['label' => 'เสร็จสิ้นแล้ว', 'class' => 'success'],
            'CLOSED' => ['label' => 'ปิดเคสแล้ว', 'class' => 'success'],
            'REJECTED' => ['label' => 'ไม่ผ่าน', 'class' => 'danger'],
        ];

        $status = $statusMap[$statusCode] ?? ['label' => $statusCode !== '' ? $statusCode : 'ไม่ระบุ', 'class' => 'default'];
        $reporterName = trim((string) ($ticket->farmer_name ?? ''));

        if ($reporterName === '') {
            $reporterName = trim((string) ($ticket->username ?? ''));
        }

        if ($reporterName === '') {
            $reporterName = trim((string) ($ticket->contact_email ?? ''));
        }

        if ($reporterName === '') {
            $reporterName = trim((string) ($ticket->contact_phone ?? ''));
        }

        if ($reporterName === '') {
            $reporterName = 'ผู้ใช้ไม่ระบุชื่อ';
        }

        $ticket->reporter_name = $reporterName;
        $ticket->status = $status['label'];
        $ticket->status_class = $status['class'];
        $ticket->formatted_date = $this->formatThaiDateTime($ticket->created_at);
        $ticket->formatted_date_short = $this->formatThaiDate($ticket->created_at);
        $ticket->detail_url = url('/admin/report/system/detail?id=' . $ticket->id);

        return $ticket;
    }

    private function filtersFromRequest(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'subject' => trim((string) $request->query('subject', '')),
            'status' => trim((string) $request->query('status', '')),
            'date' => trim((string) $request->query('date', '')),
        ];
    }

    private function formatThaiDateTime(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return (string) $value;
        }

        $months = [
            1 => 'ม.ค.',
            2 => 'ก.พ.',
            3 => 'มี.ค.',
            4 => 'เม.ย.',
            5 => 'พ.ค.',
            6 => 'มิ.ย.',
            7 => 'ก.ค.',
            8 => 'ส.ค.',
            9 => 'ก.ย.',
            10 => 'ต.ค.',
            11 => 'พ.ย.',
            12 => 'ธ.ค.',
        ];

        return sprintf(
            '%d %s %d %s',
            (int) date('j', $timestamp),
            $months[(int) date('n', $timestamp)] ?? date('m', $timestamp),
            (int) date('Y', $timestamp) + 543,
            date('H:i', $timestamp)
        );
    }

    private function formatThaiDate(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return (string) $value;
        }

        $months = [
            1 => 'ม.ค.',
            2 => 'ก.พ.',
            3 => 'มี.ค.',
            4 => 'เม.ย.',
            5 => 'พ.ค.',
            6 => 'มิ.ย.',
            7 => 'ก.ค.',
            8 => 'ส.ค.',
            9 => 'ก.ย.',
            10 => 'ต.ค.',
            11 => 'พ.ย.',
            12 => 'ธ.ค.',
        ];

        return sprintf(
            '%d %s %d',
            (int) date('j', $timestamp),
            $months[(int) date('n', $timestamp)] ?? date('m', $timestamp),
            (int) date('Y', $timestamp) + 543
        );
    }
}
