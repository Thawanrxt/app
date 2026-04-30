@extends('admin.layout')
@section('title', 'รายละเอียดรายงานปัญหาการปลูกข้าว')
@section('content')
@php
  $statusLabel = match ($activity->status) {
    'passed' => 'ผ่านแล้ว',
    'needs_fix' => 'ต้องแก้ไข',
    'failed' => 'ไม่ผ่าน',
    default => 'รอตรวจสอบ',
  };

  $statusClass = match ($activity->status) {
    'passed' => 'success',
    'needs_fix', 'failed' => 'danger',
    default => 'warning',
  };
@endphp

<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="{{ $backUrl }}" aria-label="กลับไปหน้ารายงานปัญหาการปลูกข้าว">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>รายละเอียดรายงานปัญหาการปลูกข้าว</h1>
    </div>
  </div>
</div>

<div class="detail-layout" style="margin-top:16px;">
  <div class="card">
    <div class="detail-header">
      <div class="detail-meta"><span class="detail-label">ชื่อเกษตรกร :</span>{{ $activity->farmer_name }}</div>
      <span class="chip {{ $statusClass }}">{{ $statusLabel }}</span>
    </div>

    <div class="detail-line"><span class="detail-label">กิจกรรม :</span>{{ $activityLabel }}</div>
    <div class="detail-line"><span class="detail-label">ชื่อแปลง :</span>{{ $activity->plot_code }}</div>
    <div class="detail-line"><span class="detail-label">รหัสแปลง :</span>{{ $activity->plot_reference ?: '-' }}</div>
    <div class="detail-line"><span class="detail-label">รอบที่ :</span>{{ $activity->round_number ?: '-' }}</div>
    <div class="detail-line"><span class="detail-label">วันที่รายงาน :</span>{{ optional($activity->activity_date)->translatedFormat('d M Y') ?: '-' }}</div>

    <div class="detail-title">รายละเอียดปัญหา</div>
    <div class="detail-note"><span class="detail-label">ปัญหาที่พบ :</span>{{ $activity->issue_found ?: '-' }}</div>
    <div class="detail-line"><span class="detail-label">ข้อมูลเพิ่มเติม :</span>{{ $activity->details ?: '-' }}</div>

    @if ($activity->image_url)
      <img class="detail-image" src="{{ $activity->image_url }}" alt="รูปปัญหาในแปลง">
    @else
      <div class="upload-preview" style="margin-top:12px;">ยังไม่มีรูปภาพประกอบจากฝั่งยูสเซอร์</div>
    @endif
  </div>

  <div class="detail-side">
    <div class="card">
      <h3>ทางลัด</h3>
      <p class="muted" style="margin-top:8px;">ถ้าต้องการจัดการสถานะหรือส่งคำแนะนำ สามารถไปต่อที่หน้าติดตามของกิจกรรมนี้ได้ทันที</p>
      <a class="btn primary" href="{{ $sourceDetailUrl }}" style="margin-top:10px;">ไปหน้าติดตามกิจกรรม</a>
    </div>
  </div>
</div>
@endsection
