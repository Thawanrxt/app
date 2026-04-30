@extends('admin.layout')
@section('title', 'ดูข้อมูลผู้ดูแลระบบ')
@section('content')
<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="/admin/admin-users" aria-label="กลับไปหน้าผู้ดูแลระบบ">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>ดูข้อมูลผู้ดูแลระบบ</h1>
      <p class="muted">รายละเอียดบัญชีและขอบเขตการดูแลของแอดมิน</p>
    </div>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>ข้อมูลบัญชีผู้ดูแล</h3>
  <div class="form-grid">
    <label>ชื่อที่แสดง
      <input class="input" type="text" value="{{ $userRecord->display_name ?: '-' }}" disabled>
    </label>
    <label>ชื่อผู้ใช้
      <input class="input" type="text" value="{{ $userRecord->username ?: '-' }}" disabled>
    </label>
    <label>เบอร์โทรศัพท์
      <input class="input" type="text" value="{{ $userRecord->phone ?: '-' }}" disabled>
    </label>
    <label>บทบาท
      <input class="input" type="text" value="{{ $userRecord->role ?: '-' }}" disabled>
    </label>
    <label>ตำแหน่งแอดมิน
      <input class="input" type="text" value="{{ $userRecord->admin_title ?: '-' }}" disabled>
    </label>
    <label>ขอบเขตการดูแล
      <input class="input" type="text" value="{{ $userRecord->scope_label ?: collect([$userRecord->scope_province, $userRecord->scope_district, $userRecord->scope_subdistrict])->filter()->implode(' / ') ?: '-' }}" disabled>
    </label>
  </div>
</div>

<div class="footer-actions" style="margin-top: 20px;">
  <a href="/admin/admin-users/{{ $userRecord->id }}/edit" class="btn primary">แก้ไขข้อมูล</a>
  <a href="/admin/admin-users" class="btn ghost">กลับ</a>
</div>
@endsection
