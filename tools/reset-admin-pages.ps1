$files = @{}

$layout = @'
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'SRP Admin')</title>
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-avatar">A</div>
      <div class="sidebar-title">SRP ADMIN</div>
    </div>
    <div class="sidebar-divider"></div>
    <nav class="sidebar-nav">
      <a href="/admin" class="sidebar-link">แดชบอร์ด</a>
      <a href="/admin/users" class="sidebar-link">ผู้ใช้งาน</a>

      <details class="sidebar-group" open>
        <summary class="sidebar-link">ข้อมูลการติดตาม</summary>
        <div class="sidebar-sub">
          <a href="/admin/tracking/prep" class="sidebar-sublink">การเตรียมดิน</a>
          <a href="/admin/tracking/water" class="sidebar-sublink">การจัดการน้ำ</a>
          <a href="/admin/tracking/fertilizer" class="sidebar-sublink">หว่านปุ๋ย</a>
          <a href="/admin/tracking/pest" class="sidebar-sublink">การจัดการศัตรูพืช</a>
          <a href="/admin/tracking/disease" class="sidebar-sublink">การจัดการโรคพืช</a>
          <a href="/admin/tracking/harvest" class="sidebar-sublink">การเก็บเกี่ยว</a>
          <a href="/admin/tracking/mill" class="sidebar-sublink">ขายข้าวเข้าโรงสี</a>
        </div>
      </details>

      <a href="/admin/srp" class="sidebar-link">คู่มือมาตรฐาน SRP</a>
      <a href="/admin/rice" class="sidebar-link">พันธุ์ข้าว</a>

      <details class="sidebar-group" open>
        <summary class="sidebar-link">รายงานปัญหา</summary>
        <div class="sidebar-sub">
          <a href="/admin/report/rice" class="sidebar-sublink">การปลูกข้าว</a>
          <a href="/admin/report/system" class="sidebar-sublink">การใช้งานระบบ</a>
          <a href="/admin/report/rice-risk" class="sidebar-sublink">แปลงเสี่ยง/ไม่ผ่านมาตรฐาน</a>
        </div>
      </details>

      <a href="/admin/settings" class="sidebar-link">ตั้งค่า</a>
    </nav>
  </aside>
  <main class="content">
    @yield('content')
  </main>
</div>
</body>
</html>
'@

$dashboard = @'
@extends('admin.layout')
@section('title', 'แดชบอร์ด')
@section('content')
<div class="page-head">
  <div>
    <h1>แดชบอร์ด</h1>
    <p class="muted">อัปเดตล่าสุด: 23 มี.ค. 2026 • 10:42 น.</p>
  </div>
  <button class="btn ghost">ส่งออกรีพอร์ต</button>
</div>

<div class="grid-4" style="margin-top:16px;">
  <div class="card"><h3>เกษตรกร</h3><p class="big">78 คน</p></div>
  <div class="card"><h3>พื้นที่รวม</h3><p class="big">70 ไร่</p></div>
  <div class="card"><h3>พันธุ์ข้าว</h3><p class="big">5 พันธุ์</p></div>
  <div class="card"><h3>การประเมิน SRP</h3><p class="big">82%</p></div>
</div>

<div class="card" style="margin-top:16px;">
  <h3>แจ้งเตือนล่าสุด</h3>
  <ul>
    <li>แปลง SM1/2345 อัปเดตการเตรียมดินแล้ว</li>
    <li>รายงานปัญหาใหม่ 3 รายการ</li>
    <li>แปลง SO/2504 สถานะรอตรวจสอบ</li>
  </ul>
</div>
@endsection
'@

$users = @'
@extends('admin.layout')
@section('title', 'ผู้ใช้งาน')
@section('content')
<div class="page-head">
  <div>
    <h1>ผู้ใช้งาน</h1>
    <p class="muted">จัดการผู้ใช้งานในระบบ</p>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <div class="search-row" style="display:flex; gap:12px; align-items:center;">
    <input class="search-input" type="text" placeholder="ค้นหา">
    <a class="btn primary" href="/admin/users/create">เพิ่มผู้ใช้งาน</a>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>รายการผู้ใช้งาน</h3>
  <table class="table">
    <thead>
      <tr>
        <th>ชื่อ</th>
        <th>อีเมล</th>
        <th>ตำแหน่ง</th>
        <th>วันที่เพิ่ม</th>
        <th>สถานะ</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="5" class="muted" style="text-align:center; padding:24px;">ยังไม่มีผู้ใช้งานในระบบ</td>
      </tr>
    </tbody>
  </table>
