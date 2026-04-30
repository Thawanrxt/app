@extends('admin.layout')
@section('title', 'รายงานปัญหาการปลูกข้าว')
@section('content')
<div class="page-head">
  <div>
    <h1>รายงานปัญหาการปลูกข้าว</h1>
    <p class="muted">สรุปรายการปัญหาและสถานะการติดตามในแปลงปลูก</p>
  </div>
  <a class="btn primary" href="/admin/report/rice/print" target="_blank" rel="noopener">Export PDF</a>
</div>

<div class="card" style="margin-top: 16px;">
  <form class="prep-filters" method="GET" action="/admin/report/rice">
    <div class="search-field">
      <input class="search-input" type="text" name="q" value="{{ $query }}" placeholder="ค้นหาเกษตรกร ชื่อแปลง หรือปัญหา">
      <button class="search-btn" type="submit" aria-label="ค้นหา">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="11" cy="11" r="7"></circle>
          <path d="M20 20l-3.5-3.5"></path>
        </svg>
      </button>
    </div>
    <div class="filter-group">
      <select class="input" name="activity">
        @foreach ($activityOptions as $value => $label)
          <option value="{{ $value }}" @selected($activity === $value)>{{ $label }}</option>
        @endforeach
      </select>
      <select class="input" name="status">
        @foreach ($statusOptions as $value => $label)
          <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
        @endforeach
      </select>
      <input class="input" type="date" name="date" value="{{ $date }}">
    </div>
  </form>
</div>

<div class="card" style="margin-top: 16px;">
  <table class="table">
    <thead>
      <tr>
        <th>เกษตรกร</th>
        <th>ปัญหา</th>
        <th>วันที่</th>
        <th>สถานะ</th>
        <th>รายละเอียด</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($activities as $activityItem)
        <tr>
          <td>
            <div style="font-weight:700;">{{ $activityItem->farmer_name }}</div>
            <div class="muted">{{ $activityItem->plot_code }}</div>
          </td>
          <td>
            <div>{{ $activityItem->issue_found }}</div>
            <div class="muted">{{ $activityItem->activity_name }}</div>
          </td>
          <td>{{ optional($activityItem->activity_date)->translatedFormat('d M Y') ?: '-' }}</td>
          <td>
            <span class="chip {{ $activityItem->status === 'passed' ? 'success' : ($activityItem->status === 'needs_fix' || $activityItem->status === 'failed' ? 'danger' : 'warning') }}">
              {{ $activityItem->status === 'passed' ? 'ผ่านแล้ว' : ($activityItem->status === 'needs_fix' ? 'ต้องแก้ไข' : ($activityItem->status === 'failed' ? 'ไม่ผ่าน' : 'รอตรวจสอบ')) }}
            </span>
          </td>
          <td>
            <a class="btn ghost btn-sm" href="{{ $activityItem->detail_url }}">ดูรายละเอียด</a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="5" class="muted empty-cell">ยังไม่มีข้อมูลปัญหาจากฝั่งผู้ใช้งาน</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
