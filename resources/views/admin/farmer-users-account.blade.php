@extends('admin.layout')
@section('title', 'สร้างบัญชีผู้ใช้งาน')
@section('content')
<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="/admin/farmer-users/create" aria-label="กลับไปหน้าสร้างผู้ใช้งาน">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>สร้างบัญชีผู้ใช้งาน</h1>
      <p class="muted">ข้อมูลชุดนี้จะถูกบันทึกลงตาราง users พร้อมเชื่อมกับโปรไฟล์เกษตรกร</p>
    </div>
  </div>
</div>

<style>
  form label {
    display: block;
    line-height: 1.55;
  }

  form label > input,
  form label > select,
  form label > textarea {
    display: block;
    margin-top: 8px;
  }

  .required-star {
    color: #dc2626;
    margin-left: 4px;
    font-weight: 700;
  }
</style>

@if ($errors->any())
  <div class="card" style="margin-top: 16px; border-color: #fca5a5; background: #fff1f2;">
    <strong>บันทึกข้อมูลไม่สำเร็จ</strong>
    <ul style="margin: 8px 0 0 18px;">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="card" style="margin-top: 16px; border-color: #bfdbfe; background: #eff6ff;">
  <strong>ข้อมูลสำหรับเข้าแอพ</strong>
  <div class="muted" style="margin-top: 8px;">
    ผู้ใช้สามารถล็อกอินเข้าแอพด้วย <strong>ชื่อผู้ใช้</strong> ที่ตั้งในหน้านี้
    และระบบรองรับ <strong>รหัสทะเบียนเกษตรกร</strong> เป็นตัวเลือกในการล็อกอินด้วยเช่นกัน
    โดยใช้รหัสผ่านเดียวกัน
  </div>
</div>

<form method="POST" action="/admin/farmer-users/account" autocomplete="off">
  @csrf
  <div class="card" style="margin-top: 16px;">
    <h3>กำหนดข้อมูลบัญชีผู้ใช้งาน</h3>
    <div class="form-grid">
      <label>ชื่อผู้ใช้
        <input class="input" name="username" type="text" placeholder="กรอกชื่อผู้ใช้" value="{{ old('username') }}" autocomplete="new-password" autocapitalize="none" autocorrect="off" spellcheck="false">
      </label>
      <label>บทบาท
        <input class="input" type="text" value="เกษตรกร" disabled>
        <input type="hidden" name="role" value="FARMER">
      </label>
      <label>รหัสผ่าน
        <input class="input" name="password" type="password" placeholder="กรอกรหัสผ่าน" value="" autocomplete="new-password">
      </label>
      <label>ยืนยันรหัสผ่าน
        <input class="input" name="password_confirmation" type="password" placeholder="กรอกรหัสผ่านอีกครั้ง" value="" autocomplete="new-password">
      </label>
    </div>
  </div>

  <div class="footer-actions" style="margin-top: 20px;">
    <button class="btn primary" type="submit">บันทึกข้อมูล</button>
    <a href="/admin/farmer-users" class="btn ghost">ยกเลิก</a>
  </div>
</form>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form label').forEach(function (label) {
      if (label.querySelector('.required-star')) {
        return;
      }

      const firstField = label.querySelector('input, select, textarea');
      if (!firstField) {
        return;
      }

      const star = document.createElement('span');
      star.className = 'required-star';
      star.textContent = '*';
      label.insertBefore(star, firstField);
    });
  });
</script>
@endsection