</div>
@endsection
'@

$usersCreate = @'
@extends('admin.layout')
@section('title', 'เพิ่มผู้ใช้งาน')
@section('content')
<div class="page-head" style="display:flex; align-items:center; gap:10px;">
  <a href="/admin/users" class="btn ghost">ย้อนกลับ</a>
  <div>
    <h1>เพิ่มผู้ใช้งาน</h1>
    <p class="muted">กรอกข้อมูลเกษตรกรและข้อมูลแปลง</p>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>ข้อมูลส่วนตัวเกษตรกร</h3>
  <div class="form-grid">
    <label>ชื่อ-นามสกุล <input class="input" type="text" placeholder="กรอกข้อมูล"></label>
    <label>เลขประจำตัวประชาชน <input class="input" type="text" placeholder="กรอกข้อมูล"></label>
    <label>เบอร์โทร <input class="input" type="text" placeholder="กรอกข้อมูล"></label>
    <label>วัน/เดือน/ปีเกิด <input class="input" type="date"></label>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>ที่อยู่ตามทะเบียนบ้าน</h3>
  <div class="form-grid">
    <label>บ้านเลขที่/ซอย/หมู่/ถนน <input class="input" type="text" placeholder="บ้านเลขที่, ซอย, หมู่, ถนน"></label>
    <label>จังหวัด <select class="input"><option>เลือกจังหวัด</option></select></label>
    <label>เขต/อำเภอ <select class="input"><option>เลือกเขต/อำเภอ</option></select></label>
    <label>แขวง/ตำบล <select class="input"><option>เลือกแขวง/ตำบล</option></select></label>
    <label>รหัสไปรษณีย์ <select class="input"><option>เลือกรหัสไปรษณีย์</option></select></label>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>ข้อมูลแปลงเกษตรกร</h3>
  <div class="form-grid">
    <label>จังหวัด <select class="input"><option>เลือกจังหวัด</option></select></label>
    <label>พื้นที่เพาะปลูก (ไร่/ตารางวา)
      <div style="display:flex; gap:8px;">
        <input class="input" type="text" placeholder="ไร่">
        <input class="input" type="text" placeholder="ตารางวา">
      </div>
    </label>
    <label>ประเภทพืชที่ปลูก <select class="input"><option>เลือกประเภทพืช</option></select></label>
  </div>
</div>

<div class="footer-actions" style="margin-top: 20px;">
  <a href="/admin/users/account" class="btn primary" style="min-width:160px; text-align:center;">ถัดไป</a>
</div>
@endsection
'@

$usersAccount = @'
@extends('admin.layout')
@section('title', 'สร้างบัญชีผู้ใช้')
@section('content')
<div class="page-head" style="display:flex; align-items:center; gap:10px;">
  <a href="/admin/users/create" class="btn ghost">ย้อนกลับ</a>
  <div>
    <h1>สร้างบัญชีผู้ใช้</h1>
    <p class="muted">ตั้งค่าบัญชีสำหรับการเข้าใช้งาน</p>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>ตั้งค่าบัญชีผู้ใช้</h3>
  <div class="form-grid">
    <label>ชื่อผู้ใช้ <input class="input" type="text" placeholder="กรอกข้อมูล"></label>
    <label>รหัสผ่าน <input class="input" type="password" placeholder="กรอกข้อมูล"></label>
    <label>ยืนยันรหัสผ่าน <input class="input" type="password" placeholder="กรอกข้อมูล"></label>
  </div>
</div>

<div class="footer-actions" style="margin-top: 20px;">
  <button class="btn primary">บันทึกข้อมูล</button>
  <a href="/admin/users" class="btn ghost">ยกเลิก</a>
</div>
@endsection
'@

$rice = @'
@extends('admin.layout')
@section('title', 'พันธุ์ข้าว')
@section('content')
<div class="page-head">
  <div>
    <h1>พันธุ์ข้าว</h1>
    <p class="muted">จัดการข้อมูลพันธุ์ข้าวในระบบ</p>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <div class="search-row" style="display:flex; gap:12px; align-items:center;">
    <input class="search-input" type="text" placeholder="ค้นหา">
    <a class="btn primary" href="/admin/rice/create">เพิ่มพันธุ์ข้าว</a>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>พันธุ์ข้าว</h3>
  <table class="table">
    <thead>
      <tr>
        <th>ประเภทข้าว</th>
        <th>พันธุ์ข้าว</th>
        <th>ระยะเวลามาตรฐาน</th>
        <th>ความต้านทานโรค</th>
        <th>ความต้านทานต่อศัตรูพืช</th>
        <th>แก้ไข</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="6" class="muted" style="text-align:center; padding:24px;">ยังไม่มีข้อมูลพันธุ์ข้าว</td>
      </tr>
    </tbody>
  </table>
