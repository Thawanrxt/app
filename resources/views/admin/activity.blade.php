@extends('admin.layout')

@section('title', 'กิจกรรมล่าสุด')

@section('content')
<div class="notification-page">
<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="/admin" aria-label="กลับไปหน้าแดชบอร์ด">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>กิจกรรมล่าสุด</h1>
      <p class="muted">รายการกิจกรรมและการอัปเดตล่าสุดในระบบ พร้อมกรองตามหมวดงานและสถานะ</p>
    </div>
  </div>
</div>

<form class="card" style="margin-bottom: 16px;" method="GET" action="/admin/activity">
  <div class="prep-filters" style="display:grid; grid-template-columns:minmax(0, 1fr) 56px 320px 240px; gap:12px; align-items:center;">
    <input
      class="input"
      type="text"
      name="q"
      value="{{ $query }}"
      placeholder="ค้นหาจากกิจกรรม เกษตรกร แปลง หรือหมายเหตุ"
      aria-label="ค้นหางานตรวจวันนี้"
    >

    <button class="search-btn" type="submit" aria-label="ค้นหา">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="11" cy="11" r="7"></circle>
        <path d="M20 20l-3.5-3.5"></path>
      </svg>
    </button>

    <select class="input" name="category" aria-label="กรองตามหมวดงาน">
      <option value="all" @selected($category === 'all' || $category === '')>ทุกหมวด</option>
      <option value="prep" @selected($category === 'prep')>เตรียมดิน</option>
      <option value="water" @selected($category === 'water')>จัดการน้ำ</option>
      <option value="fertilizer" @selected($category === 'fertilizer')>หว่านปุ๋ย</option>
      <option value="pest" @selected($category === 'pest')>ศัตรูพืช</option>
      <option value="disease" @selected($category === 'disease')>โรคพืช</option>
      <option value="harvest" @selected($category === 'harvest')>เก็บเกี่ยว</option>
      <option value="mill" @selected($category === 'mill')>ขายเข้าโรงสี</option>
      <option value="document" @selected($category === 'document')>เอกสาร</option>
      <option value="general" @selected($category === 'general')>ทั่วไป</option>
    </select>

    <select class="input" name="status" aria-label="กรองตามสถานะ">
      <option value="" @selected($status === '')>ทุกสถานะ</option>
      <option value="pending_review" @selected($status === 'pending_review')>รอตรวจสอบ</option>
      <option value="in_progress" @selected($status === 'in_progress')>กำลังตรวจ</option>
      <option value="needs_fix" @selected($status === 'needs_fix')>ต้องแก้ไข</option>
      <option value="passed" @selected($status === 'passed')>ผ่านแล้ว</option>
      <option value="failed" @selected($status === 'failed')>ไม่ผ่าน</option>
    </select>
  </div>
</form>

<div class="card activity-page">
  <div class="card-head">
    <div>
      <h3>กิจกรรมล่าสุด</h3>
      <p class="muted" style="margin-top: 6px;">รายการกิจกรรมที่มีการอัปเดตล่าสุด เรียงจากใหม่ไปเก่า</p>
    </div>
    <span class="muted">แสดง {{ $activities->count() }} รายการล่าสุด</span>
  </div>

  <div class="activity-timeline">
    @forelse ($activities as $activity)
      <div class="activity-row">
        <span class="activity-time">{{ $activity['time'] }}</span>
        <div class="activity-content">
          <div class="activity-title">{{ $activity['title'] }}</div>
          <div class="muted">{{ $activity['subtitle'] }}</div>
          <div class="muted" style="margin-top: 6px;">วันที่อัปเดต: {{ $activity['date_label'] }} • หมวดงาน: {{ $activity['category_label'] }}</div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; justify-content: flex-end;">
          <span class="activity-tag {{ $activity['tag_class'] }}">{{ $activity['tag_label'] }}</span>
          <a class="btn btn-secondary" href="{{ $activity['detail_url'] }}">{{ $activity['detail_label'] }}</a>
        </div>
      </div>
    @empty
      <div class="activity-row">
        <span class="activity-time">-</span>
        <div class="activity-content">
          <div class="activity-title">ยังไม่พบกิจกรรมตามเงื่อนไขที่เลือก</div>
          <div class="muted">ลองเปลี่ยนคำค้นหา หมวดงาน หรือสถานะ เพื่อดูรายการเพิ่มเติม</div>
        </div>
      </div>
    @endforelse
  </div>
</div>
</div>
@endsection
