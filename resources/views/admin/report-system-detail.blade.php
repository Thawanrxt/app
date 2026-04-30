@extends('admin.layout')
@section('title', 'รายละเอียดรายงานปัญหาการใช้งานระบบ')
@section('content')
<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="{{ $backUrl }}" aria-label="กลับไปหน้ารายงานปัญหาการใช้งานระบบ">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>รายละเอียดรายงานปัญหาการใช้งานระบบ</h1>
      <p class="muted">ตรวจสอบข้อมูลคำร้องที่ผู้ใช้งานแจ้งเข้ามาในระบบ</p>
    </div>
  </div>
</div>

<div class="detail-layout" style="margin-top:16px;">
  <div class="card">
    <div class="detail-header-row">
      <div>
        <h2>{{ $ticket->subject ?: 'ไม่ระบุหัวข้อ' }}</h2>
        <div class="muted">เลขที่รายการ: {{ $ticket->id }}</div>
      </div>
      <span class="status-pill {{ $ticket->status_class }}">{{ $ticket->status }}</span>
    </div>

    <div class="detail-grid" style="margin-top: 16px;">
      <div class="detail-line"><span class="detail-label">ผู้รายงาน:</span>{{ $ticket->reporter_name }}</div>
      <div class="detail-line"><span class="detail-label">วันที่แจ้ง:</span>{{ $ticket->formatted_date }}</div>
      <div class="detail-line"><span class="detail-label">อีเมลติดต่อ:</span>{{ $ticket->contact_email ?: '-' }}</div>
      <div class="detail-line"><span class="detail-label">เบอร์โทรติดต่อ:</span>{{ $ticket->contact_phone ?: '-' }}</div>
      <div class="detail-line"><span class="detail-label">สถานะต้นทาง:</span>{{ $ticket->source_status ?: '-' }}</div>
      <div class="detail-line"><span class="detail-label">รหัสผู้ใช้:</span>{{ $ticket->user_id ?: '-' }}</div>
    </div>

    <div class="card-section" style="margin-top: 18px;">
      <h3>รายละเอียดปัญหา</h3>
      <div class="detail-message-box">
        {{ $ticket->message ?: 'ไม่มีรายละเอียดเพิ่มเติม' }}
      </div>
    </div>
  </div>
</div>
@endsection
