@extends('admin.layout')
@section('title', 'ตั้งค่า')
@section('content')
<div class="settings-page">
  <div class="settings-hero">
    <div>
      <p class="settings-eyebrow">SYSTEM PREFERENCES</p>
      <h1>ตั้งค่า</h1>
      <p class="muted">ปรับธีม ภาษา เวลา และรูปแบบการแสดงผลของระบบแอดมิน โดยทุกการเปลี่ยนค่าจะถูกบันทึกให้อัตโนมัติทันที</p>
    </div>
  </div>

  @if (session('success'))
    <div class="settings-alert success">{{ session('success') }}</div>
  @endif

  @if (session('warning'))
    <div class="settings-alert warning">{{ session('warning') }}</div>
  @endif

  @if (isset($settingsAvailable) && ! $settingsAvailable)
    <div class="settings-alert info">
      ระบบยังไม่มีตาราง <code>app_settings</code> ในฐานข้อมูล PostgreSQL จึงแสดงค่าเริ่มต้นชั่วคราวให้ก่อน
      @if (! empty($missingColumns))
        <div style="margin-top: 8px;">
          คอลัมน์ที่ยังขาด: {{ implode(', ', $missingColumns) }}
        </div>
      @endif
    </div>
  @endif

  <form method="POST" action="/admin/settings" id="settings-form">
    @csrf

    <div class="settings-grid settings-grid--wide">
      <section class="card settings-panel settings-panel--appearance">
        <div class="settings-panel__head">
          <div>
            <h3>รูปแบบการแสดงผล</h3>
            <p class="muted">เลือกโทนหน้าจอและรูปแบบตัวอักษรให้เหมาะกับการทำงานของทีมแอดมิน</p>
          </div>
        </div>

        <div class="settings-theme-picker">
          <label class="theme-tile">
            <input type="radio" name="theme" value="light" @checked(old('theme', $settings->theme) === 'light')>
            <span class="theme-tile__preview theme-tile__preview--light">
              <span class="theme-preview__bar"></span>
              <span class="theme-preview__card"></span>
              <span class="theme-preview__card small"></span>
            </span>
            <span class="theme-tile__label">โหมดสว่าง</span>
          </label>

          <label class="theme-tile">
            <input type="radio" name="theme" value="dark" @checked(old('theme', $settings->theme) === 'dark')>
            <span class="theme-tile__preview theme-tile__preview--dark">
              <span class="theme-preview__bar"></span>
              <span class="theme-preview__card"></span>
              <span class="theme-preview__card small"></span>
            </span>
            <span class="theme-tile__label">โหมดมืด</span>
          </label>
        </div>

        <div class="settings-form-grid">
          <label class="settings-field">
            <span>ฟอนต์หลัก</span>
            <select class="input" name="font_family">
              @foreach (['Prompt', 'Sarabun'] as $fontFamily)
                <option value="{{ $fontFamily }}" @selected(old('font_family', $settings->font_family) === $fontFamily)>{{ $fontFamily }}</option>
              @endforeach
            </select>
          </label>

          <label class="settings-field">
            <span>ขนาดตัวอักษร</span>
            <select class="input" name="font_size">
              @foreach (['14', '16', '18', '20'] as $fontSize)
                <option value="{{ $fontSize }}" @selected(old('font_size', $settings->font_size) === $fontSize)>{{ $fontSize }} px</option>
              @endforeach
            </select>
          </label>
        </div>
      </section>

      <section class="card settings-panel">
        <div class="settings-panel__head">
          <div>
            <h3>ภาษาและเวลา</h3>
            <p class="muted">เลือกรูปแบบการแสดงผลที่ใช้ทั้งในรายงาน ตารางข้อมูล และหน้ารายละเอียดต่าง ๆ</p>
          </div>
        </div>

        <div class="settings-form-grid">
          <label class="settings-field">
            <span>ภาษา</span>
            <select class="input" name="language">
              @foreach (['th' => 'ภาษาไทย', 'en' => 'English'] as $languageValue => $languageLabel)
                <option value="{{ $languageValue }}" @selected(old('language', $settings->language) === $languageValue)>{{ $languageLabel }}</option>
              @endforeach
            </select>
          </label>

          <label class="settings-field">
            <span>เขตเวลา</span>
            <select class="input" name="timezone">
              @foreach (['Asia/Bangkok' => 'Asia/Bangkok (GMT+7)', 'UTC' => 'UTC'] as $timezoneValue => $timezoneLabel)
                <option value="{{ $timezoneValue }}" @selected(old('timezone', $settings->timezone) === $timezoneValue)>{{ $timezoneLabel }}</option>
              @endforeach
            </select>
          </label>

          <label class="settings-field">
            <span>รูปแบบวันที่</span>
            <select class="input" name="date_format">
              @foreach (['DD/MM/YYYY' => 'วัน/เดือน/ปี (25 มิ.ย. 2568)', 'YYYY-MM-DD' => 'ปี-เดือน-วัน (2025-06-25)'] as $dateFormatValue => $dateFormatLabel)
                <option value="{{ $dateFormatValue }}" @selected(old('date_format', $settings->date_format) === $dateFormatValue)>{{ $dateFormatLabel }}</option>
              @endforeach
            </select>
          </label>

          <label class="settings-field">
            <span>หน่วยพื้นที่</span>
            <input class="input" type="text" value="ไร่/งาน/ตารางวา" disabled>
            <input type="hidden" name="area_unit" value="rai">
          </label>
        </div>
      </section>
    </div>
  </form>
</div>

@push('scripts')
<script>
  (function () {
    var form = document.getElementById('settings-form');
    if (!form) return;

    var fields = form.querySelectorAll('select, input[type="radio"]');
    fields.forEach(function (field) {
      field.addEventListener('change', function () {
        form.requestSubmit();
      });
    });
  })();
</script>
@endpush
@endsection
