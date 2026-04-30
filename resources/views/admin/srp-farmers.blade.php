@extends('admin.layout')
@section('title', 'ข้อมูลเกษตรกร')
@section('content')
<div class="page-head">
  <div>
    <h1>ข้อมูลเกษตรกรในความดูแล</h1>
    <p class="muted">ดูรายชื่อเกษตรกรที่แอดมินรับผิดชอบ พร้อมข้อมูลโปรไฟล์ ทะเบียน ผู้ดูแล และความคืบหน้าจากแปลงในแอพ</p>
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
    <span class="srp-summary-card__label">เกษตรกรทั้งหมด</span>
    <strong class="srp-summary-card__value">{{ $summary['all_farmers'] }}</strong>
    <span class="muted">รายชื่อเกษตรกรที่อยู่ในความดูแลของคุณ</span>
  </div>
  <div class="card srp-summary-card">
    <span class="srp-summary-card__label">มีผู้ดูแลหลัก</span>
    <strong class="srp-summary-card__value">{{ $summary['with_primary_admin'] }}</strong>
    <span class="muted">เกษตรกรที่มีการกำหนดผู้ดูแลหลักไว้แล้ว</span>
  </div>
  <div class="card srp-summary-card">
    <span class="srp-summary-card__label">มีความเคลื่อนไหวในแอพ</span>
    <strong class="srp-summary-card__value">{{ $summary['with_app_activity'] }}</strong>
    <span class="muted">เกษตรกรที่มีการบันทึกกิจกรรมแปลงจากแอพ</span>
  </div>
  <div class="card srp-summary-card">
    <span class="srp-summary-card__label">แปลงทั้งหมด</span>
    <strong class="srp-summary-card__value">{{ $summary['all_plots'] }}</strong>
    <span class="muted">จำนวนแปลงที่ผูกอยู่กับเกษตรกรในความดูแล</span>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  @php
    $provinceOptions = $farmers->pluck('province')->filter(fn ($value) => filled($value) && $value !== '-')->unique()->sort()->values();
    $districtOptions = $farmers->pluck('district')->filter(fn ($value) => filled($value) && $value !== '-')->unique()->sort()->values();
    $provinceDistrictMap = $farmers
      ->filter(fn ($farmer) => filled($farmer['province']) && $farmer['province'] !== '-' && filled($farmer['district']) && $farmer['district'] !== '-')
      ->groupBy('province')
      ->map(fn ($rows) => $rows->pluck('district')->unique()->sort()->values()->all());
  @endphp

  <div class="search-row srp-search-toolbar" style="display:flex; align-items:center; gap:12px; flex-wrap:nowrap;">
    <div class="search-field" style="display:flex; align-items:center; gap:8px; flex:2.2 1 0; min-width:0;">
      <input
        class="search-input"
        id="farmer-care-search"
        type="search"
        placeholder="ค้นหาชื่อเกษตรกร รหัสทะเบียน เบอร์โทร จังหวัด อำเภอ หรือชื่อผู้ดูแล"
        aria-label="ค้นหาข้อมูลเกษตรกร"
        style="flex:1 1 auto;"
      >
    </div>

    <select class="search-input" id="farmer-care-province" style="width: 220px; flex:1 1 0; min-width:180px;">
      <option value="">ทุกจังหวัด</option>
      @foreach ($provinceOptions as $province)
        <option value="{{ $province }}">{{ $province }}</option>
      @endforeach
    </select>

    <select class="search-input" id="farmer-care-district" style="width: 220px; flex:1 1 0; min-width:180px;">
      <option value="">ทุกอำเภอ/เขต</option>
      @foreach ($districtOptions as $district)
        <option value="{{ $district }}">{{ $district }}</option>
      @endforeach
    </select>

    <button class="search-btn" type="button" aria-label="ค้นหา" style="flex:0 0 auto; margin:0;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="11" cy="11" r="7"></circle>
        <path d="M20 20l-3.5-3.5"></path>
      </svg>
    </button>
  </div>
</div>

