@extends('admin.layout')

@section('title', 'การแจ้งเตือน')

@section('content')
@php
  $demoAlerts = collect([
      [
          'id' => 'demo-today-1',
          'title' => 'งานต้องตรวจวันนี้: ติดตามการจัดการศัตรูพืช แปลงหนองขาม 02',
          'detail' => 'นัดลงพื้นที่ตรวจแปลงของคุณสมชาย ใจดี เพื่อตรวจร่องรอยเพลี้ยและยืนยันบันทึกการพ่นสารช่วงเช้า',
          'chip_label' => 'ต้องตรวจวันนี้',
          'chip_class' => 'warning',
          'dot_class' => 'warning',
          'meta' => 'คุณสมชาย ใจดี • NK-02 • 10 พ.ค. 2026 09:00',
          'detail_url' => '/admin/tracking/pest',
          'detail_label' => 'เปิดงานตรวจ',
          'group' => 'today',
          'scope_label' => 'งานติดตามวันนี้',
      ],
      [
          'id' => 'demo-today-2',
          'title' => 'งานต้องตรวจวันนี้: ยืนยันการจัดการน้ำ รอบเช้า แปลงคลองส่งน้ำ 04',
          'detail' => 'ค่าน้ำลดลงเร็วกว่าปกติจากบันทึกล่าสุด ต้องตรวจซ้ำก่อน 10:30 น. และยืนยันคำแนะนำในระบบ',
          'chip_label' => 'ต้องตรวจวันนี้',
          'chip_class' => 'warning',
          'dot_class' => 'warning',
          'meta' => 'คุณวารินทร์ ทองคำ • WT-04 • 10 พ.ค. 2026 10:15',
          'detail_url' => '/admin/tracking/water',
          'detail_label' => 'เปิดงานตรวจ',
          'group' => 'today',
          'scope_label' => 'งานติดตามวันนี้',
      ],
      [
          'id' => 'demo-today-3',
          'title' => 'งานต้องตรวจวันนี้: ตรวจการหว่านปุ๋ยครั้งที่ 2 แปลงสาธิตทุ่งยาว',
          'detail' => 'มีแผนนัดติดตามภาคสนามและตรวจว่าปริมาณปุ๋ยที่บันทึกสอดคล้องกับรอบการใส่จริงหรือไม่',
          'chip_label' => 'ต้องตรวจวันนี้',
          'chip_class' => 'warning',
          'dot_class' => 'warning',
          'meta' => 'คุณลัดดา ศรีสุข • FT-07 • 10 พ.ค. 2026 11:00',
          'detail_url' => '/admin/tracking/fertilizer',
          'detail_label' => 'เปิดงานตรวจ',
          'group' => 'today',
          'scope_label' => 'งานติดตามวันนี้',
      ],
      [
          'id' => 'demo-today-4',
          'title' => 'งานต้องตรวจวันนี้: ตรวจเอกสารประกอบ SRP ของสมาชิกใหม่ 3 ราย',
          'detail' => 'เอกสารบัตรประชาชนและข้อมูลแปลงยังรอตรวจความครบถ้วนก่อนอนุมัติเข้าชุดประเมินรอบเดือนพฤษภาคม',
          'chip_label' => 'ต้องตรวจวันนี้',
          'chip_class' => 'warning',
          'dot_class' => 'info',
          'meta' => 'กลุ่มเกษตรกรบ้านดอน • DOC-03 • 10 พ.ค. 2026 13:30',
          'detail_url' => '/admin/farmer-users',
          'detail_label' => 'ตรวจเอกสาร',
          'group' => 'today',
          'scope_label' => 'งานติดตามวันนี้',
      ],
      [
          'id' => 'demo-today-5',
          'title' => 'งานต้องตรวจวันนี้: ติดตามผลเก็บเกี่ยวแปลงสาธิตรอบเย็น',
          'detail' => 'ต้องสรุปผลผลิตและยืนยันตัวเลขก่อนส่งเข้ารายงานสรุปประจำวันของผู้ดูแลระบบ',
          'chip_label' => 'ต้องตรวจวันนี้',
          'chip_class' => 'warning',
          'dot_class' => 'warning',
          'meta' => 'คุณประยูร แก้วดี • HV-01 • 10 พ.ค. 2026 16:00',
          'detail_url' => '/admin/tracking/harvest',
          'detail_label' => 'เปิดงานตรวจ',
          'group' => 'today',
          'scope_label' => 'งานติดตามวันนี้',
      ],
      [
          'id' => 'demo-problem-1',
          'title' => 'แจ้งเตือนรายงานปัญหา: ภาพกิจกรรมเตรียมดินไม่ชัด',
          'detail' => 'ผู้ใช้ส่งภาพใหม่มาแล้ว แต่ยังต้องตรวจว่าหลักฐานรอบล่าสุดอ่านรายละเอียดเครื่องจักรได้ครบหรือไม่',
          'chip_label' => 'รอตรวจสอบ',
          'chip_class' => 'danger',
          'dot_class' => 'danger',
          'meta' => 'คุณชูชาติ มั่นคง • PR-12 • รายงานปัญหาการปลูกข้าว',
          'detail_url' => '/admin/report/rice',
          'detail_label' => 'ดูรายงานปัญหา',
          'group' => 'problem',
          'scope_label' => 'รายงานปัญหา',
      ],
      [
          'id' => 'demo-problem-2',
          'title' => 'แจ้งเตือนรายงานปัญหา: ค่าระดับน้ำแปลงคลองส่งน้ำ 04 ผิดปกติ',
          'detail' => 'ระบบตรวจพบค่าลดลงต่อเนื่องจาก 3 บันทึกล่าสุด และเกษตรกรแจ้งว่าคันนาอาจรั่ว',
          'chip_label' => 'เร่งด่วน',
          'chip_class' => 'danger',
          'dot_class' => 'danger',
          'meta' => 'คุณวารินทร์ ทองคำ • WT-04 • รายงานปัญหาการปลูกข้าว',
          'detail_url' => '/admin/report/rice',
          'detail_label' => 'ดูรายงานปัญหา',
          'group' => 'problem',
          'scope_label' => 'รายงานปัญหา',
      ],
      [
          'id' => 'demo-problem-3',
          'title' => 'แจ้งเตือนรายงานปัญหา: เกษตรกรเข้าเมนูบันทึกกิจกรรมไม่ได้',
          'detail' => 'มีการแจ้งซ้ำจากพื้นที่เดียวกัน 2 ราย คาดว่าเกี่ยวกับสิทธิ์เมนูหลังอัปเดตบัญชีผู้ใช้',
          'chip_label' => 'รอทีมระบบ',
          'chip_class' => 'info',
          'dot_class' => 'warning',
          'meta' => 'ตั๋วระบบ ST-204 • รายงานปัญหาการใช้งานระบบ',
          'detail_url' => '/admin/report/system',
          'detail_label' => 'ดูปัญหาระบบ',
          'group' => 'problem',
          'scope_label' => 'รายงานปัญหา',
      ],
      [
          'id' => 'demo-problem-4',
          'title' => 'แจ้งเตือนรายงานปัญหา: พิกัด GPS แปลงนาไม่ตรงกับตำแหน่งจริง',
          'detail' => 'แปลงของสมาชิกใหม่เลื่อนออกจากขอบเขตเดิมประมาณ 120 เมตร ต้องตรวจซ้ำก่อนประเมิน SRP',
          'chip_label' => 'ต้องแก้ไข',
          'chip_class' => 'warning',
          'dot_class' => 'warning',
          'meta' => 'คุณกนกวรรณ ผลดี • SRP-09 • รายงานปัญหาการปลูกข้าว',
          'detail_url' => '/admin/report/rice',
          'detail_label' => 'ดูรายงานปัญหา',
          'group' => 'problem',
          'scope_label' => 'รายงานปัญหา',
      ],
      [
          'id' => 'demo-problem-5',
          'title' => 'แจ้งเตือนรายงานปัญหา: บันทึกโรคพืชซ้ำซ้อนในรอบเดียวกัน',
          'detail' => 'พบรายการโรคใบจุดซ้ำ 2 รายการในแปลงเดียว ต้องตรวจว่าผู้ใช้กดบันทึกซ้ำหรือมีหลายจุดจริง',
          'chip_label' => 'รอตรวจสอบ',
          'chip_class' => 'danger',
          'dot_class' => 'warning',
          'meta' => 'คุณนิภาพร บุญมาก • DS-03 • รายงานปัญหาการปลูกข้าว',
          'detail_url' => '/admin/report/rice',
          'detail_label' => 'ดูรายงานปัญหา',
          'group' => 'problem',
          'scope_label' => 'รายงานปัญหา',
      ],
      [
          'id' => 'demo-problem-6',
          'title' => 'แจ้งเตือนรายงานปัญหา: น้ำหนักผลผลิตไม่ตรงกับบันทึกการขายเข้าโรงสี',
          'detail' => 'ส่วนต่าง 320 กิโลกรัมระหว่างหน้าสรุปเก็บเกี่ยวกับบันทึกขาย ต้องยืนยันตัวเลขก่อนปิดรอบ',
          'chip_label' => 'เร่งด่วน',
          'chip_class' => 'danger',
          'dot_class' => 'danger',
          'meta' => 'คุณประยูร แก้วดี • ML-08 • รายงานปัญหาการปลูกข้าว',
          'detail_url' => '/admin/report/rice',
          'detail_label' => 'ดูรายงานปัญหา',
          'group' => 'problem',
          'scope_label' => 'รายงานปัญหา',
      ],
      [
          'id' => 'demo-problem-7',
          'title' => 'แจ้งเตือนรายงานปัญหา: ข้อมูลติดต่อเกษตรกรไม่อัปเดต',
          'detail' => 'เบอร์โทรศัพท์ติดต่อไม่ได้และมีผลกับการนัดลงพื้นที่ ต้องตรวจจากเอกสารสมัครและข้อมูลภาคสนาม',
          'chip_label' => 'ต้องแก้ไข',
          'chip_class' => 'warning',
          'dot_class' => 'info',
          'meta' => 'คุณสุพัตรา ขันดี • DOC-11 • รายงานปัญหาข้อมูลเกษตรกร',
          'detail_url' => '/admin/farmer-users',
          'detail_label' => 'เปิดข้อมูลเกษตรกร',
          'group' => 'problem',
          'scope_label' => 'รายงานปัญหา',
      ],
      [
          'id' => 'demo-problem-8',
          'title' => 'แจ้งเตือนรายงานปัญหา: ระบบส่งรหัสยืนยันล่าช้าช่วงเช้า',
          'detail' => 'ผู้ดูแลพื้นที่แจ้งว่าการรีเซ็ตรหัสผ่านใช้เวลานานกว่าปกติในช่วง 08:00-08:30 น.',
          'chip_label' => 'รอทีมระบบ',
          'chip_class' => 'info',
          'dot_class' => 'info',
          'meta' => 'ตั๋วระบบ ST-205 • รายงานปัญหาการใช้งานระบบ',
          'detail_url' => '/admin/report/system',
          'detail_label' => 'ดูปัญหาระบบ',
          'group' => 'problem',
          'scope_label' => 'รายงานปัญหา',
      ],
      [
          'id' => 'demo-other-1',
          'title' => 'ประกาศเตือนความพร้อม: อบรมผู้ดูแลพื้นที่ SRP รอบใหม่สัปดาห์หน้า',
          'detail' => 'ขอให้ผู้ดูแลทุกพื้นที่ตรวจรายชื่อผู้เข้าร่วมและยืนยันอุปกรณ์ที่ต้องใช้ก่อนวันประชุม',
          'chip_label' => 'ข้อมูลทั่วไป',
          'chip_class' => 'info',
          'dot_class' => 'info',
          'meta' => 'ฝ่ายประสานงาน SRP • 12 พ.ค. 2026 09:30',
          'detail_url' => '/admin/srp',
          'detail_label' => 'ดูรายละเอียด',
          'group' => 'other',
          'scope_label' => 'ประกาศและอื่นๆ',
      ],
      [
          'id' => 'demo-other-2',
          'title' => 'แจ้งเตือนระบบ: สำรองข้อมูลรายสัปดาห์สำเร็จ',
          'detail' => 'การสำรองข้อมูลรอบวันเสาร์เสร็จสมบูรณ์และพร้อมใช้งานหากต้องเรียกคืนข้อมูลย้อนหลัง',
          'chip_label' => 'ข้อมูลทั่วไป',
          'chip_class' => 'success',
          'dot_class' => 'info',
          'meta' => 'ระบบส่วนกลาง • 09 พ.ค. 2026 23:40',
          'detail_url' => '/admin/settings',
          'detail_label' => 'ดูรายละเอียด',
          'group' => 'other',
          'scope_label' => 'ประกาศและอื่นๆ',
      ],
  ]);

  $alerts = isset($alerts) && $alerts instanceof \Illuminate\Support\Collection ? $alerts : collect($alerts ?? []);
  $isDemoMode = $alerts->isEmpty();

  if ($isDemoMode) {
      $alerts = $demoAlerts;
      $query = $query ?? '';
      $scope = $scope ?? 'all';
      $status = $status ?? '';
  } else {
      $alerts = $alerts->map(function (array $alert): array {
          $title = (string) ($alert['title'] ?? '');
          $detail = (string) ($alert['detail'] ?? '');
          $meta = (string) ($alert['meta'] ?? '');
          $titleAndDetail = mb_strtolower($title . ' ' . $detail . ' ' . $meta);

          $group = 'other';
          $scopeLabel = 'ประกาศและอื่นๆ';

          if (str_contains($titleAndDetail, 'วันนี้') || str_contains($titleAndDetail, 'ต้องตรวจ')) {
              $group = 'today';
              $scopeLabel = 'งานติดตามวันนี้';
          } elseif (str_contains($titleAndDetail, 'ปัญหา') || str_contains($titleAndDetail, 'ticket') || str_contains($titleAndDetail, 'issue')) {
              $group = 'problem';
              $scopeLabel = 'รายงานปัญหา';
          }

          return $alert + [
              'group' => $group,
              'scope_label' => $scopeLabel,
          ];
      });
  }

  $summary = [
      'all' => $alerts->count(),
      'today' => $alerts->where('group', 'today')->count(),
      'problem' => $alerts->where('group', 'problem')->count(),
      'other' => $alerts->where('group', 'other')->count(),
  ];
