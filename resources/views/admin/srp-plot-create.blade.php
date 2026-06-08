@extends('admin.layout')
@section('title', 'เพิ่มแปลงใหม่')
@section('content')
<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="/admin/srp/farmers/{{ $farmer['slug'] }}" aria-label="กลับ">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>เพิ่มแปลงใหม่</h1>
      <p class="muted">สร้างแปลงและแผนปลูกให้กับ <strong>{{ $farmer['name'] }}</strong></p>
    </div>
  </div>
</div>

@if ($errors->any())
  <div class="card" style="margin-top:16px; border-color:#fca5a5; background:#fff1f2;">
    <strong>กรอกข้อมูลไม่ครบ</strong>
    <ul style="margin:8px 0 0 18px;">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form method="POST" action="/admin/srp/farmers/{{ $farmer['slug'] }}/plots">
  @csrf

  {{-- ข้อมูลแปลง --}}
  <div class="card" style="margin-top:16px;">
    <h3 style="margin-bottom:16px;">ข้อมูลแปลง</h3>
    <div class="form-grid">

      <label style="grid-column:1/-1;">ชื่อแปลง <span style="color:#dc2626;">*</span>
        <input class="input" name="plot_name" type="text"
               placeholder="เช่น นาสมศรี"
               value="{{ old('plot_name') }}" required>
      </label>

      <label style="grid-column:1/-1;">ฤดูกาลปลูก <span style="color:#dc2626;">*</span>
        <div style="display:flex; flex-wrap:wrap; gap:16px; margin-top:8px;">
          @foreach (['นาปี','นาปรัง 1','นาปรัง 2','นาปรัง 3'] as $season)
            <label style="display:flex; align-items:center; gap:6px; font-weight:normal; cursor:pointer;">
              <input type="radio" name="season_type" value="{{ $season }}"
                     {{ old('season_type', 'นาปี') === $season ? 'checked' : '' }} required>
              {{ $season }}
            </label>
          @endforeach
        </div>
      </label>

      <label>เลือกประเภทที่ปลูก <span style="color:#dc2626;">*</span>
        <select class="input" name="crop_type" required>
          <option value="">-- เลือกประเภท --</option>
          @foreach (['ข้าว','ข้าวโพด','อ้อย','มันสำปะหลัง','อื่นๆ'] as $crop)
            <option value="{{ $crop }}" {{ old('crop_type','ข้าว') === $crop ? 'selected' : '' }}>{{ $crop }}</option>
          @endforeach
        </select>
      </label>

      <label>พันธุ์ข้าวที่ปลูก <span style="color:#dc2626;">*</span>
        <select class="input" name="rice_id" required>
          <option value="">-- เลือกพันธุ์ข้าว --</option>
          @foreach ($riceVarieties as $variety)
            <option value="{{ $variety->id }}" {{ old('rice_id') === (string)$variety->id ? 'selected' : '' }}>
              {{ $variety->name }}
            </option>
          @endforeach
        </select>
      </label>

      <label>วันที่เริ่มปลูก (พ.ศ.) <span style="color:#dc2626;">*</span>
        <input class="input" name="start_date" type="date"
               value="{{ old('start_date', date('Y-m-d')) }}" required>
      </label>

      <label>วันที่คาดว่าจะเก็บเกี่ยว (พ.ศ.)
        <input class="input" name="expected_harvest_date" type="date"
               value="{{ old('expected_harvest_date') }}">
      </label>

    </div>
  </div>

  {{-- พิกัดแปลงนา --}}
  <div class="card" style="margin-top:16px;">
    <h3 style="margin-bottom:16px;">พิกัดแปลงนา (GPS)</h3>
    <div class="form-grid">
      <label>ละติจูด
        <input class="input" name="latitude" id="lat-input" type="number" step="any"
               placeholder="เช่น 13.7563" value="{{ old('latitude') }}">
      </label>
      <label>ลองจิจูด
        <input class="input" name="longitude" id="lng-input" type="number" step="any"
               placeholder="เช่น 100.5018" value="{{ old('longitude') }}">
      </label>
      <div style="grid-column:1/-1;">
        <button type="button" class="btn ghost" id="use-location-btn"
                style="display:flex; align-items:center; gap:6px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>
          </svg>
          ใช้ตำแหน่งปัจจุบันของคุณ
        </button>
      </div>
    </div>
  </div>

  {{-- พื้นที่ปลูก --}}
  <div class="card" style="margin-top:16px;">
    <h3 style="margin-bottom:16px;">พื้นที่ปลูก</h3>
    <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
      <label style="flex:1; min-width:80px;">ไร่
        <input class="input" name="area_rai" type="number" min="0" placeholder="0"
               value="{{ old('area_rai', 0) }}">
      </label>
      <label style="flex:1; min-width:80px;">งาน
        <input class="input" name="area_ngan" type="number" min="0" placeholder="0"
               value="{{ old('area_ngan', 0) }}">
      </label>
      <label style="flex:1; min-width:80px;">ตร.ว.
        <input class="input" name="area_sq_wa" type="number" min="0" placeholder="0"
               value="{{ old('area_sq_wa', 0) }}">
      </label>
      <label style="flex:1; min-width:80px;">ตร.ม.
        <input class="input" name="area_sq_meter" type="number" min="0" placeholder="0"
               value="{{ old('area_sq_meter', 0) }}">
      </label>
    </div>
  </div>

  <div class="footer-actions" style="margin-top:20px;">
    <button class="btn primary" type="submit">ยืนยันการสร้างแปลง</button>
    <a href="/admin/srp/farmers/{{ $farmer['slug'] }}" class="btn ghost">ยกเลิก</a>
  </div>
</form>

<script>
  document.getElementById('use-location-btn').addEventListener('click', function () {
    if (!navigator.geolocation) {
      alert('เบราว์เซอร์ไม่รองรับ GPS');
      return;
    }
    this.textContent = 'กำลังดึงตำแหน่ง...';
    var btn = this;
    navigator.geolocation.getCurrentPosition(function (pos) {
      document.getElementById('lat-input').value = pos.coords.latitude.toFixed(7);
      document.getElementById('lng-input').value = pos.coords.longitude.toFixed(7);
      btn.textContent = '✓ ดึงตำแหน่งสำเร็จ';
    }, function () {
      btn.textContent = 'ใช้ตำแหน่งปัจจุบันของคุณ';
      alert('ไม่สามารถดึงตำแหน่งได้ กรุณากรอกพิกัดด้วยตนเอง');
    });
  });
</script>
@endsection
