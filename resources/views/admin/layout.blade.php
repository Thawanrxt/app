<!doctype html>
<html lang="th">
@php
  $adminSettings = null;
  $currentAdmin = auth()->user();
  $menuPermissions = [];
  $showRoleRegistry = false;

  try {
      if (class_exists(\App\Models\AppSetting::class)) {
          $adminSettings = \App\Models\AppSetting::query()->first();
      }
  } catch (\Throwable $exception) {
      $adminSettings = null;
  }

  $theme = $adminSettings->theme ?? 'light';
  $fontFamily = $adminSettings->font_family ?? 'Sarabun';
  $fontSize = $adminSettings->font_size ?? '16';

  $fontStack = match ($fontFamily) {
      'Prompt' => '"Prompt", "Sarabun", "Noto Sans Thai", "Tahoma", sans-serif',
      default => '"Sarabun", "Prompt", "Noto Sans Thai", "Tahoma", sans-serif',
  };

  try {
      if ($currentAdmin && \Illuminate\Support\Facades\Schema::hasTable('role_menu_permissions')) {
          $menuPermissions = \Illuminate\Support\Facades\DB::table('role_menu_permissions')
              ->where('role_code', $currentAdmin->role)
              ->pluck('can_view', 'menu_key')
              ->map(fn ($value) => (bool) $value)
              ->all();
      }
  } catch (\Throwable $exception) {
      $menuPermissions = [];
  }

  try {
      $showRoleRegistry = \App\Support\AdminAccess::isSuperAdmin($currentAdmin);
  } catch (\Throwable $exception) {
      $showRoleRegistry = false;
  }
