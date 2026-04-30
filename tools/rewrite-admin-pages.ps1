$files = @{}

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

$files["resources/views/admin/users.blade.php"] = @'
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

$files["resources/views/admin/users-create.blade.php"] = @'
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

$files["resources/views/admin/users-account.blade.php"] = @'
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

$files["resources/views/admin/rice.blade.php"] = @'
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

$files["resources/views/admin/rice-create.blade.php"] = @'
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

$files["resources/views/admin/notifications.blade.php"] = @'
@extends('admin.layout')
@section('title', 'แจ้งเตือน')
@section('content')
<div class="page-head">
  <div>
    <h1>การแจ้งเตือน</h1>
    <p class="muted">ติดตามการอัปเดตจากผู้ใช้ทุกกิจกรรม</p>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>แจ้งเตือนล่าสุด</h3>
  <ul class="list">
    <li>สมศักดิ์ สุขสวย อัปเดตกิจกรรม “เตรียมดิน” แล้ว</li>
    <li>สมศรี อวยพร ส่งรายงานปัญหาใหม่ 1 รายการ</li>
    <li>มีนา พาใจ อัปเดตกิจกรรม “การจัดการน้ำ” แล้ว</li>
  </ul>
</div>
@endsection
'@

$files["resources/views/admin/settings.blade.php"] = @'
@extends('admin.layout')
@section('title', 'การตั้งค่า')
@section('content')
<div class="page-head">
  <div>
    <h1>การตั้งค่า</h1>
    <p class="muted">ปรับแต่งระบบสำหรับผู้ดูแล</p>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>บัญชีผู้ดูแล</h3>
  <div class="form-grid">
    <label>ชื่อผู้ดูแล <input class="input" type="text" value="แอดมิน"></label>
    <label>อีเมล <input class="input" type="email" value="admin@example.com"></label>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>การแจ้งเตือน</h3>
  <div class="form-grid">
    <label><input type="checkbox" checked> แจ้งเตือนเมื่อมีการอัปเดตจากผู้ใช้</label>
    <label><input type="checkbox" checked> แจ้งเตือนเมื่อมีรายงานปัญหาใหม่</label>
  </div>
</div>

<div class="footer-actions" style="margin-top: 20px;">
  <button class="btn primary">บันทึก</button>
</div>
@endsection
'@

$files["resources/views/admin/srp.blade.php"] = @'
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

<div class="card" style="margin-top: 16px;">
  <h3>ขอบข่ายของมาตรฐาน</h3>
  <div class="chip-grid">
    <span class="chip">การจัดการฟาร์ม</span>
    <span class="chip">การเตรียมดินก่อนปลูก</span>
    <span class="chip">การใช้น้ำ</span>
    <span class="chip">การจัดการธาตุอาหาร (ปุ๋ย)</span>
    <span class="chip">การจัดการศัตรูพืช (IPM)</span>
    <span class="chip">การเก็บเกี่ยวและหลังเก็บเกี่ยว</span>
    <span class="chip">ความปลอดภัยแรงงาน</span>
    <span class="chip">สิทธิแรงงาน</span>
    <span class="chip">การแปรรูปและฉลากสินค้า</span>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>หลักการสำคัญ</h3>
  <div class="grid-3">
    <div class="card">
      <h4>🌱 เพิ่มผลผลิตอย่างยั่งยืน</h4>
      <ul>
        <li>ใช้ทรัพยากรอย่างมีประสิทธิภาพ</li>
        <li>ลดต้นทุนการผลิต</li>
        <li>ลดผลกระทบต่อสิ่งแวดล้อม</li>
      </ul>
    </div>
    <div class="card">
      <h4>🌍 อนุรักษ์สิ่งแวดล้อม</h4>
      <ul>
        <li>ลดการใช้สารเคมี</li>
        <li>ใช้เทคโนโลยีที่เป็นมิตรต่อธรรมชาติ</li>
        <li>ป้องกันระบบนิเวศ</li>
      </ul>
    </div>
    <div class="card">
      <h4>👨‍🌾 พัฒนาคุณภาพชีวิตเกษตรกร</h4>
      <ul>
        <li>เพิ่มรายได้</li>
        <li>ส่งเสริมความปลอดภัยในการทำงาน</li>
        <li>สนับสนุนความเป็นอยู่ที่ดี</li>
      </ul>
    </div>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>การปฏิบัติสำคัญ</h3>
  <details class="accordion" open>
    <summary>📊 การบันทึกข้อมูล</summary>
    <ul>
      <li>ข้อมูลพื้นที่</li>
      <li>ต้นทุนการผลิต</li>
      <li>การใช้น้ำ</li>
      <li>การใช้ปุ๋ย/สารเคมี</li>
      <li>ผลผลิตและราคาขาย</li>
    </ul>
  </details>
  <details class="accordion">
    <summary>🌾 การจัดการปุ๋ย</summary>
    <ul>
      <li>ใช้ตามความเหมาะสมของดิน</li>
      <li>ใช้ปุ๋ยอินทรีย์ร่วม</li>
      <li>ลดการใช้สารเคมี</li>
    </ul>
  </details>
  <details class="accordion">
    <summary>🐛 การจัดการศัตรูพืช (IPM)</summary>
    <ul>
      <li>สำรวจศัตรูพืชสม่ำเสมอ</li>
      <li>ใช้วิธีที่ปลอดภัยก่อน</li>
      <li>ลดการใช้สารเคมี</li>
    </ul>
  </details>
  <details class="accordion">
    <summary>💧 การจัดการน้ำ</summary>
    <ul>
      <li>ใช้น้ำอย่างมีประสิทธิภาพ</li>
      <li>ใช้ระบบ “เปียกสลับแห้ง”</li>
    </ul>
  </details>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>ประโยชน์ของ SRP</h3>
  <ul>
    <li>เพิ่มผลผลิตและลดต้นทุน</li>
    <li>เพิ่มโอกาสทางการตลาด</li>
    <li>สร้างความเชื่อมั่นผู้บริโภค</li>
    <li>สนับสนุนความยั่งยืน</li>
  </ul>
