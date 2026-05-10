@extends('admin.layout')

@section('title', 'แดชบอร์ด')

@section('content')
@php
  $emptyMessage = 'ยังไม่มีข้อมูล';
  $filterLabels = [
    'all' => 'ทั้งหมด',
    'in_progress' => 'กำลังตรวจ',
    'needs_fix' => 'ต้องแก้ไข',
  ];
  $activeFilter = $dashboard['active_filter'];
  $commonIssues = $dashboard['common_issues'];
  $commonIssueMax = collect($commonIssues)->max('count') ?: 1;
  $todayTasksUrl = '/admin/dashboard/today-tasks';
  $issueReportsUrl = '/admin/dashboard/all-issues';
  $systemReportsUrl = '/admin/dashboard/issue-reports';
  $documentReviewUrl = '/admin/dashboard/document-reviews';
@endphp

<div class="page-head dashboard-head">
  <div>
    <div class="dashboard-eyebrow">ระบบจัดการแปลงนา · SRP</div>
    <h1>แดชบอร์ด</h1>
    <p class="muted" id="dashboard-updated-at">
      อัปเดตล่าสุด:
      {{ $dashboard['updated_at'] ? \Illuminate\Support\Carbon::parse($dashboard['updated_at'])->translatedFormat('d M Y H:i') : '-' }}
    </p>
  </div>
  <div class="page-actions">
    <a class="btn ghost" href="/admin/report/export/print" target="_blank" rel="noopener">Export PDF</a>
    <a class="btn ghost" href="/admin/followup-plans/create">เพิ่มแผนงาน</a>
    <a class="btn primary" href="/admin/farmer-users/create">เพิ่มเกษตรกร</a>
  </div>
</div>

<div class="kpi-grid">
  <a class="kpi-card kpi-card--link kpi-card--green" href="/admin/farmer-users" aria-label="ดูรายละเอียดเกษตรกร">
    <div>
      <div class="kpi-value">{{ $dashboard['summary']['farmers_total'] }}</div>
      <div class="kpi-label">เกษตรกร</div>
      <div class="kpi-sub">
        {{ $dashboard['summary']['farmers_total'] > 0 ? 'มีรายชื่อเกษตรกรในระบบแล้ว' : $emptyMessage }}
      </div>
    </div>
  </a>

  <a class="kpi-card kpi-card--link kpi-card--blue" href="/admin/srp/farmers" aria-label="ดูรายละเอียดพื้นที่รวม">
    <div>
      <div class="kpi-value">{{ $dashboard['summary']['area_total_rai'] }}</div>
      <div class="kpi-label">พื้นที่รวม (ไร่)</div>
      <div class="kpi-sub">
        เฉลี่ย {{ number_format($dashboard['summary']['area_average_rai'], 1) }} ไร่/ราย
      </div>
    </div>
  </a>

  <a class="kpi-card kpi-card--link kpi-card--amber" href="/admin/rice" aria-label="ดูรายละเอียดพันธุ์ข้าว">
    <div>
      <div class="kpi-value">{{ $dashboard['summary']['rice_varieties_total'] }}</div>
      <div class="kpi-label">พันธุ์ข้าว</div>
      <div class="kpi-sub">
        {{ $dashboard['summary']['rice_varieties_total'] > 0 ? 'มีรายการพันธุ์ข้าวในระบบ' : $emptyMessage }}
      </div>
    </div>
  </a>

  <a class="kpi-card kpi-card--link kpi-card--purple kpi-wide" href="/admin/srp/farmers/passed" aria-label="ดูรายละเอียดการประเมินมาตรฐาน SRP">
    <div>
      <div class="kpi-value">{{ $dashboard['summary']['srp_pass_rate'] }}%</div>
      <div class="kpi-label">การประเมินตามมาตรฐาน SRP</div>
      <div class="kpi-sub">ผ่านแล้ว {{ $dashboard['summary']['srp_passed_total'] }} ราย</div>
    </div>
    <span class="btn primary btn-slim">ดูรายชื่อเกษตรกร</span>
  </a>
</div>

