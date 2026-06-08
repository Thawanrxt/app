@extends('admin.layout')
@section('title', 'รายละเอียดรายงานปัญหาการใช้งานระบบ')
@section('content')
@if (session('success'))
  <div class="card" style="margin-bottom:16px; border-color:#86efac; background:#f0fdf4; color:#166534; padding:12px 16px;">
    ✓ {{ session('success') }}
  </div>
@endif
@if ($errors->any())
  <div class="card" style="margin-bottom:16px; border-color:#fca5a5; background:#fff1f2;">
    <strong>เกิดข้อผิดพลาด</strong>
    <ul style="margin:8px 0 0 18px;">
      @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
  </div>
@endif
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

  {{-- คำแนะนำที่เคยส่งแล้ว --}}
  @if ($adminReply)
    <div class="card" style="margin-top:16px; border-color:#86efac; background:#f0fdf4;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <h3 style="color:#166534; margin:0;">✓ คำแนะนำที่ส่งไปแล้ว</h3>
        @if ($adminReplyAt)
          <span class="muted" style="font-size:13px;">{{ \Carbon\Carbon::parse($adminReplyAt)->locale('th')->translatedFormat('d M Y H:i') }}</span>
        @endif
      </div>
      <div style="white-space:pre-wrap; color:#166534;">{{ $adminReply }}</div>
    </div>
  @endif

  {{-- ฟอร์มส่งคำแนะนำ --}}
  <div class="card" style="margin-top:16px;">
    <h3 style="margin-bottom:16px;">
      {{ $adminReply ? 'แก้ไขคำแนะนำ / ส่งใหม่' : 'ส่งคำแนะนำให้ผู้ใช้งาน' }}
    </h3>
    <p class="muted" style="margin-bottom:16px;">คำแนะนำจะถูกส่งเป็น <strong>การแจ้งเตือน</strong> ไปยังแอพของผู้ใช้ทันที</p>

    <form method="POST" action="/admin/report/system/{{ $ticket->id }}/reply">
      @csrf
      <div style="display:flex; flex-direction:column; gap:14px;">
        <label>คำแนะนำ / คำตอบกลับ <span style="color:#dc2626;">*</span>
          <textarea class="input" name="admin_reply" rows="5"
                    placeholder="พิมพ์คำแนะนำหรือวิธีแก้ปัญหาให้ผู้ใช้งาน..."
                    required style="margin-top:6px; width:100%; resize:vertical;">{{ old('admin_reply', $adminReply) }}</textarea>
        </label>

        <label style="max-width:280px;">เปลี่ยนสถานะ <span style="color:#dc2626;">*</span>
          <select class="input" name="new_status" style="margin-top:6px;">
            <option value="IN_PROGRESS" {{ old('new_status') === 'IN_PROGRESS' ? 'selected' : '' }}>กำลังดำเนินการ</option>
            <option value="RESOLVED"   {{ old('new_status', 'RESOLVED') === 'RESOLVED' ? 'selected' : '' }}>เสร็จสิ้นแล้ว</option>
            <option value="CLOSED"     {{ old('new_status') === 'CLOSED' ? 'selected' : '' }}>ปิดเคส</option>
          </select>
        </label>
      </div>

      <div class="footer-actions" style="margin-top:16px;">
        <button class="btn primary" type="submit">
          📨 ส่งคำแนะนำ + แจ้งเตือนผู้ใช้
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
