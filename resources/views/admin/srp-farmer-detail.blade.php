@extends('admin.layout')
@section('title', 'รายละเอียดข้อมูลเกษตรกร')
@section('content')
<a class="back-link icon-only" href="/admin/srp/farmers" aria-label="กลับไปรายชื่อเกษตรกร">
  <span class="back-icon">‹</span>
</a>

<div class="page-head">
  <div>
    <h1>{{ $farmer['name'] }}</h1>
    <p class="muted">{{ $farmer['farmer_code'] }} • {{ $farmer['district'] }} {{ $farmer['province'] }}</p>
  </div>
</div>

@if (isset($databaseAvailable) && ! $databaseAvailable)
  <div class="card" style="margin-top: 16px; border-color: #bfdbfe; background: #eff6ff; color: #1d4ed8;">
    หน้านี้ยังแสดงข้อมูลได้ไม่ครบ เพราะตารางหลักบางส่วนยังไม่พร้อม
    @if (! empty($missingTables))
      <div style="margin-top: 8px; color: #1e40af;">
        ตารางที่ยังขาด: {{ implode(', ', $missingTables) }}
      </div>
    @endif
  </div>
@endif

<div class="srp-summary-grid" style="margin-top: 16px;">
  <div class="card srp-summary-card">
    <span class="srp-summary-card__label">จำนวนแปลง</span>
    <strong class="srp-summary-card__value">{{ $plotSummary['total'] }}</strong>
    <span class="muted">แปลงที่ผูกอยู่กับเกษตรกรรายนี้</span>
  </div>
  <div class="card srp-summary-card">
    <span class="srp-summary-card__label">มีข้อมูลจากแอพ</span>
    <strong class="srp-summary-card__value">{{ $plotSummary['with_activity'] }}</strong>
    <span class="muted">แปลงที่มีการบันทึกกิจกรรมจากแอพแล้ว</span>
  </div>
  <div class="card srp-summary-card">
    <span class="srp-summary-card__label">ความคืบหน้าเฉลี่ย</span>
    <strong class="srp-summary-card__value">{{ $plotSummary['average_progress'] }}%</strong>
    <span class="muted">คิดจากกิจกรรมที่บันทึกในทุกแปลง</span>
  </div>
  <div class="card srp-summary-card">
    <span class="srp-summary-card__label">อัปเดตล่าสุด</span>
    <strong class="srp-summary-card__value" style="font-size: 24px;">{{ $plotSummary['latest_activity_date'] }}</strong>
    <span class="muted">วันที่มีความเคลื่อนไหวล่าสุดในระบบแปลง</span>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <div class="section-head">
    <div>
      <h3>ข้อมูลโปรไฟล์เกษตรกร</h3>
      <p class="muted">ข้อมูลที่บันทึกจากฟอร์มเพิ่มผู้ใช้งานและแก้ไขข้อมูลเกษตรกร</p>
    </div>
  </div>
  <div class="form-grid" style="margin-top: 16px;">
    <div>
      <span class="muted">ชื่อ-นามสกุล</span>
      <strong style="display:block; margin-top:6px;">{{ $farmer['name'] }}</strong>
    </div>
    <div>
      <span class="muted">ชื่อผู้ใช้</span>
      <strong style="display:block; margin-top:6px;">{{ $farmer['username'] }}</strong>
    </div>
    <div>
      <span class="muted">เบอร์โทรศัพท์</span>
      <strong style="display:block; margin-top:6px;">{{ $farmer['phone'] }}</strong>
    </div>
    <div>
      <span class="muted">เลขบัตรประชาชน</span>
      <strong style="display:block; margin-top:6px;">{{ $farmer['citizen_id'] }}</strong>
    </div>
    <div>
      <span class="muted">วันเกิด</span>
      <strong style="display:block; margin-top:6px;">{{ $farmer['birthdate'] }}</strong>
    </div>
    <div>
      <span class="muted">ที่อยู่</span>
      <strong style="display:block; margin-top:6px;">{{ $farmer['full_address'] }}</strong>
    </div>
    <div>
      <span class="muted">รหัสทะเบียนเกษตรกร</span>
      <strong style="display:block; margin-top:6px;">{{ $farmer['farmer_code'] }}</strong>
    </div>
    <div>
      <span class="muted">วันที่ขึ้นทะเบียน</span>
      <strong style="display:block; margin-top:6px;">{{ $farmer['registered_at'] }}</strong>
    </div>
    <div>
      <span class="muted">พื้นที่รับผิดชอบ</span>
      <strong style="display:block; margin-top:6px;">{{ $farmer['province'] }} / {{ $farmer['district'] }} / {{ $farmer['subdistrict'] }}</strong>
    </div>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <div class="section-head">
    <div>
      <h3>ข้อมูลผู้ดูแล</h3>
      <p class="muted">แสดงผู้ดูแลหลัก ผู้ดูแลร่วม และหมายเหตุการมอบหมายของเกษตรกรรายนี้</p>
    </div>
  </div>
  <div class="form-grid" style="margin-top: 16px;">
    <div>
      <span class="muted">ผู้ดูแลหลัก</span>
      <strong style="display:block; margin-top:6px;">{{ $farmer['primary_admin_name'] }}</strong>
    </div>
    <div>
      <span class="muted">ผู้ดูแลร่วม</span>
      <strong style="display:block; margin-top:6px;">{{ $farmer['secondary_admins'] !== [] ? implode(', ', $farmer['secondary_admins']) : '-' }}</strong>
    </div>
    <div style="grid-column: 1 / -1;">
      <span class="muted">หมายเหตุการมอบหมาย</span>
      <strong style="display:block; margin-top:6px; white-space: pre-line;">{{ $farmer['assignment_note'] ?: '-' }}</strong>
    </div>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <div class="section-head">
    <div>
      <h3>ข้อมูลแปลงและกิจกรรมจากแอพ</h3>
      <p class="muted">สรุปรายการแปลงทั้งหมด พร้อมชนิดพืช ความคืบหน้า และปุ่มกดดูภาพรวมความคืบหน้าของแต่ละแปลง</p>
    </div>
    <div style="display:flex; align-items:center; gap:10px;">
      <span class="tag">{{ $farmer['plot_count'] }} แปลง</span>
      <a href="/admin/farmer-users/{{ $farmer['id'] }}/plots/create" class="btn primary btn-sm">
        + เพิ่มแปลง
      </a>
    </div>
  </div>

  @if (empty($farmer['plots']))
    <div class="empty-state" style="margin-top: 16px;">ยังไม่มีข้อมูลแปลงของเกษตรกรรายนี้</div>
  @else
    <div class="srp-table-wrap" style="margin-top: 16px;">
      <table class="table srp-table">
        <thead>
          <tr>
            <th>ชื่อแปลง</th>
            <th>อ้างอิงแปลง</th>
            <th>ชนิดพืช</th>
            <th>พื้นที่</th>
            <th>กิจกรรม</th>
            <th>กิจกรรมล่าสุด</th>
            <th>อัปเดตล่าสุด</th>
            <th>ความคืบหน้า</th>
            <th>ภาพรวม</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($farmer['plots'] as $plot)
            <tr>
              <td>{{ $plot['plot_name'] }}</td>
              <td>{{ $plot['farm_id'] }}</td>
              <td>{{ $plot['crop_type'] }}</td>
              <td>{{ $plot['area'] }}</td>
              <td>{{ $plot['activity_count'] }} รายการ</td>
              <td>{{ $plot['latest_activity_name'] }}</td>
              <td>{{ $plot['last_activity_at'] }}</td>
              <td>
                <div class="srp-progress">
                  <div class="srp-progress__bar">
                    <span style="width: {{ $plot['progress_percent'] }}%;"></span>
                  </div>
                  <strong>{{ $plot['progress_percent'] }}%</strong>
                </div>
              </td>
              <td>
                <a class="btn ghost btn-sm" href="/admin/srp/farmers/{{ $farmer['slug'] }}/plots/{{ $plot['plot_id'] }}">ดูภาพรวมแปลง</a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
