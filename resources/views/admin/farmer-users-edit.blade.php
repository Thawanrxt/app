@extends('admin.layout')
@section('title', 'แก้ไขผู้ใช้งาน')
@section('content')
<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="/admin/farmer-users" aria-label="กลับไปหน้าผู้ใช้งาน">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>แก้ไขผู้ใช้งาน</h1>
      <p class="muted">ปรับข้อมูลบัญชีผู้ใช้และข้อมูลเกษตรกร</p>
    </div>
  </div>
</div>

<style>
  form label {
    display: block;
    line-height: 1.55;
  }

  form label > input,
  form label > select,
  form label > textarea,
  form label > .multi-select,
  form label > .area-inputs {
    display: block;
    margin-top: 8px;
  }

  .plot-grid {
    align-items: start;
  }

  .plot-area-label {
    grid-column: span 2;
  }

  .plot-code-label {
    grid-column: span 2;
  }

  .form-grid:not(.plot-grid) > .plot-code-label {
    display: none;
  }

  .area-inputs.compact {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
  }

  .area-item {
    align-items: center;
    display: flex;
    gap: 8px;
  }

  .area-item .area-field {
    max-width: 140px;
    min-width: 0;
    width: 140px;
  }

  .area-item .unit {
    color: #475569;
    font-weight: 600;
    white-space: nowrap;
  }

  @media (max-width: 900px) {
    .plot-area-label {
      grid-column: auto;
    }

    .plot-code-label {
      grid-column: auto;
    }
  }

  .required-star {
    color: #dc2626;
    margin-left: 4px;
    font-weight: 700;
  }

  .multi-select {
    position: relative;
  }

  .multi-select-toggle {
    align-items: center;
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 14px;
    cursor: pointer;
    display: flex;
    gap: 12px;
    justify-content: space-between;
    min-height: 46px;
    padding: 10px 14px;
    width: 100%;
  }

  .multi-select-toggle:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    outline: none;
  }

  .multi-select-value {
    color: #0f172a;
    overflow: hidden;
    text-align: left;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .multi-select-value.placeholder {
    color: #64748b;
  }

  .multi-select-arrow {
    color: #64748b;
    flex-shrink: 0;
    font-size: 12px;
  }

  .multi-select-panel {
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 14px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
    display: none;
    left: 0;
    margin-top: 8px;
    max-height: 220px;
    overflow: auto;
    padding: 8px;
    position: absolute;
    right: 0;
    top: 100%;
    z-index: 20;
  }

  .multi-select.open .multi-select-panel {
    display: block;
  }

  .multi-select-option {
    align-items: center;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    gap: 10px;
    padding: 10px 12px;
  }

  .multi-select-option:hover {
    background: #f8fafc;
  }

  .multi-select-option input {
    margin: 0;
  }

  .multi-select-hint {
    color: #64748b;
    display: block;
    font-size: 12px;
    margin-top: 6px;
  }

  .input-error {
    border-color: #dc2626 !important;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
  }

  .input-error-text {
    color: #dc2626;
    display: none;
    font-size: 12px;
    margin-top: 6px;
  }

  .input-error-text.is-visible {
    display: block;
  }
</style>

@if ($errors->any())
  <div class="card" style="margin-top: 16px; border-color: #fca5a5; background: #fff1f2;">
    <strong>อัปเดตข้อมูลไม่สำเร็จ</strong>
    <ul style="margin: 8px 0 0 18px;">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form method="POST" action="/admin/farmer-users/{{ $userRecord->id }}">
  @csrf
  @method('PUT')

  <div class="card" style="margin-top: 16px;">
    <h3>ข้อมูลส่วนตัวผู้ใช้งาน</h3>
    <div class="form-grid">
      <label>ชื่อ-นามสกุล
        <input class="input" name="name" type="text" placeholder="กรอกชื่อเกษตรกร" value="{{ old('name', $userRecord->full_name) }}">
      </label>
      <label>เลขบัตรประชาชน
        <input class="input" name="citizen_id" type="text" inputmode="numeric" autocomplete="off" maxlength="17" placeholder="กรอกเลขบัตรประชาชน" value="{{ old('citizen_id', $userRecord->citizen_id) }}" data-format="citizen-id" oninput="window.AdminInputMasks && window.AdminInputMasks.apply(this, 'citizen-id')" onblur="window.AdminInputMasks && window.AdminInputMasks.apply(this, 'citizen-id')">
        <span class="input-error-text" data-input-error-for="citizen_id">ต้องใส่เป็นตัวเลขเท่านั้น</span>
      </label>
      <label>เบอร์โทรศัพท์
        <input class="input" name="phone" type="text" inputmode="numeric" autocomplete="off" maxlength="12" placeholder="กรอกเบอร์โทรศัพท์" value="{{ old('phone', $userRecord->phone) }}" data-format="phone" oninput="window.AdminInputMasks && window.AdminInputMasks.apply(this, 'phone')" onblur="window.AdminInputMasks && window.AdminInputMasks.apply(this, 'phone')">
        <span class="input-error-text" data-input-error-for="phone">ต้องใส่เป็นตัวเลขเท่านั้น</span>
      </label>
      <label>วันเดือนปีเกิด
        <input class="input" name="birth_date" type="date" value="{{ old('birth_date', $userRecord->birth_date ? \Illuminate\Support\Carbon::parse($userRecord->birth_date)->format('Y-m-d') : '') }}" data-buddhist-date>
      </label>
      <label class="plot-code-label">รหัสแปลง
        <input
          class="input"
          type="text"
          value="{{ old('plot_farm_id', $userRecord->plot_farm_id) }}"
          placeholder="ระบบสร้างรหัสแปลงอัตโนมัติ"
          readonly
          disabled
        >
      </label>
      <label class="plot-code-label">รหัสแปลง
        <input
          class="input"
          type="text"
          value="{{ old('plot_farm_id', $userRecord->plot_farm_id) }}"
          placeholder="ระบบสร้างรหัสแปลงอัตโนมัติ"
          readonly
          disabled
        >
      </label>
    </div>
  </div>

  <div class="card" style="margin-top: 16px;">
    <h3>ที่อยู่ปัจจุบัน</h3>
    <div class="form-grid">
      <label>ที่อยู่เลขที่/หมู่/ซอย/ถนน
        <input class="input" name="address_line" type="text" placeholder="บ้านเลขที่, หมู่, ซอย, ถนน" value="{{ old('address_line', $userRecord->address_line) }}">
      </label>
      <label>จังหวัด
        <select class="input" name="province" id="province-select" data-selected="{{ old('province', $userRecord->province) }}">
          <option value="">{{ old('province', $userRecord->province) ? 'กำลังโหลดข้อมูล...' : 'เลือกจังหวัด' }}</option>
        </select>
      </label>
      <label>อำเภอ/เขต
        <select class="input" name="district" id="district-select" data-selected="{{ old('district', $userRecord->district) }}" disabled>
          <option value="">{{ old('district', $userRecord->district) ? 'กำลังโหลดข้อมูล...' : 'เลือกอำเภอ/เขต' }}</option>
        </select>
      </label>
      <label>ตำบล/แขวง
        <select class="input" name="subdistrict" id="subdistrict-select" data-selected="{{ old('subdistrict', $userRecord->subdistrict) }}" disabled>
          <option value="">{{ old('subdistrict', $userRecord->subdistrict) ? 'กำลังโหลดข้อมูล...' : 'เลือกตำบล/แขวง' }}</option>
        </select>
      </label>
    </div>
  </div>

  <div class="card" style="margin-top: 16px;">
    <h3>ข้อมูลเกษตรกร</h3>
    <div class="form-grid">
      <label>รหัสทะเบียนเกษตรกร
        <input
          class="input"
          name="farmer_code"
          type="text"
          inputmode="numeric"
          autocomplete="off"
          maxlength="15"
          placeholder="กรอกรหัสทะเบียนเกษตรกร 12 หลัก"
          value="{{ old('farmer_code', $userRecord->farmer_code) }}"
          data-format="farmer-code"
          oninput="window.AdminInputMasks && window.AdminInputMasks.apply(this, 'farmer-code')"
          onblur="window.AdminInputMasks && window.AdminInputMasks.apply(this, 'farmer-code')"
        >
        <span class="input-error-text" data-input-error-for="farmer_code">ต้องใส่เป็นตัวเลขเท่านั้น</span>
      </label>
      <label>วันที่ขึ้นทะเบียน
        <input class="input" name="registered_at" type="date" value="{{ old('registered_at', $userRecord->registered_at ? \Illuminate\Support\Carbon::parse($userRecord->registered_at)->format('Y-m-d') : '') }}" data-buddhist-date>
      </label>
      <label>จังหวัดที่ขึ้นทะเบียน
        <select class="input" name="registered_province" id="register-province-select" data-selected="{{ old('registered_province', $userRecord->registered_province) }}">
          <option value="">{{ old('registered_province', $userRecord->registered_province) ? 'กำลังโหลดข้อมูล...' : 'เลือกจังหวัดที่ขึ้นทะเบียน' }}</option>
        </select>
      </label>
    </div>
  </div>

  <div class="card" style="margin-top: 16px;">
    <h3>ข้อมูลผู้ดูแลเกษตรกร</h3>
    <div class="form-grid" style="margin-top: 12px;">
      <label>ผู้ดูแลหลัก
        <select class="input" name="assigned_admin_user_id">
          <option value="">เลือกผู้ดูแลหลัก</option>
          @foreach (($assignableAdminOptions ?? []) as $adminOption)
            <option value="{{ $adminOption['id'] }}" @selected(old('assigned_admin_user_id', $userRecord->assigned_admin_user_id) === $adminOption['id'])>{{ $adminOption['label'] }}</option>
          @endforeach
        </select>
      </label>
      <label>ผู้ดูแลร่วม
        <div class="multi-select" data-multi-select>
          <button class="multi-select-toggle" type="button" data-multi-select-toggle aria-expanded="false">
            <span class="multi-select-value placeholder" data-multi-select-value>เลือกผู้ดูแลร่วม</span>
            <span class="multi-select-arrow">▼</span>
          </button>
          <div class="multi-select-panel" data-multi-select-panel>
            @foreach (($assignableAdminOptions ?? []) as $adminOption)
              <label class="multi-select-option">
                <input
                  type="checkbox"
                  value="{{ $adminOption['id'] }}"
                  data-label="{{ $adminOption['label'] }}"
                  @checked(collect(old('secondary_admin_user_ids', $userRecord->secondary_admin_user_ids ?? []))->contains($adminOption['id']))
                >
                <span>{{ $adminOption['label'] }}</span>
              </label>
            @endforeach
          </div>
          <div data-multi-select-inputs></div>
        </div>
        <span class="multi-select-hint">กดเพื่อเปิดรายการ และเลือกได้มากกว่า 1 คน</span>
      </label>
      <label>ประเภทการดูแล<span class="required-star">*</span>
        <select class="input" name="assignment_type">
          <option value="">เลือกประเภทการดูแล</option>
          <option value="AREA" @selected(old('assignment_type', $userRecord->assignment_type) === 'AREA')>ดูแลตามพื้นที่</option>
          <option value="INDIVIDUAL" @selected(old('assignment_type', $userRecord->assignment_type) === 'INDIVIDUAL')>ดูแลเฉพาะราย</option>
        </select>
      </label>
      <label>หมายเหตุการมอบหมาย
        <textarea class="input" name="assignment_note" rows="2" placeholder="เช่น ผู้ดูแลประจำตำบล หรือประสานงานเฉพาะเคสนี้">{{ old('assignment_note', $userRecord->assignment_note) }}</textarea>
      </label>
    </div>
  </div>

  <div class="card" style="margin-top: 16px;">
    <h3>ข้อมูลแปลงหลัก</h3>
    <div class="form-grid plot-grid">
      <label>จังหวัดแปลง<span class="required-star">*</span>
        <select class="input" name="farm_province" id="farm-province-select" data-selected="{{ old('farm_province', $userRecord->farm_province) }}">
          <option value="">{{ old('farm_province', $userRecord->farm_province) ? 'กำลังโหลดข้อมูล...' : 'เลือกจังหวัดแปลง' }}</option>
        </select>
      </label>
      <label>ประเภทพืชปลูก<span class="required-star">*</span>
        <select class="input" name="crop_type" id="crop-type-select">
          <option value="">เลือกประเภทพืช</option>
          @foreach (['ข้าว', 'อ้อย', 'มันสำปะหลัง', 'ข้าวโพด', 'ผัก', 'ผลไม้', 'ยางพารา', 'ปาล์มน้ำมัน', 'อื่นๆ'] as $cropType)
            <option value="{{ $cropType }}" @selected(old('crop_type', $userRecord->crop_type) === $cropType)>{{ $cropType }}</option>
          @endforeach
        </select>
      </label>
      <label class="plot-area-label">พื้นที่แปลงปลูก<span class="required-star">*</span>
        <div class="area-inputs compact">
          <div class="area-item">
            <input class="input area-field" name="farm_area_rai" type="number" min="0" placeholder="จำนวน" value="{{ old('farm_area_rai', $userRecord->farm_area_rai) }}">
            <span class="unit">ไร่</span>
          </div>
          <div class="area-item">
            <input class="input area-field" name="farm_area_ngan" type="number" min="0" placeholder="จำนวน" value="{{ old('farm_area_ngan', $userRecord->farm_area_ngan) }}">
            <span class="unit">งาน</span>
          </div>
          <div class="area-item">
            <input class="input area-field" name="farm_area_square_wa" type="number" min="0" placeholder="จำนวน" value="{{ old('farm_area_square_wa', $userRecord->farm_area_square_wa) }}">
            <span class="unit">ตารางวา</span>
          </div>
        </div>
      </label>
      <label class="plot-code-label">รหัสแปลง
        <input
          class="input"
          type="text"
          value="{{ old('plot_farm_id', $userRecord->plot_farm_id) }}"
          placeholder="ระบบสร้างรหัสแปลงอัตโนมัติ"
          readonly
          disabled
        >
      </label>
    </div>
  </div>

  <div class="card" style="margin-top: 16px;">
    <h3>ข้อมูลบัญชีผู้ใช้</h3>
    <div class="form-grid">
      <label>ชื่อผู้ใช้
        <input class="input" name="username" type="text" placeholder="กรอกชื่อผู้ใช้" value="{{ old('username', $userRecord->username) }}">
      </label>
      <label>บทบาท
        <select class="input" name="role">
          @foreach (($roleOptions ?? []) as $roleOption)
            <option value="{{ $roleOption['code'] }}" @selected(old('role', $userRecord->role) === $roleOption['code'])>{{ $roleOption['name_th'] }}</option>
          @endforeach
        </select>
      </label>
    </div>
  </div>

  <div class="card" style="margin-top: 16px;">
    <h3>เปลี่ยนรหัสผ่าน</h3>
    <div class="form-grid">
      <label>รหัสผ่านใหม่
        <input class="input" name="password" type="password" placeholder="เว้นว่างไว้หากไม่ต้องการเปลี่ยน">
      </label>
      <label>ยืนยันรหัสผ่านใหม่
        <input class="input" name="password_confirmation" type="password" placeholder="กรอกรหัสผ่านใหม่อีกครั้ง">
      </label>
    </div>
  </div>

  <div class="footer-actions" style="margin-top: 20px;">
    <button class="btn primary" type="submit">อัปเดตข้อมูล</button>
    <a href="/admin/farmer-users" class="btn ghost">ยกเลิก</a>
  </div>
</form>
<script>
  window.AdminInputMasks = window.AdminInputMasks || (function () {
    function onlyDigits(value, maxLength) {
      return String(value || '').replace(/\D+/g, '').slice(0, maxLength);
    }

    function formatCitizenId(value) {
      var digits = onlyDigits(value, 13);
      var parts = [];

      if (digits.slice(0, 1)) parts.push(digits.slice(0, 1));
      if (digits.slice(1, 5)) parts.push(digits.slice(1, 5));
      if (digits.slice(5, 10)) parts.push(digits.slice(5, 10));
      if (digits.slice(10, 12)) parts.push(digits.slice(10, 12));
      if (digits.slice(12, 13)) parts.push(digits.slice(12, 13));

      return parts.join(' ');
    }

    function formatPhone(value) {
      var digits = onlyDigits(value, 10);
      var parts = [];

      if (digits.slice(0, 3)) parts.push(digits.slice(0, 3));
      if (digits.slice(3, 6)) parts.push(digits.slice(3, 6));
      if (digits.slice(6, 10)) parts.push(digits.slice(6, 10));

      return parts.join('-');
    }

    function formatFarmerCode(value) {
      var digits = onlyDigits(value, 12);
      var parts = [];

      if (digits.slice(0, 6)) parts.push(digits.slice(0, 6));
      if (digits.slice(6, 10)) parts.push(digits.slice(6, 10));
      if (digits.slice(10, 11)) parts.push(digits.slice(10, 11));
      if (digits.slice(11, 12)) parts.push(digits.slice(11, 12));

      return parts.join('-');
    }

    function apply(input, formatter) {
      if (!input) return;

      var rawValue = String(input.value || '');
      var hasInvalidChars = /[^0-9\s-]/.test(rawValue);
      var errorTarget = document.querySelector('[data-input-error-for="' + input.name + '"]');

      input.classList.toggle('input-error', hasInvalidChars);

      if (errorTarget) {
        errorTarget.classList.toggle('is-visible', hasInvalidChars);
      }

      if (hasInvalidChars) {
        return;
      }

      if (formatter === 'citizen-id') {
        input.value = formatCitizenId(input.value);
        return;
      }

      if (formatter === 'phone') {
        input.value = formatPhone(input.value);
        return;
      }

      if (formatter === 'farmer-code') {
        input.value = formatFarmerCode(input.value);
      }
    }

    return {
      apply: apply
    };
  })();

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-multi-select]').forEach(function (multiSelect) {
      const toggle = multiSelect.querySelector('[data-multi-select-toggle]');
      const value = multiSelect.querySelector('[data-multi-select-value]');
      const inputs = multiSelect.querySelector('[data-multi-select-inputs]');
      const checkboxes = Array.from(multiSelect.querySelectorAll('input[type="checkbox"]'));

      function syncMultiSelect() {
        const selected = checkboxes.filter(function (checkbox) {
          return checkbox.checked;
        });

        inputs.innerHTML = '';

        selected.forEach(function (checkbox) {
          const hiddenInput = document.createElement('input');
          hiddenInput.type = 'hidden';
          hiddenInput.name = 'secondary_admin_user_ids[]';
          hiddenInput.value = checkbox.value;
          inputs.appendChild(hiddenInput);
        });

        if (selected.length === 0) {
          value.textContent = 'เลือกผู้ดูแลร่วม';
          value.classList.add('placeholder');
          return;
        }

        value.textContent = selected.map(function (checkbox) {
          return checkbox.dataset.label || checkbox.value;
        }).join(', ');
        value.classList.remove('placeholder');
      }

      function closePanel() {
        multiSelect.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
      }

      toggle.addEventListener('click', function () {
        const isOpen = multiSelect.classList.toggle('open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });

      checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', syncMultiSelect);
      });

      document.addEventListener('click', function (event) {
        if (!multiSelect.contains(event.target)) {
          closePanel();
        }
      });

      syncMultiSelect();
    });

    document.querySelectorAll('form label').forEach(function (label) {
      if (label.querySelector('.required-star')) {
        return;
      }

      const firstField = label.querySelector('input, select, textarea, .area-inputs');
      if (!firstField) {
        return;
      }

      const star = document.createElement('span');
      star.className = 'required-star';
      star.textContent = '*';
      label.insertBefore(star, firstField);
    });

    document.querySelectorAll('[data-format]').forEach(function (input) {
      var formatter = input.dataset.format;

      input.addEventListener('input', function () {
        window.AdminInputMasks.apply(input, formatter);
      });
      input.addEventListener('blur', function () {
        window.AdminInputMasks.apply(input, formatter);
      });
      window.AdminInputMasks.apply(input, formatter);
    });
  });
</script>
@endsection
