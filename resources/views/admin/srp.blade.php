@extends('admin.layout')
@section('title', 'คู่มือมาตรฐาน SRP')
@section('content')
<div class="srp-head">
  <div class="srp-pill">มาตรฐาน SRP</div>
  <a class="srp-link" href="https://e-book.acfs.go.th/Book_view/346" target="_blank" rel="noopener">อ่านรายละเอียดมาตรฐาน SRP</a>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>คู่มือมาตรฐาน SRP</h3>
  <p class="muted">แนวทางการปลูกข้าวอย่างยั่งยืน (Sustainable Rice Platform) สำหรับเกษตรกรและผู้ตรวจประเมิน</p>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>ภาพรวมมาตรฐาน SRP</h3>
  <p>มาตรฐาน SRP (Sustainable Rice Platform) เป็นแนวทางการปลูกข้าวอย่างยั่งยืน ครอบคลุมด้านความปลอดภัยอาหาร เศรษฐกิจ สังคม และสิ่งแวดล้อม</p>
  <span class="tag">อ้างอิงมาตรฐานสากล + FAO + UNEP</span>
</div>

<div class="grid-3" style="margin-top: 16px;">
  <div class="card">
    <h3>วัตถุประสงค์หลัก</h3>
    <ul>
      <li>ลดต้นทุนและเพิ่มผลผลิตอย่างยั่งยืน</li>
      <li>ยกระดับความปลอดภัยอาหารและสุขภาพแรงงาน</li>
      <li>ลดผลกระทบต่อสิ่งแวดล้อมและน้ำ</li>
    </ul>
  </div>
  <div class="card">
    <h3>กลุ่มผู้ใช้งาน</h3>
    <ul>
      <li>เกษตรกรผู้ปลูกข้าว</li>
      <li>ผู้ตรวจประเมินแปลง</li>
      <li>หน่วยงานสถาบัน/สหกรณ์</li>
    </ul>
  </div>
  <div class="card">
    <h3>ผลลัพธ์ที่คาดหวัง</h3>
    <ul>
      <li>คุณภาพข้าวที่ดีขึ้น</li>
      <li>ความเสี่ยงลดลง</li>
      <li>มาตรฐานตรวจสอบชัดเจน</li>
    </ul>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>หัวข้อหลักที่ควรมีในคู่มือ</h3>
  <div class="form-grid" style="margin-top: 8px;">
    <div>
      <strong>1. การจัดการดินและน้ำ</strong>
      <p class="muted">เตรียมดิน การใช้น้ำแบบ AWD การอนุรักษ์หน้าดิน</p>
    </div>
    <div>
      <strong>2. การจัดการปุ๋ยและสารเคมี</strong>
      <p class="muted">สูตรปุ๋ย ปริมาณใช้ ความปลอดภัยในการใช้</p>
    </div>
    <div>
      <strong>3. การจัดการศัตรูพืช/โรค</strong>
      <p class="muted">ระบบเตือนภัย แนวทางลดสารเคมี บันทึกการใช้</p>
    </div>
    <div>
      <strong>4. การเก็บเกี่ยวและหลังการเก็บเกี่ยว</strong>
      <p class="muted">เวลาที่เหมาะสม การลดการสูญเสีย การคัดคุณภาพ</p>
    </div>
    <div>
      <strong>5. ความปลอดภัยแรงงาน</strong>
      <p class="muted">อุปกรณ์ป้องกัน การอบรม ข้อควรระวัง</p>
    </div>
    <div>
      <strong>6. การบันทึกข้อมูล</strong>
      <p class="muted">การระบุแปลง ประวัติกิจกรรม หลักฐาน</p>
    </div>
  </div>
</div>

<div class="grid-3" style="margin-top: 16px;">
  <div class="card">
    <h3>เอกสารประกอบที่ควรมี</h3>
    <ul>
      <li>ทะเบียนเกษตรกร</li>
      <li>แผนที่แปลง/เอกสารสิทธิ์ที่ดิน</li>
      <li>บันทึกการใช้ปุ๋ยและสารเคมี</li>
      <li>บันทึกการใช้น้ำ</li>
      <li>ผลตรวจคุณภาพ/ความชื้น</li>
    </ul>
  </div>
  <div class="card">
    <h3>ตัวชี้วัดสำคัญ</h3>
    <ul>
      <li>ปริมาณผลผลิตต่อไร่</li>
      <li>ต้นทุนต่อรอบการผลิต</li>
      <li>การใช้น้ำอย่างมีประสิทธิภาพ</li>
      <li>การลดสารเคมี</li>
      <li>การปฏิบัติตามแผนงาน</li>
    </ul>
  </div>
  <div class="card">
    <h3>ระดับการประเมิน</h3>
    <ul>
      <li>ผ่าน</li>
      <li>รอตรวจสอบ</li>
      <li>ต้องแก้ไข</li>
      <li>ไม่ผ่าน</li>
    </ul>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>รายการตรวจ (Checklist) ก่อนยื่นประเมิน</h3>
  <ul>
    <li>ข้อมูลทะเบียนเกษตรกรครบถ้วน</li>
    <li>บันทึกกิจกรรมครบทุกขั้นตอน</li>
    <li>หลักฐานการใช้น้ำ/ปุ๋ย/สารเคมีชัดเจน</li>
    <li>สภาพแปลงพร้อมตรวจ</li>
    <li>เอกสารอ้างอิงและรูปภาพประกอบ</li>
  </ul>
</div>
@endsection