@endphp

<style>
  .alerts-demo {
    display: grid;
    gap: 16px;
  }
  .alerts-demo__hero {
    display: grid;
    gap: 14px;
  }
  .alerts-demo__summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
  }
  .alerts-demo__stat {
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    padding: 14px 16px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }
  .alerts-demo__stat strong {
    display: block;
    font-size: 28px;
    line-height: 1;
    color: #0f172a;
  }
  .alerts-demo__stat span {
    display: block;
    margin-top: 6px;
    color: #64748b;
    font-size: 13px;
  }
  .alerts-demo__toolbar {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 220px 220px auto;
    gap: 12px;
    align-items: center;
  }
  .alerts-demo__hint {
    font-size: 13px;
    color: #64748b;
  }
  .alerts-demo__list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 12px;
  }
  .alerts-demo__item {
    display: grid;
    grid-template-columns: 14px minmax(0, 1fr) auto;
    gap: 14px;
    align-items: center;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    padding: 14px 16px;
    background: #fff;
  }
  .alerts-demo__dot {
    width: 12px;
    height: 12px;
    border-radius: 999px;
    margin-top: 2px;
  }
  .alerts-demo__body {
    min-width: 0;
  }
  .alerts-demo__title {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.4;
  }
  .alerts-demo__detail {
    margin-top: 4px;
    color: #475569;
    line-height: 1.55;
  }
  .alerts-demo__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 10px;
    margin-top: 10px;
    font-size: 12px;
    color: #64748b;
  }
  .alerts-demo__meta .chip {
    margin: 0;
  }
  .alerts-demo__actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
  }
  .alerts-demo__empty {
    text-align: center;
    padding: 26px 12px 10px;
    color: #64748b;
  }
  .alerts-demo__badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 12px;
    border-radius: 999px;
    background: #ecfdf5;
    color: #166534;
    font-size: 13px;
    font-weight: 600;
  }
  .alerts-demo__badge.is-demo {
    background: #eff6ff;
    color: #1d4ed8;
  }
  .alerts-demo__filters {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }
  .alerts-demo__filters .chip {
    cursor: pointer;
  }
  .alerts-demo__filters .chip.is-active {
    background: #dcfce7;
    color: #166534;
  }

  @media (max-width: 960px) {
    .alerts-demo__summary,
    .alerts-demo__toolbar {
      grid-template-columns: 1fr;
    }

    .alerts-demo__item {
      grid-template-columns: 14px minmax(0, 1fr);
    }

    .alerts-demo__actions {
      grid-column: 2;
      justify-content: flex-start;
    }
  }
