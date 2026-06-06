@extends('admin.layout')
@section('title', 'ดูข้อมูลผู้ใช้งาน')
@section('content')
<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="/admin/farmer-users" aria-label="กลับไปหน้าผู้ใช้งาน">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>ดูข้อมูลผู้ใช้งาน</h1>
      <p class="muted">รายละเอียดบัญชีผู้ใช้และข้อมูลเกษตรกร</p>
    </div>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>ข้อมูลผู้ดูแลเกษตรกร</h3>
  <div class="form-grid">
    <label>ผู้ดูแลหลัก
      <input class="input" type="text" value="{{ $userRecord->assigned_admin_display_name ?: $userRecord->assigned_admin_username ?: '-' }}" disabled>
    </label>
    @if (filled($userRecord->assignment_type))
      <label>ประเภทการดูแล
        <input
          class="input"
          type="text"
          value="{{ match($userRecord->assignment_type) { 'AREA' => 'ดูแลตามพื้นที่', 'INDIVIDUAL' => 'ดูแลเฉพาะราย', default => $userRecord->assignment_type } }}"
          disabled
        >
      </label>
    @endif
    @if (filled($userRecord->assignment_note))
      <label>หมายเหตุการมอบหมาย
        <input class="input" type="text" value="{{ $userRecord->assignment_note }}" disabled>
      </label>
    @endif
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>ข้อมูลส่วนตัวผู้ใช้งาน</h3>
  <div class="form-grid">
    <label>ชื่อ-นามสกุล
      <input class="input" type="text" value="{{ $userRecord->full_name }}" disabled>
    </label>
    <label>ชื่อผู้ใช้
      <input class="input" type="text" value="{{ $userRecord->username }}" disabled>
    </label>
    <label>เลขบัตรประชาชน
      <input class="input" type="text" value="{{ $userRecord->citizen_id }}" disabled>
    </label>
    <label>บทบาท
      <input class="input" type="text" value="{{ $userRecord->role }}" disabled>
    </label>
    <label>เบอร์โทรศัพท์
      <input class="input" type="text" value="{{ $userRecord->phone }}" disabled>
    </label>
    <label>วันเดือนปีเกิด
      <input class="input" type="text" value="{{ $userRecord->birth_date ? \Illuminate\Support\Carbon::parse($userRecord->birth_date)->format('d/m/Y') : '' }}" disabled>
    </label>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>ที่อยู่ปัจจุบัน</h3>
  <div class="form-grid">
    <label>ที่อยู่เลขที่/หมู่/ซอย/ถนน
      <input class="input" type="text" value="{{ $userRecord->address_line }}" disabled>
    </label>
    <label>จังหวัด
      <input class="input" type="text" value="{{ $userRecord->province }}" disabled>
    </label>
    <label>อำเภอ/เขต
      <input class="input" type="text" value="{{ $userRecord->district }}" disabled>
    </label>
    <label>ตำบล/แขวง
      <input class="input" type="text" value="{{ $userRecord->subdistrict }}" disabled>
    </label>
    <label>รหัสไปรษณีย์
      <input class="input" type="text" value="{{ $userRecord->postcode }}" disabled>
    </label>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>ข้อมูลเกษตรกร</h3>
  <div class="form-grid">
    <label>รหัสทะเบียนเกษตรกร
      <input class="input" type="text" value="{{ $userRecord->farmer_code }}" disabled>
    </label>
    <label>วันที่ขึ้นทะเบียน
      <input class="input" type="text" value="{{ $userRecord->registered_at ? \Illuminate\Support\Carbon::parse($userRecord->registered_at)->format('d/m/Y') : '' }}" disabled>
    </label>
    <label>จังหวัดที่ขึ้นทะเบียน
      <input class="input" type="text" value="{{ $userRecord->registered_province }}" disabled>
    </label>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <div class="section-head">
    <div>
      <h3>แปลงนาทั้งหมด</h3>
      <p class="muted">แปลงที่เกษตรกรมีอยู่ในระบบ</p>
    </div>
    <a href="/admin/farmer-users/{{ $userRecord->id }}/plots/create" class="btn primary btn-sm">
      + เพิ่มแปลง
    </a>
  </div>

  @if (session('success'))
    <div class="status-banner success" style="margin-top: 12px;">{{ session('success') }}</div>
  @endif

  @if (isset($plots) && $plots->isNotEmpty())
    <div class="srp-table-wrap" style="margin-top: 16px;">
      <table class="table srp-table">
        <thead>
          <tr>
            <th>รหัสแปลง</th>
            <th>ชื่อแปลง</th>
            <th>พื้นที่</th>
            <th>พืชที่ปลูก</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($plots as $plot)
            <tr>
              <td class="muted" style="font-size:13px;">{{ $plot->farm_id }}</td>
              <td>{{ $plot->plot_name ?: '-' }}</td>
              <td>
                @php
                  $parts = [];
                  if (filled($plot->area_rai)) $parts[] = $plot->area_rai . ' ไร่';
                  if (filled($plot->area_ngan)) $parts[] = $plot->area_ngan . ' งาน';
                  if (filled($plot->area_sq_wa)) $parts[] = $plot->area_sq_wa . ' ตร.ว.';
                @endphp
                {{ implode(' ', $parts) ?: '-' }}
              </td>
              <td>{{ $plot->crop_type ?: '-' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div class="empty-state" style="margin-top: 16px;">ยังไม่มีแปลงในระบบ</div>
  @endif
</div>

<div class="card" style="margin-top: 16px;">
  <h3>ข้อมูลแปลงหลัก</h3>
  <div class="form-grid">
    <label>จังหวัดแปลง
      <input class="input" type="text" value="{{ $userRecord->farm_province }}" disabled>
    </label>
    <label>พื้นที่แปลงปลูก (ไร่-งาน-ตารางวา)
      <div class="area-inputs">
        <input class="input area-field" type="text" value="{{ $userRecord->farm_area_rai }}" disabled>
        <span class="unit">ไร่</span>
        <input class="input area-field" type="text" value="{{ $userRecord->farm_area_ngan }}" disabled>
        <span class="unit">งาน</span>
        <input class="input area-field" type="text" value="{{ $userRecord->farm_area_square_wa }}" disabled>
        <span class="unit">ตารางวา</span>
      </div>
    </label>
    <label>ประเภทพืชปลูก
      <input class="input" type="text" value="{{ $userRecord->crop_type }}" disabled>
    </label>
  </div>
</div>

@if (!empty($canManageAdminRoles) && (filled($userRecord->admin_title) || filled($userRecord->scope_province) || filled($userRecord->scope_district) || filled($userRecord->scope_subdistrict)))
  <div class="card" style="margin-top: 16px;">
    <h3>ขอบเขตการดูแลของแอดมิน</h3>
    <div class="form-grid" style="margin-top: 12px;">
      <label>ตำแหน่งแอดมิน
        <input class="input" type="text" value="{{ $userRecord->admin_title }}" disabled>
      </label>
      <label>จังหวัดที่รับผิดชอบ
        <input class="input" type="text" value="{{ $userRecord->scope_province }}" disabled>
      </label>
      <label>อำเภอ/เขตที่รับผิดชอบ
        <input class="input" type="text" value="{{ $userRecord->scope_district }}" disabled>
      </label>
      <label>ตำบล/แขวงที่รับผิดชอบ
        <input class="input" type="text" value="{{ $userRecord->scope_subdistrict }}" disabled>
      </label>
    </div>
  </div>
@endif
@endsection