@endphp
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'SRP Admin')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=20260323">
  <style>
    .buddhist-date {
      position: relative;
    }

    .buddhist-date .input,
    .buddhist-date__toggle {
      margin-top: 0;
      width: 100%;
    }

    .buddhist-date__hidden {
      display: none !important;
    }

    .buddhist-date__toggle {
      align-items: center;
      appearance: none;
      background: #fff;
      border: 1px solid #d1d5db;
      border-radius: 12px;
      box-sizing: border-box;
      color: #0f172a;
      cursor: pointer;
      display: flex;
      font-family: inherit;
      font-size: 14px;
      font-weight: 400;
      justify-content: space-between;
      line-height: 1.5;
      min-height: 44px;
      padding: 10px 12px;
      text-align: left;
      white-space: nowrap;
    }

    .buddhist-date__toggle-label {
      color: #111827;
      font: inherit;
      line-height: inherit;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .buddhist-date__toggle-placeholder {
      color: #6b7280;
    }

    .buddhist-date__icon {
      color: #475569;
      flex-shrink: 0;
      margin-left: 12px;
    }

    .buddhist-date__panel {
      background: #fff;
      border: 1px solid #cbd5e1;
      border-radius: 18px;
      box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
      display: none;
      left: 0;
      margin-top: 10px;
      padding: 14px;
      position: absolute;
      top: 100%;
      width: min(320px, calc(100vw - 48px));
      z-index: 60;
    }

    .buddhist-date.is-open .buddhist-date__panel {
      display: block;
    }

    .buddhist-date__controls {
      display: grid;
      gap: 10px;
      grid-template-columns: 1.4fr 1fr;
      margin-bottom: 12px;
    }

    .buddhist-date__weekdays,
    .buddhist-date__grid {
      display: grid;
      gap: 6px;
      grid-template-columns: repeat(7, minmax(0, 1fr));
    }

    .buddhist-date__weekdays {
      color: #64748b;
      font-size: 12px;
      margin-bottom: 8px;
      text-align: center;
    }

    .buddhist-date__weekday {
      padding: 4px 0;
    }

    .buddhist-date__day {
      align-items: center;
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      color: #0f172a;
      cursor: pointer;
      display: inline-flex;
      font: inherit;
      height: 38px;
      justify-content: center;
      padding: 0;
    }

    .buddhist-date__day:hover {
      background: #f8fafc;
      border-color: #cbd5e1;
    }

    .buddhist-date__day.is-today {
      border-color: #16a34a;
      color: #15803d;
    }

    .buddhist-date__day.is-selected {
      background: #166534;
      border-color: #166534;
      color: #fff;
    }

    .buddhist-date__day.is-outside {
      color: #94a3b8;
    }

    @media (max-width: 640px) {
      .buddhist-date__controls {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body data-theme="{{ $theme }}" style="font-family: {{ $fontStack }}; font-size: {{ (int) $fontSize }}px;">
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-brand">SRP ADMIN</div>
      <div class="sidebar-user">
        <div class="sidebar-avatar">{{ strtoupper(mb_substr((string) ($currentAdmin?->username ?? 'A'), 0, 1)) }}</div>
        <div class="sidebar-user-text">
          <div class="sidebar-user-name sidebar-user-name--dynamic">{{ $currentAdmin?->username ?? 'Admin' }}</div>
          <div class="sidebar-user-sub sidebar-user-sub--dynamic">ระบบประเมิน SRP</div>
          <div class="sidebar-user-name">แอดมิน</div>
          <div class="sidebar-user-sub">ระบบประเมิน SRP</div>
        </div>
      </div>
    </div>
    <div class="sidebar-divider"></div>
    <nav class="sidebar-nav">
      <a href="/admin" class="sidebar-link" data-menu-key="dashboard">
        <span class="sidebar-icon">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v6l4 2" fill="none" stroke="currentColor" stroke-width="2"/></svg>
        </span>
        <span>แดชบอร์ด</span>
      </a>
      <a href="/admin/farmer-users" class="sidebar-link" data-menu-key="farmer_users">
        <span class="sidebar-icon">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4 20c2-4 14-4 16 0" fill="none" stroke="currentColor" stroke-width="2"/></svg>
        </span>
        <span>ผู้ใช้งาน</span>
      </a>
      @if ($showRoleRegistry)
        <a href="/admin/admin-users" class="sidebar-link" data-menu-key="admin_users">
          <span class="sidebar-icon">
            <svg viewBox="0 0 24 24"><path d="M12 2l7 4v6c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-4Z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 8a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5Zm-4 10c.8-1.8 2.3-3 4-3s3.2 1.2 4 3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          </span>
          <span>ผู้ดูแลระบบ</span>
        </a>
        <a href="/admin/roles" class="sidebar-link" data-menu-key="roles">
          <span class="sidebar-icon">
            <svg viewBox="0 0 24 24"><path d="M12 3l7 4v5c0 5-3.5 8-7 9-3.5-1-7-4-7-9V7l7-4Z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M9 12l2 2 4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          <span>การเพิ่มสิทธิ์แอดมิน</span>
        </a>
      @endif

      <details class="sidebar-group" data-menu-group="tracking">
        <summary class="sidebar-link">
          <span class="sidebar-icon">
            <svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 9h8M8 13h8M8 17h5" fill="none" stroke="currentColor" stroke-width="2"/></svg>
          </span>
          <span>ข้อมูลการติดตาม</span>
        </summary>
        <div class="sidebar-sub">
          <a href="/admin/tracking/prep" class="sidebar-sublink">การเตรียมดิน</a>
          <a href="/admin/tracking/water" class="sidebar-sublink">การจัดการน้ำ</a>
          <a href="/admin/tracking/fertilizer" class="sidebar-sublink">หว่านปุ๋ย</a>
          <a href="/admin/tracking/pest" class="sidebar-sublink">การจัดการศัตรูพืช</a>
          <a href="/admin/tracking/disease" class="sidebar-sublink">การจัดการโรคพืช</a>
          <a href="/admin/tracking/harvest" class="sidebar-sublink">การเก็บเกี่ยว</a>
          <a href="/admin/tracking/mill" class="sidebar-sublink">ขายข้าวเข้าโรงสี</a>
        </div>
      </details>

      <a href="/admin/srp" class="sidebar-link">
        <span class="sidebar-icon">
          <svg viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 8h8M8 12h8M8 16h6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
        </span>
        <span>คู่มือมาตรฐาน SRP</span>
      </a>
      <a href="/admin/srp/farmers" class="sidebar-link">
        <span class="sidebar-icon">
          <svg viewBox="0 0 24 24"><path d="M7 8a3 3 0 1 1 0-6 3 3 0 0 1 0 6Zm10 0a3 3 0 1 1 0-6 3 3 0 0 1 0 6ZM7 22v-2a4 4 0 0 1 4-4h2a4 4 0 0 1 4 4v2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 22v-1a4 4 0 0 1 3-3.87M21 22v-1a4 4 0 0 0-3-3.87" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </span>
        <span>ข้อมูลเกษตรกร</span>
      </a>
      <a href="/admin/rice" class="sidebar-link">
        <span class="sidebar-icon">
          <svg viewBox="0 0 24 24"><path d="M12 3c3 4 3 10 0 18" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 9c2 1 6 1 8 0" fill="none" stroke="currentColor" stroke-width="2"/></svg>
        </span>
        <span>พันธุ์ข้าว</span>
      </a>

      <details class="sidebar-group">
        <summary class="sidebar-link">
          <span class="sidebar-icon">
            <svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 9h8M8 13h8M8 17h5" fill="none" stroke="currentColor" stroke-width="2"/></svg>
          </span>
          <span>รายงานปัญหา</span>
        </summary>
        <div class="sidebar-sub">
          <a href="/admin/report/rice" class="sidebar-sublink">การปลูกข้าว</a>
          <a href="/admin/report/system" class="sidebar-sublink">การใช้งานระบบ</a>
        </div>
      </details>

      <a href="/admin/settings" class="sidebar-link">
        <span class="sidebar-icon">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M19.4 15a1 1 0 0 0 .2 1.1l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9V21a2 2 0 1 1-4 0v-.1a1 1 0 0 0-.6-.9 1 1 0 0 0-1.1.2l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6H3a2 2 0 1 1 0-4h.1a1 1 0 0 0 .9-.6 1 1 0 0 0-.2-1.1l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1 1 0 0 0 1.1.2 1 1 0 0 0 .6-.9V3a2 2 0 1 1 4 0v.1a1 1 0 0 0 .6.9 1 1 0 0 0 1.1-.2l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1 1 0 0 0-.2 1.1 1 1 0 0 0 .9.6H21a2 2 0 1 1 0 4h-.1a1 1 0 0 0-.9.6z" fill="none" stroke="currentColor" stroke-width="2"/></svg>
        </span>
        <span>ตั้งค่า</span>
      </a>
    </nav>
    <div class="sidebar-note">
      โฟกัสรายการที่ต้องติดตามวันนี้ให้เสร็จ และเตรียมรีพอร์ตสำหรับผู้บริหาร
    </div>
    <form method="POST" action="{{ url('/admin/logout') }}" style="margin-top: 12px;">
      @csrf
      <button class="btn ghost sidebar-logout" type="submit">ออกจากระบบ</button>
    </form>
  </aside>
  <main class="content">
    <div class="content-inner">
      @yield('content')
    </div>
  </main>
</div>
<div class="confirm-modal" id="confirm-modal" aria-hidden="true">
  <div class="confirm-modal__backdrop" data-confirm-close></div>
  <div class="confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title">
    <div class="confirm-modal__icon danger">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M3 6h18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <path d="M8 6V4h8v2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M19 6l-1 14H6L5 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M10 11v6M14 11v6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </div>
    <h3 id="confirm-modal-title">ยืนยันการลบข้อมูล</h3>
    <p class="confirm-modal__message" id="confirm-modal-message">คุณต้องการลบข้อมูลนี้ใช่หรือไม่?</p>
    <div class="confirm-modal__actions">
      <button class="btn ghost" type="button" id="confirm-modal-cancel">ยกเลิก</button>
      <button class="btn danger-solid" type="button" id="confirm-modal-submit">ลบข้อมูล</button>
    </div>
  </div>
</div>
<script>
  window.SrpSearchUtils = (function () {
    function normalize(value) {
      return String(value || '')
        .toLowerCase()
        .trim()
        .replace(/\s+/g, ' ');
    }

    function tokens(value) {
      var normalized = normalize(value);
      return normalized
        .split(/[^0-9a-zA-Zก-๙]+/u)
        .filter(function (token) { return token !== ''; });
    }

    function tokenVariants(token) {
      var variants = [token];
      var withoutLeadingVowels = token.replace(/^[เแโใไ]+/u, '');

      if (withoutLeadingVowels && withoutLeadingVowels !== token) {
        variants.push(withoutLeadingVowels);
      }

      return Array.from(new Set(variants));
    }

    function matches(haystack, needle) {
      var normalizedNeedle = normalize(needle);
      if (!normalizedNeedle) return true;

      var normalizedHaystack = normalize(Array.isArray(haystack) ? haystack.join(' ') : haystack);
      if (!normalizedHaystack) return false;

      if (normalizedHaystack.indexOf(normalizedNeedle) !== -1) {
        return true;
      }

      return tokens(normalizedHaystack).some(function (token) {
        return tokenVariants(token).some(function (variant) {
          return variant.indexOf(normalizedNeedle) === 0;
        });
      });
    }

    function matchesFromStart(haystack, needle) {
      var normalizedNeedle = normalize(needle);
      if (!normalizedNeedle) return true;

      var normalizedHaystack = normalize(Array.isArray(haystack) ? haystack.join(' ') : haystack);
      if (!normalizedHaystack) return false;

      if (normalizedHaystack.indexOf(normalizedNeedle) === 0) {
        return true;
      }

      return tokens(normalizedHaystack).some(function (token) {
        return tokenVariants(token).some(function (variant) {
          return variant.indexOf(normalizedNeedle) === 0;
        });
      });
    }

    return {
      normalize: normalize,
      matches: matches,
      matchesFromStart: matchesFromStart
    };
  })();
</script>
<script>
  (function () {
    var thaiMonths = [
      'มกราคม',
      'กุมภาพันธ์',
      'มีนาคม',
      'เมษายน',
      'พฤษภาคม',
      'มิถุนายน',
      'กรกฎาคม',
      'สิงหาคม',
      'กันยายน',
      'ตุลาคม',
      'พฤศจิกายน',
      'ธันวาคม'
    ];

    function padDatePart(value) {
      return String(value).padStart(2, '0');
    }

    function daysInMonth(year, month) {
      return new Date(year, month, 0).getDate();
    }

    function buildSelect(options, className) {
      var select = document.createElement('select');
      select.className = 'input ' + className;

      options.forEach(function (option) {
        var optionEl = document.createElement('option');
        optionEl.value = option.value;
        optionEl.textContent = option.label;
        select.appendChild(optionEl);
      });

      return select;
    }

    function syncDayOptions(daySelect, yearValue, monthValue) {
      var maxDays = yearValue && monthValue ? daysInMonth(Number(yearValue), Number(monthValue)) : 31;
      var currentValue = daySelect.value;

      daySelect.innerHTML = '';
      daySelect.appendChild(new Option('วัน', ''));

      for (var day = 1; day <= maxDays; day += 1) {
        daySelect.appendChild(new Option(String(day), String(day)));
      }

      if (currentValue && Number(currentValue) <= maxDays) {
        daySelect.value = currentValue;
      }
    }

    function createBuddhistDatePicker(input) {
      var wrapper = document.createElement('div');
      wrapper.className = 'buddhist-date';

      var now = new Date();
      var currentYear = now.getFullYear();
      var parsed = input.value && /^\d{4}-\d{2}-\d{2}$/.test(input.value)
        ? input.value.split('-').map(Number)
        : null;
      var selectedYear = parsed ? parsed[0] : '';
      var selectedMonth = parsed ? parsed[1] : '';
      var selectedDay = parsed ? parsed[2] : '';

      var daySelect = buildSelect([{ value: '', label: 'วัน' }], 'buddhist-date__day');
      var monthSelect = buildSelect(
        [{ value: '', label: 'เดือน' }].concat(
          thaiMonths.map(function (month, index) {
            return { value: String(index + 1), label: month };
          })
        ),
        'buddhist-date__month'
      );

      var yearOptions = [{ value: '', label: 'ปี พ.ศ.' }];
      for (var year = currentYear - 100; year <= currentYear + 10; year += 1) {
        yearOptions.push({ value: String(year), label: String(year + 543) });
      }
      var yearSelect = buildSelect(yearOptions, 'buddhist-date__year');

      function syncHiddenValue() {
        syncDayOptions(daySelect, yearSelect.value, monthSelect.value);

        if (selectedDay) {
          daySelect.value = String(selectedDay);
          selectedDay = '';
        }

        if (!daySelect.value || !monthSelect.value || !yearSelect.value) {
          input.value = '';
          return;
        }

        input.value = [
          yearSelect.value,
          padDatePart(monthSelect.value),
          padDatePart(daySelect.value)
        ].join('-');
      }

      monthSelect.value = selectedMonth ? String(selectedMonth) : '';
      yearSelect.value = selectedYear ? String(selectedYear) : '';
      syncDayOptions(daySelect, yearSelect.value, monthSelect.value);
      daySelect.value = selectedDay ? String(selectedDay) : '';
      syncHiddenValue();

      [daySelect, monthSelect, yearSelect].forEach(function (select) {
        select.addEventListener('change', syncHiddenValue);
      });

      input.classList.add('buddhist-date__hidden');
      input.parentNode.insertBefore(wrapper, input.nextSibling);
      wrapper.appendChild(daySelect);
      wrapper.appendChild(monthSelect);
      wrapper.appendChild(yearSelect);
    }

    document.querySelectorAll('input[type="date"][data-buddhist-date]').forEach(function (input) {
      createBuddhistDatePicker(input);
    });
  })();
</script>
<script>
  (function () {
    var path = window.location.pathname;
    var links = Array.from(document.querySelectorAll('.sidebar-link, .sidebar-sublink'));

    function isMatch(href) {
      if (!href) return false;
      if (href === path) return true;
      if (href === '/admin') return path === '/admin';
      return path.startsWith(href + '/');
    }

    var matchedLinks = links.filter(function (link) {
      return isMatch(link.getAttribute('href'));
    });

    if (matchedLinks.length === 0) return;

    var activeLink = matchedLinks.sort(function (a, b) {
      return b.getAttribute('href').length - a.getAttribute('href').length;
    })[0];

    activeLink.classList.add('is-active');

    var group = activeLink.closest('details.sidebar-group');
    if (group) group.open = true;
  })();
</script>
<script>
  (function () {
    var thaiMonths = [
      'มกราคม',
      'กุมภาพันธ์',
      'มีนาคม',
      'เมษายน',
      'พฤษภาคม',
      'มิถุนายน',
      'กรกฎาคม',
      'สิงหาคม',
      'กันยายน',
      'ตุลาคม',
      'พฤศจิกายน',
      'ธันวาคม'
    ];
    var thaiWeekdays = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];

    function padDatePart(value) {
      return String(value).padStart(2, '0');
    }

    function daysInMonth(year, monthIndex) {
      return new Date(year, monthIndex + 1, 0).getDate();
    }

    function parseInputValue(value) {
      if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return null;
      }

      var parts = value.split('-').map(Number);
      return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function formatDisplayValue(date) {
      if (!date) return '';

      return [
        date.getDate(),
        thaiMonths[date.getMonth()],
        date.getFullYear() + 543
      ].join(' ');
    }

    function createSelect(className, options) {
      var select = document.createElement('select');
      select.className = 'input ' + className;

      options.forEach(function (option) {
        var optionEl = document.createElement('option');
        optionEl.value = option.value;
        optionEl.textContent = option.label;
        select.appendChild(optionEl);
      });

      return select;
    }

    function closeAllPickers() {
      document.querySelectorAll('.buddhist-date.is-open').forEach(function (element) {
        element.classList.remove('is-open');
        var button = element.querySelector('.buddhist-date__toggle');
        if (button) {
          button.setAttribute('aria-expanded', 'false');
        }
      });
    }

    function removeLegacyWrapper(input) {
      var sibling = input.nextElementSibling;
      if (!sibling || !sibling.classList || !sibling.classList.contains('buddhist-date')) {
        return;
      }

      if (sibling.getAttribute('data-picker-mode') === 'calendar') {
        sibling.remove();
        return;
      }

      sibling.remove();
    }

    function createBuddhistDatePicker(input) {
      removeLegacyWrapper(input);

      var today = new Date();
      var selectedDate = parseInputValue(input.value);
      var visibleDate = selectedDate
        ? new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1)
        : new Date(today.getFullYear(), today.getMonth(), 1);

      var wrapper = document.createElement('div');
      wrapper.className = 'buddhist-date';
      wrapper.setAttribute('data-picker-mode', 'calendar');

      var toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = 'buddhist-date__toggle';
      toggle.setAttribute('aria-expanded', 'false');

      var toggleLabel = document.createElement('span');
      toggleLabel.className = 'buddhist-date__toggle-label';

      var icon = document.createElement('span');
      icon.className = 'buddhist-date__icon';
      icon.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M7 2v3M17 2v3M4 8h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

      toggle.appendChild(toggleLabel);
      toggle.appendChild(icon);

      var panel = document.createElement('div');
      panel.className = 'buddhist-date__panel';

      var controls = document.createElement('div');
      controls.className = 'buddhist-date__controls';

      var monthSelect = createSelect(
        'buddhist-date__month',
        thaiMonths.map(function (month, index) {
          return { value: String(index), label: month };
        })
      );

      var yearOptions = [];
      var currentYear = today.getFullYear();
      for (var year = currentYear - 100; year <= currentYear + 10; year += 1) {
        yearOptions.push({ value: String(year), label: String(year + 543) });
      }
      var yearSelect = createSelect('buddhist-date__year', yearOptions);

      controls.appendChild(monthSelect);
      controls.appendChild(yearSelect);

      var weekdays = document.createElement('div');
      weekdays.className = 'buddhist-date__weekdays';
      thaiWeekdays.forEach(function (label) {
        var weekday = document.createElement('div');
        weekday.className = 'buddhist-date__weekday';
        weekday.textContent = label;
        weekdays.appendChild(weekday);
      });

      var grid = document.createElement('div');
      grid.className = 'buddhist-date__grid';

      panel.appendChild(controls);
      panel.appendChild(weekdays);
      panel.appendChild(grid);

      function updateToggleLabel() {
        if (selectedDate) {
          toggleLabel.textContent = formatDisplayValue(selectedDate);
          toggleLabel.classList.remove('buddhist-date__toggle-placeholder');
        } else {
          toggleLabel.textContent = input.getAttribute('placeholder') || 'วว/ดด/ปปปป';
          toggleLabel.classList.add('buddhist-date__toggle-placeholder');
        }
      }

      function syncHiddenValue() {
        if (!selectedDate) {
          input.value = '';
          return;
        }

        input.value = [
          selectedDate.getFullYear(),
          padDatePart(selectedDate.getMonth() + 1),
          padDatePart(selectedDate.getDate())
        ].join('-');
      }

      function closePicker() {
        wrapper.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      }

      function renderCalendar() {
        monthSelect.value = String(visibleDate.getMonth());
        yearSelect.value = String(visibleDate.getFullYear());
        grid.innerHTML = '';

        var displayYear = visibleDate.getFullYear();
        var displayMonth = visibleDate.getMonth();
        var firstDay = new Date(displayYear, displayMonth, 1).getDay();
        var totalDays = daysInMonth(displayYear, displayMonth);
        var prevMonthDays = new Date(displayYear, displayMonth, 0).getDate();

        for (var leading = 0; leading < firstDay; leading += 1) {
          var prevDayButton = document.createElement('button');
          prevDayButton.type = 'button';
          prevDayButton.className = 'buddhist-date__day is-outside';
          prevDayButton.textContent = String(prevMonthDays - firstDay + leading + 1);
          prevDayButton.tabIndex = -1;
          grid.appendChild(prevDayButton);
        }

        for (var day = 1; day <= totalDays; day += 1) {
          (function (pickedDay) {
            var candidateDate = new Date(displayYear, displayMonth, pickedDay);
            var dayButton = document.createElement('button');
            dayButton.type = 'button';
            dayButton.className = 'buddhist-date__day';
            dayButton.textContent = String(pickedDay);

            if (
              selectedDate &&
              candidateDate.getFullYear() === selectedDate.getFullYear() &&
              candidateDate.getMonth() === selectedDate.getMonth() &&
              candidateDate.getDate() === selectedDate.getDate()
            ) {
              dayButton.classList.add('is-selected');
            }

            if (
              candidateDate.getFullYear() === today.getFullYear() &&
              candidateDate.getMonth() === today.getMonth() &&
              candidateDate.getDate() === today.getDate()
            ) {
              dayButton.classList.add('is-today');
            }

            dayButton.addEventListener('click', function () {
              selectedDate = candidateDate;
              syncHiddenValue();
              updateToggleLabel();
              renderCalendar();
              closePicker();
            });

            grid.appendChild(dayButton);
          })(day);
        }

        var filledCells = firstDay + totalDays;
        var trailingDays = (7 - (filledCells % 7)) % 7;

        for (var trailing = 1; trailing <= trailingDays; trailing += 1) {
          var nextDayButton = document.createElement('button');
          nextDayButton.type = 'button';
          nextDayButton.className = 'buddhist-date__day is-outside';
          nextDayButton.textContent = String(trailing);
          nextDayButton.tabIndex = -1;
          grid.appendChild(nextDayButton);
        }
      }

      monthSelect.addEventListener('change', function () {
        visibleDate = new Date(Number(yearSelect.value), Number(monthSelect.value), 1);
        renderCalendar();
      });

      yearSelect.addEventListener('change', function () {
        visibleDate = new Date(Number(yearSelect.value), Number(monthSelect.value), 1);
        renderCalendar();
      });

      toggle.addEventListener('click', function () {
        var shouldOpen = !wrapper.classList.contains('is-open');
        closeAllPickers();

        if (shouldOpen) {
          wrapper.classList.add('is-open');
          toggle.setAttribute('aria-expanded', 'true');
        }
      });

      input.classList.add('buddhist-date__hidden');
      input.parentNode.insertBefore(wrapper, input.nextSibling);
      wrapper.appendChild(toggle);
      wrapper.appendChild(panel);

      updateToggleLabel();
      syncHiddenValue();
      renderCalendar();
    }

    document.addEventListener('click', function (event) {
      if (!event.target.closest('.buddhist-date')) {
        closeAllPickers();
      }
    });

    document.querySelectorAll('input[type="date"][data-buddhist-date]').forEach(function (input) {
      createBuddhistDatePicker(input);
    });
  })();
</script>
<script>
  (function () {
    var permissions = @json($menuPermissions);
    if (!permissions || Object.keys(permissions).length === 0) return;

    var hrefMap = {
      '/admin': 'dashboard',
      '/admin/users': 'farmer_users',
      '/admin/farmer-users': 'farmer_users',
      '/admin/admin-users': 'admin_users',
      '/admin/roles': 'roles',
      '/admin/tracking/prep': 'tracking_prep',
      '/admin/tracking/water': 'tracking_water',
      '/admin/tracking/fertilizer': 'tracking_fertilizer',
      '/admin/tracking/pest': 'tracking_pest',
      '/admin/tracking/disease': 'tracking_disease',
      '/admin/tracking/harvest': 'tracking_harvest',
      '/admin/tracking/mill': 'tracking_mill',
      '/admin/srp': 'srp_manual',
      '/admin/srp/farmers': 'srp_farmers',
      '/admin/rice': 'rice',
      '/admin/report/rice': 'report_rice',
      '/admin/report/system': 'report_system',
      '/admin/settings': 'settings'
    };

    Object.keys(hrefMap).forEach(function (href) {
      var key = hrefMap[href];
      if (permissions[key] !== false) return;

      var link = document.querySelector('.sidebar-nav a[href="' + href + '"]');
      if (!link) return;

      link.style.display = 'none';
    });

    document.querySelectorAll('details.sidebar-group').forEach(function (group) {
      var visibleLinks = Array.from(group.querySelectorAll('a.sidebar-sublink')).filter(function (link) {
        return link.style.display !== 'none';
      });

      if (visibleLinks.length === 0) {
        group.style.display = 'none';
      }
    });
  })();
</script>
<script>
  (function () {
    var alerts = document.querySelectorAll('.flash-auto-dismiss, .settings-alert.success, .settings-alert.warning, .status-banner.success, .status-banner.error');
    if (!alerts.length) return;

    alerts.forEach(function (alertEl) {
      window.setTimeout(function () {
        alertEl.classList.add('is-hiding');
        window.setTimeout(function () {
          if (alertEl.parentNode) {
            alertEl.parentNode.removeChild(alertEl);
          }
        }, 400);
      }, 5000);
    });
  })();
</script>
<script>
  (function () {
    var modal = document.getElementById('confirm-modal');
    var messageEl = document.getElementById('confirm-modal-message');
    var cancelBtn = document.getElementById('confirm-modal-cancel');
    var submitBtn = document.getElementById('confirm-modal-submit');
    if (!modal || !messageEl || !cancelBtn || !submitBtn) return;

    var activeForm = null;

    function closeModal() {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      activeForm = null;
    }

    function openModal(form) {
      activeForm = form;
      messageEl.textContent = form.getAttribute('data-confirm-delete') || 'คุณต้องการลบข้อมูลนี้ใช่หรือไม่?';
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
    }

    document.querySelectorAll('form[data-confirm-delete]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (form.dataset.confirmed === 'true') {
          form.dataset.confirmed = 'false';
          return;
        }

        event.preventDefault();
        openModal(form);
      });
    });

    submitBtn.addEventListener('click', function () {
      if (!activeForm) return;
      activeForm.dataset.confirmed = 'true';
      activeForm.requestSubmit();
      closeModal();
    });

    cancelBtn.addEventListener('click', closeModal);
    modal.querySelectorAll('[data-confirm-close]').forEach(function (element) {
      element.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal.classList.contains('is-open')) {
        closeModal();
      }
    });
  })();
