@extends('admin.layout')

@section('title', 'กิจกรรมล่าสุด')

@section('content')
<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="/admin" aria-label="กลับไปหน้าแดชบอร์ด">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>กิจกรรมล่าสุด</h1>
      <p class="muted">ประวัติการอัปเดตจากข้อมูลจริงในระบบ</p>
    </div>
  </div>
</div>

<form class="card" style="margin-bottom: 16px;" method="GET" action="/admin/activity">
  <div class="prep-filters" style="display: flex; flex-wrap: nowrap; align-items: center; gap: 12px;">
    <div class="search-field" style="flex: 1 1 auto; min-width: 0;">
      <input
        class="search-input"
        type="text"
        name="q"
        value="{{ $query }}"
        placeholder="ค้นหาจากกิจกรรม เกษตรกร แปลง หรือหมายเหตุ"
        aria-label="ค้นหากิจกรรม"
      >
      <button class="search-btn" type="submit" aria-label="ค้นหา">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="11" cy="11" r="7"></circle>
          <path d="M20 20l-3.5-3.5"></path>
        </svg>
      </button>
    </div>
    <div class="filter-group" style="flex: 0 0 280px;">
      <select class="input" name="status" aria-label="กรองตามสถานะ">
        <option value="" @selected($status === '')>ทุกสถานะ</option>
        <option value="pending_review" @selected($status === 'pending_review')>รอตรวจสอบ</option>
        <option value="in_progress" @selected($status === 'in_progress')>กำลังตรวจ</option>
        <option value="needs_fix" @selected($status === 'needs_fix')>ต้องแก้ไข</option>
        <option value="passed" @selected($status === 'passed')>ผ่านแล้ว</option>
        <option value="failed" @selected($status === 'failed')>ไม่ผ่าน</option>
      </select>
    </div>
  </div>
</form>

<div class="card activity-page">
  <div class="card-head">
    <h3>ไทม์ไลน์กิจกรรม</h3>
    <span class="muted">แสดง {{ $activities->count() }} รายการล่าสุด</span>
  </div>

  <div class="activity-timeline">
    @forelse ($activities as $activity)
      <div class="activity-row">
        <span class="activity-time">{{ $activity['time'] }}</span>
        <div class="activity-content">
          <div class="activity-title">{{ $activity['title'] }}</div>
          <div class="muted">{{ $activity['subtitle'] }}</div>
        </div>
        <span class="activity-tag {{ $activity['tag_class'] }}">{{ $activity['tag_label'] }}</span>
      </div>
    @empty
      <div class="activity-row">
        <span class="activity-time">-</span>
        <div class="activity-content">
          <div class="activity-title">ยังไม่มีกิจกรรมในระบบ</div>
          <div class="muted">ลองเปลี่ยนคำค้นหาหรือรอข้อมูลกิจกรรมจากระบบเพิ่มเติม</div>
        </div>
      </div>
    @endforelse
  </div>
</div>
@endsection
