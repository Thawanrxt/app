@extends('admin.layout')
@section('title', 'แก้ไขพันธุ์ข้าว')
@section('content')
<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="/admin/rice" aria-label="กลับไปหน้าพันธุ์ข้าว">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>แก้ไขพันธุ์ข้าว</h1>
      <p class="muted">ปรับปรุงข้อมูลพันธุ์ข้าวในระบบ</p>
    </div>
  </div>
</div>

<style>
  .required-star {
    color: #dc2626;
    margin-left: 4px;
    font-weight: 700;
  }
</style>

@if ($errors->any())
  <div class="card" style="margin-top: 16px; border-color: #fca5a5; background: #fff1f2;">
    <strong>บันทึกข้อมูลไม่สำเร็จ</strong>
    <ul style="margin: 8px 0 0 18px;">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form method="POST" action="/admin/rice/{{ $riceVariety->id }}">
  @csrf
  @method('PUT')
  <div class="card" style="margin-top: 16px;">
    <div class="form-grid">
      <label>ประเภทข้าว
        <input class="input" name="rice_type" type="text" placeholder="กรอกข้อมูล" value="{{ old('rice_type', $riceVariety->rice_type) }}">
      </label>
      <label>ชื่อพันธุ์ข้าว
        <input class="input" name="name" type="text" placeholder="กรอกข้อมูล" value="{{ old('name', $riceVariety->name) }}">
      </label>
      <label>ระยะเวลามาตรฐาน (วัน)
        <input class="input" name="standard_duration_days" type="text" placeholder="จำนวนวัน เช่น 120-150 วัน" value="{{ old('standard_duration_days', $riceVariety->standard_duration_days ?: ($riceVariety->grow_duration_days ? $riceVariety->grow_duration_days.' วัน' : '')) }}">
      </label>
      <label>ความต้านทานโรค
        <input class="input" name="disease_resistance" type="text" placeholder="กรอกข้อมูล" value="{{ old('disease_resistance', $riceVariety->disease_resistance) }}">
      </label>
      <label>
        ความต้านทานศัตรูพืช
        <div class="pest-resistance">
          @php
            $pestResistances = old('pest_resistances', $riceVariety->pest_resistances ?? []);
            $primaryPestResistance = $pestResistances[0] ?? '';
          @endphp
          <div class="inline-field">
            <input class="input" type="text" placeholder="กรอกข้อมูล" id="pest-resistance-input" name="pest_resistances[]" value="{{ $primaryPestResistance }}">
            <button class="btn ghost" type="button" id="add-pest-resistance">เพิ่ม</button>
          </div>
          <div class="pest-list" id="pest-list">
            @foreach (collect($pestResistances)->skip(1) as $pestResistance)
              @if (filled($pestResistance))
                <div class="inline-field pest-row">
                  <input class="input" type="text" name="pest_resistances[]" value="{{ $pestResistance }}">
                  <button class="icon-action-btn danger compact" type="button" aria-label="ลบความต้านทานศัตรูพืช">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M3 6h18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                      <path d="M8 6V4h8v2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M19 6l-1 14H6L5 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M10 11v6M14 11v6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                  </button>
                </div>
              @endif
            @endforeach
          </div>
        </div>
      </label>
    </div>
  </div>

  <div class="footer-actions" style="margin-top: 20px;">
    <button class="btn primary" type="submit">บันทึกข้อมูล</button>
    <a href="/admin/rice" class="btn ghost">ยกเลิก</a>
  </div>
</form>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form label').forEach(function (label) {
      if (label.querySelector('.required-star')) {
        return;
      }

      const firstField = label.querySelector('input, select, textarea, .pest-resistance');
      if (!firstField) {
        return;
      }

      const star = document.createElement('span');
      star.className = 'required-star';
      star.textContent = '*';
      label.insertBefore(star, firstField);
    });
  });

  (function () {
    var addBtn = document.getElementById('add-pest-resistance');
    var list = document.getElementById('pest-list');
    if (!addBtn || !list) return;

    function attachRemove(button, row) {
      button.addEventListener('click', function () {
        row.remove();
      });
    }

    list.querySelectorAll('.pest-row').forEach(function (row) {
      var removeBtn = row.querySelector('button');
      if (removeBtn) {
        attachRemove(removeBtn, row);
      }
    });

    function addRow(value) {
      var row = document.createElement('div');
      row.className = 'inline-field pest-row';
      row.innerHTML = '<input class="input" type="text" name="pest_resistances[]" placeholder="กรอกข้อมูลเพิ่มเติม">' +
        '<button class="icon-action-btn danger compact" type="button" aria-label="ลบความต้านทานศัตรูพืช">' +
        '<svg viewBox="0 0 24 24" aria-hidden="true">' +
        '<path d="M3 6h18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' +
        '<path d="M8 6V4h8v2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
        '<path d="M19 6l-1 14H6L5 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
        '<path d="M10 11v6M14 11v6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' +
        '</svg>' +
        '</button>';

      var rowInput = row.querySelector('input');
      var removeBtn = row.querySelector('button');
      rowInput.value = value || '';
      attachRemove(removeBtn, row);
      list.appendChild(row);
      rowInput.focus();
    }

    addBtn.addEventListener('click', function () {
      addRow('');
    });
  })();
</script>
@endsection