</div>
@endsection
'@

$riceCreate = @'
@extends('admin.layout')
@section('title', 'เพิ่มพันธุ์ข้าว')
@section('content')
<div class="page-head" style="display:flex; align-items:center; gap:10px;">
  <a href="/admin/rice" class="btn ghost">ย้อนกลับ</a>
  <div>
    <h1>เพิ่มพันธุ์ข้าว</h1>
    <p class="muted">กรอกข้อมูลพันธุ์ข้าวที่ต้องการเพิ่ม</p>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <div class="form-grid">
    <label>ประเภทข้าว <input class="input" type="text" placeholder="กรอกข้อมูล"></label>
    <label>ชื่อพันธุ์ข้าว <input class="input" type="text" placeholder="กรอกข้อมูล"></label>
    <label>ระยะเวลาในการเจริญเติบโต (วัน) <input class="input" type="text" placeholder="จำนวนวัน"></label>
    <label>ความต้านทานโรคข้าว <input class="input" type="text" placeholder="กรอกข้อมูล"></label>
    <label>ความต้านทานต่อศัตรูพืช <input class="input" type="text" placeholder="กรอกข้อมูล"></label>
  </div>
</div>

<div class="footer-actions" style="margin-top: 20px;">
  <button class="btn primary">บันทึกข้อมูล</button>
  <a href="/admin/rice" class="btn ghost">ยกเลิก</a>
</div>
@endsection
'@

$srp = @'
@extends('admin.layout')
@section('title', 'คู่มือมาตรฐาน SRP')
@section('content')
<div class="page-head" style="display:flex; justify-content: space-between; align-items:center;">
  <div>
    <h1>มาตรฐาน SRP</h1>
    <p class="muted">แนวทางการปลูกข้าวอย่างยั่งยืน (Sustainable Rice)</p>
  </div>
  <a class="btn ghost" href="https://e-book.acfs.go.th/Book_view/346" target="_blank" rel="noopener">อ่านรายละเอียดมาตรฐาน SRP</a>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>ภาพรวมมาตรฐาน SRP</h3>
  <p>มาตรฐาน SRP (Sustainable Rice Platform) เป็นแนวทางการปลูกข้าวอย่างยั่งยืน ครอบคลุมด้านความปลอดภัยอาหาร เศรษฐกิจ สังคม และสิ่งแวดล้อม</p>
  <span class="tag">อิงจากมาตรฐานสากล + FAO + UNEP</span>
</div>
@endsection
'@

$settings = @'
@extends('admin.layout')
@section('title', 'การตั้งค่า')
@section('content')
<div class="page-head">
  <div>
    <h1>การตั้งค่า</h1>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>เลือกธีม</h3>
  <div style="display:flex; gap:16px; align-items:center;">
    <label style="display:flex; align-items:center; gap:10px;">
      <input type="radio" name="theme" checked>
      <div style="width:220px; height:80px; border-radius:12px; border:1px solid #e5e7eb; background:#fff;"></div>
    </label>
    <label style="display:flex; align-items:center; gap:10px;">
      <input type="radio" name="theme">
      <div style="width:220px; height:80px; border-radius:12px; border:1px solid #e5e7eb; background:#0f172a;"></div>
    </label>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>ปรับขนาด</h3>
  <div class="form-grid">
    <label>เลือกฟอนต์ตัวอักษร
      <select class="input">
        <option>Prompt</option>
        <option>Sarabun</option>
      </select>
    </label>
    <label>ขนาดตัวอักษร
      <select class="input">
        <option>16</option>
        <option>18</option>
        <option>20</option>
      </select>
    </label>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>เปลี่ยนภาษา</h3>
  <label>ภาษา
    <select class="input">
      <option>ภาษาไทย</option>
      <option>English</option>
    </select>
  </label>
</div>

<div class="footer-actions" style="margin-top: 20px;">
  <button class="btn primary">บันทึก</button>
</div>
@endsection
'@

$trackingTemplate = @'
@extends('admin.layout')
@section('title', '@@TITLE@@')
@section('content')
<div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
  <div>
    <h1>@@TITLE@@</h1>
  </div>
  <button class="btn primary">Export PDF</button>
