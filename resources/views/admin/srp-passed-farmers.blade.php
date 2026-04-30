@extends('admin.layout')
@section('title', 'เกษตรกรที่ผ่าน SRP 100%')
@section('content')
<div class="page-head">
  <div>
    <h1>รายชื่อเกษตรกรที่ผ่านการประเมิน SRP 100%</h1>
    <p class="muted">หน้าแยกสำหรับดูรายชื่อเกษตรกรที่ผ่านการประเมินครบ 100% โดยดึงจากข้อมูลเกษตรกรในความดูแล</p>
  </div>
  <div class="page-actions">
    <a class="btn ghost" href="/admin">กลับหน้าแดชบอร์ด</a>
  </div>
</div>

@if (isset($databaseAvailable) && ! $databaseAvailable)
  <div class="card" style="margin-top: 16px; border-color: #bfdbfe; background: #eff6ff; color: #1d4ed8;">
    หน้านี้ยังไม่สามารถดึงข้อมูลได้ครบ เพราะตารางหลักบางส่วนยังไม่พร้อม
    @if (! empty($missingTables))
      <div style="margin-top: 8px; color: #1e40af;">
        ตารางที่ยังขาด: {{ implode(', ', $missingTables) }}
      </div>
    @endif
  </div>
@endif

<div class="srp-summary-grid" style="margin-top: 16px;">
  <div class="card srp-summary-card">
    <span class="srp-summary-card__label">ผ่านการประเมินแล้ว</span>
    <strong class="srp-summary-card__value">{{ $summary['all_farmers'] }}</strong>
    <span class="muted">จำนวนเกษตรกรที่มีความคืบหน้าเฉลี่ยครบ 100%</span>
  </div>
  <div class="card srp-summary-card">
    <span class="srp-summary-card__label">มีผู้ดูแลหลัก</span>
    <strong class="srp-summary-card__value">{{ $summary['with_primary_admin'] }}</strong>
    <span class="muted">เกษตรกรที่มีการกำหนดผู้ดูแลหลักเรียบร้อยแล้ว</span>
  </div>
  <div class="card srp-summary-card">
    <span class="srp-summary-card__label">มีความเคลื่อนไหวในแอพ</span>
    <strong class="srp-summary-card__value">{{ $summary['with_app_activity'] }}</strong>
    <span class="muted">เกษตรกรที่มีการบันทึกกิจกรรมแปลงจากแอพ</span>
  </div>
  <div class="card srp-summary-card">
    <span class="srp-summary-card__label">แปลงทั้งหมด</span>
    <strong class="srp-summary-card__value">{{ $summary['all_plots'] }}</strong>
    <span class="muted">จำนวนแปลงของเกษตรกรที่ผ่านการประเมิน</span>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <div class="section-head">
    <div>
      <h3>รายชื่อเกษตรกร</h3>
      <p class="muted">กดดูรายละเอียดเพื่อเปิดข้อมูลโปรไฟล์ ทะเบียน ผู้ดูแล และรายการแปลงทั้งหมดของเกษตรกรแต่ละราย</p>
    </div>
    <span class="tag">{{ $farmers->count() }} รายชื่อ</span>
  </div>

  @if ($farmers->isEmpty())
    <div class="empty-state" style="margin-top: 16px;">ยังไม่พบเกษตรกรที่ผ่านการประเมิน 100%</div>
  @else
    <div class="srp-table-wrap" style="margin-top: 16px;">
      <table class="table srp-table">
        <thead>
          <tr>
            <th>ชื่อเกษตรกร</th>
            <th>รหัสทะเบียนเกษตรกร</th>
            <th>จังหวัด</th>
            <th>อำเภอ/เขต</th>
            <th>ผู้ดูแลหลัก</th>
            <th>แปลง</th>
            <th>กิจกรรมจากแอพ</th>
            <th>ความคืบหน้า</th>
            <th>รายละเอียด</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($farmers as $farmer)
            <tr>
              <td>
                <div class="srp-person">
                  <strong>{{ $farmer['name'] }}</strong>
                  <span class="muted">{{ $farmer['phone'] }}</span>
                </div>
              </td>
              <td>{{ $farmer['farmer_code'] }}</td>
              <td>{{ $farmer['province'] }}</td>
              <td>{{ $farmer['district'] }}</td>
              <td>
                <div class="srp-person">
                  <strong>{{ $farmer['primary_admin_name'] }}</strong>
                  <span class="muted">{{ $farmer['assignment_note'] ?: 'ไม่มีหมายเหตุการมอบหมาย' }}</span>
                </div>
              </td>
              <td>{{ $farmer['plot_count'] }} แปลง</td>
              <td>{{ $farmer['activity_count'] }} รายการ</td>
              <td>
                <div class="srp-progress">
                  <div class="srp-progress__bar">
                    <span style="width: {{ $farmer['average_progress'] }}%;"></span>
                  </div>
                  <strong>{{ $farmer['average_progress'] }}%</strong>
                </div>
              </td>
              <td><a class="btn ghost btn-sm" href="/admin/srp/farmers/{{ $farmer['slug'] }}">ดูรายละเอียด</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