</script>
<script src="{{ asset('js/thai-address.js') }}"></script>
<script>
  (function () {
    function findTable(selectEl) {
      var card = selectEl.closest('.card');
      if (card && card.nextElementSibling && card.nextElementSibling.querySelector) {
        var nextTable = card.nextElementSibling.querySelector('table');
        if (nextTable) return nextTable;
      }
      return document.querySelector('table.table');
    }

    function getRoundColumnIndex(table) {
      if (!table) return -1;
      var headers = table.querySelectorAll('thead th');
      for (var i = 0; i < headers.length; i++) {
        var text = headers[i].textContent.trim();
        if (text === 'ครั้งที่') return i;
      }
      return -1;
    }

    function applyRoundFilter(selectEl) {
      var table = findTable(selectEl);
      if (!table) return;
      var colIndex = getRoundColumnIndex(table);
      if (colIndex === -1) return;

      var value = selectEl.value.trim();
      var isAll = value === '' || value === 'ครั้งที่';
      var rows = table.querySelectorAll('tbody tr');
      rows.forEach(function (row) {
        var cell = row.children[colIndex];
        var cellText = cell ? cell.textContent.trim() : '';
        row.style.display = isAll || cellText === value ? '' : 'none';
      });
    }

    var selects = document.querySelectorAll('.filter-round');
    selects.forEach(function (selectEl) {
      selectEl.addEventListener('change', function () {
        applyRoundFilter(selectEl);
      });
    });
  })();