</div>

<div class="card" style="margin-top: 16px;">
  <div class="search-row" style="display:flex; gap:12px; align-items:center;">
    <input class="search-input" type="text" placeholder="ค้นหา">
    <select class="input"><option>กิจกรรม</option></select>
    <select class="input"><option>สถานะ</option></select>
    <input class="input" type="date">
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <table class="table">
    <thead>
      <tr>
        <th>เกษตรกร</th>
        <th>ชื่อแปลง</th>
        <th>กิจกรรม</th>
        <th>วันที่</th>
        <th>สถานะ</th>
        <th>ดำเนินการ</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>สมศักดิ์ สุขสวย</td>
        <td>SS/1205</td>
        <td>@@ACTIVITY@@</td>
        <td>15 มิ.ย. 2568</td>
        <td>ผ่านแล้ว</td>
        <td><a href="/admin/tracking/prep/detail">ดูรายละเอียด</a></td>
      </tr>
      <tr>
        <td>สมศรี อวยพร</td>
        <td>SO/2504</td>
        <td>@@ACTIVITY@@</td>
        <td>24 มิ.ย. 2568</td>
        <td>รอตรวจสอบ</td>
        <td><a href="/admin/tracking/prep/detail">ดูรายละเอียด</a></td>
      </tr>
    </tbody>
  </table>
</div>
@endsection
'@

$reportTemplate = @'
@extends('admin.layout')
@section('title', '@@TITLE@@')
@section('content')
<div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
  <div>
    <h1>@@TITLE@@</h1>
  </div>
  <button class="btn primary">Export PDF</button>
</div>

<div class="card" style="margin-top: 16px;">
  <div class="search-row" style="display:flex; gap:12px; align-items:center;">
    <input class="search-input" type="text" placeholder="ค้นหา">
    <select class="input"><option>กิจกรรม</option></select>
    <select class="input"><option>สถานะ</option></select>
    <input class="input" type="date">
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <table class="table">
    <thead>
      <tr>
        <th>เกษตรกร</th>
        <th>ปัญหา</th>
        <th>วันที่</th>
        <th>สถานะ</th>
        <th>ดำเนินการ</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>สมศักดิ์ สุขสวย</td>
        <td>@@PROBLEM@@</td>
        <td>15 มิ.ย. 2568</td>
        <td>กำลังดำเนินการ</td>
        <td><a href="/admin/report/rice-detail">ดูรายละเอียด</a></td>
      </tr>
    </tbody>
  </table>
</div>
@endsection
'@

$trackingDetail = @'
@extends('admin.layout')
@section('title', 'รายละเอียดการเตรียมดิน')
@section('content')
<div class="page-head" style="display:flex; align-items:center; gap:10px;">
  <a href="/admin/tracking/prep" class="btn ghost">ย้อนกลับ</a>
  <h1>รายละเอียดการเตรียมดิน</h1>
</div>

<div class="detail-layout" style="margin-top:16px;">
  <div class="card">
    <h3>ข้อมูลแปลง : SM1/2345</h3>
    <p>กิจกรรม : เตรียมดิน (ปรับ land leveling)</p>
    <p>วิธีการ : ไถดะ</p>
    <h4 style="margin-top:12px;">รายละเอียด</h4>
    <p>ผู้ที่ทำกิจกรรม : สมศักดิ์ สุขสวย</p>
    <p>วันที่ทำกิจกรรม : 10 พ.ค. 2024</p>
    <p>ผลการตรวจดิน : ค่า pH 6.5, N-P-K 10-15-10</p>
  </div>
  <div class="detail-side">
    <div class="card">
      <h3>Admin</h3>
      <label><input type="radio" name="status"> ผ่าน</label>
      <label><input type="radio" name="status"> ไม่ผ่าน</label>
      <button class="btn primary" style="margin-top:10px;">บันทึกสถานะ</button>
    </div>
    <div class="card" style="margin-top:12px;">
      <h3>คำแนะนำ</h3>
      <textarea class="input" rows="4" placeholder="ใส่รายละเอียด"></textarea>
      <button class="btn primary" style="margin-top:10px;">ส่งคำแนะนำถึงเกษตรกร</button>
    </div>
  </div>
</div>
@endsection
'@

$reportRiceDetail = @'
@extends('admin.layout')
@section('title', 'รายละเอียดรายงานการปลูกข้าว')
@section('content')
<div class="page-head" style="display:flex; align-items:center; gap:10px;">
  <a href="/admin/report/rice" class="btn ghost">ย้อนกลับ</a>
  <h1>รายละเอียดรายงานการปลูกข้าว</h1>