<div class="card" style="margin-top: 16px;">
  <div class="section-head">
    <div>
      <h3>รายชื่อเกษตรกร</h3>
      <p class="muted">กดดูรายละเอียดเพื่อเปิดข้อมูลโปรไฟล์ ทะเบียน ผู้ดูแล และรายการแปลงทั้งหมดของเกษตรกรแต่ละราย</p>
    </div>
    <span class="tag" data-search-count="farmers">{{ $farmers->count() }} รายชื่อ</span>
  </div>

  @if ($farmers->isEmpty())
    <div class="empty-state" style="margin-top: 16px;">ยังไม่พบเกษตรกรในความดูแล</div>
  @else
    <div class="srp-table-wrap" style="margin-top: 16px;" data-search-container="farmers">
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
            <tr
              data-search-item
              data-search-group="farmers"
              data-province="{{ $farmer['province'] }}"
              data-district="{{ $farmer['district'] }}"
              data-search-name="{{ strtolower($farmer['name']) }}"
              data-search-code="{{ strtolower($farmer['farmer_code']) }}"
              data-search-phone="{{ strtolower($farmer['phone']) }}"
              data-search-province="{{ strtolower($farmer['province']) }}"
              data-search-district="{{ strtolower($farmer['district']) }}"
              data-search-primary-admin="{{ strtolower($farmer['primary_admin_name']) }}"
              data-search-secondary-admin="{{ strtolower(implode(' ', $farmer['secondary_admins'])) }}"
              data-search-text="{{ strtolower(implode(' ', [
                $farmer['name'],
                $farmer['farmer_code'],
                $farmer['phone'],
                $farmer['province'],
                $farmer['district'],
                $farmer['primary_admin_name'],
                implode(' ', $farmer['secondary_admins']),
              ])) }}"
            >
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
    <div class="empty-state is-search-hidden" style="margin-top: 16px;" data-search-empty="farmers">ไม่พบเกษตรกรจากคำค้นนี้</div>
  @endif
</div>
@endsection

@push('scripts')
<script>
  (function () {
    var input = document.getElementById('farmer-care-search');
    var provinceSelect = document.getElementById('farmer-care-province');
    var districtSelect = document.getElementById('farmer-care-district');
    var provinceDistrictMap = @json($provinceDistrictMap);
    if (!input || !provinceSelect || !districtSelect) return;

    function updateGroup(groupName) {
      var items = Array.from(document.querySelectorAll('[data-search-item][data-search-group="' + groupName + '"]'));
      var container = document.querySelector('[data-search-container="' + groupName + '"]');
      var empty = document.querySelector('[data-search-empty="' + groupName + '"]');
      var counter = document.querySelector('[data-search-count="' + groupName + '"]');
      var visibleCount = items.filter(function (item) {
        return !item.classList.contains('is-search-hidden');
      }).length;

      if (container) {
        container.classList.toggle('is-search-hidden', visibleCount === 0);
      }

      if (empty) {
        empty.classList.toggle('is-search-hidden', visibleCount !== 0);
      }

      if (counter) {
        counter.textContent = visibleCount + ' รายชื่อ';
      }
    }

    function syncDistrictOptions() {
      var selectedProvince = provinceSelect.value;
      var currentDistrict = districtSelect.value;
      var districts = selectedProvince && provinceDistrictMap[selectedProvince]
        ? provinceDistrictMap[selectedProvince]
        : @json($districtOptions->all());

      districtSelect.innerHTML = '';

      var defaultOption = document.createElement('option');
      defaultOption.value = '';
      defaultOption.textContent = 'ทุกอำเภอ/เขต';
      districtSelect.appendChild(defaultOption);

      districts.forEach(function (district) {
        var option = document.createElement('option');
        option.value = district;
        option.textContent = district;
        districtSelect.appendChild(option);
      });

      if (currentDistrict && districts.indexOf(currentDistrict) !== -1) {
        districtSelect.value = currentDistrict;
      } else {
        districtSelect.value = '';
      }
    }

    function applySearch() {
      var term = input.value.trim();
      var hasTerm = term.length > 0;
      var selectedProvince = provinceSelect.value;
      var selectedDistrict = districtSelect.value;
      var searchUtils = window.SrpSearchUtils;
      var searchFields = [
        'data-search-name',
        'data-search-code',
        'data-search-phone',
        'data-search-province',
        'data-search-district',
        'data-search-primary-admin',
        'data-search-secondary-admin'
      ];
      var items = Array.from(document.querySelectorAll('[data-search-item]'));
      var activeField = searchFields.find(function (fieldName) {
        return items.some(function (item) {
          var value = item.getAttribute(fieldName) || '';
          return hasTerm && searchUtils ? searchUtils.matchesFromStart(value, term) : value.toLowerCase().indexOf(term.toLowerCase()) === 0;
        });
      }) || searchFields[0];

      items.forEach(function (item) {
        var haystack = hasTerm ? (item.getAttribute(activeField) || '') : (item.getAttribute('data-search-text') || '');
        var province = item.getAttribute('data-province') || '';
        var district = item.getAttribute('data-district') || '';
        var matchesTerm = !hasTerm || (searchUtils ? searchUtils.matchesFromStart(haystack, term) : haystack.toLowerCase().indexOf(term.toLowerCase()) === 0);
        var matchesProvince = !selectedProvince || province === selectedProvince;
        var matchesDistrict = !selectedDistrict || district === selectedDistrict;
        item.classList.toggle('is-search-hidden', !(matchesTerm && matchesProvince && matchesDistrict));
      });

      updateGroup('farmers');
    }

    input.addEventListener('input', applySearch);
    provinceSelect.addEventListener('change', function () {
      syncDistrictOptions();
      applySearch();
    });
    districtSelect.addEventListener('change', applySearch);

    syncDistrictOptions();
    applySearch();
  })();
</script>
@endpush
