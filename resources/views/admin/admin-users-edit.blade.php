@extends('admin.layout')
@section('title', 'แก้ไขผู้ดูแลระบบ')
@section('content')
<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="/admin/admin-users" aria-label="กลับไปหน้าผู้ดูแลระบบ">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>แก้ไขผู้ดูแลระบบ</h1>
      <p class="muted">ปรับข้อมูลบัญชีและขอบเขตการดูแลของแอดมิน</p>
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
  form label > textarea {
    display: block;
    margin-top: 8px;
  }

  .required-star {
    color: #dc2626;
    margin-left: 4px;
    font-weight: 700;
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

<form method="POST" action="/admin/admin-users/{{ $userRecord->id }}">
  @csrf
  @method('PUT')

  <div class="card" style="margin-top: 16px;">
    <h3>ข้อมูลและขอบเขตการดูแลของแอดมิน</h3>
    <div class="form-grid">
      <label>ชื่อ-สกุล
        <input class="input" name="display_name" type="text" placeholder="กรอกชื่อและนามสกุล" value="{{ old('display_name', $userRecord->display_name) }}">
      </label>
      <label>เบอร์โทรศัพท์
        <input class="input" name="phone" type="text" placeholder="กรอกเบอร์โทรศัพท์" value="{{ old('phone', $userRecord->phone) }}">
      </label>
      <label>ตำแหน่งแอดมิน
        <input class="input" name="admin_title" type="text" placeholder="เช่น ผู้ประสานงานภาคสนาม" value="{{ old('admin_title', $userRecord->admin_title) }}">
      </label>
      <div></div>
      <label>จังหวัดที่รับผิดชอบ
        <select class="input" id="scope_province" name="scope_province" data-selected="{{ old('scope_province', $userRecord->scope_province) }}">
          <option value="">{{ old('scope_province', $userRecord->scope_province) ? 'กำลังโหลดข้อมูล...' : 'เลือกจังหวัด' }}</option>
        </select>
      </label>
      <label>อำเภอ/เขตที่รับผิดชอบ
        <select class="input" id="scope_district" name="scope_district" data-selected="{{ old('scope_district', $userRecord->scope_district) }}">
          <option value="">เลือกอำเภอ/เขต</option>
        </select>
      </label>
      <label>ตำบล/แขวงที่รับผิดชอบ
        <input class="input" name="scope_subdistrict" type="text" placeholder="กรอกตำบล/แขวง หรือหลายตำบลคั่นด้วย ," value="{{ old('scope_subdistrict', $userRecord->scope_subdistrict) }}">
      </label>
    </div>
  </div>

  <div class="card" style="margin-top: 16px;">
    <h3>ข้อมูลบัญชีผู้ดูแล</h3>
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
    <a href="/admin/admin-users" class="btn ghost">ยกเลิก</a>
  </div>
</form>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form label').forEach(function (label) {
      if (label.querySelector('.required-star')) {
        return;
      }

      const firstField = label.querySelector('input, select, textarea');
      if (!firstField) {
        return;
      }

      const star = document.createElement('span');
      star.className = 'required-star';
      star.textContent = '*';
      label.insertBefore(star, firstField);
    });

    const provinceSelect = document.getElementById('scope_province');
    const districtSelect = document.getElementById('scope_district');
    const dataUrl = 'https://raw.githubusercontent.com/thailand-geography-data/thailand-geography-json/main/src/geography.json';

    function setOptions(select, items, placeholder, selectedValue) {
      select.innerHTML = '';

      const placeholderOption = document.createElement('option');
      placeholderOption.value = '';
      placeholderOption.textContent = placeholder;
      select.appendChild(placeholderOption);

      items.forEach(function (item) {
        const option = document.createElement('option');
        option.value = item.value;
        option.textContent = item.label;
        if (item.code) {
          option.dataset.code = item.code;
        }
        select.appendChild(option);
      });

      select.disabled = items.length === 0;

      if (selectedValue) {
        select.value = selectedValue;
      }
    }

    function uniqueBy(items, keyFn) {
      const map = new Map();
      items.forEach(function (item) {
        const key = keyFn(item);
        if (!map.has(key)) {
          map.set(key, item);
        }
      });
      return Array.from(map.values());
    }

    function getSelectedCode(select) {
      const option = select.options[select.selectedIndex];
      return option && option.dataset.code ? option.dataset.code : '';
    }

    fetch(dataUrl)
      .then(function (response) { return response.json(); })
      .then(function (rows) {
        const provinces = uniqueBy(rows.map(function (row) {
          return {
            value: row.provinceNameTh,
            label: row.provinceNameTh,
            code: String(row.provinceCode)
          };
        }), function (item) { return item.code; }).sort(function (a, b) {
          return a.label.localeCompare(b.label, 'th');
        });

        const selectedProvince = provinceSelect.dataset.selected || '';
        const selectedDistrict = districtSelect.dataset.selected || '';
        setOptions(provinceSelect, provinces, 'เลือกจังหวัด', selectedProvince);

        function refreshDistricts(presetDistrict) {
          const provinceCode = getSelectedCode(provinceSelect);

          if (!provinceCode) {
            setOptions(districtSelect, [], 'เลือกอำเภอ/เขต', '');
            return;
          }

          const districts = uniqueBy(rows.filter(function (row) {
            return String(row.provinceCode) === provinceCode;
          }).map(function (row) {
            return {
              value: row.districtNameTh,
              label: row.districtNameTh,
              code: String(row.districtCode)
            };
          }), function (item) { return item.code; }).sort(function (a, b) {
            return a.label.localeCompare(b.label, 'th');
          });

          setOptions(districtSelect, districts, 'เลือกอำเภอ/เขต', presetDistrict || '');
        }
        provinceSelect.addEventListener('change', function () {
          districtSelect.dataset.selected = '';
          refreshDistricts('');
        });

        refreshDistricts(selectedDistrict);
      })
      .catch(function () {
        setOptions(provinceSelect, [], 'โหลดข้อมูลไม่สำเร็จ', '');
        setOptions(districtSelect, [], 'โหลดข้อมูลไม่สำเร็จ', '');
      });
  });
</script>
@endsection
