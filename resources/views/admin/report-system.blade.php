@extends('admin.layout')
@section('title', 'รายงานปัญหาการใช้งานระบบ')
@section('content')
<div class="page-head">
  <div>
    <h1>รายงานปัญหาการใช้งานระบบ</h1>
    <p class="muted">รายการปัญหาและคำร้องที่ผู้ใช้งานแจ้งเข้ามาผ่านระบบ</p>
  </div>
  <a class="btn primary" href="{{ url('/admin/report/system/print?' . http_build_query(array_filter($filters))) }}" target="_blank" rel="noopener">Export PDF</a>
</div>

<form class="card" style="margin-top: 16px;" method="GET" action="{{ url('/admin/report/system') }}">
  <div class="prep-filters">
    <div class="search-field">
      <input class="search-input" type="text" name="q" value="{{ $filters['q'] }}" placeholder="ค้นหาจากชื่อเกษตรกร หัวข้อ อีเมล เบอร์โทร หรือรายละเอียด">
      <button class="search-btn" type="submit" aria-label="ค้นหา">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="11" cy="11" r="7"></circle>
          <path d="M20 20l-3.5-3.5"></path>
        </svg>
      </button>
    </div>
    <div class="filter-group">
      <select class="input" name="subject" onchange="this.form.submit()">
        <option value="">หัวข้อทั้งหมด</option>
        @foreach ($subjectOptions as $subject)
          <option value="{{ $subject }}" @selected($filters['subject'] === $subject)>{{ $subject }}</option>
        @endforeach
      </select>
      <select class="input" name="status" onchange="this.form.submit()">
        <option value="">สถานะทั้งหมด</option>
        <option value="OPEN" @selected($filters['status'] === 'OPEN')>เปิดเคส</option>
        <option value="IN_PROGRESS" @selected($filters['status'] === 'IN_PROGRESS')>กำลังดำเนินการ</option>
        <option value="PENDING" @selected($filters['status'] === 'PENDING')>รอตรวจสอบ</option>
        <option value="RESOLVED" @selected($filters['status'] === 'RESOLVED')>เสร็จสิ้นแล้ว</option>
        <option value="CLOSED" @selected($filters['status'] === 'CLOSED')>ปิดเคสแล้ว</option>
        <option value="REJECTED" @selected($filters['status'] === 'REJECTED')>ไม่ผ่าน</option>
      </select>
      <input class="input" type="date" name="date" value="{{ $filters['date'] }}" onchange="this.form.submit()">
    </div>
  </div>
</form>

<div class="card" style="margin-top: 16px;">
  <table class="table">
    <thead>
      <tr>
        <th>ชื่อเกษตรกร</th>
        <th>ปัญหา</th>
        <th>วันที่</th>
        <th>สถานะ</th>
        <th>รายละเอียด</th>
        <th>ดำเนินการ</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($tickets as $ticket)
        <tr>
          <td>
            <strong>{{ $ticket->reporter_name }}</strong>
            @if (!empty($ticket->contact_phone))
              <div class="muted">{{ $ticket->contact_phone }}</div>
            @endif
          </td>
          <td>
            <strong>{{ $ticket->subject ?: 'ไม่ระบุหัวข้อ' }}</strong>
            <div class="muted">{{ \Illuminate\Support\Str::limit($ticket->message ?: '-', 90) }}</div>
          </td>
          <td>{{ $ticket->formatted_date_short }}</td>
          <td><span class="status-pill {{ $ticket->status_class }}">{{ $ticket->status }}</span></td>
          <td><a class="btn ghost" href="{{ $ticket->detail_url }}">ดูรายละเอียด</a></td>
          <td>
            <form method="POST" action="{{ url('/admin/report/system/' . $ticket->id . '/delete') }}" onsubmit="return confirm('ยืนยันการลบรายการปัญหานี้?')">
              @csrf
              <button class="icon-action-btn danger compact" type="submit" aria-label="ลบรายการปัญหา">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"></path>
                  <path d="M19 6l-1 13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6"></path>
                  <path d="M10 11v6"></path>
                  <path d="M14 11v6"></path>
                </svg>
              </button>
            </form>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="muted empty-cell">ยังไม่มีข้อมูลปัญหาการใช้งานระบบในเงื่อนไขที่เลือก</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
