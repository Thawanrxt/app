@extends('admin.layout')
@section('title', 'เพิ่มแปลงนา')
@section('content')

<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="/admin/farmer-users/{{ $userRecord->id }}" aria-label="กลับไปหน้าเกษตรกร">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>เพิ่มแปลงนา</h1>
      <p class="muted">{{ $userRecord->full_name }} ({{ $userRecord->username }})</p>
    </div>
  </div>
</div>

<form method="POST" action="/admin/farmer-users/{{ $userRecord->id }}/plots" style="margin-top: 16px;">
  @csrf

  @if ($errors->any())
    <div class="status-banner danger" style="margin-bottom: 16px;">
      <ul style="margin: 0; padding-left: 16px;">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Card 1: ข้อมูลแปลงและแผนปลูก --}}
  <div class="card">
    <h3>ข้อมูลแปลงและแผนปลูก</h3>
    <div style="margin-top: 16px; display: flex; flex-direction: column; gap: 16px;">

      {{-- ชื่อแปลง --}}
      <label>ชื่อแปลง <span class="required-star">*</span>
        <input class="input" type="text" name="plot_name" value="{{ old('plot_name') }}" placeholder="เช่น นาสมศรี" required style="margin-top: 6px;">
      </label>

      {{-- ฤดูกาลปลูก --}}
      <div>
        <div style="font-size: 14px; font-weight: 500; margin-bottom: 10px;">ฤดูกาลปลูก</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
          @foreach (['นาปี', 'นาปรัง 1', 'นาปรัง 2', 'นาปรัง 3'] as $season)
            <label class="radio-card {{ old('season_type', 'นาปี') === $season ? 'is-checked' : '' }}" style="display: flex; align-items: center; gap: 10px; border: 1.5px solid {{ old('season_type', 'นาปี') === $season ? '#166534' : '#e2e8f0' }}; border-radius: 12px; padding: 12px 14px; cursor: pointer; transition: border-color 0.15s;" onclick="selectSeason(this, '{{ $season }}')">
              <input type="radio" name="season_type" value="{{ $season }}" {{ old('season_type', 'นาปี') === $season ? 'checked' : '' }} style="accent-color: #166534; width: 16px; height: 16px; cursor: pointer;">
              <span style="font-size: 14px;">{{ $season }}</span>
            </label>
          @endforeach
        </div>
      </div>

      {{-- เลือกประเภทที่ปลูก --}}
      <label>เลือกประเภทที่ปลูก
        <select class="input" name="planting_type" id="planting-type-select" style="margin-top: 6px;">
          <option value="ข้าว" {{ old('planting_type', 'ข้าว') === 'ข้าว' ? 'selected' : '' }}>ข้าว</option>
          <option value="ข้าวโพด" {{ old('planting_type') === 'ข้าวโพด' ? 'selected' : '' }}>ข้าวโพด</option>
          <option value="อ้อย" {{ old('planting_type') === 'อ้อย' ? 'selected' : '' }}>อ้อย</option>
          <option value="มันสำปะหลัง" {{ old('planting_type') === 'มันสำปะหลัง' ? 'selected' : '' }}>มันสำปะหลัง</option>
          <option value="อื่นๆ" {{ old('planting_type') === 'อื่นๆ' ? 'selected' : '' }}>อื่นๆ</option>
        </select>
      </label>

      {{-- พันธุ์ข้าวที่ปลูก (show only when ข้าว selected) --}}
      <div id="rice-variety-section">
        <label>พันธุ์ข้าวที่ปลูก
          <select class="input" name="rice_id" id="rice-select" style="margin-top: 6px;">
            <option value="">-- เลือกพันธุ์ข้าว --</option>
            @foreach ($riceVarieties as $rice)
              <option
                value="{{ $rice->id }}"
                data-days="{{ $rice->grow_duration_days }}"
                data-season="{{ $rice->recommended_season }}"
                {{ old('rice_id') == $rice->id ? 'selected' : '' }}
              >{{ $rice->name }} ({{ $rice->grow_duration_days }} วัน)</option>
            @endforeach
          </select>
        </label>
      </div>

      {{-- วันที่เริ่มปลูก --}}
      <label>วันที่เริ่มปลูก (พ.ศ. {{ now()->year + 543 }})
        <input
          class="input"
          type="date"
          name="start_date"
          id="start-date"
          value="{{ old('start_date') }}"
          data-buddhist-date
          style="margin-top: 6px;"
        >
      </label>

      {{-- วันที่คาดว่าจะเก็บเกี่ยว --}}
      <label>วันที่คาดว่าจะเก็บเกี่ยว (พ.ศ.)
        <div
          id="harvest-date-display"
          style="margin-top: 6px; padding: 10px 14px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; min-height: 44px; font-size: 14px; color: #64748b; display: flex; align-items: center;"
        >
          <span id="harvest-date-text" style="color: #6b7280;">-- จะคำนวณอัตโนมัติเมื่อเลือกพันธุ์ข้าวและวันปลูก --</span>
        </div>
      </label>

      {{-- พิกัดแปลงนา --}}
      <div>
        <div style="font-size: 14px; font-weight: 500; margin-bottom: 10px;">พิกัดแปลงนา <span style="font-weight: 400; color: #6b7280;">(กำหนดเองหรือ GPS)</span></div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
          <input
            class="input"
            type="number"
            name="latitude"
            id="latitude-input"
            value="{{ old('latitude') }}"
            step="any"
            placeholder="13.7563"
          >
          <input
            class="input"
            type="number"
            name="longitude"
            id="longitude-input"
            value="{{ old('longitude') }}"
            step="any"
            placeholder="100.5018"
          >
        </div>
        <button type="button" class="btn ghost" id="gps-btn" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>
          </svg>
          ใช้ตำแหน่งปัจจุบันของคุณ
        </button>
        <p id="gps-status" class="muted" style="font-size: 12px; margin-top: 6px; display: none;"></p>
      </div>

    </div>
  </div>

  {{-- Card 2: พื้นที่และที่ตั้งแปลง --}}
  <div class="card" style="margin-top: 16px;">
    <h3>พื้นที่ปลูก</h3>
    <div style="margin-top: 16px; display: flex; flex-direction: column; gap: 16px;">

      <div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
          <label>
            <input class="input area-field" name="area_rai" id="area-rai" type="number" min="0" placeholder="0" value="{{ old('area_rai', 0) }}">
            <span style="font-size: 12px; color: #6b7280; margin-top: 4px; display: block; text-align: center;">ไร่</span>
          </label>
          <label>
            <input class="input area-field" name="area_ngan" id="area-ngan" type="number" min="0" max="3" placeholder="0" value="{{ old('area_ngan', 0) }}">
            <span style="font-size: 12px; color: #6b7280; margin-top: 4px; display: block; text-align: center;">งาน</span>
          </label>
          <label>
            <input class="input area-field" name="area_sq_wa" id="area-sq-wa" type="number" min="0" max="99" placeholder="0" value="{{ old('area_sq_wa', 0) }}">
            <span style="font-size: 12px; color: #6b7280; margin-top: 4px; display: block; text-align: center;">ตร.ว.</span>
          </label>
        </div>
        <div style="margin-top: 8px; display: grid; grid-template-columns: 1fr 3fr; gap: 8px; align-items: center;">
          <label>
            <input class="input" id="area-sqm-display" type="number" placeholder="0" value="{{ old('area_sqm', 0) }}" readonly style="background: #f8fafc; cursor: default;">
            <span style="font-size: 12px; color: #6b7280; margin-top: 4px; display: block; text-align: center;">ตร.ม.</span>
          </label>
          <span id="area-summary" class="muted" style="font-size: 13px; padding-top: 4px;"></span>
        </div>
      </div>

    </div>
  </div>

  {{-- Card 3: ที่ตั้งแปลง (optional) --}}
  <div class="card" style="margin-top: 16px;">
    <h3>ที่ตั้งแปลง <span class="muted" style="font-weight: 400; font-size: 14px;">(ไม่บังคับ)</span></h3>
    <div class="form-grid" style="margin-top: 12px;">

      <label>จังหวัด
        <select class="input" name="province" id="province-select" data-selected="{{ old('province') }}">
          <option value="">{{ old('province') ? 'กำลังโหลด...' : 'เลือกจังหวัด' }}</option>
        </select>
      </label>

      <label>อำเภอ/เขต
        <select class="input" name="district" id="district-select" data-selected="{{ old('district') }}">
          <option value="">เลือกอำเภอ/เขต</option>
        </select>
      </label>

      <label>ตำบล/แขวง
        <select class="input" name="subdistrict" id="subdistrict-select" data-selected="{{ old('subdistrict') }}">
          <option value="">เลือกตำบล/แขวง</option>
        </select>
      </label>

      <label>รหัสไปรษณีย์
        <input class="input" type="text" name="postcode" value="{{ old('postcode') }}" placeholder="เช่น 10200" maxlength="10">
      </label>

    </div>
  </div>

  <div style="display: flex; gap: 12px; margin-top: 24px;">
    <button class="btn primary" type="submit" style="flex: 1; min-height: 48px; font-size: 16px;">ยืนยันการสร้างแผน</button>
    <a class="btn ghost" href="/admin/farmer-users/{{ $userRecord->id }}" style="padding: 0 20px; min-height: 48px; display: flex; align-items: center;">ยกเลิก</a>
  </div>

