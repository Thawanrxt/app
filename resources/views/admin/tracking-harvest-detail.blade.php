@extends('admin.layout')
@section('title', 'รายละเอียดการเก็บเกี่ยว')
@section('content')
@php
  $statusOptions = [
    'pending_review' => 'รอตรวจสอบ',
    'passed' => 'ผ่าน',
    'needs_fix' => 'ต้องแก้ไข',
    'failed' => 'ไม่ผ่าน',
  ];
@endphp

<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="/admin/tracking/harvest" aria-label="กลับไปหน้าการเก็บเกี่ยว">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>รายละเอียดการเก็บเกี่ยว</h1>
    </div>
  </div>
</div>

<div class="detail-layout" style="margin-top:16px;">
  <div class="card">
    <div class="detail-header">
      <div class="detail-meta"><span class="detail-label">ชื่อแปลง :</span>{{ $activity->plot_code }}</div>
      <span class="chip {{ $activity->status === 'passed' ? 'success' : ($activity->status === 'needs_fix' || $activity->status === 'failed' ? 'danger' : 'warning') }}">
        {{ $statusOptions[$activity->status] ?? 'รอตรวจสอบ' }}
      </span>
    </div>
    <div class="detail-line"><span class="detail-label">กิจกรรม :</span>{{ $activity->activity_name }}</div>
    <div class="detail-title">รายละเอียด</div>
    <div class="detail-line"><span class="detail-label">ผู้ที่ทำกิจกรรม :</span>{{ $activity->farmer_name }}</div>
    <div class="detail-line"><span class="detail-label">วันที่เริ่มเก็บเกี่ยว :</span>{{ optional($activity->started_at)->translatedFormat('d M Y') ?: '-' }}</div>
    <div class="detail-line"><span class="detail-label">วันที่สิ้นสุดเก็บเกี่ยว :</span>{{ optional($activity->ended_at)->translatedFormat('d M Y') ?: '-' }}</div>
    <div class="detail-line"><span class="detail-label">ผลผลิตรวม :</span>{{ $activity->yield_amount_kg ? number_format($activity->yield_amount_kg, 0) . ' กก.' : '-' }}</div>
    <div class="detail-line"><span class="detail-label">ความชื้น :</span>{{ $activity->moisture_percent !== null ? rtrim(rtrim(number_format($activity->moisture_percent, 2), '0'), '.') . '%' : '-' }}</div>
    <div class="detail-line"><span class="detail-label">รายละเอียดเพิ่มเติม :</span>{{ $activity->details ?: '-' }}</div>
    <div class="detail-note"><span class="detail-label">ปัญหาที่พบ :</span>{{ $activity->issue_found ?: 'ไม่มี' }}</div>
    @if ($activity->image_url)
      <img class="detail-image" src="{{ $activity->image_url }}" alt="รูปการเก็บเกี่ยว">
    @else
      <div class="upload-preview" style="margin-top: 12px;">ยังไม่มีรูปภาพประกอบจากฝั่งยูสเซอร์</div>
    @endif
  </div>
  <div class="detail-side">
    <div class="card">
      <h3>Admin</h3>
      @if (session('success'))
        <div class="status-banner success" style="margin-top: 10px;">{{ session('success') }}</div>
      @endif
      <form method="POST" action="/admin/tracking/harvest/{{ $activity->id }}/status" style="margin-top:10px;">
        @csrf
        @foreach ($statusOptions as $statusValue => $statusLabel)
          <label style="display:block; margin-top:8px;">
            <input type="radio" name="status" value="{{ $statusValue }}" @checked($activity->status === $statusValue)>
            {{ $statusLabel }}
          </label>
        @endforeach
        <textarea class="input" name="admin_note" rows="4" style="margin-top:10px;" placeholder="หมายเหตุจากแอดมิน">{{ old('admin_note', $activity->admin_note) }}</textarea>
        <button class="btn primary" type="submit" style="margin-top:10px;">บันทึกสถานะ</button>
      </form>
    </div>
    @include('admin.partials.tracking-advice-card')
  </div>
</div>
@endsection