</div>

<div class="detail-layout" style="margin-top:16px;">
  <div class="card">
    <h3>ข้อมูลผู้รายงาน</h3>
    <p>ชื่อเกษตรกร: สมศักดิ์ สุขสวย</p>
    <p>ชื่อแปลง: SM1/2345</p>
    <p>ประเภทปัญหา: เตรียมดิน</p>
    <p>รายละเอียด: น้ำในแปลงไม่ไหล</p>
  </div>
  <div class="detail-side">
    <div class="card">
      <h3>คำแนะนำ</h3>
      <textarea class="input" rows="4" placeholder="ใส่รายละเอียด"></textarea>
      <button class="btn primary" style="margin-top:10px;">ส่งคำแนะนำถึงเกษตรกร</button>
    </div>
  </div>
</div>
@endsection
'@

$reportSystemDetail = @'
@extends('admin.layout')
@section('title', 'รายละเอียดรายงานระบบ')
@section('content')
<div class="page-head" style="display:flex; align-items:center; gap:10px;">
  <a href="/admin/report/system" class="btn ghost">ย้อนกลับ</a>
  <h1>รายละเอียดรายงานระบบ</h1>
</div>

<div class="detail-layout" style="margin-top:16px;">
  <div class="card">
    <h3>ข้อมูลผู้รายงาน</h3>
    <p>ชื่อผู้ใช้งาน: สมศักดิ์ สุขสวย</p>
    <p>วันที่รายงาน: 25 มิ.ย. 2568</p>
    <p>ประเภทปัญหา: Upload กิจกรรมไม่ได้</p>
    <p>รายละเอียด: กดส่งแล้วไม่ขึ้นสถานะ</p>
  </div>
  <div class="detail-side">
    <div class="card">
      <h3>คำแนะนำ</h3>
      <textarea class="input" rows="4" placeholder="ใส่รายละเอียด"></textarea>
      <button class="btn primary" style="margin-top:10px;">ส่งคำแนะนำถึงผู้ใช้งาน</button>
    </div>
  </div>
</div>
@endsection
'@

$files["resources/views/admin/layout.blade.php"] = $layout
$files["resources/views/admin/dashboard.blade.php"] = $dashboard
$files["resources/views/admin/users.blade.php"] = $users
$files["resources/views/admin/users-create.blade.php"] = $usersCreate
$files["resources/views/admin/users-account.blade.php"] = $usersAccount
$files["resources/views/admin/rice.blade.php"] = $rice
$files["resources/views/admin/rice-create.blade.php"] = $riceCreate
$files["resources/views/admin/srp.blade.php"] = $srp
$files["resources/views/admin/settings.blade.php"] = $settings
$files["resources/views/admin/tracking-prep.blade.php"] = $trackingTemplate.Replace('@@TITLE@@','การเตรียมดิน').Replace('@@ACTIVITY@@','เตรียมดิน')
$files["resources/views/admin/tracking-water.blade.php"] = $trackingTemplate.Replace('@@TITLE@@','การจัดการน้ำ').Replace('@@ACTIVITY@@','การใช้น้ำ')
$files["resources/views/admin/tracking-fertilizer.blade.php"] = $trackingTemplate.Replace('@@TITLE@@','หว่านปุ๋ย').Replace('@@ACTIVITY@@','หว่านปุ๋ย')
$files["resources/views/admin/tracking-pest.blade.php"] = $trackingTemplate.Replace('@@TITLE@@','การจัดการศัตรูพืช').Replace('@@ACTIVITY@@','การจัดการศัตรูพืช')
$files["resources/views/admin/tracking-disease.blade.php"] = $trackingTemplate.Replace('@@TITLE@@','การจัดการโรคพืช').Replace('@@ACTIVITY@@','การจัดการโรคพืช')
$files["resources/views/admin/tracking-harvest.blade.php"] = $trackingTemplate.Replace('@@TITLE@@','การเก็บเกี่ยว').Replace('@@ACTIVITY@@','การเก็บเกี่ยว')
$files["resources/views/admin/tracking-mill.blade.php"] = $trackingTemplate.Replace('@@TITLE@@','ขายข้าวเข้าโรงสี').Replace('@@ACTIVITY@@','ขายข้าวเข้าโรงสี')
$files["resources/views/admin/tracking-prep-detail.blade.php"] = $trackingDetail
$files["resources/views/admin/report-rice.blade.php"] = $reportTemplate.Replace('@@TITLE@@','รายงานการปลูกข้าว').Replace('@@PROBLEM@@','เตรียมดิน')
$files["resources/views/admin/report-system.blade.php"] = $reportTemplate.Replace('@@TITLE@@','รายงานปัญหาเกี่ยวกับระบบ').Replace('@@PROBLEM@@','Upload กิจกรรมไม่ได้')
$files["resources/views/admin/report-rice-risk.blade.php"] = $reportTemplate.Replace('@@TITLE@@','แปลงเสี่ยง/ไม่ผ่านมาตรฐาน').Replace('@@PROBLEM@@','ไม่ผ่านมาตรฐาน')
$files["resources/views/admin/report-rice-detail.blade.php"] = $reportRiceDetail
$files["resources/views/admin/report-system-detail.blade.php"] = $reportSystemDetail

