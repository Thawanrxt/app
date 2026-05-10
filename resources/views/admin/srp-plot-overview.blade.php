@extends('admin.layout')
@section('title', 'ภาพรวมความคืบหน้าแปลง')
@section('content')
<a class="back-link icon-only" href="/admin/srp/farmers/{{ $farmer['slug'] }}" aria-label="กลับไปหน้ารายละเอียดเกษตรกร">
  <span class="back-icon">‹</span>
</a>

<div class="page-head">
  <div>
    <h1>ภาพรวมแปลง {{ $plot['plot_name'] }}</h1>
    <p class="muted">{{ $farmer['name'] }} • {{ $plot['farm_id'] }} • {{ $plot['area'] }}</p>
  </div>
</div>

<div class="srp-summary-grid" style="margin-top: 16px;">
  <div class="card srp-summary-card">
    <span class="srp-summary-card__label">ความคืบหน้า</span>
    <strong class="srp-summary-card__value">{{ $plot['progress_percent'] }}%</strong>
    <span class="muted">คำนวณจากกิจกรรมที่บันทึกของแปลงนี้</span>
  </div>
  <div class="card srp-summary-card">
    <span class="srp-summary-card__label">จำนวนกิจกรรม</span>
    <strong class="srp-summary-card__value">{{ $plot['activity_count'] }}</strong>
    <span class="muted">รายการกิจกรรมทั้งหมดจากแอพ</span>
  </div>
  <div class="card srp-summary-card">
    <span class="srp-summary-card__label">กิจกรรมล่าสุด</span>
    <strong class="srp-summary-card__value" style="font-size: 24px;">{{ $plot['latest_activity_name'] }}</strong>
    <span class="muted">อัปเดตล่าสุด {{ $plot['last_activity_at'] }}</span>
  </div>
  <div class="card srp-summary-card">
    <span class="srp-summary-card__label">สถานะรวม</span>
    <strong class="srp-summary-card__value">{{ $statusSummary['passed'] }}</strong>
    <span class="muted">ผ่านแล้ว {{ $statusSummary['passed'] }} • รอตรวจ {{ $statusSummary['pending_review'] }} • ต้องแก้ {{ $statusSummary['needs_fix'] }}</span>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <div class="section-head">
    <div>
      <h3>ข้อมูลแปลง</h3>
      <p class="muted">สรุปข้อมูลหลักของแปลงนี้สำหรับดูภาพรวมก่อนเปิดกิจกรรมรายรายการ</p>
    </div>
  </div>
  <div class="form-grid" style="margin-top: 16px;">
    <div>
      <span class="muted">ชื่อแปลง</span>
      <strong style="display:block; margin-top:6px;">{{ $plot['plot_name'] }}</strong>
    </div>
    <div>
      <span class="muted">รหัสแปลง</span>
      <strong style="display:block; margin-top:6px;">{{ $plot['farm_id'] }}</strong>
    </div>
    <div>
      <span class="muted">ชนิดพืช</span>
      <strong style="display:block; margin-top:6px;">{{ $plot['crop_type'] }}</strong>
    </div>
    <div>
      <span class="muted">พื้นที่</span>
      <strong style="display:block; margin-top:6px;">{{ $plot['area'] }}</strong>
    </div>
    <div style="grid-column: 1 / -1;">
      <span class="muted">ที่ตั้งแปลง</span>
      <strong style="display:block; margin-top:6px;">{{ $plot['address'] }}</strong>
    </div>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <div class="section-head">
    <div>
      <h3>สัดส่วนประเภทกิจกรรมในแปลงนี้</h3>
      <p class="muted">แสดงเป็นเปอร์เซ็นต์ก่อน เพื่อดูภาพรวมว่าแปลงนี้ใช้เวลากับกิจกรรมประเภทไหนมากที่สุด</p>
    </div>
  </div>
  @if ($activityBreakdown->isEmpty())
    <div class="empty-state" style="margin-top: 16px;">ยังไม่มีกิจกรรมจากแอพในแปลงนี้</div>
  @else
    @php($activityTotal = max(1, $activities->count()))
    <div class="srp-table-wrap" style="margin-top: 16px;">
      <table class="table srp-table">
        <thead>
          <tr>
            <th>กิจกรรม</th>
            <th>เปอร์เซ็นต์</th>
            <th>จำนวนจริง</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($activityBreakdown as $item)
            @php($percent = (int) round(($item['count'] / $activityTotal) * 100))
            <tr>
              <td>{{ $item['label'] }}</td>
              <td>
                <div class="srp-progress">
                  <div class="srp-progress__bar">
                    <span style="width: {{ $percent }}%;"></span>
                  </div>
                  <strong>{{ $percent }}%</strong>
                </div>
              </td>
              <td>{{ $item['count'] }}/{{ $activityTotal }} รายการ</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

<div class="card" style="margin-top: 16px;">
  <div class="section-head">
    <div>
      <h3>ไทม์ไลน์กิจกรรมของแปลง</h3>
      <p class="muted">กดดูรายละเอียดเพื่อเปิดหน้ากิจกรรมเดิมของระบบและดูความคืบหน้ารายรายการ</p>
    </div>
    <span class="tag">{{ $activities->count() }} รายการ</span>
  </div>

  @if ($activities->isEmpty())
    <div class="empty-state" style="margin-top: 16px;">ยังไม่มีกิจกรรมจากแอพในแปลงนี้</div>
  @else
    <div class="srp-table-wrap" style="margin-top: 16px;">
      <table class="table srp-table">
        <thead>
          <tr>
            <th>วันที่</th>
            <th>กิจกรรม</th>
            <th>ครั้งที่</th>
            <th>ผู้ทำกิจกรรม</th>
            <th>สถานะ</th>
            <th>รายละเอียด</th>
            <th>เปิดต่อ</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($activities as $activity)
            <tr>
              <td>{{ $activity['activity_date'] }}</td>
              <td>{{ $activity['activity_name'] }}</td>
              <td>{{ $activity['round_number'] }}</td>
              <td>{{ $activity['performed_by_name'] }}</td>
              <td><span class="chip {{ $activity['status_class'] }}">{{ $activity['status_label'] }}</span></td>
              <td>{{ $activity['details'] }}</td>
              <td><a class="btn ghost btn-sm" href="{{ $activity['detail_url'] }}">ดูรายละเอียด</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
