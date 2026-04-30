@extends('admin.layout')

@section('title', 'การแจ้งเตือน')

@section('content')
<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="/admin" aria-label="กลับไปหน้าแดชบอร์ด">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>การแจ้งเตือน</h1>
      <p class="muted">รวมรายการแจ้งเตือนที่ต้องติดตามจากข้อมูลจริงในระบบ</p>
    </div>
  </div>
  <div class="page-actions">
    <form method="POST" action="/admin/alerts/mark-read" style="display:inline;">
      @csrf
      <button class="btn ghost" type="submit" @disabled($alerts->isEmpty())>ทำเครื่องหมายอ่านแล้ว</button>
    </form>
    <a class="btn primary" href="{{ url('/admin/report/export/print?' . http_build_query(array_filter(['q' => $query]))) }}" target="_blank" rel="noopener">ส่งออก</a>
  </div>
</div>

<div class="card alert-page">
  <div class="card-head">
    <h3>รายการแจ้งเตือนล่าสุด</h3>
    <span class="muted" id="alerts-count">แสดง {{ $alerts->count() }} รายการ</span>
  </div>

  <div class="prep-filters" style="margin-top: 12px; display:flex; flex-wrap:nowrap; align-items:center; gap:12px;">
    <div class="search-field" style="display:flex; align-items:center; gap:12px; flex:1 1 560px; max-width: 760px;">
      <input
        class="search-input"
        id="alerts-search"
        type="text"
        value="{{ $query }}"
        placeholder="ค้นหารายการแจ้งเตือน"
        aria-label="ค้นหารายการแจ้งเตือน"
        style="flex:1 1 auto;"
      >
    </div>
    <div class="filter-group" style="display:block; flex:0 0 280px;">
      <select class="input" id="alerts-search-scope" aria-label="เลือกประเภทข้อมูลที่ต้องการค้นหา">
        <option value="all" @selected($scope === 'all')>ค้นหาทั้งหมด</option>
        <option value="title" @selected($scope === 'title')>หัวข้อแจ้งเตือน</option>
        <option value="detail" @selected($scope === 'detail')>รายละเอียด</option>
        <option value="report" @selected($scope === 'report')>แจ้งเตือนรายงานปัญหา</option>
        <option value="tag" @selected($scope === 'tag')>สถานะ</option>
        <option value="action" @selected($scope === 'action')>ปุ่มดำเนินการ</option>
      </select>
    </div>
    <button class="search-btn" type="button" aria-label="ค้นหา" style="flex:0 0 auto;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="11" cy="11" r="7"></circle>
        <path d="M20 20l-3.5-3.5"></path>
      </svg>
    </button>
  </div>

  <p class="muted" style="margin-top: 10px;">
    ค้นหาจากหัวข้อ รายละเอียด ประเภทรายงานปัญหา สถานะ หรือข้อความในปุ่มดำเนินการได้ทันที
  </p>

  <div class="alert-list big" id="alerts-list">
    @forelse ($alerts as $alert)
      @php
        $reportTopic = str_contains((string) $alert['title'], 'แจ้งเตือนรายงานปัญหา')
          ? 'แจ้งเตือนรายงานปัญหา'
          : '';
        $searchAll = implode(' ', array_filter([
          $alert['title'],
          $alert['detail'],
          $reportTopic,
          $alert['chip_label'],
          $alert['meta'],
          $alert['detail_label'],
        ]));
      @endphp
      <div
        class="alert-item"
        data-alert-item
        data-search-title="{{ \Illuminate\Support\Str::lower($alert['title']) }}"
        data-search-detail="{{ \Illuminate\Support\Str::lower($alert['detail']) }}"
        data-search-report="{{ \Illuminate\Support\Str::lower($reportTopic) }}"
        data-search-tag="{{ \Illuminate\Support\Str::lower($alert['chip_label']) }}"
        data-search-action="{{ \Illuminate\Support\Str::lower($alert['detail_label']) }}"
        data-search-all="{{ \Illuminate\Support\Str::lower($searchAll) }}"
      >
        <span class="alert-dot {{ $alert['dot_class'] }}"></span>
        <div class="alert-body">
          <div class="alert-title">{{ $alert['title'] }}</div>
          <div class="muted">{{ $alert['detail'] }}</div>
          <div class="alert-meta">
            @if ($reportTopic !== '')
              <span class="chip info">{{ $reportTopic }}</span>
            @endif
            <span class="chip {{ $alert['chip_class'] }}">{{ $alert['chip_label'] }}</span>
            <span>{{ $alert['meta'] }}</span>
          </div>
        </div>
        <a class="btn ghost" href="{{ $alert['detail_url'] }}">{{ $alert['detail_label'] }}</a>
      </div>
    @empty
      <div class="alert-item">
        <div class="alert-body">
          <div class="alert-title">ยังไม่มีรายการแจ้งเตือน</div>
          <div class="muted">เมื่อมีรายการที่ต้องติดตาม ระบบจะแสดงที่หน้านี้</div>
        </div>
      </div>
    @endforelse
  </div>

  <div class="muted" id="alerts-empty" style="display:none; text-align:center; padding: 24px 0 8px;">
    ไม่พบรายการแจ้งเตือนที่ตรงกับคำค้น
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('alerts-search');
    var scopeSelect = document.getElementById('alerts-search-scope');
    var countEl = document.getElementById('alerts-count');
    var emptyEl = document.getElementById('alerts-empty');
    var items = Array.from(document.querySelectorAll('[data-alert-item]'));

    function getHaystack(item, scope) {
      if (scope === 'title') return item.getAttribute('data-search-title') || '';
      if (scope === 'detail') return item.getAttribute('data-search-detail') || '';
      if (scope === 'report') return item.getAttribute('data-search-report') || '';
      if (scope === 'tag') return item.getAttribute('data-search-tag') || '';
      if (scope === 'action') return item.getAttribute('data-search-action') || '';
      return item.getAttribute('data-search-all') || '';
    }

    function updateAlerts() {
      var keyword = (searchInput.value || '').trim();
      var scope = scopeSelect ? scopeSelect.value : 'all';
      var visibleCount = 0;
      var searchUtils = window.SrpSearchUtils;
      var priorityScopes = scope === 'all'
        ? ['title', 'detail', 'report', 'tag', 'action']
        : [scope];
      var activeScope = priorityScopes.find(function (scopeName) {
        return items.some(function (item) {
          var haystack = getHaystack(item, scopeName);
          return keyword === '' || (searchUtils ? searchUtils.matchesFromStart(haystack, keyword) : haystack.toLowerCase().indexOf(keyword.toLowerCase()) === 0);
        });
      }) || priorityScopes[0];

      items.forEach(function (item) {
        var haystack = getHaystack(item, activeScope);
        var matched = keyword === '' || (searchUtils ? searchUtils.matchesFromStart(haystack, keyword) : haystack.toLowerCase().indexOf(keyword.toLowerCase()) === 0);
        item.style.display = matched ? '' : 'none';
        if (matched) {
          visibleCount += 1;
        }
      });

      if (countEl) {
        countEl.textContent = 'แสดง ' + visibleCount + ' รายการ';
      }

      if (emptyEl) {
        emptyEl.style.display = visibleCount === 0 ? 'block' : 'none';
      }
    }

    if (searchInput) {
      searchInput.addEventListener('input', updateAlerts);
    }

    if (scopeSelect) {
      scopeSelect.addEventListener('change', updateAlerts);
    }

    updateAlerts();
  });
</script>
@endsection