<div class="quick-grid">
  <a class="quick-card quick-card--link quick-card--blue" href="{{ $todayTasksUrl }}" aria-label="ดูรายละเอียดงานตรวจวันนี้">
    <div class="quick-card-top">
      <span class="quick-icon quick-icon--blue">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
      </span>
    </div>
    <div class="quick-value">{{ $dashboard['quick_stats']['today_tasks_total'] }}</div>
    <div class="quick-label">งานตรวจวันนี้</div>
    <div class="quick-sub">กำหนดติดตามภายในวันที่ {{ now()->translatedFormat('d M Y') }}</div>
  </a>

  <a class="quick-card quick-card--link quick-card--red" href="{{ $systemReportsUrl }}" aria-label="ดูรายละเอียดรายงานปัญหาใหม่">
    <div class="quick-card-top">
      <span class="quick-icon quick-icon--red">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
      </span>
    </div>
    <div class="quick-value">{{ $dashboard['quick_stats']['new_issue_reports_total'] }}</div>
    <div class="quick-label">รายงานปัญหาใหม่</div>
    <div class="quick-sub">รวมเคสที่ต้องแก้ไขในระบบ</div>
  </a>

  <a class="quick-card quick-card--link quick-card--amber" href="{{ $documentReviewUrl }}" aria-label="ดูรายละเอียดเอกสารรอตรวจ">
    <div class="quick-card-top">
      <span class="quick-icon quick-icon--amber">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
      </span>
    </div>
    <div class="quick-value">{{ $dashboard['quick_stats']['pending_documents_total'] }}</div>
    <div class="quick-label">เอกสารรอตรวจสอบ</div>
    <div class="quick-sub">อยู่ระหว่างรอตรวจสอบหลักฐาน</div>
  </a>

  <a class="quick-card quick-card--link quick-card--slate" href="{{ $issueReportsUrl }}" aria-label="ดูรายละเอียดปัญหาที่พบทั้งหมด">
    <div class="quick-card-top">
      <span class="quick-icon quick-icon--slate">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
        </svg>
      </span>
    </div>
    <div class="quick-value">{{ $dashboard['quick_stats']['all_issues_total'] ?? 0 }}</div>
    <div class="quick-label">ปัญหาที่พบทั้งหมด</div>
    <div class="quick-sub">รวมจำนวนปัญหาจากชุดเดียวกับการ์ดปัญหาพบบ่อย</div>
  </a>
</div>

<div class="section-row">
  <div class="card">
    <div class="card-head">
      <h3>สถานะงานวันนี้</h3>
      <div class="filters">
        @foreach ($filterLabels as $filterKey => $filterLabel)
          <a
            class="chip{{ $activeFilter === $filterKey ? ' is-active' : '' }}"
            href="{{ request()->fullUrlWithQuery(['status_filter' => $filterKey]) }}"
          >{{ $filterLabel }}</a>
        @endforeach
      </div>
    </div>

    <div class="muted" style="margin-top: 6px;">กำลังแสดงข้อมูลในกลุ่ม: {{ $filterLabels[$activeFilter] }}</div>

    <div class="status-grid">
      <a class="status-item status-item--link status-item--amber" href="{{ $documentReviewUrl }}" aria-label="ดูรายละเอียดงานรอตรวจสอบ">
        <div class="status-value">{{ $dashboard['status_overview']['pending_review_total'] }}</div>
        <div class="status-label">รอตรวจสอบ</div>
      </a>

      <a class="status-item status-item--link status-item--red" href="{{ $issueReportsUrl }}" aria-label="ดูรายละเอียดงานที่พบปัญหา">
        <div class="status-value">{{ $dashboard['status_overview']['issues_found_total'] }}</div>
        <div class="status-label">พบปัญหา</div>
      </a>

      <a class="status-item status-item--link status-item--blue" href="{{ $todayTasksUrl }}" aria-label="ดูรายละเอียดงานครบกำหนดวันนี้">
        <div class="status-value">{{ $dashboard['status_overview']['due_today_total'] }}</div>
        <div class="status-label">ครบกำหนดวันนี้</div>
      </a>

      <a class="status-item status-item--link status-item--green" href="{{ $issueReportsUrl }}" aria-label="ดูรายละเอียดอัตราการตอบกลับ">
        <div class="status-value">{{ $dashboard['status_overview']['response_rate_percent'] }}%</div>
        <div class="status-label">อัตราการตอบกลับ</div>
        <div class="status-sub muted">({{ $dashboard['status_overview']['response_responded_total'] }}/{{ $dashboard['status_overview']['response_required_total'] }} งานที่ตอบแล้ว)</div>
      </a>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <h3>สภาพอากาศวันนี้</h3>
      <span
        class="chip sky"
        id="weather-condition"
        data-latitude="{{ config('services.open_meteo.latitude') }}"
        data-longitude="{{ config('services.open_meteo.longitude') }}"
        data-timezone="{{ config('services.open_meteo.timezone') }}"
      >-</span>
    </div>

    <div class="weather">
      <div class="weather-temp" id="weather-temperature">-</div>
      <div class="weather-meta">
        <span id="weather-humidity">ความชื้น -</span>
        <span id="weather-wind">ลม -</span>
        <span id="weather-rain">โอกาสฝน -</span>
      </div>
    </div>

    <div class="weather-tip" id="weather-advice">{{ $emptyMessage }}</div>
  </div>