</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var riceSelect       = document.getElementById('rice-select');
  var startDateInput   = document.getElementById('start-date');
  var harvestText      = document.getElementById('harvest-date-text');
  var harvestDisplay   = document.getElementById('harvest-date-display');
  var plantingType     = document.getElementById('planting-type-select');
  var riceSection      = document.getElementById('rice-variety-section');
  var raiInput         = document.getElementById('area-rai');
  var nganInput        = document.getElementById('area-ngan');
  var sqWaInput        = document.getElementById('area-sq-wa');
  var sqmDisplay       = document.getElementById('area-sqm-display');
  var areaSummary      = document.getElementById('area-summary');
  var gpsBtn           = document.getElementById('gps-btn');
  var gpsStatus        = document.getElementById('gps-status');
  var latInput         = document.getElementById('latitude-input');
  var lonInput         = document.getElementById('longitude-input');

  var thaiMonths = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];

  // ---- season radio card highlight ----
  window.selectSeason = function (labelEl, season) {
    document.querySelectorAll('.radio-card').forEach(function (el) {
      el.style.borderColor = '#e2e8f0';
      el.classList.remove('is-checked');
    });
    labelEl.style.borderColor = '#166534';
    labelEl.classList.add('is-checked');
  };

  document.querySelectorAll('.radio-card input[type="radio"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
      var label = radio.closest('.radio-card');
      if (label) window.selectSeason(label, radio.value);
    });
  });

  // ---- show/hide rice variety based on planting type ----
  function toggleRiceSection() {
    riceSection.style.display = plantingType.value === 'ข้าว' ? '' : 'none';
    if (plantingType.value !== 'ข้าว') {
      riceSelect.value = '';
      harvestText.textContent = '-- จะคำนวณอัตโนมัติเมื่อเลือกพันธุ์ข้าวและวันปลูก --';
      harvestText.style.color = '#6b7280';
    }
  }

  plantingType.addEventListener('change', function () {
    toggleRiceSection();
    updateHarvest();
  });

  // ---- harvest date calculation ----
  function updateHarvest() {
    var option  = riceSelect ? riceSelect.options[riceSelect.selectedIndex] : null;
    var days    = option ? parseInt(option.dataset.days) : 0;
    var dateVal = startDateInput.value;

    if (!days || !dateVal) {
      harvestText.textContent = '-- จะคำนวณอัตโนมัติเมื่อเลือกพันธุ์ข้าวและวันปลูก --';
      harvestText.style.color = '#6b7280';
      harvestDisplay.style.background = '#f8fafc';
      return;
    }

    var start   = new Date(dateVal);
    var harvest = new Date(start);
    harvest.setDate(harvest.getDate() + days);

    var d  = harvest.getDate();
    var m  = thaiMonths[harvest.getMonth()];
    var y  = harvest.getFullYear() + 543;

    harvestText.textContent = d + ' ' + m + ' ' + y + '  (อีก ' + days + ' วัน)';
    harvestText.style.color = '#166534';
    harvestDisplay.style.background = '#f0fdf4';
    harvestDisplay.style.borderColor = '#bbf7d0';
  }

  // ---- suggest season from rice variety ----
  function suggestSeason() {
    if (!riceSelect) return;
    var option = riceSelect.options[riceSelect.selectedIndex];
    var season = option ? option.dataset.season : '';
    if (!season) return;

    var checked = document.querySelector('input[name="season_type"]:checked');
    if (checked && checked.value !== 'นาปี') return; // don't overwrite if user already picked

    var match = document.querySelector('input[name="season_type"][value="' + season + '"]');
    if (match) {
      match.click();
    }
  }

  if (riceSelect) {
    riceSelect.addEventListener('change', function () {
      suggestSeason();
      updateHarvest();
    });
  }

  startDateInput.addEventListener('change', updateHarvest);

  // ---- area calculation ----
  function updateArea() {
    var rai   = parseInt(raiInput.value)  || 0;
    var ngan  = parseInt(nganInput.value) || 0;
    var sqWa  = parseInt(sqWaInput.value) || 0;
    var sqM   = (rai * 1600) + (ngan * 400) + (sqWa * 4);

    sqmDisplay.value = sqM || 0;

    if (sqM === 0) {
      areaSummary.textContent = '';
    } else {
      areaSummary.textContent = 'รวม ≈ ' + sqM.toLocaleString() + ' ตร.ม.  (' + rai + ' ไร่ ' + ngan + ' งาน ' + sqWa + ' ตร.ว.)';
    }
  }

  raiInput.addEventListener('input', updateArea);
  nganInput.addEventListener('input', updateArea);
  sqWaInput.addEventListener('input', updateArea);

  // ---- GPS button ----
  gpsBtn.addEventListener('click', function () {
    if (!navigator.geolocation) {
      gpsStatus.textContent = 'เบราว์เซอร์นี้ไม่รองรับ GPS';
      gpsStatus.style.display = 'block';
      return;
    }

    gpsBtn.disabled = true;
    gpsBtn.textContent = 'กำลังระบุตำแหน่ง...';
    gpsStatus.textContent = '';
    gpsStatus.style.display = 'none';

    navigator.geolocation.getCurrentPosition(
      function (pos) {
        latInput.value = pos.coords.latitude.toFixed(6);
        lonInput.value = pos.coords.longitude.toFixed(6);
        gpsBtn.disabled = false;
        gpsBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg> ใช้ตำแหน่งปัจจุบันของคุณ';
        gpsStatus.textContent = 'ระบุตำแหน่งสำเร็จ: ' + latInput.value + ', ' + lonInput.value;
        gpsStatus.style.color = '#166534';
        gpsStatus.style.display = 'block';
      },
      function (err) {
        gpsBtn.disabled = false;
        gpsBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg> ใช้ตำแหน่งปัจจุบันของคุณ';
        gpsStatus.textContent = 'ไม่สามารถระบุตำแหน่งได้: ' + err.message;
        gpsStatus.style.color = '#dc2626';
        gpsStatus.style.display = 'block';
      },
      { timeout: 10000, enableHighAccuracy: true }
    );
  });

  // ---- init ----
  toggleRiceSection();
  updateArea();
  updateHarvest();
});
</script>
@endsection
