@extends('admin.layout')
@section('title', 'รายละเอียดการขายข้าวเข้าโรงสี')
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
    <a class="back-link icon-only" href="/admin/tracking/mill" aria-label="กลับไปหน้าขายข้าวเข้าโรงสี">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>รายละเอียดการขายข้าวเข้าโรงสี</h1>
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
    <div class="grid-3 detail-grid">
      <div class="detail-line"><span class="detail-label">เกษตรกร :</span>{{ $activity->farmer_name }}</div>
      <div class="detail-line"><span class="detail-label">โรงสี :</span>{{ $activity->mill_name ?: '-' }}</div>
      <div class="detail-line"><span class="detail-label">วันที่ :</span>{{ optional($activity->activity_date)->translatedFormat('d M Y') }}</div>
      <div class="detail-line"><span class="detail-label">คิว :</span>{{ $activity->queue_number ?: '-' }}</div>
      <div class="detail-line"><span class="detail-label">เลขที่เอกสาร :</span>{{ $activity->document_number ?: '-' }}</div>
      <div class="detail-line"><span class="detail-label">สินค้า :</span>{{ $activity->product_name ?: '-' }}</div>
      <div class="detail-line"><span class="detail-label">ทะเบียนรถ :</span>{{ $activity->vehicle_plate ?: '-' }}</div>
      <div class="detail-line"><span class="detail-label">เวลาเข้า :</span>{{ $activity->time_in ?: '-' }}</div>
      <div class="detail-line"><span class="detail-label">เวลาออก :</span>{{ $activity->time_out ?: '-' }}</div>
      <div class="detail-line"><span class="detail-label">น้ำหนักก่อนสี :</span>{{ $activity->pre_mill_weight_kg !== null ? number_format($activity->pre_mill_weight_kg, 0) . ' กก.' : '-' }}</div>
      <div class="detail-line"><span class="detail-label">น้ำหนักหลังสี :</span>{{ $activity->post_mill_weight_kg !== null ? number_format($activity->post_mill_weight_kg, 0) . ' กก.' : '-' }}</div>
      <div class="detail-line"><span class="detail-label">น้ำหนักสุทธิ :</span>{{ $activity->net_weight_kg !== null ? number_format($activity->net_weight_kg, 0) . ' กก.' : '-' }}</div>
      <div class="detail-line"><span class="detail-label">ราคาต่อกก. :</span>{{ $activity->price_per_kg !== null ? number_format($activity->price_per_kg, 2) . ' บาท' : '-' }}</div>
      <div class="detail-line"><span class="detail-label">รวมรายได้ :</span>{{ $activity->total_income !== null ? number_format($activity->total_income, 2) . ' บาท' : '-' }}</div>
      <div class="detail-line"><span class="detail-label">รายละเอียดเพิ่มเติม :</span>{{ $activity->details ?: '-' }}</div>
    </div>
    <div class="detail-note" style="margin-top: 12px;"><span class="detail-label">ปัญหาที่พบ :</span>{{ $activity->issue_found ?: 'ไม่มี' }}</div>
    @if ($activity->image_url)
      <img class="detail-image" src="{{ $activity->image_url }}" alt="รูปการขายข้าวเข้าโรงสี">
    @else
      <div class="upload-preview" style="margin-top: 12px;">ยังไม่มีรูปภาพหรือไฟล์ประกอบจากฝั่งยูสเซอร์</div>
    @endif
  </div>
  <div class="detail-side">
    <div class="card">
      <h3>Admin</h3>
      @if (session('success'))
        <div class="status-banner success" style="margin-top: 10px;">{{ session('success') }}</div>
      @endif
      <form method="POST" action="/admin/tracking/mill/{{ $activity->id }}/status" style="margin-top:10px;">
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