foreach ($path in $files.Keys) {
  $content = $files[$path]
  Set-Content -Path $path -Value $content -Encoding UTF8
}

$css = @'
body {
  font-family: "Prompt", "Sarabun", sans-serif;
  background: #f3f4f6;
  color: #111827;
  margin: 0;
}
.layout {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 260px 1fr;
}
.sidebar {
  background: #1f7a4d;
  color: #fff;
  padding: 20px;
}
.sidebar-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}
.sidebar-avatar {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: #fff;
  color: #1f7a4d;
  display: grid;
  place-items: center;
  font-weight: 700;
}
.sidebar-title { font-weight: 700; letter-spacing: .5px; }
.sidebar-divider { height: 2px; background: rgba(255,255,255,.35); margin: 12px 0 16px; }
.sidebar-nav { display: flex; flex-direction: column; gap: 8px; }
.sidebar-link, .sidebar-sublink {
  color: #fff;
  text-decoration: none;
  padding: 10px 12px;
  border-radius: 10px;
  display: block;
}
.sidebar-link { background: rgba(255,255,255,.08); }
.sidebar-link:hover, .sidebar-sublink:hover { background: rgba(255,255,255,.15); }
.sidebar-group summary { list-style: none; cursor: pointer; }
.sidebar-group summary::-webkit-details-marker { display: none; }
.sidebar-group summary::after { content: "▾"; float: right; opacity: .8; }
.sidebar-sub { padding-left: 12px; display: flex; flex-direction: column; gap: 6px; }

.content { padding: 24px; }
.page-head { display:flex; justify-content: space-between; align-items: center; gap: 12px; }
.page-head h1 { margin: 0; font-size: 28px; }
.muted { color: #6b7280; }

.card {
  background: #fff;
  border-radius: 16px;
  padding: 16px;
  box-shadow: 0 12px 24px rgba(15,23,42,.08);
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  padding: 8px 16px;
  font-weight: 600;
  border: 1px solid #e5e7eb;
  background: #fff;
  color: #111827;
  text-decoration: none;
  cursor: pointer;
}
.btn.primary { background: #2f9e61; color: #fff; border-color: #2f9e61; }
.btn.ghost { background: #f3f4f6; }

.search-input, .input {
  width: 100%;
  border: 1px solid #d1d5db;
  border-radius: 12px;
  padding: 10px 12px;
  background: #fff;
  font-size: 14px;
}

.table { width: 100%; border-collapse: collapse; }
.table th { text-align: left; background: #e5e7eb; padding: 10px; }
.table td { padding: 10px; border-bottom: 1px solid #e5e7eb; }

.grid-4 { display: grid; gap: 12px; grid-template-columns: repeat(4, minmax(0,1fr)); }
.grid-3 { display: grid; gap: 12px; grid-template-columns: repeat(3, minmax(0,1fr)); }
.form-grid { display: grid; gap: 12px; grid-template-columns: repeat(2, minmax(0,1fr)); }
.detail-layout { display: grid; gap: 12px; grid-template-columns: 2fr 1fr; }
.footer-actions { display: flex; gap: 10px; justify-content: center; }

.tag { display:inline-block; margin-top:8px; padding:6px 10px; background:#eef2ff; border-radius:999px; font-size:12px; }

@media (max-width: 900px) {
  .layout { grid-template-columns: 1fr; }
  .form-grid, .grid-3, .grid-4, .detail-layout { grid-template-columns: 1fr; }
}
'@

Set-Content -Path "public/css/admin.css" -Value $css -Encoding UTF8