</div>

<div class="dash-section-label">การแจ้งเตือนและกิจกรรม</div>
<div class="dashboard-stack">
  <div class="card">
    <div class="card-head">
      <h3>การแจ้งเตือน</h3>
      <a class="btn ghost" href="/admin/alerts">ดูทั้งหมด</a>
    </div>

    <div class="alert-list big">
      @forelse ($dashboard['urgent_alerts'] as $alert)
        <div class="alert-item">
          <span class="alert-dot {{ $alert['dot_class'] }}"></span>
          <div class="alert-body">
            <div class="alert-item-top">
              <div class="alert-title">{{ $alert['title'] }}</div>
              <span class="chip {{ $alert['chip_class'] }}">{{ $alert['chip_label'] }}</span>
            </div>
            @if ($alert['detail'])
              <div class="alert-detail muted">{{ $alert['detail'] }}</div>
            @endif
            @if ($alert['meta'] !== '')
              <div class="alert-meta">{{ $alert['meta'] }}</div>
            @endif
          </div>
          <a class="btn ghost btn-sm alert-action" href="{{ $alert['detail_url'] }}">ดู</a>
        </div>
      @empty
        <div class="alert-item">
          <div class="alert-body">
            <div class="alert-title">{{ $emptyMessage }}</div>
            <div class="alert-meta">ยังไม่มีการแจ้งเตือนในกลุ่ม {{ $filterLabels[$activeFilter] }}</div>
          </div>
        </div>
      @endforelse
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <h3>กิจกรรมล่าสุด</h3>
      <a class="btn ghost" href="/admin/activity">ดูทั้งหมด</a>
    </div>

    <div class="activity-timeline">
      @forelse ($dashboard['recent_activities'] as $activity)
        <div class="activity-row">
          <span class="activity-time">{{ $activity['time'] }}</span>
          <div class="activity-content">
            <div class="activity-title">{{ $activity['title'] }}</div>
            <div class="activity-subtitle muted">{{ $activity['subtitle'] }}</div>
          </div>
          <span class="activity-tag {{ $activity['tag_class'] }}">{{ $activity['tag_label'] }}</span>
        </div>
      @empty
        <div class="activity-row">
          <span class="activity-time">-</span>
          <div class="activity-content">
            <div class="activity-title">{{ $emptyMessage }}</div>
          </div>
        </div>
      @endforelse
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <h3>งานที่ต้องติดตามวันนี้</h3>
      <a class="btn ghost" href="{{ $todayTasksUrl }}">ดูทั้งหมด</a>
    </div>

    <ul class="task-list">
      @forelse ($dashboard['today_followups'] as $task)
        <li>
          <form method="POST" action="/admin/dashboard-work-items/{{ $task['id'] }}/toggle-followup" class="task-toggle-form">
            @csrf
            <input type="hidden" name="checked" value="{{ $task['checked'] ? 0 : 1 }}">
            <input type="hidden" name="status_filter" value="{{ $activeFilter }}">
            <label class="task-toggle-label">
              <input
                type="checkbox"
                {{ $task['checked'] ? 'checked' : '' }}
                onchange="this.form.submit()"
              >
              <span>{{ $task['title'] }}</span>
            </label>
          </form>
        </li>
      @empty
        <li><label><input type="checkbox" disabled> {{ $emptyMessage }}</label></li>
      @endforelse
    </ul>
  </div>
</div>