</script>
<script>
  (function () {
    var searchUtils = window.SrpSearchUtils;
    if (!searchUtils) return;

    function readCellText(row, index) {
      if (index === null || index === undefined || index < 0) return '';

      var cell = row.children[index];
      return cell ? cell.textContent.trim() : '';
    }

    function normalizeHeaderLabel(value) {
      return (value || '').replace(/\s+/g, '').trim();
    }

    function findColumnIndex(table, labels) {
      var normalizedLabels = labels.map(normalizeHeaderLabel);
      var headers = Array.from(table.querySelectorAll('thead th'));

      for (var i = 0; i < headers.length; i += 1) {
        var headerLabel = normalizeHeaderLabel(headers[i].textContent);
        if (normalizedLabels.indexOf(headerLabel) !== -1) {
          return i;
        }
      }

      return null;
    }

    function setupTrackingLiveSearch(form) {
      var action = form.getAttribute('action') || '';
      if (action.indexOf('/admin/tracking/') === -1) return;

      var searchInput = form.querySelector('input[name="q"]');
      var roundSelect = form.querySelector('select[name="round"]');
      var statusSelect = form.querySelector('select[name="status"]');
      var dateInput = form.querySelector('input[name="date"]');
      if (!searchInput && !roundSelect && !statusSelect && !dateInput) return;

      var card = form.closest('.card');
      var table = card && card.nextElementSibling ? card.nextElementSibling.querySelector('table.table') : null;
      if (!table) return;

      var columnIndexes = {
        farmer: findColumnIndex(table, ['เกษตรกร']),
        plotName: findColumnIndex(table, ['ชื่อแปลง']),
        plotCode: findColumnIndex(table, ['รหัสแปลง']),
        round: findColumnIndex(table, ['ครั้งที่']),
        activity: findColumnIndex(table, ['กิจกรรม']),
        date: findColumnIndex(table, ['วันที่']),
        status: findColumnIndex(table, ['สถานะ'])
      };

      var rows = Array.from(table.querySelectorAll('tbody tr')).filter(function (row) {
        return row.children.length > 0;
      });
      if (!rows.length) return;

      var priorityColumns = [
        columnIndexes.farmer,
        columnIndexes.plotName,
        columnIndexes.plotCode,
        columnIndexes.round,
        columnIndexes.activity,
        columnIndexes.date,
        columnIndexes.status
      ].filter(function (index) {
        return index !== null && index !== undefined;
      });
      var statusMap = {
        pending_review: 'รอตรวจสอบ',
        passed: 'ผ่านแล้ว',
        needs_fix: 'ต้องแก้ไข',
        failed: 'ไม่ผ่าน'
      };

      function findActiveColumn(keyword) {
        if (!keyword) return null;

        for (var i = 0; i < priorityColumns.length; i += 1) {
          var columnIndex = priorityColumns[i];
          var hasMatch = rows.some(function (row) {
            return searchUtils.matchesFromStart(readCellText(row, columnIndex), keyword);
          });

          if (hasMatch) {
            return columnIndex;
          }
        }

        return null;
      }

      function normalizeTableDate(value) {
        if (!value) return '';

        var parsed = new Date(value);
        if (Number.isNaN(parsed.getTime())) return '';

        var year = parsed.getFullYear();
        var month = String(parsed.getMonth() + 1).padStart(2, '0');
        var day = String(parsed.getDate()).padStart(2, '0');

        return year + '-' + month + '-' + day;
      }

      function applyLiveSearch() {
        var keyword = searchInput ? searchInput.value.trim() : '';
        var roundValue = roundSelect ? roundSelect.value.trim() : '';
        var statusValue = statusSelect ? statusSelect.value.trim() : '';
        var dateValue = dateInput ? dateInput.value.trim() : '';
        var activeColumn = findActiveColumn(keyword);

        rows.forEach(function (row) {
          var matchesKeyword = !keyword || (
            activeColumn !== null
            && searchUtils.matchesFromStart(readCellText(row, activeColumn), keyword)
          );
          var matchesRound = !roundValue || (
            columnIndexes.round !== null
            && readCellText(row, columnIndexes.round) === roundValue
          );
          var matchesStatus = !statusValue || (
            columnIndexes.status !== null
            && readCellText(row, columnIndexes.status).indexOf(statusMap[statusValue] || '') !== -1
          );
          var matchesDate = !dateValue || (
            columnIndexes.date !== null
            && normalizeTableDate(readCellText(row, columnIndexes.date)) === dateValue
          );
          var matched = matchesKeyword && matchesRound && matchesStatus && matchesDate;

          row.style.display = matched ? '' : 'none';
        });
      }

      if (searchInput) {
        searchInput.addEventListener('input', applyLiveSearch);
      }

      if (roundSelect) {
        roundSelect.addEventListener('change', applyLiveSearch);
      }

      if (statusSelect) {
        statusSelect.addEventListener('change', applyLiveSearch);
      }

      if (dateInput) {
        dateInput.addEventListener('change', applyLiveSearch);
      }

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        applyLiveSearch();
      });

      applyLiveSearch();
    }

    document.querySelectorAll('form.prep-filters').forEach(setupTrackingLiveSearch);
  })();
</script>
@stack('scripts')
</body>
</html>
