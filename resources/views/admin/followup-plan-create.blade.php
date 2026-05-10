@extends('admin.layout')
@section('title', 'เพิ่มแผนงานติดตาม')
@section('content')
@php
  $selectedFarmer = old('farmer_user_id', $farmers[0]['user_id'] ?? '');
  $selectedPlot = old('plot_id', $farmers[0]['plots'][0]['plot_id'] ?? '');
@endphp

<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="/admin" aria-label="กลับไปหน้าแดชบอร์ด">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>เพิ่มแผนงานติดตาม</h1>
      <p class="muted">กำหนดวันติดตามเกษตรกรและแปลงล่วงหน้า เพื่อให้ขึ้นในปฏิทินและรายการงานที่ต้องติดตาม</p>
    </div>
  </div>
</div>

@if ($errors->any())
  <div class="card" style="margin-top: 16px; border-color: #fca5a5; background: #fff1f2;">
    <strong>บันทึกแผนงานไม่สำเร็จ</strong>
    <ul style="margin: 8px 0 0 18px;">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

@if (session('success'))
  <div class="card" style="margin-top: 16px; border-color: #86efac; background: #f0fdf4;">
    <strong>{{ session('success') }}</strong>
  </div>
@endif

<form method="POST" action="/admin/followup-plans" style="margin-top: 16px;">
  @csrf

  <div class="card" style="border-color: #bfdbfe; background: #eff6ff;">
    <strong>คำแนะนำการใช้งาน</strong>
    <div class="muted" style="margin-top: 8px;">
      เมื่อบันทึกแล้ว แผนงานนี้จะถูกสร้างในรายการติดตามของแดชบอร์ด ใช้แสดงใน <strong>ปฏิทินตรวจแปลง</strong> และกล่อง <strong>งานที่ต้องติดตามวันนี้</strong> ตามวันที่ที่กำหนด
    </div>
  </div>

  <div class="card" style="margin-top: 16px;">
    <h3>กำหนดแผนงาน</h3>

    @if (count($farmers) === 0)
      <div class="muted" style="margin-top: 10px;">ยังไม่พบข้อมูลเกษตรกรและแปลงสำหรับสร้างแผนงานติดตาม</div>
    @else
      <div class="form-grid" style="margin-top: 16px;">
        <label>
          เกษตรกร <span class="req">*</span>
          <select class="input" name="farmer_user_id" id="followup-farmer-select">
            @foreach ($farmers as $farmer)
              <option value="{{ $farmer['user_id'] }}" {{ (string) $selectedFarmer === (string) $farmer['user_id'] ? 'selected' : '' }}>
                {{ $farmer['farmer_name'] }} @if(!empty($farmer['username']))({{ $farmer['username'] }})@endif
              </option>
            @endforeach
          </select>
        </label>

        <label>
          แปลง <span class="req">*</span>
          <select class="input" name="plot_id" id="followup-plot-select" data-selected-plot="{{ $selectedPlot }}"></select>
        </label>

        <label>
          วันที่ติดตาม <span class="req">*</span>
          <input class="input" name="task_date" type="date" value="{{ old('task_date', now()->format('Y-m-d')) }}" data-buddhist-date>
        </label>

        <label>
          ประเภทงาน <span class="req">*</span>
          <select class="input" name="task_type">
            @foreach ($taskTypes as $taskKey => $task)
              <option value="{{ $taskKey }}" {{ old('task_type') === $taskKey ? 'selected' : '' }}>
                {{ $task['label'] }}
              </option>
            @endforeach
          </select>
        </label>

        <label>
          ความสำคัญ <span class="req">*</span>
          <select class="input" name="priority">
            <option value="normal" {{ old('priority', 'medium') === 'normal' ? 'selected' : '' }}>ปกติ</option>
            <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>ปานกลาง</option>
            <option value="urgent" {{ old('priority', 'medium') === 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
          </select>
        </label>

        <label style="grid-column: 1 / -1;">
          ประเภทการนัดหมาย (สีในปฏิทิน) <span class="req">*</span>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px;">
            <label class="calendar-type-option {{ old('appointment_type', 'visit') === 'visit' ? 'is-selected' : '' }}" style="display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 12px; border: 2px solid {{ old('appointment_type', 'visit') === 'visit' ? '#f59e0b' : '#e5e7eb' }}; background: {{ old('appointment_type', 'visit') === 'visit' ? '#fffbeb' : '#fff' }}; cursor: pointer;">
              <input type="radio" name="appointment_type" value="visit" {{ old('appointment_type', 'visit') === 'visit' ? 'checked' : '' }} style="accent-color: #f59e0b;">
              <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: #f59e0b; flex-shrink: 0;"></span>
              <div>
                <div style="font-weight: 700; font-size: 13px;">ตรวจแปลงทั่วไป</div>
                <div style="font-size: 11px; color: #6b7280;">ขึ้นจุดสีเหลืองในปฏิทิน</div>
              </div>
            </label>
            <label class="calendar-type-option {{ old('appointment_type', 'visit') === 'fix' ? 'is-selected' : '' }}" style="display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 12px; border: 2px solid {{ old('appointment_type', 'visit') === 'fix' ? '#ef4444' : '#e5e7eb' }}; background: {{ old('appointment_type', 'visit') === 'fix' ? '#fff1f2' : '#fff' }}; cursor: pointer;">
              <input type="radio" name="appointment_type" value="fix" {{ old('appointment_type', 'visit') === 'fix' ? 'checked' : '' }} style="accent-color: #ef4444;">
              <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: #ef4444; flex-shrink: 0;"></span>
              <div>
                <div style="font-weight: 700; font-size: 13px;">นัดแก้ไขปัญหา</div>
                <div style="font-size: 11px; color: #6b7280;">ขึ้นจุดสีแดงในปฏิทิน</div>
              </div>
            </label>
          </div>
        </label>

        <label style="grid-column: 1 / -1;">
          รายละเอียดเพิ่มเติม
          <textarea class="input" name="latest_note" rows="4" placeholder="ระบุสิ่งที่ต้องตรวจ จุดที่ต้องเน้น หรือหมายเหตุเพิ่มเติม">{{ old('latest_note') }}</textarea>
        </label>
      </div>

      <div class="footer-actions" style="margin-top: 18px;">
        <a class="btn ghost" href="/admin">ยกเลิก</a>
        <button class="btn primary" type="submit">บันทึกแผนงาน</button>
      </div>
    @endif
  </div>
</form>
@endsection

@push('scripts')
<script>
  (function () {
    var farmerSelect = document.getElementById('followup-farmer-select');
    var plotSelect = document.getElementById('followup-plot-select');
    if (!farmerSelect || !plotSelect) return;

    var farmers = @json($farmers);

    function renderPlots() {
      var selectedFarmer = farmerSelect.value;
      var selectedPlot = plotSelect.dataset.selectedPlot || '';
      var farmer = farmers.find(function (item) {
        return String(item.user_id) === String(selectedFarmer);
      });

      plotSelect.innerHTML = '';

      if (!farmer || !Array.isArray(farmer.plots) || farmer.plots.length === 0) {
        var emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = 'ไม่พบข้อมูลแปลง';
        plotSelect.appendChild(emptyOption);
        return;
      }

      farmer.plots.forEach(function (plot, index) {
        var option = document.createElement('option');
        option.value = plot.plot_id;
        option.textContent = plot.plot_name + ' (' + plot.plot_code + ')';

        if (String(selectedPlot) === String(plot.plot_id) || (!selectedPlot && index === 0)) {
          option.selected = true;
        }

        plotSelect.appendChild(option);
      });
    }

    renderPlots();

    farmerSelect.addEventListener('change', function () {
      plotSelect.dataset.selectedPlot = '';
      renderPlots();
    });
  })();

  (function () {
    var options = document.querySelectorAll('.calendar-type-option');
    options.forEach(function (label) {
      var radio = label.querySelector('input[type="radio"]');
      if (!radio) return;

      radio.addEventListener('change', function () {
        options.forEach(function (l) {
          var isVisit = l.querySelector('input').value === 'visit';
          l.style.borderColor = '#e5e7eb';
          l.style.background = '#fff';
        });
        var isVisit = radio.value === 'visit';
        label.style.borderColor = isVisit ? '#f59e0b' : '#ef4444';
        label.style.background = isVisit ? '#fffbeb' : '#fff1f2';
      });
    });
  })();
</script>
@endpush