<div class="dash-section-label">รายชื่อการประเมิน</div>
<div class="card">
  <div class="card-head">
    <h3>รายชื่อเกษตรกรที่กำลังติดตาม</h3>
    <span class="muted">
      {{ count($dashboard['latest_assessments']) > 0 ? 'แสดง ' . count($dashboard['latest_assessments']) . ' รายการ' : $emptyMessage }}
    </span>
  </div>

  <table class="table" style="margin-top: 8px;">
    <thead>
      <tr>
        <th>ชื่อ</th>
        <th>ความคืบหน้า</th>
        <th>สถานะการประเมิน</th>
        <th>ปัญหาที่พบล่าสุด</th>
        <th>วันที่อัปเดต</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($dashboard['latest_assessments'] as $assessment)
        <tr>
          <td>{{ $assessment['name'] }}</td>
          <td>
            <div class="progress-bar {{ $assessment['status_class'] === 'danger' ? 'red' : ($assessment['status_class'] === 'warning' ? 'orange' : 'green') }}">
              <div class="progress-fill" style="width: {{ $assessment['progress_percent'] }}%">{{ $assessment['progress_percent'] }}%</div>
            </div>
          </td>
          <td><span class="chip {{ $assessment['status_class'] }}">{{ $assessment['status_label'] }}</span></td>
          <td>{{ $assessment['issue_label'] }}</td>
          <td>{{ $assessment['updated_at'] ?: '-' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="5" class="muted" style="text-align: center; padding: 24px;">{{ $emptyMessage }}</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="dash-section-label">สรุปปัญหาและปฏิทิน</div>
<div class="dashboard-bottom">
  <div class="card">
    <div class="card-head">
      <h3>ปัญหาพบบ่อย</h3>
      <a class="btn ghost" href="{{ $issueReportsUrl }}">ดูสถานะนี้ทั้งหมด</a>
    </div>

    <div class="issue-chart{{ count($commonIssues) === 0 ? ' issue-chart--empty' : '' }}">
      <div class="issue-chart__header">
        <span>หมวดปัญหา</span>
        <span>สัดส่วน<br><small class="muted" style="font-weight:400;font-size:10px;">(100%)</small></span>
      </div>

      @if (count($commonIssues) === 0)
        <div class="issue-chart__empty">
          <div class="issue-chart__empty-visual">
            <span></span>
            <span></span>
            <span></span>
          </div>
          <div>
            <div class="issue-chart__empty-title">ยังไม่มีข้อมูลปัญหาที่สรุปผลแล้ว</div>
            <div class="issue-chart__empty-copy">เมื่อเริ่มมีการติดตามและบันทึกปัญหา ระบบจะแสดงอันดับปัญหาที่พบมากที่สุดที่นี่</div>
          </div>
        </div>
      @else
        <div class="issue-chart__list">
          @foreach ($commonIssues as $issue)
            <div class="issue-chart__row">
              <div class="issue-chart__label-wrap">
                <span class="issue-chart__rank">{{ $issue['rank'] }}</span>
                <span class="issue-chart__label">{{ $issue['label'] }}</span>
              </div>
              <div class="issue-chart__bar-track">
                <div class="issue-chart__bar-fill" style="--w: {{ max(16, (int) round(($issue['count'] / $commonIssueMax) * 100)) }}%"></div>
              </div>
              <div class="issue-chart__metric">
                <span class="issue-chart__value">{{ $issue['percent'] }}%</span>
                <span class="issue-chart__ratio">({{ $issue['count'] }}/{{ $issue['total'] }})</span>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <h3>ปฏิทินตรวจแปลง</h3>
      <div class="calendar-controls">
        <select id="calendar-month" class="calendar-select"></select>
        <select id="calendar-year" class="calendar-select"></select>
      </div>
    </div>

    <div class="calendar" data-calendar-events='@json($dashboard["calendar_events"])'>
      <div class="calendar-header">
        <span>อา</span><span>จ</span><span>อ</span><span>พ</span><span>พฤ</span><span>ศ</span><span>ส</span>
      </div>
      <div class="calendar-grid" id="calendar-grid"></div>
      <div class="calendar-selection" id="calendar-selection">
        <div class="calendar-selection__title">เลือกวันที่ที่มีสีเพื่อดูรายละเอียด</div>
        <div class="calendar-selection__meta muted">ระบบจะแสดงสรุปของวันตรวจแปลงหรือวันนัดแก้ไขในช่องนี้</div>
      </div>
      <div class="calendar-legend">
        <span><i class="dot event"></i> วันที่มีงานตรวจแปลง</span>
        <span><i class="dot today"></i> วันนี้</span>
        <span><i class="dot appointment"></i> วันนัดหมายที่ต้องแก้ไข</span>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (function () {
    var calendarRoot = document.querySelector('.calendar');
    var calendarGrid = document.getElementById('calendar-grid');
    var monthSelect = document.getElementById('calendar-month');
    var yearSelect = document.getElementById('calendar-year');
    var selectionBox = document.getElementById('calendar-selection');

    if (!calendarRoot || !calendarGrid || !monthSelect || !yearSelect || !selectionBox) return;

    var monthNames = [
      'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
      'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];

    var now = new Date();
    var currentMonth = now.getMonth();
    var currentYear = now.getFullYear();

    var rawEvents = [];

    try {
      rawEvents = JSON.parse(calendarRoot.dataset.calendarEvents || '[]');
    } catch (error) {
      rawEvents = [];
    }

    function eventMap() {
      return rawEvents.reduce(function (carry, item) {
        if (!item || !item.date) return carry;

        if (!carry[item.date]) {
          carry[item.date] = {
            hasEvent: false,
            hasAppointment: false,
            total: 0,
            appointmentTotal: 0
          };
        }

        carry[item.date].hasEvent = true;
        carry[item.date].total += 1;

        if (item.status === 'needs_fix') {
          carry[item.date].hasAppointment = true;
          carry[item.date].appointmentTotal += 1;
        }

        return carry;
      }, {});
    }

    var eventsByDate = eventMap();

    function pad(value) {
      return String(value).padStart(2, '0');
    }

    function isoDate(year, month, day) {
      return year + '-' + pad(month + 1) + '-' + pad(day);
    }

    function formatThaiDate(year, month, day) {
      return new Date(year, month, day).toLocaleDateString('th-TH', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      });
    }

    function renderSelection(dateKey, eventInfo) {
      var parts = dateKey.split('-');
      var year = Number(parts[0]);
      var month = Number(parts[1]) - 1;
      var day = Number(parts[2]);
      var summary = [];

      if (!eventInfo) {
        selectionBox.innerHTML =
          '<div class="calendar-selection__title">' + formatThaiDate(year, month, day) + '</div>' +
          '<div class="calendar-selection__meta muted">วันที่นี้ยังไม่มีรายการตรวจแปลงหรือวันนัดแก้ไข</div>';
        return;
      }

      if (eventInfo.total > 0) {
        summary.push('มีงานตรวจแปลง ' + eventInfo.total + ' รายการ');
      }

      if (eventInfo.appointmentTotal > 0) {
        summary.push('มีรายการที่ต้องแก้ไข ' + eventInfo.appointmentTotal + ' รายการ');
      }

      selectionBox.innerHTML =
        '<div class="calendar-selection__title">' + formatThaiDate(year, month, day) + '</div>' +
        '<div class="calendar-selection__meta">' + summary.join(' · ') + '</div>';
    }

    function populateControls() {
      monthSelect.innerHTML = monthNames.map(function (monthName, index) {
        return '<option value="' + index + '">' + monthName + '</option>';
      }).join('');

      var yearOptions = [];
      for (var year = currentYear - 3; year <= currentYear + 3; year += 1) {
        yearOptions.push('<option value="' + year + '">' + (year + 543) + '</option>');
      }
      yearSelect.innerHTML = yearOptions.join('');
    }

    function renderCalendar(month, year) {
      var firstDay = new Date(year, month, 1);
      var startOffset = firstDay.getDay();
      var daysInMonth = new Date(year, month + 1, 0).getDate();
      var daysInPreviousMonth = new Date(year, month, 0).getDate();
      var today = new Date();
      var cells = [];

      for (var i = 0; i < 42; i += 1) {
        var dayNumber = i - startOffset + 1;
        var cellMonth = month;
        var cellYear = year;
        var isMuted = false;

        if (dayNumber <= 0) {
          dayNumber = daysInPreviousMonth + dayNumber;
          cellMonth = month - 1;
          if (cellMonth < 0) {
            cellMonth = 11;
            cellYear -= 1;
          }
          isMuted = true;
        } else if (dayNumber > daysInMonth) {
          dayNumber -= daysInMonth;
          cellMonth = month + 1;
          if (cellMonth > 11) {
            cellMonth = 0;
            cellYear += 1;
          }
          isMuted = true;
        }

        var dateKey = isoDate(cellYear, cellMonth, dayNumber);
        var dayClasses = ['calendar-day'];

        if (isMuted) {
          dayClasses.push('is-muted');
        }

        if (
          today.getFullYear() === cellYear &&
          today.getMonth() === cellMonth &&
          today.getDate() === dayNumber
        ) {
          dayClasses.push('is-today');
        }

        if (eventsByDate[dateKey] && eventsByDate[dateKey].hasAppointment) {
          dayClasses.push('has-appointment');
        } else if (eventsByDate[dateKey] && eventsByDate[dateKey].hasEvent) {
          dayClasses.push('has-event');
        }

        dayClasses.push('is-clickable');

        cells.push(
          '<button class="' + dayClasses.join(' ') + '" type="button" data-date="' + dateKey + '">' + dayNumber + '</button>'
        );
      }

      calendarGrid.innerHTML = cells.join('');
      monthSelect.value = String(month);
      yearSelect.value = String(year);

      var activeDate = Object.keys(eventsByDate).find(function (dateKey) {
        return dateKey.indexOf(year + '-' + pad(month + 1)) === 0;
      }) || isoDate(year, month, today.getDate());

      renderSelection(activeDate || null, activeDate ? eventsByDate[activeDate] : null);

      calendarGrid.querySelectorAll('.calendar-day').forEach(function (button) {
        button.addEventListener('click', function () {
          var selectedDate = button.getAttribute('data-date');
          calendarGrid.querySelectorAll('.calendar-day.is-selected').forEach(function (element) {
            element.classList.remove('is-selected');
          });
          button.classList.add('is-selected');
          renderSelection(selectedDate, eventsByDate[selectedDate]);
        });
      });

      if (activeDate) {
        var activeButton = calendarGrid.querySelector('[data-date="' + activeDate + '"]');
        if (activeButton) {
          activeButton.classList.add('is-selected');
        }
      }
    }

    populateControls();
    renderCalendar(currentMonth, currentYear);

    monthSelect.addEventListener('change', function () {
      renderCalendar(Number(monthSelect.value), Number(yearSelect.value));
    });

    yearSelect.addEventListener('change', function () {
      renderCalendar(Number(monthSelect.value), Number(yearSelect.value));
    });
  })();

  (function () {
    var updatedAtEl = document.getElementById('dashboard-updated-at');
    var conditionEl = document.getElementById('weather-condition');
    var temperatureEl = document.getElementById('weather-temperature');
    var humidityEl = document.getElementById('weather-humidity');
    var windEl = document.getElementById('weather-wind');
    var rainEl = document.getElementById('weather-rain');
    var adviceEl = document.getElementById('weather-advice');

    if (!conditionEl || !temperatureEl || !humidityEl || !windEl || !rainEl || !adviceEl) return;

    var latitude = conditionEl.dataset.latitude || '13.7563';
    var longitude = conditionEl.dataset.longitude || '100.5018';
    var timezone = conditionEl.dataset.timezone || 'Asia/Bangkok';

    function formatUpdatedAt(value) {
      if (!value) return '-';

      var date = new Date(value);
      if (Number.isNaN(date.getTime())) return value;

      return date.toLocaleString('th-TH', {
        dateStyle: 'medium',
        timeStyle: 'short',
      });
    }

    function mapCondition(code) {
      if (code === 0) return 'ท้องฟ้าแจ่มใส';
      if ([1, 2].indexOf(code) !== -1) return 'มีเมฆบางส่วน';
      if (code === 3) return 'เมฆมาก';
      if ([45, 48].indexOf(code) !== -1) return 'มีหมอก';
      if ([51, 53, 55, 56, 57].indexOf(code) !== -1) return 'ฝนปรอย';
      if ([61, 63, 65, 66, 67, 80, 81, 82].indexOf(code) !== -1) return 'มีฝน';
      if ([71, 73, 75, 77, 85, 86].indexOf(code) !== -1) return 'หิมะ';
      if ([95, 96, 99].indexOf(code) !== -1) return 'พายุฝนฟ้าคะนอง';
      return 'ไม่ทราบสภาพอากาศ';
    }

    function buildAdvice(condition, rainChance) {
      if (rainChance != null && rainChance >= 70) {
        return 'มีแนวโน้มฝนสูง ควรตรวจระบบระบายน้ำและเลี่ยงงานกลางแจ้ง';
      }

      if (condition.indexOf('พายุ') !== -1) {
        return 'ควรเลื่อนงานลงพื้นที่และเฝ้าระวังความเสียหายจากลมแรง';
      }

      if (condition.indexOf('ฝน') !== -1) {
        return 'ควรเตรียมแผนตรวจแปลงหลังฝนและระวังน้ำขัง';
      }

      if (condition === 'ท้องฟ้าแจ่มใส') {
        return 'อากาศค่อนข้างดี เหมาะกับการวางแผนลงพื้นที่';
      }

      return 'ติดตามสภาพอากาศอย่างต่อเนื่องก่อนวางแผนงานภาคสนาม';
    }

    function renderWeather(weather) {
      if (updatedAtEl) {
        updatedAtEl.textContent = 'อัปเดตล่าสุด: ' + formatUpdatedAt(weather.updated_at);
      }

      conditionEl.textContent = weather.condition || '-';
      temperatureEl.textContent = weather.temperature_celsius != null
        ? Math.round(weather.temperature_celsius) + '°'
        : '-';
      humidityEl.textContent = 'ความชื้น ' + (weather.humidity_percent != null ? weather.humidity_percent + '%' : '-');
      windEl.textContent = 'ลม ' + (weather.wind_kmh != null ? weather.wind_kmh + ' กม./ชม.' : '-');
      rainEl.textContent = 'โอกาสฝน ' + (weather.rain_chance_percent != null ? weather.rain_chance_percent + '%' : '-');
      adviceEl.textContent = weather.advice || 'ยังไม่มีข้อมูล';
    }

    function fetchFromDashboardApi() {
      return fetch('/api/v1/dashboard', {
        headers: {
          'Accept': 'application/json'
        }
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Cannot load dashboard data');
          }

          return response.json();
        })
        .then(function (payload) {
          var data = payload.data || {};
          var weather = data.weather || {};

          renderWeather({
            updated_at: data.updated_at || weather.updated_at || null,
            condition: weather.condition || '',
            temperature_celsius: weather.temperature_celsius,
            humidity_percent: weather.humidity_percent,
            wind_kmh: weather.wind_kmh,
            rain_chance_percent: weather.rain_chance_percent,
            advice: weather.advice || ''
          });
        });
    }

    function fetchFromOpenMeteo() {
      var url = 'https://api.open-meteo.com/v1/forecast'
        + '?latitude=' + encodeURIComponent(latitude)
        + '&longitude=' + encodeURIComponent(longitude)
        + '&timezone=' + encodeURIComponent(timezone)
        + '&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m'
        + '&daily=precipitation_probability_max'
        + '&forecast_days=1';

      return fetch(url, {
        headers: {
          'Accept': 'application/json'
        }
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Cannot load Open-Meteo weather');
          }

          return response.json();
        })
        .then(function (payload) {
          var current = payload.current || {};
          var daily = payload.daily || {};
          var rainChance = daily.precipitation_probability_max && daily.precipitation_probability_max.length
            ? daily.precipitation_probability_max[0]
            : null;
          var condition = mapCondition(Number(current.weather_code));

          renderWeather({
            updated_at: current.time || null,
            condition: condition,
            temperature_celsius: current.temperature_2m != null ? current.temperature_2m : null,
            humidity_percent: current.relative_humidity_2m != null ? current.relative_humidity_2m : null,
            wind_kmh: current.wind_speed_10m != null ? current.wind_speed_10m : null,
            rain_chance_percent: rainChance,
            advice: buildAdvice(condition, rainChance)
          });
        });
    }

    fetchFromOpenMeteo()
      .catch(function () {
        return fetchFromDashboardApi();
      })
      .catch(function () {
        conditionEl.textContent = '-';
        temperatureEl.textContent = '-';
        humidityEl.textContent = 'ความชื้น -';
        windEl.textContent = 'ลม -';
        rainEl.textContent = 'โอกาสฝน -';
        adviceEl.textContent = 'โหลดข้อมูลสภาพอากาศไม่สำเร็จ';
      });
  })();
</script>
@endpush