</div>
@endsection
'@

$files["resources/views/admin/srp-farmers.blade.php"] = @'
@extends('admin.layout')
@section('title', 'รายการเกษตรกรตามมาตรฐาน SRP')
@section('content')
<div class="page-head">
  <div>
    <h1>รายการเกษตรกรตามมาตรฐาน SRP</h1>
    <p class="muted">ติดตามความคืบหน้าการประเมิน</p>
  </div>
  <button class="btn ghost">ส่งออกรีพอร์ต</button>
</div>

<div class="card" style="margin-top: 16px;">
  <table class="table">
    <thead>
      <tr>
        <th>ชื่อเกษตรกร</th>
        <th>รหัสแปลง</th>
        <th>ความคืบหน้า</th>
        <th>สถานะ SRP</th>
        <th>ปัญหาล่าสุด</th>
        <th>อัปเดตล่าสุด</th>
        <th>ดำเนินการ</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>สมศักดิ์ สุขสวย</td>
        <td>SM1/2345</td>
        <td>100%</td>
        <td>ผ่าน</td>
        <td>-</td>
        <td>15 มิ.ย. 2568</td>
        <td><a href="/admin/report/rice-detail">ดูรายละเอียด</a></td>
      </tr>
    </tbody>
  </table>
</div>
@endsection
'@

$files["resources/views/admin/tracking-prep.blade.php"] = $trackingTemplate.Replace('@@TITLE@@','การเตรียมดิน').Replace('@@ACTIVITY@@','เตรียมดิน')
$files["resources/views/admin/tracking-water.blade.php"] = $trackingTemplate.Replace('@@TITLE@@','การจัดการน้ำ').Replace('@@ACTIVITY@@','การใช้น้ำ')
$files["resources/views/admin/tracking-fertilizer.blade.php"] = $trackingTemplate.Replace('@@TITLE@@','หว่านปุ๋ย').Replace('@@ACTIVITY@@','หว่านปุ๋ย')
$files["resources/views/admin/tracking-pest.blade.php"] = $trackingTemplate.Replace('@@TITLE@@','การจัดการศัตรูพืช').Replace('@@ACTIVITY@@','การจัดการศัตรูพืช')
$files["resources/views/admin/tracking-disease.blade.php"] = $trackingTemplate.Replace('@@TITLE@@','การจัดการโรคพืช').Replace('@@ACTIVITY@@','การจัดการโรคพืช')
$files["resources/views/admin/tracking-harvest.blade.php"] = $trackingTemplate.Replace('@@TITLE@@','การเก็บเกี่ยว').Replace('@@ACTIVITY@@','การเก็บเกี่ยว')
$files["resources/views/admin/tracking-mill.blade.php"] = $trackingTemplate.Replace('@@TITLE@@','ขายข้าวเข้าโรงสี').Replace('@@ACTIVITY@@','ขายข้าวเข้าโรงสี')

$files["resources/views/admin/tracking-prep-detail.blade.php"] = @'
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

$files["resources/views/admin/report-rice.blade.php"] = $reportTemplate.Replace('@@TITLE@@','รายงานการปลูกข้าว').Replace('@@PROBLEM@@','เตรียมดิน')
$files["resources/views/admin/report-system.blade.php"] = $reportTemplate.Replace('@@TITLE@@','รายงานปัญหาเกี่ยวกับระบบ').Replace('@@PROBLEM@@','Upload กิจกรรมไม่ได้')
$files["resources/views/admin/report-rice-risk.blade.php"] = $reportTemplate.Replace('@@TITLE@@','แปลงเสี่ยง/ไม่ผ่านมาตรฐาน').Replace('@@PROBLEM@@','ไม่ผ่านมาตรฐาน')

$files["resources/views/admin/report-rice-detail.blade.php"] = @'
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

$files["resources/views/admin/report-system-detail.blade.php"] = @'
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

$files["resources/views/admin/dashboard.blade.php"] = @'
@extends('admin.layout')
@section('title', 'แดชบอร์ด')
@section('content')
<div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
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

<div class="grid-3" style="margin-top:16px;">
  <div class="card">
    <h3>งานรอดำเนินการวันนี้</h3>
    <p class="big">12 รายการ</p>
  </div>
  <div class="card">
    <h3>รายงานปัญหาใหม่</h3>
    <p class="big">5 รายการ</p>
  </div>
  <div class="card">
    <h3>แปลงเสี่ยง/ไม่ผ่านมาตรฐาน</h3>
    <p class="big">3 แปลง</p>
  </div>
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

foreach ($path in $files.Keys) {
  $content = $files[$path]
  Set-Content -Path $path -Value $content -Encoding UTF8
}