</style>

<div class="alerts-demo">
  <div class="page-head">
    <div class="page-title">
      <a class="back-link icon-only" href="/admin" aria-label="กลับไปหน้าแดชบอร์ด">
        <span class="back-icon">‹</span>
      </a>
      <div>
        <h1>การแจ้งเตือน</h1>
        <p class="muted">รวมรายการเดโม่ที่ต้องติดตามในระบบ แบ่งเป็นงานต้องตรวจวันนี้ 5 รายการ รายงานปัญหา 8 รายการ และเรื่องอื่น ๆ 2 รายการ</p>
      </div>
    </div>
    <div class="page-actions">
      <span class="alerts-demo__badge {{ $isDemoMode ? 'is-demo' : '' }}">
        {{ $isDemoMode ? 'โหมดเดโม่ 15 รายการ' : 'ข้อมูลจากระบบจริง' }}
      </span>
    </div>
  </div>

  <div class="alerts-demo__hero">
    <div class="alerts-demo__summary">
      <div class="alerts-demo__stat">
        <strong>{{ $summary['all'] }}</strong>
        <span>แจ้งเตือนทั้งหมด</span>
      </div>
      <div class="alerts-demo__stat">
        <strong>{{ $summary['today'] }}</strong>
        <span>งานต้องตรวจวันที่ 10 พ.ค. 2026</span>
      </div>
      <div class="alerts-demo__stat">
        <strong>{{ $summary['problem'] }}</strong>
        <span>รายงานปัญหาที่ต้องตามต่อ</span>
      </div>
      <div class="alerts-demo__stat">
        <strong>{{ $summary['other'] }}</strong>
        <span>ประกาศและแจ้งเตือนทั่วไป</span>
      </div>
    </div>

    <div class="card">
      <div class="alerts-demo__toolbar">
        <input
          class="input"
          id="alerts-search"
          type="text"
          value="{{ $query ?? '' }}"
          placeholder="ค้นหาหัวข้อ รายละเอียด เกษตรกร รหัสแปลง หรือประเภทแจ้งเตือน"
          aria-label="ค้นหารายการแจ้งเตือน"
        >

        <select class="input" id="alerts-group-filter" aria-label="เลือกกลุ่มแจ้งเตือน">
          <option value="all">ทุกกลุ่มแจ้งเตือน</option>
          <option value="today">งานต้องตรวจวันนี้</option>
          <option value="problem">รายงานปัญหา</option>
          <option value="other">เรื่องอื่น ๆ</option>
        </select>

        <select class="input" id="alerts-scope-filter" aria-label="เลือกขอบเขตการค้นหา">
          <option value="all">ค้นหาทั้งหมด</option>
          <option value="title">เฉพาะหัวข้อ</option>
          <option value="detail">เฉพาะรายละเอียด</option>
          <option value="meta">เฉพาะข้อมูลอ้างอิง</option>
        </select>

        <button class="search-btn" type="button" aria-label="ค้นหา">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="7"></circle>
            <path d="M20 20l-3.5-3.5"></path>
          </svg>
        </button>
      </div>

      <div class="alerts-demo__filters" style="margin-top: 12px;">
        <button class="chip is-active" type="button" data-quick-group="all">ทั้งหมด {{ $summary['all'] }}</button>
        <button class="chip" type="button" data-quick-group="today">ต้องตรวจวันนี้ {{ $summary['today'] }}</button>
        <button class="chip" type="button" data-quick-group="problem">ปัญหา {{ $summary['problem'] }}</button>
        <button class="chip" type="button" data-quick-group="other">อื่น ๆ {{ $summary['other'] }}</button>
      </div>

      <p class="alerts-demo__hint" style="margin-top: 12px;">
        ชุดเดโม่นี้อ้างอิงกับข้อมูลตัวอย่างของหน้าเกษตรกร การติดตามแปลง รายงานปัญหาระบบ และเอกสาร SRP ในโปรเจกต์เดียวกัน
      </p>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <h3>รายการแจ้งเตือนล่าสุด</h3>
      <span class="muted" id="alerts-count">แสดง {{ $summary['all'] }} รายการ</span>
    </div>

    <div class="alerts-demo__list" id="alerts-list">
      @foreach ($alerts as $alert)
        @php
          $searchAll = implode(' ', array_filter([
              $alert['title'] ?? '',
              $alert['detail'] ?? '',
              $alert['meta'] ?? '',
              $alert['chip_label'] ?? '',
              $alert['scope_label'] ?? '',
          ]));
        @endphp
        <div
          class="alerts-demo__item"
          data-alert-item
          data-group="{{ $alert['group'] ?? 'other' }}"
          data-search-all="{{ \Illuminate\Support\Str::lower($searchAll) }}"
          data-search-title="{{ \Illuminate\Support\Str::lower((string) ($alert['title'] ?? '')) }}"
          data-search-detail="{{ \Illuminate\Support\Str::lower((string) ($alert['detail'] ?? '')) }}"
          data-search-meta="{{ \Illuminate\Support\Str::lower((string) ($alert['meta'] ?? '')) }}"
        >
          <span class="alerts-demo__dot alert-dot {{ $alert['dot_class'] ?? 'info' }}"></span>

          <div class="alerts-demo__body">
            <div class="alerts-demo__title">{{ $alert['title'] }}</div>
            <div class="alerts-demo__detail">{{ $alert['detail'] }}</div>
            <div class="alerts-demo__meta">
              <span class="chip info">{{ $alert['scope_label'] ?? 'แจ้งเตือนทั่วไป' }}</span>
              <span class="chip {{ $alert['chip_class'] ?? 'info' }}">{{ $alert['chip_label'] }}</span>
              <span>{{ $alert['meta'] }}</span>
            </div>
          </div>

          <div class="alerts-demo__actions">
            <a class="btn ghost" href="{{ $alert['detail_url'] }}">{{ $alert['detail_label'] }}</a>
          </div>
        </div>
      @endforeach
    </div>

    <div class="alerts-demo__empty" id="alerts-empty" style="display:none;">
      ไม่พบรายการแจ้งเตือนที่ตรงกับคำค้นหรือกลุ่มที่เลือก
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('alerts-search');
    var groupFilter = document.getElementById('alerts-group-filter');
    var scopeFilter = document.getElementById('alerts-scope-filter');
    var countEl = document.getElementById('alerts-count');
    var emptyEl = document.getElementById('alerts-empty');
    var quickButtons = Array.from(document.querySelectorAll('[data-quick-group]'));
    var items = Array.from(document.querySelectorAll('[data-alert-item]'));
    var searchUtils = window.SrpSearchUtils;

    function getHaystack(item, scope) {
      if (scope === 'title') return item.getAttribute('data-search-title') || '';
      if (scope === 'detail') return item.getAttribute('data-search-detail') || '';
      if (scope === 'meta') return item.getAttribute('data-search-meta') || '';
      return item.getAttribute('data-search-all') || '';
    }

    function setQuickGroup(group) {
      quickButtons.forEach(function (button) {
        button.classList.toggle('is-active', button.getAttribute('data-quick-group') === group);
      });
    }

    function matchesKeyword(haystack, keyword) {
      if (!keyword) return true;
      if (searchUtils) return searchUtils.matches(haystack, keyword);
      return String(haystack || '').toLowerCase().indexOf(keyword.toLowerCase()) !== -1;
    }

    function updateAlerts() {
      var keyword = (searchInput.value || '').trim();
      var group = groupFilter.value || 'all';
      var scope = scopeFilter.value || 'all';
      var visibleCount = 0;

      items.forEach(function (item) {
        var matchesGroup = group === 'all' || item.getAttribute('data-group') === group;
        var haystack = getHaystack(item, scope);
        var matched = matchesGroup && matchesKeyword(haystack, keyword);

        item.style.display = matched ? '' : 'none';
        if (matched) visibleCount += 1;
      });

      countEl.textContent = 'แสดง ' + visibleCount + ' รายการ';
      emptyEl.style.display = visibleCount === 0 ? 'block' : 'none';
      setQuickGroup(group);
    }

    searchInput.addEventListener('input', updateAlerts);
    groupFilter.addEventListener('change', updateAlerts);
    scopeFilter.addEventListener('change', updateAlerts);

    quickButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        groupFilter.value = button.getAttribute('data-quick-group') || 'all';
        updateAlerts();
      });
    });

    updateAlerts();
  });
</script>
@endsection
